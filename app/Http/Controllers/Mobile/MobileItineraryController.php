<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Itinerary\ItineraryController;
use App\Models\itineraries;
use App\Models\itinerary_logs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MobileItineraryController extends Controller
{
    /**
     * Town options for the logged-in field user.
     */
    public function towns()
    {
        $response = app(ItineraryController::class)->getOptions();

        return response()->json([
            'success' => true,
            'data' => json_decode($response->getContent(), true),
        ]);
    }

    /**
     * Monthly itinerary batches for the logged-in user.
     * Query: year, month
     */
    public function monthlyList(Request $request)
    {
        $userId = Auth::user()->EID;
        $year = $request->query('year', date('Y'));
        $month = $request->query('month', date('m'));

        $request->merge(['employee_id' => $userId]);
        $request->query->set('employee_id', $userId);
        $request->query->set('year', $year);
        $request->query->set('month', $month);

        $response = app(ItineraryController::class)->getItems($request);

        return response()->json([
            'success' => true,
            'data' => json_decode($response->getContent(), true),
        ]);
    }

    /**
     * Detail rows for one monthly batch (itinerary_logs.id).
     */
    public function show($id)
    {
        $userId = Auth::user()->EID;

        $log = itinerary_logs::where('id', $id)
            ->where('created_by', $userId)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found.',
            ], 404);
        }

        $response = app(ItineraryController::class)->getItemById($id);

        return response()->json([
            'success' => true,
            'data' => [
                'log' => $log,
                'rows' => json_decode($response->getContent(), true),
            ],
        ]);
    }

    /**
     * Create monthly plan (status: save).
     * Body: year, month, rows[{date, town, visitWith, location, objective, meetingWith, startTime, endTime}]
     */
    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required',
            'month' => 'required',
            'rows' => 'required|array|min:1',
            'rows.*.date' => 'required',
            'rows.*.town' => 'required',
        ]);

        return app(ItineraryController::class)->store($request);
    }

    /**
     * Update visit rows in an existing batch (save / rejected / pending flow).
     */
    public function update(Request $request, $id)
    {
        $userId = Auth::user()->EID;

        $log = itinerary_logs::where('id', $id)
            ->where('created_by', $userId)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found.',
            ], 404);
        }

        if (!in_array(strtolower((string) $log->status), ['save', 'rejected', 'pending', 'recheck'], true)
            && !in_array(strtoupper((string) $log->status), ['SAVE', 'REJECTED', 'PENDING', 'RECHECK'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This itinerary cannot be edited in its current status.',
            ], 400);
        }

        $request->validate([
            'rows' => 'required|array|min:1',
        ]);

        // Ensure log id is present on each row for the web update logic
        $rows = collect($request->input('rows'))->map(function ($row) use ($id, $log) {
            $row['itinerary_logs_id'] = $row['itinerary_logs_id'] ?? $id;
            $row['itinerary_status'] = $row['itinerary_status'] ?? strtoupper((string) $log->status);
            $row['marked'] = $row['marked'] ?? true;
            return $row;
        })->all();

        $request->merge(['rows' => $rows]);

        app(ItineraryController::class)->update($request, $id);

        return response()->json([
            'success' => true,
            'message' => 'Itinerary updated successfully.',
        ]);
    }

    /**
     * Submit monthly plan for supervisor approval (save → pending).
     */
    public function submit($id)
    {
        $userId = Auth::user()->EID;

        $log = itinerary_logs::where('id', $id)
            ->where('created_by', $userId)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found.',
            ], 404);
        }

        $status = strtolower((string) $log->status);
        if (!in_array($status, ['save', 'rejected'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only saved or rejected itineraries can be submitted.',
            ], 400);
        }

        $response = app(ItineraryController::class)->submit($id);
        $payload = json_decode($response->getContent(), true);

        return response()->json([
            'success' => true,
            'message' => $payload['message'] ?? 'Itinerary submitted successfully.',
        ], $response->getStatusCode());
    }

    /**
     * Today's planned / actual visits for the logged-in user.
     */
    public function today()
    {
        $response = app(ItineraryController::class)->getDailyItems();

        $data = $response
            ? json_decode($response->getContent(), true)
            : [];

        return response()->json([
            'success' => true,
            'data' => [
                'date' => Carbon::today()->toDateString(),
                'items' => $data ?? [],
            ],
        ]);
    }

    /**
     * Save / update today's ACTUAL visit.
     * Body matches web dailyItineraryUpdate:
     * itinerary_id, date, town, location, visitWith, objective, meetingWith, startTime, endTime, updateStatus
     */
    public function updateToday(Request $request)
    {
        $request->validate([
            'itinerary_id' => 'nullable',
            'date' => 'required|date',
            'town' => 'required',
        ]);

        $userId = Auth::user()->EID;

        // Ownership check when updating an existing plan row
        if ($request->filled('itinerary_id')) {
            $owns = itineraries::where('id', $request->itinerary_id)
                ->where('created_by', $userId)
                ->exists();

            $ownsUpdate = DB::table('itineraries_updates')
                ->where('itineraries_id', $request->itinerary_id)
                ->where('employee_id', $userId)
                ->exists();

            if (!$owns && !$ownsUpdate) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot update this itinerary row.',
                ], 403);
            }
        }

        app(ItineraryController::class)->dailyItineraryUpdate($request);

        return response()->json([
            'success' => true,
            'message' => 'Daily itinerary updated successfully.',
        ]);
    }

    /**
     * Supervisor: monthly batches for direct reports (excludes draft "save").
     * Query: year, month
     */
    public function supervisorMonthlyList(Request $request)
    {
        $supervisorId = Auth::user()->EID;
        $year = $request->query('year', date('Y'));
        $month = $request->query('month', date('m'));

        $items = DB::table('itinerary_logs')
            ->join('employees', 'employees.id', '=', 'itinerary_logs.created_by')
            ->join('officials', 'officials.EID', '=', 'itinerary_logs.created_by')
            ->leftJoin('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->select(
                'employees.id as employee_id',
                'officials.Supervisor_Id as supervisor_id',
                'employees.Full_Name as employee_name',
                'designations.Name as designation_name',
                'itinerary_logs.created_date as submitted_date',
                'itinerary_logs.status',
                'itinerary_logs.supervisor_remarks',
                'itinerary_logs.id as itinerary_logs_id'
            )
            ->where('officials.Supervisor_Id', $supervisorId)
            ->whereYear('itinerary_logs.created_date', $year)
            ->whereMonth('itinerary_logs.created_date', $month)
            ->whereRaw('LOWER(itinerary_logs.status) != ?', ['save'])
            ->orderByDesc('itinerary_logs.id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Supervisor: view a report's monthly batch detail.
     */
    public function supervisorShow($id)
    {
        $supervisorId = Auth::user()->EID;

        $log = DB::table('itinerary_logs')
            ->join('officials', 'officials.EID', '=', 'itinerary_logs.created_by')
            ->join('employees', 'employees.id', '=', 'itinerary_logs.created_by')
            ->select(
                'itinerary_logs.*',
                'employees.Full_Name as employee_name',
                'officials.Supervisor_Id as supervisor_id'
            )
            ->where('itinerary_logs.id', $id)
            ->where('officials.Supervisor_Id', $supervisorId)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found or not under your supervision.',
            ], 404);
        }

        $response = app(ItineraryController::class)->getItemById($id);

        return response()->json([
            'success' => true,
            'data' => [
                'log' => $log,
                'rows' => json_decode($response->getContent(), true),
            ],
        ]);
    }

    /**
     * Supervisor accept (pending → approved).
     */
    public function accept($id)
    {
        $check = $this->assertSupervisesLog($id);
        if ($check !== true) {
            return $check;
        }

        $response = app(ItineraryController::class)->accept($id);
        $payload = json_decode($response->getContent(), true);

        return response()->json([
            'success' => true,
            'message' => $payload['message'] ?? 'Itinerary accepted.',
        ], $response->getStatusCode());
    }

    /**
     * Supervisor reject (pending → rejected).
     * Body: remarks (optional)
     */
    public function reject(Request $request, $id)
    {
        $check = $this->assertSupervisesLog($id);
        if ($check !== true) {
            return $check;
        }

        $response = app(ItineraryController::class)->reject($request, $id);
        $payload = json_decode($response->getContent(), true);

        return response()->json([
            'success' => true,
            'message' => $payload['message'] ?? 'Itinerary rejected.',
        ], $response->getStatusCode());
    }

    /**
     * @return true|\Illuminate\Http\JsonResponse
     */
    private function assertSupervisesLog($id)
    {
        $supervisorId = Auth::user()->EID;

        $log = DB::table('itinerary_logs')
            ->join('officials', 'officials.EID', '=', 'itinerary_logs.created_by')
            ->where('itinerary_logs.id', $id)
            ->where('officials.Supervisor_Id', $supervisorId)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found or not under your supervision.',
            ], 404);
        }

        return true;
    }
}
