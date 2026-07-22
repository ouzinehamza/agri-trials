<?php

namespace Tests\Feature;

use App\Domain\Stock\StockService;
use App\Models\{StockItem,Trial,TrialStageRecord,User,WorkflowStage,WorkflowTemplate,Workspace};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_receives_lot_and_balance_is_derived_from_immutable_movements(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $item=StockItem::create(['name'=>'Melon X','kind'=>'variety','unit'=>'graines','alert_threshold'=>10]);
        $this->actingAs($admin)->post("/stock/{$item->id}/lots",['code'=>'LOT-X','received_on'=>'2026-07-19','initial_quantity'=>100])->assertRedirect();
        $lot=$item->lots()->firstOrFail();
        $this->actingAs($admin)->post("/stock/{$item->id}/movements",['stock_lot_id'=>$lot->id,'type'=>'out','quantity'=>35,'moved_on'=>'2026-07-19','reason'=>'allocation'])->assertRedirect();
        $this->assertSame(65,$item->fresh()->currentStock());
        $this->assertDatabaseCount('stock_movements',2);
    }

    public function test_stock_cannot_be_consumed_below_available_lot_balance(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);$item=StockItem::create(['name'=>'Control A','kind'=>'control','unit'=>'graines','alert_threshold'=>5]);$lot=$item->lots()->create(['code'=>'LOT-A']);
        app(StockService::class)->record($item,['stock_lot_id'=>$lot->id,'type'=>'in','quantity'=>10,'moved_on'=>'2026-07-19','reason'=>'achat'], $admin->id);
        $this->actingAs($admin)->post("/stock/{$item->id}/movements",['stock_lot_id'=>$lot->id,'type'=>'out','quantity'=>11,'moved_on'=>'2026-07-19','reason'=>'allocation'])->assertSessionHasErrors('quantity');
        $this->assertSame(10,$lot->fresh()->balance());
    }

    public function test_partner_cannot_view_or_manage_company_stock(): void
    {
        $partner=User::factory()->create(['role'=>'partner']);
        $this->actingAs($partner)->get('/stock')->assertForbidden();
        $this->actingAs($partner)->post('/stock',['name'=>'Forbidden','kind'=>'variety','unit'=>'graines','alert_threshold'=>1])->assertForbidden();
    }

    public function test_completing_sowing_consumes_each_lot_once(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $workspace=Workspace::create(['name'=>'W']);
        $trial=Trial::create(['workspace_id'=>$workspace->id,'code'=>'P-STOCK','variety'=>'V','culture'=>'Melon','current_stage'=>'semis']);
        $workflow=WorkflowTemplate::create(['name'=>['fr'=>'Stock test'],'description'=>[]]);
        $stage=WorkflowStage::create(['workflow_template_id'=>$workflow->id,'key'=>'semis','name'=>['fr'=>'Semis'],'sort_order'=>0,'config'=>[]]);
        $record=TrialStageRecord::create(['trial_id'=>$trial->id,'workflow_stage_id'=>$stage->id,'stage_key'=>'semis','stage_name'=>['fr'=>'Semis'],'sort_order'=>0,'status'=>'active','data'=>[]]);
        $item=StockItem::create(['name'=>'V','kind'=>'variety','unit'=>'graines','alert_threshold'=>5]);
        $lot=$item->lots()->create(['code'=>'LOT-V']);
        app(StockService::class)->record($item,['stock_lot_id'=>$lot->id,'type'=>'in','quantity'=>50,'moved_on'=>'2026-07-19','reason'=>'achat'],$admin->id);
        $record->update(['data'=>['stock_allocations'=>[['stock_lot_id'=>$lot->id,'quantity'=>12]]]]);
        app(StockService::class)->consumeSowing($trial,$record->fresh(),$admin->id);
        app(StockService::class)->consumeSowing($trial,$record->fresh(),$admin->id);
        $this->assertSame(38,$lot->fresh()->balance());
        $this->assertDatabaseCount('stock_movements',2);
    }

    public function test_admin_can_round_trip_stock_with_csv_and_xlsx(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $csv="article,type,reference,unite,seuil_alerte,lot,fournisseur,recu_le,peremption,germination_pct,purete_pct,dernier_test_germination,emplacement,quantite_initiale\nImported V,variety,V-01,graines,8,LOT-IMP,Supplier,2026-07-19,2027-01-01,95,99,2026-07-01,Magasin B,75\n";
        $this->actingAs($admin)->post('/stock-import',['file'=>UploadedFile::fake()->createWithContent('stock.csv',$csv)])->assertRedirect();
        $this->assertDatabaseHas('stock_lots',['code'=>'LOT-IMP']);
        $this->assertSame(75,StockItem::where('name','Imported V')->firstOrFail()->currentStock());
        $this->actingAs($admin)->get('/stock-export.xlsx')->assertOk()->assertHeader('content-disposition');
    }
}
