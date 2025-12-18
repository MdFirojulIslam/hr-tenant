<?php

namespace Database\Seeders;

use App\Models\navigation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $navigations = [
            ['id' => '1', 'name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'NULL', 'page' => 'Dashboard', 'path' => './pages/dashboard.vue', 'parent_id' => '0', 'active' => '1'],
            ['id' => '2', 'name' => 'Security', 'url' => 'NULL', 'icon' => 'fa-solid fa-lock', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '1'],
            ['id' => '3', 'name' => 'User Registration', 'url' => '/security/user-registration', 'icon' => 'NULL', 'page' => 'UserRegistration', 'path' => './pages/security/userRegistration.vue', 'parent_id' => '2', 'active' => '1'],
            ['id' => '4', 'name' => 'Role', 'url' => '/security/create-role', 'icon' => 'NULL', 'page' => 'CreateRole', 'path' => './pages/security/createRole.vue', 'parent_id' => '2', 'active' => '0'],
            ['id' => '5', 'name' => 'User Page Permission', 'url' => '/security/user-page-permission', 'icon' => 'NULL', 'page' => 'UserPagePermission', 'path' => './pages/security/userPagePermission.vue', 'parent_id' => '2', 'active' => '1'],
            ['id' => '6', 'name' => 'Setup', 'url' => 'NULL', 'icon' => 'fa-solid fa-wrench', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '1'],
            ['id' => '7', 'name' => 'Employee', 'url' => '/setup/employee/employeeList', 'icon' => 'NULL', 'page' => 'EmployeeList', 'path' => './pages/setup/employee/employeeList.vue', 'parent_id' => '6', 'active' => '1'],
            ['id' => '8', 'name' => 'Employee List', 'url' => '/setup/employee/empexl', 'icon' => 'NULL', 'page' => 'EmpExl', 'path' => './pages/setup/employee/empExl.vue', 'parent_id' => '6', 'active' => '1'],
            ['id' => '9', 'name' => 'Attendance', 'url' => 'NULL', 'icon' => 'fa-solid fa-fingerprint', 'page' => 'NULL', 'path' => '', 'parent_id' => '0', 'active' => '1'],
            ['id' => '10', 'name' => 'Attendance', 'url' => 'Attendance', 'icon' => 'NULL', 'page' => 'Attendance', 'path' => './pages/attendance/attendance.vue', 'parent_id' => '9', 'active' => '1'],
            ['id' => '11', 'name' => 'Report', 'url' => 'NULL', 'icon' => 'fas fa-book-open', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '1'],
            ['id' => '12', 'name' => 'Attendance Report', 'url' => '/report/attendanceReport', 'icon' => 'NULL', 'page' => 'AttendanceReport', 'path' => './pages/report/AttendanceReport.vue', 'parent_id' => '11', 'active' => '1'],
            ['id' => '13', 'name' => 'Leave', 'url' => 'NULL', 'icon' => 'fa-solid fa-wrench', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '1'],
            ['id' => '14', 'name' => 'Holiday', 'url' => '/leave/holiday', 'icon' => 'NULL', 'page' => 'Holiday', 'path' => './pages/leave/holiday.vue', 'parent_id' => '13', 'active' => '1'],
            ['id' => '15', 'name' => 'Leave Application', 'url' => '/leave/leave-application', 'icon' => 'NULL', 'page' => 'LeaveApplication', 'path' => './pages/leave/leaveApplication.vue', 'parent_id' => '13', 'active' => '1'],
            ['id' => '16', 'name' => 'Supervisor Approval', 'url' => '/leave/supervisor-approval', 'icon' => 'NULL', 'page' => 'SupervisorApproval', 'path' => './pages/leave/supervisorApproval.vue', 'parent_id' => '13', 'active' => '1'],
            ['id' => '18', 'name' => 'Asset Information', 'url' => '/setup/assetInformation', 'icon' => 'NULL', 'page' => 'AssetInformation', 'path' => './pages/setup/assets/assetInformation.vue', 'parent_id' => '6', 'active' => '1'],
            ['id' => '20', 'name' => 'Employee Assets Info', 'url' => '/setup/emp-assets-info', 'icon' => 'NULL', 'page' => 'EmpAssetsInfo', 'path' => './pages/setup/assets/empAssetInfo.vue', 'parent_id' => '6', 'active' => '1'],
            ['id' => '22', 'name' => 'Fetch Attendance', 'url' => '/attendance/fetch-attendance', 'icon' => 'NULL', 'page' => 'FetchAttendance', 'path' => './pages/attendance/fetchAttendance.vue', 'parent_id' => '9', 'active' => '1'],
            ['id' => '23', 'name' => 'Change Password', 'url' => '/security/change-password', 'icon' => 'NULL', 'page' => 'ChangePassword', 'path' => './pages/security/changePassword.vue', 'parent_id' => '2', 'active' => '1'],
            ['id' => '24', 'name' => 'HR Approval', 'url' => '/leave/hr-approval', 'icon' => 'NULL', 'page' => 'HRApproval', 'path' => './pages/leave/hrApproval.vue', 'parent_id' => '13', 'active' => '1'],
            ['id' => '25', 'name' => 'Certificates', 'url' => '/report/certificate', 'icon' => 'NULL', 'page' => 'Certificate', 'path' => './pages/report/certificate.vue', 'parent_id' => '11', 'active' => '1'],
            ['id' => '26', 'name' => 'Leave Application Manual', 'url' => '/leave/leave-application-manual', 'icon' => 'NULL', 'page' => 'LeaveApplicationManual', 'path' => './pages/leave/leaveApplicationManual.vue', 'parent_id' => '13', 'active' => '1'],
            ['id' => '27', 'name' => 'Expense', 'url' => '/expense', 'icon' => 'fa-solid fa-sack-dollar', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '1'],
            ['id' => '28', 'name' => 'Expense Form', 'url' => '/expense/expense-form', 'icon' => 'NULL', 'page' => 'ExpenseForm', 'path' => './pages/expense/expenseForm.vue', 'parent_id' => '27', 'active' => '1'],
            ['id' => '29', 'name' => 'Expense Records', 'url' => '/expense/expense-records', 'icon' => 'NULL', 'page' => 'ExpenseRecords', 'path' => './pages/expense/expenseRecords.vue', 'parent_id' => '27', 'active' => '1'],
            ['id' => '30', 'name' => 'Expense Report', 'url' => '/report/expense-report', 'icon' => 'NULL', 'page' => 'ExpenseReport', 'path' => './pages/report/expenseReport.vue', 'parent_id' => '27', 'active' => '1'],
            ['id' => '31', 'name' => 'Leave Report', 'url' => '/report/leave-report', 'icon' => 'NULL', 'page' => 'LeaveReport', 'path' => './pages/report/leaveReport.vue', 'parent_id' => '11', 'active' => '1'],
            ['id' => '32', 'name' => 'Field Force Attendance', 'url' => '/attendance/field-force-attendance', 'icon' => 'NULL', 'page' => 'FieldForceAttendance', 'path' => './pages/attendance/field-force-attendance.vue', 'parent_id' => '9', 'active' => '1'],
            ['id' => '33', 'name' => 'Organizational Hierarchy', 'url' => '/setup/employee/organizational-hierarchy', 'icon' => 'NULL', 'page' => 'OrganizationalHierarchy', 'path' => './pages/setup/employee/hierarchy.vue', 'parent_id' => '6', 'active' => '1'],
            ['id' => '34', 'name' => 'Workstation', 'url' => '/workstation', 'icon' => 'fa-solid fa-briefcase', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '1'],
            ['id' => '35', 'name' => 'Notes', 'url' => '/workstation/notes', 'icon' => 'NULL', 'page' => 'Notes', 'path' => './pages/workstation/notes.vue', 'parent_id' => '34', 'active' => '1'],
            ['id' => '47', 'name' => 'Itinerary', 'url' => '/workstation/itinerary', 'icon' => 'NULL', 'page' => 'Itinerary', 'path' => './pages/workstation/itinerary.vue', 'parent_id' => '34', 'active' => '1'],
            ['id' => '48', 'name' => 'Daily Itinerary', 'url' => '/workstation/daily-itinerary', 'icon' => 'NULL', 'page' => 'DailyItinerary', 'path' => './pages/workstation/dailyItinerary.vue', 'parent_id' => '34', 'active' => '1'],
            ['id' => '49', 'name' => 'Daily Itinerary List', 'url' => '/workstation/daily-itinerary-list', 'icon' => 'NULL', 'page' => 'DailyItineraryList', 'path' => './pages/workstation/dailyItineraryList.vue', 'parent_id' => '34', 'active' => '1'],
            ['id' => '50', 'name' => 'Admin Itinerary List', 'url' => '/workstation/admin-itinerary-list', 'icon' => 'NULL', 'page' => 'AdminItineraryList', 'path' => './pages/workstation/adminItineraryList.vue', 'parent_id' => '34', 'active' => '1'],
            ['id' => '36', 'name' => 'Vehicle', 'url' => '/vehicle', 'icon' => 'fa-solid fa-car', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '1'],
            ['id' => '37', 'name' => 'Vehicle Setup', 'url' => '/setup/vehicle/vehicle-setup', 'icon' => 'NULL', 'page' => 'VehicleSetup', 'path' => './pages/setup/vehicle/vehicleSetup.vue', 'parent_id' => '6', 'active' => '1'],
            ['id' => '38', 'name' => 'Vehicle Requisition', 'url' => '/vehicle/vehicle-requisition', 'icon' => 'NULL', 'page' => 'VehicleRequisition', 'path' => './pages/vehicle/vehicleRequisition.vue', 'parent_id' => '36', 'active' => '1'],
            ['id' => '39', 'name' => 'Vehicle Requisition Approval', 'url' => '/vehicle/vehicle-requisition-approval', 'icon' => 'NULL', 'page' => 'VehicleRequisitionApproval', 'path' => './pages/vehicle/vehicleRequisitionApproval.vue', 'parent_id' => '36', 'active' => '1'],
            ['id' => '40', 'name' => 'Expense Permission', 'url' => '/expense/expense-permission', 'icon' => 'NULL', 'page' => 'ExpensePermission', 'path' => './pages/expense/expensePermission.vue', 'parent_id' => '27', 'active' => '1'],
            ['id' => '41', 'name' => 'Payroll', 'url' => '/payroll', 'icon' => 'fa-solid fa-money-check-dollar', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '1'],
            ['id' => '42', 'name' => 'Salary Setup', 'url' => '/payroll/salary-setup', 'icon' => 'NULL', 'page' => 'SalarySetup', 'path' => './pages/payroll/salarySetup.vue', 'parent_id' => '41', 'active' => '1'],
            ['id' => '43', 'name' => 'Salary Adjustment List', 'url' => '/payroll/salary-adjustment', 'icon' => 'NULL', 'page' => 'SalaryAdjustment', 'path' => './pages/payroll/salaryAdjustment.vue', 'parent_id' => '41', 'active' => '1'],
            ['id' => '44', 'name' => 'Employee Loan List', 'url' => '/payroll/employee-loan', 'icon' => 'NULL', 'page' => 'EmployeeLoan', 'path' => './pages/payroll/employeeLoan.vue', 'parent_id' => '41', 'active' => '1'],
            ['id' => '45', 'name' => 'Salary Sheet', 'url' => '/payroll/salary-sheet', 'icon' => 'NULL', 'page' => 'SalarySheet', 'path' => './pages/payroll/salarySheet.vue', 'parent_id' => '41', 'active' => '1'],
            ['id' => '46', 'name' => 'Loan Application', 'url' => '/payroll/loan-application', 'icon' => 'NULL', 'page' => 'LoanApplication', 'path' => './pages/payroll/loanApplication.vue', 'parent_id' => '41', 'active' => '1'],
        ];

        foreach ($navigations as $item) {
            navigation::create($item);
        }
    }
}
