<script setup>
import { ref, onMounted, defineAsyncComponent } from "vue";
import api from "@/api";
import { useStore } from "vuex";

import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

const store = useStore();
const user = store.getters.getUser;

const department = ref([]);
const employee = ref([]);

const searchDept = ref("");
const selectedEmployee = ref(null);
const rosterReports = ref([]);
const searchDate = ref("");
const downloadReport = ref(false);

const rosterReportModal = defineAsyncComponent(
    () => import("@/pages/attendance/component/rosterReportDownload.vue"),
);

// -------------------------
// DATE FUNCTIONS
// -------------------------

const downloadRosterReport = () => {
    downloadReport.value = true;
};

const closeDownloadReport = () => {
    downloadReport.value = false;
};

const getTodayDate = () => {
    const today = new Date();
    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-US", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const formatTime = (timeStr) => {
    if (!timeStr) return "";
    const [hour, minute] = timeStr.split(":");

    const date = new Date();
    date.setHours(hour, minute);

    return date.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
};

// -------------------------
// GET EMPLOYEE
// -------------------------
const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        return;
    }

    try {
        const res = await api.get(`/adjustment-employees/${deptID}`);
        employee.value = res.data;
    } catch (err) {
        console.error("Error fetching employees:", err);
    }
};

// -------------------------
// GET DATA (MAIN API)
// -------------------------
const getData = async () => {
    try {
        const [deptRes, response] = await Promise.all([
            api.get(`/department-list/${user.id}`),
            api.get("/roster-report-list", {
                params: {
                    searchDate: searchDate.value,
                    department_id: searchDept.value,
                    employee_id: selectedEmployee.value?.id,
                },
            }),
        ]);

        department.value = deptRes.data || [];
        rosterReports.value = response.data.data ?? response.data;
    } catch (error) {
        console.error("Error fetching roster reports:", error);
    }
};

// -------------------------
// EVENTS
// -------------------------
const clearDepartment = async () => {
    searchDept.value = "";
    selectedEmployee.value = null;
    employee.value = [];
    await getData();
};

const onDeptChange = async () => {
    await getEmployee(searchDept.value);
    selectedEmployee.value = null;
    getData();
};

const onEmployeeChange = () => {
    getData();
};

const handleDateChange = () => {
    getData();
};

const isLateIn = (roster) => {
    if (!roster.in_diff_minutes) return false;

    // Late means positive delay (late entry)
    return roster.in_diff_minutes > 15;
};

const isEarlyOut = (roster) => {
    if (!roster.out_diff_minutes) return false;

    // Early خروج = negative (left before time)
    return roster.out_diff_minutes < 0;
};

// -------------------------
// INIT
// -------------------------
onMounted(() => {
    searchDate.value = getTodayDate();
    getData();
});
</script>

<template>
    <component
        v-if="downloadReport"
        :is="rosterReportModal"
        :isOpen="downloadReport"
        @modal-close="closeDownloadReport"
    />

    <div class="border rounded container-fluid d-flex flex-column p-0 m-0">
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="m-0 header-design">Daily Attendance</h2>
            <div class="d-flex flex-wrap gap-2 buttons-container">
                <button
                    @click="downloadRosterReport()"
                    class="btn btn-primary download-adjustment"
                >
                    <i class="fa-solid fa-download me-2"></i> Download
                </button>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="p-3">
                <!-- Filters -->
                <div class="row g-3 mb-4">
                    <!-- Department -->
                    <div class="col-lg-3 position-relative">
                        <label class="form-label">Select Department</label>
                        <select
                            class="form-control field-height field-background-colour"
                            :class="{ 'placeholder-text': !searchDept }"
                            v-model="searchDept"
                            @change="onDeptChange"
                        >
                            <option value="">Select Department</option>
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
                            type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 me-3 cross-icon"
                            @click="clearDepartment"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <!-- Employee -->
                    <div class="col-lg-3">
                        <label class="form-label">Select Employee</label>
                        <Multiselect
                            v-model="selectedEmployee"
                            :options="employee"
                            label="Full_Name"
                            track-by="id"
                            placeholder="Select employee"
                            class="multiselect-design"
                            @select="onEmployeeChange"
                        />
                    </div>

                    <!-- Date -->
                    <div class="col-lg-3">
                        <label class="form-label">Select Date</label>
                        <input
                            type="date"
                            class="form-control field-height field-background-colour"
                            v-model="searchDate"
                            :class="{ 'placeholder-text': !searchDept }"
                            @change="handleDateChange"
                        />
                    </div>
                </div>

                <div
                    class="d-flex align-items-center py-2 fw-semibold border-bottom"
                >
                    <span class="me-2">Total Employees:</span>
                    <span class="border rounded px-2 py-1 fw-medium">{{
                        rosterReports.length
                    }}</span>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr class="text-nowrap">
                                <th width="2%" class="text-center">S/N</th>
                                <th width="6%" class="text-center">EMP ID</th>
                                <th width="13%">EMPLOYEE NAME</th>
                                <th width="18%">DEPARTMENT</th>
                                <th width="7%" class="text-center">DATE</th>
                                <th width="5%" class="text-center">DAY</th>
                                <th width="5%" class="text-center">STATUS</th>
                                <th width="5%" class="text-center">IN</th>
                                <th width="5%" class="text-center">OUT</th>
                                <th width="7%" class="text-center">IN DIFF</th>
                                <th width="7%" class="text-center">OUT DIFF</th>
                                <th width="7%" class="text-center">WORKING</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(roster, index) in rosterReports"
                                :key="index"
                            >
                                <td class="text-center">{{ index + 1 }}</td>
                                <td class="text-center">
                                    ABNB-{{ roster.official_id }}
                                </td>
                                <td>{{ roster.employee_name }}</td>
                                <td>{{ roster.department_name }}</td>
                                <td class="text-center">
                                    {{ formatDate(roster.report_date) }}
                                </td>
                                <td class="text-center">
                                    {{ roster.day_name }}
                                </td>
                                <td class="text-center">
                                    {{ roster.attendance_status }}
                                </td>
                                <td
                                    class="text-center"
                                    :class="{
                                        'text-danger fw-bold': isLateIn(roster),
                                    }"
                                >
                                    {{ formatTime(roster.actual_in) }}
                                </td>

                                <td
                                    class="text-center"
                                    :class="{
                                        'text-danger fw-bold':
                                            isEarlyOut(roster),
                                    }"
                                >
                                    {{ formatTime(roster.actual_out) }}
                                </td>

                                <td class="text-center">
                                    {{ roster.in_diff }}
                                </td>
                                <td class="text-center">
                                    {{ roster.out_diff }}
                                </td>
                                <td class="text-center">
                                    {{ roster.working_hours }}
                                </td>
                            </tr>

                            <tr v-if="rosterReports.length === 0">
                                <td colspan="12" class="text-center text-muted">
                                    No Data Found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.table-responsive .table,
.table-responsive .table tbody,
.table-responsive .table tr,
.table-responsive .table td {
    /* background-color: rgba(234, 236, 234, 0.2) !important; */
    background: #ffffffd5 !important;
    color: #000000;
    border: 0.8px solid #7e7c7c63;
    letter-spacing: 0.3px;
}

.table tbody tr:hover > td {
    background-color: #717c73c7 !important;
}

.table thead th {
    background-color: #4e4e4e;
    font-weight: 700;
    color: #fff;
}

.table thead {
    height: 30px;
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
