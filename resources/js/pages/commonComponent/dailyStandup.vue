<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
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
                    ? Math.round((lateEmployees.length / presentCount) * 1000) /
                      10
                    : 0;
        });
    } catch (err) {
        console.error("Error fetching daily standup data:", err);
        error.value = true;
    } finally {
        loading.value = false;
    }
};

// Table shows only today and the previous day (no future/tomorrow column).
const dayCards = [
    { key: "yesterday", label: "Yesterday" },
    { key: "today", label: "Today" },
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

// Presentation-only helpers (reference palette). No data/logic change.
const dayDotColors = {
    present: "#0E8A5F",
    late: "#C0392B",
    on_leave: "#C27C0E",
    schedule_outlet_visited: "#2F6FDE",
    random_outlet_visit: "#1A9BB5",
    meeting_minutes: "#5B6B70",
};

const standupRows = [
    {
        key: "present",
        label: "Present attendance",
        dot: dayDotColors.present,
        cell: (day) => ({
            value: `${formatNumber(day?.present?.count)} of ${formatNumber(day?.present?.total)}`,
            pct: `${day?.present?.percentage || 0}%`,
            pctClass: "pct-success",
        }),
    },
    {
        key: "late",
        label: "Late attendance",
        dot: dayDotColors.late,
        cell: (day, cardKey) => ({
            value: `${formatNumber(day?.late?.count)} of ${formatNumber(day?.present?.count)}`,
            pct: `${day?.late?.percentage || 0}%`,
            pctClass: "pct-danger",
            clickable: true,
            onClick: () => openLateModal(cardKey),
        }),
    },
    {
        key: "on_leave",
        label: "On leave",
        dot: dayDotColors.on_leave,
        onHover: true,
        cell: (day) => ({
            value: formatNumber(day?.on_leave?.count),
            pct: `${day?.on_leave?.percentage || 0}%`,
            pctClass: "pct-warning",
            hoverable: (day?.on_leave?.count || 0) > 0,
        }),
    },
    {
        key: "schedule_outlet_visited",
        label: "Scheduled outlet visited",
        dot: dayDotColors.schedule_outlet_visited,
        cell: (day) => ({
            value: `${formatNumber(day?.schedule_outlet_visited?.visited)} of ${formatNumber(day?.schedule_outlet_visited?.scheduled)}`,
            pct: `${day?.schedule_outlet_visited?.percentage || 0}%`,
            pctClass: "pct-primary",
            clickable: true,
            onClick: () => navigateTo("OutletVisitReport"),
        }),
    },
    {
        key: "random_outlet_visit",
        label: "Random outlet visit",
        dot: dayDotColors.random_outlet_visit,
        cell: (day) => ({
            value: `${formatNumber(day?.random_outlet_visit?.count)} by ${formatNumber(day?.random_outlet_visit?.employees)}`,
            pct: `${formatNumber(day?.random_outlet_visit?.towns)} towns`,
            pctClass: "pct-muted",
            clickable: true,
            onClick: () => navigateTo("RandomOutletVisitReport"),
        }),
    },
    {
        key: "meeting_minutes",
        label: "Meeting minutes",
        dot: dayDotColors.meeting_minutes,
        cell: (day) => ({
            value: formatNumber(day?.meeting_minutes?.meetings),
            pct: `${formatNumber(day?.meeting_minutes?.persons)} persons`,
            pctClass: "pct-muted",
            clickable: true,
            onClick: () => navigateTo("MeetingMinutesReport"),
        }),
    },
];

const standupMatrix = computed(() =>
    standupRows.map((row) => ({
        ...row,
        cells: dayCards.map((card) =>
            row.cell(standupData.value[card.key], card.key),
        ),
    })),
);

const todayStandup = computed(() => standupData.value.today || {});

const progressWidth = (percentage) =>
    `${Math.min(Number(percentage) || 0, 100)}%`;

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
        <div class="section-label">Daily standup · yesterday vs. today</div>

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

        <div v-else class="standup-grid">
            <!-- Comparison table: yesterday / today / tomorrow -->
            <div class="standup-card standup-table-card">
                <div class="standup-table-wrap">
                    <table class="standup-table">
                        <thead>
                            <tr>
                                <th class="col-metric">Metric</th>
                                <th
                                    v-for="card in dayCards"
                                    :key="card.key"
                                    class="col-day"
                                >
                                    {{ card.label }} ·
                                    {{ formatDateLabel(dates[card.key]) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in standupMatrix" :key="row.key">
                                <td class="metric-name">
                                    <span
                                        class="metric-dot"
                                        :style="{ background: row.dot }"
                                    ></span>
                                    {{ row.label }}
                                </td>
                                <td
                                    v-for="(cell, i) in row.cells"
                                    :key="dayCards[i].key"
                                    class="metric-day-cell"
                                    :class="{
                                        'metric-clickable': cell.clickable,
                                    }"
                                    @click="cell.clickable && cell.onClick()"
                                    @mouseenter="
                                        row.onHover && cell.hoverable
                                            ? showLeaveHover(
                                                  $event,
                                                  dayCards[i].key,
                                              )
                                            : null
                                    "
                                    @mouseleave="
                                        row.onHover ? hideLeaveHover() : null
                                    "
                                >
                                    <b>{{ cell.value }}</b>
                                    <span
                                        class="metric-pct"
                                        :class="cell.pctClass"
                                        >{{ cell.pct }}</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Field activity today -->
            <div class="standup-card field-activity-card">
                <div class="field-activity-title">Field activity today</div>

                <div class="field-progress">
                    <div class="field-progress-head">
                        <span>Scheduled outlets visited</span>
                        <span>
                            {{
                                formatNumber(
                                    todayStandup.schedule_outlet_visited
                                        ?.visited,
                                )
                            }}
                            of
                            {{
                                formatNumber(
                                    todayStandup.schedule_outlet_visited
                                        ?.scheduled,
                                )
                            }}
                        </span>
                    </div>
                    <div class="field-progress-track">
                        <div
                            class="field-progress-fill"
                            :style="{
                                width: progressWidth(
                                    todayStandup.schedule_outlet_visited
                                        ?.percentage,
                                ),
                                background: '#2F6FDE',
                            }"
                        ></div>
                    </div>
                </div>

                <div class="field-progress">
                    <div class="field-progress-head">
                        <span>Present attendance</span>
                        <span>
                            {{ formatNumber(todayStandup.present?.count) }} of
                            {{ formatNumber(todayStandup.present?.total) }}
                        </span>
                    </div>
                    <div class="field-progress-track">
                        <div
                            class="field-progress-fill"
                            :style="{
                                width: progressWidth(
                                    todayStandup.present?.percentage,
                                ),
                                background: '#0E8A5F',
                            }"
                        ></div>
                    </div>
                </div>

                <div class="field-stats">
                    <div
                        class="field-stat metric-clickable"
                        @click="navigateTo('RandomOutletVisitReport')"
                    >
                        <div class="field-stat-value">
                            {{
                                formatNumber(
                                    todayStandup.random_outlet_visit?.count,
                                )
                            }}
                        </div>
                        <div class="field-stat-sub">
                            Random outlet visits by
                            {{
                                formatNumber(
                                    todayStandup.random_outlet_visit?.employees,
                                )
                            }}
                            people ·
                            {{
                                formatNumber(
                                    todayStandup.random_outlet_visit?.towns,
                                )
                            }}
                            towns
                        </div>
                    </div>
                    <div
                        class="field-stat metric-clickable"
                        @click="navigateTo('MeetingMinutesReport')"
                    >
                        <div class="field-stat-value">
                            {{
                                formatNumber(
                                    todayStandup.meeting_minutes?.meetings,
                                )
                            }}
                        </div>
                        <div class="field-stat-sub">
                            Meeting minutes filed today
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
    display: flex;
    flex-direction: column;
    gap: 12px;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
}

.section-label {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #5b6b70;
}

/* Two-card row: comparison table + field activity */
.standup-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: stretch;
}

.standup-card {
    background: #ffffff;
    border: 1px solid #e3e9e7;
    border-radius: 14px;
    padding: 20px;
    box-sizing: border-box;
    min-width: 0;
}

.standup-table-card {
    flex: 3 1 460px;
}

.field-activity-card {
    flex: 2 1 320px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* Comparison table */
.standup-table-wrap {
    overflow-x: auto;
}

.standup-table {
    width: 100%;
    min-width: 420px;
    border-collapse: collapse;
    font-size: 12.5px;
}

.standup-table thead th {
    text-align: left;
    color: #5b6b70;
    font-size: 11.5px;
    font-weight: 700;
    padding: 8px 6px;
    border-bottom: 1px solid #e3e9e7;
    white-space: nowrap;
}

.standup-table .col-metric {
    width: 34%;
}

.standup-table tbody td {
    padding: 11px 6px;
    border-bottom: 1px solid #eef2f1;
    vertical-align: middle;
}

.standup-table tbody tr:last-child td {
    border-bottom: none;
}

.metric-name {
    font-weight: 600;
    color: #1b2a2f;
    white-space: nowrap;
}

.metric-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
    vertical-align: middle;
}

.metric-day-cell {
    color: #1b2a2f;
    white-space: nowrap;
}

.metric-day-cell b {
    font-weight: 700;
}

.metric-pct {
    font-weight: 700;
    margin-left: 6px;
}

.pct-success {
    color: #075c41;
}

.pct-danger {
    color: #8e2418;
}

.pct-warning {
    color: #7a4b00;
}

.pct-primary {
    color: #1a4aa3;
}

.pct-muted {
    color: #5b6b70;
}

.metric-clickable {
    cursor: pointer;
    transition: color 0.15s ease;
}

.metric-clickable:hover {
    color: #0e8a5f;
}

/* Field activity today */
.field-activity-title {
    font-weight: 700;
    font-size: 15px;
    color: #1b2a2f;
}

.field-progress-head {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    font-size: 12.5px;
    font-weight: 600;
    color: #1b2a2f;
}

.field-progress-track {
    height: 8px;
    border-radius: 999px;
    background: #e3e9e7;
    margin-top: 5px;
    overflow: hidden;
}

.field-progress-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.3s ease;
}

.field-stats {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.field-stat {
    flex: 1 1 120px;
    padding: 12px;
    background: #f3f6f5;
    border-radius: 12px;
}

.field-stat-value {
    font-size: 22px;
    font-weight: 800;
    color: #1b2a2f;
}

.field-stat-sub {
    font-size: 11.5px;
    color: #5b6b70;
    margin-top: 2px;
}

/* Loading / error */
.standup-loading,
.standup-error {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #5b6b70;
    font-size: 0.875rem;
    background: #f3f6f5;
    border: 1px solid #e3e9e7;
    border-radius: 14px;
}

.standup-error {
    color: #c0392b;
    background: #fbe3e0;
    border-color: #fbe3e0;
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
    border-bottom: 1px solid #e3e9e7;
}

.standup-modal-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1b2a2f;
    margin: 0;
    display: flex;
    align-items: center;
}

.standup-modal-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: #5b6b70;
    cursor: pointer;
    padding: 0.25rem;
    line-height: 1;
    border-radius: 6px;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}

.standup-modal-close:hover {
    background: #f3f6f5;
    color: #1b2a2f;
}

.standup-modal-date {
    padding: 0.5rem 1.25rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #0e8a5f;
    background: #ddf1e8;
    border-bottom: 1px solid #e3e9e7;
}

.standup-modal-body {
    padding: 1rem 1.25rem;
    overflow-y: auto;
    flex: 1;
}

.standup-modal-empty {
    text-align: center;
    padding: 2rem;
    color: #5b6b70;
    font-size: 0.875rem;
}

.standup-modal-table th {
    font-size: 0.75rem;
    font-weight: 600;
    color: #5b6b70;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-bottom: 2px solid #e3e9e7;
    padding: 0.5rem 0.5rem;
}

.standup-modal-table td {
    font-size: 0.85rem;
    color: #33444a;
    padding: 0.6rem 0.5rem;
    vertical-align: middle;
}

.standup-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.25rem;
    border-top: 1px solid #e3e9e7;
    background: #f3f6f5;
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

@media (max-width: 575.98px) {
    .standup-card {
        padding: 16px;
    }

    .standup-table .col-metric {
        width: 40%;
    }
}
</style>
