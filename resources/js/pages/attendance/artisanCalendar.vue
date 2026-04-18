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
const searchDate = ref("");

/**
 * Month names
 */

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
        const response = await api.get(`/department-list/${user.id}`);

        department.value = response.data || [];
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

/**
 * State
 */
const selectedEmployeeId = ref(1);
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());

const attendanceData = ref([]);

/**
 * Week days
 */
const weekDays = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];

/**
 * Year list (±5 years)
 */
const yearList = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let i = currentYear - 5; i <= currentYear + 5; i++) {
        years.push(i);
    }
    return years;
});

/**
 * Dynamic calendar title
 */
const calendarTitle = computed(() => {
    return `${monthNames[selectedMonth.value - 1]} ${selectedYear.value}`;
});

/**
 * API call
 */
const getMonthlyData = async () => {
    const res = await api.get("/calendar-date-report", {
        params: {
            month: selectedMonth.value,
            year: selectedYear.value,
        },
    });

    attendanceData.value = res.data.data;
};

/**
 * Map attendance by date
 */
const attendanceMap = computed(() => {
    const map = {};
    attendanceData.value.forEach((item) => {
        const day = new Date(item.report_date).getDate();
        map[day] = item;
    });
    return map;
});

/**
 * Build calendar grid
 */
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

    // empty cells before start
    for (let i = 0; i < firstDay; i++) {
        cells.push(null);
    }

    // actual days
    for (let d = 1; d <= daysInMonth; d++) {
        cells.push({
            day: d,
            data: attendanceMap.value[d] || null,
        });
    }

    return cells;
});

/**
 * Watch changes → reload API
 */
watch([selectedMonth, selectedYear, selectedEmployeeId], () => {
    getMonthlyData();
});

/**
 * On load
 */
onMounted(() => {
    getMonthlyData();
    getData();
});
</script>

<template>
    <div class="container-fluid p-3">
        <!-- Controls -->
        <div class="d-flex gap-2 align-items-center mb-3">
            <!-- Month -->
            <select v-model="selectedMonth" class="form-select w-auto">
                <option
                    v-for="(month, index) in monthNames"
                    :key="index"
                    :value="index + 1"
                >
                    {{ month }}
                </option>
            </select>

            <!-- Year -->
            <select v-model="selectedYear" class="form-select w-auto">
                <option v-for="year in yearList" :key="year" :value="year">
                    {{ year }}
                </option>
            </select>
        </div>

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

        <!-- Title -->
        <h2 class="mb-3">{{ calendarTitle }}</h2>

        <!-- Calendar -->
        <div class="calendar">
            <!-- Week Days -->
            <div class="calendar-header">
                <div v-for="day in weekDays" :key="day" class="day-name">
                    {{ day }}
                </div>
            </div>

            <!-- Days -->
            <div class="calendar-body">
                <div
                    v-for="(date, index) in calendarDays"
                    :key="index"
                    class="calendar-cell"
                    :class="{ empty: !date }"
                >
                    <!-- Empty -->
                    <div v-if="!date"></div>

                    <!-- Date -->
                    <div v-else class="date-box">
                        <div class="date-number">
                            {{ date.day }}
                        </div>

                        <!-- Attendance -->
                        <div v-if="date.data" class="attendance-info">
                            <div
                                v-if="date.data.attendance_status === 'Holiday'"
                                class="holiday"
                            >
                                {{ date.data.holiday_name }}
                            </div>

                            <div v-else>
                                <div class="in-time">
                                    In: {{ date.data.actual_in || "-" }}
                                </div>

                                <div class="out-time">
                                    Out: {{ date.data.actual_out || "-" }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar {
    border: 1px solid #ddd;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #444;
    color: white;
    text-align: center;
    font-weight: bold;
}

.day-name {
    padding: 10px;
    border-right: 1px solid #666;
}

.calendar-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendar-cell {
    min-height: 100px;
    border: 1px solid #ddd;
    padding: 5px;
}

.empty {
    background: #f9f9f9;
}

.date-number {
    font-weight: bold;
    margin-bottom: 5px;
}

.in-time {
    font-size: 12px;
    color: green;
}

.out-time {
    font-size: 12px;
    color: red;
}

.holiday {
    font-size: 12px;
    color: blue;
    font-weight: bold;
}
</style>
