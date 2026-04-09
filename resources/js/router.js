import { createRouter, createWebHistory } from "vue-router";

import store from "./store/store";

const routes = [
    {
        path: "/",
        name: "Login",
        component: () => import("./pages/login.vue"),
    },
    {
        path: "/profile",
        name: "Profile",
        component: () => import("./pages/profile.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/dashboard",
        name: "Dashboard",
        component: () => import("./pages/dashboard.vue"),
        meta: {
            requireAuth: true,
            pageId: 1,
        },
    },

    {
        path: "/setup/employee/employeeList",
        name: "EmployeeList",
        component: () => import("./pages/setup/employee/employeeList.vue"),
        meta: {
            requireAuth: true,
            pageId: 7,
        },
    },
    {
        path: "/setup/employee/empexl",
        name: "EmpExl",
        component: () => import("./pages/setup/employee/empExl.vue"),
        meta: {
            requireAuth: true,
            pageId: 8,
        },
    },
    {
        path: "/setup/employee/empdetails/:id",
        name: "EmpDetails",
        component: () => import("./pages/setup/employee/employeeDetails.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/setup/employee/emp/:id?",
        name: "Emp",
        component: () => import("./pages/setup/employee/employee.vue"),
        meta: {
            requireAuth: true,
        },
        children: [
            {
                path: "/setup/employee/employee/:id?",
                name: "Employee",
                component: () =>
                    import("./pages/setup/employee/employeeInfo.vue"),
                meta: {
                    requireAuth: true,
                },
            },
            {
                path: "/setup/employee/personal/:id?",
                name: "Personal",
                component: () =>
                    import("./pages/setup/employee/personalInfo.vue"),
                meta: {
                    requireAuth: true,
                },
            },
            {
                path: "/setup/employee/official/:id?",
                name: "Official",
                component: () =>
                    import("./pages/setup/employee/officialInfo.vue"),
                meta: {
                    requireAuth: true,
                },
            },
            {
                path: "/setup/employee/professional/:id?",
                name: "Professional",
                component: () =>
                    import("./pages/setup/employee/professionalInfo.vue"),
                meta: {
                    requireAuth: true,
                },
            },
        ],
    },
    {
        path: "/setup/employee/active-employee",
        name: "ActiveEmployee",
        component: () => import("./pages/setup/employee/activeEmployee.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/setup/employee/without-account",
        name: "WithoutAccount",
        component: () => import("./pages/setup/employee/withoutAccount.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/setup/employee/inactive",
        name: "Inactive",
        component: () => import("./pages/setup/employee/inactiveEmployee.vue"),
        meta: {
            requireAuth: true,
        },
    },

    {
        path: "/security/user-registration",
        name: "UserRegistration",
        component: () => import("./pages/security/userRegistration.vue"),
        meta: {
            requireAuth: true,
            pageId: 3,
        },
    },

    {
        path: "/security/user-component-permission",
        name: "UserComponentPermission",
        component: () => import("./pages/security/userComponentPermission.vue"),
        meta: {
            requireAuth: true,
            pageId: 56,
        },
    },
    {
        path: "/security/create-role",
        name: "CreateRole",
        component: () => import("./pages/security/createRole.vue"),
        meta: {
            requireAuth: true,
            pageId: 4,
        },
    },

    {
        path: "/security/user-page-permission",
        name: "UserPagePermission",
        component: () => import("./pages/security/userPagePermission.vue"),
        meta: {
            requireAuth: true,
            pageId: 5,
        },
    },
    {
        path: "/attendance/attendance",
        name: "Attendance",
        component: () => import("./pages/attendance/attendance.vue"),
        meta: {
            requireAuth: true,
            pageId: 10,
        },
    },
    {
        path: "/attendance/fetch-attendance",
        name: "FetchAttendance",
        component: () => import("./pages/attendance/fetchAttendance.vue"),
        meta: {
            requireAuth: true,
            pageId: 22,
        },
    },
    {
        path: "/attendance/field-force-attendance",
        name: "FieldForceAttendance",
        component: () =>
            import("./pages/attendance/field-force-attendance.vue"),
        meta: {
            requireAuth: true,
            pageId: 32,
        },
    },
    {
        path: "/setup/assetInformation",
        name: "AssetInformation",
        component: () => import("./pages/setup/assets/assetInformation.vue"),
        meta: {
            requireAuth: true,
            pageId: 18,
        },
    },
    {
        path: "/setup/emp-assets-info",
        name: "EmpAssetsInfo",
        component: () => import("./pages/setup/assets/empAssetInfo.vue"),
        meta: {
            requireAuth: true,
            pageId: 20,
        },
    },
    {
        path: "/security/change-password",
        name: "ChangePassword",
        component: () => import("./pages/security/changePassword.vue"),
        meta: {
            requireAuth: true,
            pageId: 23,
        },
    },
    {
        path: "/leave/leave-application-manual",
        name: "LeaveApplicationManual",
        component: () => import("./pages/leave/leaveApplicationManual.vue"),
        meta: {
            requireAuth: true,
            pageId: 26,
        },
    },
    {
        path: "/leave/hr-approval",
        name: "HRApproval",
        component: () => import("./pages/leave/hrApproval.vue"),
        meta: {
            requireAuth: true,
            pageId: 24,
        },
    },
    {
        path: "/leave/holiday",
        name: "Holiday",
        component: () => import("./pages/leave/holiday.vue"),
        meta: {
            requireAuth: true,
            pageId: 14,
        },
    },
    {
        path: "/leave/leave-application",
        name: "LeaveApplication",
        component: () => import("./pages/leave/leaveApplication.vue"),
        meta: {
            requireAuth: true,
            pageId: 15,
        },
    },
    {
        path: "/report/certificate",
        name: "Certificate",
        component: () => import("./pages/report/certificate.vue"),
        meta: {
            requireAuth: true,
            pageId: 25,
        },
    },
    {
        path: "/leave/supervisor-approval",
        name: "SupervisorApproval",
        component: () => import("./pages/leave/supervisorApproval.vue"),
        meta: {
            requireAuth: true,
            pageId: 16,
        },
    },

    {
        path: "/expense/expense-form",
        name: "ExpenseForm",
        component: () => import("./pages/expense/expenseForm.vue"),
        meta: {
            requireAuth: true,
            pageId: 28,
        },
    },
    {
        path: "/expense/expense-records",
        name: "ExpenseRecords",
        component: () => import("./pages/expense/expenseRecords.vue"),
        meta: {
            requireAuth: true,
            pageId: 29,
        },
    },

    {
        path: "/report/expense-report",
        name: "ExpenseReport",
        component: () => import("./pages/report/expenseReport.vue"),
        meta: {
            requireAuth: true,
            pageId: 30,
        },
    },
    {
        path: "/report/expense-details/:id/:month/:year",
        name: "ExpenseDetails",
        component: () => import("./pages/report/component/expenseDetails.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/report/leave-report",
        name: "LeaveReport",
        component: () => import("./pages/report/leaveReport.vue"),
        meta: {
            requireAuth: true,
            pageId: 31,
        },
    },
    {
        path: "/report/attendance-report",
        name: "AttendanceReport",
        component: () => import("./pages/report/AttendanceReport.vue"),
        meta: {
            requireAuth: true,
            pageId: 12,
        },
    },

    {
        path: "/setup/employee/organizational-hierarchy",
        name: "OrganizationalHierarchy",
        component: () => import("./pages/setup/employee/hierarchy.vue"),
        meta: {
            requireAuth: true,
            pageId: 33,
        },
    },
    {
        path: "/workstation/notes",
        name: "Notes",
        component: () => import("./pages/workstation/notes.vue"),
        meta: {
            requireAuth: true,
            pageId: 35,
        },
    },
    {
        path: "/workstation/trash-note/",
        name: "TrashedNote",
        component: () => import("./pages/workstation/trashedNote.vue"),
        meta: {
            requireAuth: true,
        },
    },

    {
        path: "/vehicle/vehicle-setup",
        name: "VehicleSetup",
        component: () => import("./pages/setup/vehicle/vehicleSetup.vue"),
        meta: {
            requireAuth: true,
            pageId: 37,
        },
    },
    {
        path: "/vehicle/vehicle-requisition",
        name: "VehicleRequisition",
        component: () => import("./pages/vehicle/vehicleRequisition.vue"),
        meta: {
            requireAuth: true,
            pageId: 38,
        },
    },
    {
        path: "/vehicle/vehicle-requisition-approval",
        name: "VehicleRequisitionApproval",
        component: () =>
            import("./pages/vehicle/vehicleRequisitionApproval.vue"),
        meta: {
            requireAuth: true,
            pageId: 39,
        },
    },
    {
        path: "/expense/expense-permission",
        name: "ExpensePermission",
        component: () => import("./pages/expense/expensePermission.vue"),
        meta: {
            requireAuth: true,
            pageId: 40,
        },
    },
    {
        path: "/payroll/salary-setup",
        name: "SalarySetup",
        component: () => import("./pages/payroll/salarySetup.vue"),
        meta: {
            requireAuth: true,
            pageId: 42,
        },
    },
    {
        path: "/payroll/salary-adjustment",
        name: "SalaryAdjustment",
        component: () => import("./pages/payroll/salaryAdjustment.vue"),
        meta: {
            requireAuth: true,
            pageId: 43,
        },
    },
    {
        path: "/payroll/employee-loan",
        name: "EmployeeLoan",
        component: () => import("./pages/payroll/employeeLoan.vue"),
        meta: {
            requireAuth: true,
            pageId: 44,
        },
    },
    {
        path: "/payroll/salary-sheet",
        name: "SalarySheet",
        component: () => import("./pages/payroll/salarySheet.vue"),
        meta: {
            requireAuth: true,
            pageId: 45,
        },
    },

    {
        path: "/payroll/salary-disbursement",
        name: "SalaryDisbursement",
        component: () => import("./pages/payroll/salaryDisbursement.vue"),
        meta: {
            requireAuth: true,
            pageId: 51,
        },
    },
    {
        path: "/payroll/salary-payslip",
        name: "SalaryPayslip",
        component: () => import("./pages/payroll/salaryPayslip.vue"),
        meta: {
            requireAuth: true,
            pageId: 57,
        },
    },
    {
        path: "/payroll/bonus_sheet",
        name: "BonusSheet",
        component: () => import("./pages/payroll/bonusSheet.vue"),
        meta: {
            requireAuth: true,
            pageId: 58,
        },
    },
    {
        path: "/payroll/bonus-disbursement",
        name: "BonusDisbursement",
        component: () => import("./pages/payroll/bonusDisbursement.vue"),
        meta: {
            requireAuth: true,
            pageId: 59,
        },
    },
    {
        path: "/separation/separation-request",
        name: "SeparationRequest",
        component: () => import("./pages/separation/separationRequest.vue"),
        meta: {
            requireAuth: true,
            pageId: 53,
        },
    },

    {
        path: "/separation/separation-approval",
        name: "SeparationApproval",
        component: () => import("./pages/separation/separationApproval.vue"),
        meta: {
            requireAuth: true,
            pageId: 54,
        },
    },

    {
        path: "/separation/all-separations",
        name: "AllSeparations",
        component: () => import("./pages/separation/allSeparations.vue"),
        meta: {
            requireAuth: true,
            pageId: 55,
        },
    },

    {
        path: "/payroll/salary-details/:id?",
        name: "SalaryDetails",
        component: () => import("./pages/payroll/salaryDetails.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/workstation/itinerary",
        name: "Itinerary",
        component: () => import("./pages/workstation/itinerary.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/workstation/itinerary/edit/:id",
        name: "EditItinerary",
        component: () =>
            import("./pages/workstation/component/itineraryForm.vue"),
        props: (route) => ({
            itineraryId: Number(route.params.id),
            viewMode: false,
        }),
    },
    {
        path: "/workstation/itinerary/view/:id",
        name: "ViewItinerary",
        component: () =>
            import("./pages/workstation/component/itineraryForm.vue"),
        props: (route) => ({
            itineraryId: Number(route.params.id),
            viewMode: true,
        }),
    },
    {
        path: "/workstation/itinerary/new",
        name: "NewItinerary",
        component: () =>
            import("./pages/workstation/component/itineraryForm.vue"),
        props: { viewMode: false },
    },
    {
        path: "/workstation/daily-itinerary",
        name: "DailyItinerary",
        component: () => import("./pages/workstation/dailyItinerary.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/workstation/daily-itinerary-list",
        name: "ItineraryList",
        component: () => import("./pages/workstation/itineraryList.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/workstation/admin-itinerary-list",
        name: "AdminItineraryList",
        component: () => import("./pages/workstation/adminItineraryList.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/roster-setup",
        name: "RosterSetup",
        component: () => import("./pages/attendance/rosterSetup.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/roster-report-list",
        name: "RosterReportList",
        component: () => import("./pages/attendance/rosterReportList.vue"),
        meta: {
            requireAuth: true,
        },
    },
    {
        path: "/attendance/roster-setup",
        name: "createRoster",
        component: () =>
            import("@/pages/attendance/component/createRoster.vue"),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const userPermissions = store.getters.getPermissions || [];
    const token = store.getters.getToken;

    if (to.path === "/" && token === 0) {
        return next();
    }

    if (to.meta.pageId && !userPermissions.includes(to.meta.pageId)) {
        return next("/");
    }

    if (to.meta.requireAuth && token === 0) {
        return next({ name: "Login" });
    }

    next();
});

export default router;
