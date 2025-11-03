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
                api.get("/salary-adjustment-type"),
            ]);
        adjustments.value = adjustmentTypeRes.data;
        department.value = departmentRes.data;
        salaryAdjustment.value = adjustmentRes.data;
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

onMounted(() => getData());
</script>

<template>
    <!-- Modal Wrapper -->
    <div
        v-if="isOpen"
        class="modal d-block"
        tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5)"
    >
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        {{
                            updateinfo === "Save"
                                ? "New Adjustment Setup"
                                : "Salary Adjustment Update"
                        }}
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-lg-9">
                            <!-- Department -->
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-3 fw-bold">
                                    Department Name
                                </div>
                                <div class="col-lg-9">
                                    <select
                                        class="form-control"
                                        v-model="form.department"
                                        @change="getEmployee(form.department)"
                                    >
                                        <option value="" disabled>
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

                            <!-- Employee -->
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-3 fw-bold">
                                    Employee Name
                                </div>
                                <div class="col-lg-9">
                                    <select
                                        class="form-control"
                                        v-model="form.eid"
                                        @change="getEmployeeImg(form.eid)"
                                    >
                                        <option value="" disabled>
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

                        <div class="col-lg-3 text-center">
                            <img
                                :src="emp_img || ''"
                                alt="Employee Image"
                                height="120"
                                width="120"
                                class="rounded shadow"
                            />
                        </div>
                    </div>

                    <!-- Adjustment Form -->
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label>Adjustment Type</label>
                                <select
                                    class="form-control"
                                    v-model="form.adjustment_type"
                                >
                                    <option disabled value="">
                                        Select Type
                                    </option>
                                    <option
                                        v-for="adjustment in adjustments"
                                        :key="adjustment.id"
                                        :value="adjustment.id"
                                    >
                                        {{ adjustment.adjustment_name }}
                                    </option>
                                </select>
                            </div>

                            <!-- <div
                                v-if="form.type === 'earning'"
                                class="col-md-3"
                            >
                                <label>Earning Options</label>
                                <select
                                    class="form-control"
                                    v-model="form.adjustmentCategory"
                                >
                                    <option disabled value="">Select</option>
                                    <option
                                        v-for="opt in earningOptions"
                                        :key="opt.id"
                                        :value="opt.key"
                                    >
                                        {{ opt.value }}
                                    </option>
                                </select>
                            </div>

                            <div
                                v-else-if="form.type === 'deduction'"
                                class="col-md-3"
                            >
                                <label>Deduction Options</label>
                                <select
                                    class="form-control"
                                    v-model="form.adjustmentCategory"
                                >
                                    <option disabled value="">Select</option>
                                    <option
                                        v-for="opt in deductionOptions"
                                        :key="opt.id"
                                        :value="opt.key"
                                    >
                                        {{ opt.value }}
                                    </option>
                                </select>
                            </div> -->

                            <div class="col-md-3">
                                <label>Salary Year</label>
                                <select
                                    class="form-control"
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

                            <div class="col-md-3">
                                <label>Salary Month</label>
                                <select
                                    class="form-control"
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

                            <div class="col-md-6">
                                <label>Amount</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="form.amount"
                                    placeholder="Amount"
                                />
                            </div>

                            <div class="col-md-6">
                                <label>Remarks</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.remarks"
                                    placeholder="Remarks"
                                />
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="mt-4 text-end">
                            <button
                                type="button"
                                class="btn btn-secondary me-2"
                                @click="closeModal"
                            >
                                Cancel
                            </button>
                            <button
                                v-if="updateinfo === 'Save'"
                                type="submit"
                                class="btn btn-success"
                            >
                                <i class="fa-solid fa-check"></i> Save
                            </button>
                            <button
                                v-else
                                type="submit"
                                class="btn btn-primary"
                            >
                                <i class="fa-solid fa-pen"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal {
    z-index: 1050;
}
.modal-backdrop {
    z-index: 1040;
}
img {
    border-radius: 8px;
    object-fit: cover;
}
</style>
