<script setup>
import { ref, onMounted, computed, defineAsyncComponent } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import api from "@/api";
import { useStore } from "vuex";

// ---------------- Vuex ----------------
const store = useStore();
const role_id = store.getters.getRoleId;

// ---------------- States ----------------
const form = ref({ department: "", eid: "" });
const department = ref([]);
const employees = ref([]);
const tableInfo = ref([]);
const paginationObject = ref({});
const error = ref("");
const isLoading = ref(true);

// ---------------- Loan Setup Modal ----------------
const heading = ref("");
const loanSetupModel = ref(false);
const selectedStore = ref(null);

// ---------------- Loan Repayment Modal ----------------
const repaymentHeading = ref("");
const repaymentModal = ref(false);
const selectedRepayment = ref(null);

// ---------------- Async Components ----------------
const loanSetupComponent = defineAsyncComponent(() =>
    import("./component/loanSetupForm.vue")
);
const repaymentComponent = defineAsyncComponent(() =>
    import("./component/loanRepayment.vue")
);

// ---------------- Fetch Data ----------------
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

// ---------------- Loan Setup ----------------
const loanSetupOpen = (title) => {
    heading.value = title;
    selectedStore.value = null;
    loanSetupModel.value = true;
};

const loanAdjustment = (loanId) => {
    const loan = tableInfo.value.find((item) => item.loanID === loanId);
    if (!loan) return;

    selectedStore.value = {
        loanID: loan.loanID,
        id: loan.eid,
        empName: loan.empName,
        deptId: loan.deptID,
        deptName: loan.deptName,
        principal: loan.principal,
        tenure: loan.tenure_months,
        monthlyInstallment: loan.monthly_installments,
        remarks: loan.remarks,
        first_due_on: loan.first_due_on,
    };

    heading.value = "Update";
    loanSetupModel.value = true;
};

const loanSetupClose = () => {
    loanSetupModel.value = false;
    getData();
};

// ---------------- Loan Repayment ----------------
const repayment = (title, loanData = null) => {
    repaymentHeading.value = title;
    selectedRepayment.value = loanData;
    repaymentModal.value = true;
};

const repaymentClose = () => {
    repaymentModal.value = false;
    getData();
};

// ---------------- Lifecycle ----------------
onMounted(() => getData());
</script>

<template>
    <div class="container-fluid h-100 d-flex flex-column p-0 m-0">
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
                    @click="repayment('repayments')"
                    type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm mb-2"
                >
                    <i class="fa-solid fa-plus me-2"></i>Loan Repayment Setup
                </button>
            </div>
        </div>

        <!-- Loan Setup Modal -->
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

        <!-- Loan Repayment Modal -->
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

        <!-- Filter Section -->
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <div class="row g-3 mv-3">
                    <div class="col-lg-3 position-relative">
                        <select
                            class="form-control"
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
                            class="form-control"
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
                                    <th width="3%">S/N</th>
                                    <th width="15%">EMPLOYEE NAME</th>
                                    <th width="15%">DEPARTMENT</th>
                                    <th width="12%">PRINCIPAL AMOUNT</th>
                                    <th width="8%">TENURE</th>
                                    <th width="10%">MONTHLY INSTALLMENT</th>
                                    <th width="5%">STATUS</th>
                                    <th width="10%">REMARKS</th>
                                    <th width="3%" class="text-right">
                                        ACTION
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in filteredData"
                                    :key="index"
                                >
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
                                            @click="loanAdjustment(item.loanID)"
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
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
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
</template>
