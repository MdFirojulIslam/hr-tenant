<?php

use App\Http\Controllers\Report\CertificateController;
use App\Http\Controllers\Report\ReportController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/link-storage', function () {
    $targetFolder = $_SERVER['DOCUMENT_ROOT'] . '/storage/app/public';
    $linkFolder = $_SERVER['DOCUMENT_ROOT'] . '/public/storage';
    if (file_exists($linkFolder)) {
        return 'The link already exists.';
    } else {
        if (symlink($targetFolder, $linkFolder)) {
            return 'Symbolic link created successfully.';
        } else {
            return 'Failed to create symbolic link. Please check permissions.';
        }
    }
});

Route::get('/jasperstarter', [CertificateController::class, 'jasperView']);


//Cettificate Report
Route::get('/certificate', [CertificateController::class, 'certificatePrint']);
Route::get('/noc/{id}', [CertificateController::class, 'getNOC']);
Route::get('/release/{id}', [CertificateController::class, 'getReleaseForm']);
Route::get('/salary-certificate/{id}', [CertificateController::class, 'getSalaryCertificate']);
Route::get('/salary-breakdown/{id}', [CertificateController::class, 'getSalaryBreakdown']);
Route::get('/request-bank/{id}', [CertificateController::class, 'reqBankAcc']);
Route::get('/separation-form/{id}', [CertificateController::class, 'getSeparationForm']);
Route::get('/salary-payslip/{id}', [CertificateController::class, 'getSalaryPayslip']);
Route::get('/certificate-of-income/{id}', [CertificateController::class, 'getCertificateOfIncome']);


//Leave
Route::get('/leave-report/{id}', [ReportController::class, 'leaveReport']);
Route::get('/app-attendance-report/{id}', [ReportController::class, 'appAttendanceReport']);
Route::get('/attendance-report/{id}', [ReportController::class, 'attendanceReport']);
Route::get('/holiday-report', [ReportController::class, 'holidayReport']);

//Expense report
Route::get('/expense-report', [ReportController::class, "getExpenseReport"]);

// Must stay ABOVE the /{any} SPA catch-all so it is reachable.
Route::get('/clear-caches', function () {
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');

    return 'Caches cleared (route, config, cache, view).';
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any','.*');
