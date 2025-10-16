<script setup>
import { ref, onMounted, computed, defineAsyncComponent } from "vue";
import api from "@/api";
import { Bootstrap4Pagination } from "laravel-vue-pagination";

import { useStore } from "vuex";

const store = useStore();
const role_id = store.getters.getRoleId;

const deptName = ref("");
const empName = ref("");
const employees = ref([]);
const searchDept = ref([]);
const tableInfo = ref([]);
const paginationObject = ref({});
const error = ref("");
const isLoading = ref(true);

//Model status
const heading = ref("");
const loanSetupModel = ref(false);
const selectedStore = ref(null);

const getData = async (page = 1) => {
    try {
        const [searchDeptResponse, tableInfoResponse] = await Promise.all([
            api.get("department"),
            api.get(`/get-employee-loan?page=${page}`),
        ]);
        searchDept.value = searchDeptResponse.data;
        paginationObject.value = tableInfoResponse.data;
        tableInfo.value = tableInfoResponse.data.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

const getEmployee = async (deptID) => {
    if (!deptID) {
        employees.value = [];
        return;
    } else {
        const employeesResponse = await api.get(`/emp/${deptID}`);
        employees.value = employeesResponse.data;
    }
};

const filteredData = computed(() => {
    return tableInfo.value.filter((item) => {
        const matchDept = deptName.value ? item.deptID == deptName.value : true;
        const matchEmp = empName.value ? item.eid == empName.value : true;
        return matchDept && matchEmp;
    });
});

const loanSetupComponent = defineAsyncComponent(() =>
    import("./component/loanSetupForm.vue")
);

const loanSetupOpen = (title) => {
    heading.value = title;
    selectedStore.value = null; // <-- reset selected loan info
    loanSetupModel.value = true;
};

const loanAdjustment = (loanId) => {
    const loan = tableInfo.value.find((item) => item.loanID === loanId);

    selectedStore.value = {
        loanID: loan.loanID,
        id: loan.eid,
        empName: loan.empName,
        deptId: loan.deptID,
        deptName: loan.deptName,
        principal: loan.principal,
        tenure: loan.tenure_months,
        monthlyInstallment: loan.monthly_installment,
        remarks: loan.remarks,
        first_due_on: loan.first_due_on,
    };

    heading.value = "Update";
    loanSetupModel.value = true;
};

const loanSetupClose = () => {
    loanSetupModel.value = false; // this hides the modal
    getData(); // optional: refresh the table
};

onMounted(() => getData());
</script>

<template>
    <div class="container-fluid h-100 d-flex flex-column p-0 m-0">
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="m-0">Employee Loan List</h2>
            <div>
                <button
                    @click="loanSetupOpen('Save')"
                    type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm"
                >
                    <i class="fa-solid fa-plus me-2"></i>New Loan Setup
                </button>
            </div>
        </div>

        <!-- Loan Model -->
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

        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <div class="row g-3 mv-3">
                    <div class="col-lg-3 position-relative">
                        <select
                            v-model="deptName"
                            class="form-control"
                            @change="getEmployee(deptName)"
                        >
                            <option value="" disabled>Select Department</option>
                            <option
                                v-for="dept in searchDept"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ dept.Name }}
                            </option>
                        </select>
                        <button
                            v-if="deptName"
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
                            @click="deptName = ''"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="col-lg-3 position-relative">
                        <select v-model="empName" class="form-control">
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
                            v-if="empName"
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
                            @click="empName = ''"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="rounded border mt-5">
                    <div
                        class="d-flex align-items-center px-2 py-2 fw-semibold border-buttom bg-light"
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
                                    <th width="4%">S/N</th>
                                    <th width="8%">EMPLOYEE ID</th>
                                    <th width="15%">EMPLOYEE NAME</th>
                                    <th width="20%">DEPARTMENT</th>
                                    <th width="12%">PRINCIPLE AMOUNT</th>
                                    <th width="8%">TENURE</th>
                                    <th width="15%">MONTHLY INSTALLMENT</th>
                                    <th width=" 5%">STATUS</th>
                                    <th width=" 5%">REMARKS</th>
                                    <th width="10%" class="text-center">
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
                                    <td>ABNB-{{ item.eid }}</td>
                                    <td>{{ item.empName }}</td>
                                    <td>{{ item.deptName }}</td>
                                    <td>{{ item.principal }}</td>
                                    <td>{{ item.tenure_months }}</td>
                                    <td>{{ item.monthly_installment }}</td>
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
