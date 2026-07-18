<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useRoute } from "vue-router";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;
const route = useRoute();

const monthPeriods = [
    "January 2026", "February 2026", "March 2026", "April 2026",
    "May 2026", "June 2026", "July 2026", "August 2026",
    "September 2026", "October 2026", "November 2026", "December 2026",
];

const selectedPeriod = ref("");
const selectedDepartments = ref([]);
const departments = ref([]);
const employees = ref([]);
const isLoadingRecord = ref(true);
const isLoadingDepartments = ref(true);
const isLoadingEmployees = ref(false);
const isSubmitting = ref(false);

const fetchRecord = async () => {
    isLoadingRecord.value = true;
    try {
        const res = await api.get(`/incentive-detail/${route.query.id}`);
        const { incentive, departments: depts } = res.data;

        selectedPeriod.value = incentive.incentive_periods;

        selectedDepartments.value = depts.map((d) => ({
            department_id: d.department_id,
            department_name: d.department_name,
        }));

        employees.value = incentive.details.map((d) => ({
            id: d.employee_id,
            Employee_Id: d.employee_id,
            Full_Name: d.employee_name,
            department_id: d.department_id,
            department_name: d.department_name,
            designation_name: d.designation_name,
            incentive_amount: d.amounts,
            remarks: d.remarks || "",
        }));
    } catch (err) {
        console.error("Error fetching record:", err);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Could not load incentive record.",
        });
    } finally {
        isLoadingRecord.value = false;
    }
};

const fetchDepartments = async () => {
    isLoadingDepartments.value = true;
    try {
        const res = await api.get(`/department-list/${user.id}`);
        departments.value = res.data;
    } catch (err) {
        console.error("Error fetching departments:", err);
    } finally {
        isLoadingDepartments.value = false;
    }
};

const fetchEmployees = async () => {
    if (!selectedDepartments.value.length) {
        employees.value = [];
        return;
    }
    isLoadingEmployees.value = true;
    try {
        const deptIds = selectedDepartments.value.map((d) => d.department_id).join(",");
        const res = await api.get(`/incentive-employees/${deptIds}`);

        const existingMap = {};
        employees.value.forEach((emp) => {
            existingMap[emp.id] = {
                incentive_amount: emp.incentive_amount,
                remarks: emp.remarks,
            };
        });

        employees.value = res.data.map((emp) => ({
            ...emp,
            incentive_amount: existingMap[emp.id]?.incentive_amount ?? "",
            remarks: existingMap[emp.id]?.remarks ?? "",
        }));
    } catch (err) {
        console.error("Error fetching employees:", err);
    } finally {
        isLoadingEmployees.value = false;
    }
};

watch(selectedDepartments, (newVal, oldVal) => {
    if (!isLoadingRecord.value && newVal.length !== oldVal.length) {
        fetchEmployees();
    }
});

const groupedEmployees = computed(() => {
    const groups = {};
    employees.value.forEach((emp) => {
        if (!groups[emp.department_id]) {
            groups[emp.department_id] = { name: emp.department_name, employees: [] };
        }
        groups[emp.department_id].employees.push(emp);
    });
    return Object.values(groups);
});

const totalAmount = computed(() =>
    employees.value.reduce((sum, emp) => sum + (parseFloat(emp.incentive_amount) || 0), 0)
);

const filledCount = computed(() =>
    employees.value.filter((emp) => emp.incentive_amount !== "" && parseFloat(emp.incentive_amount) > 0).length
);

const validationErrors = ref({});

const validate = () => {
    validationErrors.value = {};
    if (!selectedPeriod.value) validationErrors.value.period = "Please select a month period.";
    if (!selectedDepartments.value.length) validationErrors.value.department = "Please select at least one department.";
    if (!employees.value.length) validationErrors.value.employees = "No employees found for selected departments.";
    if (!employees.value.filter((e) => e.incentive_amount !== "" && parseFloat(e.incentive_amount) > 0).length) {
        validationErrors.value.amount = "Please enter incentive amount for at least one employee.";
    }
    return Object.keys(validationErrors.value).length === 0;
};

const update = async () => {
    if (!validate()) return;
    isSubmitting.value = true;

    const details = employees.value
        .filter((emp) => emp.incentive_amount !== "" && parseFloat(emp.incentive_amount) > 0)
        .map((emp) => ({
            employee_id: emp.id,
            department_id: emp.department_id,
            amounts: parseFloat(emp.incentive_amount),
            remarks: emp.remarks || "",
        }));

    try {
        const res = await api.put(`/incentive-update/${route.query.id}`, {
            incentive_periods: selectedPeriod.value,
            details,
        });

        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: "Success",
                text: res.data.message,
                timer: 2000,
                showConfirmButton: false,
            }).then(() => window.close());
        } else {
            Swal.fire({ icon: "error", title: "Error", text: res.data.message || "Something went wrong." });
        }
    } catch (err) {
        Swal.fire({ icon: "error", title: "Error", text: err.response?.data?.message || "Something went wrong." });
    } finally {
        isSubmitting.value = false;
    }
};

const cancel = () => window.close();

onMounted(async () => {
    await fetchDepartments();
    await fetchRecord();
});
</script>

<template>
    <div class="border rounded h-100 d-flex flex-column p-0 m-0">
        <div class="p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="m-0 fw-bold" style="letter-spacing: 0.5px;">
                    Edit Incentive
                </h4>
            </div>

            <!-- Loading Record -->
            <div v-if="isLoadingRecord" class="text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading incentive record...</p>
            </div>

            <template v-else>
                <!-- Controls Row -->
                <div class="row g-3 align-items-end mb-4">
                    <!-- Month Period -->
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold mb-1">
                            Month Period <span class="text-danger">*</span>
                        </label>
                        <select
                            class="form-select form-select-sm"
                            v-model="selectedPeriod"
                            :class="{ 'is-invalid': validationErrors.period }"
                        >
                            <option value="" disabled>Select Month Period</option>
                            <option v-for="period in monthPeriods" :key="period" :value="period">
                                {{ period }}
                            </option>
                        </select>
                        <div v-if="validationErrors.period" class="invalid-feedback">
                            {{ validationErrors.period }}
                        </div>
                    </div>

                    <!-- Department Multi-Select -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label fw-semibold mb-1">
                            Department <span class="text-danger">*</span>
                        </label>
                        <Multiselect
                            v-model="selectedDepartments"
                            :options="departments"
                            :multiple="true"
                            label="department_name"
                            track-by="department_id"
                            :searchable="true"
                            :close-on-select="false"
                            :clear-on-select="false"
                            :preserve-search="true"
                            :placeholder="isLoadingDepartments ? 'Loading departments...' : 'Select departments'"
                            :disabled="isLoadingDepartments"
                            class="multiselect-design"
                        >
                        </Multiselect>
                        <div v-if="validationErrors.department" class="text-danger mt-1" style="font-size: 0.8rem;">
                            {{ validationErrors.department }}
                        </div>
                    </div>

                    <!-- Total Amount Summary -->
                    <div class="col-lg-5 col-md-12">
                        <label class="form-label fw-semibold mb-1">&nbsp;</label>
                        <div class="summary-box d-flex align-items-center justify-content-between h-38 px-3">
                            <div class="d-flex align-items-center gap-4">
                                <div>
                                    <span class="summary-label">Total Amount</span>
                                    <span class="summary-value ms-2">{{ Number(totalAmount).toLocaleString() }}</span>
                                </div>
                                <div class="vr"></div>
                                <div>
                                    <span class="summary-label">Filled</span>
                                    <span class="summary-value ms-2">{{ filledCount }}</span>
                                </div>
                                <div class="vr"></div>
                                <div>
                                    <span class="summary-label">Employees</span>
                                    <span class="summary-value ms-2">{{ employees.length }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Validation Error Banner -->
                <div
                    v-if="validationErrors.employees || validationErrors.amount"
                    class="alert alert-warning py-2 mb-3 d-flex align-items-center"
                >
                    <span class="me-2 fw-bold">!</span>
                    {{ validationErrors.employees || validationErrors.amount }}
                </div>

                <!-- Employee Table -->
                <div class="table-responsive bg-light">
                    <div v-if="isLoadingEmployees" class="text-center py-5">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading employees...</p>
                    </div>

                    <div v-else-if="!employees.length" class="text-center py-5 text-muted">
                        <p class="mb-0">Select one or more departments to load employees.</p>
                    </div>

                    <table v-else class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 4%">SL</th>
                                <th class="text-center" style="width: 10%">EMP ID</th>
                                <th style="width: 22%">EMPLOYEE NAME</th>
                                <th style="width: 18%">DEPARTMENT</th>
                                <th style="width: 18%">DESIGNATION</th>
                                <th class="text-center" style="width: 14%">INCENTIVE AMOUNT</th>
                                <th style="width: 14%">REMARKS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="group in groupedEmployees" :key="group.name">
                                <tr class="dept-header-row">
                                    <td></td>
                                    <td colspan="6" class="fw-bold">
                                        {{ group.name }} ({{ group.employees.length }})
                                    </td>
                                </tr>
                                <tr v-for="emp in group.employees" :key="emp.id" class="text-nowrap">
                                    <td class="text-center">{{ employees.indexOf(emp) + 1 }}</td>
                                    <td class="text-center">ABNB{{ emp.Employee_Id }}</td>
                                    <td>{{ emp.Full_Name }}</td>
                                    <td>{{ emp.department_name }}</td>
                                    <td>{{ emp.designation_name }}</td>
                                    <td>
                                        <input
                                            type="number"
                                            class="form-control form-control-sm text-end"
                                            v-model="emp.incentive_amount"
                                            min="0"
                                            step="1"
                                            placeholder="0"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            v-model="emp.remarks"
                                            placeholder="Optional"
                                        />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Action Buttons -->
                <div v-if="employees.length" class="d-flex justify-content-end gap-2 mt-4">
                    <button
                        type="button"
                        class="btn btn-light btn-sm border px-3"
                        @click="cancel"
                        :disabled="isSubmitting"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="btn btn-dark btn-sm fw-semibold px-3"
                        @click="update"
                        :disabled="isSubmitting"
                    >
                        <span
                            v-if="isSubmitting"
                            class="spinner-border spinner-border-sm me-1"
                            role="status"
                        ></span>
                        {{ isSubmitting ? "Updating..." : "Update" }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>
.form-select,
.form-control {
    border-radius: 5px;
    font-size: 0.875rem;
}

.form-label {
    font-size: 0.825rem;
    color: #495057;
    letter-spacing: 0.3px;
}

.h-38 {
    height: 38px;
}

.summary-box {
    background: #f1f3f5;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}

.summary-label {
    font-size: 0.78rem;
    color: #868e96;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #212529;
}

.table {
    font-size: 0.82rem;
    margin-bottom: 0;
}

.table th {
    background: #343a40;
    color: #fff;
    font-weight: 600;
    font-size: 0.78rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    padding: 8px 10px;
    white-space: nowrap;
}

.table td {
    padding: 6px 10px;
    vertical-align: middle;
}

.table tbody tr:hover > td {
    background: #e9ecef !important;
}

.dept-header-row td {
    background: #e2e6ea !important;
    font-size: 0.82rem;
    letter-spacing: 0.5px;
    padding: 7px 10px;
    border-bottom: 2px solid #adb5bd !important;
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    opacity: 1;
}

@media (max-width: 576px) {
    .table td,
    .table th {
        font-size: 0.72rem;
        padding: 4px 6px;
    }
}
</style>

<style>
.multiselect-design .multiselect__tags {
    min-height: 38px !important;
    height: 38px;
    padding: 5px 35px 5px 10px;
    border-radius: 5px;
    border: 1px solid #ced4da;
    background: #fff;
    display: flex;
    align-items: center;
    font-size: 0.875rem;
}

.multiselect-design .multiselect__input,
.multiselect-design .multiselect__single {
    height: 100%;
    line-height: 38px;
    padding: 0;
    margin: 0;
    background: transparent;
    border: 0;
    flex: 1;
    font-size: 0.875rem;
}

.multiselect-design .multiselect__select {
    height: 38px;
}

.multiselect-design .multiselect__tag {
    background: #343a40;
    color: #fff;
    border-radius: 4px;
    padding: 3px 24px 3px 8px;
    font-size: 0.75rem;
    margin-bottom: 0;
}

.multiselect-design .multiselect__tag-icon {
    line-height: 18px;
}

.multiselect-design .multiselect__placeholder {
    color: #6c757d;
    font-size: 0.875rem;
}
</style>
