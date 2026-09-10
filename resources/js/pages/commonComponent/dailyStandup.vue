<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";

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
    return num.toString().padStart(2, "0");
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

const fetchStandup = async () => {
    loading.value = true;
    error.value = false;
    try {
        const response = await api.get("/daily-standup");
        standupData.value = response.data;
        dates.value = response.data.dates || {};
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

onMounted(fetchStandup);
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
                                    }}
                                    Persons
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
                                <span class="metric-count col-4">
                                    {{
                                        formatNumber(
                                            standupData[card.key]?.late?.count,
                                        )
                                    }}
                                    Persons
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
                                <span class="metric-count col-4">
                                    {{
                                        formatNumber(
                                            standupData[card.key]?.on_leave
                                                ?.count,
                                        )
                                    }}
                                    Persons
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
                                <span class="metric-count col-4">
                                    {{
                                        formatNumber(
                                            standupData[card.key]
                                                ?.schedule_outlet_visited
                                                ?.visited,
                                        )
                                    }}
                                    Outlets
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
                                <span class="metric-count col-4">
                                    {{
                                        formatNumber(
                                            standupData[card.key]
                                                ?.random_outlet_visit?.count,
                                        )
                                    }}
                                    Outlets
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
                                <span class="metric-count col-4">
                                    {{
                                        formatNumber(
                                            standupData[card.key]
                                                ?.meeting_minutes?.meetings,
                                        )
                                    }}
                                    Meetings
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
</style>
