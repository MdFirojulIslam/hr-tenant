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
        class="modal-mask col-lg-6 col-md-6 grid-margin stretch-card"
        ref="target"
    >
        <div class="card">
            <div class="card-body">
                <div class="card mb-2">
                    <div
                        v-if="updateinfo === 'Save'"
                        class="card-header mb-1 bg-grey align-items-center"
                    >
                        <h5 class="mb-0">New Salary Setup</h5>
                    </div>
                    <div
                        v-else-if="updateinfo === 'Update'"
                        class="card-header mb-1 bg-grey align-items-center"
                    >
                        <h5 class="mb-0">Salary Setup Update</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-9">
                                <div class="row mb-3">
                                    <div class="col-lg-3">Department Name</div>
                                    <div class="col-lg-1">:</div>
                                    <div class="col-lg-8">
                                        <select
                                            class="form-control"
                                            v-model="form.department"
                                            @change="
                                                getEmployee(form.department)
                                            "
                                        >
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

                                <div class="row mb-3">
                                    <div class="col-lg-3">Employee Name</div>
                                    <div class="col-lg-1">:</div>
                                    <div class="col-lg-8">
                                        <select
                                            class="form-control"
                                            v-model="form.eid"
                                            @change="getEmployeeInfo(form.eid)"
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
                                <div class="row mb-3">
                                    <div class="col-lg-3">Designation</div>
                                    <div class="col-lg-1">:</div>
                                    <div class="col-lg-8">
                                        <input
                                            type="text"
                                            name=""
                                            class="form-control"
                                            :value="selected_designation"
                                            readonly
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <img
                                    :src="emp_img ? emp_img.img_url : ''"
                                    alt=""
                                    height="110px"
                                    width="110px"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-9 col-md-12 col-sm-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Gross Salary</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.gross_salary"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Basic</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.basic"
                                                readonly
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >House Rent</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.house_rent"
                                                readonly
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Conveyance</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.conveyance"
                                                readonly
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Medical Allowance</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.medical_allowance"
                                                readonly
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Consolidated</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.consolidated"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Festival Bonus</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.festival_bonus"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Travel Allowance</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.travel_allowance"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Overtime</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.overtime"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Daily Allowance</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.daily_allowance"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Mobile Bill Allowance</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.mobile_bill"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Other Allowance</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.other_allowance"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Provident Fund</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.provident_fund"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Income Tax</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.income_tax"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Revenue Stamp</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.revenue_stamp"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Leave Without Pay</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.leave_without_pay"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Penalty/Other Deduction</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="
                                                    form.penalty_other_deduction
                                                "
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Advance</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.advance"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-12 col-sm-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Total Payment</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.total_payment"
                                                readonly
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Total Deduction</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.total_deduction"
                                                readonly
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Total Earning</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.total_earning"
                                                readonly
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1"
                                                >Net Pay</label
                                            >
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model="form.net_pay"
                                                readonly
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4 col-md-6 col-sm-6">
                                <div class="form-group">
                                    <label for="exampleInputEmail1"
                                        >Salary Type</label
                                    >
                                    <select
                                        v-model="form.salary_type"
                                        class="form-control"
                                    >
                                        <option selected disabled>
                                            -select-
                                        </option>
                                        <option value="bank">Bank</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>
                            </div>
                            <div
                                class="col-lg-4 col-md-6 col-sm-6"
                                v-if="form.salary_type == 'bank'"
                            >
                                <div class="form-group">
                                    <label for="exampleInputEmail1"
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

                <div class="d-flex justify-content-between mt-3">
                    <button
                        type="submit"
                        class="custom-btn btn-13 p-2"
                        @click="submit"
                    >
                        <i class="fa-solid fa-check"></i> | {{ updateinfo }}
                    </button>
                    <button
                        type="submit"
                        class="custom-btn btn-15 p-2"
                        @click="resetForm"
                    >
                        <i class="fa-solid fa-power-off"></i> | Reset
                    </button>
                    <button
                        type="reset"
                        class="custom-btn btn-12 p-2"
                        @click.stop="closeModal"
                    >
                        <i class="fa-solid fa-x"></i> | Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
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
    background-color: #620d0d;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    background-color: blue;
}

.swal2-container {
    z-index: 2001 !important;
    /* Make sure it's higher than .modal-mask */
}

.swal-popup-zindex {
    z-index: 2001 !important;
    /* Ensure it's higher than .modal-mask */
}
</style>
