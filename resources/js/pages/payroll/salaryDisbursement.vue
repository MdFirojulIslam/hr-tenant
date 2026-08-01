<script setup>
import { ref, onMounted, computed, watch, defineAsyncComponent } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

/* ================= STATE ================= */
const disburse = ref([]);
const bankTotal = ref(0);
const cashTotal = ref(0);
const netTotal = ref(0);
const error = ref([]);

const monthStatus = ref("PENDING");

const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const searchQuery = ref("");

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

/* ================= STATIC DATA ================= */
const years = [
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
];

const months = [
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
];
const monthName = computed(() => {
    return months[selectedMonth.value - 1];
});

const selectedMonthName = computed(() => months[selectedMonth.value - 1]);

/* ================= COMPLETE ================= */
const setcomplete = async () => {
    const result = await Swal.fire({
        title: "Confirm Disbursement",
        html: `<p>Confirm disbursement for <strong>${selectedMonthName.value} ${selectedYear.value}</strong>?</p>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, Confirm",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#048a1fd5",
        cancelButtonColor: "#bd360dd5",
    });

    if (result.isConfirmed) {
        try {
            await api.post("/salary-disbursement-complete", {
                year: selectedYear.value,
                month: selectedMonth.value,
            });

            Swal.fire(
                "Completed!",
                `Disbursement for ${selectedMonthName.value} ${selectedYear.value} completed.`,
                "success",
            );

            getData();
        } catch {
            Swal.fire("Error", "Failed to complete disbursement", "error");
        }
    }
};

/* ================= FETCH DATA ================= */
const getData = async () => {
    try {
        const response = await api.get("/salary-disbursement", {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        disburse.value = response.data.salaries;
        netTotal.value = response.data.net_total;
        bankTotal.value = response.data.bank_total;
        cashTotal.value = response.data.cash_total;

        if (disburse.value.length > 0) {
            monthStatus.value = String(
                disburse.value[0].disbursement_status,
            ).toUpperCase();
        } else {
            monthStatus.value = "PENDING";
        }
    } catch (err) {
        error.value = err.message || "Failed to load data";
    }
};

const changeDate = () => getData();

/* ================= FILTER ================= */
const filteredDisbursement = computed(() => {
    if (!searchQuery.value) return disburse.value;

    const q = searchQuery.value.toLowerCase();

    return disburse.value.filter(
        (s) =>
            (s.full_name || "").toLowerCase().includes(q) ||
            String(s.employee_id || "").includes(q) ||
            String(s.bank_account_no || "").includes(q),
    );
});

/* ================= INIT ================= */
onMounted(() => getData());
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
        <header
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"
        >
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h2 class="h4 fw-bold text-dark m-0">
                        {{ monthName }}'s Salary Disbursement
                    </h2>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button
                    class="btn btn-action btn-outline-secondary"
                    @click="exportSalary"
                >
                    <i class="fa-solid fa-arrow-down-to-line me-2"></i>Export
                </button>
                <button
                    class="btn btn-action"
                    :class="
                        monthStatus === 'COMPLETED'
                            ? 'btn-secondary'
                            : 'btn-success'
                    "
                    :disabled="monthStatus === 'COMPLETED'"
                    @click="setcomplete"
                >
                    <i
                        :class="
                            monthStatus === 'COMPLETED'
                                ? 'fa-solid fa-check me-2'
                                : 'fa-solid fa-paper-plane me-2'
                        "
                    ></i>

                    {{ monthStatus === "COMPLETED" ? "Released" : "Release" }}
                </button>
            </div>
        </header>

        <section class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-icon bg-primary-soft text-primary">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <span class="kpi-label">Total Employees</span>
                        <h4 class="kpi-value mb-0">
                            {{ filteredDisbursement.length || 0 }}
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
        <main class="content-card">
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

            <!-- TABLE -->
            <div class="table-responsive d-lg-block">
                <table
                    class="table table-bordered table-sm custom-payroll-table"
                >
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th class="text-center">#</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Payment Method</th>
                            <th>Payable Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(item, i) in filteredDisbursement"
                            :key="i"
                            class="text-nowrap"
                        >
                            <td class="text-center">{{ i + 1 }}</td>
                            <td>ABNB{{ item.employee_id }}</td>
                            <td class="text-nowrap">{{ item.full_name }}</td>
                            <td>
                                {{ item.bank_account_no ?? "Cash" }}
                            </td>
                            <td>{{ item.final_payments }}</td>
                        </tr>

                        <tr v-if="filteredDisbursement.length === 0">
                            <td colspan="5" class="text-center py-3">
                                No data found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div
                v-if="filteredDisbursement.length === 0"
                class="text-center py-5"
            >
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
    font-size: 10px;
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
    background-color: #563535;
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
