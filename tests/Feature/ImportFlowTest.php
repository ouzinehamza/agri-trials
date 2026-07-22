<?php
namespace Tests\Feature;

use App\Jobs\ProcessImportJob;
use App\Models\{FieldDefinition, ImportJob, Supplier, User, Variety};
use Database\Seeders\FieldDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** SPEC §3.6: two-step import — validated preview, then commit (inline or queued). */
class ImportFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FieldDefinitionSeeder::class);
    }

    private function user(): User
    {
        return User::factory()->create(['role' => 'manager', 'email_verified_at' => now()]);
    }

    private function csv(string $body): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('import.csv', $body);
    }

    public function test_preview_validates_without_persisting(): void
    {
        $csv = "name,code\nAcme,AC1\n,MISSINGNAME\n";
        $res = $this->actingAs($this->user())->post('/referentiels/fournisseurs/import/preview', ['file' => $this->csv($csv)]);

        $res->assertRedirect();
        $preview = session('import_preview');
        $this->assertSame(2, $preview['total']);
        $this->assertSame(1, $preview['valid']);
        $this->assertSame(1, $preview['invalid']);
        // Nothing persisted yet; a previewed ImportJob exists.
        $this->assertSame(0, Supplier::count());
        $this->assertDatabaseHas('import_jobs', ['id' => $preview['job_id'], 'status' => 'previewed']);
    }

    public function test_commit_persists_valid_rows_only(): void
    {
        $user = $this->user();
        $csv = "name,code\nAcme,AC1\n,BAD\n";
        $this->actingAs($user)->post('/referentiels/fournisseurs/import/preview', ['file' => $this->csv($csv)]);
        $jobId = session('import_preview')['job_id'];

        $res = $this->actingAs($user)->post("/referentiels/fournisseurs/import/{$jobId}/commit");
        $res->assertRedirect();

        $this->assertSame(1, Supplier::count());
        $this->assertSame('Acme', Supplier::first()->name);
        $this->assertDatabaseHas('import_jobs', ['id' => $jobId, 'status' => 'completed', 'imported' => 1, 'failed' => 1]);
    }

    public function test_reference_fields_resolve_by_label_and_reject_unknown(): void
    {
        FieldDefinition::create(['model_type' => 'variety', 'key' => 'suppliers', 'label' => 'Fournisseurs', 'type' => 'multiselect', 'settings' => ['option_source' => 'entity', 'reference_model' => 'supplier']]);
        $clause = Supplier::create(['name' => 'Clause', 'custom_data' => []]);
        $rz = Supplier::create(['name' => 'Rijk Zwaan', 'custom_data' => []]);
        $user = $this->user();

        // Row 1: two labels resolve; Row 2: an unknown label fails.
        $csv = "name,suppliers\nV1,Clause;Rijk Zwaan\nV2,Nonexistent Co\n";
        $this->actingAs($user)->post('/referentiels/varietes/import/preview', ['file' => $this->csv($csv)]);
        $preview = session('import_preview');
        $this->assertSame(1, $preview['valid']);
        $this->assertSame(1, $preview['invalid']);

        $this->actingAs($user)->post("/referentiels/varietes/import/{$preview['job_id']}/commit");
        $v1 = Variety::where('name', 'V1')->first();
        $this->assertNotNull($v1);
        $this->assertEqualsCanonicalizing([$clause->id, $rz->id], $v1->custom_data['suppliers']);
        $this->assertNull(Variety::where('name', 'V2')->first());
    }

    public function test_another_user_cannot_commit_someone_elses_preview(): void
    {
        $owner = $this->user();
        $this->actingAs($owner)->post('/referentiels/fournisseurs/import/preview', ['file' => $this->csv("name,code\nAcme,AC1\n")]);
        $jobId = session('import_preview')['job_id'];

        $intruder = $this->user();
        $this->actingAs($intruder)->post("/referentiels/fournisseurs/import/{$jobId}/commit")->assertForbidden();
        $this->actingAs($intruder)->get("/import-jobs/{$jobId}")->assertForbidden();
        $this->assertSame(0, Supplier::count());
    }

    public function test_large_file_is_committed_on_the_queue(): void
    {
        Queue::fake();
        $user = $this->user();
        $rows = collect(range(1, 600))->map(fn ($i) => "Sup{$i},C{$i}")->implode("\n");
        $this->actingAs($user)->post('/referentiels/fournisseurs/import/preview', ['file' => $this->csv("name,code\n{$rows}\n")]);
        $jobId = session('import_preview')['job_id'];

        $res = $this->actingAs($user)->post("/referentiels/fournisseurs/import/{$jobId}/commit");
        $res->assertRedirect();
        $this->assertSame(['queued' => true, 'job_id' => $jobId, 'total' => 600], session('import_result'));
        Queue::assertPushed(ProcessImportJob::class, fn ($job) => $job->importJobId === $jobId);
        $this->assertDatabaseHas('import_jobs', ['id' => $jobId, 'status' => 'processing']);
    }

    public function test_queued_job_commits_the_rows(): void
    {
        $user = $this->user();
        $rows = collect(range(1, 3))->map(fn ($i) => "Sup{$i},C{$i}")->implode("\n");
        $this->actingAs($user)->post('/referentiels/fournisseurs/import/preview', ['file' => $this->csv("name,code\n{$rows}\n")]);
        $jobId = session('import_preview')['job_id'];

        (new ProcessImportJob($jobId))->handle();

        $this->assertSame(3, Supplier::count());
        $this->assertDatabaseHas('import_jobs', ['id' => $jobId, 'status' => 'completed', 'imported' => 3]);
    }
}
