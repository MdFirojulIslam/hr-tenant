<script setup>
import { ref, onMounted, computed, defineAsyncComponent } from "vue";
import api from "@/api";
import { useStore } from "vuex";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

const store = useStore();
const role_id = store.getters.getRoleId;
const user = store.getters.getUser;

// ---------------- States ----------------
const expandedRow = ref(null);
const form = ref({ department: "", eid: "" });
const department = ref([]);
const employees = ref([]);
const tableInfo = ref([]);
const paginationObject = ref({});
const error = ref("");
const isLoading = ref(true);
const installments = ref({});
const selectedEmployee = ref(null);
const searchDept = ref("");
const selectedStatus = ref("active");

// ---------------- Loan Setup Modal ----------------
const heading = ref("");
const loanSetupModel = ref(false);
const selectedStore = ref(null);

// ---------------- Loan Repayment Modal ----------------
const repaymentHeading = ref("");
const repaymentModal = ref(false);
const selectedRepayment = ref(null);

// ---------------- Single Installment Modal ----------------
const singleLoanModal = ref(false);
const singleLoanData = ref(null);
const singleLoanTitle = ref("");
const editingInstallment = ref(null);

// ---------------- Loan Report ----------------
const loanReportModal = ref(false);
const downloadLoanReport = () => {
    loanReportModal.value = true;
};
const closeLoanReportModal = () => {
    loanReportModal.value = false;
};

// ---------------- Async Components ----------------
const loanSetupComponent = defineAsyncComponent(
    () => import("./component/loanSetupForm.vue"),
);
const repaymentComponent = defineAsyncComponent(
    () => import("./component/loanRepayment.vue"),
);
const singleLoanComponent = defineAsyncComponent(
    () => import("./component/installmentForm.vue"),
);
const loanAdjustmentReport = defineAsyncComponent(
    () => import("@/pages/payroll/component/loanAdjustmentReport.vue"),
);

// ---------------- Fetch Table Data ----------------
const getData = async () => {
    try {
        const [deptRes, loanRes] = await Promise.all([
            api.get(`/department-list/${user.id}`),
            api.get("/get-employee-loan"),
        ]);
        department.value = deptRes.data;
        tableInfo.value = loanRes.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

// ---------------- Fetch Employees ----------------
const getEmployee = async (deptID) => {
    if (!deptID) {
        employees.value = [];
        return;
    }
    try {
        const res = await api.get(`/emp/${deptID}`);
        employees.value = res.data;
    } catch (err) {
        console.error("Error loading employees:", err);
    }
};

// ---------------- Computed Filter ----------------
const filteredData = computed(() => {
    return tableInfo.value.filter((item) => {
        const matchDept = searchDept.value
            ? item.deptID == searchDept.value
            : true;
        const matchEmp = selectedEmployee.value
            ? item.eid == selectedEmployee.value.id
            : true;
        const matchStatus = selectedStatus.value
            ? item.status.toLowerCase() === selectedStatus.value
            : true;
        return matchDept && matchEmp && matchStatus;
    });
});

// ---------------- Expandable Row ----------------
const toggleRow = async (loanId) => {
    loanId = Number(loanId);
    if (expandedRow.value === loanId) {
        expandedRow.value = null;
    } else {
        expandedRow.value = loanId;
        if (!installments.value[loanId]) {
            await getInstallments(loanId);
        }
    }
};

const getInstallments = async (loanId) => {
    try {
        const res = await api.get(`/loan-installments/${loanId}`);
        installments.value[loanId] = res.data;
    } catch (err) {
        console.error("Error fetching installments:", err);
    }
};

// ---------------- Loan Setup ----------------
const loanSetupOpen = (title) => {
    heading.value = title;
    selectedStore.value = null;
    loanSetupModel.value = true;
};
const loanAdjustment = (item) => {
    selectedStore.value = item;
    heading.value = "Update";
    loanSetupModel.value = true;
};
const loanSetupClose = () => {
    loanSetupModel.value = false;
    getData();
};

// ---------------- Loan Repayment ----------------
const repaymentOpen = (title, loanData = null) => {
    repaymentHeading.value = title;
    selectedRepayment.value = loanData;
    repaymentModal.value = true;
};
const repaymentClose = () => {
    repaymentModal.value = false;
    getData();
};

// ---------------- Single Loan Installment ----------------
const singleLoanAdjustment = async (installmentId, loanId) => {
    try {
        const res = await api.get(
            `/loan-installment-details/${installmentId}/${loanId}`,
        );
        singleLoanData.value = res.data;
        singleLoanTitle.value = "Update Repayment";
        singleLoanModal.value = true;
    } catch (err) {
        console.error("Error loading installment data:", err);
    }
};
const singleLoanClose = () => {
    singleLoanModal.value = false;
};

// ---------------- Update Single Installment Inline ----------------
const updateInstallmentInline = async (ins) => {
    try {
        const payload = {
            repayment_id: ins.id,
            installment_amount: Number(ins.installment_amounts) || 0,
            installment_date: ins.paid_on_dates,
        };
        const res = await api.put(`/loan-repayment/${ins.id}`, payload);
        if (res.data.success) {
            updateInstallment({
                loan_id: ins.loan_id,
                repayment_id: ins.id,
                installment_amounts: Number(ins.installment_amounts) || 0,
                paid_on_dates: ins.paid_on_dates,
            });
            editingInstallment.value = null;
        }
    } catch (err) {
        console.error("Error updating installment:", err);
    }
};

// ---------------- Update Installment in Local State ----------------
const updateInstallment = (updatedData) => {
    const loanId = updatedData.loan_id;
    if (!loanId || !installments.value[loanId]) return;
    installments.value[loanId] = installments.value[loanId].map((ins) => {
        if (ins.id === updatedData.repayment_id) {
            return {
                ...ins,
                installment_amounts:
                    Number(updatedData.installment_amounts) || 0,
                paid_on_dates: updatedData.paid_on_dates,
                paid_status: updatedData.paid_status || ins.paid_status,
            };
        }
        return ins;
    });
    const loan = tableInfo.value.find((l) => l.loan_id === loanId);
    if (loan) {
        loan.total_paid = installments.value[loanId]
            .filter((i) => i.paid_status.toLowerCase() === "paid")
            .reduce((sum, i) => sum + Number(i.installment_amounts || 0), 0);
    }
};

// ---------------- Delete Loan / Installment ----------------
const deleteLoan = async (loanId) => {
    if (!confirm("Are you sure you want to delete this loan?")) return;
    try {
        const response = await api.get(`/delete-loan/${loanId}`);
        if (response.data.success) {
            alert("Loan deleted successfully.");
            getData();
        } else {
            alert("Failed to delete loan.");
        }
    } catch (err) {
        console.error(err);
        alert("An error occurred while deleting the loan.");
    }
};

const deleteSingleLoan = async (repaymentId, loanId) => {
    if (!confirm("Are you sure you want to delete this Installment?")) return;
    try {
        const response = await api.get(`/delete-single-loan/${repaymentId}`);
        if (response.data.success) {
            if (installments.value[loanId]) {
                installments.value[loanId] = installments.value[loanId].filter(
                    (ins) => ins.id !== repaymentId,
                );
            }
            const loan = tableInfo.value.find((l) => l.loan_id === loanId);
            if (loan) {
                loan.total_paid = installments.value[loanId]
                    .filter((i) => i.paid_status.toLowerCase() === "paid")
                    .reduce(
                        (sum, i) => sum + Number(i.installment_amounts || 0),
                        0,
                    );
            }
            alert("Installment deleted successfully.");
        } else {
            alert("Failed to delete installment.");
        }
    } catch (err) {
        console.error(err);
        alert("An error occurred while deleting the installment.");
    }
};

// ---------------- Employee Select ----------------
const onEmployeeSelect = (emp) => {
    if (!emp) return;
    form.eid = emp.id;
    getEmployee(emp.id);
};

// ---------------- Lifecycle ----------------
onMounted(() => getData());
</script>

<template>
    <div
        class="border rounded container-fluid h-100 d-flex flex-column p-0 m-0"
    >
        <!-- Header -->
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3 flex-wrap"
        >
            <h2 class="m-0 mb-2 mb-md-0">Employee Loan List</h2>
            <div class="d-flex flex-row gap-2 flex-wrap">
                <button
                    @click="downloadLoanReport()"
                    type="button"
                    class="btn btn-primary d-flex align-items-center fw-medium shadow-sm mb-2"
                >
                    <i class="fa-solid fa-download me-2"></i> Download Report
                </button>
                <button
                    @click="loanSetupOpen('Save')"
                    type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm mb-2"
                >
                    <i class="fa-solid fa-plus me-2"></i> New Loan Setup
                </button>
                <button
                    @click="repaymentOpen('Loan Repayment')"
                    type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm mb-2"
                >
                    <i class="fa-solid fa-plus me-2"></i> Loan Repayment Setup
                </button>
            </div>
        </div>

        <!-- Modals -->
        <component
            v-if="loanReportModal"
            :is="loanAdjustmentReport"
            :isOpen="loanReportModal"
            @modal-close="closeLoanReportModal"
        />
        <component
            v-if="loanSetupModel"
            :is="loanSetupComponent"
            :isOpen="loanSetupModel"
            :updateinfo="heading"
            :editStore="selectedStore"
            :roleId="role_id"
            @modal-close="loanSetupClose"
            @refresh-list="getData"
        />
        <component
            v-if="repaymentModal"
            :is="repaymentComponent"
            :isOpen="repaymentModal"
            :updateinfo="repaymentHeading"
            :editStore="selectedRepayment"
            :roleId="role_id"
            @modal-close="repaymentClose"
            @refresh-list="getData"
        />

        <!-- Filters -->
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <div class="row g-3 mb-3 flex-wrap">
                    <!-- Department -->
                    <div class="col-lg-3 position-relative">
                        <select
                            class="form-control field-height field-background-colour"
                            v-model="searchDept"
                            @change="getEmployee(searchDept)"
                        >
                            <option value="" disabled>Select Department</option>
                            <option
                                v-for="dept in department"
                                :key="dept.department_id"
                                :value="dept.department_id"
                            >
                                {{ dept.department_name }}
                            </option>
                        </select>
                    </div>
                    <!-- Employee -->
                    <div class="col-lg-3 position-relative">
                        <Multiselect
                            v-model="selectedEmployee"
                            :options="employees"
                            label="Full_Name"
                            track-by="id"
                            placeholder="Select or search employee"
                            :searchable="true"
                            :close-on-select="true"
                            :allow-empty="true"
                            @update:modelValue="onEmployeeSelect"
                            class="multiselect-design"
                        />
                    </div>
                    <!-- Status -->
                    <div class="col-lg-1 position-relative">
                        <select
                            class="form-control field-height field-background-colour"
                            v-model="selectedStatus"
                        >
                            <option value="" disabled>Select Status</option>
                            <option value="active">ACTIVE</option>
                            <option value="closed">CLOSE</option>
                        </select>
                    </div>
                </div>

                <!-- Loan Table -->
                <div class="mt-3">
                    <div
                        class="d-flex align-items-center px-2 py-2 fw-semibold border-bottom bg-light"
                    >
                        <span class="me-2">Total Employees:</span>
                        <span
                            class="bg-white border rounded px-3 py-1 fw-medium"
                            >{{ filteredData.length }}</span
                        >
                    </div>

                    <div class="table-responsive shadow-sm bg-light">
                        <table
                            class="table table-bordered table-hover table-sm"
                        >
                            <thead class="table-light">
                                <tr class="text-nowrap">
                                    <th width="2%"></th>
                                    <th class="text-center" width="3%">S/N</th>
                                    <th width="13%">EMPLOYEE NAME</th>
                                    <th width="17%">DEPARTMENT</th>
                                    <th width="17%">DESIGNATION</th>
                                    <th class="text-center" width="10%">
                                        PRINCIPAL AMOUNT
                                    </th>
                                    <th class="text-center" width="4%">
                                        TENURE
                                    </th>
                                    <th class="text-center" width="10%">
                                        MONTHLY INSTALLMENT
                                    </th>
                                    <th class="text-center" width="10%">
                                        TOTAL PAID
                                    </th>
                                    <th class="text-center" width="5%">
                                        STATUS
                                    </th>
                                    <th width="10%">REMARKS</th>
                                    <th class="text-center" width="3%">
                                        ACTION
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template
                                    v-for="(item, index) in filteredData"
                                    :key="item.loan_id"
                                >
                                    <tr class="text-nowrap">
                                        <td>
                                            <button
                                                class="btn btn-sm btn-light border me-1"
                                                @click="toggleRow(item.loan_id)"
                                            >
                                                <i
                                                    class="fa-solid"
                                                    :class="
                                                        expandedRow ===
                                                        Number(item.loan_id)
                                                            ? 'fa-minus text-primary'
                                                            : 'fa-plus text-primary'
                                                    "
                                                ></i>
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            {{ index + 1 }}
                                        </td>
                                        <td>{{ item.employee_name }}</td>
                                        <td>{{ item.deptName }}</td>
                                        <td>{{ item.designation_name }}</td>
                                        <td class="text-center">
                                            {{ item.principal_amounts }}
                                        </td>
                                        <td class="text-center">
                                            {{ item.tenure_months }}
                                        </td>
                                        <td class="text-center">
                                            {{ item.monthly_installments }}
                                        </td>
                                        <td class="text-center">
                                            {{ item.total_paid }}
                                        </td>
                                        <td class="text-center text-uppercase">
                                            {{ item.status }}
                                        </td>
                                        <td>{{ item.remarks }}</td>
                                        <td class="text-center text-nowrap">
                                            <button
                                                class="btn btn-sm btn-light border me-1"
                                                title="Edit"
                                                @click="loanAdjustment(item)"
                                            >
                                                <i
                                                    class="fa-regular fa-pen-to-square text-warning"
                                                ></i>
                                            </button>
                                            <button
                                                class="btn btn-sm btn-light border"
                                                title="Delete"
                                                @click="
                                                    deleteLoan(item.loan_id)
                                                "
                                            >
                                                <i
                                                    class="fa-solid fa-trash text-danger"
                                                ></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Expandable Installments -->
                                    <tr
                                        v-if="
                                            expandedRow === Number(item.loan_id)
                                        "
                                    >
                                        <td colspan="12" class="bg-light">
                                            <div
                                                class="ms-5 p-1"
                                                style="width: 50%"
                                            >
                                                <table
                                                    class="table table-bordered table-sm mb-0"
                                                >
                                                    <thead>
                                                        <tr>
                                                            <th
                                                                class="text-center"
                                                            >
                                                                INSTALLMENT NO
                                                            </th>
                                                            <th
                                                                class="text-center"
                                                            >
                                                                PAID AMOUNT
                                                            </th>
                                                            <th
                                                                class="text-center"
                                                            >
                                                                STATUS
                                                            </th>
                                                            <th
                                                                class="text-center"
                                                            >
                                                                PAID DATE
                                                            </th>
                                                            <th
                                                                class="text-center"
                                                            >
                                                                ACTION
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(
                                                                ins, i
                                                            ) in installments[
                                                                item.loan_id
                                                            ] || []"
                                                            :key="i"
                                                        >
                                                            <td
                                                                class="text-center"
                                                            >
                                                                {{ i + 1 }}
                                                            </td>
                                                            <td
                                                                class="text-center"
                                                            >
                                                                <input
                                                                    v-if="
                                                                        editingInstallment ===
                                                                        ins.id
                                                                    "
                                                                    type="number"
                                                                    v-model="
                                                                        ins.installment_amounts
                                                                    "
                                                                    class="form-control form-control-sm"
                                                                    @keyup.enter="
                                                                        updateInstallmentInline(
                                                                            ins,
                                                                        )
                                                                    "
                                                                />
                                                                <span
                                                                    v-else
                                                                    @dblclick="
                                                                        editingInstallment =
                                                                            ins.id
                                                                    "
                                                                    >{{
                                                                        ins.installment_amounts
                                                                    }}</span
                                                                >
                                                            </td>
                                                            <td
                                                                class="text-center"
                                                            >
                                                                {{
                                                                    ins.paid_status
                                                                }}
                                                            </td>
                                                            <td
                                                                class="text-center"
                                                            >
                                                                <input
                                                                    v-if="
                                                                        editingInstallment ===
                                                                        ins.id
                                                                    "
                                                                    type="date"
                                                                    v-model="
                                                                        ins.paid_on_dates
                                                                    "
                                                                    class="form-control form-control-sm"
                                                                    @keyup.enter="
                                                                        updateInstallmentInline(
                                                                            ins,
                                                                        )
                                                                    "
                                                                />
                                                                <span
                                                                    v-else
                                                                    @dblclick="
                                                                        editingInstallment =
                                                                            ins.id
                                                                    "
                                                                    >{{
                                                                        ins.paid_on_dates
                                                                    }}</span
                                                                >
                                                            </td>
                                                            <td
                                                                class="text-center text-nowrap"
                                                            >
                                                                <button
                                                                    class="btn btn-sm btn-light border"
                                                                    title="Delete"
                                                                    @click="
                                                                        deleteSingleLoan(
                                                                            ins.id,
                                                                            item.loan_id,
                                                                        )
                                                                    "
                                                                >
                                                                    <i
                                                                        class="fa-solid fa-trash text-danger"
                                                                    ></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr
                                                            v-if="
                                                                !installments[
                                                                    item.loan_id
                                                                ]?.length
                                                            "
                                                        >
                                                            <td
                                                                colspan="5"
                                                                class="text-center text-muted"
                                                            >
                                                                No installment
                                                                data found.
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.border-color {
    border: 1.8px solid #059223d5;
}
.text-uppercase {
    text-transform: uppercase;
}
.table thead th {
    background-color: #4e4e4e;
    font-weight: 700;
    color: #fff;
}
.field-height {
    height: 35px;
}
.placeholder-text {
    color: #999;
    font-size: 14px;
}
.multiselect-design .multiselect__tags {
    min-height: 35px !important;
    height: 35px;
    padding: 0 8px;
    border-radius: 5px;
    border: 0.8px solid #b3a9a963;
    background: #c4c2c250;
    display: flex;
    align-items: center;
    font-size: 14px;
}
.multiselect-design .multiselect__input,
.multiselect-design .multiselect__single {
    height: 100%;
    line-height: 35px;
    padding: 0;
    margin: 0;
    background: transparent;
    border: 0;
    flex: 1;
}
.multiselect-design .multiselect__select {
    height: 35px;
}
.field-background-colour {
    background: #c4c2c250;
    border: 0.8px solid #b3a9a963;
}

/* Responsive */
@media (max-width: 768px) {
    .d-flex.flex-row.gap-2 {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .d-flex.flex-row.gap-2 button {
        width: 100%;
        display: block;
    }
    .row.g-3.mb-3 {
        flex-direction: column;
    }
    .row.g-3.mb-3 .col-lg-3,
    .row.g-3.mb-3 .col-lg-1 {
        flex: 1 1 100%;
        max-width: 100%;
        margin-bottom: 8px;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .ms-5.p-1 {
        width: 100% !important;
        margin-left: 0 !important;
    }
}
</style>
