<?php

use App\Http\Controllers\Api\TaskApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.basic')->group(function (): void {
    Route::get('/tasks', [TaskApiController::class, 'index'])->name('api.tasks.index');
    Route::patch('/tasks/{task}/status', [TaskApiController::class, 'updateStatus'])->name('api.tasks.status');
});
