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

import { useStore } from "vuex";
const store = useStore();
const user = store.getters.getUser;

const { isOpen, editStore, updateinfo, EID, salId } = defineProps([
    "EID",
    "editStore",
    "isOpen",
    "salId",
    "updateinfo",
]);

const department = ref([]);
const employee = ref([]);
const emp_img = ref([]);
const selected_designation = ref("");

const emp_info = ref({});

const error = ref([]);

const form = ref({
    department: "",
    eid: "",
    gross_salary: "",
    basic: "",
    house_rent: "",
    conveyance: "",
    medical_allowance: "",
    consolidated: "0",
    festival_bonus: "0",
    travel_allowance: "0",
    overtime: "0",
    daily_allowance: "0",
    mobile_bill: "0",
    other_allowance: "0",
    total_payment: "",
    provident_fund: "0",
    income_tax: "0",
    revenue_stamp: "0",
    leave_without_pay: "0",
    penalty_other_deduction: "0",
    advance: "0",
    total_deduction: "",
    total_earning: "",
    net_pay: "",
    salary_type: "",
    bank_account_no: "",
});

import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

const selectedEmployee = ref(null);

// When you fetch employees (getEmployee), map to id + Full_Name
const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        selectedEmployee.value = null;
        return;
    }
    try {
        const res = await api.get(`/emp/${deptID}`);
        employee.value = res.data.map((emp) => ({
            id: emp.id, // must match track-by
            Full_Name: emp.Full_Name,
            img_url: emp.img_url || null,
        }));

        // For edit mode: prefill selectedEmployee
        if (editStore && editStore.employee_id) {
            selectedEmployee.value = employee.value.find(
                (e) => e.id === editStore.employee_id,
            );
        }
    } catch (err) {
        console.error("Error fetching employees:", err);
    }
};
console.log("Employee List:", form.value);
// When Multiselect changes
const onEmployeeSelect = (emp) => {
    if (!emp) return;
    form.value.eid = emp.id;
    getEmployeeInfo(emp.id);
    getEmployeeImg(emp.id);
};

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
        Number(form.value.other_allowance),
);
watch(total_payment, (val) => (form.value.total_payment = val));

const total_deduction = computed(
    () =>
        Number(form.value.provident_fund) +
        Number(form.value.income_tax) +
        Number(form.value.revenue_stamp) +
        Number(form.value.leave_without_pay) +
        Number(form.value.penalty_other_deduction) +
        Number(form.value.advance),
);
watch(total_deduction, (val) => (form.value.total_deduction = val));

const net_pay = computed(
    () => form.value.total_payment - form.value.total_deduction,
);
watch(net_pay, (val) => (form.value.net_pay = val));

const getData = async () => {
    try {
        const response = await api.get(`/department-list/${user.id}`);
        department.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

// const getEmployee = async (deptID) => {
//     if (!deptID) {
//         employee.value = [];
//         return;
//     }
//     try {
//         const res = await api.get(`/emp/${deptID}`);
//         employee.value = res.data;
//     } catch (err) {
//         console.error("Error fetching employees:", err);
//     }
// };

const getEmployeeInfo = async (id) => {
    try {
        const response = await api.get(`/emp-info/${id}`);
        emp_info.value = response.data; // ✅ object
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

watch(
    () => editStore,
    async (item) => {
        if (item) {
            form.value.department = item.department_id || "";
            await getEmployee(item.department_id);

            // Prefill selectedEmployee for Multiselect
            selectedEmployee.value = employee.value.find(
                (e) => e.id === item.employee_id,
            );
            await getEmployeeInfo(item.employee_id);
            form.value.eid = item.employee_id || "";
            await getEmployeeImg(item.employee_id);

            // Fill other fields...
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
            form.value.total_earning = item.total_payment || 0;
            form.value.net_pay = item.net_pay || "";
            form.value.salary_type = item.salary_type || "";
            form.value.bank_account_no = item.bank_account_no || "";
        }
    },
    { immediate: true },
);

const getEmployeeImg = async (id) => {
    try {
        const response = await api.get(`/emp-img/${id}`);
        emp_img.value = response.data;
    } catch (error) {
        console.error("Error fetching employee image:", error);
    }
};
const instance = getCurrentInstance();

const resetForm = () => {
    form.value = {
        department: "",
        eid: "",
        gross_salary: "",
        basic: "",
        house_rent: "",
        conveyance: "",
        medical_allowance: "",
        consolidated: "0",
        festival_bonus: "0",
        travel_allowance: "0",
        overtime: "0",
        daily_allowance: "0",
        mobile_bill: "0",
        other_allowance: "0",
        total_payment: "",
        provident_fund: "0",
        income_tax: "0",
        revenue_stamp: "0",
        leave_without_pay: "0",
        penalty_other_deduction: "0",
        advance: "0",
        net_pay: "",
        salary_type: "",
        bank_account_no: "",
    };
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
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header gradient text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-stack me-2"></i>
                        {{
                            updateinfo === "Save"
                                ? "NEW SALARY SETUP"
                                : "SALARY SETUP UPDATE"
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
                                <div class="col-md-8">
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
                                                class="form-select border-color custom-input field-height"
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
                                    <!-- Employee Name -->
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-3 d-flex">
                                            <span class="flex-grow-1 me-2"
                                                >Employee Name</span
                                            >
                                            <span>:</span>
                                        </div>
                                        <div class="col-md-6">
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

                                            <button
                                                v-if="selectedEmployee"
                                                type="button"
                                                class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
                                                @click="selectedEmployee = null"
                                            >
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Designation -->
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-3 d-flex">
                                            <span class="flex-grow-1 me-2"
                                                >Designation</span
                                            >
                                            <span>:</span>
                                        </div>
                                        <div class="col-md-6">
                                            <input
                                                type="text"
                                                class="form-control border-color custom-input field-height"
                                                :value="
                                                    emp_info.designation_name
                                                "
                                                readonly
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Image -->
                                <div
                                    class="col-md-4"
                                    v-for="(item, index) in employee"
                                    :key="item.id"
                                >
                                    <div
                                        v-if="index === 0"
                                        class="about-avatar"
                                    >
                                        <img
                                            :src="item.img_url"
                                            class="img-fluid"
                                            style="
                                                max-height: 150px;
                                                max-width: 130px;
                                            "
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Breakdown Section -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            SALARY DETAILS
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label"
                                        >Gross Salary</label
                                    >
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.gross_salary"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"
                                        >Basic Salary</label
                                    >
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.basic"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">House Rent</label>
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.house_rent"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Conveyance</label>
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.conveyance"
                                    />
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-3">
                                    <label class="form-label"
                                        >Medical Allowance</label
                                    >
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.medical_allowance"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <div class="form-label">Consolidated</div>
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.consolidated"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <div class="form-label">Festival Bonus</div>
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.festival_bonus"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <div class="form-label">
                                        Travel Allowance
                                    </div>
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.travel_allowance"
                                    />
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-3">
                                    <div class="form-label">
                                        Daily Allowance
                                    </div>
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.daily_allowance"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <div class="form-label">Mobile Bill</div>
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.mobile_bill"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <div class="form-label">
                                        Other Allowance
                                    </div>
                                    <input
                                        type="number"
                                        class="form-control border-color custom-input"
                                        v-model="form.other_allowance"
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
                                    DEDUCTIONS
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <div class="form-label">
                                                Provident Fund
                                            </div>
                                            <input
                                                type="number"
                                                class="form-control border-color custom-input"
                                                v-model="form.provident_fund"
                                            />
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-label">
                                                Income Tax
                                            </div>
                                            <input
                                                type="number"
                                                class="form-control border-color custom-input"
                                                v-model="form.income_tax"
                                            />
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-label">
                                                Revenue Stamp
                                            </div>
                                            <input
                                                type="number"
                                                class="form-control border-color custom-input"
                                                v-model="form.revenue_stamp"
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
                                    <label class="form-label">NET PAY</label>
                                    <input
                                        type="number"
                                        class="form-control border-color fs-5 fw-bold text-success"
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
                                <div class="col-md-3">
                                    <label class="form-label"
                                        >Salary Type</label
                                    >
                                    <select
                                        v-model="form.salary_type"
                                        class="form-select border-color custom-input"
                                    >
                                        <option selected disabled>
                                            -Select-
                                        </option>
                                        <option value="bank">Bank</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>

                                <div
                                    class="col-md-3"
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
.field-height {
    height: 38px;
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
