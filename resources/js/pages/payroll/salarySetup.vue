<script setup>
import { ref, onMounted, computed, defineAsyncComponent } from "vue";

import { Bootstrap4Pagination } from "laravel-vue-pagination";
import api from "@/api";
import Swal from "sweetalert2";

// import { useRouter } from "vue-router";
// const router = useRouter();

import { useStore } from "vuex";
const store = useStore();
const role_id = store.getters.getRoleId;

const salaryComponent = defineAsyncComponent(() =>
    import("./component/salarySetupForm.vue")
);

// const currentYear = ref(new Date().getFullYear());

const isLoading = ref(true);
const error = ref(null);
const selectedStore = ref(null);
const heading = ref(null);
const filter = ref("");

const department = ref([]);
const employeeIDs = ref([]);
const searchDept = ref("");
const searchEmp = ref("");

const salaries = ref([]);

const salaryId = ref();
const salaryModel = ref(false);

const salaryOpen = (title) => {
    heading.value = title;

    if (title === "Save") {
        selectedStore.value = null; // reset previous edit data
        salaryId.value = null; // reset salary id
    }

    salaryModel.value = true;
};

const salaryClose = () => {
    salaryModel.value = false;
    getData();
};

const openSalaryDetails = (id) => {
    window.open(`/payroll/salary-details/${id}`, "_blank");
};

// get employees for department
const getEmployee = async (deptID) => {
    if (!deptID) {
        employeeIDs.value = [];
        return;
    }
    const empIDs = await api.get(`/emp/${deptID}`);
    employeeIDs.value = empIDs.data;
};

const employeeName = ref("");
const searchQuery = ref(""); // single input field for account_no or official_id

const searchemp = async () => {
    // Only trigger search if something is typed
    if (searchQuery.value) {
        try {
            const response = await api.get("/employee-salary-search", {
                params: {
                    query: searchQuery.value, // send the input as 'query'
                },
            });
            salaries.value = response.data; // replace current salaries
        } catch (error) {
            console.error(error);
        }
    } else {
        getData(); // fetch all if input is empty
    }
};

const getData = async (page = 1) => {
    try {
        const [deptRes, salary] = await Promise.all([
            api.get("/department"),
            api.get(`/salary?page=${page}`),
        ]);
        department.value = deptRes.data;
        salaries.value = salary.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

const filteredData = computed(() => {
    // check if salaries.value has 'data'
    const salaryList = salaries.value.data || [];

    return salaryList.filter((item) => {
        const matchesDept = searchDept.value
            ? item.department_id == searchDept.value
            : true;
        const matchesEmp = searchEmp.value
            ? item.employee_id == searchEmp.value
            : true;

        return matchesDept && matchesEmp;
    });
});

const editsalary = async (id) => {
    try {
        selectedStore.value = salaries.value.data.find(
            (item) => item.employee_id === id
        );
        salaryId.value = id;
        salaryOpen("Update");
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const deletesalary = async (id) => {
    try {
        //const response = await api.delete(`/delete-holiday/${id}`);
        if (response.data.success) {
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been Deleted",
                showConfirmButton: false,
                timer: 1500,
            });
            await getData();
        }
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const submitHandler = async () => {
    await getData();
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
            <h2 class="m-0">Salary Setup List</h2>
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
                            class="form-control border-color"
                            v-model="searchDept"
                            @change="getEmployee(searchDept)"
                            style="font-weight: normal; background: transparent"
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
                            v-if="searchDept"
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
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
                        <select
                            class="form-control border-color"
                            v-model="searchEmp"
                            style="font-weight: normal; background: transparent"
                        >
                            <option value="" disabled>
                                Select Employee Name
                            </option>
                            <option
                                v-for="emp in employeeIDs"
                                :key="emp.id"
                                :value="emp.id"
                            >
                                {{ emp.Full_Name }}
                            </option>
                        </select>
                        <button
                            v-if="searchEmp"
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
                            @click="searchEmp = ''"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="col-lg-3 position-relative">
                        <input
                            type="text"
                            class="form-control border-color"
                            v-model="searchQuery"
                            placeholder="Search By Account No or Official ID"
                            @input="searchemp"
                        />
                    </div>
                </div>

                <!-- Salary Table -->
                <div class="border rounded mt-5">
                    <!-- Total Employees Row -->
                    <div
                        class="d-flex align-items-center px-2 py-2 fw-semibold border-bottom bg-light"
                    >
                        <span class="me-2">Total Employees:</span>
                        <span
                            class="bg-white border rounded px-3 py-1 fw-medium"
                            >{{ salaries.total }}</span
                        >
                    </div>

                    <div class="table-responsive px-2 bg-light">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th width="4%">S/N</th>
                                    <th width="7%">OFFICIAL ID</th>
                                    <th width="8%">EMPLOYEE NAME</th>
                                    <th width="15%">DEPARTMENT</th>
                                    <th width="10%">GROSS SALARY</th>
                                    <th width="10%">EARNING</th>
                                    <th width="10%">DEDUCTION</th>
                                    <th width="8%">TOTAL PAY</th>
                                    <th width="10%">BANK ACCOUNT</th>
                                    <th width="10%" class="text-center">
                                        ACTION
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(salary, index) in filteredData"
                                    :key="salary.id"
                                >
                                    <td>{{ index + 1 }}</td>
                                    <td>ABNB-{{ salary.official_id }}</td>
                                    <td>{{ salary.full_name }}</td>
                                    <td>{{ salary.department_name }}</td>
                                    <td>{{ salary.gross_salary }}</td>
                                    <td>
                                        {{
                                            salary.total_earning == 0
                                                ? "-"
                                                : salary.total_earning
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            salary.total_deduction == 0
                                                ? "-"
                                                : salary.total_deduction
                                        }}
                                    </td>
                                    <td>{{ salary.net_pay }}</td>
                                    <td>{{ salary.bank_account_no }}</td>

                                    <td
                                        v-if="role_id == 1 || role_id == 2"
                                        class="text-center text-nowrap"
                                    >
                                        <button
                                            class="btn btn-sm btn-light border me-1"
                                            title="View"
                                            @click="
                                                openSalaryDetails(
                                                    salary.employee_id
                                                )
                                            "
                                        >
                                            <i
                                                class="fa-regular fa-user text-primary"
                                            ></i>
                                        </button>
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
                        :limit="2"
                        @pagination-change-page="getData"
                    />
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
