<script setup>
import {
    ref,
    defineProps,
    getCurrentInstance,
    watch,
    onMounted,
    computed,
} from "vue";
import Swal from "sweetalert2";
import api from "@/api";

const { isOpen, editStore, updateinfo, EID, salId } = defineProps([
    "isOpen",
    "editStore",
    "updateinfo",
    "EID",
    "salId",
]);

const department = ref([]);
const employee = ref([]);
const emp_img = ref([]);
const selected_designation = ref("");

const emp_info = ref([]);

const error = ref([]);

const form = ref({
    department: "",
    eid: "",
    gross_salary: 0,
    basic: 0,
    house_rent: 0,
    conveyance: 0,
    medical_allowance: 0,
    consolidated: 0,
    festival_bonus: 0,
    travel_allowance: 0,
    overtime: 0,
    daily_allowance: 0,
    mobile_bill: 0,
    other_allowance: 0,
    total_payment: 0,
    provident_fund: 0,
    income_tax: 0,
    revenue_stamp: 0,
    leave_without_pay: 0,
    penalty_other_deduction: 0,
    advance: 0,
    total_deduction: "",
    total_earning: "",
    net_pay: 0,
    salary_type: "",
    bank_account_no: "",
});

const basic = computed(() => form.value.gross_salary * 0.5);
watch(basic, (val) => (form.value.basic = val));

const house_rent = computed(() => form.value.gross_salary * 0.4);
watch(house_rent, (val) => (form.value.house_rent = val));

const conveyance = computed(() => form.value.gross_salary * 0.05);
watch(conveyance, (val) => (form.value.conveyance = val));

const medical_allowance = computed(() => form.value.gross_salary * 0.05);
watch(medical_allowance, (val) => (form.value.medical_allowance = val));

const total_payment = computed(
    () =>
        Number(basic.value) +
        Number(form.value.house_rent) +
        Number(form.value.conveyance) +
        Number(form.value.medical_allowance) +
        Number(form.value.consolidated) +
        Number(form.value.festival_bonus) +
        Number(form.value.travel_allowance) +
        Number(form.value.overtime) +
        Number(form.value.daily_allowance) +
        Number(form.value.mobile_bill) +
        Number(form.value.other_allowance)
);
watch(total_payment, (val) => (form.value.total_payment = val));

const total_deduction = computed(
    () =>
        Number(form.value.provident_fund) +
        Number(form.value.income_tax) +
        Number(form.value.revenue_stamp) +
        Number(form.value.leave_without_pay) +
        Number(form.value.penalty_other_deduction) +
        Number(form.value.advance)
);
watch(total_deduction, (val) => (form.value.total_deduction = val));

const net_pay = computed(
    () => form.value.total_payment - form.value.total_deduction
);
watch(net_pay, (val) => (form.value.net_pay = val));

const getData = async () => {
    try {
        const response = await api.get("/department");
        department.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

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

const getEmployeeInfo = async (id) => {
    try {
        console.log("Fetching info for Employee ID:", id);
        const response = await api.get(`/emp-info/${id}`);
        emp_info.value = response.data;
        console.log("Employee Info:", emp_info.value);
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

watch(
    () => editStore,
    async (item) => {
        if (item) {
            form.value.department = item.dept_id || "";
            await getEmployee(item.dept_id);
            form.value.eid = item.eid || "";
            await getEmployeeImg(item.eid);
            form.value.gross_salary = item.gross_salary || "";
            form.value.house_rent = item.house_rent || "";
            form.value.conveyance = item.conveyance || "";
            form.value.medical_allowance = item.medical_allowance || "";
            form.value.consolidated = item.consolidated || "";
            form.value.festival_bonus = item.festival_bonus || "";
            form.value.travel_allowance = item.travel_allowance || "";
            form.value.overtime = item.overtime || "";
            form.value.daily_allowance = item.daily_allowance || "";
            form.value.mobile_bill = item.mobile_bill || "";
            form.value.other_allowance = item.other_allowance || "";
            form.value.provident_fund = item.provident_fund || "";
            form.value.income_tax = item.income_tax || "";
            form.value.revenue_stamp = item.revenue_stamp || "";
            form.value.leave_without_pay = item.leave_without_pay || "";
            form.value.penalty_other_deduction =
                item.penalty_other_deduction || "";
            form.value.advance = item.advance || "";
            form.value.total_deduction = item.total_deduction || 0;
            form.value.total_earning = item.earning_amount || 0;
            form.value.net_pay = item.salary_net_pay || "";
        }
    },
    { immediate: true }
);

const instance = getCurrentInstance();

const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        if (typeof form.value[key] === "string") {
            form.value[key] = "";
        } else {
            form.value[key] = 0;
        }
    });
};

const closeModal = () => {
    selected_designation.value = "";
    instance.emit("modal-close");
};

const create = async () => {
    try {
        const response = await api.post("/salary-setup", form.value);
        if (response.data.success) {
            instance.emit("modal-close");
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been Saved",
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
        }
    } catch (error) {
        if (error.response && error.response.status === 422) {
            instance.emit("modal-close");
            const messages = Object.values(error.response.data.message)
                .flat()
                .join("<br>");
            Swal.fire({
                icon: "error",
                title: "Validation Error",
                html: messages,
                customClass: {
                    popup: "swal-popup-zindex",
                },
            });
        } else {
            Swal.fire({
                icon: "error",
                title: "An error occurred",
                text: "Something went wrong. Please try again later.",
            });
        }
        console.error("Error creating store:", error);
    }
};

const update = async () => {
    try {
        const response = await api.put(`/salary-update/${salId}`, form.value);
        if (response.data.success) {
            instance.emit("modal-close");
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been Updated",
                showConfirmButton: false,
                timer: 1500,
            });
        }
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

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
    <div
        v-if="isOpen"
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-stack me-2"></i>
                        {{
                            updateinfo === "Save"
                                ? "New Salary Setup"
                                : "Salary Setup Update"
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
                            Employee Information
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <!-- Department -->
                                    <div class="row mb-3 align-items-center">
                                        <label class="col-4 col-form-label"
                                            >Department</label
                                        >
                                        <div class="col-8">
                                            <select
                                                class="form-select"
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
                                        <label class="col-4 col-form-label"
                                            >Employee Name</label
                                        >
                                        <div class="col-8">
                                            <select
                                                class="form-select"
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

                                    <!-- Designation -->
                                    <div class="row mb-3 align-items-center">
                                        <label class="col-4 col-form-label"
                                            >Designation</label
                                        >
                                        <div class="col-8">
                                            <input
                                                type="text"
                                                class="form-control"
                                                :value="selected_designation"
                                                readonly
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Image -->
                                <div class="col-md-4 text-center">
                                    <img
                                        :src="emp_img ? emp_img.img_url : ''"
                                        class="rounded border"
                                        alt="Employee"
                                        height="120"
                                        width="120"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Breakdown Section -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            Salary Details
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div
                                    class="col-md-3"
                                    v-for="(label, field) in {
                                        'Gross Salary': 'gross_salary',
                                        'Basic Salary': 'basic',
                                        'House Rent': 'house_rent',
                                        Conveyance: 'conveyance',
                                    }"
                                    :key="field"
                                >
                                    <label class="form-label">{{
                                        label
                                    }}</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model="form[field]"
                                        :readonly="field !== 'gross_salary'"
                                    />
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div
                                    class="col-md-3"
                                    v-for="(label, field) in {
                                        'Medical Allowance':
                                            'medical_allowance',
                                        Consolidated: 'consolidated',
                                        'Festival Bonus': 'festival_bonus',
                                        'Travel Allowance': 'travel_allowance',
                                    }"
                                    :key="field"
                                >
                                    <label class="form-label">{{
                                        label
                                    }}</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model="form[field]"
                                        :readonly="
                                            field === 'medical_allowance'
                                        "
                                    />
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div
                                    class="col-md-3"
                                    v-for="(label, field) in {
                                        'Daily Allowance': 'daily_allowance',
                                        'Mobile Bill': 'mobile_bill',
                                        'Other Allowance': 'other_allowance',
                                    }"
                                    :key="field"
                                >
                                    <label class="form-label">{{
                                        label
                                    }}</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model="form[field]"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions & Net Pay -->
                    <div class="row">
                        <div class="col-md-9">
                            <div class="card mb-3 shadow-sm">
                                <div class="card-header bg-light fw-bold">
                                    Deductions
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div
                                            class="col-md-4"
                                            v-for="(label, field) in {
                                                'Provident Fund':
                                                    'provident_fund',
                                                'Income Tax': 'income_tax',
                                                'Revenue Stamp':
                                                    'revenue_stamp',
                                            }"
                                            :key="field"
                                        >
                                            <label class="form-label">{{
                                                label
                                            }}</label>
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form[field]"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Net Pay -->
                        <div class="col-md-3">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <label class="form-label">Net Pay</label>
                                    <input
                                        type="number"
                                        class="form-control fs-5 fw-bold text-success"
                                        v-model="form.net_pay"
                                        readonly
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Type -->
                    <div class="card shadow-sm mt-3">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Salary Type</label
                                    >
                                    <select
                                        v-model="form.salary_type"
                                        class="form-select"
                                    >
                                        <option selected disabled>
                                            -Select-
                                        </option>
                                        <option value="bank">Bank</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>

                                <div
                                    class="col-md-4"
                                    v-if="form.salary_type === 'bank'"
                                >
                                    <label class="form-label"
                                        >Bank Account No</label
                                    >
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.bank_account_no"
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
