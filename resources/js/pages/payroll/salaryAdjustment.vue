<script setup>
import { ref, onMounted, computed, defineAsyncComponent } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";
import { useRouter } from "vue-router";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

// ----------------------------
// BASIC SETUP
// ----------------------------
const store = useStore();
const user = store.getters.getUser;

// modal component
const salaryAdjustmentComponent = defineAsyncComponent(
    () => import("@/pages/payroll/component/salaryAdjustmentEdit.vue"),
);

const salaryAdjustmentComponentNew = defineAsyncComponent(
    () => import("@/pages/payroll/component/salaryAdjustmentForm.vue"),
);

const salaryAdjustmentReport = defineAsyncComponent(
    () => import("@/pages/payroll/component/salaryAdjustmentReport.vue"),
);

// states
const selectedMonth = ref(new Date().getMonth() + 1); // optional
const selectedYear = ref(new Date().getFullYear()); // optional

// previous month (if current month is January, it becomes December)
const today = new Date();
const previousMonth = today.getMonth() === 0 ? 12 : today.getMonth(); // 0 = Jan
const searchMonth = ref(previousMonth); // 1 = Jan, 12 = Dec

const searchDept = ref("");
const searchEmp = ref("");

const searchYear = ref("");

const department = ref([]);
const employeeIDs = ref([]);
const employee = ref([]);

const adjustments = ref([]);
const salaries = ref({});
const salaryRecords = ref([]);
const selectedEmployee = ref(null);

const reportModal = ref(false);
const downloadReport = () => {
    reportModal.value = true;
};
const closeReportModal = () => {
    reportModal.value = false;
};

const onEmployeeSelect = (emp) => {
    if (!emp) return;
    form.eid = emp.id;
    searchEmp.value = emp.id; // if you use searchEmp for filtering
    getEmployeeImg(emp.id);
};

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

const years = ref(["2025", "2026", "2027", "2028", "2029", "2030"]);

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
const getData = async () => {
    isLoading.value = true;
    try {
        const [deptRes, adjustmentTypeRes, adjustmentRes] = await Promise.all([
            api.get(`/department-list/${user.id}`),
            api.get("/salary-adjustment-type"),
            api.get(`/salary-adjustment/${user.id}`),
        ]);
        department.value = deptRes.data;
        adjustments.value = adjustmentTypeRes.data;
        salaryRecords.value = adjustmentRes.data; // direct array
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

// get employees for department
const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        return;
    }
    try {
        const res = await api.get(`/adjustment-employees/${deptID}`);
        employee.value = res.data;
    } catch (err) {
        console.error("Error fetching employees:", err);
    }
};

// computed filtered data
const filteredData = computed(() => {
    return salaryRecords.value.filter((item) => {
        const matchDept = searchDept.value
            ? item.dept_id == searchDept.value
            : true;
        const matchEmp = selectedEmployee.value
            ? item.eid == selectedEmployee.value.id
            : true;

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
            <h2 class="m-0 header-design">Salary Adjustment List</h2>
            <div class="d-flex gap-2">
                <button @click="downloadReport()" class="btn btn-primary">
                    <i class="fa-solid fa-download me-2"></i> Download Report
                </button>

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
        </div>
        <!-- Download Report Modal -->
        <component
            v-if="reportModal"
            :is="salaryAdjustmentReport"
            :isOpen="reportModal"
            @modal-close="closeReportModal"
        />

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
                            :options="employee"
                            label="Full_Name"
                            track-by="id"
                            placeholder="Select or search employee"
                            :searchable="true"
                            :close-on-select="true"
                            :allow-empty="true"
                            @input="onEmployeeSelect"
                            :clear-on-select="false"
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

                    <!-- Year -->
                    <div class="col-lg-2 position-relative">
                        <select
                            class="form-control field-height field-background-colour"
                            :class="{ 'placeholder-text': !searchYear }"
                            v-model="searchYear"
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
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-2 me-3"
                            @click="searchYear = ''"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <!-- Month -->
                    <div class="col-lg-2 position-relative">
                        <select
                            class="form-control field-height field-background-colour"
                            v-model="searchMonth"
                        >
                            <option class="default-selection" disabled>
                                Select Month
                            </option>
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
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-2 me-3"
                            @click="searchMonth = ''"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
                <!-- Salary Table -->
                <div class="mt-4">
                    <div class="table-responsive shadow-sm">
                        <table
                            class="table table-bordered table-hover table-sm"
                        >
                            <thead class="table-light">
                                <tr class="text-nowrap fw-bold">
                                    <th class="text-center" style="width: 3%">
                                        S/N
                                    </th>
                                    <th class="text-center" style="width: 8%">
                                        EMPLOYEE ID
                                    </th>
                                    <th style="width: 16%">EMPLOYEE NAME</th>
                                    <th style="width: 13%">DEPARTMENT</th>
                                    <th class="text-center" style="width: 10%">
                                        ADJUSTMENT TYPE
                                    </th>
                                    <th class="text-center" style="width: 14%">
                                        ADJUSTMENT NAME
                                    </th>
                                    <th class="text-center" style="width: 10%">
                                        DATE
                                    </th>
                                    <th class="text-center" style="width: 10%">
                                        AMOUNT
                                    </th>
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
                                    <td class="text-center">
                                        ANBN-{{ item.employee_official_id }}
                                    </td>
                                    <td>{{ item.emp_name }}</td>
                                    <td>
                                        {{ item.emp_dept }}
                                    </td>
                                    <td class="text-center">
                                        {{
                                            item.type == "earning"
                                                ? "Earning"
                                                : "Deduction"
                                        }}
                                    </td>
                                    <td class="text-center">
                                        {{ item.adjustment_name }}
                                    </td>
                                    <td class="text-center">
                                        {{ formatMonthYear(item.date) }}
                                    </td>
                                    <td class="text-center">
                                        {{ item.amount }}
                                    </td>
                                    <td>{{ item.remarks }}</td>
                                    <td class="text-center">
                                        <button
                                            @click="
                                                editSalaryAdjustment(
                                                    item,
                                                    item.eid,
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
