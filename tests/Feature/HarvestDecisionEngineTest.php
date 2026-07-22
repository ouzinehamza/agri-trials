<?php
namespace Tests\Feature;

use App\Domain\Harvests\HarvestAggregationService;
use App\Models\{Decision,Measurement,Trial,User,Workspace};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HarvestDecisionEngineTest extends TestCase
{
    use RefreshDatabase;

    private function setupTrial(string $code='P-H1',string $site='Agadir'): array
    {
        $admin=User::factory()->create(['role'=>'admin']);$workspace=Workspace::create(['name'=>'Trials']);$measurement=Measurement::create(['name'=>['fr'=>'Rendement'],'code'=>'YLD-'.$code,'unit'=>'kg','data_type'=>'number','aggregation'=>'sum','direction'=>'up','active'=>true]);$trial=Trial::create(['workspace_id'=>$workspace->id,'code'=>$code,'variety'=>'Melon X','culture'=>'Melon','site'=>$site,'season'=>'2026','controls'=>['Control A'],'measurement_snapshot'=>[['id'=>$measurement->id,'code'=>$measurement->code,'name'=>$measurement->name,'unit'=>'kg','data_type'=>'number','aggregation'=>'sum','direction'=>'up','weight'=>100]]]);return [$admin,$workspace,$measurement,$trial];
    }

    public function test_repeatable_harvests_aggregate_by_catalog_rule_and_compare_control(): void
    {
        [$admin,,$measurement,$trial]=$this->setupTrial();foreach([[10,8],[12,9]] as[$value,$control])$this->actingAs($admin)->post("/trials/{$trial->id}/harvests",['harvested_on'=>'2026-07-19','values'=>[['measurement_id'=>$measurement->id,'subject_type'=>'trial','subject_label'=>'Melon X','value'=>$value],['measurement_id'=>$measurement->id,'subject_type'=>'control','subject_label'=>'Control A','value'=>$control]]])->assertRedirect();$row=app(HarvestAggregationService::class)->trial($trial->fresh())[0];$this->assertSame(22.0,$row['essai']);$this->assertSame(17.0,$row['temoin']);$this->assertTrue($row['beats_control']);$this->assertDatabaseCount('harvests',2);
    }

    public function test_viewer_cannot_capture_harvests_outside_write_roles(): void
    {
        [, $workspace,$measurement,$trial]=$this->setupTrial();$viewer=User::factory()->create(['role'=>'viewer']);$viewer->workspaces()->attach($workspace->id,['role'=>'viewer']);$this->actingAs($viewer)->post("/trials/{$trial->id}/harvests",['harvested_on'=>'2026-07-19','values'=>[['measurement_id'=>$measurement->id,'subject_type'=>'trial','subject_label'=>'Melon X','value'=>1]]])->assertForbidden();
    }

    public function test_admin_decision_is_snapshotted_and_immutable(): void
    {
        [$admin,,$measurement,$trial]=$this->setupTrial();$this->actingAs($admin)->post("/trials/{$trial->id}/harvests",['harvested_on'=>'2026-07-19','values'=>[['measurement_id'=>$measurement->id,'subject_type'=>'trial','subject_label'=>'Melon X','value'=>12],['measurement_id'=>$measurement->id,'subject_type'=>'control','subject_label'=>'Control A','value'=>10]]]);$this->actingAs($admin)->post("/trials/{$trial->id}/decision",['verdict'=>'launch','justification'=>'Performance supérieure','weights'=>[$measurement->code=>100]])->assertRedirect();$decision=Decision::firstOrFail();$this->assertSame('launch',$decision->verdict);$this->assertSame([$trial->harvests()->first()->id],$decision->context_snapshot['harvest_ids']);$this->expectException(\LogicException::class);$decision->update(['justification'=>'Changed']);
    }

    public function test_variety_decision_aggregates_trials_across_sites_and_snapshots_contributors(): void
    {
        [$admin,,$measurement,$first]=$this->setupTrial('P-V1','Agadir');$workspace=$first->workspace_id;$second=Trial::create(['workspace_id'=>$workspace,'code'=>'P-V2','variety'=>'Melon X','culture'=>'Melon','site'=>'Marrakech','season'=>'2027','controls'=>['Control A'],'measurement_snapshot'=>$first->measurement_snapshot]);foreach([[$first,12,10],[$second,14,11]] as[$trial,$value,$control])$this->actingAs($admin)->post("/trials/{$trial->id}/harvests",['harvested_on'=>'2026-07-19','values'=>[['measurement_id'=>$measurement->id,'subject_type'=>'trial','subject_label'=>'Melon X','value'=>$value],['measurement_id'=>$measurement->id,'subject_type'=>'control','subject_label'=>'Control A','value'=>$control]]]);$this->actingAs($admin)->post('/varieties/Melon%20X/decision',['verdict'=>'launch','justification'=>'Stable multi-sites','weights'=>[$measurement->code=>100]])->assertRedirect();$decision=Decision::where('level','variety')->firstOrFail();$this->assertCount(2,$decision->context_snapshot['trial_ids']);$this->assertEqualsCanonicalizing(['Agadir','Marrakech'],$decision->context_snapshot['sites']);
    }
}
