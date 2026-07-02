<script setup>
import { onMounted, ref, watch, computed } from "vue";
import { useRoute } from "vue-router";
import NumberField from "@/pages/components/input_field/NumberField.vue";
import Label from "@/pages/components/label/Label.vue";
import SelectField from "@/pages/components/input_field/SelectField.vue";
import Image from "@/pages/components/image/Image.vue";
import api from "@/api";
import { useStore } from "vuex";
import { useRouter } from "vue-router";

const router = useRouter();

const store = useStore();
const role_id = store.getters.getRoleId;
const user = store.getters.getUser;

// Get the route parameters
const route = useRoute();
const employee_id_params = route.params.id;

const user_id = user.id;
const salaries_info = ref({});
const isloading = ref(true);
const errors = ref({});

const increment_status = ref(false);
const activeTab = ref("salary_details");

const increment_date = ref("");

const tmp_gross_salary = ref(0);

const tmp_previous_gross_salary = ref(0);

const originalPresentIncrementDate = ref("");
const originalPresentIncrementAmount = ref("");

const tabs = [
    { key: "salary_details", label: "Salary Details" },
    { key: "increments", label: "Increments" },
    { key: "adjustments", label: "Deductions" },
];

const getData = async () => {
    try {
        const res = await api.get(
            `/single-salaries-info/${employee_id_params}`,
        );
        salaries_info.value = res.data?.[0];
    } catch (err) {
        error.value = err.message || "Error Fetching Data";
    } finally {
        isloading.value = false;
    }
};

const form = ref({
    employee_id: "",
    employee_name: "",
    department_name: "",
    designation_name: "",
    date_of_join: "",
    status: "",
    job_location: "",
    salary_type: "",
    bank_account_no: "",
    gross_salary: "",
    basic_salary: "",
    house_rent: "",
    conveyance: "",
    medical_allowance: "",
    consolidated: "",
    festival_bonus: "",
    travel_allowance: "",
    daily_allowance: "",
    mobile_bill: "",
    other_allowance: "",
    total_payment: "",
    provident_fund: "",
    increment_amount: "",
    increment_date: "",
    present_increment_amount: "",
    present_increment_date: "",
    income_tax: "",
    revenue_stamp: "",
    total_deduction: "",
    hold_status: false,
    net_pay: "",
});
watch(salaries_info, (item) => {
    if (item) {
        form.value.employee_id = item?.employee?.id || 0;
        form.value.employee_name = item?.employee?.Full_Name || 0;
        form.value.department_name = item?.official?.department?.Name || 0;
        form.value.designation_name = item?.official?.designation?.Name || 0;
        form.value.date_of_join = item?.official?.DOJ || 0;
        form.value.status =
            item?.official?.Status == "N" ? "Active" : "Inactive";
        form.value.job_location = item?.official?.branch.Name || 0;
        form.value.salary_type = item?.salary_type || 0;
        form.value.bank_account_no = item?.bank_account_no || 0;
        tmp_gross_salary.value = Number(item?.gross_salary) || 0;

        tmp_previous_gross_salary.value =
            Number(item?.salary_increment?.previous_gross_salary) || 0;

        form.value.gross_salary = Number(item?.gross_salary) || 0;

        form.value.basic_salary = Number(item?.basic) || 0;
        form.value.house_rent = Number(item?.house_rent) || 0;
        form.value.conveyance = Number(item?.conveyance) || 0;
        form.value.medical_allowance = Number(item?.medical_allowance) || 0;
        form.value.consolidated = Number(item?.consolidated) || 0;
        form.value.festival_bonus = Number(item?.festival_bonus) || 0;
        form.value.travel_allowance = Number(item?.travel_allowance) || 0;
        form.value.daily_allowance = Number(item?.daily_allowance) || 0;
        form.value.mobile_bill = Number(item?.mobile_bill) || 0;
        form.value.other_allowance = Number(item?.other_allowance) || 0;

        originalPresentIncrementDate.value =
            item?.salary_increment?.increment_dates || "";

        originalPresentIncrementAmount.value =
            Number(item?.salary_increment?.increment_amount) || 0;

        form.value.present_increment_amount =
            Number(item?.salary_increment?.increment_amount) || 0;
        form.value.present_increment_date =
            item?.salary_increment?.increment_dates || "";

        form.value.provident_fund = Number(item?.provident_fund) || 0;
        form.value.income_tax = Number(item?.income_tax) || 0;
        form.value.revenue_stamp = Number(item?.revenue_stamp) || 0;
        form.value.hold_status = item?.hold_status == 1 ? true : false;
        form.value.net_pay = Number(item?.net_pay) || 0;
    }
});

const tmp_previous_gross = computed(
    () =>
        (Number(tmp_previous_gross_salary.value) || 0) +
        Number(form.value.present_increment_amount || 0),
);

const present_gross = computed(() => {
    if (Number(tmp_previous_gross.value > 0)) {
        return (
            Number(tmp_previous_gross.value) +
            Number(form.value.increment_amount)
        );
    } else {
        return (
            Number(tmp_gross_salary.value) + Number(form.value.increment_amount)
        );
    }
});

const isPresentIncrementReadonly = computed(() => {
    return (
        form.value.increment_amount !== "" &&
        form.value.increment_amount !== null &&
        Number(form.value.increment_amount) > 0
    );
});

watch(
    () => form.value.increment_amount,
    (newValue) => {
        if (newValue !== "" && newValue !== null && Number(newValue) > 0) {
            // Reset to API value
            form.value.present_increment_amount =
                originalPresentIncrementAmount.value;

            form.value.present_increment_date =
                originalPresentIncrementDate.value;
        }
    },
);

watch(present_gross, (newValue) => {
    form.value.gross_salary = newValue;
});

// 50%
const basic_salary = computed(() => Number(form.value.gross_salary) * 0.5);
watch(basic_salary, (value) => {
    form.value.basic_salary = value;
});

// 5%
const house_rent = computed(() => Number(form.value.gross_salary) * 0.4);
watch(house_rent, (value) => {
    form.value.house_rent = value;
});

const conveyance = computed(() => Number(form.value.gross_salary) * 0.05);
watch(conveyance, (value) => {
    form.value.conveyance = value;
});

const medical_allowance = computed(
    () => Number(form.value.gross_salary) * 0.05,
);
watch(medical_allowance, (value) => {
    form.value.medical_allowance = value;
});

const total_payment = computed(
    () =>
        Number(form.value.gross_salary || 0) +
        Number(form.value.consolidated || 0) +
        Number(form.value.festival_bonus || 0) +
        Number(form.value.travel_allowance || 0) +
        Number(form.value.daily_allowance || 0) +
        Number(form.value.mobile_bill || 0) +
        Number(form.value.other_allowance || 0),
);

watch(total_payment, (value) => {
    form.value.total_payment = value;
});

const total_deduction = computed(
    () =>
        Number(form.value.provident_fund || 0) +
        Number(form.value.income_tax || 0) +
        Number(form.value.revenue_stamp || 0),
);

const net_pay = computed(
    () => Number(total_payment.value || 0) - Number(total_deduction.value || 0),
);

watch(net_pay, (value) => {
    form.value.net_pay = value;
});

const increment = () => {
    increment_status.value = true;
};

const submit = async () => {
    try {
        const response = await api.put("/single-salaries-info", form.value);
        if (response.data.success) {
            store.commit("setSuccess", response.data.message);

            router.push({
                name: "SalarySetup",
            });
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        }
    }
};

onMounted(() => {
    getData();
});
</script>

<template>
    <div class="container border rounded">
        <div class="header m-3">
            <h4 class="header-text">
                Edit salarie's for {{ form.employee_name }}
            </h4>
        </div>
        <div class="m-3">
            <form @submit.prevent="submit">
                <div class="row">
                    <div class="col-md-8">
                        <div class="top-card">
                            <div class="d-flex gap-4 mt-4">
                                <div class="image">
                                    <Image
                                        src="/default-avatar.png"
                                        width="100px"
                                        height="100px"
                                        circle
                                    />
                                </div>
                                <div class="mt-2">
                                    <div class="basic-info-rows">
                                        <span class="basic-info-labels"
                                            >Name</span
                                        >
                                        <span class="colon">:</span>
                                        <span class="basic-info-values">{{
                                            form.employee_name
                                        }}</span>
                                    </div>

                                    <div class="basic-info-rows">
                                        <span class="basic-info-labels"
                                            >Department</span
                                        >
                                        <span class="colon">:</span>
                                        <span class="basic-info-values">{{
                                            form.department_name
                                        }}</span>
                                    </div>
                                    <div class="basic-info-rows">
                                        <span class="basic-info-labels"
                                            >Designation</span
                                        >
                                        <span class="colon">:</span>
                                        <span class="basic-info-values">{{
                                            form.designation_name
                                        }}</span>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="basic-info-rows">
                                        <span class="basic-info-labels"
                                            >DOJ</span
                                        >
                                        <span class="colon">:</span>
                                        <span class="basic-info-values">{{
                                            form.date_of_join
                                        }}</span>
                                    </div>
                                    <div class="basic-info-rows">
                                        <span class="basic-info-labels"
                                            >Status</span
                                        >
                                        <span class="colon">:</span>
                                        <span class="basic-info-values">{{
                                            form.status
                                        }}</span>
                                    </div>
                                    <div class="basic-info-rows">
                                        <span class="basic-info-labels"
                                            >Job Location</span
                                        >
                                        <span class="colon">:</span>
                                        <span class="basic-info-values">{{
                                            form.job_location
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="top-card">
                            <div class="info-heading mb-3">
                                Payment Type Information's
                            </div>
                            <div class="row g-3">
                                <div
                                    class="col-md-12 d-flex align-items-center"
                                >
                                    <Label
                                        label="Salary Type*"
                                        class="bank-labels"
                                    />
                                    <span class="colon">:</span>
                                    <SelectField
                                        v-model="form.salary_type"
                                        :items="[
                                            { name: 'Bank', value: 'bank' },
                                            { name: 'Cash', value: 'cash' },
                                        ]"
                                    />
                                </div>
                                <div
                                    class="col-md-12 d-flex align-items-center"
                                >
                                    <Label
                                        label="Bank Account No"
                                        class="bank-labels"
                                    />
                                    <span class="colon">:</span>
                                    <NumberField
                                        v-model="form.bank_account_no"
                                        type="number"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-heading mb-3">
                    <ul class="tab-menu d-flex gap-4 m-0 p-0 list-unstyled">
                        <li
                            v-for="tab in tabs"
                            :key="tab.key"
                            class="tab-items"
                            :class="{ active: activeTab === tab.key }"
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                        </li>
                    </ul>
                </div>
                <div
                    v-if="activeTab === 'salary_details'"
                    class="salary-info-body"
                >
                    <div class="row g-3">
                        <div class="col-md-2 salary-input-fields">
                            <Label label="Gross Salary*" />
                            <NumberField
                                v-model="form.gross_salary"
                                type="number"
                            />
                        </div>
                        <div class="col-md-2 salary-input-fields">
                            <Label label="Basic Salary : (50%)" />
                            <NumberField
                                v-model="form.basic_salary"
                                readonly
                                type="number"
                            />
                        </div>
                        <div class="col-md-2 salary-input-fields">
                            <Label label="House Rent : (40%)" />
                            <NumberField
                                v-model="form.house_rent"
                                readonly
                                type="number"
                            />
                        </div>
                        <div class="col-md-2 salary-input-fields">
                            <Label label="Conveyance : (5%)" />
                            <NumberField
                                v-model="form.conveyance"
                                readonly
                                type="number"
                            />
                        </div>
                        <div class="col-md-2 salary-input-fields">
                            <Label label="Medical Allowance : (5%)" />
                            <NumberField
                                v-model="form.medical_allowance"
                                readonly
                                type="number"
                            />
                        </div>
                        <div class="col-md-2 salary-input-fields">
                            <Label label="Consolidated" />
                            <NumberField
                                v-model="form.consolidated"
                                type="number"
                            />
                        </div>
                        <div class="col-md-2 salary-input-fields">
                            <Label label="Festival Bonus" />
                            <NumberField
                                v-model="form.festival_bonus"
                                type="number"
                            />
                        </div>
                        <div class="col-md-2 salary-input-fields">
                            <Label label="Travel Allowance" />
                            <NumberField
                                v-model="form.travel_allowance"
                                type="number"
                            />
                        </div>
                        <div class="col-md-2 salary-input-fields">
                            <Label label="Daily Allowance" />
                            <NumberField
                                v-model="form.daily_allowance"
                                type="number"
                            />
                        </div>
                        <div class="col-md-2 salary-input-fields">
                            <Label label="Mobile Bill" />
                            <NumberField
                                v-model="form.mobile_bill"
                                type="number"
                            />
                        </div>
                        <div class="col-md-2 salary-input-fields">
                            <Label label="Other Allowance" />
                            <NumberField
                                v-model="form.other_allowance"
                                type="number"
                            />
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="row">
                        <div v-if="activeTab === 'increments'" class="col-md-4">
                            <div class="salary-info-body">
                                <div class="row g-3">
                                    <div
                                        class="col-md-12 d-flex align-items-center"
                                    >
                                        <Label
                                            label="Increment Amount*"
                                            class="increment-label"
                                        />
                                        <span class="colon">:</span>
                                        <NumberField
                                            v-model="form.increment_amount"
                                            type="number"
                                        />
                                    </div>
                                    <div
                                        class="col-md-12 d-flex align-items-center"
                                    >
                                        <Label
                                            label="Effective From*"
                                            class="increment-label"
                                        />
                                        <span class="colon">:</span>
                                        <NumberField
                                            v-model="form.increment_date"
                                            type="date"
                                            :error="errors.increment_date"
                                        />
                                    </div>
                                    <p
                                        v-if="errors.increment_date"
                                        class="text-danger"
                                    >
                                        {{ errors.increment_date[0] }}
                                    </p>
                                    <div
                                        v-if="
                                            form.present_increment_amount ===
                                                '' ||
                                            form.present_increment_amount ===
                                                null ||
                                            Number(
                                                form.present_increment_amount,
                                            ) !== 0
                                        "
                                        class="col-md-12 d-flex align-items-center"
                                    >
                                        <Label
                                            label="Current Increment Amount*"
                                            class="increment-label"
                                        />
                                        <span class="colon">:</span>
                                        <NumberField
                                            v-model="
                                                form.present_increment_amount
                                            "
                                            type="number"
                                            :readonly="
                                                isPresentIncrementReadonly
                                            "
                                        />
                                    </div>
                                    <div
                                        v-if="
                                            form.present_increment_date != '' &&
                                            form.present_increment_date != 0
                                        "
                                        class="col-md-12 d-flex align-items-center"
                                    >
                                        <Label
                                            label="Current Increment Date*"
                                            class="increment-label"
                                        />
                                        <span class="colon">:</span>
                                        <NumberField
                                            v-model="
                                                form.present_increment_date
                                            "
                                            type="date"
                                            :readonly="
                                                isPresentIncrementReadonly
                                            "
                                        />
                                    </div>
                                    <div
                                        class="col-md-12 d-flex align-items-center"
                                    >
                                        <Label
                                            label="Present Gross Salary"
                                            class="increment-label"
                                        />
                                        <span class="colon">:</span>
                                        <NumberField
                                            v-model="form.gross_salary"
                                            type="number"
                                            readonly
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="activeTab === 'adjustments'"
                            class="col-md-4"
                        >
                            <div class="salary-info-body">
                                <div class="row g-3">
                                    <div
                                        class="col-md-12 d-flex align-items-center"
                                    >
                                        <Label
                                            label="Provident Fund"
                                            class="deductions-label"
                                        />
                                        <span class="colon">:</span>
                                        <NumberField
                                            v-model="form.provident_fund"
                                            type="number"
                                        />
                                    </div>
                                    <div
                                        class="col-md-12 d-flex align-items-center"
                                    >
                                        <Label
                                            label="Income Tax"
                                            class="deductions-label"
                                        />
                                        <span class="colon">:</span>
                                        <NumberField
                                            v-model="form.income_tax"
                                            type="number"
                                        />
                                    </div>
                                    <div
                                        class="col-md-12 d-flex align-items-center"
                                    >
                                        <Label
                                            label="Revenue Stamp"
                                            class="deductions-label"
                                        />
                                        <span class="colon">:</span>
                                        <NumberField
                                            v-model="form.revenue_stamp"
                                            type="number"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="net-salary">Net Salary : {{ form.net_pay }}</div>

                <div class="d-flex justify-content-between bottom-0 end-0 mt-3">
                    <div class="hold-salaries d-flex align-items-center m-0">
                        <label class="hold-salaries-label"
                            >Hold Salaries :
                        </label>
                        <input v-model="form.hold_status" type="checkbox" />
                    </div>
                    <div class="action-btn m-0 d-flex gap-3">
                        <button type="submit" class="btn btn-success btn-font">
                            UPDATE
                        </button>
                        <button
                            type="button"
                            @click="cancel"
                            class="btn btn-danger btn-font"
                        >
                            CANCEL
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.container {
    margin: 0;
    padding: 0;
}

.header-text {
    font-size: 18px;
    font-weight: bold;
    letter-spacing: 1px;
    font-family: system-ui;
}

.top-card {
    height: 155px;
}

.basic-info-rows {
    display: flex;
    align-items: center;
}

.basic-info-labels {
    width: 90px;
    font-size: 12px;
    padding: 4px;
    font-family: system-ui;
}

.basic-info-values {
    flex: 1;
    font-size: 12px;
    font-weight: 500;
    font-family: system-ui;
}

.basic-info-labels {
    font-size: 12px;
    font-family: system-ui;
}

.details-button {
    border-radius: 5px;
    background-color: rgb(255, 127, 127);
}

.bank-labels {
    width: 110px;
}

.increment-label {
    width: 165px;
}

.deductions-label {
    width: 115px;
}

.info-heading {
    font-size: 14px;
    font-weight: bold;
    letter-spacing: 1px;
}

.salary-info {
    margin-top: 10px;
}

.colon {
    width: 20px;
    text-align: center;
}

.net-salary {
    font-size: 25px;
    margin-top: 30px;
    align-items: center;
    background: #ffffff;
    font-weight: bold;
    color: rgb(1, 67, 1);
    font-family: system-ui;
    border-radius: 10px;
}

.tab-item {
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

/* hover effect */
.tab-item:hover {
    background-color: #f0f0f0;
}

/* active state */
.tab-item.active {
    background-color: #0d6efd;
    color: white;
}

.hold-salaries input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.hold-salaries-label {
    font-size: 16px;
    width: 110px;
}

.btn-font {
    font-size: 12px;
    letter-spacing: 1px;
}

.tab-menu {
    border-bottom: 1px solid #dee2e6;
}

.tab-items {
    position: relative;
    padding: 10px 0;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    color: #6c757d;
    transition: all 0.2s ease;
}

.tab-items:hover {
    color: #0d6efd;
}

.tab-items.active {
    color: #0d6efd;
    font-weight: 600;
}

.tab-items.active::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: -1px;
    width: 100%;
    height: 3px;
    background-color: #0d6efd;
    border-radius: 2px 2px 0 0;
}
</style>
