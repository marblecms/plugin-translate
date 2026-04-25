<?php

use Illuminate\Support\Facades\Route;
use MarbleCms\Translate\Http\Controllers\Admin\TranslateController;

Route::prefix('translate')->group(function () {
    Route::get('',                     [TranslateController::class, 'index'])->name('translate.index');
    Route::get('item/{item}',          [TranslateController::class, 'show'])->name('translate.show');
    Route::post('item/{item}',         [TranslateController::class, 'translate'])->name('translate.translate');
    Route::post('job/{job}/apply',     [TranslateController::class, 'apply'])->name('translate.apply');
    Route::post('job/{job}/reject',    [TranslateController::class, 'reject'])->name('translate.reject');
});
