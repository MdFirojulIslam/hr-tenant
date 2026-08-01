<script setup>
import { ref, onMounted, computed, watch } from "vue";
import api from "@/api";
import { useStore } from "vuex";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

const store = useStore();
const user = store.getters.getUser;

// -------------------------
const department = ref([]);
const employee = ref([]);

const searchDept = ref("");
const selectedEmployee = ref(null);
const selectedEmployeeId = ref(null);

// -------------------------
const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

// -------------------------
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
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());

const attendanceData = ref({});

const weekDays = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];

const getData = async () => {
    try {
        const res = await api.get(`/department-list/${user.id}`);
        department.value = res.data || [];
    } catch (err) {
        console.error("Error loading departments:", err);
    }
};

// -------------------------
const onEmployeeChange = (val) => {
    selectedEmployeeId.value = val?.id || null;
    getMonthlyData();
};

const onDeptChange = async () => {
    await getEmployee(searchDept.value);
    selectedEmployee.value = null;
    selectedEmployeeId.value = null;
    getMonthlyData();
};

const clearDepartment = () => {
    searchDept.value = "";
    selectedEmployee.value = null;
    selectedEmployeeId.value = null;
    employee.value = [];
    getMonthlyData();
};

const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        return;
    }
    try {
        const res = await api.get(`/adjustment-employees/${deptID}`);
        employee.value = res.data || [];
    } catch (err) {
        console.error("Error loading employees:", err);
    }
};

// -------------------------
const getMonthlyData = async () => {
    try {
        const res = await api.get("/calendar-date-report", {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value,
                employee_id: selectedEmployeeId.value,
                department_id: searchDept.value,
            },
        });
        attendanceData.value = res?.data?.data ?? {};
    } catch (err) {
        console.error("Error loading monthly report:", err);
    }
};

// -------------------------
const attendanceMap = computed(() => attendanceData.value || {});

// -------------------------
const calendarDays = computed(() => {
    const daysInMonth = new Date(
        selectedYear.value,
        selectedMonth.value,
        0,
    ).getDate();

    const firstDay = new Date(
        selectedYear.value,
        selectedMonth.value - 1,
        1,
    ).getDay();

    const cells = [];
    const today = new Date();
    const todayKey = today.toLocaleDateString("en-CA");

    // empty leading cells
    for (let i = 0; i < firstDay; i++) {
        cells.push(null);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const currentDate = new Date(
            selectedYear.value,
            selectedMonth.value - 1,
            d,
        );

        const isFuture = currentDate > today;
        const key = currentDate.toLocaleDateString("en-CA");
        const isToday = key === todayKey;
        const data = attendanceMap.value[key];

        cells.push({
            day: d,
            dateKey: key,
            data: data || null,
            isFuture,
            isToday,
        });
    }

    return cells;
});

// -------------------------
watch([selectedMonth, selectedYear], getMonthlyData);

// -------------------------
onMounted(() => {
    getMonthlyData();
    getData();
});
</script>

<template>
    <div class="calendar-page container-fluid p-3 p-md-4">
        <!-- Header Section -->
        <div
            class="header-card mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2"
        >
            <div>
                <h2 class="header-design m-0">Artisan Calendar</h2>
            </div>

            <!-- Quick Summary Legend -->
            <div
                class="legend-container d-flex flex-wrap gap-2 align-items-center"
            >
                <span class="legend-item badge-present"
                    ><i class="fa-solid fa-circle"></i> Present</span
                >
                <span class="legend-item badge-absent"
                    ><i class="fa-solid fa-circle"></i> Absent</span
                >
                <span class="legend-item badge-leave"
                    ><i class="fa-solid fa-circle"></i> Leave</span
                >
                <span class="legend-item badge-holiday"
                    ><i class="fa-solid fa-circle"></i> Holiday</span
                >
                <span class="legend-item badge-weekend"
                    ><i class="fa-solid fa-circle"></i> Weekend</span
                >
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-card mb-4">
            <div class="row g-3">
                <!-- Department Filter -->
                <div class="col-lg-3 col-md-6">
                    <label class="field-label">
                        <i class="fa-solid fa-sitemap"></i> Department
                    </label>
                    <div class="input-wrapper">
                        <select
                            v-model="searchDept"
                            class="form-select custom-input"
                            :class="{ 'placeholder-active': !searchDept }"
                            @change="onDeptChange"
                        >
                            <option value="">All Departments</option>
                            <option
                                v-for="d in department"
                                :key="d.department_id"
                                :value="d.department_id"
                            >
                                {{ d.department_name }}
                            </option>
                        </select>
                        <button
                            v-if="searchDept"
                            type="button"
                            class="btn-clear"
                            @click="clearDepartment"
                            title="Clear"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <!-- Employee Filter -->
                <div class="col-lg-3 col-md-6">
                    <label class="field-label">
                        <i class="fa-solid fa-user"></i> Employee
                    </label>
                    <Multiselect
                        v-model="selectedEmployee"
                        :options="employee"
                        label="Full_Name"
                        track-by="id"
                        placeholder="Search employee..."
                        class="multiselect-custom"
                        @select="onEmployeeChange"
                        @remove="onEmployeeChange"
                    />
                </div>

                <!-- Month Selector -->
                <div class="col-lg-3 col-md-6">
                    <label class="field-label">
                        <i class="fa-solid fa-calendar-days"></i> Month
                    </label>
                    <select
                        v-model="selectedMonth"
                        class="form-select custom-input"
                    >
                        <option
                            v-for="(m, i) in monthNames"
                            :key="i"
                            :value="i + 1"
                        >
                            {{ m }}
                        </option>
                    </select>
                </div>

                <!-- Year Selector -->
                <div class="col-lg-3 col-md-6">
                    <label class="field-label">
                        <i class="fa-solid fa-calendar"></i> Year
                    </label>
                    <select
                        v-model="selectedYear"
                        class="form-select custom-input"
                    >
                        <option v-for="y in 10" :key="y" :value="2020 + y">
                            {{ 2020 + y }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Calendar Container -->
        <div class="calendar-card">
            <div class="calendar-scroll">
                <div class="calendar-grid">
                    <!-- Day Headers -->
                    <div class="calendar-header-row">
                        <div v-for="d in weekDays" :key="d" class="day-header">
                            {{ d }}
                        </div>
                    </div>

                    <!-- Calendar Body -->
                    <div class="calendar-body-grid">
                        <div
                            v-for="(date, index) in calendarDays"
                            :key="index"
                            class="calendar-cell"
                            :class="{
                                'empty-cell': !date,
                                'is-today': date?.isToday,
                                'is-future': date?.isFuture,
                            }"
                        >
                            <template v-if="date">
                                <div class="cell-top">
                                    <span
                                        class="date-badge"
                                        :class="{ 'today-badge': date.isToday }"
                                    >
                                        {{ date.day }}
                                    </span>
                                    <span
                                        v-if="date.isToday"
                                        class="today-label"
                                        >Today</span
                                    >
                                </div>

                                <div class="attendance-content">
                                    <div
                                        v-if="date.data"
                                        class="status-container"
                                    >
                                        <!-- LEAVE -->
                                        <div
                                            v-if="date.data.type === 'leave'"
                                            class="status-pill badge-leave"
                                            :title="date.data.purpose"
                                        >
                                            <i
                                                class="fa-solid fa-plane-departure"
                                            ></i>
                                            <span>{{
                                                date.data.purpose || "Leave"
                                            }}</span>
                                        </div>

                                        <!-- HOLIDAY -->
                                        <div
                                            v-else-if="
                                                date.data.type === 'holiday'
                                            "
                                            class="status-pill badge-holiday"
                                            :title="date.data.holiday_name"
                                        >
                                            <i
                                                class="fa-solid fa-umbrella-beach"
                                            ></i>
                                            <span>{{
                                                date.data.holiday_name ||
                                                "Holiday"
                                            }}</span>
                                        </div>

                                        <!-- WEEKEND -->
                                        <div
                                            v-else-if="
                                                date.data.type === 'weekend'
                                            "
                                            class="status-pill badge-weekend"
                                        >
                                            <i class="fa-solid fa-mug-hot"></i>
                                            <span>Weekend</span>
                                        </div>

                                        <!-- PRESENT -->
                                        <div
                                            v-else-if="
                                                date.data.type === 'present'
                                            "
                                            class="present-time-block"
                                        >
                                            <div class="time-row in">
                                                <i
                                                    class="fa-solid fa-arrow-right-to-bracket"
                                                ></i>
                                                <span>{{
                                                    formatTime(
                                                        date.data.in_time,
                                                    ) || "--:--"
                                                }}</span>
                                            </div>
                                            <div class="time-row out">
                                                <i
                                                    class="fa-solid fa-arrow-right-from-bracket"
                                                ></i>
                                                <span>{{
                                                    formatTime(
                                                        date.data.out_time,
                                                    ) || "--:--"
                                                }}</span>
                                            </div>
                                        </div>

                                        <!-- ABSENT -->
                                        <div
                                            v-else-if="
                                                date.data.type === 'absent'
                                            "
                                            class="status-pill badge-absent"
                                        >
                                            <i
                                                class="fa-solid fa-user-xmark"
                                            ></i>
                                            <span>Absent</span>
                                        </div>

                                        <div
                                            v-else
                                            class="status-pill badge-none"
                                        >
                                            <span>-</span>
                                        </div>
                                    </div>

                                    <div
                                        v-else-if="!date.isFuture"
                                        class="no-data"
                                    >
                                        <span class="text-muted small">-</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-page {
    background-color: #f8fafc;
    min-height: 68vh;
    font-family:
        -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial,
        sans-serif;
    color: #1e293b;
}

/* Header Styling */
.header-design {
    letter-spacing: 0.5px;
    font-weight: 700;
    font-size: 1.5rem;
    color: #0f172a;
}

/* .header-card {
   background: #ffffff;
   padding: 16px 20px;
   border-radius: 8px;
   border: 1px solid #e2e8f0;
} */

/* Legend Styling */
/* .legend-container {
   background: #f1f5f9;
   padding: 6px 12px;
   border-radius: 6px;
} */

.legend-item {
    font-size: 0.725rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Filter Card */
/* .filter-card {
   background-color: #ffffff;
   border: 1px solid #cbd5e1;
   border-radius: 8px;
   padding: 16px;
   box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
} */

.field-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.custom-input {
    height: 38px;
    border-radius: 6px;
    border: 1px solid #94a3b8;
    font-size: 0.85rem;
    padding: 6px 10px;
    color: #0f172a;
    width: 100%;
    background-color: #ffffff;
    transition: all 0.2s ease;
}

.custom-input:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
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

/* Multiselect Styling */
:deep(.multiselect-custom .multiselect__tags) {
    min-height: 38px !important;
    height: 38px;
    padding: 4px 10px;
    border-radius: 6px;
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
    line-height: 28px;
}

:deep(.multiselect-custom .multiselect__select) {
    height: 36px;
}

/* Calendar Main Container */
.calendar-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.calendar-scroll {
    overflow-x: auto;
    width: 100%;
}

.calendar-grid {
    min-width: 768px; /* Prevents squishing on small mobile screens */
}

/* Day Header Row */
.calendar-header-row {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background-color: #1e293b;
    color: #ffffff;
}

.day-header {
    padding: 10px;
    text-align: center;
    font-weight: 700;
    font-size: 0.775rem;
    letter-spacing: 0.75px;
    text-transform: uppercase;
    border-right: 1px solid #334155;
}

.day-header:last-child {
    border-right: none;
}

/* Calendar Body Grid */
.calendar-body-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background-color: #cbd5e1;
    gap: 1px;
}

.calendar-cell {
    background-color: #ffffff;
    min-height: 75px;
    padding: 6px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: background-color 0.15s ease;
}

.calendar-cell:hover:not(.empty-cell) {
    background-color: #f8fafc;
}

.calendar-cell.empty-cell {
    background-color: #f1f5f9;
}

.calendar-cell.is-today {
    background-color: #eff6ff !important;
}

.calendar-cell.is-future {
    background-color: #fafafa;
}

/* Cell Top Section */
.cell-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.date-badge {
    font-size: 0.85rem;
    font-weight: 700;
    color: #475569;
    width: 24px;
    height: 24px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.today-badge {
    background-color: #2563eb;
    color: #ffffff;
}

.today-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #2563eb;
    text-transform: uppercase;
}

/* Attendance Badges & Content */
.attendance-content {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.status-container {
    width: 100%;
}

.status-pill {
    width: 100%;
    padding: 6px 8px;
    border-radius: 4px;
    font-size: 0.725rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.badge-present {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #86efac;
}

.badge-absent {
    background-color: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
}

.badge-leave {
    background-color: #dbeafe;
    color: #1d4ed8;
    border: 1px solid #93c5fd;
}

.badge-holiday {
    background-color: #f3e8ff;
    color: #6b21a8;
    border: 1px solid #d8b4fe;
}

.badge-weekend {
    background-color: #fef3c7;
    color: #b45309;
    border: 1px solid #fde047;
}

.badge-none {
    background-color: #f1f5f9;
    color: #94a3b8;
}

/* Time Block for Present State */
.present-time-block {
    background-color: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 4px;
    padding: 4px 6px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.time-row {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.7rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.time-row.in {
    color: #15803d;
}

.time-row.out {
    color: #dc2626;
}

.no-data {
    text-align: center;
}

/* Responsive Overrides */
@media (max-width: 992px) {
    .calendar-cell {
        min-height: 90px;
        padding: 4px;
    }
}
</style>
