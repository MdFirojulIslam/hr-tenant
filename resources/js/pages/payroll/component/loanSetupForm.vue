<script setup>
import { ref, defineProps, watch, onMounted } from "vue";
import Swal from "sweetalert2";
import api from "@/api";

// Props from parent
const { isOpen, editStore, updateinfo, roleId } = defineProps([
    "isOpen",
    "editStore",
    "updateinfo",
    "roleId",
]);

// Emits for parent communication
const emit = defineEmits(["modal-close", "refresh-list"]);

// Form state
const form = ref({
    department: "",
    employee: "",
    principal: "",
    tenure: "",
    first_due_on: "",
    remarks: "",
});

const departmentInfo = ref([]);
const employeeByDept = ref([]);
const emp_img = ref(null);

// 🔹 Fetch departments
const getData = async () => {
    try {
        const res = await api.get("department");
        departmentInfo.value = res.data;
    } catch (error) {
        console.error("Error fetching departments:", error);
    }
};

// 🔹 Fetch employees by department
const getEmployee = async (deptID) => {
    if (!deptID) {
        employeeByDept.value = [];
        return;
    }
    try {
        const res = await api.get(`/emp/${deptID}`);
        employeeByDept.value = res.data;
    } catch (error) {
        console.error("Error fetching employees:", error);
    }
};

// 🔹 Create
const create = async () => {
    try {
        const response = await api.post(`/employee-loan`, form.value);
        if (response.data.success) {
            Swal.fire({
                icon: "success",
                title: response.data.message,
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
            emit("refresh-list");
            closeModal();
        } else {
            Swal.fire({
                icon: "error",
                title: response.data.message,
                showConfirmButton: true,
            });
        }
    } catch (err) {
        console.error("Error creating adjustment:", err);
    }
};

// 🔹 Update
const update = async () => {
    try {

        const response = await api.put(`/employee-loan/${editStore.loanID}`, form.value);
        if (response.data.success) {
            Swal.fire({
                icon: "success",
                title: response.data.message,
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
            emit("refresh-list");
            closeModal();
        } else {
            Swal.fire({
                icon: "error",
                title: response.data.message,
                showConfirmButton: true,
            });
        }
    } catch (err) {
        console.error("Error creating adjustment:", err);
    }
};

// 🔹 Reset form
const resetForm = () => {
    form.value = {
        department: "",
        employee: "",
        principal: "",
        tenure: "",
        first_due_on: "",
        remarks: "",
    };
    emp_img.value = null;
};

// 🔹 Close modal
const closeModal = () => {
    emit("modal-close");
};

// 🔹 Watch for editStore (prefill form on edit)
watch(
    () => editStore,
    async (item) => {
        if (item) {
            form.value.department = item.deptId || "";
            await getEmployee(form.value.department);
            form.value.employee = item.id || "";
            emp_img.value =
                employeeByDept.value.find((emp) => emp.id === form.value.employee) || null;
            form.value.principal = item.principal || "";
            form.value.tenure = item.tenure || "";
            form.value.first_due_on = item.first_due_on || "";
            form.value.remarks = item.remarks || "";
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

const submit = () => {
    if (updateinfo === "Save") {
        create();
    } else if (updateinfo === "Update") {
        update();
    } else {
        console.error("Invalid updateinfo value:", updateinfo);
    }
};

onMounted(() => getData());
</script>

<template>
    <div v-if="isOpen" class="modal-mask">
        <div class="card modal-card">
            <div class="card-body">
                <div class="card mb-2">
                    <div v-if="updateinfo === 'Save'" class="card-header mb-1 bg-grey align-items-center">
                        <h5 class="mb-0">New Loan Setup</h5>
                    </div>
                    <div v-else-if="updateinfo === 'Update'" class="card-header mb-1 bg-grey align-items-center">
                        <h5 class="mb-0">Update Loan Setup</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-9">
                                <!-- Department -->
                                <div class="row mb-3">
                                    <div class="col-lg-3">Department Name</div>
                                    <div class="col-lg-1">:</div>
                                    <div class="col-lg-8">
                                        <select class="form-control" v-model="form.department"
                                            @change="getEmployee(form.department)">
                                            <option value="">Select Department</option>
                                            <option v-for="dept in departmentInfo" :key="dept.id" :value="dept.id">
                                                {{ dept.Name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Employee -->
                                <div class="row mb-3">
                                    <div class="col-lg-3">Employee Name</div>
                                    <div class="col-lg-1">:</div>
                                    <div class="col-lg-8">
                                        <select class="form-control" v-model="form.employee">
                                            <option value="">Select Employee</option>
                                            <option v-for="emp in employeeByDept" :key="emp.id" :value="emp.id">
                                                {{ emp.Full_Name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <img :src="emp_img?.img_url || ''" alt="" height="110px" width="110px" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit">
                    <div class="row mt-3">
                        <div class="col-lg-6 mt-3">
                            <div class="form-group">
                                <label>Principal Amount</label>
                                <input type="number" class="form-control" v-model="form.principal" />
                            </div>
                        </div>
                        <div class="col-lg-6 mt-3">
                            <div class="form-group">
                                <label>Tenure (Months)</label>
                                <input type="number" class="form-control" v-model="form.tenure" />
                            </div>
                        </div>
                        <div class="col-lg-6 mt-3">
                            <div class="form-group">
                                <label>First Due On (Date)</label>
                                <input type="date" class="form-control" v-model="form.first_due_on" />
                            </div>
                        </div>
                        <div class="col-lg-6 mt-3">
                            <div class="form-group">
                                <label>Remarks</label>
                                <input type="text" class="form-control" v-model="form.remarks" />
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" class="custom-btn btn-13 p-2">
                            <i class="fa-solid fa-check"></i> | {{ updateinfo }}
                        </button>
                        <button type="button" class="custom-btn btn-15 p-2" @click.prevent="resetForm">
                            <i class="fa-solid fa-power-off"></i> | Reset
                        </button>
                        <button type="button" class="custom-btn btn-12 p-2" @click.stop="closeModal">
                            <i class="fa-solid fa-x"></i> | Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style>
.salary-table td input,
.salary-table td select {
    margin: 7px;
}

#BrandModal,
.modal-mask {
    position: fixed;
    z-index: 2000;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-container {
    padding: 20px 30px;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    background-color: blue;
}

.swal2-container {
    z-index: 2001 !important;
}

.swal-popup-zindex {
    z-index: 2001 !important;
}
</style>
