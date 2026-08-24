<?php

namespace Database\Seeders;

use App\Models\navigation;
use App\Models\user_has_page;
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
            ['id' => '10', 'name' => 'Monthly Attendance', 'url' => 'Attendance', 'icon' => 'NULL', 'page' => 'Attendance', 'path' => './pages/attendance/attendance.vue', 'parent_id' => '9', 'active' => '1'],
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
            ['id' => '32', 'name' => 'Online Attendance', 'url' => '/attendance/field-force-attendance', 'icon' => 'NULL', 'page' => 'FieldForceAttendance', 'path' => './pages/attendance/field-force-attendance.vue', 'parent_id' => '9', 'active' => '1'],
            ['id' => '33', 'name' => 'Organizational Hierarchy', 'url' => '/setup/employee/organizational-hierarchy', 'icon' => 'NULL', 'page' => 'OrganizationalHierarchy', 'path' => './pages/setup/employee/hierarchy.vue', 'parent_id' => '6', 'active' => '1'],
            ['id' => '34', 'name' => 'Workstation', 'url' => '/workstation', 'icon' => 'fa-solid fa-briefcase', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '1'],
            ['id' => '35', 'name' => 'Notes', 'url' => '/workstation/notes', 'icon' => 'NULL', 'page' => 'Notes', 'path' => './pages/workstation/notes.vue', 'parent_id' => '34', 'active' => '1'],
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
            ['id' => '47', 'name' => 'Itinerary', 'url' => '/workstation/itinerary', 'icon' => 'NULL', 'page' => 'Itinerary', 'path' => './pages/workstation/itinerary.vue', 'parent_id' => '34', 'active' => '1'],
            ['id' => '48', 'name' => 'Daily Itinerary', 'url' => '/workstation/daily-itinerary', 'icon' => 'NULL', 'page' => 'DailyItinerary', 'path' => './pages/workstation/dailyItinerary.vue', 'parent_id' => '34', 'active' => '1'],
            ['id' => '49', 'name' => 'Itinerary List', 'url' => '/workstation/itinerary-list', 'icon' => 'NULL', 'page' => 'ItineraryList', 'path' => './pages/workstation/itineraryList.vue', 'parent_id' => '34', 'active' => '1'],
            ['id' => '50', 'name' => 'Admin Itinerary List', 'url' => '/workstation/admin-itinerary-list', 'icon' => 'NULL', 'page' => 'AdminItineraryList', 'path' => './pages/workstation/adminItineraryList.vue', 'parent_id' => '34', 'active' => '1'],
            ['id' => '51', 'name' => 'Salary Disbursement', 'url' => '/payroll/salary-disbursement', 'icon' => 'NULL', 'page' => 'SalaryDisbursement', 'path' => './pages/payroll/salaryDisbursement.vue', 'parent_id' => '41', 'active' => '1'],
            ['id' => '52', 'name' => 'Employee Separation', 'url' => '/separation', 'icon' => 'fa-solid fa-money-check-dollar', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '0'],
            ['id' => '53', 'name' => 'Separation Request', 'url' => '/separation/separation-request', 'icon' => 'NULL', 'page' => 'SeparationRequest', 'path' => './pages/separation/separationRequest.vue', 'parent_id' => '52', 'active' => '1'],
            ['id' => '54', 'name' => 'Separation Approval', 'url' => '/separation/separation-approval', 'icon' => 'NULL', 'page' => 'SeparationApproval', 'path' => './pages/separation/separationApproval.vue', 'parent_id' => '52', 'active' => '1'],
            ['id' => '55', 'name' => 'All Separations', 'url' => '/separation/all-separations', 'icon' => 'NULL', 'page' => 'AllSeparations', 'path' => './pages/separation/allSeparations.vue', 'parent_id' => '52', 'active' => '1'],
            ['id' => '56', 'name' => 'User Component Permission', 'url' => '/security/user-component-permission', 'icon' => 'NULL', 'page' => 'UserComponentPermission', 'path' => './pages/security/userComponentPermission.vue', 'parent_id' => '2', 'active' => '0'],
            ['id' => '57', 'name' => 'Salary Payslip', 'url' => '/payroll/salary-payslip', 'icon' => 'NULL', 'page' => 'SalaryPayslip', 'path' => './pages/payroll/salaryPayslip.vue', 'parent_id' => '41', 'active' => '1'],
            ['id' => '58', 'name' => 'Bonus Sheet', 'url' => '/payroll/bonus_sheet', 'icon' => 'NULL', 'page' => 'BonusSheet', 'path' => './pages/payroll/bonusSheet.vue', 'parent_id' => '41', 'active' => '1'],
            ['id' => '59', 'name' => 'Bonus Disbursement', 'url' => '/payroll/bonus-disbursement', 'icon' => 'NULL', 'page' => 'BonusDisbursement', 'path' => './pages/payroll/bonusDisbursement.vue', 'parent_id' => '41', 'active' => '1'],
            ['id' => '60', 'name' => 'Roster/Schedule Setup', 'url' => '/roster-setup', 'icon' => 'NULL', 'page' => 'RosterSetup', 'path' => './pages/attendance/rosterSetup.vue', 'parent_id' => '9', 'active' => '1'],
            ['id' => '61', 'name' => 'Daily Attendance', 'url' => '/roster-report-list', 'icon' => 'NULL', 'page' => 'RosterReportList', 'path' => './pages/attendance/rosterReportList.vue', 'parent_id' => '9', 'active' => '1'],
            ['id' => '62', 'name' => 'Artisan Calendar', 'url' => '/artisan-calendar', 'icon' => 'NULL', 'page' => 'ArtisanCalendar', 'path' => './pages/attendance/artisanCalendar.vue', 'parent_id' => '9', 'active' => '1'],
            ['id' => '63', 'name' => 'Manual Attendance', 'url' => '/manual-attendance', 'icon' => 'NULL', 'page' => 'ManualAttendance', 'path' => './pages/attendance/manualAttendance.vue', 'parent_id' => '9', 'active' => '1'],
            ['id' => '64', 'name' => 'Incentive', 'url' => '/incentive', 'icon' => 'fa-solid fa-money-check-dollar', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '0'],
            ['id' => '65', 'name' => 'Incentive Setup', 'url' => '/incentive/incentive-setup', 'icon' => 'NULL', 'page' => 'IncentiveSetup', 'path' => './pages/attendance/incentiveSetup.vue', 'parent_id' => '41', 'active' => '1'],
            ['id' => '66', 'name' => 'Daily Order Target', 'url' => '/workstation/daily-order-target', 'icon' => 'fa-solid fa-bullseye', 'page' => 'DailyOrderTarget', 'path' => 'NULL', 'parent_id' => '34', 'active' => '1'],
            ['id' => '67', 'name' => 'Market Intelligence', 'url' => '/workstation/market-intelligence', 'icon' => 'fa-solid fa-chart-line', 'page' => 'MarketIntelligence', 'path' => 'NULL', 'parent_id' => '34', 'active' => '1'],
            // 68 = parent group (web nav folder). Flutter page permission is child 70.
            ['id' => '68', 'name' => 'Outlet Visit', 'url' => '/outlet-visit', 'icon' => 'fa-solid fa-store', 'page' => 'NULL', 'path' => 'NULL', 'parent_id' => '0', 'active' => '1'],
            ['id' => '69', 'name' => 'Outlet Visit Setup', 'url' => '/outlet-visit/setup', 'icon' => 'NULL', 'page' => 'OutletVisitSetup', 'path' => './pages/outletVisit/outletVisitSetup.vue', 'parent_id' => '68', 'active' => '1'],
            ['id' => '70', 'name' => 'Outlet Visit', 'url' => '/workstation/outlet-visit', 'icon' => 'NULL', 'page' => 'OutletVisit', 'path' => 'NULL', 'parent_id' => '68', 'active' => '1'],
            ['id' => '71', 'name' => 'MoM', 'url' => '/workstation/mom', 'icon' => 'fa-solid fa-clipboard-list', 'page' => 'MinutesOfMeeting', 'path' => 'NULL', 'parent_id' => '34', 'active' => '1'],
            ['id' => '72', 'name' => 'Task Board', 'url' => '/workstation/task-board', 'icon' => 'fa-solid fa-list-check', 'page' => 'TaskBoard', 'path' => './pages/workstation/taskBoard.vue', 'parent_id' => '34', 'active' => '1'],
            ['id' => '73', 'name' => 'Task Board', 'url' => '/workstation/task-board-mobile', 'icon' => 'NULL', 'page' => 'TaskBoardMobile', 'path' => 'NULL', 'parent_id' => '34', 'active' => '1'],
            ['id' => '74', 'name' => 'Event Setup', 'url' => '/setup/event-setup', 'icon' => 'NULL', 'page' => 'EventSetup', 'path' => './pages/setup/event/eventSetup.vue', 'parent_id' => '6', 'active' => '1'],
            ['id' => '75', 'name' => 'Events', 'url' => '/workstation/events', 'icon' => 'NULL', 'page' => 'Events', 'path' => 'NULL', 'parent_id' => '34', 'active' => '1'],
            ['id' => '76', 'name' => 'Setup List', 'url' => '/outlet-visit/setup-list', 'icon' => 'NULL', 'page' => 'SetupList', 'path' => './pages/outletVisit/setupList.vue', 'parent_id' => '68', 'active' => '1'],
            ['id' => '77', 'name' => 'Outlet Visit Report', 'url' => '/outlet-visit/outlet-visit-report', 'icon' => 'NULL', 'page' => 'OutletVisitReport', 'path' => './pages/outletVisit/outletVisitReport.vue', 'parent_id' => '68', 'active' => '1'],

        ];

        foreach ($navigations as $item) {
            navigation::updateOrCreate(
                ['id' => $item['id']],
                $item
            );
        }

        $this->grantPagesToParentHolders([
            72 => 34, // Task Board (website) — Workstation
            73 => 34, // Task Board (mobile) — Workstation
            74 => 6,  // Event Setup (website) — Setup
            75 => 34, // Events (mobile) — Workstation
        ]);
    }

    /**
     * Sidebar / mobile modules only appear if the page ID is in user_has_pages.
     * New children are granted to anyone who already has the parent menu.
     */
    private function grantPagesToParentHolders(array $pageToParent): void
    {
        foreach (user_has_page::query()->get() as $row) {
            $ids = is_array($row->Page_Id)
                ? $row->Page_Id
                : (json_decode((string) $row->Page_Id, true) ?: []);
            $ids = array_values(array_unique(array_map('intval', $ids)));

            $changed = false;
            foreach ($pageToParent as $pageId => $parentId) {
                if (in_array((int) $parentId, $ids, true) && !in_array((int) $pageId, $ids, true)) {
                    $ids[] = (int) $pageId;
                    $changed = true;
                }
            }

            if ($changed) {
                $row->Page_Id = json_encode($ids);
                $row->save();
            }
        }
    }
}
