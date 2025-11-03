<template>
    <div v-if="isOpen" class="modal-mask">
        <div class="card modal-card">
            <div class="card-body">
                <div class="card mb-2">
                    <div class="card-header mb-1 bg-grey">
                        <h5 class="mb-0">Loan Repayment Setup</h5>
                    </div>

                    <div class="card-body">
                        <!-- Employee Info -->
                        <div class="row mb-3">
                            <div class="col-lg-9">
                                <!-- Department -->
                                <div class="row mb-3">
                                    <div class="col-lg-3">Department Name</div>
                                    <div class="col-lg-1">:</div>
                                    <div class="col-lg-8">
                                        <select
                                            class="form-control"
                                            v-model="form.department_id"
                                            @change="
                                                getEmployee(form.department_id)
                                            "
                                        >
                                            <option value="">
                                                Select Department
                                            </option>
                                            <option
                                                v-for="dept in departmentInfo"
                                                :key="dept.id"
                                                :value="dept.id"
                                            >
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
                                        <select
                                            class="form-control"
                                            v-model="form.employee_id"
                                        >
                                            <option value="">
                                                Select Employee
                                            </option>
                                            <option
                                                v-for="emp in employeeByDept"
                                                :key="emp.id"
                                                :value="emp.id"
                                            >
                                                {{ emp.Full_Name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Principal Amount / Tenure / Total Installment -->
                                <div class="row mb-3">
                                    <div class="col-lg-3">Principal Amount</div>
                                    <div class="col-lg-1">:</div>
                                    <div class="col-lg-3">
                                        <input
                                            class="form-control"
                                            type="number"
                                            v-model="form.principal_amount"
                                            readonly
                                        />
                                    </div>
                                    <div class="col-lg-3">Tenure (Months)</div>
                                    <div class="col-lg-2">
                                        <input
                                            class="form-control"
                                            type="number"
                                            v-model="form.tenure_months"
                                            readonly
                                        />
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-lg-3">
                                        Total Installment Paid
                                    </div>
                                    <div class="col-lg-1">:</div>
                                    <div class="col-lg-8">
                                        <input
                                            class="form-control"
                                            type="number"
                                            v-model="form.total_installment"
                                            readonly
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Employee Image -->
                            <div class="col-lg-3">
                                <img
                                    :src="emp_img?.img_url || ''"
                                    alt="Employee Image"
                                    height="110"
                                    width="110"
                                />
                            </div>
                        </div>

                        <!-- Installments -->
                        <form @submit.prevent="saveRepayment">
                            <div class="row mt-3">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Installment Date</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="form.installment_date"
                                        />
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Installment Amount</label>
                                        <input
                                            type="number"
                                            class="form-control"
                                            v-model="form.installment_amount"
                                            readonly
                                        />
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="installment_status"
                                            >Installment Status</label
                                        >
                                        <select
                                            id="installment_status"
                                            class="form-control"
                                            v-model="form.installment_status"
                                        >
                                            <option value="Pending">
                                                Pending
                                            </option>
                                            <option value="Paid">Paid</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="d-flex justify-content-between mt-3">
                                <button
                                    type="submit"
                                    class="custom-btn btn-13 p-2"
                                >
                                    <i class="fa-solid fa-check"></i> |
                                    {{ updateinfo }}
                                </button>
                                <button
                                    type="button"
                                    class="custom-btn btn-15 p-2"
                                    @click.prevent="resetForm"
                                >
                                    <i class="fa-solid fa-power-off"></i> |
                                    Reset
                                </button>
                                <button
                                    type="button"
                                    class="custom-btn btn-12 p-2"
                                    @click.stop="closeModal"
                                >
                                    <i class="fa-solid fa-x"></i> | Cancel
                                </button>
                            </div>

                            <p v-if="error" class="text-danger mt-2">
                                {{ error }}
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

// Props and emits
const props = defineProps({
    isOpen: Boolean,
    editStore: Object,
    roleId: Number,
    updateinfo: String,
});
const emit = defineEmits(["modal-close", "refresh-list"]);

// Form state
const form = ref({
    department_id: "",
    employee_id: "",
    principal_amount: "",
    installment_date: "",
    installment_amount: "",
    tenure_months: "",
    total_installment: "",
    installment_status: "Pending",
});

// Other states
const emp_img = ref(null);
const error = ref("");
const loading = ref(false);
const departmentInfo = ref([]);
const employeeByDept = ref([]);
const loanAmount = ref(0);

// Fetch department data
const getData = async () => {
    try {
        const res = await api.get("/department");
        departmentInfo.value = res.data;
    } catch (err) {
        console.error("Error fetching departments:", err);
    }
};

// Fetch employees based on department
const getEmployee = async (deptID) => {
    if (!deptID) {
        employeeByDept.value = [];
        return;
    }
    try {
        const res = await api.get(`/emp/${deptID}`);
        employeeByDept.value = res.data;
    } catch (err) {
        console.error("Error fetching employees:", err);
    }
};

// Fetch loan data for employee
const getLoanAmount = async (empID) => {
    if (!empID) {
        form.value.principal_amount = "";
        form.value.tenure_months = "";
        form.value.total_installment = "";
        form.value.installment_amount = "";
        return;
    }

    try {
        const res = await api.get(`/get-loan-amount/${empID}`);
        const data = res.data;

        if (data.success) {
            form.value.principal_amount = data.principal_amount;
            form.value.tenure_months = data.tenure_months;
            form.value.total_installment = data.total_installments;

            const remaining = data.principal_amount - data.total_installments;
            form.value.installment_amount = data.tenure_months
                ? (data.principal_amount / data.tenure_months).toFixed(2)
                : 0;
        }
    } catch (err) {
        console.error("Error fetching loan amount:", err);
        form.value.principal_amount = "";
        form.value.tenure_months = "";
        form.value.total_installment = "";
        form.value.installment_amount = "";
    }
};

// Watch employee selection
watch(
    () => form.value.employee_id,
    (newEmpID) => {
        getLoanAmount(newEmpID);
    }
);

// Watch editStore for edit mode
watch(
    () => props.editStore,
    (newVal) => {
        if (newVal) {
            form.value.department_id = newVal.department_id || "";
            form.value.employee_id = newVal.employee_id || "";
            form.value.principal_amount = newVal.principal_amount || "";
            form.value.installment_date = newVal.installment_date || "";
            form.value.installment_amount = newVal.installment_amount || "";
            form.value.tenure_months = newVal.tenure_months || "";
            form.value.total_installment = newVal.total_installment || "";
            form.value.installment_status =
                newVal.installment_status || "Pending";
            emp_img.value = newVal.emp_img || null;
        }
    },
    { immediate: true }
);

// Reset form
const resetForm = () => {
    form.value = {
        department_id: "",
        employee_id: "",
        principal_amount: "",
        installment_date: "",
        installment_amount: "",
        tenure_months: "",
        total_installment: "",
        installment_status: "Pending",
    };
    emp_img.value = null;
    error.value = "";
};

// Close modal
const closeModal = () => emit("modal-close");

// Save repayment
const saveRepayment = async () => {
    if (!form.value.installment_date || !form.value.installment_amount) {
        error.value = "Installment date and amount are required.";
        return;
    }

    try {
        // const response = await api.post(`/employee-loan`, form.value);
        const response = await api.post("/loan-repayment", form.value);

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
        closeModal();
    } catch (err) {
        error.value = err.response?.data?.message || err.message;
    }
};

// Mounted
onMounted(() => getData());
</script>

<style scoped>
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
.modal-card {
    width: 90%;
    max-width: 900px;
    padding: 20px;
}
</style>
