<?php

use App\Http\Controllers\AgentEarningsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentPlansController;
use App\Http\Controllers\PaymentTransactionsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyDeletionRequestController;
use App\Http\Controllers\PropertyReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureAdminRole;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', EnsureAdminRole::class])->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/search', SearchController::class)->name('search');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->whereNumber('id')->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{user}/properties', [UserController::class, 'properties'])->name('users.properties');
    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::post('/locations/states', [LocationController::class, 'storeState'])->name('locations.states.store');
    Route::put('/locations/states/{state}', [LocationController::class, 'updateState'])->name('locations.states.update');
    Route::delete('/locations/states/{state}', [LocationController::class, 'destroyState'])->name('locations.states.destroy');
    Route::post('/locations/cities', [LocationController::class, 'storeCity'])->name('locations.cities.store');
    Route::put('/locations/cities/{city}', [LocationController::class, 'updateCity'])->name('locations.cities.update');
    Route::delete('/locations/cities/{city}', [LocationController::class, 'destroyCity'])->name('locations.cities.destroy');
    Route::post('/locations/localities', [LocationController::class, 'storeLocality'])->name('locations.localities.store');
    Route::put('/locations/localities/{locality}', [LocationController::class, 'updateLocality'])->name('locations.localities.update');
    Route::delete('/locations/localities/{locality}', [LocationController::class, 'destroyLocality'])->name('locations.localities.destroy');
    Route::patch('payments-plans/{payments_plan}/toggle', [PaymentPlansController::class, 'toggle'])->name('payments-plans.toggle');
    Route::resource('payments-plans', PaymentPlansController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/payment-transactions', [PaymentTransactionsController::class, 'index'])->name('payment-transactions.index');
    Route::get('/payment-transactions/export', [PaymentTransactionsController::class, 'export'])->name('payment-transactions.export');
    Route::get('/agent-earnings', [AgentEarningsController::class, 'index'])->name('agent-earnings.index');
    Route::post('/agent-earnings/commissions', [AgentEarningsController::class, 'storeCommission'])->name('agent-earnings.commissions.store');
    Route::patch('/agent-earnings/commissions/{commission}/received', [AgentEarningsController::class, 'markReceived'])->name('agent-earnings.commissions.received');
    Route::patch('/agent-earnings/payouts/{payout}/processing', [AgentEarningsController::class, 'processPayout'])->name('agent-earnings.payouts.process');
    Route::patch('/agent-earnings/payouts/{payout}/paid', [AgentEarningsController::class, 'payPayout'])->name('agent-earnings.payouts.paid');
    Route::patch('/agent-earnings/payouts/{payout}/reject', [AgentEarningsController::class, 'rejectPayout'])->name('agent-earnings.payouts.reject');
    Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('/properties/{item}', [PropertyController::class, 'show'])->where('item', '(listing|project)-[0-9]+')->name('properties.show');
    // Operations' reviewed submissions are approved (or queried) right in Properties
    Route::patch('/properties/reviews/{review}/approve', [PropertyReviewController::class, 'approve'])->name('properties.reviews.approve');
    Route::patch('/properties/reviews/{review}/query', [PropertyReviewController::class, 'query'])->name('properties.reviews.query');
    Route::redirect('/approvals', '/properties?status=pending');
    Route::redirect('/ops-approvals', '/properties?status=pending');
    // builder projects: view-only tab in Properties (BRM reviews them)
    Route::redirect('/projects', '/properties?status=builder');
    Route::redirect('/builder-projects', '/properties?status=builder');
    Route::get('/deletion-requests', [PropertyDeletionRequestController::class, 'index'])->name('deletion-requests.index');
    Route::patch('/deletion-requests/{propertyDeletionRequest}/approve', [PropertyDeletionRequestController::class, 'approve'])->name('deletion-requests.approve');
    Route::patch('/deletion-requests/{propertyDeletionRequest}/reject', [PropertyDeletionRequestController::class, 'reject'])->name('deletion-requests.reject');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::get('/leads-bargains', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/{ticket}', [ComplaintController::class, 'show'])->whereNumber('ticket')->name('complaints.show');
    Route::post('/complaints/{ticket}/reply', [ComplaintController::class, 'reply'])->whereNumber('ticket')->name('complaints.reply');
    Route::patch('/complaints/{ticket}', [ComplaintController::class, 'update'])->whereNumber('ticket')->name('complaints.update');
    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::post('/blogs/editor-image', [BlogController::class, 'uploadEditorImage'])->name('blogs.editor-image');
    Route::resource('blogs', BlogController::class)->except(['show']);
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::post('/settings/team', [SettingsController::class, 'storeTeamMember'])->name('settings.team.store');
    Route::patch('/settings/team/{user}/toggle', [SettingsController::class, 'toggleTeamMember'])->name('settings.team.toggle');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

// Forgot password: email a one-time reset link (sent through the shared CMNHousing Gmail account).
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});
