<script setup>
import { ref, onMounted, watch, computed } from "vue";
import api from "@/api";
import dashboardChart from "./commonComponent/dashboardChart.vue";
import dailyStandup from "./commonComponent/dailyStandup.vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import Card from "primevue/card";
import Tag from "primevue/tag";
import ProgressBar from "primevue/progressbar";

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
        <div class="dashboard-inner">
            <!-- Header Section -->
            <header class="dashboard-header">
                <div class="header-main">
                    <div class="header-icon">
                        <i class="pi pi-th-large"></i>
                    </div>
                    <div class="header-text">
                        <h1 class="dashboard-title">Dashboard overview</h1>
                        <p class="dashboard-subtitle">
                            Real-time workforce, attendance &amp; compliance
                            insights
                        </p>
                    </div>
                </div>
                <div class="header-aside" v-if="currentDate">
                    <i class="pi pi-calendar"></i>
                    <span>{{ currentDate }}</span>
                </div>
            </header>

            <!-- Filter Controls Bar -->
            <!-- <section class="filter-card mb-4">
                <div class="row g-3 align-items-center">
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
            </section> -->

            <!-- Metrics Grid -->
            <section class="metrics-grid" aria-label="Key metrics">
                <!-- Total Employees -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-primary">
                                <i class="pi pi-users"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.active_employee || 0 }}
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title">Total employees</span>
                        </div>
                    </template>
                </Card>

                <!-- Departments -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-violet">
                                <i class="pi pi-sitemap"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.department_total || 0 }}
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title">Departments</span>
                        </div>
                    </template>
                </Card>

                <!-- Present attendance -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-success">
                                <i class="pi pi-check-circle"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.total_present || 0 }}
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title">Present attendance</span>
                            <Tag
                                v-if="activeAttendanceData.avg_present != null"
                                :value="activeAttendanceData.avg_present + '%'"
                                severity="success"
                                rounded
                            />
                        </div>
                    </template>
                </Card>

                <!-- Absent attendance -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-danger">
                                <i class="pi pi-times-circle"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.total_absent || 0 }}
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title">Absent attendance</span>
                            <Tag
                                v-if="activeAttendanceData.avg_absent != null"
                                :value="activeAttendanceData.avg_absent + '%'"
                                severity="danger"
                                rounded
                            />
                        </div>
                    </template>
                </Card>

                <!-- Late arrivals -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-warn">
                                <i class="pi pi-clock"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.total_late || 0 }}
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title">Late arrivals</span>
                            <Tag
                                v-if="activeAttendanceData.avg_late != null"
                                :value="activeAttendanceData.avg_late + '%'"
                                severity="warn"
                                rounded
                            />
                        </div>
                    </template>
                </Card>

                <!-- Avg. work hours -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-info">
                                <i class="pi pi-stopwatch"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.average_work_hours || 0 }}
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title">Avg. work hours</span>
                        </div>
                    </template>
                </Card>

                <!-- On leave -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-info">
                                <i class="pi pi-send"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.total_leave || 0 }}
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title">On leave</span>
                        </div>
                    </template>
                </Card>

                <!-- Pending leave -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-warn">
                                <i class="pi pi-hourglass"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.total_pending_leave || 0 }}
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title">Pending leave</span>
                        </div>
                    </template>
                </Card>

                <!-- Average age -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-primary">
                                <i class="pi pi-user"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.averageAge || 0 }}
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title"
                                >Average age (years)</span
                            >
                        </div>
                    </template>
                </Card>

                <!-- Average tenure -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-violet">
                                <i class="pi pi-star"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.averageTenure || 0 }}
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title"
                                >Average tenure (years)</span
                            >
                        </div>
                    </template>
                </Card>

                <!-- TA/DA MTD -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-success">
                                <i class="pi pi-wallet"></i>
                            </span>
                        </div> -->
                        <div class="metric-value">
                            {{ activeAttendanceData.total_expense || 0
                            }}<small class="metric-unit"> TK.</small>
                        </div>
                        <div class="metric-footer">
                            <span class="metric-title">TA/DA (MTD)</span>
                        </div>
                    </template>
                </Card>

                <!-- Document Compliance -->
                <Card class="metric-card">
                    <template #content>
                        <!-- <div class="metric-top">
                            <span class="metric-icon icon-info">
                                <i class="pi pi-file-check"></i>
                            </span>
                        </div> -->
                        <div class="metric-value metric-value-sm">
                            {{ activeAttendanceData.active_employee || 0 }}
                            <span class="metric-divider">/</span>
                            {{
                                activeAttendanceData.totalEmployeeDocumentation ||
                                0
                            }}
                        </div>
                        <div class="metric-footer compliance-footer">
                            <span class="metric-title"
                                >Document Compliance</span
                            >
                            <span
                                class="metric-tag"
                                v-if="
                                    activeAttendanceData.averagePercentageOfDocumentation !=
                                    null
                                "
                            >
                                {{
                                    activeAttendanceData.averagePercentageOfDocumentation
                                }}%
                            </span>
                        </div>
                        <ProgressBar
                            v-if="
                                activeAttendanceData.averagePercentageOfDocumentation !=
                                null
                            "
                            :value="
                                Number(
                                    activeAttendanceData.averagePercentageOfDocumentation,
                                ) || 0
                            "
                            :showValue="false"
                            class="compliance-progress"
                        />
                    </template>
                </Card>
            </section>

            <!-- Secondary Analytics / Charts Section -->
            <section class="panel-section">
                <dailyStandup />
            </section>
            <section class="panel-section">
                <dashboardChart :dashData="dashCount" />
            </section>
        </div>
    </main>
</template>

<style scoped>
.dashboard-wrapper {
    background-color: var(--p-surface-50, #f8fafc);
    min-height: 100vh;
    font-family:
        "Inter",
        system-ui,
        -apple-system,
        "Segoe UI",
        sans-serif;
    color: var(--p-text-color, #0f172a);
}

.dashboard-inner {
    max-width: 1440px;
    margin: 0 auto;
    padding: 1.25rem 1.25rem 2rem;
}

/* Header */
.dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.header-main {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.header-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: var(--p-primary-color, #6366f1);
    background: color-mix(
        in srgb,
        var(--p-primary-color, #6366f1) 12%,
        transparent
    );
    flex-shrink: 0;
}

.dashboard-title {
    font-size: 1.75rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.2;
    margin: 0;
    color: var(--p-text-color, #0f172a);
}

.dashboard-subtitle {
    margin: 0.2rem 0 0;
    font-size: 0.875rem;
    color: var(--p-text-muted-color, #64748b);
}

.header-aside {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.875rem;
    border-radius: 999px;
    background: var(--p-surface-0, #ffffff);
    border: 1px solid var(--p-content-border-color, #e2e8f0);
    color: var(--p-text-muted-color, #64748b);
    font-size: 0.8125rem;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.header-aside i {
    color: var(--p-primary-color, #6366f1);
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.metric-card {
    border-radius: var(--p-content-border-radius, 12px);
    transition:
        transform 0.16s ease,
        box-shadow 0.16s ease;
    border: 0.9px solid rgb(207, 207, 207);
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px -12px rgba(15, 23, 42, 0.22);
}

.metric-card :deep(.p-card-body) {
    padding: 1.125rem 1.125rem 1rem;
}

.metric-card :deep(.p-card-content) {
    padding: 0;
}

.metric-top {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-bottom: 0.75rem;
}

.metric-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.0625rem;
}

.icon-primary {
    color: #6366f1;
    background: rgba(99, 102, 241, 0.12);
}

.icon-violet {
    color: #8b5cf6;
    background: rgba(139, 92, 246, 0.12);
}

.icon-success {
    color: #10b981;
    background: rgba(16, 185, 129, 0.12);
}

.icon-danger {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.12);
}

.icon-warn {
    color: #f59e0b;
    background: rgba(245, 158, 11, 0.14);
}

.icon-info {
    color: #0ea5e9;
    background: rgba(14, 165, 233, 0.12);
}

.metric-value {
    font-size: 1.875rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.02em;
    color: var(--p-text-color, #0f172a);
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}

.metric-value-sm {
    font-size: 1.5rem;
}

.metric-unit {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--p-text-muted-color, #64748b);
}

.metric-divider {
    color: var(--p-text-muted-color, #94a3b8);
    font-weight: 400;
    margin: 0 0.1rem;
}

.metric-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.metric-title {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--p-text-muted-color, #64748b);
    line-height: 1.35;
}

.metric-tag {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--p-text-color, #0f172a);
    background: var(--p-surface-100, #f1f5f9);
    border-radius: 999px;
    padding: 0.15rem 0.5rem;
    white-space: nowrap;
}

.compliance-footer {
    margin-bottom: 0.5rem;
}

.compliance-progress {
    height: 6px;
}

.compliance-progress :deep(.p-progressbar) {
    height: 6px;
    border-radius: 999px;
}

/* Panels */
.panel-section {
    margin-top: 1rem;
}

@media (max-width: 575.98px) {
    .dashboard-inner {
        padding: 1rem 0.875rem 1.5rem;
    }

    .dashboard-title {
        font-size: 1.4rem;
    }

    .header-icon {
        width: 40px;
        height: 40px;
    }

    .metrics-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.75rem;
    }

    .metric-value {
        font-size: 1.5rem;
    }

    .metric-card :deep(.p-card-body) {
        padding: 0.875rem;
    }
}
</style>
