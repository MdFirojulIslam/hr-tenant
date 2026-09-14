<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import api from "@/api";

const router = useRouter();

const standupData = ref({
    yesterday: null,
    today: null,
    tomorrow: null,
});
const dates = ref({
    yesterday: "",
    today: "",
    tomorrow: "",
});
const loading = ref(true);
const error = ref(false);

const formatNumber = (value) => {
    const num = Number(value) || 0;
    return num.toString();
};

const formatDateLabel = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr + "T00:00:00");
    return date.toLocaleDateString("en-US", {
        weekday: "long",
        month: "short",
        day: "2-digit",
        year: "numeric",
    });
};

const ROSTERED_START_MINUTES = 10 * 60;
const LATE_GRACE_MINUTES = 15;

const parseTimeToMinutes = (timeStr) => {
    if (!timeStr) return null;
    const match = String(timeStr).match(/(\d{1,2}):(\d{2})/);
    if (!match) return null;
    return parseInt(match[1], 10) * 60 + parseInt(match[2], 10);
};

const isBeyondGracePeriod = (timeIn) => {
    const inMinutes = parseTimeToMinutes(timeIn);
    if (inMinutes === null) return false;
    return inMinutes > ROSTERED_START_MINUTES + LATE_GRACE_MINUTES;
};

const fetchStandup = async () => {
    loading.value = true;
    error.value = false;
    try {
        const response = await api.get("/daily-standup");
        standupData.value = response.data;
        dates.value = response.data.dates || {};

        ["yesterday", "today", "tomorrow"].forEach((key) => {
            const day = standupData.value[key];
            if (!day || !Array.isArray(day.late?.employees)) return;
            const lateEmployees = day.late.employees.filter((emp) =>
                isBeyondGracePeriod(emp.time_in),
            );
            day.late.employees = lateEmployees;
            day.late.count = lateEmployees.length;
            const presentCount = day.present?.count || 0;
            day.late.percentage =
                presentCount > 0
                    ? Math.round((lateEmployees.length / presentCount) * 1000) / 10
                    : 0;
        });
    } catch (err) {
        console.error("Error fetching daily standup data:", err);
        error.value = true;
    } finally {
        loading.value = false;
    }
};

const dayCards = [
    { key: "yesterday", label: "Yesterday" },
    { key: "today", label: "Today" },
    { key: "tomorrow", label: "Tomorrow" },
];

// Late employees modal
const showLateModal = ref(false);
const lateModalEmployees = ref([]);
const lateModalDate = ref("");

const openLateModal = (cardKey) => {
    const data = standupData.value[cardKey];
    if (!data || !data.late || !data.late.employees) return;
    lateModalEmployees.value = data.late.employees;
    lateModalDate.value = dates.value[cardKey] || "";
    showLateModal.value = true;
};

const closeLateModal = () => {
    showLateModal.value = false;
    lateModalEmployees.value = [];
    lateModalDate.value = "";
};

// Leave tooltip
const showLeaveTooltip = ref(false);
const leaveTooltipNames = ref([]);
const leaveTooltipPos = ref({ x: 0, y: 0 });

const showLeaveHover = (event, cardKey) => {
    const data = standupData.value[cardKey];
    if (
        !data ||
        !data.on_leave ||
        !data.on_leave.employee_names ||
        data.on_leave.employee_names.length === 0
    )
        return;
    leaveTooltipNames.value = data.on_leave.employee_names;
    const rect = event.currentTarget.getBoundingClientRect();
    leaveTooltipPos.value = {
        x: rect.left + rect.width / 2,
        y: rect.top - 8,
    };
    showLeaveTooltip.value = true;
};

const hideLeaveHover = () => {
    showLeaveTooltip.value = false;
    leaveTooltipNames.value = [];
};

// Navigation
const navigateTo = (routeName) => {
    router.push({ name: routeName });
};

// Handle click outside modal
const handleKeydown = (e) => {
    if (e.key === "Escape" && showLateModal.value) {
        closeLateModal();
    }
};

onMounted(() => {
    fetchStandup();
    document.addEventListener("keydown", handleKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
    <div class="daily-standup-content">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="icon-badge">
                    <i class="fa-solid fa-sun"></i>
                </div>
                <h2 class="standup-title mb-0">Daily Standup Notes</h2>
            </div>
        </div>

        <div v-if="loading" class="standup-loading">
            <div
                class="spinner-border spinner-border-sm text-primary me-2"
            ></div>
            Loading Daily Standup...
        </div>

        <div v-else-if="error" class="standup-error">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            Unable to load Daily Standup data. Please try again later.
        </div>

        <div v-else class="row g-3">
            <div
                v-for="card in dayCards"
                :key="card.key"
                class="col-12 col-md-4"
            >
                <div class="standup-card">
                    <div class="standup-card-header">
                        <span class="standup-day-label">{{ card.label }}</span>
                        <span class="standup-date-label">{{
                            formatDateLabel(dates[card.key])
                        }}</span>
                    </div>

                    <div class="standup-metrics">
                        <!-- Present -->
                        <div class="standup-metric">
                            <div class="metric-row">
                                <span class="metric-name col-5">
                                    <i
                                        class="fa-solid fa-circle small text-success me-1"
                                    ></i>
                                    Present attendance
                                </span>
                                <span class="metric-count col-4">
                                    {{
                                        formatNumber(
                                            standupData[card.key]?.present
                                                ?.count,
                                        )
                                    }}/{{
                                        formatNumber(
                                            standupData[card.key]?.present
                                                ?.total,
                                        )
                                    }}
                                </span>
                                <span class="metric-pct text-success col-3"
                                    >{{
                                        standupData[card.key]?.present
                                            ?.percentage || 0
                                    }}%</span
                                >
                            </div>
                        </div>

                        <!-- Late -->
                        <div class="standup-metric">
                            <div class="metric-row">
                                <span class="metric-name col-5">
                                    <i
                                        class="fa-solid fa-circle small text-danger me-1"
                                    ></i>
                                    Late attendance
                                </span>
                                <span
                                    class="metric-count col-4 metric-clickable"
                                    @click="openLateModal(card.key)"
                                >
                                    {{
                                        formatNumber(
                                            standupData[card.key]?.late?.count,
                                        )
                                    }}/{{
                                        formatNumber(
                                            standupData[card.key]?.late?.total,
                                        )
                                    }}
                                </span>
                                <span class="metric-pct text-danger col-3"
                                    >{{
                                        standupData[card.key]?.late
                                            ?.percentage || 0
                                    }}%</span
                                >
                            </div>
                        </div>

                        <!-- On Leave -->
                        <div class="standup-metric">
                            <div class="metric-row">
                                <span class="metric-name col-5">
                                    <i
                                        class="fa-solid fa-circle small text-warning me-1"
                                    ></i>
                                    On Leave
                                </span>
                                <span
                                    class="metric-count col-4"
                                    :class="{
                                        'metric-hoverable':
                                            standupData[card.key]?.on_leave
                                                ?.count > 0,
                                    }"
                                    @mouseenter="
                                        showLeaveHover($event, card.key)
                                    "
                                    @mouseleave="hideLeaveHover"
                                >
                                    {{
                                        formatNumber(
                                            standupData[card.key]?.on_leave
                                                ?.count,
                                        )
                                    }}
                                </span>
                                <span class="metric-pct text-warning col-3"
                                    >{{
                                        standupData[card.key]?.on_leave
                                            ?.percentage || 0
                                    }}%</span
                                >
                            </div>
                        </div>

                        <!-- Schedule Outlet Visited -->
                        <div class="standup-metric">
                            <div class="metric-row">
                                <span class="metric-name col-5">
                                    <i
                                        class="fa-solid fa-store me-1 text-primary"
                                    ></i>
                                    Schedule Outlet Visited
                                </span>
                                <span
                                    class="metric-count col-4 metric-clickable"
                                    @click="navigateTo('OutletVisitReport')"
                                >
                                    {{
                                        formatNumber(
                                            standupData[card.key]
                                                ?.schedule_outlet_visited
                                                ?.visited,
                                        )
                                    }}/{{
                                        formatNumber(
                                            standupData[card.key]
                                                ?.schedule_outlet_visited
                                                ?.scheduled,
                                        )
                                    }}
                                </span>
                                <span class="metric-pct text-primary col-3"
                                    >{{
                                        standupData[card.key]
                                            ?.schedule_outlet_visited
                                            ?.percentage || 0
                                    }}%</span
                                >
                            </div>
                        </div>

                        <!-- Random Outlet Visit -->
                        <div class="standup-metric">
                            <div class="metric-row">
                                <span class="metric-name col-5">
                                    <i
                                        class="fa-solid fa-location-dot me-1 text-info"
                                    ></i>
                                    Random Outlet Visit
                                </span>
                                <span
                                    class="metric-count col-4 metric-clickable"
                                    @click="
                                        navigateTo('RandomOutletVisitReport')
                                    "
                                >
                                    {{
                                        formatNumber(
                                            standupData[card.key]
                                                ?.random_outlet_visit?.count,
                                        )
                                    }}/{{
                                        formatNumber(
                                            standupData[card.key]
                                                ?.random_outlet_visit
                                                ?.employees,
                                        )
                                    }}
                                </span>
                                <span class="metric-pct text-muted col-3">
                                    {{
                                        formatNumber(
                                            standupData[card.key]
                                                ?.random_outlet_visit?.towns,
                                        )
                                    }}
                                    Towns
                                </span>
                            </div>
                        </div>

                        <!-- Meeting Minutes -->
                        <div class="standup-metric">
                            <div class="metric-row">
                                <span class="metric-name col-5">
                                    <i
                                        class="fa-solid fa-file-lines me-1 text-secondary"
                                    ></i>
                                    Meeting Minutes
                                </span>
                                <span
                                    class="metric-count col-4 metric-clickable"
                                    @click="navigateTo('MeetingMinutesReport')"
                                >
                                    {{
                                        formatNumber(
                                            standupData[card.key]
                                                ?.meeting_minutes?.meetings,
                                        )
                                    }}
                                </span>
                                <span class="metric-pct text-muted col-3">
                                    {{
                                        formatNumber(
                                            standupData[card.key]
                                                ?.meeting_minutes?.persons,
                                        )
                                    }}
                                    Persons
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Late Employees Modal -->
        <Teleport to="body">
            <div
                v-if="showLateModal"
                class="standup-modal-overlay"
                @click.self="closeLateModal"
            >
                <div class="standup-modal">
                    <div class="standup-modal-header">
                        <h5 class="standup-modal-title">
                            <i class="fa-solid fa-clock text-danger me-2"></i>
                            Late Employees
                        </h5>
                        <button
                            class="standup-modal-close"
                            @click="closeLateModal"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="standup-modal-date">
                        {{ formatDateLabel(lateModalDate) }}
                    </div>
                    <div class="standup-modal-body">
                        <div
                            v-if="lateModalEmployees.length === 0"
                            class="standup-modal-empty"
                        >
                            No late employees found.
                        </div>
                        <table
                            v-else
                            class="table table-hover standup-modal-table mb-0"
                        >
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Department</th>
                                    <th class="text-end">In Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="emp in lateModalEmployees"
                                    :key="emp.eid"
                                >
                                    <td>{{ emp.name }}</td>
                                    <td>{{ emp.department }}</td>
                                    <td class="text-end">
                                        <span
                                            class="badge bg-danger-subtle text-danger"
                                        >
                                            {{ emp.time_in }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="standup-modal-footer">
                        <span class="text-muted small">
                            Total: {{ lateModalEmployees.length }} late
                            employee(s)
                        </span>
                        <button
                            class="btn btn-sm btn-secondary"
                            @click="closeLateModal"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Leave Tooltip -->
        <Teleport to="body">
            <div
                v-if="showLeaveTooltip && leaveTooltipNames.length > 0"
                class="standup-leave-tooltip"
                :style="{
                    left: leaveTooltipPos.x + 'px',
                    top: leaveTooltipPos.y + 'px',
                }"
            >
                <div class="leave-tooltip-header">Employees on Leave</div>
                <div
                    v-for="(name, idx) in leaveTooltipNames"
                    :key="idx"
                    class="leave-tooltip-item"
                >
                    <i class="fa-solid fa-user me-2 text-muted"></i>
                    {{ name }}
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.daily-standup-content {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    font-family:
        "Inter",
        system-ui,
        -apple-system,
        sans-serif;
}

.icon-badge {
    width: 40px;
    height: 40px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
    flex-shrink: 0;
}

.standup-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
}

.standup-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem 1.125rem;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.standup-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 0.75rem;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.standup-day-label {
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
}

.standup-date-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #2563eb;
    background: #dbeafe;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    white-space: nowrap;
}

.standup-metrics {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.standup-metric {
    padding-bottom: 0.85rem;
    border-bottom: 1px dashed #e2e8f0;
}

.standup-metric:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.metric-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    width: 100%;
}

.metric-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
    display: flex;
    align-items: center;
    min-width: 0;
}

.metric-name i {
    font-size: 0.6rem;
}

.metric-pct {
    font-size: 0.8rem;
    font-weight: 600;
    white-space: nowrap;
    text-align: right;
}

.metric-count {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
    text-align: center;
}

.metric-clickable {
    cursor: pointer;
    transition:
        color 0.15s ease,
        text-decoration 0.15s ease;
}

.metric-clickable:hover {
    color: #2563eb;
    text-decoration: underline;
}

.metric-hoverable {
    cursor: default;
}

.standup-loading,
.standup-error {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #64748b;
    font-size: 0.875rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

.standup-error {
    color: #b91c1c;
    background: #fef2f2;
    border-color: #fecaca;
}

/* Late Employees Modal */
.standup-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1055;
    padding: 1rem;
}

.standup-modal {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 550px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.standup-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.standup-modal-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
}

.standup-modal-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: #64748b;
    cursor: pointer;
    padding: 0.25rem;
    line-height: 1;
    border-radius: 6px;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}

.standup-modal-close:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.standup-modal-date {
    padding: 0.5rem 1.25rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #2563eb;
    background: #f0f7ff;
    border-bottom: 1px solid #e2e8f0;
}

.standup-modal-body {
    padding: 1rem 1.25rem;
    overflow-y: auto;
    flex: 1;
}

.standup-modal-empty {
    text-align: center;
    padding: 2rem;
    color: #94a3b8;
    font-size: 0.875rem;
}

.standup-modal-table th {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-bottom: 2px solid #e2e8f0;
    padding: 0.5rem 0.5rem;
}

.standup-modal-table td {
    font-size: 0.85rem;
    color: #334155;
    padding: 0.6rem 0.5rem;
    vertical-align: middle;
}

.standup-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.25rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

/* Leave Tooltip */
.standup-leave-tooltip {
    position: fixed;
    transform: translateX(-50%) translateY(-100%);
    background: #1e293b;
    color: #f1f5f9;
    border-radius: 8px;
    padding: 0.6rem 0.85rem;
    z-index: 1060;
    min-width: 180px;
    max-width: 280px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    pointer-events: none;
}

.standup-leave-tooltip::after {
    content: "";
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid #1e293b;
}

.leave-tooltip-header {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #94a3b8;
    margin-bottom: 0.4rem;
    padding-bottom: 0.3rem;
    border-bottom: 1px solid #334155;
}

.leave-tooltip-item {
    font-size: 0.8rem;
    padding: 0.2rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
