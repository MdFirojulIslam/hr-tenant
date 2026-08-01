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
// DATE & TIME HELPERS
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
    if (!dateStr) return "-";
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-US", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const formatTime = (timeStr) => {
    if (!timeStr) return "--:--";
    const parts = timeStr.split(":");
    if (parts.length < 2) return timeStr;

    const hour = parseInt(parts[0], 10);
    const minute = parseInt(parts[1], 10);

    const date = new Date();
    date.setHours(hour, minute, 0, 0);

    return date.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
};

// -------------------------
// API CALLS
// -------------------------
const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        return;
    }

    try {
        const res = await api.get(`/adjustment-employees/${deptID}`);
        employee.value = res.data || [];
    } catch (err) {
        console.error("Error fetching employees:", err);
    }
};

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
        rosterReports.value = response.data.data ?? response.data ?? [];
    } catch (error) {
        console.error("Error fetching roster reports:", error);
    }
};

// -------------------------
// EVENT HANDLERS
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

// -------------------------
// CONDITIONAL STYLES & BADGES
// -------------------------
const isLateIn = (roster) => {
    if (!roster.in_diff_minutes) return false;
    return roster.in_diff_minutes > 15;
};

const isEarlyOut = (roster) => {
    if (!roster.out_diff_minutes) return false;
    return roster.out_diff_minutes < 0;
};

const getStatusBadgeClass = (status) => {
    if (!status) return "badge-secondary";
    const s = status.toLowerCase();
    if (s.includes("p") || s.includes("present")) return "badge-present";
    if (s.includes("a") || s.includes("absent")) return "badge-absent";
    if (s.includes("l") || s.includes("leave")) return "badge-leave";
    if (s.includes("off") || s.includes("holiday")) return "badge-off";
    return "badge-secondary";
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
    <!-- Modal Dynamic Component -->
    <component
        v-if="downloadReport"
        :is="rosterReportModal"
        :isOpen="downloadReport"
        @modal-close="closeDownloadReport"
    />

    <div class="attendance-page">
        <!-- Top Navigation / Header -->
        <header class="header-bar border-bottom">
            <div class="header-content">
                <div class="title-group">
                    <i class="fa-solid fa-clipboard-user header-icon"></i>
                    <div>
                        <h1 class="header-title">Daily Attendance Roster</h1>
                    </div>
                </div>
                <button
                    @click="downloadRosterReport"
                    class="btn btn-primary btn-download"
                >
                    <i class="fa-solid fa-file-arrow-down"></i>
                    <span>Export Roster</span>
                </button>
            </div>
        </header>

        <div class="main-body">
            <!-- Filter Section -->
            <div class="filter-card">
                <div class="filter-grid">
                    <!-- Department -->
                    <div class="field-group">
                        <label class="field-label">
                            <i class="fa-solid fa-building"></i> Department
                        </label>
                        <div class="input-wrapper">
                            <select
                                class="form-select custom-input"
                                :class="{ 'placeholder-active': !searchDept }"
                                v-model="searchDept"
                                @change="onDeptChange"
                            >
                                <option value="">All Departments</option>
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
                                class="btn-clear"
                                @click="clearDepartment"
                                title="Clear Selection"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Employee -->
                    <div class="field-group">
                        <label class="field-label">
                            <i class="fa-solid fa-user"></i> Employee
                        </label>
                        <Multiselect
                            v-model="selectedEmployee"
                            :options="employee"
                            label="Full_Name"
                            track-by="id"
                            placeholder="All Employees"
                            class="multiselect-custom"
                            @select="onEmployeeChange"
                            @remove="onEmployeeChange"
                        />
                    </div>

                    <!-- Date -->
                    <div class="field-group">
                        <label class="field-label">
                            <i class="fa-solid fa-calendar-day"></i> Attendance
                            Date
                        </label>
                        <input
                            type="date"
                            class="form-control custom-input"
                            v-model="searchDate"
                            @change="handleDateChange"
                        />
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-card">
                <div class="table-toolbar">
                    <div class="count-badge">
                        <i class="fa-solid fa-users"></i>
                        <span
                            >Total Records:
                            <strong>{{ rosterReports.length }}</strong></span
                        >
                    </div>
                </div>

                <!-- Table Container with strict grid visibility -->
                <div class="table-scroll-wrapper">
                    <table class="table table-bordered roster-table">
                        <thead>
                            <tr>
                                <th width="3%" class="text-center">#</th>
                                <th width="7%" class="text-center">
                                    <i class="fa-solid fa-id-badge"></i> ID
                                </th>
                                <th width="19%">
                                    <i class="fa-solid fa-user"></i> Name
                                </th>
                                <th width="22%">
                                    <i class="fa-solid fa-sitemap"></i>
                                    Department
                                </th>
                                <!-- <th width="9%" class="text-center">
                                   <i class="fa-solid fa-calendar"></i> Date
                               </th> -->
                                <!-- <th width="6%" class="text-center">Day</th> -->
                                <th width="8%" class="text-center">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    Status
                                </th>
                                <th width="8%" class="text-center">
                                    <i class="fa-solid fa-right-to-bracket"></i>
                                    In
                                </th>
                                <th width="8%" class="text-center">
                                    <i
                                        class="fa-solid fa-right-from-bracket"
                                    ></i>
                                    Out
                                </th>
                                <th width="8%" class="text-center">In Diff</th>
                                <th width="8%" class="text-center">Out Diff</th>
                                <th width="9%" class="text-center">
                                    <i class="fa-solid fa-clock"></i> Working
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(roster, index) in rosterReports"
                                :key="index"
                            >
                                <td class="text-center col-sn">
                                    {{ index + 1 }}
                                </td>
                                <td class="text-center col-emp-id">
                                    ABNB{{ roster.official_id }}
                                </td>
                                <td class="col-name">
                                    {{ roster.employee_name || "-" }}
                                </td>
                                <td class="col-dept">
                                    {{ roster.department_name || "-" }}
                                </td>
                                <!-- <td class="text-center text-nowrap col-date">
                                   {{ formatDate(roster.report_date) }}
                               </td>
                               <td class="text-center col-day">
                                   {{ roster.day_name }}
                               </td> -->
                                <td class="text-center">
                                    <span
                                        class="status-pill"
                                        :class="
                                            getStatusBadgeClass(
                                                roster.attendance_status,
                                            )
                                        "
                                    >
                                        {{ roster.attendance_status || "-" }}
                                    </span>
                                </td>

                                <!-- IN TIME -->
                                <td
                                    class="text-center time-cell"
                                    :class="{ 'flag-late': isLateIn(roster) }"
                                >
                                    <span>{{
                                        formatTime(roster.actual_in)
                                    }}</span>
                                    <i
                                        v-if="isLateIn(roster)"
                                        class="fa-solid fa-triangle-exclamation flag-icon"
                                        title="Late Check-in"
                                    ></i>
                                </td>

                                <!-- OUT TIME -->
                                <td
                                    class="text-center time-cell"
                                    :class="{
                                        'flag-early': isEarlyOut(roster),
                                    }"
                                >
                                    <span>{{
                                        formatTime(roster.actual_out)
                                    }}</span>
                                    <i
                                        v-if="isEarlyOut(roster)"
                                        class="fa-solid fa-triangle-exclamation flag-icon"
                                        title="Early Departure"
                                    ></i>
                                </td>

                                <td class="text-center diff-cell">
                                    {{ roster.in_diff || "-" }}
                                </td>
                                <td class="text-center diff-cell">
                                    {{ roster.out_diff || "-" }}
                                </td>
                                <td class="text-center col-worktime">
                                    {{ roster.working_hours || "-" }}
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="rosterReports.length === 0">
                                <td colspan="12" class="empty-cell">
                                    <div class="empty-box">
                                        <i
                                            class="fa-solid fa-folder-open empty-icon"
                                        ></i>
                                        <p>
                                            No attendance data found matching
                                            your query.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.attendance-page {
    background-color: #f1f5f9;
    min-height: 100vh;
    font-family:
        -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial,
        sans-serif;
    color: #1e293b;
}

/* Header Styling */
.header-bar {
    background-color: #ffffff;
    padding: 8px 16px;
    border-bottom: 1px solid #cbd5e1;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.title-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    font-size: 1.5rem;
    color: #2563eb;
    background: #eff6ff;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #bfdbfe;
}

.header-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.header-subtitle {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0;
}

.btn-download {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #2563eb;
    border: 1px solid #1d4ed8;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 16px;
    border-radius: 6px;
    color: #ffffff;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-download:hover {
    background-color: #1d4ed8;
}

/* Body & Filters */
.main-body {
    padding: 16px;
}

.filter-card {
    /* background-color: #ffffff;
   border: 1px solid #cbd5e1;
   border-radius: 6px;
   padding: 12px 16px; */
    margin-bottom: 16px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.field-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.field-label i {
    color: #64748b;
    margin-right: 4px;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.custom-input {
    height: 34px;
    border-radius: 4px;
    border: 1px solid #94a3b8;
    font-size: 0.85rem;
    padding: 4px 8px;
    color: #0f172a;
    width: 100%;
}

.custom-input:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.placeholder-active {
    color: #94a3b8;
}

.btn-clear {
    position: absolute;
    right: 8px;
    background: transparent;
    border: none;
    color: #64748b;
    cursor: pointer;
    font-size: 0.85rem;
}

.btn-clear:hover {
    color: #dc2626;
}

/* Vue Multiselect Tight Styling */
:deep(.multiselect-custom .multiselect__tags) {
    min-height: 34px !important;
    height: 34px;
    padding: 2px 8px;
    border-radius: 4px;
    border: 1px solid #94a3b8;
    display: flex;
    align-items: center;
    font-size: 0.85rem;
}

:deep(.multiselect-custom .multiselect__placeholder) {
    color: #94a3b8;
    font-size: 0.85rem;
    margin-bottom: 0;
}

:deep(.multiselect-custom .multiselect__single),
:deep(.multiselect-custom .multiselect__input) {
    font-size: 0.85rem;
    margin-bottom: 0;
    line-height: 26px;
}

:deep(.multiselect-custom .multiselect__select) {
    height: 32px;
}

/* Table Card & High-Visibility Border Grid */
.table-card {
    background: #ffffff;
    border: 1px solid #94a3b8;
    border-radius: 6px;
    overflow: hidden;
}

.table-toolbar {
    background-color: #f8fafc;
    border-bottom: 1px solid #cbd5e1;
    padding: 8px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.count-badge {
    font-size: 0.8rem;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 6px;
}

.count-badge i {
    color: #2563eb;
}

.table-scroll-wrapper {
    overflow-x: auto;
    width: 100%;
}

/* Dense Data Table Rules */
.roster-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: collapse !important;
    font-size: 0.825rem;
}

/* Grid Line Definitions */
.roster-table th,
.roster-table td {
    border: 1px solid #cbd5e1 !important; /* Visible Grid Borders */
    padding: 6px 8px !important;
    vertical-align: middle;
}

.roster-table thead th {
    background-color: #334155 !important;
    color: #ffffff !important;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.725rem;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.roster-table thead th i {
    opacity: 0.8;
    margin-right: 3px;
}

.roster-table tbody tr:nth-child(even) {
    background-color: #f8fafc;
}

.roster-table tbody tr:hover {
    background-color: #e2e8f0 !important;
}

/* Specific Columns Styling */
.col-sn {
    font-weight: 600;
    color: #64748b;
}

.col-emp-id {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 700;
    color: #1e40af;
}

.col-name {
    font-weight: 600;
    color: #0f172a;
}

.col-dept {
    color: #334155;
}

.col-date,
.col-day {
    color: #475569;
}

.col-worktime {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 700;
    color: #0f172a;
}

.diff-cell {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    color: #64748b;
    font-size: 0.775rem;
}

/* Status Badges */
.status-pill {
    display: inline-block;
    padding: 2px 8px;
    font-size: 0.7rem;
    font-weight: 700;
    border-radius: 3px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-present {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

.badge-absent {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.badge-leave {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde047;
}

.badge-off {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}

.badge-secondary {
    background-color: #e2e8f0;
    color: #475569;
}

/* Highlighted Alarm Cell Styles */
.time-cell {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 600;
}

.flag-late,
.flag-early {
    background-color: #fef2f2 !important;
    color: #dc2626 !important;
    font-weight: 800;
}

.flag-icon {
    margin-left: 4px;
    font-size: 0.75rem;
    color: #dc2626;
}

/* Empty State */
.empty-cell {
    padding: 32px !important;
    background-color: #ffffff !important;
}

.empty-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #64748b;
}

.empty-icon {
    font-size: 2rem;
    color: #94a3b8;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .header-bar {
        padding: 12px 16px;
    }

    .header-title {
        font-size: 1.1rem;
    }

    .main-body {
        padding: 8px;
    }

    .filter-card {
        padding: 10px;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .btn-download {
        width: 100%;
        justify-content: center;
    }

    .roster-table {
        font-size: 0.775rem;
    }

    .roster-table th,
    .roster-table td {
        padding: 4px 6px !important;
    }
}
</style>
