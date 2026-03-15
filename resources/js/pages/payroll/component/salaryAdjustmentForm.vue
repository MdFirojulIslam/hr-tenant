<script setup>
import {
    ref,
    reactive,
    defineProps,
    getCurrentInstance,
    watch,
    onMounted,
} from "vue";
import Swal from "sweetalert2";
import api from "@/api";
import { useStore } from "vuex";
import { useRouter } from "vue-router";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

// ----------------------------
// BASIC SETUP
// ----------------------------
const store = useStore();
const user = store.getters.getUser;

// Props
const { isOpen, editStore, updateinfo } = defineProps([
    "isOpen",
    "editStore",
    "updateinfo",
]);

// Refs and reactive
const error = ref([]);
const department = ref([]);
const employee = ref([]);
const emp_img = ref(null);
const salaryAdjustment = ref([]);
const earning_adjustments = ref([]);
const deduction_adjustments = ref([]);

const selectedEmployee = ref(null);

const onEmployeeSelect = (emp) => {
    if (!emp) {
        form.eid = "";
        emp_img.value = null;
        return;
    }

    form.eid = emp.id;

    getEmployeeImg(emp.id);
};

// Current year and month
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());

const today = new Date();
const previousMonth = today.getMonth() === 0 ? 12 : today.getMonth(); // Jan হলে Dec
// Years & months for dropdown
const years = ref(Array.from({ length: 8 }, (_, i) => 2022 + i));
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

// Modal instance
const instance = getCurrentInstance();

// Reactive form with dynamic arrays
const form = reactive({
    department: "",
    eid: "",
    year: selectedYear.value,
    month: previousMonth,
    earnings: [{ adjustment_type: "", amount: "", remarks: "" }],
    deductions: [{ adjustment_type: "", amount: "", remarks: "" }],
});

// Add/Remove dynamic rows
const addRow = (type) => {
    if (type === "earnings")
        form.earnings.push({ adjustment_type: "", amount: "", remarks: "" });
    if (type === "deductions")
        form.deductions.push({ adjustment_type: "", amount: "", remarks: "" });
};

const removeRow = (type, index) => {
    if (type === "earnings" && form.earnings.length > 1)
        form.earnings.splice(index, 1);
    if (type === "deductions" && form.deductions.length > 1)
        form.deductions.splice(index, 1);
};

// Fetch departments & adjustments
const getData = async () => {
    try {
        const [departmentRes, adjustmentRes, adjustmentTypeRes] =
            await Promise.all([
                api.get(`/department-list/${user.id}`),
                api.get("/salary-adjustment"),
                api.get("/salary-adjustment-type"),
            ]);
        department.value = departmentRes.data;
        salaryAdjustment.value = adjustmentRes.data;
        earning_adjustments.value = adjustmentTypeRes.data.earnings;
        deduction_adjustments.value = adjustmentTypeRes.data.deductions;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        selectedEmployee.value = null;
        form.eid = "";
        emp_img.value = null;
        return;
    }
    try {
        const res = await api.get(`/adjustment-employees/${deptID}`);
        employee.value = res.data;

        selectedEmployee.value = null;
        form.eid = "";
        emp_img.value = null;
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

// Prefill form on edit
watch(
    () => editStore,
    async (item) => {
        if (item) {
            form.department = item.dept_id || "";
            await getEmployee(item.dept_id);
            form.eid = item.eid || "";
            await getEmployeeImg(item.eid);

            // Fill earnings & deductions arrays if available
            form.earnings = item.earnings || [
                { adjustment_type: "", amount: "", remarks: "" },
            ];
            form.deductions = item.deductions || [
                { adjustment_type: "", amount: "", remarks: "" },
            ];

            if (item.date) {
                const [year, month] = item.date.split("-");
                form.year = year;
                form.month = parseInt(month);
            }
        } else {
            resetForm();
        }
    },
    { immediate: true },
);

// Reset form
const resetForm = () => {
    form.department = "";
    form.eid = "";
    form.year = selectedYear.value;
    form.month = selectedMonth.value;
    form.earnings = [{ adjustment_type: "", amount: "", remarks: "" }];
    form.deductions = [{ adjustment_type: "", amount: "", remarks: "" }];
    emp_img.value = null;
};

// Close modal
const closeModal = () => {
    instance.emit("modal-close");
};

// Create salary adjustment
const create = async () => {
    try {
        const res = await api.post("/salary-adjustment", form);
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
// const update = async () => {
//     try {
//         const recordIdentifier = `${form.eid}-${form.year}-${form.month}`;
//         const res = await api.put(
//             `/salary-adjustment/${recordIdentifier}`,
//             form
//         );
//         if (res.data.success) {
//             Swal.fire({
//                 icon: "success",
//                 title: "Salary Adjustment Updated",
//                 showConfirmButton: false,
//                 timer: 1500,
//             });
//             resetForm();
//             getData();
//             closeModal();
//         }
//     } catch (err) {
//         console.error("Error updating adjustment:", err);
//     }
// };

// Submit handler
const submit = () => {
    if (updateinfo === "Save") create();
    // else if (updateinfo === "Update") update();
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
    <div v-if="isOpen" class="modal fade show d-block" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header gradient text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-sliders me-2"></i> NEW ADJUSTMENT SETUP
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
                                            ><span> :</span>
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
                                                    :key="dept.department_id"
                                                    :value="dept.department_id"
                                                >
                                                    {{ dept.department_name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Employee Name -->
                                    <!-- <div class="row mb-3 align-items-center">
                                        <div class="col-3 d-flex">
                                            <span class="flex-grow-1 me-2">Employee Name</span><span>:</span>
                                        </div>
                                        <div class="col-md-6">
                                            <select class="form-select border-color custom-input" v-model="form.eid"
                                                @change="
                                                    getEmployeeImg(form.eid)
                                                    ">
                                                <option value="" selected>
                                                    Select Employee
                                                </option>
                                                <option v-for="emp in employee" :key="emp.id" :value="emp.id">
                                                    {{ emp.Full_Name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div> -->

                                    <div class="row mb-3 align-items-center">
                                        <div class="col-3 d-flex">
                                            <span class="flex-grow-1 me-2"
                                                >Employee Name</span
                                            ><span>:</span>
                                        </div>
                                        <div class="col-md-6 position-relative">
                                            <Multiselect
                                                v-model="selectedEmployee"
                                                :options="employee"
                                                label="Full_Name"
                                                track-by="id"
                                                placeholder="Select or search employee"
                                                :searchable="true"
                                                :close-on-select="true"
                                                :allow-empty="true"
                                                @update:modelValue="
                                                    onEmployeeSelect
                                                "
                                                :clear-on-select="false"
                                                class="multiselect-design"
                                            />

                                            <!-- REMOVE this -->
                                            <button
                                                v-if="selectedEmployee"
                                                type="button"
                                                class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-2"
                                                @click="selectedEmployee = ''"
                                            >
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Year & Month -->
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-3 d-flex">
                                            <span class="flex-grow-1 me-2"
                                                >Salary Year</span
                                            ><span>:</span>
                                        </div>
                                        <div class="col-md-3">
                                            <select
                                                class="form-select border-color custom-input"
                                                v-model="form.year"
                                            >
                                                <option disabled value="">
                                                    Select Date
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
                                        <div class="col-md-3">
                                            <select
                                                class="form-select border-color custom-input"
                                                v-model="form.month"
                                            >
                                                <option disabled value="">
                                                    Select Month
                                                </option>
                                                <option
                                                    v-for="(
                                                        month, index
                                                    ) in months"
                                                    :key="month"
                                                    :value="index + 1"
                                                >
                                                    {{ month }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- <div class="row mb-3 align-items-center">
                                        <div class="col-3 d-flex">
                                            <span class="flex-grow-1 me-2"
                                                >Salary Month</span
                                            ><span>:</span>
                                        </div>
                                        <div class="col-md-3">
                                            <select
                                                class="form-select border-color custom-input"
                                                v-model="form.month"
                                            >
                                                <option disabled value="">
                                                    Select Month
                                                </option>
                                                <option
                                                    v-for="(
                                                        month, index
                                                    ) in months"
                                                    :key="month"
                                                    :value="index + 1"
                                                >
                                                    {{ month }}
                                                </option>
                                            </select>
                                        </div>
                                    </div> -->
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

                    <!-- Earning Adjustment -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            EARNING ADJUSTMENT
                            <button
                                class="btn btn-sm btn-success float-end"
                                @click="addRow('earnings')"
                            >
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div
                                class="row g-3 mb-2"
                                v-for="(earning, index) in form.earnings"
                                :key="index"
                            >
                                <div class="col-md-3">
                                    <select
                                        class="form-select border-color custom-input"
                                        v-model="earning.adjustment_type"
                                    >
                                        <option disabled value="">
                                            Earning Type
                                        </option>
                                        <option
                                            v-for="adjustment in earning_adjustments"
                                            :key="adjustment.id"
                                            :value="adjustment.id"
                                        >
                                            {{ adjustment.adjustment_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="earning.amount"
                                        placeholder="Amount"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <input
                                        type="text"
                                        class="form-control border-color custom-input"
                                        v-model="earning.remarks"
                                        placeholder="Remarks"
                                    />
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <button
                                        v-if="form.earnings.length > 1"
                                        class="btn btn-sm btn-danger"
                                        @click="removeRow('earnings', index)"
                                    >
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deduction Adjustment -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            DEDUCTION ADJUSTMENT
                            <button
                                class="btn btn-sm btn-success float-end"
                                @click="addRow('deductions')"
                            >
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div
                                class="row g-3 mb-2"
                                v-for="(deduction, index) in form.deductions"
                                :key="index"
                            >
                                <div class="col-md-3">
                                    <select
                                        class="form-select border-color custom-input"
                                        v-model="deduction.adjustment_type"
                                    >
                                        <option disabled value="">
                                            Deduction Type
                                        </option>
                                        <option
                                            v-for="adjustment in deduction_adjustments"
                                            :key="adjustment.id"
                                            :value="adjustment.id"
                                        >
                                            {{ adjustment.adjustment_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="deduction.amount"
                                        placeholder="Amount"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <input
                                        type="text"
                                        class="form-control border-color custom-input"
                                        v-model="deduction.remarks"
                                        placeholder="Remarks"
                                    />
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <button
                                        v-if="form.deductions.length > 1"
                                        class="btn btn-sm btn-danger"
                                        @click="removeRow('deductions', index)"
                                    >
                                        <i class="fa fa-times"></i>
                                    </button>
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

.modal-content label,
.modal-content .card-header,
.modal-content option,
.modal-content select,
.modal-content input,
.modal-content textarea {
    text-transform: capitalize !important;
}
</style>
