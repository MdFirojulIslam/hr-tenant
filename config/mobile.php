<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile Sanctum idle session (rolling)
    |--------------------------------------------------------------------------
    | ArtisanHRIS-Mobile tokens remain valid while last_used_at (or created_at
    | if never used) is within this many days. Enforced by MobileTokenIdlePolicy
    | via Sanctum::authenticateAccessTokensUsing. Sanctum absolute expiration
    | must be null (see config/sanctum.php).
    */
    'session_idle_days' => 3,

    /*
    |--------------------------------------------------------------------------
    | Mobile API rate limit (requests / minute / user)
    |--------------------------------------------------------------------------
    | Applied to api/mobile/* via MobileApiRateLimiter (overrides throttle:api
    | for those paths only). Default Laravel api limiter is 60/min.
    */
    'api_rate_limit_per_minute' => (int) env('MOBILE_API_RATE_LIMIT_PER_MINUTE', 180),

    /*
    |--------------------------------------------------------------------------
    | Mobile-only navigation IDs
    |--------------------------------------------------------------------------
    | These remain available to Flutter via login permissions/modules, but must
    | NOT appear in the website sidebar (no Vue page path yet / mobile-first).
    */
    'mobile_only_navigation_ids' => [
        66, // Daily Order Target
        67, // Market Intelligence
        70, // Outlet Visit mobile leaf (child of parent 68)
        71, // MoM (Minutes of Meeting)
        73, // Task Board mobile
        75, // Events mobile
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile App Page Permissions (navigation IDs from navigations table)
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'attendance' => 32,      // Online Attendance / FieldForceAttendance
        'leave'      => 15,      // Leave Application
        'expense'    => 28,      // Expense Form
        'expense_records' => 29, // Expense Records
        'itinerary' => 47,       // Monthly Itinerary plan
        'itinerary_daily' => 48, // Daily Itinerary (ACTUAL)
        'itinerary_admin' => 50, // Admin / supervisor list (phase 2)
        'daily_order_target' => 66, // Daily Order Target
        'market_intelligence' => 67, // Market Intelligence
        // 68 = parent nav group "Outlet Visit" (not a Flutter page gate)
        'outlet_visited_admin' => 69, // Outlet Visit Setup (web, child of 68)
        'outlet_visited' => 70, // Outlet Visit mobile (child of 68)
        'mom' => 71, // Minutes of Meeting (mobile)
        'vehicle_requisition' => 38, // Vehicle Requisition (existing website page)
        'task_board_admin' => 72, // Task Board website assign panel
        'task_board' => 73, // Task Board mobile
        'event_setup' => 74, // Event Setup website
        // Registered only. Mobile Event is auth-only — do NOT apply mobile.permission:events.
        'events' => 75,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile module definitions returned to the app after login
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'attendance' => [
            'id' => 32,
            'name' => 'Attendance',
            'icon' => 'fingerprint',
        ],
        'leave' => [
            'id' => 15,
            'name' => 'Leave',
            'icon' => 'event_busy',
        ],
        'expense' => [
            'id' => 28,
            'name' => 'Expense',
            'icon' => 'payments',
        ],
        'itinerary' => [
            'id' => 47,
            'name' => 'Itinerary',
            'icon' => 'map',
        ],
        'itinerary_daily' => [
            'id' => 48,
            'name' => 'Daily Itinerary',
            'icon' => 'today',
        ],
        'itinerary_admin' => [
            'id' => 50,
            'name' => 'Approve Itinerary',
            'icon' => 'verified',
        ],
        'daily_order_target' => [
            'id' => 66,
            'name' => 'Daily Order Target',
            'icon' => 'track_changes',
        ],
        'market_intelligence' => [
            'id' => 67,
            'name' => 'Market Intelligence',
            'icon' => 'insights',
        ],
        'outlet_visited' => [
            'id' => 70,
            'name' => 'Outlet Visit',
            'icon' => 'store',
        ],
        'mom' => [
            'id' => 71,
            'name' => 'MoM',
            'icon' => 'note_alt',
        ],
        'vehicle_requisition' => [
            'id' => 38,
            'name' => 'Vehicle Requisition',
            'icon' => 'directions_car',
        ],
        'task_board' => [
            'id' => 73,
            'name' => 'Task Board',
            'icon' => 'task_alt',
        ],
        'events' => [
            'id' => 75,
            'name' => 'Events',
            'icon' => 'event',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Outlet Visit week-1 anchor
    |--------------------------------------------------------------------------
    | Canonical value lives on OutletVisitWeekCycle::ANCHOR_DATE (01/08/2026).
    | Env override is optional; do not hard-code the date a second time.
    */
    'outlet_visit_week1_anchor' => env(
        'OUTLET_VISIT_WEEK1_ANCHOR',
        \App\Support\OutletVisitWeekCycle::ANCHOR_DATE
    ),

];
