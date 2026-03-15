<script setup>
import { ref, onMounted, computed, defineAsyncComponent } from "vue";
// import { Bootstrap4Pagination } from "laravel-vue-pagination";

import api from "@/api";
import { useStore } from "vuex";

import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

const onEmployeeSelect = (emp) => {
    if (!emp) return;
    form.eid = emp.id;
    searchEmp.value = emp.id; // if you use searchEmp for filtering
    getEmployeeImg(emp.id);
};

const selectedEmployee = ref(null);

const store = useStore();
const role_id = store.getters.getRoleId;

// ---------------- States ----------------
const expandedRow = ref(null);
const form = ref({ department: "", eid: "" });
const department = ref([]);
const employees = ref([]);
const tableInfo = ref([]);
const paginationObject = ref({});
const error = ref("");
const isLoading = ref(true);
const installments = ref({}); // key = loan_id, value = installment array
const searchDept = ref("");
const user = store.getters.getUser; // or from auth

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

const selectedStatus = ref("active"); // will hold "active" or "close"

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
const getData = async (page = 1) => {
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

// const updateInstallmentInline = async (ins) => {
//     try {
//         const payload = {
//             repayment_id: ins.id,
//             installment_amount: ins.installment_amounts,
//             installment_date: ins.paid_on_dates,
//         };

//         const res = await api.put(`/loan-repayment/${ins.id}`, payload);

//         if (res.data.success) {
//             updateInstallment({
//                 repayment_id: ins.id,
//                 loan_id: ins.loan_id, // ✅ add this
//                 installment_amounts: ins.installment_amounts,
//                 paid_on_dates: ins.paid_on_dates,
//             });
//             editingInstallment.value = null;
//         }
//     } catch (err) {
//         console.error(err);
//     }
// };

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
    loanId = Number(loanId); // <--- FIXED

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
    // getData();
};

// ---------------- Update Single Installment Inline ----------------
const updateInstallmentInline = async (ins) => {
    try {
        // Prepare payload for API
        const payload = {
            repayment_id: ins.id,
            installment_amount: Number(ins.installment_amounts) || 0,
            installment_date: ins.paid_on_dates,
        };

        const res = await api.put(`/loan-repayment/${ins.id}`, payload);

        if (res.data.success) {
            // Update the local installment array
            updateInstallment({
                loan_id: ins.loan_id, // make sure loan_id is passed
                repayment_id: ins.id,
                installment_amounts: Number(ins.installment_amounts) || 0,
                paid_on_dates: ins.paid_on_dates,
            });

            // Clear editing state
            editingInstallment.value = null;
        }
    } catch (err) {
        console.error("Error updating installment:", err);
    }
};

// ---------------- Update Installment in Local State ----------------
const updateInstallment = (updatedData) => {
    const loanId = updatedData.loan_id; // get loan_id from updatedData

    if (!loanId || !installments.value[loanId]) return;

    // Update the specific installment
    installments.value[loanId] = installments.value[loanId].map((ins) => {
        if (ins.id === updatedData.repayment_id) {
            return {
                ...ins,
                installment_amounts:
                    Number(updatedData.installment_amounts) || 0,
                paid_on_dates: updatedData.paid_on_dates,
                paid_status: updatedData.paid_status || ins.paid_status, // keep status if not provided
            };
        }
        return ins;
    });

    // Recalculate total_paid for this loan based only on PAID installments
    const loan = tableInfo.value.find((l) => l.loan_id === loanId);
    if (loan) {
        loan.total_paid = installments.value[loanId]
            .filter((i) => i.paid_status.toLowerCase() === "paid")
            .reduce((sum, i) => sum + Number(i.installment_amounts || 0), 0);
    }
};

const deleteLoan = async (loanId) => {
    if (!confirm("Are you sure you want to delete this loan?")) {
        return;
    }
    try {
        const response = await api.get(`/delete-loan/${loanId}`);
        if (response.data.success) {
            alert("Loan deleted successfully.");
            getData();
        } else {
            alert("Failed to delete loan.");
        }
    } catch (err) {
        console.error("Error deleting loan:", err);
        alert("An error occurred while deleting the loan.");
    }
};

const deleteSingleLoan = async (repaymentId, loanId) => {
    if (!confirm("Are you sure you want to delete this Installment?")) return;

    try {
        const response = await api.get(`/delete-single-loan/${repaymentId}`);
        if (response.data.success) {
            // Remove from local reactive state
            if (installments.value[loanId]) {
                installments.value[loanId] = installments.value[loanId].filter(
                    (ins) => ins.id !== repaymentId,
                );
            }

            // Also update total_paid for this loan
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
        console.error("Error deleting installment:", err);
        alert("An error occurred while deleting the installment.");
    }
};

// ---------------- Lifecycle ----------------
onMounted(() => getData());
</script>

<template>
    <div
        class="border rounded container-fluid h-100 d-flex flex-column p-0 m-0"
    >
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="m-0">Employee Loan List</h2>
            <div class="d-flex flex-row gap-2">
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
                    <i class="fa-solid fa-plus me-2"></i>New Loan Setup
                </button>
                <button
                    @click="repaymentOpen('Loan Repayment')"
                    type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm mb-2"
                >
                    <i class="fa-solid fa-plus me-2"></i>Loan Repayment Setup
                </button>
            </div>
        </div>

        <!-- Parent -->
        <component
            v-if="loanReportModal"
            :is="loanAdjustmentReport"
            :isOpen="loanReportModal"
            @modal-close="closeLoanReportModal"
        />

        <!-- Modals -->
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

        <!-- <component
            v-if="singleLoanModal"
            :is="singleLoanComponent"
            :isOpen="singleLoanModal"
            :updateinfo="singleLoanTitle"
            :editStore="singleLoanData"
            :roleId="role_id"
            @modal-close="singleLoanClose"
            @refresh-list="updateInstallment"
        /> -->

        <!-- Filter Section -->
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <div class="row g-3 mb-3">
                    <!-- Department -->
                    <div class="col-lg-3 position-relative">
                        <select
                            class="form-control field-height field-background-colour"
                            v-model="searchDept"
                            :class="{ 'placeholder-text': !searchDept }"
                            @change="getEmployee(searchDept)"
                        >
                            <option value="" disabled>Select Department</option>
                            <!-- <option class="default-selection" value="" disabled>
                                Select Department
                            </option> -->
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
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-2 me-3"
                            @click="
                                searchDept = '';
                                employeeIDs = [];
                            "
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <!-- Employee -->
                    <!-- <div class="col-lg-3 position-relative">
                        <select class="form-control border-color" v-model="searchEmp"
                            style="font-weight: normal; background: transparent">
                            <option value="" disabled>
                                Select Employee Name
                            </option>
                            <option v-for="emp in employeeIDs" :key="emp.id" :value="emp.id">
                                {{ emp.Full_Name }}
                            </option>
                        </select>
                        <button v-if="searchEmp" type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1" @click="searchEmp = ''">
                            <i class="fa fa-times"></i>
                        </button>
                    </div> -->
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

                        <!-- REMOVE this -->
                        <!-- <button
                            v-if="selectedEmployee"
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-2 me-3"
                            @click="selectedEmployee = ''"
                        >
                            <i class="fa fa-times"></i>
                        </button> -->
                    </div>
                    <div class="col-lg-1 position-relative">
                        <select
                            class="form-control field-height field-background-colour"
                            v-model="selectedStatus"
                        >
                            <option value="" disabled>Select Status</option>
                            <option value="active">ACTIVE</option>
                            <option value="closed">CLOSE</option>
                        </select>
                        <button
                            v-if="selectedStatus"
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-2 me-3"
                            @click="selectedStatus = ''"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Loan Table -->
                <div class="mt-5">
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
                                                    <!-- <pre>{{
                                                        installments
                                                    }}</pre> -->
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
                                                                >
                                                                    {{
                                                                        ins.installment_amounts
                                                                    }}
                                                                </span>
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
                                                                >
                                                                    {{
                                                                        ins.paid_on_dates
                                                                    }}
                                                                </span>
                                                            </td>
                                                            <td
                                                                class="text-center text-nowrap"
                                                            >
                                                                <!-- <button
                                                                    class="btn btn-sm btn-light border me-1"
                                                                    title="Edit"
                                                                    @click="
                                                                        singleLoanAdjustment(
                                                                            ins.id,
                                                                            ins.loan_id,
                                                                        )
                                                                    "
                                                                >
                                                                    <i
                                                                        class="fa-regular fa-pen-to-square text-warning"
                                                                    ></i>
                                                                </button> -->
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
                    <!-- <div class="d-flex justify-content-end mt-0">
                        <Bootstrap4Pagination
                            :data="paginationObject"
                            :limit="2"
                            @pagination-change-page="getData"
                        />
                    </div> -->
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
.text-uppercase {
    text-transform: uppercase;
}
.table-responsive .table,
.table-responsive .table tbody,
.table-responsive .table tr,
.table-responsive .table td {
    /* background-color: rgba(234, 236, 234, 0.2) !important; */
    background: #ffffffd5 !important;
    color: #000000;
    border: 0.8px solid #7e7c7c63;
    letter-spacing: 0.3px;
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
</style>
