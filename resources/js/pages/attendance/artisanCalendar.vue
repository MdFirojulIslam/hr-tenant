<script setup>
import { ref, onMounted, computed, watch } from "vue";
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
const selectedEmployeeId = ref(null);

// -------------------------
// FORMAT TIME
// -------------------------
const formatTime = (timeStr) => {
    if (!timeStr) return "-";

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
// API CALLS
// -------------------------
const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        return;
    }

    const res = await api.get(`/adjustment-employees/${deptID}`);
    employee.value = res.data;
};

const getData = async () => {
    const res = await api.get(`/department-list/${user.id}`);
    department.value = res.data || [];
};

// -------------------------
// EVENTS
// -------------------------
const clearDepartment = async () => {
    searchDept.value = "";
    selectedEmployee.value = null;
    selectedEmployeeId.value = null;
    employee.value = [];
    await getData();
};

const onDeptChange = async () => {
    await getEmployee(searchDept.value);
    selectedEmployee.value = null;
    selectedEmployeeId.value = null;
};

const onEmployeeChange = () => {
    selectedEmployeeId.value = selectedEmployee.value?.id || null;
    getMonthlyData();
};

// -------------------------
// CALENDAR
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

const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());

const attendanceData = ref([]);

const weekDays = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];

const yearList = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let i = currentYear - 5; i <= currentYear + 5; i++) {
        years.push(i);
    }
    return years;
});

// -------------------------
// MAIN API
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

        attendanceData.value = res.data.data || [];
    } catch (err) {
        console.error(err);
    }
};

// -------------------------
// MAP DATA
// -------------------------
const attendanceMap = computed(() => {
    const map = {};
    attendanceData.value.forEach((item) => {
        if (!item.report_date) return;
        const day = new Date(item.report_date).getDate();
        map[day] = item;
    });
    return map;
});

// -------------------------
// STATUS HELPER
// -------------------------
const getStatusText = (data) => {
    if (!data) return "Absent";

    if (data.attendance_status === "Weekend" || data.is_weekend == 1) {
        return "Weekend";
    }

    return data.attendance_status || "Absent";
};

// -------------------------
// CALENDAR GRID (FIXED)
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

    // empty cells
    for (let i = 0; i < firstDay; i++) {
        cells.push(null);
    }

    // days
    for (let d = 1; d <= daysInMonth; d++) {
        const data = attendanceMap.value[d];

        cells.push({
            day: d,
            data: data || {
                attendance_status: "Absent",
                actual_in: null,
                actual_out: null,
            },
        });
    }

    return cells;
});

// -------------------------
watch([selectedMonth, selectedYear], getMonthlyData);

onMounted(() => {
    getMonthlyData();
    getData();
});
</script>

<template>
    <div class="container-fluid p-3">
        <h2 class="header-design">Artisan Calendar</h2>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-lg-3">
                <label class="form-label">Department</label>
                <select
                    v-model="searchDept"
                    class="form-control field-height field-background-colour"
                    :class="{ 'placeholder-text': !searchDept }"
                    @change="onDeptChange"
                >
                    <option value="">Select Department</option>
                    <option
                        v-for="d in department"
                        :key="d.department_id"
                        :value="d.department_id"
                    >
                        {{ d.department_name }}
                    </option>
                </select>
            </div>

            <div class="col-lg-3">
                <label class="form-label">Employee</label>
                <Multiselect
                    v-model="selectedEmployee"
                    :options="employee"
                    label="Full_Name"
                    track-by="id"
                    class="multiselect-design"
                    @select="onEmployeeChange"
                />
            </div>

            <div class="col-lg-1">
                <label class="form-label">Month</label>
                <select
                    v-model="selectedMonth"
                    class="form-control field-height field-background-colour"
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

            <div class="col-lg-1">
                <label class="form-label">Year</label>
                <select
                    v-model="selectedYear"
                    class="form-control field-height field-background-colour"
                >
                    <option v-for="y in yearList" :key="y" :value="y">
                        {{ y }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Calendar -->
        <div class="calendar">
            <div class="calendar-header">
                <div v-for="day in weekDays" :key="day" class="day-name">
                    {{ day }}
                </div>
            </div>

            <div class="calendar-body">
                <div
                    v-for="(date, index) in calendarDays"
                    :key="index"
                    class="calendar-cell"
                >
                    <div v-if="date">
                        <div class="date-number">{{ date.day }}</div>

                        <div class="attendance-info">
                            <div
                                class="status"
                                :class="{
                                    present:
                                        getStatusText(date.data) === 'Present',
                                    absent:
                                        getStatusText(date.data) === 'Absent',
                                    weekend:
                                        getStatusText(date.data) === 'Weekend',
                                    leave:
                                        getStatusText(date.data) === 'On Leave',
                                }"
                            >
                                {{ getStatusText(date.data) }}
                            </div>

                            <div class="in-time">
                                In: {{ formatTime(date.data.actual_in) }}
                            </div>

                            <div class="out-time">
                                Out: {{ formatTime(date.data.actual_out) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.calendar {
    border: 1px solid #ddd;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #444;
    color: white;
}

.day-name {
    padding: 10px;
    text-align: center;
}

.calendar-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendar-cell {
    min-height: 110px;
    border: 1px solid #ddd;
    padding: 5px;
}

.date-number {
    font-weight: bold;
}

.status {
    font-size: 12px;
}

.present {
    color: green;
    font-weight: bold;
}

.absent {
    color: red;
    font-weight: bold;
}

.weekend {
    color: orange;
    font-weight: bold;
}

.leave {
    color: blue;
    font-weight: bold;
}

.in-time {
    font-size: 11px;
    color: green;
}

.out-time {
    font-size: 11px;
    color: red;
}

.header-design {
    font-weight: 700;
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
