<?php
// routes/web.php
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/health', \App\Http\Controllers\HealthController::class);
Route::get('/test', function () {
    return 'Laravel kjører som det skal 🚀';
});
Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);
Route::redirect('/', '/projects');

Route::resource('customers', \App\Http\Controllers\CustomerController::class);
Route::resource('projects',  \App\Http\Controllers\ProjectController::class);
Route::get('/projects/{project}/findings', [\App\Http\Controllers\ProjectController::class, 'findings'])
    ->name('projects.findings');
Route::get('/projects/{project}/findings', [\App\Http\Controllers\ProjectController::class, 'findings'])
    ->name('projects.findings');

Route::post('/projects/{project}/findings', [\App\Http\Controllers\ProjectController::class, 'saveFindings'])
    ->name('projects.findings.save');

Route::get('/projects/{project}/report/preview', [\App\Http\Controllers\ProjectController::class, 'reportPreview'])
    ->name('projects.report.preview');
Route::get('/projects/{project}/report/pdf', [\App\Http\Controllers\ProjectController::class, 'reportPdf'])
    ->name('projects.report.pdf');
Route::get('/settings/company', [\App\Http\Controllers\SettingsController::class, 'company'])->name('settings.company');
Route::post('/settings/company', [\App\Http\Controllers\SettingsController::class, 'companySave'])->name('settings.company.save');


Route::resource('sections', SectionController::class)->except(['show']);
Route::resource('blocks',   BlockController::class)->except(['show']);


Route::resource('templates', TemplateController::class);
Route::post('/templates/{template}/sync', [TemplateController::class, 'sync'])->name('templates.sync');
Route::post('/projects/{project}/apply-template', [\App\Http\Controllers\ProjectController::class, 'applyTemplate'])
    ->name('projects.applyTemplate');

Route::resource('templates', TemplateController::class);

// routes/web.php
Route::post('/projects/{project}/apply-template', [\App\Http\Controllers\ProjectController::class, 'applyTemplate'])
    ->name('projects.applyTemplate');
Route::post('/templates/{template}/structure', [TemplateController::class, 'saveStructure'])
    ->name('templates.saveStructure');

Route::post('/projects/{project}/duplicate', [ProjectController::class, 'duplicate'])
    ->name('projects.duplicate');

use Illuminate\Http\Request;

// NYE RUTER FOR PDF-INNSTILLINGER
Route::get('/settings/pdf', [SettingsController::class, 'pdf'])->name('settings.pdf');
Route::post('/settings/pdf', [SettingsController::class, 'savePdf'])->name('settings.pdf.save');

Route::post('/blocks/reorder', [BlockController::class, 'reorder'])->name('blocks.reorder');
Route::post('/sections/reorder', [SectionController::class, 'reorder'])->name('sections.reorder');