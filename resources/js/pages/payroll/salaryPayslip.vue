<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useStore } from "vuex";
import api from "@/api";
import BaseDept from "@/pages/components/input_field/BaseDept.vue";
import BaseDeptEmp from "@/pages/components/input_field/BaseDeptEmp.vue";
import Label from "@/pages/components/label/Label.vue";

const store = useStore();
const role_id = store.getters.getRoleId;
const user = store.getters.getUser;

const payslip = ref([]);
const loading = ref(false);
const error = ref(null);
const check = ref([]);

const userID = user.id;

const department_id = ref(null);
const employee_id = ref(null);

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

const monthNumber = computed(
    () => months.value.indexOf(selectedMonth.value) + 1,
);

// ---------------- Safe number helpers ----------------
const toNumber = (value) => {
    const num = Number(value);
    return Number.isFinite(num) ? num : 0;
};

const formatAmount = (value) => toNumber(value).toFixed(2);

const earningsTotal = (p) =>
    toNumber(p.basic_salary) +
    toNumber(p.house_rent) +
    toNumber(p.conveyance) +
    toNumber(p.medical_allowance) +
    toNumber(p.travel_allowance) +
    toNumber(p.daily_allowance) +
    toNumber(p.mobile_bill) +
    toNumber(p.other_allowance) +
    toNumber(p.earning_adjustment) +
    toNumber(p.bonus_amounts);

const deductionsTotal = (p) =>
    toNumber(p.provident_fund) +
    toNumber(p.income_tax) +
    toNumber(p.revenue_stamp) +
    toNumber(p.monthly_loan_repayments) +
    toNumber(p.deduction_adjustment);

const netPayable = (p) => earningsTotal(p) - deductionsTotal(p);

const printFormate = async () => {
    const idToFetch = employee_id.value || user.id;

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

// ---------------- Fetch Payslip ----------------
const getData = async (employeeId = null) => {
    loading.value = true;
    error.value = null;

    const idToFetch = employeeId || employee_id.value || userID;

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

const selectedEmployee = computed(() => employee_id.value);

// ---------------- Watch Filters ----------------
watch([selectedYear, selectedMonth, selectedEmployee], () => getData());

// ---------------- Initial Load ----------------
onMounted(async () => {
    getData(user.id); // load logged-in user's payslip on page load
});
</script>

<template>
    <div class="container mt-2 p-3">
        <div class="row mb-3">
            <!-- Department Filter -->
            <div class="col-lg-4 position-relative" v-if="role_id == 1">
                <Label label="Department Name" class="dept-labels" />
                <BaseDept
                    v-model:departmentID="department_id"
                    :userID="userID"
                />
            </div>

            <!-- Employee Filter -->
            <div class="col-lg-3 position-relative me-3" v-if="role_id == 1">
                <Label label="Employee Name" class="employee-labels" />
                <BaseDeptEmp
                    v-model:employeeID="employee_id"
                    :deptID="department_id"
                    :isEdit="false"
                />
            </div>

            <!-- Year & Month -->
            <div class="col-lg-4 d-flex align-items-end gap-2">
                <div class="col-lg-4">
                    <Label label="Select Year" class="year-labels" />
                    <select
                        v-model="selectedYear"
                        class="form-control field-height field-hover"
                    >
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div class="col-lg-4 label-class">
                    <Label label="Select Month" class="month-labels" />
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
            </div>
        </div>

        <!-- Payslip Cards -->
        <div class="content" v-if="payslip.length">
            <div v-for="p in payslip" :key="p.id" class="card p-2 payslip">
                <!-- Header -->
                <div class="payslip-header text-center">
                    <h3>Salary Payslip</h3>
                </div>

                <!-- Employee Info -->
                <div class="row info mt-2 mb-2">
                    <div class="col-md-6">
                        <div class="label-row">
                            <span class="label-basic">Employee ID</span>:
                            <span class="value ms-1"
                                >ABNB{{ p.Employee_Id }}</span
                            >
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
                                            {{ formatAmount(p.basic_salary) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>House Rent</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.house_rent) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Conveyance</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.conveyance) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Medical Allowance</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.medical_allowance) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Travel Allowance</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.travel_allowance) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Daily Allowance</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.daily_allowance) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mobile Bill</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.mobile_bill) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Other Allowance</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.other_allowance) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Earning Adjustment</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.earning_adjustment) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Bonus Amount</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.bonus_amounts) }}
                                        </td>
                                    </tr>
                                    <tr class="fw-bold table-light">
                                        <td>Total Earnings</td>
                                        <td class="text-end">
                                            {{ formatAmount(earningsTotal(p)) }}
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
                                            {{ formatAmount(p.provident_fund) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Income Tax</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.income_tax) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Revenue Stamp</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.revenue_stamp) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Loan Repayment</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.monthly_loan_repayments) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Deduction Adjustment</td>
                                        <td class="text-end">
                                            {{ formatAmount(p.deduction_adjustment) }}
                                        </td>
                                    </tr>
                                    <tr class="fw-bold table-light">
                                        <td>Total Deductions</td>
                                        <td class="text-end">
                                            {{ formatAmount(deductionsTotal(p)) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="net-pay text-center card mt-3">
                            <h3>
                                Net Pay:
                                <span class="amount">{{
                                    formatAmount(netPayable(p))
                                }}</span>
                            </h3>
                        </div>
                        <div class="text-end print-button-align">
                            <button
                                class="print-button"
                                @click="printFormate()"
                            >
                                PRINT
                            </button>
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

/* .content {
   display: flex;
   align-items: center;
   justify-content: center;
} */

.print-button {
    height: 30px;
    background-color: rgb(3, 102, 28);
    letter-spacing: 2px;
    color: white;
    border-radius: 5px;
    font-weight: 400;
}
.print-button-align {
    margin-top: 50px;
    margin-right: 8px;
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
}
.section {
    padding: 5px;
    margin-left: 8px;
    margin-right: 8px;
}
.section-title {
    font-weight: 600;
}
.table td {
    padding: 6px 8px;
}
.net-pay {
    border-top: 2px solid #000;
    padding-top: 10px;
    margin-left: 8px;
    margin-right: 8px;
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

.table tbody td {
    font-size: 10px;
}

.table thead {
    height: 30px;
}

.field-height {
    height: 38px;
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
