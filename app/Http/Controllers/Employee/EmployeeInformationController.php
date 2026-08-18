<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

use App\Models\area;
use App\Models\board;
use App\Models\leave;
use App\Models\scale;
use App\Models\branch;
use App\Models\degree;
use App\Models\company;
use App\Models\country;
use App\Models\emp_img;
use App\Models\employee;
use App\Models\official;
use App\Models\religion;
use App\Models\territory;
use App\Models\department;
use App\Models\blood_group;
use App\Models\employee_type;
use App\Models\town;
use App\Models\separation_request;

use App\Models\town_belong;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\EmpInfoDocumentation;
use Illuminate\Http\Request;

class EmployeeInformationController extends Controller
{
    public function currentUser()
    {
        $userId = Auth::user()->EID;
        if ($userId) {
            $user = employee::select('employees.id', 'employees.Full_Name', 'emp_imgs.img_url', 'departments.Name as dept_name', 'designations.Name as des_name')
                ->leftjoin('emp_imgs', 'employees.id', '=', 'emp_imgs.EID')
                ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
                ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
                ->leftjoin('designations', 'officials.Designation_Id', '=', 'designations.id')
                ->where('employees.id', '=', $userId)
                ->first();
            return response()->json($user);
        } else {
            return response()->json('logout');
        }
    }

    public function getEmployee($id)
    {
        if ($id) {
            $user = employee::select('employees.id', 'employees.Full_Name', 'emp_imgs.img_url', 'departments.Name as dept_name', 'designations.Name as des_name')
                ->leftjoin('emp_imgs', 'employees.id', '=', 'emp_imgs.EID')
                ->leftjoin('officials', 'officials.EID', '=', 'employees.id')
                ->leftjoin('departments', 'officials.Department_Id', '=', 'departments.id')
                ->leftjoin('designations', 'officials.Designation_Id', '=', 'designations.id')
                ->where('employees.id', '=', $id)
                ->first();
            return response()->json($user);
        }
    }



    public function employeeLeave()
    {
        $userId = Auth::user()->EID;
        if ($userId) {
            $leave = leave::select('leaves.id', 'leaves.Status', 'leaves.Notification', 'employees.Full_Name', 'leave_types.Name as leave_type')
                ->join('employees', 'employees.id', '=', 'leaves.employee_id')
                ->join('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
                ->where('employee_id', $userId)
                ->get();

            return response()->json($leave);
        }
    }


    public function getTown()
    {
        $blood = town::all();
        return response()->json($blood);
    }

    public function getBloodGroup()
    {
        $blood = blood_group::all();
        return response()->json($blood);
    }

    public function getBoard()
    {
        $board = board::all();
        return response()->json($board);
    }

    public function getBranch()
    {
        $branch = branch::all();
        return response()->json($branch);
    }

    public function getCompany()
    {
        $company = company::all();
        return response()->json($company);
    }

    public function getPhone()
    {
        $phone = country::all();
        return response()->json($phone);
    }

    public function getDegree()
    {
        $degree = degree::all();
        return response()->json($degree);
    }

    public function getReligion()
    {
        $religion = religion::all();
        return response()->json($religion);
    }

    public function getScale()
    {
        $scale = scale::all();
        return response()->json($scale);
    }



    public function fetchDegree($id)
    {
        $degree = DB::table('level_of_educations')
            ->select('id', 'Name')
            ->where('education_id', $id)
            ->orderBy('Name', 'asc')
            ->get();

        return response()->json($degree);
    }

    public function fetchEmployeeImg($id)
    {
        // $image = emp_img::where('EID', $id)->value('img_url');
        $image = emp_img::where('EID', $id)->first(['img_url']);
        return response()->json($image);
    }

    public function getEducation()
    {
        $education = DB::table('level_of_educations')
            ->select('id', 'Name')
            ->whereNull('education_id')
            ->orWhere('education_id', '')
            ->get();

        $degree = DB::table('level_of_educations')
            ->select('id', 'Name')
            ->whereNotNull('education_id')
            ->get();

        return response()->json([
            'education' => $education,
            'degree' => $degree,
        ]);
    }

    //added by firoj
    public function getNotification()
    {
        $user = Auth::user();
        $currentYear = now()->year;

        $hrId = employee::query()
            ->join('officials as o', 'o.EID', '=', 'employees.id')
            ->where('o.department_id', 5)
            ->where('o.status', 'N')
            ->pluck('employees.id')
            ->first();
        if ($user->EID == $hrId) {
            $pending_leave = official::select('leaves.id as leave_id', 'employees.Full_Name', 'departments.Name as department_name', 'leaves.status', 'leaves.purpose', 'leave_types.leave_name as Leave_Type')
                ->join('leaves', 'leaves.employee_id', '=', 'officials.EID')
                ->join('employees', 'employees.id', '=', 'officials.EID')
                ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
                ->join('departments', 'departments.id', '=', 'officials.Department_Id')
                ->where('leaves.status', '=', 'Approved (SV)')
                ->where('officials.Status', '=', 'N')
                ->whereYear('leaves.from_date', '=', $currentYear)
                ->get();
        } else {
            $pending_leave = official::select('leaves.id as leave_id', 'employees.Full_Name', 'departments.Name as department_name', 'leaves.status', 'leaves.purpose', 'leave_types.leave_name as Leave_Type')
                ->join('leaves', 'leaves.employee_id', '=', 'officials.EID')
                ->join('employees', 'employees.id', '=', 'officials.EID')
                ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
                ->join('departments', 'departments.id', '=', 'officials.Department_Id')
                ->where('officials.Supervisor_Id', '=', $user->EID)
                ->where('leaves.status', '=', 'Pending')
                ->where('officials.Status', '=', 'N')
                ->whereYear('leaves.from_date', '=', $currentYear)
                ->get();
        }

        // $pending_separation = separation_request::select(
        //     'separation_request.id as separation_request_id',
        //     'employees.Full_Name as employee_name',
        //     'departments.Name as department_name',
        //     'separation_info.status as approval_status',
        //     'separation_info.separation_approval_id as first_approval_id',
        //     'separation_info.department_approval_id',
        //     'separation_info.hr_approval_id',
        //     'separation_info.it_approval_id',
        //     'separation_info.accounts_approval_id'
        // )
        //     ->join('employees', 'employees.id', '=', 'separation_request.employee_id')
        //     ->join('departments', 'departments.id', '=', 'employees.department_id')
        //     ->where('separation_request.status', '=', 'Pending')
        //     ->get();

        // $all_dept = department::pluck('id')->toArray();

        // if($all_dept == 1)

        // $first_approval = separation_request::select(
        //     'separation_request.id as separation_request_id',
        //     'employees.Full_Name as employee_name',
        //     'departments.Name as department_name',
        //     'separation_info.status as approval_status',
        //     'separation_info.separation_approval_id as first_approval_id'
        // )
        //     ->join('employees', 'employees.id', '=', 'separation_request.employee_id')
        //     ->join('officials', 'officials.EID', '=', 'employees.id')
        //     ->join('departments', 'departments.id', '=', 'officials.Department_Id')
        //     ->where('separation_request.status', '=', 'Pending')
        //     ->get();

        return response()->json($pending_leave);
    }
    //firoj ends


    public function getOfficialInfo()
    {
        $department = department::get();
        $emptype = employee_type::get();
        $area = area::get();
        $territory = territory::get();
        $branch = branch::get();

        return response()->json([
            "department" => $department,
            "emptype" => $emptype,
            "area" => $area,
            "territory" => $territory,
            "branch" => $branch,
        ]);
    }


    public function getSupervisor()
    {
        $employee = employee::select('id', 'Full_Name', 'Employee_Id')->get();
        return response()->json($employee);
    }


    public function userDetails()
    {
        $userId = Auth::user()->EID;
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
        ])->find($userId);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json($employee);
    }




    function buildTree(array $elements, $parentId = null)
    {
        $tree = [];
        foreach ($elements as $element) {
            if ($element['Supervisor_Id'] == $parentId) {
                $children = $this->buildTree($elements, $element['EID']);
                if ($children) {
                    $element['children'] = $children;
                }
                $tree[] = $element;
            }
        }
        return $tree;
    }

    public function getHierarchy()
    {
        // Fetch all rows from the official table
        $officials = official::select('employees.Full_Name', 'departments.Name as dept_name', 'designations.Name as des_name', 'officials.Supervisor_Id', 'officials.EID', 'emp_imgs.img_url')
            ->join('employees', 'employees.id', '=', 'officials.EID')
            ->join('departments', 'departments.id', '=', 'officials.Department_Id')
            ->join('designations', 'designations.id', '=', 'officials.Designation_Id')
            ->join('emp_imgs', 'emp_imgs.EID', '=', 'employees.id')
            ->where('officials.Status', '=', 'N')
            ->distinct()
            ->get()->toArray();

        // Build the tree starting from the root supervisor (head of the company)
        $tree = $this->buildTree($officials, 0); // Assuming 1 is the root EID (head of the company)

        return response()->json($tree);
    }



    public function getSupervisedEmployee($id)
    {
        $user = Auth::user();
        if ($user->role_id === 1 || $user->role_id === 2) {
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

        if ($user->EID) {
            $employee = $this->getSubordinates($user->EID, $id);

            return response()->json($employee);
        }

        return response()->json(['message' => 'User not found'], 404);
    }

    public function getSubordinates($userId, $id)
    {
        $subordinates = [];
        $queue = [$userId];

        while (!empty($queue)) {
            $currentUserId = array_shift($queue);
            $subordinateEmployees = employee::select('employees.id', 'employees.Full_Name', 'employees.Employee_Id', 'designations.Name')
                ->join('officials', 'officials.EID', '=', 'employees.id')
                ->join('departments', 'officials.Department_Id', '=', 'departments.id')
                ->join('designations', 'officials.Designation_Id', '=', 'designations.id')
                ->where('departments.id', '=', $id)
                ->where('officials.Supervisor_Id', '=', $currentUserId)
                ->orderby('employees.Full_Name', 'asc')
                ->get();

            foreach ($subordinateEmployees as $subordinate) {
                $subordinates[] = $subordinate;
                $queue[] = $subordinate->id;
            }
        }

        return $subordinates;
    }

    private function calculateCompletedPercent(array $data): string
    {
        $total = 18;
        $completed = 0;

        $fields = [
            'job_circular',
            'interview_evalation',
            'appointment_letter',
            'updated_resume',
            'nid_passport',
            'academic_cert',
            'post_graduate_cert',
            'prev_cmp_exp_letter',
            'prev_cmp_release_letter',
            'prev_cmp_salary_cert',
            'recent_passport_pic',
            'two_emegency_info',
            'nid_verified',
            'job_description',
            'joining_letter',
            'kye_form',
            'emp_declation_letter',
            'pa_pp_address',
        ];

        foreach ($fields as $field) {
            $value = $data[$field] ?? null;
            if ($value === 'Yes' || $value === 'Not Applicable') {
                $completed++;
            }
        }

        return round(($completed / $total) * 100, 2);
    }

    public function getDocumentationInfo(Request $request)
    {
        $employeeId = $request->query('employee_id');

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee ID is required.',
            ], 400);
        }

        $documentation = EmpInfoDocumentation::where('employee_id', $employeeId)->first();

        if (!$documentation) {
            return response()->json([
                'success' => false,
                'message' => 'Empty',
            ]);
        }

        return response()->json($documentation);
    }

    public function editDocumentationInfo(Request $request)
    {
        $employeeId = $request->query('employee_id');

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee ID is required.',
            ], 400);
        }

        $documentation = EmpInfoDocumentation::where('employee_id', $employeeId)->first();

        if (!$documentation) {
            return response()->json([
                'success' => false,
                'message' => 'Empty',
            ]);
        }

        return response()->json($documentation);
    }

    public function insertDocumentationInfo(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer',
            'job_circular' => 'nullable|in:Not Applicable,Yes,No',
            'interview_evalation' => 'nullable|in:Not Applicable,Yes,No',
            'appointment_letter' => 'nullable|in:Yes,No',
            'updated_resume' => 'nullable|in:Yes,No',
            'nid_passport' => 'nullable|in:Yes,No',
            'academic_cert' => 'nullable|in:Yes,No',
            'post_graduate_cert' => 'nullable|in:Yes,No',
            'prev_cmp_exp_letter' => 'nullable|in:Yes,No',
            'prev_cmp_release_letter' => 'nullable|in:Yes,No',
            'prev_cmp_salary_cert' => 'nullable|in:Yes,No',
            'recent_passport_pic' => 'nullable|in:Yes,No',
            'two_emegency_info' => 'nullable|in:Yes,No',
            'nid_verified' => 'nullable|in:Yes,No',
            'job_description' => 'nullable|in:Yes,No',
            'joining_letter' => 'nullable|in:Yes,No',
            'kye_form' => 'nullable|in:Yes,No',
            'emp_declation_letter' => 'nullable|in:Yes,No',
            'pa_pp_address' => 'nullable|in:Yes,No',
        ]);

        $existing = EmpInfoDocumentation::where('employee_id', $request->input('employee_id'))->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Documentation record already exists for this employee. Please update instead.',
            ], 409);
        }

        $userId = Auth::user()->EID;

        $documentationData = $request->only([
            'employee_id',
            'job_circular',
            'interview_evalation',
            'appointment_letter',
            'updated_resume',
            'nid_passport',
            'academic_cert',
            'post_graduate_cert',
            'prev_cmp_exp_letter',
            'prev_cmp_release_letter',
            'prev_cmp_salary_cert',
            'recent_passport_pic',
            'two_emegency_info',
            'nid_verified',
            'job_description',
            'joining_letter',
            'kye_form',
            'emp_declation_letter',
            'pa_pp_address',
        ]);

        $documentationData['created_by'] = $userId;
        $documentationData['updated_by'] = $userId;
        $documentationData['completed_percent'] = $this->calculateCompletedPercent($documentationData);

        EmpInfoDocumentation::create($documentationData);

        return response()->json([
            'success' => true,
            'message' => 'Successfully inserted',
        ]);
    }

    public function updateDocumentationInfo(Request $request, $emp_id)
    {
        $validated = $request->validate([
            'job_circular' => 'nullable|in:Not Applicable,Yes,No',
            'interview_evalation' => 'nullable|in:Not Applicable,Yes,No',
            'appointment_letter' => 'nullable|in:Yes,No',
            'updated_resume' => 'nullable|in:Yes,No',
            'nid_passport' => 'nullable|in:Yes,No',
            'academic_cert' => 'nullable|in:Yes,No',
            'post_graduate_cert' => 'nullable|in:Yes,No',
            'prev_cmp_exp_letter' => 'nullable|in:Yes,No',
            'prev_cmp_release_letter' => 'nullable|in:Yes,No',
            'prev_cmp_salary_cert' => 'nullable|in:Yes,No',
            'recent_passport_pic' => 'nullable|in:Yes,No',
            'two_emegency_info' => 'nullable|in:Yes,No',
            'nid_verified' => 'nullable|in:Yes,No',
            'job_description' => 'nullable|in:Yes,No',
            'joining_letter' => 'nullable|in:Yes,No',
            'kye_form' => 'nullable|in:Yes,No',
            'emp_declation_letter' => 'nullable|in:Yes,No',
            'pa_pp_address' => 'nullable|in:Yes,No',
        ]);

        $documentation = EmpInfoDocumentation::where('employee_id', $emp_id)->first();

        if (!$documentation) {
            return response()->json([
                'success' => false,
                'message' => 'Documentation record not found.',
            ], 404);
        }

        $userId = Auth::user()->EID;

        $documentationData = $request->only([
            'job_circular',
            'interview_evalation',
            'appointment_letter',
            'updated_resume',
            'nid_passport',
            'academic_cert',
            'post_graduate_cert',
            'prev_cmp_exp_letter',
            'prev_cmp_release_letter',
            'prev_cmp_salary_cert',
            'recent_passport_pic',
            'two_emegency_info',
            'nid_verified',
            'job_description',
            'joining_letter',
            'kye_form',
            'emp_declation_letter',
            'pa_pp_address',
        ]);

        $documentationData['updated_by'] = $userId;
        $documentationData['completed_percent'] = $this->calculateCompletedPercent($documentationData);

        $documentation->update($documentationData);

        return response()->json([
            'success' => true,
            'message' => 'Successfully updated',
        ]);
    }
}
