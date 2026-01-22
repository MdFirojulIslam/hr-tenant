<script setup>
import { ref, onMounted, computed, defineAsyncComponent } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import api from "@/api";
import Swal from "sweetalert2";

// modal component
const salaryAdjustmentComponent = defineAsyncComponent(() =>
    import("@/pages/payroll/component/salaryAdjustmentEdit.vue")
);

const salaryAdjustmentComponentNew = defineAsyncComponent(() =>
    import("@/pages/payroll/component/salaryAdjustmentForm.vue")
);
// states
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());

const adjustments = ref([]);

const searchYear = ref("");
const searchMonth = ref("");
const searchDept = ref("");
const searchEmp = ref("");

const department = ref([]);
const employeeIDs = ref([]);

const salaries = ref({}); // pagination object
const salaryRecords = ref([]); // actual rows

// convert department full name to short name
const getDeptShortName = (name) => {
    const map = {
        "General Trade": "GT",
        "Information Technology & Communication": "ITC",
        "Human Resources": "HR",
        "Finance & Accounts": "FA",
        Marketing: "MKT",
    };
    return map[name] || name; // fallback to full name if not found
};

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

const formatMonthYear = (dateString) => {
    const date = new Date(dateString);
    if (isNaN(date)) return "";
    return date.toLocaleString("default", { month: "long", year: "numeric" });
};

// modal states
const heading = ref("");
const selectedStore = ref(null);
const salaryId = ref(null);
const salaryAdjustmentModel = ref(false);

const salaryAdjustmentModelNew = ref(false);

const isLoading = ref(true);
const error = ref(null);

// fetch data with pagination
const getData = async (page = 1) => {
    try {
        const [deptRes, adjustmentTypeRes, adjustmentRes] = await Promise.all([
            api.get("/department"),
            api.get("/salary-adjustment-type"),
            api.get(`/salary-adjustment?page=${page}`),
        ]);
        department.value = deptRes.data;
        adjustments.value = adjustmentTypeRes.data;
        salaries.value = adjustmentRes.data; // full pagination object
        salaryRecords.value = adjustmentRes.data.data; // rows
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
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

// computed filtered data
const filteredData = computed(() => {
    return salaryRecords.value.filter((item) => {
        const matchDept = searchDept.value
            ? item.dept_id == searchDept.value
            : true;
        const matchEmp = searchEmp.value ? item.eid == searchEmp.value : true;

        const [year, month] = item.date ? item.date.split("-") : [null, null];
        const matchYear = searchYear.value ? searchYear.value == year : true;
        const matchMonth = searchMonth.value
            ? parseInt(searchMonth.value) == parseInt(month)
            : true;

        return matchDept && matchEmp && matchYear && matchMonth;
    });
});

// modal functions
const salaryAdjustmentOpen = (title) => {
    heading.value = title;
    salaryAdjustmentModel.value = true;
};

const salaryAdjustmentNew = (title) => {
    heading.value = title;
    salaryAdjustmentModelNew.value = true;
};

const salaryAdjustmentClose = () => {
    salaryAdjustmentModel.value = false;
    salaryAdjustmentModelNew.value = false;
    getData();
};

// const editSalaryAdjustment = (eid, date) => {
//     selectedStore.value = salaryRecords.value.find(
//         (item) => item.eid === eid && item.date === date
//     );
//     salaryId.value = eid; // or any unique identifier you want to track
//     salaryAdjustmentOpen("Update");
// };

const editSalaryAdjustment = (item, eid) => {
    selectedStore.value = item; // direct reference
    salaryId.value = eid;
    salaryAdjustmentOpen("Update");
};

// mounted
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
            <h2 class="m-0">Salary Adjustment List</h2>
            <div>
                <button
                    @click="salaryAdjustmentNew('Save')"
                    type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm"
                >
                    <i class="fa-solid fa-plus me-2"></i> New Adjustment
                </button>
            </div>
        </div>

        <!-- Salary Modal -->
        <component
            v-if="salaryAdjustmentModel"
            :is="salaryAdjustmentComponent"
            :isOpen="salaryAdjustmentModel"
            :editStore="selectedStore"
            :updateinfo="heading"
            :salId="salaryId"
            @modal-close="salaryAdjustmentClose"
            @submit="getData"
            name="first-modal"
        />

        <component
            v-if="salaryAdjustmentModelNew"
            :is="salaryAdjustmentComponentNew"
            :isOpen="salaryAdjustmentModelNew"
            :updateinfo="heading"
            :salId="salaryId"
            @modal-close="salaryAdjustmentClose"
            @submit="getData"
            name="first-modal"
        />

        <!-- Table & Filters -->
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <!-- Filters -->
                <div class="row g-3 mb-3">
                    <!-- Department -->
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

                    <!-- Year -->
                    <div class="col-lg-2 position-relative">
                        <select
                            class="form-control border-color"
                            v-model="searchYear"
                            style="font-weight: normal; background: transparent"
                        >
                            <option value="" disabled>Select Year</option>
                            <option
                                v-for="year in years"
                                :key="year"
                                :value="year"
                            >
                                {{ year }}
                            </option>
                        </select>
                        <button
                            v-if="searchYear"
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
                            @click="searchYear = ''"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <!-- Month -->
                    <div class="col-lg-2 position-relative">
                        <select
                            class="form-control border-color"
                            v-model="searchMonth"
                            style="font-weight: normal; background: transparent"
                        >
                            <option value="" disabled>Select Month</option>
                            <option
                                v-for="(month, index) in months"
                                :key="month"
                                :value="index + 1"
                            >
                                {{ month }}
                            </option>
                        </select>
                        <button
                            v-if="searchMonth"
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
                            @click="searchMonth = ''"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
                <!-- Salary Table -->
                <div class="mt-5">
                    <div
                        class="d-flex align-items-center px-2 py-2 fw-semibold border-bottom bg-light"
                    >
                        <span class="me-2">Total:</span>
                        <span
                            class="bg-white border rounded px-3 py-1 fw-medium"
                            >{{ salaries.total }}</span
                        >
                    </div>
                    <div class="table-responsive shadow-sm bg-light">
                        <table
                            class="table table-bordered table-hover table-sm"
                        >
                            <thead class="table-light">
                                <tr class="text-nowrap fw-bold">
                                    <th style="width: 3%">S/N</th>
                                    <th style="width: 8%">EMPLOYEE ID</th>
                                    <th style="width: 16%">EMPLOYEE NAME</th>
                                    <th style="width: 13%">DEPARTMENT</th>
                                    <th style="width: 10%">ADJUSTMENT TYPE</th>
                                    <th style="width: 14%">ADJUSTMENT NAME</th>
                                    <th style="width: 10%">DATE</th>
                                    <th style="width: 10%">AMOUNT</th>
                                    <th style="width: 10%">REMARKS</th>
                                    <th class="text-center" style="width: 6%">
                                        ACTION
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    class="text-nowrap"
                                    v-for="(item, index) in filteredData"
                                    :key="index"
                                >
                                    <td>{{ index + 1 }}</td>
                                    <td>
                                        ANBN-{{ item.employee_official_id }}
                                    </td>
                                    <td>{{ item.emp_name }}</td>
                                    <td>
                                        {{ getDeptShortName(item.emp_dept) }}
                                    </td>
                                    <td>
                                        {{
                                            item.type == "earning"
                                                ? "Earning"
                                                : "Deduction"
                                        }}
                                    </td>
                                    <td>{{ item.adjustment_name }}</td>
                                    <td>{{ formatMonthYear(item.date) }}</td>
                                    <td>{{ item.amount }}</td>
                                    <td>{{ item.remarks }}</td>
                                    <td class="text-center">
                                        <button
                                            @click="
                                                editSalaryAdjustment(
                                                    item,
                                                    item.eid
                                                )
                                            "
                                            class="custom-btn btn-15"
                                        >
                                            <i
                                                class="fa-regular fa-pen-to-square"
                                            ></i>
                                        </button>
                                    </td>
                                    <!-- <button
                                        class="btn btn-sm btn-light border"
                                        title="Delete"
                                        @click="
                                            deleteadjustment(item.employee_id)
                                        "
                                    >
                                        <i
                                            class="fa-solid fa-trash text-danger"
                                        ></i>
                                    </button> -->
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
