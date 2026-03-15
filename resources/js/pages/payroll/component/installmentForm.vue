<script setup>
import { ref, watch, onMounted } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

// Props and emits
const { isOpen, editStore, updateinfo, roleId } = defineProps([
    "isOpen",
    "editStore",
    "updateinfo",
    "roleId",
]);

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
    repayment_id: "",
});

// Other states
const emp_img = ref(null);
const error = ref("");
const departmentInfo = ref([]);
const employeeByDept = ref([]);

// -------------------------
// FETCH DEPARTMENTS
// -------------------------
const getData = async () => {
    try {
        const res = await api.get("/department");
        departmentInfo.value = res.data;
    } catch (err) {
        console.error("Error fetching departments:", err);
    }
};

// -------------------------
// FETCH EMPLOYEES BY DEPARTMENT
// -------------------------
const getEmployee = async (deptID) => {
    if (!deptID) {
        employeeByDept.value = [];
        form.value.employee_id = "";
        emp_img.value = null;
        return;
    }
    try {
        const res = await api.get(`/emp/${deptID}`);
        employeeByDept.value = res.data;
        form.value.employee_id = "";
        emp_img.value = null;
    } catch (err) {
        console.error("Error fetching employees:", err);
    }
};

// -------------------------
// FETCH EMPLOYEE IMAGE
// -------------------------
const getEmployeeImg = async (empID) => {
    if (!empID) {
        emp_img.value = null;
        return;
    }
    try {
        const res = await api.get(`/empimg/${empID}`);
        emp_img.value = res.data?.img_url || null;
    } catch (err) {
        console.error("Error fetching employee image:", err);
        emp_img.value = null;
    }
};

// -------------------------
// WATCH EMPLOYEE CHANGE
// -------------------------
watch(
    () => form.value.employee_id,
    (newID) => {
        getEmployeeImg(newID);
    },
);

// -------------------------
// PREFILL FORM ON EDIT
// -------------------------
watch(
    () => editStore,
    (newVal) => {
        if (!newVal) return;

        form.value.department_id = newVal.department_id || "";
        form.value.employee_id = newVal.employee_id || "";
        form.value.principal_amount = newVal.principal_amounts || "";
        form.value.tenure_months = newVal.tenure_months || "";
        form.value.installment_amount = newVal.installment_amounts || "";
        form.value.total_installment = newVal.total_installments || "";
        form.value.installment_date = newVal.paid_on_dates || "";
        form.value.installment_status = "Pending";
        form.value.repayment_id = newVal.loan_repayment_id || "";

        // Load employees and image for this department
        getEmployee(newVal.department_id);
        getEmployeeImg(newVal.employee_id);
    },
    { immediate: true },
);

// -------------------------
// RESET FORM
// -------------------------
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
        repayment_id: "",
    };
    emp_img.value = null;
    error.value = "";
};

// -------------------------
// CLOSE MODAL
// -------------------------
const closeModal = () => {
    const dialog = document.querySelector(".modal-dialog");
    if (dialog) {
        dialog.style.position = "";
        dialog.style.left = "";
        dialog.style.top = "";
        dialog.style.margin = "";
        dialog.style.transform = "";
        dialog.style.width = "";
    }
    emit("modal-close");
};

// -------------------------
// SAVE REPAYMENT
// -------------------------
const saveRepayment = async () => {
    try {
        const response = await api.put(
            `/loan-repayment/${form.value.repayment_id}`,
            form.value,
        );

        if (response.data.success) {
            Swal.fire({
                icon: "success",
                title: response.data.message,
                timer: 1500,
                showConfirmButton: false,
            });

            emit("refresh-list", {
                repayment_id: form.value.repayment_id,
                installment_amounts: form.value.installment_amount,
                paid_on_dates: form.value.installment_date,
            });

            emit("modal-close");
        }
    } catch (err) {
        error.value = err.response?.data?.message || err.message;
    }
};

// -------------------------
// ENABLE DRAG MODAL
// -------------------------
const enableDrag = () => {
    const dialog = document.querySelector(".modal-dialog");
    const header = document.querySelector(".modal-header");
    if (!dialog || !header) return;

    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;
    let originalWidth = 0;

    header.style.cursor = "move";

    header.addEventListener("mousedown", (e) => {
        isDragging = true;
        originalWidth = dialog.offsetWidth;
        dialog.style.width = originalWidth + "px";

        const rect = dialog.getBoundingClientRect();
        dialog.style.position = "absolute";
        dialog.style.left = rect.left + "px";
        dialog.style.top = rect.top + "px";
        dialog.style.margin = 0;
        dialog.style.transform = "none";

        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;
    });

    document.addEventListener("mousemove", (e) => {
        if (!isDragging) return;
        dialog.style.left = e.clientX - offsetX + "px";
        dialog.style.top = e.clientY - offsetY + "px";
    });

    document.addEventListener("mouseup", () => {
        isDragging = false;
    });
};

// -------------------------
// ON MOUNT
// -------------------------
onMounted(() => {
    getData();
    enableDrag();
});
</script>

<template>
    <div v-if="isOpen" class="modal fade show d-block" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <!-- Header -->
                <div class="modal-header gradient text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-coin me-2"></i> Loan Repayment
                        Setup
                    </h5>
                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Body -->
                <div class="modal-body overflow-auto" style="max-height: 80vh">
                    <!-- Employee Information -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            EMPLOYEE INFORMATION
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-9">
                                    <!-- Department -->
                                    <div class="row mb-3 align-items-center">
                                        <div
                                            class="col-md-3 d-flex align-items-center"
                                        >
                                            Department Name:
                                        </div>
                                        <div class="col-md-6">
                                            <select
                                                class="form-select border-color custom-input"
                                                v-model="form.department_id"
                                                @change="
                                                    getEmployee(
                                                        form.department_id,
                                                    )
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
                                    <div class="row mb-3 align-items-center">
                                        <div
                                            class="col-md-3 d-flex align-items-center"
                                        >
                                            Employee Name:
                                        </div>
                                        <div class="col-md-6">
                                            <select
                                                class="form-select border-color custom-input"
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
                                </div>

                                <!-- Employee Image -->
                                <div class="col-md-3 text-center">
                                    <img
                                        :src="emp_img || ''"
                                        alt="Employee"
                                        class="rounded border"
                                        height="110"
                                        width="110"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Installment Form -->
                    <form @submit.prevent="saveRepayment">
                        <div class="card mb-3 shadow-sm">
                            <div class="card-header bg-light fw-bold">
                                INSTALLMENT INSERT INFO
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">
                                            Installment Date*
                                        </label>
                                        <input
                                            type="date"
                                            class="form-control border-color custom-input"
                                            v-model="form.installment_date"
                                        />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">
                                            Installment Amount*
                                        </label>
                                        <input
                                            type="number"
                                            class="form-control border-color custom-input"
                                            v-model="form.installment_amount"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Buttons -->
                        <div class="d-flex justify-content-end mt-4 gap-2">
                            <button
                                type="submit"
                                class="btn btn-success px-4 py-2"
                            >
                                <i class="bi bi-check-circle me-1"></i>
                                {{ updateinfo }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-warning px-4 py-2"
                                @click.prevent="resetForm"
                            >
                                <i class="bi bi-arrow-clockwise me-1"></i> Reset
                            </button>
                            <button
                                type="button"
                                class="btn btn-secondary px-4 py-2"
                                @click.stop="closeModal"
                            >
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </button>
                        </div>

                        <p v-if="error" class="text-danger mt-3 text-center">
                            {{ error }}
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-input {
    background: linear-gradient(
        to right,
        white 85%,
        #059223d5 85%,
        #02861fd5 100%
    );
}

.border-color {
    border: 1.8px solid #059223d5;
}

.gradient {
    background: linear-gradient(
        90deg,
        rgb(28, 105, 9) 27%,
        rgb(14, 119, 58) 100%,
        rgb(7, 134, 24) 91%
    );
}

.modal {
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content label,
.modal-content .card-header,
.modal-content option,
.modal-content select,
.modal-content input,
.modal-content textarea {
    text-transform: capitalize !important;
}
</style>
