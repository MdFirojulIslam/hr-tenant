<script setup>
import { ref, onMounted, watch, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const error = ref([]);
const disburse = ref([]);
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const searchQuery = ref("");

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
        const reponse = await api.get(`/salary-disbursement`, {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });
        disburse.value = reponse.data;
        console.log("Disbursement data:", disburse.value);
    } catch (err) {
        if (err.response && err.response.status === 422) {
            console.error(
                "Authentication failed, redirect to login if router is available."
            );
        } else {
            error.value = err.message || "Error fetching data.";
        }
    }
};

const filteredDisbursement = computed(() => {
    if (!searchQuery.value) {
        return disburse.value;
    }

    const q = searchQuery.value.toLowerCase();

    return disburse.value.filter((salary) => {
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

const downloadBankDisbursement = async () => {
    try {
        const response = await api.get("/salary-disbursement-bank", {
            responseType: "blob",
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.download = `salary_disbursement_${selectedYear.value}_${selectedMonth.value}.pdf`;
        document.body.appendChild(link);
        link.click();
    } catch (error) {
        console.error("Error downloading salary disbursement:", error);
        Swal.fire({
            icon: "error",
            title: "Download failed!",
            text: "Please try again.",
        });
    }
};

const downloadCashDisbursement = async () => {
    try {
        const response = await api.get("/salary-disbursement-cash", {
            responseType: "blob",
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.download = `salary_disbursement_${selectedYear.value}_${selectedMonth.value}.pdf`;
        document.body.appendChild(link);
        link.click();
    } catch (error) {
        console.error("Error downloading salary disbursement:", error);
        Swal.fire({
            icon: "error",
            title: "Download failed!",
            text: "Please try again.",
        });
    }
};

onMounted(() => getData());
</script>

<template>
    <div
        class="border rounded container-fluid h-100 d-flex flex-column p-0 m-0"
    >
        <div class="m-3">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center py-3">
                <h2 class="m-0">Monthly Salary Disbursement</h2>
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

                <div class="ms-auto">
                    <input
                        type="text"
                        class="form-control mt-4 mx-7 border-color"
                        placeholder="Name, ID, or Bank A/C"
                        v-model="searchQuery"
                    />
                </div>

                <div class="ms-3">
                    <button
                        class="btn btn-info mt-4"
                        @click="downloadBankDisbursement"
                    >
                        Download Bank Disbursement
                    </button>
                </div>
                <div class="ms-3">
                    <button
                        class="btn btn-info mt-4"
                        @click="downloadCashDisbursement"
                    >
                        Download Cash Disbursement
                    </button>
                </div>
            </div>

            <div class="mt-5">
                <!-- Total Employees Row -->
                <div
                    class="d-flex align-items-center px-2 py-2 fw-semibold border-bottom bg-light"
                >
                    <span class="me-2">Total Employees:</span>
                    <span class="bg-white border rounded px-3 py-1 fw-medium">
                        {{ filteredDisbursement.length }}</span
                    >
                </div>

                <div class="table-responsive shadow-sm bg-light">
                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th class="text-center">EMPLOYEE ID</th>
                                <th>EMPLOYEE NAME</th>
                                <th class="text-center">BANK ACCOUNT</th>
                                <th class="text-center">NET SALARY</th>
                            </tr>
                        </thead>
                        <!-- <pre>{{ filteredSalaries }}</pre> -->
                        <tbody>
                            <tr
                                class="ver-align"
                                v-for="(item, index) in filteredDisbursement"
                                :key="index"
                            >
                                <td>{{ index + 1 }}</td>
                                <td class="text-center">
                                    ABNB-{{ item.employee_id }}
                                </td>
                                <td>
                                    {{ item.full_name }}
                                </td>
                                <td class="text-center">
                                    {{
                                        item.bank_account_no === null
                                            ? "Cash"
                                            : item.bank_account_no
                                    }}
                                </td>
                                <td class="text-center">{{ item.net_pay }}</td>
                            </tr>
                            <tr v-if="filteredDisbursement.length === 0">
                                <td colspan="5" class="text-center">
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
</style>
