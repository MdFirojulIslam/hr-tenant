<script setup>
import { ref, watch, computed } from "vue";
import { useStore } from "vuex";
import api from "@/api";
const store = useStore();
const user = store.getters.getUser;
// Year & Month
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
const selectedYear = ref(new Date().getFullYear().toString());
const selectedMonth = ref(months.value[new Date().getMonth()]);
// Payslip Data
const payslip = ref(null);
const loading = ref(false);
const error = ref(null);
// Convert month name to number
const monthNumber = computed(
    () => months.value.indexOf(selectedMonth.value) + 1,
);
// Fetch Payslip
const fetchPayslip = async () => {
    loading.value = true;
    error.value = null;
    try {
        const res = await api.get(`/payslip/${user.id}`, {
            params: {
                year: selectedYear.value,
                month: monthNumber.value, // Send numeric month
            },
        });
        if (res.data) {
            payslip.value = res.data;
        } else {
            payslip.value = null;
            error.value = "No payslip found for this month.";
        }
    } catch (err) {
        console.error("API Error:", err.response || err);
        error.value = "Failed to load payslip";
        payslip.value = null;
    } finally {
        loading.value = false;
    }
};
// Fetch initially
fetchPayslip();
// Watch for changes
watch([selectedYear, selectedMonth], fetchPayslip);
</script>
<template>
    <div class="container card payslip mt-2 p-3" v-if="payslip">
        <!-- Header -->
        <div class="payslip-header text-center">
            <h3>Salary Payslip</h3>
            <!-- Year & Month Inputs -->
            <div
                class="d-flex justify-content-center align-items-center gap-2 mt-2"
            >
                <p class="text-muted mb-0">Salary Month :</p>
                <select
                    v-model="selectedYear"
                    class="form-select w-auto no-arrow"
                >
                    <option v-for="year in years" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
                <select
                    v-model="selectedMonth"
                    class="form-select w-auto no-arrow"
                >
                    <option v-for="month in months" :key="month" :value="month">
                        {{ month }}
                    </option>
                </select>
            </div>
        </div>
        <!-- Employee Info -->
        <div class="row mb-3 info">
            <div class="col-md-6">
                <div class="label-row">
                    <span class="label-basic">Employee ID</span>:
                    <span class="value ms-1">{{ payslip.Employee_Id }}</span>
                </div>
                <div class="label-row">
                    <span class="label-basic">Full Name</span>:
                    <span class="value ms-1">{{ payslip.Full_Name }}</span>
                </div>
                <div class="label-row">
                    <span class="label-basic">Department</span>:
                    <span class="value ms-1">{{ payslip.departmentName }}</span>
                </div>
                <div class="label-row">
                    <span class="label-basic">Designation</span>:
                    <span class="value ms-1">{{
                        payslip.designationName
                    }}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="label-row">
                    <span class="label-info">Salary Date</span>:
                    <span class="value ms-1">{{ payslip.salary_date }}</span>
                </div>
                <div class="label-row">
                    <span class="label-info">Payment Method</span>:
                    <span class="value ms-1">{{ payslip.payment_method }}</span>
                </div>
                <div class="label-row">
                    <span class="label-info">Bank Account</span>:
                    <span class="value ms-1">{{
                        payslip.bank_account_no
                    }}</span>
                </div>
                <div class="label-row">
                    <span class="label-info">Status</span>:
                    <span class="value ms-1">{{ payslip.salary_status }}</span>
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
                                    {{ payslip.basic_salary }}
                                </td>
                            </tr>
                            <tr>
                                <td>House Rent</td>
                                <td class="text-end">
                                    {{ payslip.house_rent }}
                                </td>
                            </tr>
                            <tr>
                                <td>Conveyance</td>
                                <td class="text-end">
                                    {{ payslip.conveyance }}
                                </td>
                            </tr>
                            <tr>
                                <td>Medical Allowance</td>
                                <td class="text-end">
                                    {{ payslip.medical_allowance }}
                                </td>
                            </tr>
                            <tr>
                                <td>Travel Allowance</td>
                                <td class="text-end">
                                    {{ payslip.travel_allowance }}
                                </td>
                            </tr>
                            <tr>
                                <td>Daily Allowance</td>
                                <td class="text-end">
                                    {{ payslip.daily_allowance }}
                                </td>
                            </tr>
                            <tr>
                                <td>Mobile Bill</td>
                                <td class="text-end">
                                    {{ payslip.mobile_bill }}
                                </td>
                            </tr>
                            <tr>
                                <td>Other Allowance</td>
                                <td class="text-end">
                                    {{ payslip.other_allowance }}
                                </td>
                            </tr>
                            <tr>
                                <td>Earning Adjustment</td>
                                <td class="text-end">
                                    {{ payslip.earning_adjustment }}
                                </td>
                            </tr>
                            <tr class="fw-bold table-light">
                                <td>Total Earnings</td>
                                <td class="text-end">
                                    {{ payslip.total_payment }}
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
                                    {{ payslip.provident_fund }}
                                </td>
                            </tr>
                            <tr>
                                <td>Income Tax</td>
                                <td class="text-end">
                                    {{ payslip.income_tax }}
                                </td>
                            </tr>
                            <tr>
                                <td>Revenue Stamp</td>
                                <td class="text-end">
                                    {{ payslip.revenue_stamp }}
                                </td>
                            </tr>
                            <tr>
                                <td>Loan Repayment</td>
                                <td class="text-end">
                                    {{ payslip.monthly_loan_repayments }}
                                </td>
                            </tr>
                            <tr>
                                <td>Deduction Adjustment</td>
                                <td class="text-end">
                                    {{ payslip.deduction_adjustment }}
                                </td>
                            </tr>
                            <tr class="fw-bold table-light">
                                <td>Total Deductions</td>
                                <td class="text-end">
                                    {{ payslip.total_deduction }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="net-pay text-center card mt-3">
                    <h3>
                        Net Pay:
                        <span class="amount">{{ payslip.net_pay }}</span>
                    </h3>
                </div>
            </div>
        </div>
        <!-- Net Pay -->
        <!-- Footer -->
        <!-- <div class="footer text-center mt-4">
           <p>This is a system generated payslip</p>
           <p>Generated on: {{ new Date().toLocaleDateString() }}</p>
       </div> -->
    </div>
    <div v-else class="text-center mt-5">
        <p v-if="loading">Loading payslip...</p>
        <p v-else-if="error">{{ error }}</p>
        <p v-else>No payslip found for this month.</p>
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
/* Hide default arrow */
.no-arrow {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background: transparent;
    padding-right: 0.75rem;
}
.payslip-header {
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
}
/* Label:Value alignment */
/* .label-row {
   display: flex;
   margin-bottom: 4px;
   align-items: center;
}
.label-row .label {
   display: inline-block;
   width: 150px;
   font-weight: 600;
}
.label-row .value {
   flex: 1;
} */
.info p {
    margin-bottom: 4px;
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
.footer {
    font-size: 12px;
    color: #777;
}
@media print {
    .container {
        margin: 0;
        padding: 0;
    }
    .label-info {
        margin-left: none;
    }
    .card {
        border: none;
    }
}
@media (max-width: 768px) {
    .label-info {
        margin-left: 10px;
        font-weight: 600;
        width: 90px;
    }
    .label-row {
        flex-wrap: wrap;
        /* label and value stack on small screens */
    }
}
</style>
