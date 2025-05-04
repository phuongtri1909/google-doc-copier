<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CopyJobController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\FolderPickerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LicenseKeyController;

// Auth routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');

// License routes
Route::get('/license/verify', [App\Http\Controllers\LicenseKeyController::class, 'verify'])->name('license.verify')->middleware('auth');
Route::post('/license/activate', [App\Http\Controllers\LicenseKeyController::class, 'activate'])->name('license.activate')->middleware('auth');

// Jobs routes
Route::get('/jobs', [CopyJobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/create', [CopyJobController::class, 'create'])->name('jobs.create');
Route::post('/jobs', [CopyJobController::class, 'store'])->name('jobs.store');
Route::post('/jobs/{job}/process', [CopyJobController::class, 'processJob'])->name('jobs.process');
Route::get('/jobs/progress', [CopyJobController::class, 'getProgress'])->name('jobs.progress');

// Folder Picker routes
Route::get('/folders/picker', [FolderPickerController::class, 'showPicker'])->name('folders.picker');
Route::post('/folders/create', [FolderPickerController::class, 'createFolder'])->name('folders.create');

// Admin routes - protected by admin middleware
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // License management routes
    Route::resource('license-keys', LicenseKeyController::class);
    Route::post('/license-keys/{licenseKey}/toggle', [LicenseKeyController::class, 'toggleStatus'])->name('license-keys.toggle');
    
    // User management routes
    Route::resource('users', UserController::class);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/generate-license', [UserController::class, 'generateLicense'])->name('users.generate-license');
    Route::post('/users/{user}/licenses/{licenseKey}/remove', [UserController::class, 'removeLicense'])->name('users.remove-license');

    Route::post('/license-keys/batch-activate', [LicenseKeyController::class, 'batchActivate'])->name('license-keys.batch-activate');
    Route::post('/license-keys/batch-deactivate', [LicenseKeyController::class, 'batchDeactivate'])->name('license-keys.batch-deactivate');
    Route::post('/license-keys/batch-delete', [LicenseKeyController::class, 'batchDelete'])->name('license-keys.batch-delete');
});