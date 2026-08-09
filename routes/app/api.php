<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
  require_once __DIR__ . '/v1/attendance/attendance.php';
  require_once __DIR__ . '/v1/daily_order_target/daily_order_target.php';
  require_once __DIR__ . '/v1/diagnostics/diagnostics.php';
  require_once __DIR__ . '/v1/expense/expense.php';
  require_once __DIR__ . '/v1/itinerary/itinerary.php';
  require_once __DIR__ . '/v1/leave/leave.php';
  require_once __DIR__ . '/v1/market_intelligence/market_intelligence.php';
  require_once __DIR__ . '/v1/mom/mom.php';
  require_once __DIR__ . '/v1/outlet_visited/outlet_visited.php';
  require_once __DIR__ . '/v1/usage/usage.php';
});
