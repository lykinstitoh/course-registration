<?php

use App\Http\Controllers\Admin\ApplicationReviewController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DocumentReviewController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\Admin\IntakeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MpesaWebhookController;
use App\Http\Controllers\Student\ApplicationController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\DocumentController;
use App\Http\Controllers\Student\PaymentController;
use App\Http\Controllers\Student\RegistrationController;
use App\Http\Controllers\Student\ResultController;
use App\Http\Controllers\Student\TimetableController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('api/mpesa')->group(function () {
    Route::post('/stk-callback', [MpesaWebhookController::class, 'stkCallback']);
    Route::post('/c2b-confirmation', [MpesaWebhookController::class, 'c2bConfirmation']);
    Route::post('/c2b-validation', [MpesaWebhookController::class, 'c2bValidation']);
});

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/create', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/applications/{application}/letter', [ApplicationController::class, 'downloadLetter'])->name('applications.letter');
    Route::patch('/applications/{application}/cancel', [ApplicationController::class, 'cancel'])->name('applications.cancel');
    Route::get('/enrollment', [\App\Http\Controllers\Student\EnrollmentController::class, 'index'])->name('enrollment.index');
    Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('/registrations', [RegistrationController::class, 'store'])->name('registrations.store');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/initiate', [PaymentController::class, 'initiate'])->name('payments.initiate');
    Route::post('/payments/{payment}/status', [PaymentController::class, 'status'])->name('payments.status');
    Route::post('/payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable');
    Route::get('/results', [ResultController::class, 'index'])->name('results');
});

Route::middleware(['auth', 'role:registrar,finance,academic_staff,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/applications', [ApplicationReviewController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [ApplicationReviewController::class, 'show'])->name('applications.show');
    Route::post('/applications/{application}/review', [ApplicationReviewController::class, 'review'])->name('applications.review');
    Route::get('/intakes', [IntakeController::class, 'index'])->name('intakes.index');
    Route::post('/intakes', [IntakeController::class, 'store'])->name('intakes.store');
    Route::get('/fees', [FeeStructureController::class, 'index'])->name('fees.index');
    Route::post('/fees', [FeeStructureController::class, 'store'])->name('fees.store');
    Route::delete('/fees/{fee}', [FeeStructureController::class, 'destroy'])->name('fees.destroy');
    Route::get('/documents', [DocumentReviewController::class, 'index'])->name('documents.index');
    Route::post('/documents/{document}/review', [DocumentReviewController::class, 'review'])->name('documents.review');
    Route::get('/documents/{document}/download', [DocumentReviewController::class, 'download'])->name('documents.download'); 
    Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/review', [\App\Http\Controllers\Admin\PaymentController::class, 'review'])->name('payments.review');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    
    Route::resource('campuses', \App\Http\Controllers\Admin\CampusController::class);
    Route::resource('course-units', \App\Http\Controllers\Admin\CourseUnitController::class)->except(['show']);

    Route::get('/settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'update'])->name('settings.update');
});
