<?php
namespace Tests\Feature;
use App\Models\{Trial,User,Workspace};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;
    private function data():array{$admin=User::factory()->create(['role'=>'admin']);$workspace=Workspace::create(['name'=>'North']);$trial=Trial::create(['workspace_id'=>$workspace->id,'code'=>'P-R1','variety'=>'Report V','culture'=>'Melon','site'=>'Agadir','season'=>'2026','current_stage'=>'evaluation','status'=>'Évaluation','controls'=>['Control']]);return[$admin,$workspace,$trial];}

    public function test_dashboard_is_scoped_and_filterable():void
    {
        [$admin,$workspace,$trial]=$this->data();$other=Workspace::create(['name'=>'South']);Trial::create(['workspace_id'=>$other->id,'code'=>'P-R2','variety'=>'Hidden','culture'=>'Tomate','site'=>'Marrakech','season'=>'2025']);$manager=User::factory()->create(['role'=>'manager']);$manager->workspaces()->attach($workspace->id,['role'=>'manager']);$this->actingAs($manager)->get('/dashboard?site=Agadir&season=2026')->assertOk()->assertInertia(fn(Assert $p)=>$p->component('Dashboard')->where('stats.active',1)->where('filters.site','Agadir')->has('options.workspaces',1));
    }

    public function test_authorized_user_can_open_and_export_trial_report():void
    {
        [$admin,,$trial]=$this->data();$this->actingAs($admin)->get("/trials/{$trial->id}/report")->assertOk()->assertInertia(fn(Assert $p)=>$p->component('Reports/Trial')->where('trial.code','P-R1'));
        $this->actingAs($admin)->get("/trials/{$trial->id}/report.xlsx")->assertOk()->assertHeader('content-disposition');
        $pdf=$this->actingAs($admin)->get("/trials/{$trial->id}/report.pdf")->assertOk()->assertHeader('content-type','application/pdf');$this->assertStringStartsWith('%PDF-',$pdf->getContent());
    }

    public function test_user_outside_workspace_cannot_access_trial_reports():void
    {
        [,,$trial]=$this->data();$viewer=User::factory()->create(['role'=>'viewer']);$this->actingAs($viewer)->get("/trials/{$trial->id}/report")->assertForbidden();$this->actingAs($viewer)->get("/trials/{$trial->id}/report.pdf")->assertForbidden();
    }
}
