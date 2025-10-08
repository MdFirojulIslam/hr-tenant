<script setup>
import { ref, onMounted, defineAsyncComponent } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import api from "@/api";
import Swal from "sweetalert2";
import { useRouter } from "vue-router";
const router = useRouter();

import { useStore } from "vuex";

const store = useStore();
const role_id = store.getters.getRoleId;

const salaryComponent = defineAsyncComponent(() =>
    import("./component/salarySetupForm.vue")
);

const currentYear = ref(new Date().getFullYear());



const isLoading = ref(true);
const error = ref(null);
const selectedStore = ref(null);
const heading = ref(null);
const filter = ref("");

const salaries = ref([]);

const salaryId = ref();
const salaryModel = ref(false);

const salaryOpen = (title) => {
    heading.value = title;
    salaryModel.value = true;
};
const salaryClose = () => {
    salaryModel.value = false;
    getData();
};



const openSalaryDetails = (id) => {
    window.open(`/payroll/salary-details/${id}`, '_blank');
};


const employeeName = ref('');
const employeeID = ref('');
const bank_account_no = ref('');

const searchemp = async () => {
    // Check if any filter input has a value
    if (employeeName.value || employeeID.value || bank_account_no.value) {
        try {
            const response = await api.get("/employee-salary-search", {
                params: {
                    employeeName: employeeName.value,
                    employeeID: employeeID.value,
                    bank_account_no: bank_account_no.value,
                },
            });
            salaries.value = response.data;
        } catch (error) {
            console.error(error);
        }
    } else {
        getData(); // fetch all if no filter
    }
};




const getData = async (page = 1) => {
    try {
        const response = await api.get(`/salary?page=${page}`);
        salaries.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

const editsalary = async (id) => {
    try {
        selectedStore.value = salaries.value.data.find(
            (item) => item.eid === id
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
        console.log(response.data.success);
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
    <div class="container-fluid h-100 d-flex flex-column p-0 m-0">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center py-2 px-3">
            <h2 class="m-0">Salary Setup List</h2>
            <div v-if="role_id == 1 || role_id == 2">
                <button @click="salaryOpen('Save')" type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm">
                    <i class="fa-solid fa-plus me-2"></i> New Setup
                </button>
            </div>
        </div>

        <!-- Salary Modal -->
        <component v-if="salaryModel" :is="salaryComponent" :isOpen="salaryModel" :editStore="selectedStore"
            :updateinfo="heading" :salId="salaryId" @modal-close="salaryClose" @submit="submitHandler"
            name="first-modal" />

        <!-- Table Card -->
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <!-- Filters -->
                <div class="row g-3 mb-3">
                    <div class="col-lg-3">
                        <input type="text" class="form-control form-control-sm" v-model="employeeName"
                            placeholder="Type Employee Name" @input="searchemp" />
                    </div>
                    <div class="col-lg-3">
                        <input type="text" class="form-control form-control-sm" v-model="employeeID"
                            placeholder="Type Employee ID" @input="searchemp" />
                    </div>
                    <div class="col-lg-3">
                        <input type="text" class="form-control form-control-sm" v-model="bank_account_no"
                            placeholder="Type Bank Account No" @input="searchemp" />
                    </div>
                </div>

                <!-- Salary Table -->
                <div class="border rounded mt-5">
                    <!-- Total Employees Row -->
                    <div class="d-flex align-items-center px-2 py-2 fw-semibold border-bottom bg-light">
                        <span class="me-2">Total Employees:</span>
                        <span class="bg-white border rounded px-3 py-1 fw-medium">{{ salaries.total }}</span>
                    </div>
                    <div class="table-responsive px-2 bg-light">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th width="4%">S/N</th>
                                    <th width="10%">EMPLOYEE ID</th>
                                    <th width="20%">EMPLOYEE NAME</th>
                                    <th width="8%">BASIC</th>
                                    <th width="10%">TOTAL PAYMENT</th>
                                    <th width="10%">TOTAL EARNING</th>
                                    <th width="10%">TOTAL DEDUCTION</th>
                                    <th width="8%">NET PAY</th>
                                    <th width="10%">BANK ACCOUNT</th>
                                    <th width="10%" class="text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>

                                <tr v-for="(salary, index) in salaries.data" :key="salary.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>ABNB-{{ salary.Employee_Id }}</td>
                                    <td>{{ salary.Full_Name }}</td>
                                    <td>{{ salary.basic }}</td>
                                    <td>{{ salary.total_payment }}</td>
                                    <td>
                                        {{ salary.earning_amount == 0 ? "-" : salary.earning_amount }}
                                    </td>
                                    <td>
                                        {{ salary.deduction_amount == 0 ? "-" : salary.deduction_amount }}
                                    </td>
                                    <td>{{ salary.net_pay }}</td>
                                    <td>{{ salary.bank_account_no }}</td>

                                    <td v-if="role_id == 1 || role_id == 2" class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-light border me-1" title="View"
                                            @click="openSalaryDetails(salary.eid)">
                                            <i class="fa-regular fa-user text-primary"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border me-1" title="Edit"
                                            @click="editsalary(salary.eid)">
                                            <i class="fa-regular fa-pen-to-square text-warning"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border" title="Delete"
                                            @click="deletesalary(salary.eid)">
                                            <i class="fa-solid fa-trash text-danger"></i>
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
                    <Bootstrap4Pagination :data="salaries" :limit="2" @pagination-change-page="getData" />
                </div>
            </div>
        </div>
    </div>
</template>
