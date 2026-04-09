<script setup>
import { ref, defineProps, defineEmits, onMounted, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";

import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

const props = defineProps({
    isOpen: Boolean,
});

const emit = defineEmits(["modal-close"]);

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
const reportFormat = ref("pdf");

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
    if (!deptID) return (employee.value = []);

    try {
        const res = await api.get(`/adjustment-employees/${deptID}`);
        employee.value = res.data;
    } catch (err) {
        console.error(err);
    }
};

// ---------------- EVENTS ----------------
const onDeptChange = () => {
    selectedEmployee.value = null;
    emp_img.value = "";
    getEmployee(searchDept.value);
};

const onEmployeeChange = (emp) => {
    emp_img.value = emp?.image || "";
};

const clearDepartment = () => {
    searchDept.value = "";
    selectedEmployee.value = null;
    employee.value = [];
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
                <!-- Department -->
                <div class="form-group">
                    <label>Department</label>
                    <div class="d-flex gap-2">
                        <select v-model="searchDept" @change="onDeptChange">
                            <option value="">Select Department</option>
                            <option
                                v-for="dept in department"
                                :key="dept.department_id"
                                :value="dept.department_id"
                            >
                                {{ dept.department_name }}
                            </option>
                        </select>

                        <button v-if="searchDept" @click="clearDepartment">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Employee -->
                <div class="form-group">
                    <label>Employee</label>
                    <Multiselect
                        v-model="selectedEmployee"
                        :options="employee"
                        label="Full_Name"
                        track-by="id"
                        placeholder="Select employee"
                        @select="onEmployeeChange"
                    />
                </div>

                <!-- Employee Image -->
                <div class="text-center mb-3">
                    <img v-if="emp_img" :src="emp_img" class="emp-img" />
                </div>

                <!-- Date Range -->
                <div class="row">
                    <div class="col">
                        <label>From Date</label>
                        <input type="date" v-model="from_date" />
                    </div>

                    <div class="col">
                        <label>To Date</label>
                        <input type="date" v-model="to_date" />
                    </div>
                </div>

                <!-- Format -->
                <div class="form-group">
                    <label>Format</label>
                    <select v-model="reportFormat">
                        <option value="pdf">PDF</option>
                        <option value="xlsx">Excel</option>
                    </select>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer">
                <button class="btn cancel" @click="closeModal">Cancel</button>

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
    padding: 12px 16px;
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

.row {
    display: flex;
    gap: 10px;
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
</style>
