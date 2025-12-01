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

console.log("Loan Setup Form - editStore:", editStore);
// Emits for parent communication
const emit = defineEmits(["modal-close", "refresh-list"]);

// Form state
const form = ref({
    department_id: "",
    employee_id: "",
    principal_amount: "",
    tenure_month: "",
    first_installment_date: "",
    installment_amount: "",
    remarks: "",
});

// Department & Employee data
const departmentInfo = ref([]);
const employeeByDept = ref([]);
const emp_img = ref(null);

// -------------------------
// 🔹 Fetch departments
// -------------------------
const getData = async () => {
    try {
        const res = await api.get("department");
        departmentInfo.value = res.data;
    } catch (error) {
        console.error("Error fetching departments:", error);
    }
};

// -------------------------
// 🔹 Fetch employees by department
// -------------------------
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

// -------------------------
// 🔹 Auto-calculate installment
// -------------------------
watch(
    () => [form.value.principal_amount, form.value.tenure_month],
    ([newPrincipal, newTenure]) => {
        if (newPrincipal && newTenure) {
            form.value.installment_amount = (newPrincipal / newTenure).toFixed(
                2
            );
        } else {
            form.value.installment_amount = "";
        }
    }
);

// -------------------------
// 🔹 Create Loan
// -------------------------
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
        console.error("Error creating loan:", err);
    }
};

// -------------------------
// 🔹 Update Loan
// -------------------------
const update = async () => {
    try {
        const response = await api.put(
            `/employee-loan/${editStore.loanID}`,
            form.value
        );
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
        console.error("Error updating loan:", err);
    }
};

// -------------------------
// 🔹 Reset Form
// -------------------------
const resetForm = () => {
    form.value = {
        department_id: "",
        employee_id: "",
        principal_amount: "",
        tenure_month: "",
        first_installment_date: "",
        installment_amount: "",
        remarks: "",
    };
    emp_img.value = null;
};

// -------------------------
// 🔹 Close Modal
// -------------------------
const closeModal = () => {
    emit("modal-close");
};

// -------------------------
// 🔹 Prefill form on edit
// -------------------------
watch(
    () => editStore,
    async (item) => {
        if (item) {
            form.value.department_id = item.deptID || "";
            await getEmployee(form.value.department_id);
            form.value.employee_id = item.eid || "";
            emp_img.value =
                employeeByDept.value.find(
                    (emp) => emp.id === form.value.employee_id
                ) || null;
            form.value.principal_amount = item.principal_amounts || "";
            form.value.tenure_month = item.tenure_months || "";
            form.value.first_installment_date = item.installment_dates || "";
            form.value.installment_amount = item.installment_amount || "";
            form.value.remarks = item.remarks || "";
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

// -------------------------
// 🔹 Submit handler
// -------------------------
const submit = () => {
    if (updateinfo === "Save") {
        create();
    } else if (updateinfo === "Update") {
        update();
    } else {
        console.error("Invalid updateinfo value:", updateinfo);
    }
};

// -------------------------
// 🔹 On Mounted
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
                        <i class="bi bi-cash-coin me-2"></i>
                        {{
                            updateinfo === "Save"
                                ? "NEW LOAN SETUP"
                                : "UPDATE LOAN SETUP"
                        }}
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
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            EMPLOYEE INFORMATION
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-9">
                                    <!-- Department -->
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-md-3 d-flex">
                                            <span class="flex-grow-1 me-2"
                                                >Department Name</span
                                            >
                                            <span>:</span>
                                        </div>
                                        <div class="col-md-6">
                                            <select
                                                class="form-select border-color custom-input"
                                                v-model="form.department_id"
                                                @change="
                                                    getEmployee(
                                                        form.department_id
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
                                        <div class="col-md-3 d-flex">
                                            <span class="flex-grow-1 me-2"
                                                >Employee Name</span
                                            >
                                            <span>:</span>
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

                    <!-- Loan Details -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            LOAN DETAILS
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"
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
                                    <label class="form-label"
                                        >Tenure (Months)</label
                                    >
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.tenure_month"
                                        placeholder="Tenure in Months"
                                    />
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label"
                                        >First Installment Date</label
                                    >
                                    <input
                                        type="date"
                                        class="form-control border-color custom-input"
                                        v-model="form.first_installment_date"
                                    />
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Installment Amount (monthly)</label
                                    >
                                    <input
                                        type="number"
                                        step="any"
                                        class="form-control border-color custom-input"
                                        v-model="form.installment_amount"
                                        placeholder="Installment Amount"
                                    />
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Remarks</label>
                                    <input
                                        type="text"
                                        class="form-control border-color custom-input"
                                        v-model="form.remarks"
                                        placeholder="Remarks"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button
                        type="submit"
                        class="btn btn-success"
                        @click="submit"
                    >
                        <i class="bi bi-check-circle"></i> {{ updateinfo }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-warning"
                        @click="resetForm"
                    >
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                    <button
                        type="button"
                        class="btn btn-secondary"
                        @click="closeModal"
                    >
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.gradient {
    background: linear-gradient(
        90deg,
        rgb(28, 105, 9) 27%,
        rgb(14, 119, 58) 100%,
        rgb(7, 134, 24) 91%
    );
}

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
