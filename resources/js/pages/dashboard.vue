<script setup>
import { ref, onMounted, watch, computed } from "vue";
import { useRouter } from "vue-router";
import { useStore } from "vuex";
import Swal from "sweetalert2";
import api from "@/api";
import dashboardChart from "./commonComponent/dashboardChart.vue";
import dailyStandup from "./commonComponent/dailyStandup.vue";
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

// Dashboard header notification + profile controls. These live here (instead
// of a separate Navbar) so the dashboard header is self-contained; the
// behaviour/endpoints are identical to the previous top-navbar controls.
const store = useStore();
const router = useRouter();
const user = store.getters.getUser;
const error = ref("");
const notification = ref([]);

const toggleSidebar = () => {
    store.commit("TOGGLE_SIDEBAR");
};

const totalNotifications = computed(() => {
    const notificationCount = notification.value.length;

    return notificationCount;
});

const goToSupervisorApproval = (item) => {
    router.push({
        path: "/leave/supervisor-approval",
        query: {
            full_name: item.Full_Name,
            leave_type: item.Leave_Type,
            department_name: item.department_name,
            leave_id: item.leave_id,
            purpose: item.purpose,
            status: item.status,
        },
    });
};

const goToHrApproval = (item) => {
    router.push({
        path: "/leave/hr-approval",
        query: {
            full_name: item.Full_Name,
            leave_type: item.Leave_Type,
            department_name: item.department_name,
            leave_id: item.leave_id,
            purpose: item.purpose,
            status: item.status,
        },
    });
};

const getNotificationData = async () => {
    try {
        const responseNotification = await api.get("/get-notification");
        notification.value = responseNotification.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
    if (user.value === "logout") {
        logout();
    }
};

const logout = async () => {
    Swal.fire({
        title: "Do you want to Logout?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes",
    }).then(async (result) => {
        if (result.isConfirmed) {
            store.dispatch("removeToken", 0);
            localStorage.removeItem("token");
            try {
                await api.post("/logout");
            } catch (error) {
                console.error("Error logging out", error);
            }
            router.push({ name: "Login" });
        }
    });
};

const openCalendar = () => {
    const el = dateInput.value;
    if (!el) return;
    if (typeof el.showPicker === "function") {
        try {
            el.showPicker();
            return;
        } catch (err) {
            // Fall through to focus/click for browsers that block showPicker.
        }
    }
    el.focus();
    el.click();
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
        weekday: "short",
        month: "short",
        day: "2-digit",
        year: "numeric",
    });
};

// Derived label only (no data/logic change) to mirror the reference pill.
const currentMonthLabel = computed(() => {
    if (!selectedDate.value) return "";
    return new Date(selectedDate.value).toLocaleDateString("en-US", {
        month: "long",
    });
});

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

// Workforce snapshot cards — bound to the same live attendance payload.
const metrics = computed(() => {
    const d = activeAttendanceData.value || {};
    const totalEmp = d.active_employee || 0;

    return [
        {
            label: "Total employees",
            value: d.active_employee || 0,
            unit: "",
            sub: "Across all departments",
        },
        {
            label: "Departments",
            value: d.department_total || 0,
            unit: "",
            sub: "Active departments",
        },
        {
            label: "Present attendance",
            value: d.total_present || 0,
            unit: "today",
            sub: `of ${totalEmp} employees`,

            hasBadge: d.avg_present != null,
            badge: `${d.avg_present ?? 0}%`,
            badgeBg: "#DFF3EA",
            badgeFg: "#075C41",
        },
        {
            label: "Absent attendance",
            value: d.total_absent || 0,
            unit: "today",
            sub: `of ${totalEmp} employees`,
            hasBadge: d.avg_absent != null,
            badge: `${d.avg_absent ?? 0}%`,
            badgeBg: "#FBE3E0",
            badgeFg: "#8E2418",
        },
        {
            label: "Late arrivals",
            value: d.total_late || 0,
            unit: "",
            sub: `of ${d.total_present || 0} present today`,
            hasBadge: d.avg_late != null,
            badge: `${d.avg_late ?? 0}%`,
            badgeBg: "#FDEBCB",
            badgeFg: "#7A4B00",
        },
        {
            label: "Avg. work hours",
            value: d.average_work_hours || 0,
            unit: "hrs",
            sub: "Per employee, today",
        },
        {
            label: "On leave",
            value: d.total_leave || 0,
            unit: "",
            sub: "Employees today",
        },
        {
            label: "Pending leave",
            value: d.total_pending_leave || 0,
            unit: "request",
            sub: "Awaiting approval",
        },
        {
            label: "Average age",
            value: d.averageAge || 0,
            unit: "years",
            sub: "Workforce profile",
        },
        {
            label: "Average tenure",
            value: d.averageTenure || 0,
            unit: "years",
            sub: "Workforce profile",
        },
        {
            label: "TA/DA (MTD)",
            value: d.total_expense || 0,
            unit: "TK.",
            sub: "Month-to-date expense",
        },
    ];
});

onMounted(() => {
    initializeChart();
    setCurrentDate();
    getNotificationData();
});
</script>

<template>
    <main class="dashboard-wrapper">
        <!-- Section 1: Minimal top navbar (title left, actions right) -->
        <header class="dashboard-navbar">
            <div class="navbar-inner">
                <div class="navbar-heading">
                    <span class="navbar-icon" aria-hidden="true">
                        <i class="fa-solid fa-chart-pie"></i>
                    </span>
                    <div class="navbar-titles">
                        <h1 class="navbar-title">HR Dashboard</h1>
                        <h5 class="navbar-subtitle">
                            Real-time workforce, attendance &amp; compliance
                            insights
                        </h5>
                    </div>
                </div>

                <div class="navbar-actions">
                    <!-- Notification -->
                    <div
                        class="nav-item dropdown position-relative notification-item"
                    >
                        <a
                            class="nav-link position-relative notification-btn"
                            id="dashNotificationDropdown"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <i class="fa-solid fa-bell notification-bell"></i>

                            <span
                                v-if="totalNotifications > 0"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge"
                            >
                                {{ totalNotifications }}
                                <span class="visually-hidden"
                                    >unread messages</span
                                >
                            </span>
                        </a>
                        <ul
                            class="dropdown-menu dropdown-menu-end notification-dropdown"
                            aria-labelledby="dashNotificationDropdown"
                        >
                            <div v-for="item in notification" :key="item.id">
                                <li
                                    class="dropdown-item"
                                    v-if="item.status === 'Pending'"
                                    @click="goToSupervisorApproval(item)"
                                >
                                    {{ item.Full_Name }} Requested a Leave
                                </li>
                                <li
                                    class="dropdown-item"
                                    v-if="item.status === 'Approved (SV)'"
                                    @click="goToHrApproval(item)"
                                >
                                    {{ item.Full_Name }} leave has been accepted
                                    by his Supervisor.
                                </li>
                            </div>
                        </ul>
                    </div>

                    <!-- Profile -->
                    <div class="nav-item dropdown dash-profile">
                        <a
                            class="nav-link dropdown-toggle profile-trigger"
                            id="dashUserDropdown"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <img
                                :src="user.img_url"
                                alt="User Image"
                                class="profile-avatar"
                            />
                        </a>
                        <ul
                            class="dropdown-menu dropdown-menu-end profile-dropdown"
                            aria-labelledby="dashUserDropdown"
                        >
                            <li class="dropdown-item">Settings</li>
                            <li class="dropdown-item">Activity Log</li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li class="dropdown-item" @click="logout">
                                Logout
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <!-- Section 2: Body (starts directly below the navbar) -->
        <div class="dashboard-body">
            <!-- Mobile-only sidebar opener (keeps the navbar to title + actions). -->
            <button
                class="body-nav-toggle d-lg-none"
                type="button"
                @click="toggleSidebar"
                aria-label="Toggle navigation"
            >
                <i class="fas fa-bars"></i>
            </button>

            <div class="dashboard-inner">
                <!-- Workforce snapshot -->
                <section class="kpi-section" aria-label="Workforce KPIs">
                    <div class="section-head">
                        <div class="section-label">Workforce snapshot</div>
                        <div
                            class="date-filter"
                            role="button"
                            tabindex="0"
                            aria-label="Select dashboard date"
                            @click="openCalendar"
                            @keydown.enter.prevent="openCalendar"
                        >
                            <i class="fa-regular fa-calendar-days"></i>
                            <span class="date-filter-value">{{
                                currentDate || "Select date"
                            }}</span>
                            <input
                                type="date"
                                ref="dateInput"
                                v-model="selectedDate"
                                class="date-filter-input"
                                tabindex="-1"
                            />
                        </div>
                    </div>
                    <div class="metrics-grid">
                        <div
                            class="kpi-card"
                            v-for="(m, idx) in metrics"
                            :key="idx"
                        >
                            <div class="kpi-top">
                                <span class="kpi-label">{{ m.label }}</span>
                                <span
                                    class="kpi-dot"
                                    :style="{ background: m.dot }"
                                ></span>
                            </div>
                            <div class="kpi-value-row">
                                <span class="kpi-value">{{ m.value }}</span>
                                <span v-if="m.unit" class="kpi-unit">{{
                                    m.unit
                                }}</span>
                                <span
                                    v-if="m.hasBadge"
                                    class="kpi-badge"
                                    :style="{
                                        background: m.badgeBg,
                                        color: m.badgeFg,
                                    }"
                                    >{{ m.badge }}</span
                                >
                            </div>
                            <div class="kpi-sub">{{ m.sub }}</div>
                        </div>

                        <!-- Document Compliance -->
                        <div class="kpi-card">
                            <div class="kpi-top">
                                <span class="kpi-label"
                                    >Document compliance</span
                                >
                                <span
                                    v-if="
                                        activeAttendanceData.averagePercentageOfDocumentation !=
                                        null
                                    "
                                    class="kpi-badge kpi-badge-neutral"
                                >
                                    {{
                                        activeAttendanceData.averagePercentageOfDocumentation
                                    }}%
                                </span>
                            </div>
                            <div class="kpi-value-row">
                                <span class="kpi-value">
                                    {{
                                        activeAttendanceData.active_employee ||
                                        0
                                    }}
                                    <span class="kpi-doc-total"
                                        >/
                                        {{
                                            activeAttendanceData.totalEmployeeDocumentation ||
                                            0
                                        }}</span
                                    >
                                </span>
                            </div>
                            <div
                                class="kpi-progress"
                                v-if="
                                    activeAttendanceData.averagePercentageOfDocumentation !=
                                    null
                                "
                            >
                                <div
                                    class="kpi-progress-fill"
                                    :style="{
                                        width:
                                            (Number(
                                                activeAttendanceData.averagePercentageOfDocumentation,
                                            ) || 0) + '%',
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Secondary Analytics / Charts Section -->
                <section class="panel-section">
                    <dailyStandup />
                </section>
                <section class="panel-section">
                    <dashboardChart :dashData="dashCount" />
                </section>
            </div>
        </div>
    </main>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap");

.dashboard-wrapper {
    background-color: #f3f6f5;
    min-height: 100vh;
    /* Full-bleed tinted canvas: cancel the container's horizontal gutter
       (Bootstrap's .container-fluid has no vertical padding) so the navbar
       spans the content column edge-to-edge without adding overflow. */
    margin: 0 -0.75rem;
    display: flex;
    flex-direction: column;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    color: #1b2a2f;
    font-variant-numeric: tabular-nums;
}

/* Section 1 — minimal top navbar: fixed height, title left, actions right. */
.dashboard-navbar {
    position: sticky;
    top: 0;
    z-index: 20;
    flex: 0 0 auto;
    height: 64px;
    background-color: #ffffff;
    border-bottom: 1px solid #e3e9e7;
}

/* Keeps the title/actions aligned with the centred body content. */
.navbar-inner {
    width: 100%;
    max-width: 1440px;
    height: 100%;
    margin: 0 auto;
    padding: 0 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

/* Icon + title/subtitle block on the left. */
.navbar-heading {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.navbar-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    border-radius: 12px;
    background: #ddf1e8;
    color: #096645;
    font-size: 1.375rem;
}

/* Title + subtitle stacked vertically on the left. */
.navbar-titles {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}

.navbar-title {
    margin: 0;
    min-width: 0;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -0.01em;
    line-height: 1.2;
    color: #1b2a2f;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.navbar-subtitle {
    margin: 0;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.35;
    color: #5b6b70;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.navbar-actions {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 0 0 auto;
}

/* Section 2 — body: occupies all space remaining below the navbar. */
.dashboard-body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

/* Mobile-only sidebar opener; keeps the navbar limited to the title and
   the two action icons. The desktop sidebar is always visible and carries
   its own collapse toggle. */
.body-nav-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    margin: 12px 0 0 28px;
    padding: 0;
    border: none;
    border-radius: 10px;
    background: transparent;
    color: #33444a;
    font-size: 19px;
    line-height: 1;
    flex-shrink: 0;
    cursor: pointer;
    transition:
        background-color 0.15s ease-in-out,
        color 0.15s ease-in-out;
}

.body-nav-toggle:hover {
    background-color: #eef2f1;
    color: #0e8a5f;
}

.dashboard-inner {
    width: 100%;
    max-width: 1440px;
    margin: 0 auto;
    padding: 16px 28px 40px;
    display: flex;
    flex-direction: column;
    gap: 22px;
}

/* Notification + profile controls (behaviour/styling identical to the
   previous top-navbar controls). */
.notification-item {
    margin-right: 0;
}

.notification-item .notification-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid #d5dfdc;
    background-color: #ffffff;
    color: #2f6fde;
    padding: 0;
    transition:
        background-color 0.15s ease-in-out,
        border-color 0.15s ease-in-out;
}

.notification-item .notification-btn:hover {
    background-color: #f3f6f5;
    border-color: #c2cfcb;
}

.notification-bell {
    font-size: 18px;
    color: #2f6fde;
    transition: color 0.15s ease-in-out;
}

.notification-badge {
    font-size: 10px;
    font-weight: 700;
}

.profile-trigger {
    display: flex;
    align-items: center;
    padding: 0;
    border: none;
}

.profile-trigger::after {
    display: none;
}

.profile-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #d5dfdc;
}

.notification-dropdown,
.profile-dropdown {
    border: 1px solid #e3e9e7;
    border-radius: 12px;
    box-shadow: 0 12px 30px -12px rgba(27, 42, 47, 0.28);
    padding: 6px;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
}

.notification-dropdown .dropdown-item,
.profile-dropdown .dropdown-item {
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #33444a;
    padding: 8px 10px;
    white-space: normal;
}

.notification-dropdown .dropdown-item:hover,
.profile-dropdown .dropdown-item:hover {
    background-color: #f3f6f5;
    color: #0e8a5f;
}

.profile-dropdown .dropdown-divider {
    margin: 4px 0;
    border-color: #e3e9e7;
}

/* Section label */
.section-label {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #5b6b70;
}

/* Section heading row: label left, controls (e.g. date filter) far right. */
.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

/* Compact date filter pinned to the right of the section header. */
.date-filter {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border: 1px solid #d5dfdc;
    border-radius: 10px;
    background-color: #ffffff;
    color: #33444a;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    cursor: pointer;
    transition:
        border-color 0.15s ease-in-out,
        box-shadow 0.15s ease-in-out;
}

.date-filter:hover,
.date-filter:focus-visible {
    border-color: #0e8a5f;
    box-shadow: 0 0 0 3px rgba(14, 138, 95, 0.14);
    outline: none;
}

.date-filter i {
    color: #0e8a5f;
    font-size: 14px;
}

.date-filter-value {
    white-space: nowrap;
}

.date-filter-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    border: none;
    opacity: 0;
    pointer-events: none;
}

/* Workforce KPI grid */
.kpi-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 14px;
}

.kpi-card {
    background: #ffffff;
    border: 1px solid #e3e9e7;
    border-radius: 14px;
    padding: 16px 18px;
    box-sizing: border-box;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.kpi-label {
    font-size: 12px;
    color: #5b6b70;
    font-weight: 600;
}

.kpi-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.kpi-value-row {
    display: flex;
    align-items: baseline;
    gap: 8px;
    flex-wrap: wrap;
}

.kpi-value {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.1;
    color: #1b2a2f;
}

.kpi-unit {
    font-size: 12px;
    color: #5b6b70;
    font-weight: 600;
}

.kpi-badge {
    margin-left: auto;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.kpi-badge-neutral {
    background: #eef2f1;
    color: #33444a;
}

.kpi-doc-total {
    font-size: 16px;
    font-weight: 600;
    color: #5b6b70;
}

.kpi-sub {
    font-size: 11.5px;
    color: #5b6b70;
}

.kpi-progress {
    height: 6px;
    border-radius: 999px;
    background: #e3e9e7;
    overflow: hidden;
    margin-top: 4px;
}

.kpi-progress-fill {
    height: 100%;
    background: #0e8a5f;
}

/* Panels */
.panel-section {
    margin-top: 0;
}

@media (max-width: 575.98px) {
    .navbar-inner {
        padding: 0 16px;
        gap: 12px;
    }

    .navbar-title {
        font-size: 16px;
    }

    .navbar-subtitle {
        font-size: 11px;
    }

    .navbar-heading {
        gap: 10px;
    }

    .navbar-icon {
        width: 38px;
        height: 38px;
        font-size: 1.125rem;
        border-radius: 10px;
    }

    .navbar-actions {
        gap: 10px;
    }

    .body-nav-toggle {
        margin: 10px 0 0 16px;
    }

    .dashboard-inner {
        padding: 12px 16px 32px;
        gap: 18px;
    }

    .metrics-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
    }

    .kpi-value {
        font-size: 1.5rem;
    }

    .kpi-card {
        padding: 14px;
    }
}
</style>
