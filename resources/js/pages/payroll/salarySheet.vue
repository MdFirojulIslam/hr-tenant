<script setup>
import { ref, onMounted, watch, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const error = ref([]);
const salaries = ref([]);
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const searchQuery = ref(""); // New ref for the search input
const isDisbursed = ref(false);
const netTotal = ref(0); // Add this

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

// Fetch department and navigation data
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
        if (err.response && err.response.status === 401) {
            // Assuming 'router' is defined elsewhere or passed as a prop
            // router.push({ name: "Login" });
            console.error(
                "Authentication failed, redirect to login if router is available.",
            );
        } else {
            error.value = err.message || "Error fetching data";
        }
    }
};

const disburseSalary = async () => {
    try {
        const response = await api.post("/disburse-salary", {
            year: selectedYear.value,
            month: selectedMonth.value,
        });

        Swal.fire({
            position: "center",
            icon: "success",
            title: response.data.message || "Salary disbursed successfully",
            showConfirmButton: false,
            timer: 1500,
        });
        isDisbursed.value = true;
        getData();
    } catch (error) {
        console.error("Error disbursing salary:", error);

        const errorMessage =
            error.response?.data?.message ||
            "Salary disbursement failed. Please try again.";

        Swal.fire({
            icon: "error",
            title: "Disbursement failed!",
            text: errorMessage,
        });
    }
};

// Handle form submission
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
                position: "middle",
                icon: "success",
                title: response.data.message,
                showConfirmButton: false,
                timer: 1500,
            });
        }
        getData();
    } catch (error) {
        console.error("Error submitting attendance:", error);
    }
};

const downloadSalary = async () => {
    try {
        const response = await api.get("/export-salary-sheet", {
            responseType: "blob", // This tells Axios it's a file
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        // Create a link element and trigger download
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", "salary_sheet.xlsx");
        document.body.appendChild(link);
        link.click();
    } catch (error) {
        console.error("Error downloading salary sheet:", error);
        Swal.fire({
            icon: "error",
            title: "Download failed!",
            text: "Please try again.",
        });
    }
};

// Computed property for filtered salaries
const filteredSalaries = computed(() => {
    if (!searchQuery.value) {
        return salaries.value;
    }

    const q = searchQuery.value.toLowerCase();

    return salaries.value.filter((salary) => {
        const name = (salary.full_name || "").toLowerCase();
        const eid = (
            salary.employee_id ? String(salary.employee_id) : ""
        ).toLowerCase();
        const officialId = (
            salary.official_id ? String(salary.official_id) : ""
        ).toLowerCase();
        const bank = (
            salary.bank_account_no ? String(salary.bank_account_no) : ""
        ).toLowerCase();

        return (
            name.includes(q) ||
            eid.includes(q) ||
            officialId.includes(q) ||
            bank.includes(q)
        );
    });
});

onMounted(() => getData());
</script>

<template>
    <div
        class="border rounded container-fluid h-100 d-flex flex-column p-0 m-0"
    >
        <div class="m-3">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center py-3">
                <h2 class="m-0">Monthly Salary Sheet</h2>
            </div>

            <div class="d-flex justify-content-start">
                <div class="form-group">
                    <label>Salary Year</label>
                    <select
                        class="form-control border-color"
                        v-model="selectedYear"
                        @change="changeDate"
                    >
                        <option
                            v-for="(year, index) in years"
                            :key="year"
                            :value="year"
                        >
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div class="form-group mx-4">
                    <label>Salary Month</label>
                    <select
                        class="form-control border-color border-color"
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

                <div class="ms-3">
                    <input
                        type="text"
                        class="form-control mt-4 mx-7 border-color"
                        placeholder="Name, ID, or Bank A/C"
                        v-model="searchQuery"
                    />
                </div>

                <div class="ms-5" v-if="!isDisbursed">
                    <button
                        class="btn btn-warning mt-4 mx-2"
                        @click="generateSalary"
                    >
                        Generate Sheet
                    </button>
                </div>

                <div
                    class="ms-auto justify-content-center align-items-center d-flex"
                >
                    <label class="net-total-lable">NET TOTAL :</label>
                    <div class="total-class border rounded px-3 py-1 fw-medium">
                        {{ netTotal }}
                    </div>
                </div>

                <div class="ms-auto">
                    <button
                        class="btn mt-4"
                        :class="isDisbursed ? 'btn-secondary' : 'btn-success'"
                        :disabled="isDisbursed"
                        @click="disburseSalary"
                    >
                        {{ isDisbursed ? "DISBURSED" : "DISBURSE SALARY" }}
                    </button>
                </div>

                <div class="ms-3">
                    <button class="btn btn-info mt-4" @click="downloadSalary">
                        DOWNLOAD SHEET
                    </button>
                </div>
            </div>

            <div class="mt-5">
                <!-- Total Employees Row -->
                <div
                    class="d-flex align-items-center px-2 py-2 fw-semibold border-bottom bg-light"
                >
                    <span class="me-2">Total Employees:</span>
                    <span class="bg-white border rounded px-3 py-1 fw-medium">{{
                        filteredSalaries.length
                    }}</span>
                </div>

                <div class="table-responsive shadow-sm bg-light">
                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th class="text-center">EMPLOYEE ID</th>
                                <th>EMPLOYEE NAME</th>
                                <th>DEPARTMENT NAME</th>
                                <th class="text-center">GROSS</th>
                                <th class="text-center">TOTAL EARNING</th>
                                <th class="text-center">TOTAL DEDUCTION</th>
                                <th class="text-center">LOAN DEDUCTION</th>
                                <th class="text-center">BANK ACCOUNT</th>
                                <th class="text-center">NET SALARY</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                class="ver-align"
                                v-for="(salary, index) in filteredSalaries"
                            >
                                <td>{{ index + 1 }}</td>
                                <td class="text-center">
                                    ABNB-{{ salary.employee_id }}
                                </td>
                                <td>{{ salary.full_name }}</td>
                                <td>{{ salary.department_name }}</td>
                                <td class="text-center">
                                    {{ salary.gross_salary }}
                                </td>
                                <td class="text-center">
                                    {{
                                        salary.total_earning == null
                                            ? "-"
                                            : salary.total_earning
                                    }}
                                </td>
                                <td class="text-center">
                                    {{
                                        salary.total_deduction == null
                                            ? "-"
                                            : salary.total_deduction
                                    }}
                                </td>
                                <td class="text-center">
                                    {{
                                        salary.monthly_loan_repayments == null
                                            ? "-"
                                            : salary.monthly_loan_repayments
                                    }}
                                </td>
                                <td class="text-center">
                                    {{
                                        salary.bank_account_no === null
                                            ? "Cash"
                                            : salary.bank_account_no
                                    }}
                                </td>
                                <td class="text-center">
                                    {{ salary.net_pay }}
                                </td>
                            </tr>
                            <tr v-if="filteredSalaries.length === 0">
                                <td colspan="9" class="text-center">
                                    No matching salaries found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.border-color {
    border: 1.8px solid #059223d5;
    border-radius: none;
}
.total-class {
    background-color: #10d431b2;
    margin-left: 10px;
    font-size: 15px;
    border-radius: none;
}
.net-total-lable {
    font-size: 12px;
    margin-bottom: 1px;
    display: block;
    font-weight: bold;
}
</style>
