<?php
namespace Tests\Feature;
use App\Models\{Measurement,User,WorkflowTemplate};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowConfigurationTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_can_create_measurement_and_workflow_stage(): void
    {
        $admin=User::factory()->create(['role'=>'admin','email_verified_at'=>now()]);
        $this->actingAs($admin)->post('/configuration/measurements',['name'=>'Durée de conservation','code'=>'SHELF','unit'=>'jours','data_type'=>'number','aggregation'=>'mean','direction'=>'up','category'=>'Qualité','active'=>true])->assertRedirect();
        $this->assertDatabaseHas('measurements',['code'=>'SHELF','aggregation'=>'mean']);
        $workflow=WorkflowTemplate::create(['name'=>['fr'=>'Test'],'allow_trial_overrides'=>true]);
        $this->actingAs($admin)->post("/configuration/workflow-templates/{$workflow->id}/stages",['name'=>'Observation','key'=>'observation','required_to_advance'=>true,'measurement_ids'=>[Measurement::first()->id],'fields'=>[]])->assertRedirect();
        $this->assertDatabaseHas('workflow_stages',['workflow_template_id'=>$workflow->id,'key'=>'observation']);
    }
    public function test_non_admin_cannot_manage_configuration(): void
    {
        $user=User::factory()->create(['role'=>'viewer','email_verified_at'=>now()]);
        $this->actingAs($user)->get('/configuration/workflows')->assertForbidden();
    }
}
