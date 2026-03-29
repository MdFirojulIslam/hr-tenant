<script setup>
import { ref, onMounted, watch, defineAsyncComponent } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
const store = useStore();
const role_id = store.getters.getRoleId;
const user = store.getters.getUser;

const salaryComponent = defineAsyncComponent(
    () => import("./component/salarySetupForm.vue"),
);

const isLoading = ref(true);
const error = ref(null);

const employee = ref([]);
const employeeIDs = ref([]);
const selectedEmployee = ref(null);

const department = ref([]);
const salaries = ref({});

const searchDept = ref("");
const searchEmp = ref("");
const searchQuery = ref("");

const salaryModel = ref(false);
const selectedStore = ref(null);
const salaryId = ref(null);
const heading = ref("");

const currentPage = ref(1);

const onEmployeeSelect = (value) => {
    selectedEmployee.value = value;
    if (value) {
        searchEmp.value = value.id;
    } else {
        searchEmp.value = "";
    }
};

/* ---------------- FETCH DATA ---------------- */
const getData = async (page = 1) => {
    currentPage.value = page;
    try {
        const [deptRes, salaryRes] = await Promise.all([
            api.get(`/department-list/${user.id}`),
            api.get("/salary", {
                params: {
                    page,
                    query: searchQuery.value,
                    department_id: searchDept.value,
                    employee_id: searchEmp.value,
                },
            }),
        ]);

        department.value = deptRes.data;
        salaries.value = salaryRes.data;
    } catch (err) {
        error.value = err.message;
    } finally {
        isLoading.value = false;
    }
};

/* ---------------- EMPLOYEE BY DEPT ---------------- */
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
        console.error("Error fetching employees:", err);
    }
};

/* ---------------- WATCH FILTERS ---------------- */
watch([searchDept, searchEmp, searchQuery], () => {
    getData();
});

/* ---------------- MODAL ---------------- */
const salaryOpen = (title) => {
    heading.value = title;
    if (title === "Save") {
        selectedStore.value = null;
        salaryId.value = null;
    }
    salaryModel.value = true;
};

const salaryClose = () => {
    salaryModel.value = false;
    getData(currentPage.value);
};

/* ---------------- ACTIONS ---------------- */
const editsalary = (id) => {
    if (!salaries.value.data || salaries.value.data.length === 0) {
        console.warn("Salary data not loaded yet.");
        return;
    }

    const store = salaries.value.data.find((item) => item.employee_id === id);

    if (!store) {
        console.warn("Selected employee not found in salaries.");
        return;
    }

    selectedStore.value = store;
    salaryId.value = id;
    salaryOpen("Update");
};

const deletesalary = async (id) => {
    const res = await api.get(`/delete-salary/${id}`);
    if (res.data.success) {
        Swal.fire({
            icon: "success",
            title: "Deleted successfully",
            timer: 1500,
            showConfirmButton: false,
        });
        getData();
    }
};

onMounted(() => getData());
</script>

<template>
    <div
        class="border rounded container-fluid h-100 d-flex flex-column p-0 m-0"
    >
        <!-- Header -->
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="m-0 header-design">Salary Setup List</h2>
            <div v-if="role_id == 1 || role_id == 2">
                <button
                    @click="salaryOpen('Save')"
                    type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm"
                >
                    <i class="fa-solid fa-plus me-2"></i> New Setup
                </button>
            </div>
        </div>

        <!-- Salary Modal -->
        <component
            v-if="salaryModel"
            :is="salaryComponent"
            :isOpen="salaryModel"
            :editStore="selectedStore"
            :updateinfo="heading"
            :salId="salaryId"
            @modal-close="salaryClose"
            @submit="submitHandler"
            name="first-modal"
        />

        <!-- Table Card -->
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <!-- Filters -->
                <div class="row g-3 mb-3">
                    <div class="col-lg-3 position-relative">
                        <select
                            class="form-control field-height field-background-colour"
                            v-model="searchDept"
                            @change="getEmployee(searchDept)"
                        >
                            <option value="" disabled>All Department</option>
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
                    <div class="col-lg-3 position-relative">
                        <Multiselect
                            v-model="selectedEmployee"
                            :options="employee"
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
                    <div class="col-lg-3 position-relative">
                        <input
                            type="text"
                            class="form-control field-height field-background-colour"
                            v-model="searchQuery"
                            placeholder="Search By Account No or Official ID"
                            @input="searchemp"
                        />
                    </div>
                </div>

                <!-- Salary Table -->
                <div class="mt-3">
                    <!-- Total Employees Row -->
                    <div
                        class="d-flex align-items-center py-2 fw-semibold border-bottom bg-light"
                    >
                        <span class="me-2">Total Employees:</span>
                        <span
                            class="bg-white border rounded px-2 py-1 fw-medium"
                            >{{ salaries.total }}</span
                        >
                    </div>
                    <div class="table-responsive shadow-sm bg-light">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr class="text-nowrap">
                                    <th class="text-center" width="2%">S/N</th>
                                    <th class="text-center" width="8%">
                                        EMP ID
                                    </th>
                                    <th width="15%">DEPARTMENT</th>
                                    <th width="18%">DESIGNATION NAME</th>
                                    <th width="12%">EMPLOYEE NAME</th>
                                    <th class="text-center" width="7%">
                                        GROSS SALARY
                                    </th>
                                    <th class="text-center" width="7%">
                                        EARNING
                                    </th>
                                    <th class="text-center" width="10%">
                                        DEDUCTION
                                    </th>
                                    <th class="text-center" width="7%">
                                        TOTAL PAY
                                    </th>
                                    <th class="text-center" width="10%">
                                        BANK / CASH
                                    </th>
                                    <th width="8%" class="text-center">
                                        ACTION
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="(salary, index) in salaries.data"
                                    :key="salary.id"
                                    class="text-nowrap"
                                >
                                    <td class="text-center">
                                        {{ salaries.from + index }}
                                    </td>
                                    <td class="text-center">
                                        ABNB-{{ salary.official_id }}
                                    </td>
                                    <td>{{ salary.department_name }}</td>
                                    <td>{{ salary.designation_name }}</td>
                                    <td>{{ salary.full_name }}</td>
                                    <td class="text-center">
                                        {{ salary.gross_salary }}
                                    </td>
                                    <td class="text-center">
                                        {{
                                            salary.total_earning == 0
                                                ? "-"
                                                : salary.total_earning
                                        }}
                                    </td>
                                    <td class="text-center">
                                        {{
                                            salary.total_deduction == 0
                                                ? "-"
                                                : salary.total_deduction
                                        }}
                                    </td>
                                    <td class="text-center">
                                        {{ salary.net_pay }}
                                    </td>
                                    <td class="text-center">
                                        {{ salary.bank_account_no ?? "Cash" }}
                                    </td>

                                    <td
                                        v-if="role_id == 1 || role_id == 2"
                                        class="text-center text-nowrap"
                                    >
                                        <!-- <button
                                            class="btn btn-sm btn-light border me-1"
                                            title="View"
                                            @click="
                                                openSalaryDetails(
                                                    salary.employee_id,
                                                )
                                            "
                                        >
                                            <i
                                                class="fa-regular fa-user text-primary"
                                            ></i>
                                        </button> -->
                                        <button
                                            class="btn btn-sm btn-light border me-1"
                                            title="Edit"
                                            @click="
                                                editsalary(salary.employee_id)
                                            "
                                        >
                                            <i
                                                class="fa-regular fa-pen-to-square text-warning"
                                            ></i>
                                        </button>
                                        <button
                                            class="btn btn-sm btn-light border"
                                            title="Delete"
                                            @click="
                                                deletesalary(salary.employee_id)
                                            "
                                        >
                                            <i
                                                class="fa-solid fa-trash text-danger"
                                            ></i>
                                        </button>
                                    </td>
                                    <td v-else></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-end mt-0">
                    <Bootstrap4Pagination
                        :data="salaries"
                        @pagination-change-page="getData"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style>
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
