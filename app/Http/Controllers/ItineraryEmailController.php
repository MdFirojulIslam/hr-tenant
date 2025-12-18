<?php

namespace App\Http\Controllers;

use App\Mail\ItinerarySubmittedMail;
use App\Models\itinerary_logs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ItineraryEmailController extends Controller
{
    public function itinerarySenderEmail($id)
    {
        $response = itinerary_logs::select('employees.Full_Name as employee_name', 'departments.Name as department_name', 'designations.Name as designation_name', 'itinerary_logs.created_date')
            ->join('employees', 'employees.id', '=', 'itinerary_logs.created_by')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->where('itinerary_logs.id', $id)
            ->first();
        $supervisor = itinerary_logs::select('officials.Supervisor_Id as supervisor_id', 'employees.Official_Email as supervisor_email')
            ->join('officials', 'officials.EID', '=', 'itinerary_logs.created_by')
            ->join('employees', 'employees.id', '=', 'officials.EID')
            ->where('itinerary_logs.id', $id)
            ->first();

        $supervisorMail = $supervisor->supervisor_email;
        $senderName = $response->employee_name;
        $senderDesignation = $response->designation_name;
        $itineraryFor = $response->created_date;

        Mail::to($supervisorMail)->send(new ItinerarySubmittedMail($senderName, $senderDesignation, $itineraryFor));

        return response()->json(['message' => 'Email sent successfully']);
    }

    public function itineraryRejectionEmail($id)
    {
        $response = itinerary_logs::select(
            'employees.Full_Name as employee_name',
            'employees.Official_Email as employee_email',
            'departments.Name as department_name',
            'designations.Name as designation_name',
            'itinerary_logs.created_date'
        )
            ->join('employees', 'employees.id', '=', 'itinerary_logs.created_by')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->where('itinerary_logs.id', $id)
            ->first();
        $employeeMail = $response->employee_email;
        $employeeName = $response->employee_name;
        $employeeDesignation = $response->designation_name;
        $itineraryFor = $response->created_date;
        Mail::to($employeeMail)->send(new ItineraryRejectMail($itineraryFor));
        return response()->json(['message' => 'Rejection email sent successfully']);
    }

    public function itineraryRecheckEmail($id)
    {
        $response = itinerary_logs::select(
            'employees.Full_Name as employee_name',
            'employees.Official_Email as employee_email',
            'departments.Name as department_name',
            'designations.Name as designation_name',
            'itinerary_logs.created_date'
        )
            ->join('employees', 'employees.id', '=', 'itinerary_logs.created_by')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->where('itinerary_logs.id', $id)
            ->first();
        $employeeMail = $response->employee_email;
        $employeeName = $response->employee_name;
        $employeeDesignation = $response->designation_name;
        $itineraryFor = $response->created_date;
        Mail::to($employeeMail)->send(new ItineraryRecheckMail($itineraryFor));
        return response()->json(['message' => 'Rejection email sent successfully']);
    }

    public function itineraryAcceptEmail($id)
    {
        $response = itinerary_logs::select(
            'employees.Full_Name as employee_name',
            'employees.Official_Email as employee_email',
            'departments.Name as department_name',
            'designations.Name as designation_name',
            'itinerary_logs.created_date'
        )
            ->join('employees', 'employees.id', '=', 'itinerary_logs.created_by')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->where('itinerary_logs.id', $id)
            ->first();
        $employeeMail = $response->employee_email;
        $employeeName = $response->employee_name;
        $employeeDesignation = $response->designation_name;
        $itineraryFor = $response->created_date;
        Mail::to($employeeMail)->send(new ItineraryAcceptMail($itineraryFor));
        return response()->json(['message' => 'Rejection email sent successfully']);
    }
}
