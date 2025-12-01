<script setup>
import { ref, defineProps, getCurrentInstance, watch, onMounted } from "vue";
import Swal from "sweetalert2";
import api from "@/api";

const { isOpen, editStore, updateinfo } = defineProps([
    "isOpen",
    "editStore",
    "updateinfo",
]);

const error = ref([]);
const department = ref([]);
const employee = ref([]);
const emp_img = ref(null);
const salaryAdjustment = ref([]);
const adjustments = ref([]);

const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());

const form = ref({
    department: "",
    eid: "",
    adjustment_type: "",
    year: selectedYear.value,
    month: selectedMonth.value,
    adjustmentCategory: "",
    amount: "",
    remarks: "",
});

const earningOptions = [
    { id: 1, key: "pending_salary", value: "Pending Salary" },
    { id: 2, key: "overtime", value: "Overtime" },
    { id: 3, key: "others", value: "Other" },
];
const deductionOptions = [
    { id: 1, key: "advance", value: "Advance" },
    { id: 2, key: "other_deductions", value: "Other Deductions" },
];

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

const instance = getCurrentInstance();

// Fetch departments & salary adjustments
const getData = async () => {
    try {
        const [departmentRes, adjustmentRes, adjustmentTypeRes] =
            await Promise.all([
                api.get("/department"),
                api.get("/salary-adjustment"),
                api.get("/salary-adjustment-types"),
            ]);
        department.value = departmentRes.data;
        salaryAdjustment.value = adjustmentRes.data;
        adjustments.value = adjustmentTypeRes.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

// Fetch employees for a department
const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        return;
    }
    try {
        const res = await api.get(`/emp/${deptID}`);
        employee.value = res.data;
    } catch (err) {
        console.error("Error fetching employees:", err);
    }
};

// Fetch employee image
const getEmployeeImg = async (eid) => {
    if (!eid) return;
    try {
        const res = await api.get(`/empimg/${eid}`);
        emp_img.value = res.data?.img_url || null;
    } catch (err) {
        console.error("Error fetching employee image:", err);
    }
};

// Watch for editStore changes (prefill form)
watch(
    () => editStore,
    async (item) => {
        if (item) {
            form.value.department = item.dept_id || "";
            await getEmployee(item.dept_id);
            form.value.eid = item.eid || "";
            await getEmployeeImg(item.eid);

            form.value.adjustment_type = item.adjustment_id || "";
            form.value.amount = item.amount || "";
            form.value.remarks = item.remarks || "";

            if (item.date) {
                const [year, month] = item.date.split("-");
                form.value.year = year;
                form.value.month = parseInt(month);
            }
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

// Reset form
const resetForm = () => {
    form.value = {
        department: "",
        eid: "",
        adjustment_type: "",
        year: selectedYear.value,
        month: selectedMonth.value,
        adjustmentCategory: "",
        amount: "",
        remarks: "",
    };
    emp_img.value = null;
};

// Close modal
const closeModal = () => {
    instance.emit("modal-close");
};

// Create salary adjustment
const create = async () => {
    try {
        const res = await api.post("/salary-adjustment", form.value);
        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: res.data.message,
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
            getData();
            closeModal();
        } else {
            Swal.fire({
                icon: "error",
                title: res.data.message,
                showConfirmButton: true,
            });
        }
    } catch (err) {
        console.error("Error creating adjustment:", err);
    }
};

// Update salary adjustment
const update = async () => {
    try {
        // Use eid + year + month as unique identifier if no id exists
        const recordIdentifier = `${form.value.eid}-${form.value.year}-${form.value.month}`;
        const res = await api.put(
            `/salary-adjustment/${recordIdentifier}`,
            form.value
        );
        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: "Salary Adjustment Updated",
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
            getData();
            closeModal();
        }
    } catch (err) {
        console.error("Error updating adjustment:", err);
    }
};

// Submit handler
const submit = () => {
    if (updateinfo === "Save") create();
    else if (updateinfo === "Update") update();
    else console.error("Invalid updateinfo:", updateinfo);
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
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header gradient text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-sliders me-2"></i>
                        {{
                            updateinfo === "Save"
                                ? "NEW ADJUSTMENT SETUP"
                                : "SALARY ADJUSTMENT UPDATE"
                        }}
                    </h5>
                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body overflow-auto" style="max-height: 80vh">
                    <!-- Employee Section -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            EMPLOYEE INFORMATION
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-9">
                                    <!-- Department -->
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-3 d-flex">
                                            <span class="flex-grow-1 me-2"
                                                >Department Name</span
                                            >
                                            <span> :</span>
                                        </div>
                                        <div class="col-md-6">
                                            <select
                                                class="form-select border-color custom-input"
                                                v-model="form.department"
                                                @change="
                                                    getEmployee(form.department)
                                                "
                                            >
                                                <option
                                                    value=""
                                                    selected
                                                    disabled
                                                >
                                                    Select Department
                                                </option>
                                                <option
                                                    v-for="dept in department"
                                                    :key="dept.id"
                                                    :value="dept.id"
                                                >
                                                    {{ dept.Name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Employee Name -->
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-3 d-flex">
                                            <span class="flex-grow-1 me-2"
                                                >Employee Name</span
                                            >
                                            <span>:</span>
                                        </div>
                                        <div class="col-md-6">
                                            <select
                                                class="form-select border-color custom-input"
                                                v-model="form.eid"
                                                @change="
                                                    getEmployeeInfo(form.eid)
                                                "
                                            >
                                                <option value="" selected>
                                                    Select Employee
                                                </option>
                                                <option
                                                    v-for="emp in employee"
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
                                        height="120"
                                        width="120"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Adjustment Section -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            ADJUSTMENT DETAILS
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Adjustment Type</label
                                    >
                                    <select
                                        class="form-select border-color custom-input"
                                        v-model="form.adjustment_type"
                                    >
                                        <option disabled value="">
                                            Select Type
                                        </option>
                                        <option
                                            v-for="adjustment in adjustments"
                                            :key="adjustment.adjustment_id"
                                            :value="adjustment.adjustment_id"
                                        >
                                            {{ adjustment.adjustment_name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Salary Year</label
                                    >
                                    <select
                                        class="form-select border-color custom-input"
                                        v-model="form.year"
                                    >
                                        <option disabled value="">
                                            Select Year
                                        </option>
                                        <option
                                            v-for="year in years"
                                            :key="year"
                                            :value="year"
                                        >
                                            {{ year }}
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Salary Month</label
                                    >
                                    <select
                                        class="form-select border-color custom-input"
                                        v-model="form.month"
                                    >
                                        <option disabled value="">
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
                                </div>
                            </div>
                            <div class="row g-3 mt-0">
                                <div class="col-md-4">
                                    <label class="form-label">Amount</label>
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.amount"
                                        placeholder="Amount"
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

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button class="btn btn-success" @click="submit">
                        <i class="bi bi-check-circle"></i> {{ updateinfo }}
                    </button>
                    <button class="btn btn-warning" @click="resetForm">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                    <button class="btn btn-secondary" @click="closeModal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
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

/* Text Transform */
.modal-content label,
.modal-content .card-header,
.modal-content option,
.modal-content select,
.modal-content input,
.modal-content textarea {
    text-transform: capitalize !important;
}
</style>
