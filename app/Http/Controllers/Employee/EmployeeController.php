<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\emp_img;
use App\Models\employee;
use App\Models\User;
use App\Models\official;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $employee = employee::select(
            'employees.id',
            'employees.Employee_Id',
            'employees.Full_Name',
            'employees.Card_No',
            'employees.DOB',
            'employees.Contact_No',
            'employees.Official_Email',
            'departments.Name as department_name',
            'emp_imgs.img_url'
        )
            ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftjoin('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
            ->distinct('employees.id')
            // ->where('employees.status', '=', 1)
            ->orderby('employees.Employee_Id', 'asc')
            ->paginate(20);
        return response()->json($employee);
    }


    public function haveAccount()
    {
        $employee = employee::select(
            'employees.id',
            'employees.Employee_Id',
            'employees.Full_Name',
            'employees.Card_No',
            'officials.DOJ',
            'employees.Contact_No',
            'employees.Official_Email',
            'departments.Name as department_name',
            'emp_imgs.img_url'
        )
            ->join('users', 'users.EID', '=', 'employees.id')
            ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftjoin('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
            ->where('users.active', '=', 1)
            ->orderby('employees.Employee_Id', 'asc')
            ->distinct('employees.id')
            ->paginate(20);
        return response()->json($employee);
    }


    public function withoutAccount()
    {
        $employee = official::select('officials.EID')->where('officials.Status', '=', 'N')->get()->pluck('EID')->toArray();
        $users = User::select('users.EID')->where('users.active', '=', 1)->pluck('EID')->toArray();


        $withoutAccount = array_diff($employee, $users);
        $employee = employee::select(
            'employees.id',
            'employees.Employee_Id',
            'employees.Full_Name',
            'employees.Card_No',
            'officials.DOJ',
            'officials.DOL',
            'employees.Contact_No',
            'employees.Official_Email',
            'departments.Name as department_name',
            'emp_imgs.img_url'
        )
            ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftjoin('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
            ->whereIn('employees.id', $withoutAccount)
            ->orderby('employees.Employee_Id', 'asc')
            ->distinct('employees.id')
            ->paginate(20);
        return response()->json($employee);
    }


    public function inactive()
    {
        $employee = employee::select(
            'employees.id',
            'employees.Employee_Id',
            'employees.Full_Name',
            'employees.Card_No',
            'officials.DOJ',
            'officials.DOL',
            'employees.Contact_No',
            'employees.Official_Email',
            'departments.Name as department_name',
            'emp_imgs.img_url'
        )
            ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftjoin('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
            ->where('officials.Status', '=', 'Y')
            ->orderby('employees.Employee_Id', 'asc')
            ->distinct('employees.id')
            ->paginate(20);
        return response()->json($employee);
    }


    public function inactiveSearch(Request $request)
    {
        $query = $request->input('query');
        $employee = employee::select(
            'employees.id',
            'employees.Employee_Id',
            'employees.Full_Name',
            'employees.Card_No',
            'officials.DOJ',
            'officials.DOL',
            'employees.Contact_No',
            'employees.Official_Email',
            'departments.Name as department_name',
            'emp_imgs.img_url'
        )
            ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftjoin('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
            ->where(function ($q) use ($query) {
                $q->where('employees.Full_Name', 'LIKE', "%{$query}%")
                    ->orWhere('employees.Employee_Id', 'LIKE', "%{$query}%");
            })
            ->where('officials.Status', '=', 'Y')
            ->distinct('employees.id')
            ->orderby('employees.Full_Name', 'asc')
            ->paginate(20);


        return response()->json($employee);
    }


    public function withoutAccountSearch(Request $request)
    {
        $query = $request->input('query');
        $employee = official::select('officials.EID')->where('officials.Status', '=', 'N')->get()->pluck('EID')->toArray();
        $users = User::select('users.EID')->where('users.active', '=', 1)->pluck('EID')->toArray();


        $withoutAccount = array_diff($employee, $users);


        $employee = employee::select(
            'employees.id',
            'employees.Employee_Id',
            'employees.Full_Name',
            'employees.Card_No',
            'officials.DOJ',
            'officials.DOL',
            'employees.Contact_No',
            'employees.Official_Email',
            'departments.Name as department_name',
            'emp_imgs.img_url'
        )
            ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftjoin('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
            ->where(function ($q) use ($query) {
                $q->where('employees.Full_Name', 'LIKE', "%{$query}%")
                    ->orWhere('employees.Employee_Id', 'LIKE', "%{$query}%");
            })
            ->whereIn('employees.id', $withoutAccount)
            ->distinct('employees.id')
            ->orderby('employees.Full_Name', 'asc')
            ->paginate(20);


        return response()->json($employee);
    }
    public function activeSearch(Request $request)
    {
        $query = $request->input('query');
        $employee = employee::select(
            'employees.id',
            'employees.Employee_Id',
            'employees.Full_Name',
            'employees.Card_No',
            'officials.DOJ',
            'employees.Contact_No',
            'employees.Official_Email',
            'departments.Name as department_name',
            'emp_imgs.img_url'
        )
            ->join('users', 'users.EID', '=', 'employees.id')
            ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftjoin('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
            ->where(function ($q) use ($query) {
                $q->where('employees.Full_Name', 'LIKE', "%{$query}%")
                    ->orWhere('employees.Employee_Id', 'LIKE', "%{$query}%");
            })
            ->where('users.active', '=', 1)
            ->distinct('employees.id')
            ->orderby('employees.Full_Name', 'asc')
            ->paginate(20);


        return response()->json($employee);
    }



    public function EmployeeId()
    {
        $employeeid = employee::select('Employee_Id')
            ->orderBy('Employee_Id', 'desc')
            ->first();
        return response()->json($employeeid);
    }

    public function allemp()
    {
        $employee = employee::orderBy('Full_Name', 'asc')->get();
        return response()->json($employee);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $results = employee::with('empImg')->where('Full_Name', 'LIKE', "%{$query}%")
            ->orWhere('Employee_Id', 'LIKE', "%{$query}%")
            ->paginate(12);


        return response()->json($results);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'companyId' => 'required',
            'employeeId' => 'required|unique:employees,Employee_Id',
            'fullName' => 'required',
            'dob' => 'required',
        ], [
            'companyId.required' => 'The company ID is required.',
            'employeeId.required' => 'The employee ID is required.',
            'employeeId.unique' => 'The employee ID has already been taken.',
            'fullName.required' => 'The full name is required.',
            'dob.required' => 'The date of birth is required.',
        ]);

        $file_path = null;

        DB::beginTransaction();
        $userId = Auth::user()->EID;
        $employee = employee::create([
            'Company_Id' => $request->input('companyId'),
            'Employee_Id' => $request->input('employeeId'),
            'Card_No' => $request->input('cardNo'),
            'Full_Name' => $request->input('fullName'),
            'Father_Name' => $request->input('fatherName'),
            'Mother_Name' => $request->input('motherName'),
            'Spouse_Name' => $request->input('spouseName'),
            'Marital_Status' => $request->input('maritalStatus'),
            'DOB' => $request->input('dob'),
            'Place_of_Birth' => $request->input('pob'),
            'Present_Address' => $request->input('presentAddress'),
            'Permanent_Address' => $request->input('permanentAddress'),
            'Contact_No' => $request->input('officialContact'),
            'Emergency_Contact' => $request->input('emergencyContact'),
            'Gender' => $request->input('gender'),
            'Personal_Email' => $request->input('personalEmail'),
            'Official_Email' => $request->input('officialEmail'),
            'Blood_Group_Id' => $request->input('bloodGroup'),
            'Religion_Id' => $request->input('religion'),
            'Nationality' => $request->input('nationality'),
            'NID' => $request->input('nid'),
            'created_by' => $userId,
            'updated_by' => 0,
        ]);

        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'required|mimes:jpg,jpeg,png,csv,txt,xlx,xls,xlsx,pdf|max:512'
            ]);

            // $file_name = time() . '_' . $request->file->getClientOriginalName();
            $file_extension = $request->file->getClientOriginalExtension();
            $file_name = $request->input('employeeId') . '.' . $file_extension;
            $file_path = $request->file('file')->storeAs('uploads', $file_name, 'public');

            $image = emp_img::create([
                'EID' => $employee->id,
                'img_url' => '/storage/' . $file_path,
            ]);
        }

        $lastId = employee::latest('id')->value('id');
        $response = [
            'success'   =>  true,
            'message'   =>  'Successfully inserted',
            'empid' => $lastId,
        ];

        DB::commit();
        return response()->json($response);


        DB::rollback();
        $response = [
            'success'   =>  false,
            'message'   =>  'Error while inserting',
        ];
        return response()->json($response);
    }

    /**
     * Display the specified resource.
     */
    public function show(employee $id)
    {
        $emp = employee::find($id);

        if (!$emp) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
        return response()->json($emp);
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function generatePdf($id)
    {
        $employee = employee::with([
            'company',
            'blood',
            'religion',
            'academic',
            'training',
            'experience',
            'official',
            'nominee',
            'child',
            'academic.scale',
            'academic.board',
            'academic.education',
            'academic.education.degree',
            'official.designation',
            'official.department',
            'official.area',
            'official.employeeType',
            'official.territory',
            'official.supervisor',
            'official.branch',
        ])->find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $pdf = Pdf::loadView('reports.attendance', compact('employee'));


        return $pdf->stream('CV.pdf');
    }


    public function edit($id)
    {
        $employee = employee::with([
            'company',
            'blood',
            'religion',
            'academic',
            'training',
            'experience',
            'official',
            'nominee',
            'child',
            'academic.scale',
            'academic.board',
            'academic.education',
            'academic.education.degree',
            'official.designation',
            'official.department',
            'official.area',
            'official.employeeType',
            'official.territory',
            'official.supervisor',
            'official.branch',
            'empImg',
        ])->find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json($employee);
    }

    public function getEmployeeFromDept($id)
    {
        $employee = Employee::select('employees.id', 'employees.Full_Name', 'employees.Employee_Id', 'designations.Name')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->where('departments.id', '=', $id)
            ->where('officials.Status', '=', 'N')
            ->orderby('employees.Full_Name', 'asc')
            ->get();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json($employee);
    }
    public function getAllEmployeeFromDept($id)
    {
        $employee = Employee::select('employees.id', 'employees.Full_Name', 'employees.Employee_Id', 'designations.Name')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->where('departments.id', '=', $id)
            ->orderby('employees.Full_Name', 'asc')
            ->get();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json($employee);
    }

    public function getAllEmployee($id)
    {
        $employee = Employee::select('employees.id', 'employees.Full_Name', 'employees.Employee_Id', 'designations.Name')
            ->join('officials', 'officials.EID', '=', 'employees.id')
            ->join('departments', 'officials.Department_Id', '=', 'departments.id')
            ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->where('departments.id', '=', $id)
            ->orderby('employees.Full_Name', 'asc')
            ->get();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json($employee);
    }

    public function getEmail($id)
    {
        $emailUser = User::select('users.name as username', 'users.email as email')->where('EID', $id)->first();
        if (!$emailUser) {
            $emailUser = Employee::select('employees.Official_Email as email')->where('id', $id)->first();
        }

        if (!$emailUser) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        return response()->json($emailUser);
    }

    public function EmployeeExl()
    {
        $employee = employee::select(
            'employees.id',
            'employees.Employee_Id',
            'employees.Full_Name',
            'employees.Father_Name',
            'employees.Mother_Name',
            'employees.Spouse_Name',
            'employees.Marital_Status',
            'employees.DOB',
            'employees.Place_of_Birth',
            'employees.Present_Address',
            'employees.Permanent_Address',
            'employees.Personal_Email',
            'employees.Official_Email',
            'employees.Nationality',
            'employees.NID',
            'employees.Emergency_Contact',
            'employees.Contact_No',
            'employees.Gender',
            'designations.Name as designation',
            'departments.Name as department',
            'departments.Division as division',
            'officials.Status as status',
            'blood_groups.Name as group',
            'religions.Name as religion',
            'officials.Employee_Grade',
            'officials.DOJ',
            'officials.DOL',
            'branches.Name as branch_name',
            'officials.Provation_period',
            'employee_types.Name as employee_type'
        )
            ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
            ->leftjoin('branches', 'officials.Job_Location_Id', '=', 'branches.id')
            ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
            ->leftjoin('designations', 'officials.Designation_Id', '=', 'designations.id')
            ->leftjoin('blood_groups', 'employees.Blood_Group_Id', '=', 'blood_groups.id')
            ->leftjoin('religions', 'employees.Religion_Id', '=', 'religions.id')
            ->leftjoin('employee_types', 'officials.Employee_type_Id', '=', 'employee_types.id')
            ->orderby('employees.Full_Name', 'asc')
            ->get();


        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json($employee);
    }


    public function update(Request $request, $id)
    {
        try {
            $userId = Auth::user()->EID;
            DB::beginTransaction();
            $employee = employee::find($id);
            $employee->update([
                'Company_Id' => $request->companyId,
                'Employee_Id' => $request->input('employeeId'),
                'Card_No' => $request->input('cardNo'),
                'Full_Name' => $request->input('fullName'),
                'Father_Name' => $request->input('fatherName'),
                'Mother_Name' => $request->input('motherName'),
                'Spouse_Name' => $request->input('spouseName'),
                'Marital_Status' => $request->input('maritalStatus'),
                'DOB' => $request->input('dob'),
                'Place_of_Birth' => $request->input('pob'),
                'Present_Address' => $request->input('presentAddress'),
                'Permanent_Address' => $request->input('permanentAddress'),
                'Contact_No' => $request->input('officialContact'),
                'Emergency_Contact' => $request->input('emergencyContact'),
                'Gender' => $request->input('gender'),
                'Personal_Email' => $request->input('personalEmail'),
                'Official_Email' => $request->input('officialEmail'),
                'Blood_Group_Id' => $request->input('bloodGroup'),
                'Religion_Id' => $request->input('religion'),
                'Nationality' => $request->input('nationality'),
                'NID' => $request->input('nid'),
                'updated_by' => $userId,
            ]);

            if ($request->hasFile('file')) {
                $request->validate([
                    'file' => 'required|mimes:jpg,jpeg,png,csv,txt,xlx,xls,xlsx,pdf|max:512'
                ]);

                $file_extension = $request->file->getClientOriginalExtension();
                $file_name = $request->input('employeeId') . '.' . $file_extension;
                $file_path = $request->file('file')->storeAs('uploads', $file_name, 'public');

                $existingImage = emp_img::where('EID', $employee->id)->first();

                if ($existingImage) {
                    $oldFilePath  = str_replace('/storage', '', $existingImage->img_url);

                    Storage::disk('public')->delete($existingImage);

                    $existingImage->update([
                        'img_url' => '/storage/' . $file_path,
                    ]);
                }
                // $file_name = time() . '_' . $request->file->getClientOriginalName();

                $image = emp_img::create([
                    'EID' => $employee->id,
                    'img_url' => '/storage/' . $file_path,
                ]);
            }

            $response = [
                'success' => true,
                'message'  => 'Updated Successfully'
            ];
            DB::commit();
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollback();
            $response = [
                'success'   =>  false,
                'message'   =>  'Error while updating',
            ];
            return response()->json($response);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(employee $employee) {}
}
