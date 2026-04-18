<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useStore } from "vuex";
import api from "@/api";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

const store = useStore();
const role_id = store.getters.getRoleId;
const user = store.getters.getUser;

const years = ref(["2022", "2023", "2024", "2025", "2026"]);
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

const selectedYear = ref(new Date().getFullYear().toString());
const selectedMonth = ref(months.value[new Date().getMonth()]);

const searchDept = ref(""); // Department filter
const searchEmp = ref(""); // Employee filter
const department = ref([]);
const employee = ref([]);
const selectedEmployee = ref(null);
const payslip = ref([]);
const loading = ref(false);
const error = ref(null);
const check = ref([]);

const monthNumber = computed(
    () => months.value.indexOf(selectedMonth.value) + 1,
);

const printFormate = async () => {
    const idToFetch = searchEmp.value || user.id;

    try {
        const res = await api.get(`/payslip-print-view/${idToFetch}`, {
            params: {
                year: selectedYear.value,
                month: monthNumber.value,
            },

            headers: {
                Accept: "text/html",
            },
        });
        const printWindow = window.open("", "_blank", "width=1000,height=650");

        printWindow.document.write(res.data);

        printWindow.document.close();
        printWindow.focus();

        printWindow.print();
        check.value = res.data;
    } catch (err) {
        console.error("Failed to fetch payslip:", err);
        alert("Failed to load payslip for printing");
    }
};

// ---------------- Fetch Departments ----------------
const getDepartments = async () => {
    try {
        const res = await api.get(`/department-list/${user.id}`);
        department.value = res.data;
    } catch (err) {
        console.error(err);
    }
};

// ---------------- Fetch Employees by Department ----------------
const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        selectedEmployee.value = null;
        searchEmp.value = "";
        return;
    }
    try {
        const res = await api.get(`/emp/${deptID}`);
        employee.value = res.data;
        selectedEmployee.value = null;
        searchEmp.value = "";
    } catch (err) {
        console.error(err);
    }
};

// ---------------- Employee Select ----------------
const onEmployeeSelect = (value) => {
    selectedEmployee.value = value;
    searchEmp.value = value ? value.id : "";
    getData(); // fetch payslip for selected employee
};

// ---------------- Fetch Payslip ----------------
const getData = async (employeeId = null) => {
    loading.value = true;
    error.value = null;

    const idToFetch = employeeId || searchEmp.value || user.id;

    try {
        const res = await api.get(`/payslip/${idToFetch}`, {
            params: {
                year: selectedYear.value,
                month: monthNumber.value,
            },
        });
        // <-- assign array directly, don't wrap in []
        payslip.value = res.data || [];
    } catch (err) {
        console.error(err);
        error.value = "Failed to load payslip";
        payslip.value = [];
    } finally {
        loading.value = false;
    }
};

// ---------------- Watch Filters ----------------
watch([selectedYear, selectedMonth], () => getData());

// ---------------- Initial Load ----------------
onMounted(async () => {
    await getDepartments();
    getData(user.id); // load logged-in user's payslip on page load
});
</script>

<template>
    <div class="container mt-2 p-3">
        <div class="row mb-3">
            <!-- Department Filter -->
            <div class="col-lg-3 position-relative" v-if="role_id == 1">
                <label class="form-label label-class">Select Department</label>
                <select
                    v-model="searchDept"
                    @change="getEmployee(searchDept)"
                    class="form-control field-height field-hover"
                >
                    <option value="">All Department</option>
                    <option
                        v-for="dept in department"
                        :key="dept.department_id"
                        :value="dept.department_id"
                    >
                        {{ dept.department_name }}
                    </option>
                </select>
                <button
                    v-if="searchDept"
                    type="button"
                    class="btn btn-sm btn-link position-absolute top-0 end-0 mt-5 me-3"
                    @click="
                        searchDept = '';
                        employee = [];
                        selectedEmployee = null;
                    "
                >
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <!-- Employee Filter -->
            <div class="col-lg-3 position-relative me-3" v-if="role_id == 1">
                <label class="form-label label-class">Select Employee</label>
                <Multiselect
                    v-model="selectedEmployee"
                    :options="employee"
                    label="Full_Name"
                    track-by="id"
                    placeholder="Select or search employee"
                    @update:modelValue="onEmployeeSelect"
                />
                <button
                    v-if="selectedEmployee"
                    type="button"
                    class="btn btn-sm btn-link position-absolute top-0 end-0 mt-5 me-5"
                    @click="
                        selectedEmployee = null;
                        searchEmp = '';
                    "
                >
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <!-- Year & Month -->
            <div class="col-lg-4 d-flex align-items-end gap-2">
                <div class="col-lg-6">
                    <label class="form-label label-class"> Select Year </label>
                    <select
                        v-model="selectedYear"
                        class="form-control field-height field-hover"
                    >
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div class="col-lg-6 label-class">
                    <label class="form-label"> Select Month </label>
                    <select
                        v-model="selectedMonth"
                        class="form-control field-height field-hover"
                    >
                        <option
                            v-for="month in months"
                            :key="month"
                            :value="month"
                        >
                            {{ month }}
                        </option>
                    </select>
                </div>
                <div class="col-lg-1 ms-auto">
                    <button class="print-button" @click="printFormate()">
                        PRINT
                    </button>
                </div>
            </div>
        </div>

        <!-- Payslip Cards -->
        <div v-if="payslip.length">
            <div v-for="p in payslip" :key="p.id" class="card mb-4 p-3 payslip">
                <!-- Header -->
                <div class="payslip-header text-center">
                    <h3>Salary Payslip</h3>
                </div>

                <!-- Employee Info -->
                <div class="row mb-3 info">
                    <div class="col-md-6">
                        <div class="label-row">
                            <span class="label-basic">Employee ID</span>:
                            <span class="value ms-1">{{ p.Employee_Id }}</span>
                        </div>
                        <div class="label-row">
                            <span class="label-basic">Full Name</span>:
                            <span class="value ms-1">{{ p.Full_Name }}</span>
                        </div>
                        <div class="label-row">
                            <span class="label-basic">Department</span>:
                            <span class="value ms-1">{{
                                p.departmentName
                            }}</span>
                        </div>
                        <div class="label-row">
                            <span class="label-basic">Designation</span>:
                            <span class="value ms-1">{{
                                p.designationName
                            }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="label-row">
                            <span class="label-info">Salary Date</span>:
                            <span class="value ms-1">{{ p.salary_date }}</span>
                        </div>
                        <div class="label-row">
                            <span class="label-info">Payment Method</span>:
                            <span class="value ms-1">{{
                                p.payment_method
                            }}</span>
                        </div>
                        <div class="label-row">
                            <span class="label-info">Bank Account</span>:
                            <span class="value ms-1">{{
                                p.bank_account_no
                            }}</span>
                        </div>
                        <div class="label-row">
                            <span class="label-info">Status</span>:
                            <span class="value ms-1">{{
                                p.salary_status
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Earnings & Deductions -->
                <div class="row">
                    <!-- Earnings -->
                    <div class="col-md-6">
                        <div class="card section">
                            <h4 class="section-title">Earnings</h4>
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td>Basic Salary</td>
                                        <td class="text-end">
                                            {{ p.basic_salary }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>House Rent</td>
                                        <td class="text-end">
                                            {{ p.house_rent }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Conveyance</td>
                                        <td class="text-end">
                                            {{ p.conveyance }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Medical Allowance</td>
                                        <td class="text-end">
                                            {{ p.medical_allowance }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Travel Allowance</td>
                                        <td class="text-end">
                                            {{ p.travel_allowance }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Daily Allowance</td>
                                        <td class="text-end">
                                            {{ p.daily_allowance }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mobile Bill</td>
                                        <td class="text-end">
                                            {{ p.mobile_bill }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Other Allowance</td>
                                        <td class="text-end">
                                            {{ p.other_allowance }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Earning Adjustment</td>
                                        <td class="text-end">
                                            {{ p.earning_adjustment }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Bonus Amount</td>
                                        <td class="text-end">
                                            {{ p.bonus_amounts }}
                                        </td>
                                    </tr>
                                    <tr class="fw-bold table-light">
                                        <td>Total Earnings</td>
                                        <td class="text-end">
                                            {{
                                                Number(p.total_payment) +
                                                Number(p.bonus_amounts)
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div class="col-md-6">
                        <div class="card section">
                            <h4 class="section-title">Deductions</h4>
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td>Provident Fund</td>
                                        <td class="text-end">
                                            {{ p.provident_fund }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Income Tax</td>
                                        <td class="text-end">
                                            {{ p.income_tax }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Revenue Stamp</td>
                                        <td class="text-end">
                                            {{ p.revenue_stamp }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Loan Repayment</td>
                                        <td class="text-end">
                                            {{ p.monthly_loan_repayments }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Deduction Adjustment</td>
                                        <td class="text-end">
                                            {{ p.deduction_adjustment }}
                                        </td>
                                    </tr>
                                    <tr class="fw-bold table-light">
                                        <td>Total Deductions</td>
                                        <td class="text-end">
                                            {{ p.total_deduction }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="net-pay text-center card mt-3">
                            <h3>
                                Net Pay:
                                <span class="amount">{{
                                    Number(p.net_pay) + Number(p.bonus_amounts)
                                }}</span>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center mt-5">
            <p v-if="loading">Loading payslip...</p>
            <p v-else-if="error">{{ error }}</p>
            <p v-else>No payslip found for this month.</p>
        </div>
    </div>
</template>

<style scoped>
.label-row {
    display: flex;
    margin-bottom: 4px;
}
.label-basic {
    margin-left: 10px;
    font-weight: 600;
    width: 90px;
}

.field-height {
    height: 38px;
}

.print-button {
    height: 38px;
    background-color: rgb(3, 102, 28);
    letter-spacing: 2px;
    color: white;
    border-radius: 5px;
    font-weight: 400;
}

.field-hover:hover {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.label-info {
    margin-left: 170px;
    font-weight: 600;
    width: 110px;
}
.payslip {
    max-width: 1000px;
    font-family: "Segoe UI", sans-serif;
    color: #333;
}
.no-arrow {
    appearance: none;
    background: transparent;
    padding-right: 0.75rem;
}
.payslip-header {
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
}
.section {
    padding: 12px;
}
.section-title {
    font-weight: 600;
    margin-bottom: 10px;
}
.table td {
    padding: 6px 8px;
}
.net-pay {
    border-top: 2px solid #000;
    padding-top: 10px;
}
.net-pay .amount {
    color: #198754;
    font-weight: bold;
    font-size: 21px;
}
.field-hover:hover {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.table tbody tr:hover > td {
    background-color: #717c73c7 !important;
}

.table thead th {
    background-color: #4e4e4e;
    font-weight: 700;
    color: #fff;
}

.table thead {
    height: 30px;
}

.field-height {
    height: 35px;
}
.placeholder-text {
    color: #999; /* faded text for placeholder */
    font-size: 14px;
}
.multiselect-design .multiselect__placeholder {
    color: #999;
    font-size: 14px;
    margin-bottom: 0px;
    align-items: center;
}

.multiselect-design .multiselect__tags {
    min-height: 35px !important; /* override default min-height */
    height: 35px; /* exact height */
    padding: 0 8px; /* adjust left/right padding */
    border-radius: 5px;
    border: 0.8px solid #b3a9a963;
    background: #c4c2c250;
    display: flex; /* align input vertically */
    align-items: center;
    font-size: 14px;
}

/* Input inside the multiselect */
.multiselect-design .multiselect__input,
.multiselect-design .multiselect__single {
    height: 100%; /* fill the tags container height */
    line-height: 35px; /* match container for vertical centering */
    padding: 0; /* remove extra padding */
    margin: 0;
    background: transparent; /* match container bg */
    border: 0; /* remove default border */
    flex: 1; /* grow to fill remaining space */
}

.multiselect-design .multiselect__select {
    height: 35px;
}

.field-background-colour {
    background: #c4c2c250;
    border: 0.8px solid #b3a9a963;
}
.header-design {
    letter-spacing: 1px;
    padding-top: 5px;
    /* background-color: #d6d6d6f5;

    border-radius: 5px; */
}
@media (max-width: 768px) {
    .label-info {
        margin-left: 10px;
        width: 90px;
    }
    .label-row {
        flex-wrap: wrap;
    }
}
</style>
