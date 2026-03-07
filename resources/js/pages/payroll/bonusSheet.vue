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

// Fetch department and navigation data
const getData = async () => {
    try {
        const response = await api.get(`/bonus-sheet`, {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });
        salaries.value = response.data.data;
        netTotal.value = response.data.total_bonus_amount;
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

// Handle form submission
const generateSalary = async () => {
    try {
        const response = await api.get(`/generate-bonus`, {
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

const disburseBonus = async () => {
    try {
        const response = await api.post("/disburse-bonus", {
            year: selectedYear.value,
            month: selectedMonth.value,
        });

        Swal.fire({
            position: "center",
            icon: "success",
            title: response.data.message || "Bonus disbursed successfully",
            showConfirmButton: false,
            timer: 1500,
        });
        isDisbursed.value = true;
        getData();
    } catch (error) {
        console.error("Error disbursing bonus:", error);

        const errorMessage =
            error.response?.data?.message ||
            "Bonus disbursement failed. Please try again.";

        Swal.fire({
            icon: "error",
            title: "Disbursement failed!",
            text: errorMessage,
        });
    }
};

const downloadSalary = async () => {
    try {
        const response = await api.get("/export-bonus-sheet", {
            responseType: "blob",
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", "bonus_sheet.xlsx");
        document.body.appendChild(link);
        link.click();
    } catch (error) {
        console.error("Error downloading bonus sheet:", error);
        Swal.fire({
            icon: "error",
            title: "Download failed!",
            text: "Please try again.",
        });
    }
};

const updatePercentage = async (salary) => {
    try {
        const response = await api.post("/update-bonus-percentage", {
            id: salary.id,
            percentage: salary.bonus_percentages,
        });

        if (response.data.success) {
            salary.bonus_amounts = response.data.bonus_amounts;

            Swal.fire({
                icon: "success",
                title: "Updated Successfully",
                timer: 1000,
                showConfirmButton: false,
            });
        }
    } catch (error) {
        console.error("Update failed:", error);
        Swal.fire({
            icon: "error",
            title: "Update Failed",
        });
    }
};

// Computed property for filtered salaries
const filteredSalaries = computed(() => {
    if (!searchQuery.value) return salaries.value;

    const q = searchQuery.value.toLowerCase();

    return salaries.value.filter((salary) => {
        return (
            (salary.full_name || "").toLowerCase().includes(q) ||
            String(salary.employee_id || "").includes(q) ||
            (salary.bank_account_no || "").toLowerCase().includes(q)
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
                <h2 class="m-0">Yearly Bonus Sheet</h2>
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

                <div class="ms-4">
                    <input
                        type="text"
                        class="form-control mt-4 mx-7 border-color"
                        placeholder="Name, ID, or Bank A/C"
                        v-model="searchQuery"
                    />
                </div>

                <div class="ms-5">
                    <button
                        class="btn btn-warning mt-4 mx-2"
                        @click="generateSalary"
                        :disabled="isDisbursed"
                    >
                        Generate Bonus Sheet
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
                        @click="disburseBonus"
                    >
                        {{ isDisbursed ? "DISBURSED" : "DISBURSE BONUS" }}
                    </button>
                </div>

                <div class="ms-3">
                    <button class="btn btn-info mt-4" @click="downloadSalary">
                        Download Bonus Sheet
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
                                <th class="text-center">DOJ</th>
                                <th class="text-center">GROSS SALARY</th>
                                <th class="text-center">BASIC SALARY</th>
                                <th class="text-center">PERCENTAGES</th>
                                <th class="text-center">BONUS AMOUNT</th>
                                <th class="text-center">FESTIVAL NAME</th>
                                <th class="text-center">BANK ACCOUNT</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(salary, index) in filteredSalaries"
                                :key="index"
                            >
                                <td>{{ index + 1 }}</td>

                                <td class="text-center">
                                    ABNB-{{ salary.Employee_Id }}
                                </td>

                                <td>{{ salary.full_name }}</td>

                                <td>{{ salary.department_name }}</td>
                                <td class="text-center">
                                    {{ salary.date_of_join }}
                                </td>
                                <td class="text-center">
                                    {{ salary.gross_salary }}
                                </td>

                                <td class="text-center">
                                    {{ salary.basic_salary }}
                                </td>

                                <input
                                    type="number"
                                    class="form-control text-center"
                                    v-model="salary.bonus_percentages"
                                    @keydown.enter.prevent="
                                        updatePercentage(salary)
                                    "
                                    min="0"
                                    max="100"
                                    style="width: 80px; display: inline-block"
                                    :disabled="isDisbursed"
                                />

                                <td class="text-center">
                                    {{ salary.bonus_amounts }}
                                </td>

                                <td class="text-center">
                                    {{ salary.festival_name }}
                                </td>

                                <td class="text-center">
                                    {{
                                        salary.bank_account_no
                                            ? salary.bank_account_no
                                            : "Cash"
                                    }}
                                </td>
                            </tr>

                            <tr v-if="filteredSalaries.length === 0">
                                <td colspan="10" class="text-center">
                                    No Bonus Sheet Found.
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
.net-total-lable {
    font-size: 12px;
    margin-bottom: 1px;
    display: block;
    font-weight: bold;
}
.total-class {
    background-color: #10d431b2;
    margin-left: 10px;
    font-size: 15px;
    border-radius: none;
}
</style>
