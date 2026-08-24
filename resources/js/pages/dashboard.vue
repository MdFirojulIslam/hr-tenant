<script setup>
import { ref, onMounted, watch, computed } from "vue";
import api from "@/api";
import dashboardChart from "./commonComponent/dashboardChart.vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

// Dynamic Data State
const dataMode = ref("all");
const dashCount = ref({});
const dashattendanceData = ref([]);
const dashattendanceDataByDept = ref({});
const departmentList = ref([]);

// Controls
const dateInput = ref(null);
const selectedDepartment = ref(null);
const currentDate = ref("");
const selectedDate = ref("");

const openCalendar = () => {
    if (dateInput.value) {
        dateInput.value.showPicker();
    }
};

const initializeChart = async () => {
    try {
        const dashCountres = await api.get("/dashboard-count");
        dashCount.value = dashCountres.data;
    } catch (error) {
        console.error("Error fetching dashboard count:", error);
    }
};

const setCurrentDate = () => {
    const today = new Date();
    selectedDate.value = today.toISOString().split("T")[0];
    formatDateLabel(today);
};

const formatDateLabel = (dateObj) => {
    currentDate.value = dateObj.toLocaleDateString("en-US", {
        month: "short",
        day: "2-digit",
        year: "numeric",
    });
};

watch(selectedDate, (newDate) => {
    if (!newDate) return;
    formatDateLabel(new Date(newDate));
    fetchAttendanceData();
});

watch(selectedDepartment, () => {
    fetchAttendanceData();
});

const fetchAttendanceData = () => {
    if (selectedDepartment.value && selectedDepartment.value.id !== 0) {
        dataMode.value = "dept";
        getDataByDept();
    } else {
        dataMode.value = "all";
        getData();
    }
};

const getDataByDept = async () => {
    try {
        const response = await api.get("/dashboard-attendance-data-by-dept", {
            params: {
                date: selectedDate.value,
                department_id: selectedDepartment.value
                    ? selectedDepartment.value.id
                    : null,
            },
        });
        dashattendanceDataByDept.value = response.data;
    } catch (error) {
        console.error("Error fetching data by department:", error);
    }
};

const getData = async () => {
    try {
        const [responseDept, responseAttendanceData] = await Promise.all([
            api.get("/dashboard-department-list"),
            api.get("/dashboard-attendance-data", {
                params: { date: selectedDate.value },
            }),
        ]);
        departmentList.value = responseDept.data;
        dashattendanceData.value = responseAttendanceData.data;
    } catch (error) {
        console.error("Error fetching dashboard data:", error);
    }
};

const activeAttendanceData = computed(() => {
    return dataMode.value === "dept"
        ? dashattendanceDataByDept.value || {}
        : dashattendanceData.value || {};
});

onMounted(() => {
    initializeChart();
    setCurrentDate();
});
</script>

<template>
    <main class="dashboard-wrapper">
        <div class="container-fluid px-4 py-3">
            <!-- Header Section -->
            <header
                class="dashboard-header d-flex align-items-center justify-content-between mb-4"
            >
                <div class="d-flex align-items-center gap-1">
                    <div class="icon-badge">
                        <i class="fa-solid fa-gauge-high"></i>
                    </div>
                    <div>
                        <h1 class="dashboard-title">Dashboard Overview</h1>
                    </div>
                </div>
            </header>

            <!-- Filter Controls Bar -->
            <section class="filter-card mb-4">
                <div class="row g-3 align-items-center">
                    <!-- Date Selector -->
                    <div class="col-12 col-md-5 col-lg-4">
                        <div
                            class="control-box d-flex align-items-center justify-content-between"
                            @click="openCalendar"
                        >
                            <div>
                                <span class="control-label d-block"
                                    >Selected Date</span
                                >
                                <span class="control-value">{{
                                    currentDate || "Select Date"
                                }}</span>
                            </div>
                            <div class="calendar-trigger">
                                <i class="fa-regular fa-calendar-days"></i>
                                <input
                                    type="date"
                                    ref="dateInput"
                                    v-model="selectedDate"
                                    class="hidden-date-input"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Department Selector -->
                    <div class="col-12 col-md-5 col-lg-4">
                        <div class="control-box multiselect-wrapper">
                            <multiselect
                                v-model="selectedDepartment"
                                :options="departmentList"
                                label="Name"
                                track-by="id"
                                :searchable="true"
                                :close-on-select="true"
                                :allow-empty="true"
                                placeholder="All Departments"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Metrics Grid -->
            <section class="metrics-grid mb-4">
                <!-- Card 1: Total Employees -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Total Employees</span>
                        <i class="fa-solid fa-users metric-icon"></i>
                    </div>
                    <div class="metric-body">
                        <div class="primary-stat">
                            {{ activeAttendanceData.active_employee || 0 }}
                        </div>
                        <div class="sub-stat-group mt-2">
                            <span class="lob-badge">
                                <strong>{{
                                    activeAttendanceData.gulshan_office_total_employee ||
                                    0
                                }}</strong>
                                <small>G</small>
                            </span>
                            <span class="lob-badge">
                                <strong>{{
                                    activeAttendanceData.warehouse_total_employee ||
                                    0
                                }}</strong>
                                <small>W</small>
                            </span>
                            <span class="lob-badge">
                                <strong>{{
                                    activeAttendanceData.field_force_total_employee ||
                                    0
                                }}</strong>
                                <small>F</small>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Attendance Split -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Attendance</span>
                        <i
                            class="fa-solid fa-user-check metric-icon text-success"
                        ></i>
                    </div>
                    <div class="metric-body">
                        <div class="stat-row">
                            <span class="stat-label text-success"
                                ><i class="fa-solid fa-circle me-1 small"></i>
                                Present</span
                            >
                            <span class="stat-value">
                                {{ activeAttendanceData.total_present || 0 }}
                                <small class="text-muted"
                                    >({{
                                        activeAttendanceData.avg_present || 0
                                    }}%)</small
                                >
                            </span>
                        </div>
                        <div class="stat-row mt-2">
                            <span class="stat-label text-danger"
                                ><i class="fa-solid fa-circle me-1 small"></i>
                                Absent</span
                            >
                            <span class="stat-value">
                                {{ activeAttendanceData.total_absent || 0 }}
                                <small class="text-muted"
                                    >({{
                                        activeAttendanceData.avg_absent || 0
                                    }}%)</small
                                >
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Working Hours & Late -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Work Hours & Late</span>
                        <i
                            class="fa-solid fa-clock metric-icon text-warning"
                        ></i>
                    </div>
                    <div class="metric-body">
                        <div class="stat-row">
                            <span class="stat-label">Late Arrivals</span>
                            <span class="stat-value text-warning">
                                {{ activeAttendanceData.total_late || 0 }}
                                <small class="text-muted"
                                    >({{
                                        activeAttendanceData.avg_late || 0
                                    }}%)</small
                                >
                            </span>
                        </div>
                        <div class="stat-row mt-2">
                            <span class="stat-label">Avg. Work Hours</span>
                            <span class="stat-value">
                                {{
                                    activeAttendanceData.average_work_hours || 0
                                }}
                                hrs
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Leave Summary -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Leave Status</span>
                        <i class="fa-solid fa-plane-departure metric-icon"></i>
                    </div>
                    <div class="metric-body">
                        <div class="stat-row">
                            <span class="stat-label">Approved</span>
                            <span class="stat-value text-success">
                                {{ activeAttendanceData.total_leave || 0 }}
                            </span>
                        </div>
                        <div class="stat-row mt-2">
                            <span class="stat-label">Pending</span>
                            <span class="stat-value text-warning">
                                {{
                                    activeAttendanceData.total_pending_leave ||
                                    0
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card 5: Average Tenure -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Average Tenure</span>
                        <i class="fa-solid fa-award metric-icon"></i>
                    </div>
                    <div class="metric-body">
                        <div class="primary-stat">
                            {{ activeAttendanceData.averageTenure || 0 }}
                            <small class="unit">Years</small>
                        </div>
                    </div>
                </div>

                <!-- Card 6: TA/DA Expense -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">TA/DA (MTD)</span>
                        <i class="fa-solid fa-receipt metric-icon"></i>
                    </div>
                    <div class="metric-body">
                        <div class="primary-stat highlight-currency">
                            {{ activeAttendanceData.total_expense || 0 }}
                            <small class="unit">BDT</small>
                        </div>
                    </div>
                </div>

                <!-- Card 7: Department Count -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Departments</span>
                        <i class="fa-solid fa-sitemap metric-icon"></i>
                    </div>
                    <div class="metric-body">
                        <div class="primary-stat">
                            {{ activeAttendanceData.department_total || 0 }}
                        </div>
                    </div>
                </div>

                <!-- Card 8: Average Age -->
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Average Age</span>
                        <i class="fa-solid fa-cake-candles metric-icon"></i>
                    </div>
                    <div class="metric-body">
                        <div class="primary-stat">
                            {{ activeAttendanceData.averageAge || 0 }}
                            <small class="unit">Years</small>
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">
                            <i
                                class="fa-solid fa-file-shield metric-icon me-2"
                            ></i>
                            Document Compliance
                        </span>
                    </div>
                    <div class="metric-body">
                        <div class="stat-row">
                            <span class="stat-label">
                                <i
                                    class="fa-solid fa-user-check text-muted me-1"
                                ></i>
                                Compliant Employees
                            </span>
                            <span class="stat-value text-success">
                                {{ activeAttendanceData.active_employee || 0 }}
                                /
                                {{
                                    activeAttendanceData.totalEmployeeDocumentation ||
                                    0
                                }}
                            </span>
                        </div>
                        <div class="stat-row mt-2">
                            <span class="stat-label">
                                <i
                                    class="fa-solid fa-chart-line text-muted me-1"
                                ></i>
                                Completion Rate
                            </span>
                            <span class="stat-value text-warning">
                                {{
                                    activeAttendanceData.averagePercentageOfDocumentation ||
                                    0
                                }}%
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Secondary Analytics / Charts Section -->
            <section class="chart-section">
                <dashboardChart :dashData="dashCount" />
            </section>
        </div>
    </main>
</template>

<style scoped>
.dashboard-wrapper {
    background-color: #f8fafc;
    min-height: 100vh;
    font-family:
        "Inter",
        system-ui,
        -apple-system,
        sans-serif;
    color: #0f172a;
}

/* Header */
.icon-badge {
    width: 44px;
    height: 44px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.dashboard-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

.dashboard-subtitle {
    font-size: 0.875rem;
}

/* Filter Controls Card */
.filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.control-box {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.5rem 0.875rem;
    cursor: pointer;
    transition: border-color 0.2s ease;
    min-height: 46px;
}

.control-box:hover {
    border-color: #2563eb;
}

.control-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
}

.control-value {
    font-size: 0.925rem;
    font-weight: 600;
    color: #0f172a;
}

.calendar-trigger {
    position: relative;
    color: #64748b;
    font-size: 1.25rem;
}

.hidden-date-input {
    opacity: 0;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.multiselect-wrapper {
    padding: 0.25rem 0.5rem;
    cursor: default;
}

/* Multiselect Custom Overrides */
:deep(.multiselect__tags) {
    border: none !important;
    background: transparent !important;
    padding-left: 0 !important;
    min-height: 28px !important;
}

:deep(.multiselect__single) {
    background: transparent !important;
    font-size: 0.925rem !important;
    font-weight: 600 !important;
    color: #0f172a !important;
    padding-left: 0 !important;
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1rem;
}

.metric-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.125rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.metric-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.metric-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
}

.metric-icon {
    font-size: 1rem;
    color: #94a3b8;
}

.primary-stat {
    font-size: 1.75rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
}

.primary-stat .unit {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
}

.highlight-currency {
    color: #2563eb;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
}

.stat-label {
    color: #64748b;
    font-weight: 500;
}

.stat-value {
    font-weight: 700;
    color: #0f172a;
}

/* Location Badges (LOB) */
.sub-stat-group {
    display: flex;
    gap: 0.35rem;
}

.lob-badge {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0.2rem 0.4rem;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.lob-badge small {
    font-weight: 700;
    color: #2563eb;
    background: #dbeafe;
    padding: 0 0.25rem;
    border-radius: 3px;
}
</style>
