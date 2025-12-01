<script setup>
import { ref, onMounted, computed, defineAsyncComponent } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import api from "@/api";
import { useStore } from "vuex";

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

// ---------------- Async Components ----------------
const loanSetupComponent = defineAsyncComponent(() =>
    import("./component/loanSetupForm.vue")
);
const repaymentComponent = defineAsyncComponent(() =>
    import("./component/loanRepayment.vue")
);
const singleLoanComponent = defineAsyncComponent(() =>
    import("./component/installmentForm.vue")
);

// ---------------- Fetch Table Data ----------------
const getData = async (page = 1) => {
    try {
        const [deptRes, loanRes] = await Promise.all([
            api.get("department"),
            api.get(`/get-employee-loan?page=${page}`),
        ]);
        department.value = deptRes.data;
        paginationObject.value = loanRes.data;
        tableInfo.value = loanRes.data.data;
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
        const matchDept = form.value.department
            ? item.deptID == form.value.department
            : true;
        const matchEmp = form.value.eid ? item.eid == form.value.eid : true;
        return matchDept && matchEmp;
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
    heading.value = "Update Loan";
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
const singleLoanOpen = (item, installment) => {
    singleLoanData.value = { item, installment };
    singleLoanTitle.value = "Update Loan Repayment";
    singleLoanModal.value = true;
};
const singleLoanClose = () => {
    singleLoanModal.value = false;
    getData();
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

        <component
            v-if="singleLoanModal"
            :is="singleLoanComponent"
            :isOpen="singleLoanModal"
            :updateinfo="singleLoanTitle"
            :editStore="singleLoanData"
            :roleId="role_id"
            @modal-close="singleLoanClose"
            @refresh-list="getData"
        />

        <!-- Filter Section -->
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <div class="row g-3 mv-3">
                    <div class="col-lg-3 position-relative">
                        <select
                            class="border-color form-control"
                            v-model="form.department"
                            @change="getEmployee(form.department)"
                        >
                            <option value="" disabled>Select Department</option>
                            <option
                                v-for="dept in department"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ dept.Name }}
                            </option>
                        </select>
                        <button
                            v-if="form.department"
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
                            @click="
                                form.department = '';
                                employees = [];
                            "
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <div class="col-lg-3 position-relative">
                        <select
                            class="form-control border-color"
                            v-model="form.eid"
                            style="font-weight: normal; background: transparent"
                        >
                            <option value="" disabled>
                                Select Employee Name
                            </option>
                            <option
                                v-for="emp in employees"
                                :key="emp.id"
                                :value="emp.id"
                            >
                                {{ emp.Full_Name }}
                            </option>
                        </select>
                        <button
                            v-if="form.eid"
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
                            @click="form.eid = ''"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Loan Table -->
                <div class="rounded border mt-5">
                    <div
                        class="d-flex align-items-center px-2 py-2 fw-semibold border-bottom bg-light"
                    >
                        <span class="me-2">Total Employees:</span>
                        <span
                            class="bg-white border rounded px-3 py-1 fw-medium"
                            >{{ paginationObject.total }}</span
                        >
                    </div>

                    <div class="table-responsive px-2 bg-light">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"></th>
                                    <th width="5%">S/N</th>
                                    <th width="13%">EMPLOYEE NAME</th>
                                    <th width="15%">DEPARTMENT</th>
                                    <th width="12%">PRINCIPAL AMOUNT</th>
                                    <th width="8%">TENURE</th>
                                    <th width="10%">MONTHLY INSTALLMENT</th>
                                    <th width="5%">STATUS</th>
                                    <th width="10%">REMARKS</th>
                                    <th width="3%">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template
                                    v-for="(item, index) in filteredData"
                                    :key="item.loan_id"
                                >
                                    <tr>
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
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ item.employee_name }}</td>
                                        <td>{{ item.deptName }}</td>
                                        <td>{{ item.principal_amounts }}</td>
                                        <td>{{ item.tenure_months }}</td>
                                        <td>{{ item.monthly_installments }}</td>
                                        <td>{{ item.status }}</td>
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
                                        <td colspan="10" class="bg-light">
                                            <div
                                                class="ms-5 p-1"
                                                style="width: 50%"
                                            >
                                                <table
                                                    class="table table-bordered table-sm mb-0"
                                                >
                                                    <thead>
                                                        <tr>
                                                            <th>
                                                                Installment No
                                                            </th>
                                                            <th>Paid Amount</th>
                                                            <th>Status</th>
                                                            <th>Paid Date</th>
                                                            <th>Edit</th>
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
                                                            <td>{{ i + 1 }}</td>
                                                            <td>
                                                                {{
                                                                    ins.installment_amounts
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{
                                                                    ins.paid_status
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{
                                                                    ins.paid_on_dates
                                                                }}
                                                            </td>
                                                            <td>
                                                                <button
                                                                    class="btn btn-sm btn-light border"
                                                                    @click="
                                                                        singleLoanOpen(
                                                                            item,
                                                                            ins
                                                                        )
                                                                    "
                                                                >
                                                                    <i
                                                                        class="fa-solid fa-pen text-success"
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
                    <div class="d-flex justify-content-end mt-0">
                        <Bootstrap4Pagination
                            :data="paginationObject"
                            :limit="2"
                            @pagination-change-page="getData"
                        />
                    </div>
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
