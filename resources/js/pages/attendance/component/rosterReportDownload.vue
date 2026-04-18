<script setup>
import { ref, defineProps, defineEmits, onMounted, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";

import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

// ---------------- PROPS / EMIT ----------------
const props = defineProps({
    isOpen: Boolean,
});
const emit = defineEmits(["modal-close"]);

// ---------------- STORE ----------------
const store = useStore();
const user = store.getters.getUser;

// ---------------- STATE ----------------
const department = ref([]);
const employee = ref([]);
const selectedEmployee = ref(null);
const emp_img = ref("");

const searchDept = ref("");
const from_date = ref("");
const to_date = ref("");
const reportFormat = ref("xlsx");

const loading = ref(false);

// ---------------- API ----------------
const getData = async () => {
    try {
        const res = await api.get(`/department-list/${user.id}`);
        department.value = res.data;
    } catch (err) {
        console.error(err);
    }
};

const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        return;
    }

    try {
        const res = await api.get(`/adjustment-employees/${deptID}`);
        employee.value = res.data;
    } catch (err) {
        console.error(err);
    }
};

// ✅ FIXED: Get employee image from API
const getEmployeeImg = async (empId) => {
    try {
        const res = await api.get(`/empimg/${empId}`);
        emp_img.value = res.data?.img_url || "";
    } catch (err) {
        console.error(err);
        emp_img.value = "";
    }
};

// ---------------- EVENTS ----------------
const onDeptChange = () => {
    selectedEmployee.value = null;
    emp_img.value = "";
    getEmployee(searchDept.value);
};

const onEmployeeChange = async (emp) => {
    if (!emp) {
        emp_img.value = "";
        return;
    }

    await getEmployeeImg(emp.id);
};

const clearDepartment = () => {
    searchDept.value = "";
    selectedEmployee.value = null;
    employee.value = [];
    emp_img.value = "";
};

// ---------------- VALIDATION ----------------
const isValid = computed(() => {
    return from_date.value && to_date.value;
});

// ---------------- ACTIONS ----------------
const closeModal = () => emit("modal-close");

const downloadReport = async () => {
    if (!isValid.value) {
        Swal.fire("Warning", "Select date range", "warning");
        return;
    }

    if (from_date.value > to_date.value) {
        Swal.fire("Warning", "Invalid date range", "warning");
        return;
    }

    try {
        loading.value = true;

        const response = await api.get("/roster-report-download", {
            params: {
                department_id: searchDept.value,
                employee_id: selectedEmployee.value?.id,
                from_date: from_date.value,
                to_date: to_date.value,
                format: reportFormat.value,
            },
            responseType: "blob",
        });

        const blob = new Blob([response.data]);
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);

        link.download =
            reportFormat.value === "pdf"
                ? "attendance_report.pdf"
                : "attendance_report.xlsx";

        link.click();
        closeModal();
    } catch (error) {
        Swal.fire("Error", "Download failed", "error");
    } finally {
        loading.value = false;
    }
};

// ---------------- INIT ----------------
onMounted(() => {
    getData();
});
</script>

<template>
    <div v-if="isOpen" class="modal-overlay">
        <div class="modal-container">
            <!-- HEADER -->
            <div class="modal-header">
                <h5>Attendance Report</h5>
                <button class="close-btn" @click="closeModal">✕</button>
            </div>

            <!-- BODY -->
            <div class="modal-body">
                <div class="row">
                    <!-- Department + Employee -->
                    <div class="row align-items-start">
                        <div class="col-md-8">
                            <!-- Department -->
                            <div class="form-group">
                                <label class="form-label">Department</label>

                                <div class="d-flex gap-2">
                                    <select
                                        v-model="searchDept"
                                        @change="onDeptChange"
                                        class="field-background-colour"
                                        :class="{
                                            'placeholder-text': !searchDept,
                                        }"
                                    >
                                        <option value="">
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

                                    <button
                                        v-if="searchDept"
                                        @click="clearDepartment"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>

                            <!-- Employee -->
                            <div class="form-group">
                                <label class="form-label">Employee</label>

                                <Multiselect
                                    v-model="selectedEmployee"
                                    :options="employee"
                                    label="Full_Name"
                                    track-by="id"
                                    placeholder="Select employee"
                                    @select="onEmployeeChange"
                                    class="multiselect-design"
                                />
                            </div>
                        </div>

                        <!-- IMAGE -->
                        <div class="text-center col-md-4 mt-4">
                            <img
                                :src="emp_img || '/default-avatar.png'"
                                class="emp-img"
                            />
                        </div>
                    </div>

                    <!-- DATE -->
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="form-label">From Date</label>
                            <input
                                type="date"
                                v-model="from_date"
                                class="form-control field-background-colour"
                            />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">To Date</label>
                            <input
                                type="date"
                                v-model="to_date"
                                class="form-control field-background-colour"
                            />
                        </div>
                    </div>

                    <!-- FORMAT -->
                    <div class="col-md-6 mt-2">
                        <label class="form-label">Format</label>
                        <select v-model="reportFormat" class="form-control">
                            <option value="xlsx">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>

                    <!-- BUTTON -->
                    <div class="col-md-6 text-end mt-5">
                        <button class="btn cancel me-3" @click="closeModal">
                            Cancel
                        </button>

                        <button
                            class="btn primary"
                            :disabled="loading"
                            @click="downloadReport"
                        >
                            {{ loading ? "Downloading..." : "Download" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-container {
    width: 500px;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.modal-header {
    background: #2f855a;
    color: white;
    padding: 10px 10px;
    display: flex;
    justify-content: space-between;
}

.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
}

.modal-body {
    padding: 16px;
}

.form-group {
    margin-bottom: 15px;
}

input,
select {
    width: 100%;
    padding: 6px;
}

.emp-img {
    width: 100px;
    height: 100px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

.modal-footer {
    padding: 12px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
}

.cancel {
    background: #ccc;
}

.primary {
    background: #2f855a;
    color: white;
}

.form-label {
    font-weight: 600;
    letter-spacing: 0.4px;
}

.field-hover:hover {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.field-height {
    height: 35px;
}

.placeholder-text {
    color: #999;
    font-size: 14px;
}

.cross-icon {
    margin-top: 29px;
}

.multiselect-design .multiselect__placeholder {
    color: #999;
    font-size: 14px;
    margin-bottom: 0px;
    align-items: center;
}

.multiselect-design .multiselect__tags {
    min-height: 35px !important;
    height: 35px;
    padding: 0 8px;
    border-radius: 5px;
    border: 0.8px solid #b3a9a963;
    display: flex;
    align-items: center;
    font-size: 14px;
}

/* Input inside the multiselect */
.multiselect-design .multiselect__input,
.multiselect-design .multiselect__single {
    height: 100%;
    line-height: 35px;
    padding: 0;
    margin: 0;
    background: transparent;
    border: 0;
    flex: 1;
}

.multiselect-design .multiselect__select {
    height: 35px;
}

.field-background-colour {
    border: 0.8px solid #b3a9a963;
}

.header-design {
    letter-spacing: 1px;
    padding-top: 5px;
    font-weight: 700;
}
</style>
