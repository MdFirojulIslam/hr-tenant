<script setup>
import { ref, onMounted, watch, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const error = ref([]);
const salaries = ref([]);
const selectedMonth = ref(new Date().getMonth());
const selectedYear = ref(new Date().getFullYear());
const searchQuery = ref(""); // New ref for the search input

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

        salaries.value = response.data;
    } catch (err) {
        if (err.response && err.response.status === 401) {
            // Assuming 'router' is defined elsewhere or passed as a prop
            // router.push({ name: "Login" });
            console.error(
                "Authentication failed, redirect to login if router is available."
            );
        } else {
            error.value = err.message || "Error fetching data";
        }
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
    // const lowerCaseQuery = searchQuery.value.toLowerCase();
    // return salaries.value.filter(salary => {
    //     const employeeName = salary.employee?.Full_Name?.toLowerCase() || '';
    //     const employeeId = (salary.employee?.Employee_Id ? String(salary.employee.Employee_Id) : '').toLowerCase();
    //     const bankAccountNo = (salary.bank_account_no ? String(salary.bank_account_no) : '').toLowerCase();

    //     return employeeName.includes(lowerCaseQuery) ||
    //         employeeId.includes(lowerCaseQuery) ||
    //         bankAccountNo.includes(lowerCaseQuery);
    // });
});

onMounted(() => getData());
</script>

<template>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-start">
                <div class="form-group mx-2">
                    <label>Salary Year</label>
                    <select
                        class="form-control"
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
                <div class="form-group mx-2">
                    <label>Salary Month</label>
                    <select
                        class="form-control"
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
                <div>
                    <button
                        class="btn btn-warning mt-4 mx-2"
                        @click="generateSalary"
                    >
                        Generate Sheet
                    </button>
                </div>
                <div>
                    <button
                        class="btn btn-info mt-4 mx-2"
                        @click="downloadSalary"
                    >
                        Download Sheet
                    </button>
                </div>
                <div>
                    <input
                        type="text"
                        class="form-control mt-4 mx-2"
                        placeholder="Search by Employee Name, ID, or Bank A/C"
                        v-model="searchQuery"
                    />
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Employee Id</th>
                            <th>Employee Name</th>
                            <th>Gross</th>
                            <th>Total Pay</th>
                            <th>Total Earning</th>
                            <th>Total Deduction</th>
                            <th>Net pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            class="ver-align"
                            v-for="(salary, index) in filteredSalaries"
                        >
                            <td>{{ index + 1 }}</td>
                            <td>ABNB-{{ salary.Employee_Id }}</td>
                            <td>{{ salary.Full_Name }}</td>
                            <td>{{ salary.gross_salary }}</td>
                            <td>{{ salary.total_payment }}</td>
                            <td>{{ salary.earning_amount }}</td>
                            <td>{{ salary.deduction_amount }}</td>
                            <td>{{ salary.net_pay }}</td>
                        </tr>
                        <tr v-if="filteredSalaries.length === 0">
                            <td colspan="7" class="text-center">
                                No matching salaries found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
