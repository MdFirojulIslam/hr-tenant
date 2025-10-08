<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\app_attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
 
class AppAttendanceController extends Controller
{
    public function insertAppAttendance(Request $request)
    {
        $request->validate([
            'eid' => 'required|integer',
            'image' => 'required|string',
            'location' => 'required|array',
            'location.lat' => 'required|numeric',
            'location.lng' => 'required|numeric',
            'timestamp' => 'required|date',
        ]);


        try {
            $date = date('Y-m-d', strtotime($request->timestamp)); // Extract the date from the timestamp

            // Check if the employee already has "in" or "out" attendance for the day
            $existingAttendance = app_attendance::where('EID', $request->eid)
                ->whereDate('created_at', $date)
                ->where('status', $request->status)
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance already recorded for the day.',
                ], 400);
            }

            // Decode Base64 Image
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->image));
            $imageName = 'attendance/' . uniqid() . '.png';

            // Store Image
            Storage::disk('public')->put($imageName, $imageData);

            // Get Location Name using Reverse Geocoding
             $locationName = $this->getLocationName($request->location['lat'], $request->location['lng']);

            // Save Attendance Record
            $attendance = app_attendance::create([
                'EID' => $request->eid,
                'town_id' => $request->town_id,
                'status' => $request->status,
                'image_path' => $imageName,
                'latitude' => $request->location['lat'],
                'longitude' => $request->location['lng'],
                'longitude' => $request->location['lng'],
                'village' => $locationName,
            ]);

            return response()->json(['success' => true, 'data' => $attendance], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function getLocationName($latitude, $longitude)
    {
        $apiKey = env('OPEN_CAGE_API_KEY');
        $url = "https://api.opencagedata.com/geocode/v1/json?q={$latitude}%2C{$longitude}&key={$apiKey}";

        try {
            $response = Http::get($url);
            
            if ($response->successful() && isset($response['results'][0]['formatted'])) {
                return $response['results'][0]['formatted'];
            }

            return $response;
        } catch (\Exception $e) {
            return 'Error fetching location';
        }
    }


    // public function fetchAttendanceImage($id){
    //     $in = app_attendance::where('EID', $id)
    //     ->where('status', '=', 'in')
    //     ->whereDate('updated_at', '=', now()->toDateString())->first();

    //     $out = app_attendance::where('EID', $id)
    //     ->where('status', '=', 'out')
    //     ->whereDate('updated_at', '=', now()->toDateString())->first();

    //     $response = [
    //         'in' => $in,
    //         'out' => $out, 
    //     ];

    //     return response()->json($response);
    // }
}
