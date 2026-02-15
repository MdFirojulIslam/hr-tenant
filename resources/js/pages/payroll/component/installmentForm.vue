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
        // console.log("Loan Amount Response:", res.data);
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
    },
);

// Watch editStore for edit mode
watch(
    () => props.editStore,
    async (newVal) => {
        if (newVal && newVal.installment && newVal.item) {
            const loan = newVal.item;

            form.value.department_id = loan.deptID || "";

            // MUST BE LOADED BEFORE SETTING employee_id
            await getEmployee(form.value.department_id);

            form.value.employee_id = loan.eid || "";
            form.value.principal_amount = loan.principal_amounts || "";
            form.value.tenure_months = loan.tenure_months || "";
            form.value.total_installment = loan.monthly_installments || "";

            const ins = newVal.installment;

            form.value.installment_date = ins.installment_dates || "";
            form.value.installment_amount = ins.installment_amounts || "";
            form.value.installment_status =
                ins.paid_status?.toLowerCase() || "pending";

            emp_img.value = loan.emp_img || null;
        }
    },
    { immediate: true },
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
const closeModal = () => {
    const dialog = document.querySelector(".modal-dialog");
    if (dialog) {
        dialog.style.position = "";
        dialog.style.left = "";
        dialog.style.top = "";
        dialog.style.margin = "";
        dialog.style.transform = "";
        dialog.style.width = ""; // reset width
    }
    emit("modal-close");
};

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

        // lock width
        originalWidth = dialog.offsetWidth;
        dialog.style.width = originalWidth + "px";

        const rect = dialog.getBoundingClientRect();

        // SAVE CURRENT POSITION BEFORE REMOVING transform
        const currentLeft = rect.left;
        const currentTop = rect.top;

        // remove bootstrap centering
        dialog.style.transform = "none";
        dialog.style.margin = 0;
        dialog.style.position = "absolute";

        // SET EXPLICIT LEFT + TOP so modal does NOT jump
        dialog.style.left = currentLeft + "px";
        dialog.style.top = currentTop + "px";

        // mouse offsets
        offsetX = e.clientX - currentLeft;
        offsetY = e.clientY - currentTop;
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

// Mounted
onMounted(() => {
    getData();
    enableDrag();
});
</script>

<template>
    <div
        v-if="isOpen"
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <!-- Header -->
                <div class="modal-header gradient text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-coin me-2"></i>Loan Repayment Setup
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
                                            <span class="flex-grow-1 me-2"
                                                >Department Name</span
                                            ><span>:</span>
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
                                            <span class="flex-grow-1 me-2"
                                                >Employee Name</span
                                            ><span>:</span>
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

                                <div class="col-md-3 text-center">
                                    <img
                                        :src="emp_img?.img_url || ''"
                                        alt="Employee"
                                        class="rounded border"
                                        height="110"
                                        width="110"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            LOAN DETAILS
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label mb-1"
                                        >Principal Amount</label
                                    >
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.principal_amount"
                                        placeholder="Principal Amount"
                                    />
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label mb-1"
                                        >Tenure (Months)</label
                                    >
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.tenure_months"
                                        placeholder="Tenure in Months"
                                    />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1"
                                        >Total Installment Paid</label
                                    >
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.total_installment"
                                        readonly
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Principal / Tenure -->

                    <!-- Total Installment -->

                    <!-- Employee Image -->

                    <!-- Installment Form -->
                    <form @submit.prevent="saveRepayment">
                        <div class="card mb-3 shadow-sm">
                            <div class="card-header bg-light fw-bold">
                                INSTALLMENT INSERT INFO
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label mb-1"
                                            >Installment Date*</label
                                        >
                                        <input
                                            type="date"
                                            class="form-control border-color custom-input"
                                            v-model="form.installment_date"
                                        />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1"
                                            >Installment Amount*</label
                                        >
                                        <input
                                            type="number"
                                            class="form-control border-color custom-input"
                                            v-model="form.installment_amount"
                                            readonly
                                        />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1"
                                            >Installment Status*</label
                                        >
                                        <select
                                            class="form-select border-color custom-input"
                                            v-model="form.installment_status"
                                        >
                                            <option value="pending">
                                                Pending
                                            </option>
                                            <option value="paid">Paid</option>
                                        </select>
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
    border-radius: none;
}
.gradient {
    background: #349c1a;
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

/* ✅ Make all field names show with First Letter of Every Word Capitalized */
.modal-content label,
.modal-content .card-header,
.modal-content option,
.modal-content select,
.modal-content input,
.modal-content textarea {
    text-transform: capitalize !important;
}
</style>
