<?php

use Illuminate\Support\Facades\Route;
use Modules\HtmlGeneral\Http\Controllers\Admin\SettingsController;
use Modules\HtmlGeneral\Http\Controllers\Admin\HtmlBlockController;

/*
|--------------------------------------------------------------------------
| HtmlGeneral Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('modules/general/html-general')->name('admin.general.html.')->group(function () {
    // Module settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // HTML Blocks CRUD
    Route::get('/blocks', [HtmlBlockController::class, 'index'])->name('index');
    Route::get('/blocks/create', [HtmlBlockController::class, 'create'])->name('create');
    Route::post('/blocks', [HtmlBlockController::class, 'store'])->name('store');
    Route::get('/blocks/{htmlBlock}/edit', [HtmlBlockController::class, 'edit'])->name('edit');
    Route::put('/blocks/{htmlBlock}', [HtmlBlockController::class, 'update'])->name('update');
    Route::delete('/blocks/{htmlBlock}', [HtmlBlockController::class, 'destroy'])->name('destroy');
    Route::post('/blocks/{htmlBlock}/toggle', [HtmlBlockController::class, 'toggle'])->name('toggle');
});

// API endpoint for getting html blocks (for theme integration)
Route::get('/api/html-blocks', [HtmlBlockController::class, 'apiList'])->name('admin.api.html-blocks');