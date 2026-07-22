<?php

use App\Http\Controllers\BrandingController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferentielController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\HarvestController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MediaManagerController;
use App\Http\Controllers\TrialController;
use App\Http\Controllers\TrialReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VarietyController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkflowConfigurationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/metadata/references/{modelType}', [ConfigurationController::class, 'referenceOptions'])->name('metadata.references');
    Route::get('/media', [MediaManagerController::class, 'index'])->name('media.index');
    Route::get('/media/options', [MediaManagerController::class, 'options'])->name('media.options');
    Route::post('/media', [MediaManagerController::class, 'store'])->name('media.store');
    Route::patch('/media/{mediaAsset}', [MediaManagerController::class, 'update'])->name('media.update');
    Route::delete('/media/{mediaAsset}', [MediaManagerController::class, 'destroy'])->name('media.destroy');
    Route::get('/media/file/{media}', [MediaManagerController::class, 'file'])->name('media.file');

    Route::get('/trials', [TrialController::class, 'index'])->name('trials.index');
    Route::post('/trials', [TrialController::class, 'store'])->name('trials.store');
    Route::get('/trials/{trial}', [TrialController::class, 'show'])->name('trials.show');
    Route::put('/trials/{trial}/stages/{stageRecord}', [TrialController::class, 'saveStage'])->name('trials.stages.save');
    Route::post('/trials/{trial}/stages/{stageRecord}/advance', [TrialController::class, 'advanceStage'])->name('trials.stages.advance');
    Route::post('/trials/{trial}/stages/{stageRecord}/reopen', [TrialController::class, 'reopenStage'])->name('trials.stages.reopen');
    Route::post('/trials/{trial}/notes', [TrialController::class, 'storeNote'])->name('trials.notes.store');
    Route::put('/trials/{trial}/assignees', [TrialController::class, 'assign'])->name('trials.assignees');
    Route::get('/trials/{trial}/harvests', [HarvestController::class, 'index'])->name('trials.harvests.index');
    Route::post('/trials/{trial}/harvests', [HarvestController::class, 'store'])->name('trials.harvests.store');
    Route::get('/trials/{trial}/harvests/export', [HarvestController::class, 'export'])->name('trials.harvests.export');
    Route::get('/trials/{trial}/decision', [TrialController::class, 'decision'])->name('trials.decision');
    Route::post('/trials/{trial}/decision', [TrialController::class, 'storeDecision'])->name('trials.decision.store');
    Route::get('/trials/{trial}/report', [TrialReportController::class, 'show'])->name('trials.report');
    Route::get('/trials/{trial}/report.xlsx', [TrialReportController::class, 'excel'])->name('trials.report.xlsx');
    Route::get('/trials/{trial}/report.pdf', [TrialReportController::class, 'pdf'])->name('trials.report.pdf');
    Route::get('/varieties/{variety}/decision', [VarietyController::class, 'decision'])->name('varieties.decision');
    Route::post('/varieties/{variety}/decision', [VarietyController::class, 'storeDecision'])->name('varieties.decision.store');

    // Référentiels — generic, metadata-driven (custom fields + dynamic form/table + import) for any entity.
    Route::get('/referentiels/{slug}', [ReferentielController::class, 'index'])->name('referentiels.index');
    Route::post('/referentiels/{slug}', [ReferentielController::class, 'store'])->name('referentiels.store');
    Route::put('/referentiels/{slug}/{id}', [ReferentielController::class, 'update'])->name('referentiels.update');
    Route::delete('/referentiels/{slug}/{id}', [ReferentielController::class, 'destroy'])->name('referentiels.destroy');
    Route::post('/referentiels/{slug}/import/preview', [ImportController::class, 'preview'])->name('referentiels.import.preview');
    Route::post('/referentiels/{slug}/import/{importJob}/commit', [ImportController::class, 'commit'])->name('referentiels.import.commit');
    Route::get('/import-jobs/{importJob}', [ImportController::class, 'status'])->name('import-jobs.status');
    Route::get('/referentiels/{slug}/modele', [ReferentielController::class, 'template'])->name('referentiels.template');

    // Stock — items with derived current stock (purchased − consumed) + movements.
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::post('/stock', [StockController::class, 'storeItem'])->name('stock.items.store');
    Route::post('/stock/{stockItem}/lots', [StockController::class, 'storeLot'])->name('stock.lots.store');
    Route::post('/stock/{stockItem}/movements', [StockController::class, 'storeMovement'])->name('stock.movements.store');
    Route::get('/stock-export', [StockController::class, 'export'])->name('stock.export');
    Route::get('/stock-export.xlsx', [StockController::class, 'exportXlsx'])->name('stock.export.xlsx');
    Route::post('/stock-import', [StockController::class, 'import'])->name('stock.import');

    // Charges & factures (expenses + third-party invoices).
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses/charges', [ExpenseController::class, 'storeExpense'])->name('expenses.charges.store');
    Route::post('/expenses/factures', [ExpenseController::class, 'storeInvoice'])->name('expenses.invoices.store');
    Route::patch('/expenses/factures/{invoice}/statut', [ExpenseController::class, 'updateInvoiceStatus'])->name('expenses.invoices.status');

    // Admin-only: config, users, branding, workspaces (SPEC §5 — only Admin edits config).
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');

        Route::get('/configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
        Route::post('/configuration/fields', [ConfigurationController::class, 'storeField'])->name('configuration.fields.store');
        Route::patch('/configuration/fields/{fieldDefinition}', [ConfigurationController::class, 'updateField'])->name('configuration.fields.update');
        Route::delete('/configuration/fields/{fieldDefinition}', [ConfigurationController::class, 'destroyField'])->name('configuration.fields.destroy');
        Route::get('/configuration/workflows', [WorkflowConfigurationController::class, 'index'])->name('configuration.workflows');
        Route::post('/configuration/measurements', [WorkflowConfigurationController::class, 'storeMeasurement']);
        Route::patch('/configuration/measurements/{measurement}', [WorkflowConfigurationController::class, 'updateMeasurement']);
        Route::post('/configuration/measurement-sets', [WorkflowConfigurationController::class, 'storeSet']);
        Route::patch('/configuration/measurement-sets/{measurementSet}', [WorkflowConfigurationController::class, 'updateSet']);
        Route::post('/configuration/workflow-templates', [WorkflowConfigurationController::class, 'storeWorkflow']);
        Route::patch('/configuration/workflow-templates/{workflowTemplate}', [WorkflowConfigurationController::class, 'updateWorkflow']);
        Route::post('/configuration/workflow-templates/{workflowTemplate}/duplicate', [WorkflowConfigurationController::class, 'duplicateWorkflow']);
        Route::patch('/configuration/workflow-templates/{workflowTemplate}/archive', [WorkflowConfigurationController::class, 'archiveWorkflow']);
        Route::post('/configuration/workflow-templates/{workflowTemplate}/stages', [WorkflowConfigurationController::class, 'storeStage']);
        Route::patch('/configuration/workflow-stages/{workflowStage}', [WorkflowConfigurationController::class, 'updateStage']);
        Route::patch('/configuration/workflow-templates/{workflowTemplate}/reorder', [WorkflowConfigurationController::class, 'reorder']);
        Route::delete('/configuration/workflow-stages/{workflowStage}', [WorkflowConfigurationController::class, 'destroyStage']);
        Route::post('/configuration/trial-templates', [WorkflowConfigurationController::class, 'storeTrialTemplate']);
        Route::patch('/configuration/trial-templates/{trialTemplate}', [WorkflowConfigurationController::class, 'updateTrialTemplate']);
        Route::post('/configuration/trial-templates/{trialTemplate}/duplicate', [WorkflowConfigurationController::class, 'duplicateTrialTemplate']);
        Route::patch('/configuration/trial-templates/{trialTemplate}/archive', [WorkflowConfigurationController::class, 'archiveTrialTemplate']);

        Route::get('/branding', [BrandingController::class, 'index'])->name('branding.index');
        Route::post('/branding', [BrandingController::class, 'update'])->name('branding.update');

        Route::get('/audit', [\App\Http\Controllers\AuditController::class, 'index'])->name('audit.index');

        Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
        Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
        Route::post('/workspaces/{workspace}/members', [WorkspaceController::class, 'addMember'])->name('workspaces.members.add');
        Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'removeMember'])->name('workspaces.members.remove');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
