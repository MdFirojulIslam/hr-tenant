<script setup>
import { ref, onMounted, watch, computed, defineAsyncComponent } from "vue";
import BaseDept from "@/pages/components/input_field/Basedept.vue";
import BaseDeptEmp from "@/pages/components/input_field/BaseDeptEmp.vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";
import { useRouter } from "vue-router";
import EditSalary from "./component/editSalary.vue";

const router = useRouter();

const store = useStore();
const role_id = store.getters.getRoleId;
const user = store.getters.getUser;

const salaryComponent = defineAsyncComponent(
    () => import("./component/salarySetupForm.vue"),
);

const userID = user.id;

const department_id = ref(null);
const employee_id = ref(null);
const isLoading = ref(true);
const error = ref(null);
const search_query = ref("");
const salaries = ref({});
const status = ref("N");
const currentPage = ref(1);

/*
Modal Variables for edit
*/
const selected_employee_object = ref(null);
const editable_employee_id = ref(null);
const heading = ref("");
const salaryModal = ref(false);

const notification = computed(() => store.state.notification);

const getData = async (page = 1) => {
    currentPage.value = page;
    try {
        const res = await api.get("/all-employee-salaries", {
            params: {
                page,
                search_query: search_query.value,
                department_id: department_id.value,
                employee_id: employee_id.value,
                status: status.value,
            },
        });
        salaries.value = res.data;
    } catch (err) {
        error.value = err.message;
    } finally {
        isLoading.value = false;
    }
};

watch([search_query, department_id, employee_id, status], () => {
    getData();
});

// const editSalary = (id) => {
//     router.push({
//         name: "SalaryEdit",
//         params: { id },
//     });
// };

const editSalary = (id) => {
    const routeData = router.resolve({
        name: "SalaryEdit",
        params: { id },
    });
    window.open(routeData.href, "_blank");
};

const newSetup = (title) => {
    salaryModal.value = true;
};

const salaryModalClose = () => {
    salaryModal.value = false;
    getData(currentPage.value);
};

const submitHandler = (data) => {
    console.log("Received from child:", data);
};

onMounted(() => {
    if (notification.value.successMessage) {
        Swal.fire({
            position: "middle",
            icon: "success",
            title: notification.value.successMessage,
            showConfirmButton: false,
            timer: 1500,
        });

        store.commit("clearNotification");
    }

    getData();
});

// const deletesalary = async (id) => {
//     const res = await api.get(`/delete-salary/${id}`);
//     if (res.data.success) {
//         Swal.fire({
//             icon: "success",
//             title: "Deleted successfully",
//             timer: 1500,
//             showConfirmButton: false,
//         });
//         getData();
//     }
// };
</script>

<template>
    <div
        class="border rounded container-fluid h-100 d-flex flex-column p-0 m-0"
    >
        <!-- Header -->
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="m-0 header-design d-flex align-items-center">
                <i class="salary-icon fa-solid fa-file-invoice-dollar"></i>
                Salary List
            </h2>
            <div v-if="role_id == 1 || role_id == 2">
                <button
                    @click="newSetup()"
                    type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm"
                >
                    <i class="fa-solid fa-plus me-2"></i> New Setup
                </button>
            </div>
        </div>

        <!-- Salary Modal -->
        <component
            v-if="salaryModal"
            :is="salaryComponent"
            :isOpen="salaryModal"
            @modal-close="salaryModalClose"
            @submit="submitHandler"
            name="first-modal"
        />

        <!-- Table Card -->
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <!-- Filters -->
                <div class="row g-3 mb-3">
                    <div class="col-lg-3 department-component">
                        <BaseDept
                            v-model:departmentID="department_id"
                            :userID="userID"
                        />
                    </div>

                    <div class="col-lg-3 position-relative col-sm-12">
                        <BaseDeptEmp
                            v-model:employeeID="employee_id"
                            :deptID="department_id"
                            :isEdit="false"
                        />
                    </div>

                    <div class="col-lg-3 position-relative col-sm-12">
                        <input
                            type="text"
                            class="form-control search-field"
                            v-model="search_query"
                            placeholder="Search By Account No or Official ID"
                            @input="searchemp"
                        />
                    </div>

                    <div class="col-md-2 position-relative">
                        <select class="active-status" v-model="status">
                            <option value="N">ACTIVE</option>
                            <option value="Y">DEACTIVE</option>
                            <option value="all">ALL</option>
                        </select>
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
                                    <th width="12%">EMPLOYEE NAME</th>
                                    <th width="15%">DEPARTMENT</th>
                                    <th width="18%">DESIGNATION NAME</th>

                                    <th class="text-center">E_DATE</th>
                                    <th class="text-center" width="7%">
                                        GROSS SALARY
                                    </th>
                                    <th class="text-center" width="7%">
                                        EARNING
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
                                        ABNB{{ salary.official_id }}
                                    </td>
                                    <td>{{ salary.full_name }}</td>
                                    <td>{{ salary.department_name }}</td>
                                    <td>{{ salary.designation_name }}</td>

                                    <td>{{ salary.effective_date }}</td>
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
                                        {{ salary.net_pay }}
                                    </td>
                                    <td class="text-center">
                                        {{ salary.bank_account_no ?? "Cash" }}
                                    </td>

                                    <td
                                        v-if="role_id == 1 || role_id == 2"
                                        class="text-center text-nowrap"
                                    >
                                        <button
                                            class="btn btn-sm btn-light border me-1"
                                            title="Edit"
                                            @click="
                                                editSalary(salary.employee_id)
                                            "
                                        >
                                            <i
                                                class="fa-regular fa-pen-to-square text-warning"
                                            ></i>
                                        </button>
                                        <!-- <button class="btn btn-sm btn-light border" title="Delete" @click="
                                            deletesalary(salary.employee_id)
                                            ">
                                            <i class="fa-solid fa-trash text-danger"></i>
                                        </button> -->
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
.header-design {
    display: inline-flex;
    align-items: center;
    letter-spacing: 1px;
    padding-top: 5px;
    color: #000000;
}

.department-component {
    width: 400px;
}

.search-field {
    height: 40px;
    font-size: 14px;
    letter-spacing: 2px;
    color: #111111;
    font-weight: 500;
    opacity: 0.8;
    transition: all 0.3s ease;
}

input.search-field::placeholder {
    color: #4e4e4e !important;
    letter-spacing: normal !important;
    opacity: 0.5 !important;
    font-size: small !important;
}

.active-status {
    width: 100px;
    height: 40px;
    font-size: 14px;
    letter-spacing: 2px;
    opacity: 0.8;
    font-weight: 500;
    border: 0.1px solid #d8d7d7;
    border-radius: 5px;
    text-align: center;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-color: #bbb8b8;
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

.salary-icon {
    font-size: 14px;
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

.salary-icon {
    font-size: 18px;
    line-height: 1;
    margin-right: 4px;
}

@media (max-width: 480px) {
    .department-component {
        width: 100%;
    }

    .active-status {
        width: 100%;
    }
}
</style>
