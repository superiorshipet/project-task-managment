<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFavoriteController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\ProjectInvitationController;
use App\Http\Controllers\ProjectMessageController;
use App\Http\Controllers\ProjectWhiteboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::delete('/team/{user}', [TeamController::class, 'destroy'])->name('team.destroy');
    Route::delete('/projects/{project}/users/{user}', [TeamController::class, 'removeFromProject'])->name('projects.users.destroy');
    Route::post('/projects/{project}/invitations', [ProjectInvitationController::class, 'store'])->name('projects.invitations.store');
    Route::post('/projects/{project}/files', [ProjectFileController::class, 'store'])->name('projects.files.store');
    Route::delete('/projects/{project}/files/{file}', [ProjectFileController::class, 'destroy'])->name('projects.files.destroy');
    Route::get('/projects/{project}/messages', [ProjectMessageController::class, 'index'])->name('projects.messages.index');
    Route::post('/projects/{project}/messages', [ProjectMessageController::class, 'store'])->name('projects.messages.store');
    Route::match(['get', 'patch'], '/project-invitations/{invitation}/accept', [ProjectInvitationController::class, 'accept'])->name('project-invitations.accept');

    Route::resource('projects', ProjectController::class);
    Route::get('/projects/{project}/whiteboard', [ProjectWhiteboardController::class, 'show'])->name('projects.whiteboard.show');
    Route::get('/projects/{project}/whiteboard/sync', [ProjectWhiteboardController::class, 'sync'])->name('projects.whiteboard.sync');
    Route::put('/projects/{project}/whiteboard', [ProjectWhiteboardController::class, 'update'])->name('projects.whiteboard.update');
    Route::post('/projects/{project}/favorite', [ProjectFavoriteController::class, 'toggle'])->name('projects.favorite');
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{task}/restore', [TaskController::class, 'restore'])->name('tasks.restore');
});
