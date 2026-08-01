<script setup>
import {
    ref,
    onMounted,
    computed,
    watch,
    defineAsyncComponent,
    onUpdated,
} from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const error = ref([]);
const salaries = ref([]);
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const searchQuery = ref("");
const isDisbursed = ref(false);
const netTotal = ref(0);
const bankTotal = ref(0);
const cashTotal = ref(0);

const bankFormat = ref("");
const cashFormat = ref("");

const isDownload = ref(false);

const downloadComponent = defineAsyncComponent(
    () => import("./component/salarySheetDownload.vue"),
);

const exportSalary = () => {
    isDownload.value = true;
};

const salaryModalClose = () => {
    isDownload.value = false;
};

const years = ref([
    "2022",
    "2023",
    "2024",
    "2025",
    "2026",
    "2027",
    "2028",
    "2029",
    "2030",
    "2031",
    "2032",
    "2033",
    "2034",
    "2035",
]);

const months = ref([
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
]);

const monthName = computed(() => {
    return months.value[selectedMonth.value - 1];
});

const changeDate = () => {
    getData();
};

const getData = async () => {
    try {
        const response = await api.get(`/salary-sheet`, {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        salaries.value = response.data.salaries;
        netTotal.value = response.data.net_total;
        bankTotal.value = response.data.bank_total;
        cashTotal.value = response.data.cash_total;

        isDisbursed.value =
            salaries.value.length > 0 &&
            String(salaries.value[0].disbursement_status).toUpperCase() ===
                "COMPLETED";

        console.log("isDisbursed", isDisbursed.value);
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const disburseSalary = async () => {
    const monthName = months.value[selectedMonth.value - 1];

    const confirm = await Swal.fire({
        title: "Are you sure wants to Doisburse?",
        text: `You are disbursing ${monthName} ${selectedYear.value} salary?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, Disburse",
    });
    if (!confirm.isConfirmed) return;
    try {
        const response = await api.post("/disburse-salary", {
            year: selectedYear.value,
            month: selectedMonth.value,
        });

        Swal.fire({
            icon: "success",
            title: response.data.message || "Salary disbursed successfully",
            timer: 1500,
            showConfirmButton: false,
        });

        isDisbursed.value = true;
        getData();
    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Disbursement failed!",
            text:
                error.response?.data?.message || "Salary disbursement failed.",
        });
    }
};

const generateSalary = async () => {
    const monthName = months.value[selectedMonth.value - 1];

    const confirm = await Swal.fire({
        title: "Are you sure?",
        text: `Do you want to generate salary for ${monthName} ${selectedYear.value}?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, Generate",
    });
    if (!confirm.isConfirmed) return;

    try {
        const response = await api.get(`/generate-salary`, {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        if (response.data.success) {
            Swal.fire({
                icon: "success",
                title: response.data.message,
                timer: 1500,
                showConfirmButton: false,
            });
            getData();
        } else {
            Swal.fire({
                icon: "warning",
                title: response.data.message,
                timer: 1500,
                showConfirmButton: false,
            });
        }
    } catch (error) {
        console.error(error);
    }
};

const filteredSalaries = computed(() => {
    if (!searchQuery.value) return salaries.value;

    const q = searchQuery.value.toLowerCase();

    return salaries.value.filter((salary) => {
        const name = (salary.full_name || "").toLowerCase();
        const eid = String(salary.employee_id || "").toLowerCase();
        const bank = String(salary.bank_account_no || "").toLowerCase();

        return name.includes(q) || eid.includes(q) || bank.includes(q);
    });
});

watch(bankFormat, (format) => {
    if (!format) return;
    downloadBank(format);
    bankFormat.value = "";
});

watch(cashFormat, (format) => {
    if (!format) return;
    downloadCash(format);
    cashFormat.value = "";
});

onMounted(() => getData());
onUpdated(() => {
    console.log("SalarySheet Updated");
});
</script>
<template>
    <component
        v-if="isDownload"
        :is="downloadComponent"
        :selectedYear="selectedYear"
        :selectedMonth="selectedMonth"
        @modal-close="salaryModalClose"
    />

    <div class="payroll-container py-2">
        <!-- Page Header & Global Actions -->
        <header
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"
        >
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h2 class="h4 fw-bold text-dark m-0">
                        {{ monthName }}'s Salary Sheet
                    </h2>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button
                    class="btn btn-action btn-outline-secondary export-button"
                    @click="exportSalary"
                >
                    <i class="fa-solid fa-arrow-down-to-line me-2"></i>Export
                </button>

                <button
                    v-if="!isDisbursed"
                    class="btn btn-action btn-primary generate-button"
                    @click="generateSalary"
                >
                    <i class="fa-solid fa-arrows-rotate me-2"></i>Generate
                </button>
                <button
                    class="btn btn-action disburse-button"
                    :class="isDisbursed ? 'btn-secondary' : 'btn-success'"
                    :disabled="isDisbursed"
                    @click="disburseSalary"
                >
                    <i
                        :class="
                            isDisbursed
                                ? 'fa-solid fa-check me-2'
                                : 'fa-solid fa-paper-plane me-2'
                        "
                    ></i>
                    {{ isDisbursed ? "Disbursed" : "Disburse" }}
                </button>
            </div>
        </header>

        <!-- Top KPI Cards -->
        <section class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-icon bg-primary-soft text-primary">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <span class="kpi-label">Total Employees</span>
                        <h4 class="kpi-value mb-0">
                            {{ filteredSalaries.length || 0 }}
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-icon bg-success-soft text-success">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <span class="kpi-label">Total Net Pay</span>
                        <h4 class="kpi-value mb-0">
                            {{ netTotal || "0.00" }}
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-icon bg-info-soft text-info">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div>
                        <span class="kpi-label">Bank Transfers</span>
                        <h4 class="kpi-value mb-0">
                            {{ bankTotal || "0.00" }}
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-icon bg-warning-soft text-warning">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <span class="kpi-label">Cash Payments</span>
                        <h4 class="kpi-value mb-0">
                            {{ cashTotal || "0.00" }}
                        </h4>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content Card -->
        <main class="content-card">
            <!-- Toolbar Filter & Search Header -->
            <div class="toolbar-header">
                <div
                    class="d-flex flex-wrap align-items-center justify-content-between gap-3"
                >
                    <!-- Month & Year Selectors -->
                    <div class="d-flex align-items-center gap-2">
                        <div class="select-wrapper">
                            <i class="fa-regular fa-calendar select-icon"></i>
                            <select
                                class="form-select month-select"
                                v-model="selectedMonth"
                                @change="changeDate"
                            >
                                <option
                                    v-for="(month, index) in months"
                                    :key="month"
                                    :value="index + 1"
                                >
                                    {{ month }}
                                </option>
                            </select>
                        </div>

                        <div class="select-wrapper">
                            <select
                                class="form-select year-select"
                                v-model="selectedYear"
                                @change="changeDate"
                            >
                                <option
                                    v-for="year in years"
                                    :key="year"
                                    :value="year"
                                >
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="search-wrapper">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input
                            type="text"
                            class="form-control search-input"
                            placeholder="Search by name, ID, department..."
                            v-model="searchQuery"
                        />
                    </div>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="table-responsive d-lg-block">
                <table
                    class="table table-bordered table-sm custom-payroll-table"
                >
                    <thead>
                        <tr class="text-nowrap">
                            <th class="text-center">#</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Gross</th>
                            <th>Allowance</th>
                            <th>Deduct</th>
                            <th class="text-center">Net Pay</th>
                            <th class="text-center">Loan Deduct</th>
                            <th class="text-center">Final Payments</th>
                            <th class="text-center">Payment Method</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(salary, index) in filteredSalaries"
                            :key="salary.employee_id"
                            class="text-nowrap"
                        >
                            <td class="text-center">{{ index + 1 }}</td>
                            <td>ABNB{{ salary.employee_id }}</td>
                            <td>{{ salary.full_name }}</td>
                            <td>{{ salary.department_name }}</td>
                            <td>
                                {{ salary.gross_salary }}
                            </td>

                            <td>
                                {{ salary.total_earning ?? "-" }}
                            </td>

                            <td>
                                {{ salary.total_deduction ?? "-" }}
                            </td>

                            <td class="text-center fw-bold">
                                {{ salary.net_pay }}
                            </td>

                            <td class="text-center">
                                {{ salary.monthly_loan_repayments ?? "-" }}
                            </td>

                            <td class="text-center">
                                {{ salary.final_payments ?? "-" }}
                            </td>

                            <td class="text-center">
                                {{ salary.bank_account_no ?? "Cash" }}
                            </td>
                        </tr>

                        <tr v-if="filteredSalaries.length === 0">
                            <td colspan="10" class="text-center">
                                No matching salaries found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card List View -->

            <div v-if="filteredSalaries.length === 0" class="text-center py-5">
                <i
                    class="fa-solid fa-receipt fa-2x text-muted mb-2 opacity-50"
                ></i>
                <p class="text-muted small mb-0">No matching salary entries.</p>
            </div>
        </main>
    </div>
</template>

<style scoped>
/* Core Page Layout */
.payroll-container {
    max-width: 1400px;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
    font-family:
        -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Button & Action Styles */
.btn-action {
    font-size: 0.875rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.export-button {
    background-color: #027a48;
    color: white;
}
.export-button:hover {
    background: #035332;
}
.generate-button:hover {
    background: #042256;
}
.disburse-button:hover {
    background: #064028;
}

/* KPI Card Styles */
.kpi-card {
    background: #ffffff;
    border: 1px solid #eaecf0;
    border-radius: 0.75rem;
    padding: 0.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
}

.kpi-icon {
    width: 30px;
    height: 40px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.kpi-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #3b414f;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    display: block;
    margin-bottom: 0.25rem;
}

.kpi-value {
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: 1px;
    color: #000000;
}

/* Soft Color Badges & Backgrounds */
.bg-primary-soft {
    background-color: #f0f5ff;
}
.bg-success-soft {
    background-color: #ecfdf3;
}
.bg-info-soft {
    background-color: #f0f9ff;
}
.bg-warning-soft {
    background-color: #fff8f0;
}

.badge-soft-success {
    background-color: #ecfdf3;
    color: #027a48;
    font-weight: 600;
}
.badge-soft-warning {
    background-color: #fff8f0;
    color: #b54708;
    font-weight: 600;
}
.badge-soft-info {
    background-color: #f0f9ff;
    color: #026aa2;
    font-weight: 600;
}
.badge-department {
    background-color: #f2f4f7;
    color: #344054;
    font-weight: 500;
    font-size: 0.75rem;
}

/* Main Content Card */
.content-card {
    margin-top: 30px;
    /* background: #ffffff;
   border: 1px solid #eaecf0;
   border-radius: 0.75rem;
   box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
   overflow: hidden; */
}

/* Toolbar Header Elements */
.toolbar-header {
    padding-bottom: 0.5rem;
    /* border-bottom: 1px solid #eaecf0;
   background-color: none; */
}

.select-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.select-icon {
    position: absolute;
    left: 0.75rem;
    color: #0d0e10;
    font-size: 0.875rem;
    pointer-events: none;
}

.month-select {
    padding-left: 2.25rem;
    padding-right: 2.5rem;
    height: 30px;
    border-radius: 0.5rem;
    border: 1px solid #d0d5dd;
    font-size: 14px;
}
.year-select {
    padding-left: 1rem;
    padding-right: 3rem;
    height: 30px;
    border-radius: 0.5rem;
    border: 1px solid #d0d5dd;
    font-size: 14px;
}

.search-wrapper {
    position: relative;
    min-width: 280px;
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #667085;
    font-size: 0.875rem;
}

.search-input {
    padding-left: 2.25rem;
    height: 30px;
    border-radius: 0.5rem;
    border: 1px solid #d0d5dd;
    font-size: 14px;
}

/* Desktop Data Table */
.custom-payroll-table thead th {
    background-color: #423434;
    color: #ffffff;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding-top: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #eaecf0;
}

.custom-payroll-table tbody td {
    padding-top: 6px;
    padding-bottom: 6px;
    font-size: 0.875rem;
}

.custom-payroll-table tbody tr:hover td {
    background-color: #7e6666;
    color: #fff;
    font-size: 11px;
}

/* User Avatars */
.avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #e0e7ff;
    color: #4338ca;
    font-weight: 700;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.avatar-circle.sm {
    width: 32px;
    height: 32px;
    font-size: 0.75rem;
}

/* Mobile View Styles */
.mobile-salary-card {
    border: 1px solid #eaecf0;
    border-radius: 0.5rem;
    padding: 1rem;
    background-color: #ffffff;
}

.mobile-card-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    background-color: #f9fafb;
    padding: 0.75rem;
    border-radius: 0.375rem;
}

.mobile-label {
    font-size: 0.7rem;
    color: #667085;
    text-transform: uppercase;
    font-weight: 600;
    display: block;
}

.mobile-value {
    font-size: 0.875rem;
    font-weight: 500;
}

.fs-7 {
    font-size: 0.75rem;
}
</style>
