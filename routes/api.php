<?php

use App\Http\Controllers\Controller;
use App\Http\Controllers\JasperController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

require_once __DIR__ . '/auth/auth.php';



Route::group(['middleware' => ['auth:sanctum']], function() {

    Route::get('jasper/report/{name}/{ext?}', [JasperController::class, 'report']);

    require_once __DIR__ . '/employee/employee.php';
    require_once __DIR__ . '/leave/leave.php';
    require_once __DIR__ . '/asset/asset.php';
    require_once __DIR__ . '/attendance/attendance.php';
    require_once __DIR__ . '/mail-pdf/mail-pdf.php';
    require_once __DIR__ . '/expense/expense.php';
    require_once __DIR__ . '/workspace/note.php';
    require_once __DIR__ . '/payroll/salary.php';

});
