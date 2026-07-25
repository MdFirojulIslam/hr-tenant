<script setup>
import { ref, computed, watch, onMounted, nextTick } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import Label from "@/pages/components/label/Label.vue";
import SelectField from "@/pages/components/input_field/SelectField.vue";
import MultiselectDept from "@/pages/components/input_field/Basedept.vue";
import NumberField from "@/pages/components/input_field/NumberField.vue";

import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;

const userID = user.id;

// const monthPeriods = [
//     "January 2026",
//     "February 2026",
//     "March 2026",
//     "April 2026",
//     "May 2026",
//     "June 2026",
//     "July 2026",
//     "August 2026",
//     "September 2026",
//     "October 2026",
//     "November 2026",
//     "December 2026",
// ];

const selectedPeriod = ref("");
const selectedDepartments = ref([]);
const departments = ref([]);
const employees = ref([]);
const isLoadingDepartments = ref(true);
const isLoadingEmployees = ref(false);
const isSubmitting = ref(false);
const isImporting = ref(false);

const fetchDepartments = async () => {
    isLoadingDepartments.value = true;
    try {
        const res = await api.get(`/departments-incentive-list/${user.id}`);
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
        const deptIds = selectedDepartments.value
            .map((d) => d.department_id)
            .join(",");
        const res = await api.get(`/incentive-employees/${deptIds}`);
        employees.value = res.data.map((emp) => ({
            ...emp,
            incentive_amount: "",
            remarks: "",
        }));
    } catch (err) {
        console.error("Error fetching employees:", err);
    } finally {
        isLoadingEmployees.value = false;
    }
};

watch(selectedDepartments, () => {
    if (!isImporting.value) {
        fetchEmployees();
    }
});

const groupedEmployees = computed(() => {
    const groups = {};
    employees.value.forEach((emp) => {
        if (!groups[emp.department_id]) {
            groups[emp.department_id] = {
                name: emp.department_name,
                employees: [],
            };
        }
        groups[emp.department_id].employees.push(emp);
    });
    let sl = 0;
    return Object.values(groups).map((group) => ({
        ...group,
        employees: group.employees.map((emp) => ({ emp, sl: ++sl })),
    }));
});

const totalAmount = computed(() =>
    employees.value.reduce(
        (sum, emp) => sum + (parseFloat(emp.incentive_amount) || 0),
        0,
    ),
);

const filledCount = computed(
    () =>
        employees.value.filter(
            (emp) =>
                emp.incentive_amount !== "" &&
                parseFloat(emp.incentive_amount) > 0,
        ).length,
);

const validationErrors = ref({});

const validate = () => {
    validationErrors.value = {};
    if (!selectedPeriod.value)
        validationErrors.value.period = "Please select a month period.";
    if (!selectedDepartments.value.length)
        validationErrors.value.department =
            "Please select at least one department.";
    if (!employees.value.length)
        validationErrors.value.employees =
            "No employees found for selected departments.";
    if (
        !employees.value.filter(
            (e) =>
                e.incentive_amount !== "" && parseFloat(e.incentive_amount) > 0,
        ).length
    ) {
        validationErrors.value.amount =
            "Please enter incentive amount for at least one employee.";
    }
    return Object.keys(validationErrors.value).length === 0;
};

const generate = async () => {
    if (!validate()) return;
    isSubmitting.value = true;
    const details = employees.value
        .filter(
            (emp) =>
                emp.incentive_amount !== "" &&
                parseFloat(emp.incentive_amount) > 0,
        )
        .map((emp) => ({
            employee_id: emp.id,
            department_id: emp.department_id,
            amounts: parseFloat(emp.incentive_amount),
            remarks: emp.remarks || "",
        }));
    try {
        const res = await api.post("/incentive-store", {
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
            Swal.fire({
                icon: "error",
                title: "Error",
                text: res.data.message || "Something went wrong.",
            });
        }
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: err.response?.data?.message || "Something went wrong.",
        });
    } finally {
        isSubmitting.value = false;
    }
};

const isUploading = ref(false);
const isDownloading = ref(false);

const downloadTemplate = async () => {
    isDownloading.value = true;
    try {
        const params = new URLSearchParams();
        if (selectedPeriod.value) params.append("period", selectedPeriod.value);
        if (selectedDepartments.value.length) {
            const deptIds = selectedDepartments.value
                .map((d) => d.department_id)
                .join(",");
            params.append("deptIds", deptIds);
        }

        const token = localStorage.getItem("token");
        const url = `/api/incentive-download-template${params.toString() ? "?" + params.toString() : ""}`;
        const res = await fetch(url, {
            headers: { Authorization: `Bearer ${token}` },
        });

        if (!res.ok) {
            const err = await res.json();
            throw new Error(err.message || "Download failed");
        }

        const blob = await res.blob();
        const ext = "xlsx";
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = `Incentive_Template${selectedPeriod.value ? "_" + selectedPeriod.value.replace(/\s/g, "_") : ""}.${ext}`;
        a.click();
        URL.revokeObjectURL(a.href);
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Download Failed",
            text:
                err.message || "Could not download template. Please try again.",
        });
    } finally {
        isDownloading.value = false;
    }
};

const uploadExcel = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const validTypes = [
        "application/vnd.ms-excel",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    ];
    if (!validTypes.includes(file.type) && !file.name.match(/\.xlsx?$/i)) {
        Swal.fire({
            icon: "error",
            title: "Invalid File",
            text: "Please upload a valid Excel file (.xls or .xlsx).",
        });
        event.target.value = "";
        return;
    }

    isUploading.value = true;
    isImporting.value = true;
    try {
        const formData = new FormData();
        formData.append("file", file);

        const res = await api.post("/incentive-import-excel", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        if (res.data.success && res.data.data) {
            const importData = res.data.data;

            const importMap = {};
            importData.forEach((row) => {
                importMap[row.id] = {
                    incentive_amount: row.incentive_amount,
                    remarks: row.remarks || "",
                };
            });

            if (employees.value.length > 0) {
                let matchedCount = 0;
                employees.value.forEach((emp) => {
                    const data = importMap[emp.id];
                    if (data) {
                        matchedCount++;
                        emp.incentive_amount = data.incentive_amount;
                        emp.remarks = data.remarks;
                    }
                });

                await nextTick();

                Swal.fire({
                    icon: "success",
                    title: "Import Successful",
                    text: `${matchedCount} employee(s) updated from Excel.`,
                    timer: 2000,
                    showConfirmButton: false,
                });
            } else {
                const loadedEmployees = importData.map((row) => ({
                    id: row.id,
                    Employee_Id: row.Employee_Id,
                    Full_Name: row.Full_Name,
                    department_id: row.department_id,
                    department_name: row.department_name,
                    designation_name: row.designation_name,
                    incentive_amount: row.incentive_amount,
                    remarks: row.remarks || "",
                }));
                employees.value = loadedEmployees;

                const deptMap = {};
                importData.forEach((row) => {
                    if (
                        row.department_id &&
                        row.department_name &&
                        !deptMap[row.department_id]
                    ) {
                        deptMap[row.department_id] = {
                            department_id: row.department_id,
                            department_name: row.department_name,
                        };
                    }
                });
                const matchedDepts = departments.value.filter(
                    (d) => deptMap[d.department_id],
                );
                if (matchedDepts.length) {
                    selectedDepartments.value = matchedDepts;
                }

                await nextTick();

                Swal.fire({
                    icon: "success",
                    title: "Import Successful",
                    text: `${employees.value.length} employee(s) loaded from Excel.`,
                    timer: 2000,
                    showConfirmButton: false,
                });
            }
        } else {
            throw new Error(res.data.message || "Import failed");
        }
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Import Failed",
            text:
                err.response?.data?.message ||
                err.message ||
                "Could not process Excel file.",
        });
    } finally {
        isImporting.value = false;
        isUploading.value = false;
        event.target.value = "";
    }
};

const cancel = () => window.close();

onMounted(() => {
    fetchDepartments();
});
</script>

<template>
    <div class="border rounded h-100 d-flex flex-column p-0 m-0">
        <!-- Header -->
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="m-0 header-design">Incentive Setup Form</h2>
        </div>

        <!-- Controls Row -->
        <div class="p-3">
            <div class="d-flex gap-4">
                <!-- Month Period -->
                <div class="">
                    <Label label="Month Period*" class="month-period-labels" />
                    <!-- <select
                        class="form-select form-select-sm"
                        v-model="selectedPeriod"
                        :class="{ 'is-invalid': validationErrors.period }"
                    >
                        <option value="" disabled>Select Month Period</option>
                        <option
                            v-for="period in monthPeriods"
                            :key="period"
                            :value="period"
                        >
                            {{ period }}
                        </option>
                    </select> -->
                    <!-- "January 2026", "February 2026", "March 2026", "April 2026",
                    "May 2026", "June 2026", "July 2026", "August 2026",
                    "September 2026", "October 2026", "November 2026", "December
                    2026", -->
                    <SelectField
                        v-model="selectedPeriod"
                        className="incnetive-select-field"
                        :items="[
                            { name: 'January 2026', value: 'January 2026' },
                            { name: 'February 2026', value: 'February 2026' },
                            { name: 'March 2026', value: 'March 2026' },
                            { name: 'April 2026', value: 'April 2026' },
                            { name: 'May 2026', value: 'May 2026' },
                            { name: 'June 2026', value: 'June 2026' },
                            { name: 'July 2026', value: 'July 2026' },
                            { name: 'August 2026', value: 'August 2026' },
                            { name: 'September 2026', value: 'September 2026' },
                            { name: 'October 2026', value: 'October 2026' },
                            { name: 'November 2026', value: 'November 2026' },
                            { name: 'December 2026', value: 'December 2026' },
                        ]"
                    />
                    <div
                        v-if="validationErrors.period"
                        class="invalid-feedback"
                    >
                        {{ validationErrors.period }}
                    </div>
                </div>

                <!-- Department Multi-Select -->
                <div class="col-lg-3">
                    <Label
                        label="Department Select*"
                        class="department-select-labels"
                    />
                    <!-- <MultiselectDept
                        v-model:departmentID="department_id"
                        :userID="userID"
                    /> -->

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
                        :placeholder="
                            isLoadingDepartments
                                ? 'Loading departments...'
                                : 'Select departments'
                        "
                        :disabled="isLoadingDepartments"
                        class="multiselect-design"
                    >
                    </Multiselect>
                    <div
                        v-if="validationErrors.department"
                        class="text-danger mt-1"
                        style="font-size: 0.8rem"
                    >
                        {{ validationErrors.department }}
                    </div>
                </div>

                <!-- Total Amount Summary -->
                <div class="col-lg-3 col-md-12">
                    <label class="form-label fw-semibold mb-2">&nbsp;</label>
                    <div
                        class="summary-box d-flex align-items-center justify-content-between h-38 px-3"
                    >
                        <div class="d-flex align-items-center gap-4">
                            <div>
                                <span class="summary-label">Total Amount</span>
                                <span class="summary-value ms-2">{{
                                    Number(totalAmount).toLocaleString()
                                }}</span>
                            </div>
                            <div class="vr"></div>
                            <div>
                                <span class="summary-label">Filled</span>
                                <span class="summary-value ms-2">{{
                                    filledCount
                                }}</span>
                            </div>
                            <div class="vr"></div>
                            <div>
                                <span class="summary-label">Employees</span>
                                <span class="summary-value ms-2">{{
                                    employees.length
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Download & Excel Upload Row -->
            <div class="d-flex gap-4 mt-3">
                <div>
                    <label class="form-label fw-semibold mb-1"
                        >Download Template</label
                    >
                    <button
                        type="button"
                        class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 px-3"
                        @click="downloadTemplate"
                        :disabled="isDownloading"
                    >
                        <span
                            v-if="isDownloading"
                            class="spinner-border spinner-border-sm"
                            role="status"
                        ></span>
                        <i v-else class="fa-solid fa-download"></i>
                        {{
                            isDownloading
                                ? "Downloading..."
                                : "Download Template"
                        }}
                    </button>
                </div>
                <div>
                    <label class="form-label fw-semibold mb-1"
                        >Upload Excel (If Have)</label
                    >
                    <input
                        type="file"
                        class="form-control form-control-sm"
                        accept=".xls,.xlsx"
                        @change="uploadExcel($event)"
                        :disabled="isUploading"
                        style="width: 260px"
                    />
                </div>
                <div v-if="isUploading" class="d-flex align-items-end">
                    <span class="text-muted" style="font-size: 0.82rem">
                        <span
                            class="spinner-border spinner-border-sm me-1"
                            role="status"
                        ></span>
                        Processing Excel...
                    </span>
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
            <div class="mt-5">
                <div class="table-responsive bg-light">
                    <!-- Loading -->
                    <div v-if="isLoadingEmployees" class="text-center py-5">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading employees...</p>
                    </div>

                    <!-- Empty -->
                    <div
                        v-else-if="!employees.length"
                        class="text-center py-5 text-muted"
                    >
                        <p class="mb-0">
                            Select one or more departments to load employees.
                        </p>
                    </div>

                    <!-- Table -->
                    <table v-else class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 4%">
                                    SL
                                </th>
                                <th class="text-center" style="width: 10%">
                                    EMP ID
                                </th>
                                <th style="width: 22%">EMPLOYEE NAME</th>
                                <th style="width: 18%">DEPARTMENT</th>
                                <th style="width: 18%">DESIGNATION</th>
                                <th class="text-center" style="width: 14%">
                                    INCENTIVE AMOUNT
                                </th>
                                <th style="width: 14%">REMARKS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template
                                v-for="group in groupedEmployees"
                                :key="group.name"
                            >
                                <tr class="dept-header-row">
                                    <td></td>
                                    <td colspan="6" class="fw-bold">
                                        {{ group.name }} ({{
                                            group.employees.length
                                        }})
                                    </td>
                                </tr>
                                <tr
                                    v-for="item in group.employees"
                                    :key="item.emp.id"
                                    class="text-nowrap"
                                >
                                    <td class="text-center">
                                        {{ item.sl }}
                                    </td>
                                    <td class="text-center">
                                        ABNB{{ item.emp.Employee_Id }}
                                    </td>
                                    <td>{{ item.emp.Full_Name }}</td>
                                    <td>{{ item.emp.department_name }}</td>
                                    <td>{{ item.emp.designation_name }}</td>
                                    <td>
                                        <!-- <input
                                            type="number"
                                            class="form-control form-control-sm text-end"
                                            v-model="emp.incentive_amount"
                                            min="0"
                                            step="1"
                                            placeholder="0"
                                        /> -->
                                        <NumberField
                                            v-model="item.emp.incentive_amount"
                                            type="number"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            v-model="item.emp.remarks"
                                            placeholder="Optional"
                                        />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Action Buttons -->
            <div
                v-if="employees.length"
                class="d-flex justify-content-end gap-2 mt-4"
            >
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
                    @click="generate"
                    :disabled="isSubmitting"
                >
                    <span
                        v-if="isSubmitting"
                        class="spinner-border spinner-border-sm me-1"
                        role="status"
                    ></span>
                    {{ isSubmitting ? "Generating..." : "Generate" }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.header-design {
    display: inline-flex;
    align-items: center;
    letter-spacing: 1px;
    padding-top: 5px;
    color: #000000;
}

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
    border: 0.8px solid rgb(196, 194, 194);
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
    /* font-size: 0.78rem; */
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
    height: auto;

    display: flex;
    flex-wrap: wrap;
    align-items: center;

    padding: 5px 35px 5px 10px;
    border-radius: 5px;
    border: 1px solid #ced4da;
    background: #fff;
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
