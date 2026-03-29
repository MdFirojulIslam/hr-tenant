<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const error = ref([]);
const salaries = ref([]);
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const searchQuery = ref("");
const isDisbursed = ref(false);
const netTotal = ref(0);

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

        isDisbursed.value =
            salaries.value.length > 0 &&
            String(salaries.value[0].disbursement_status).toUpperCase() ===
                "COMPLETED";
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const disburseSalary = async () => {
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
        }

        getData();
    } catch (error) {
        console.error(error);
    }
};

const downloadSalary = async () => {
    try {
        const response = await api.get("/export-salary-sheet", {
            responseType: "blob",
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", "salary_sheet.xlsx");
        document.body.appendChild(link);
        link.click();
    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Download failed!",
            text: "Please try again.",
        });
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

onMounted(() => getData());
</script>

<template>
    <div class="container-fluid border rounded p-3 salary-page">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="m-0">Monthly Salary Sheet</h4>
        </div>

        <!-- Filters -->
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-1">
                <label class="label-class">Salary Year</label>
                <select
                    class="form-control border-color"
                    v-model="selectedYear"
                    @change="changeDate"
                >
                    <option v-for="year in years" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
            </div>

            <div class="col-12 col-md-1">
                <label class="label-class">Salary Month</label>
                <select
                    class="form-control border-color"
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

            <div class="col-12 col-md-2">
                <label class="label-class">Search</label>
                <input
                    class="form-control border-color"
                    placeholder="Name, ID, Bank"
                    v-model="searchQuery"
                />
            </div>

            <div class="col-6 col-md-1 generate-button">
                <button
                    class="btn btn-warning"
                    @click="generateSalary"
                    :disabled="isDisbursed"
                >
                    Generate Sheet
                </button>
            </div>

            <div class="col-6 col-md-2 align-items-center net-total">
                <div
                    class="d-flex justify-content-end align-items-center mt-3 text-nowrap"
                >
                    <span class="net-total-lable me-2">NET TOTAL :</span>
                    <div class="total-class border rounded px-3 py-1 fw-bold">
                        {{ netTotal }}
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-1 ms-auto disburse-button">
                <button
                    class="btn w-100"
                    :class="isDisbursed ? 'btn-secondary' : 'btn-success'"
                    :disabled="isDisbursed"
                    @click="disburseSalary"
                >
                    {{ isDisbursed ? "Disbursed" : "Disburse Salary" }}
                </button>
            </div>

            <div class="col-12 col-md-1">
                <button class="btn btn-info w-100" @click="downloadSalary">
                    <i class="fa-solid fa-download me-2"></i> Download
                </button>
            </div>
        </div>

        <!-- Net Total -->

        <!-- Total Employees -->
        <div class="mt-4 fw-semibold border-bottom pb-2">
            Total Employees :
            <span class="badge bg-light text-dark ms-2">
                {{ filteredSalaries.length }}
            </span>
        </div>

        <!-- Table -->
        <div class="table-responsive salary-table mt-3 shadow-sm">
            <table class="table table-bordered table-hover table-sm">
                <thead>
                    <tr class="text-nowrap">
                        <th class="text-center">S/N</th>
                        <th class="text-center">EMP ID</th>
                        <th>EMPLOYEE NAME</th>
                        <th>DEPARTMENT</th>
                        <th class="text-center">GROSS</th>
                        <th class="text-center">TOTAL EARNING</th>
                        <th class="text-center">TOTAL DEDUCTION</th>
                        <th class="text-center">LOAN</th>
                        <th class="text-center">BANK ACCOUNT</th>
                        <th class="text-center">NET SALARY</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="(salary, index) in filteredSalaries"
                        :key="salary.employee_id"
                        class="text-nowrap"
                    >
                        <td class="text-center">{{ index + 1 }}</td>
                        <td class="text-center">
                            ABNB-{{ salary.employee_id }}
                        </td>
                        <td>{{ salary.full_name }}</td>
                        <td>{{ salary.department_name }}</td>
                        <td class="text-center">{{ salary.gross_salary }}</td>

                        <td class="text-center">
                            {{ salary.total_earning ?? "-" }}
                        </td>

                        <td class="text-center">
                            {{ salary.total_deduction ?? "-" }}
                        </td>

                        <td class="text-center">
                            {{ salary.monthly_loan_repayments ?? "-" }}
                        </td>

                        <td class="text-center">
                            {{ salary.bank_account_no ?? "Cash" }}
                        </td>

                        <td class="text-center fw-bold">
                            {{ salary.net_pay }}
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
    </div>
</template>

<style scoped>
.border-color {
    border: 1.8px solid #059223d5;
}

.salary-table {
    overflow-x: auto;
}

.total-class {
    background: #10d431b2;
    font-size: 15px;
}

.net-total-lable {
    font-size: 12px;
    font-weight: bold;
}

.label-class {
    font-weight: 600;
    font-size: 13px;
}

.table thead th {
    background: #4e4e4e;
    color: #fff;
    font-weight: 700;
}

.table tbody tr:hover > td {
    background: #717c73c7 !important;
}

/* MOBILE RESPONSIVE */

@media (max-width: 768px) {
    .generate-button {
        width: 100% !important;
    }
    .net-total {
        display: flex; /* Make it flex */
        justify-content: flex-start; /* ✅ Aligns items to the left horizontally */
        width: 100% !important;
        margin: 0 !important;
        align-items: center; /* optional, aligns items vertically */
    }
    .disburse-button {
        display: flex; /* Make it flex */
        justify-content: flex-start; /* ✅ Aligns items to the left horizontally */
        width: 100% !important;
        align-items: center;
    }
    h4 {
        font-size: 18px;
    }

    .table {
        font-size: 12px;
    }

    .table th,
    .table td {
        padding: 6px;
    }

    .total-class {
        font-size: 13px;
    }

    .label-class {
        font-size: 11px;
    }

    .btn {
        font-size: 12px;
        padding: 6px;
    }
}
</style>
