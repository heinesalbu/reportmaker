<?php
// routes/web.php
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ProjectController;

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

Route::get('/debug-test', function (Request $request) {
    echo '<pre>';
    echo "=====================================================<br>";
    echo "LARAVEL DEBUG OUTPUT<br>";
    echo "=====================================================<br><br>";

    echo "<b>Request Scheme:</b> " . $request->getScheme() . "<br>";
    echo "<b>Is Secure Connection?:</b> " . ($request->isSecure() ? 'Yes' : 'No') . "<br><br>";

    echo "<b>Config APP_URL:</b> " . config('app.url') . "<br>";
    echo "<b>.env APP_URL:</b> " . env('APP_URL') . "<br><br>";

    echo "<b>Generated 'blocks.index' route:</b> " . route('blocks.index') . "<br>";
    echo "<b>Generated secure_url('/'):</b> " . secure_url('/') . "<br>";
    echo '</pre>';
});