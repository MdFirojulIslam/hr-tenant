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
    const res = await api.get(`/department-list/${user.id}`);
    department.value = res.data || [];
};

// -------------------------
// ✅ FIX: employee select handler
const onEmployeeChange = (val) => {
    selectedEmployeeId.value = val?.id || null;
};

// -------------------------
// ✅ FIX: department change handler
const onDeptChange = async () => {
    await getEmployee(searchDept.value);
    selectedEmployee.value = null;
    selectedEmployeeId.value = null;
};

const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        return;
    }

    const res = await api.get(`/adjustment-employees/${deptID}`);
    employee.value = res.data;
};

// -------------------------
const getMonthlyData = async () => {
    const res = await api.get("/calendar-date-report", {
        params: {
            month: selectedMonth.value,
            year: selectedYear.value,
            employee_id: selectedEmployeeId.value,
            department_id: searchDept.value,
        },
    });

    attendanceData.value = res?.data?.data ?? {};
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

    // empty cells
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

        // ✅ IMPORTANT FIX (timezone issue solved)
        const key = currentDate.toLocaleDateString("en-CA");

        const data = attendanceMap.value[key];

        cells.push({
            day: d,
            dateKey: key,
            data: data || null,
            isFuture,
        });
    }

    return cells;
});

// -------------------------
watch([selectedMonth, selectedYear, selectedEmployeeId], getMonthlyData);

// -------------------------
onMounted(() => {
    getMonthlyData();
    getData();
});
</script>

<template>
    <div class="container-fluid p-3">
        <h2 class="header-design">Artisan Calendar</h2>

        <!-- Filters -->
        <div class="row my-4">
            <div class="col-lg-3">
                <label class="form-label">Department</label>
                <select
                    v-model="searchDept"
                    class="form-control field-height field-background-colour field-hover"
                    :class="{ 'placeholder-text': !searchDept }"
                    @change="onDeptChange"
                >
                    <option value="">Select</option>
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
                    class="form-control field-height field-background-colour field-hover"
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
                    class="form-control field-height field-background-colour field-hover"
                >
                    <option v-for="y in 10" :key="y" :value="2020 + y">
                        {{ 2020 + y }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Calendar -->
        <div class="calendar">
            <div class="calendar-header">
                <div v-for="d in weekDays" :key="d" class="day-name">
                    {{ d }}
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

                        <div v-if="date.data" class="attendance-info">
                            <div
                                v-if="date.data.type === 'leave'"
                                class="status leave"
                            >
                                {{ date.data.purpose }}
                            </div>

                            <div
                                v-else-if="date.data.type === 'holiday'"
                                class="status holiday"
                            >
                                {{ date.data.holiday_name }}
                            </div>

                            <div
                                v-else-if="date.data.type === 'weekend'"
                                class="status weekend"
                            >
                                Weekend
                            </div>

                            <div
                                v-else-if="date.data.type === 'present'"
                                class="time-wrapper"
                            >
                                <div class="in-time">
                                    {{ formatTime(date.data.in_time) }}
                                </div>
                                <div class="out-time">
                                    {{ formatTime(date.data.out_time) }}
                                </div>
                            </div>

                            <div
                                v-else-if="date.data.type === 'absent'"
                                class="status absent"
                            >
                                Absent
                            </div>

                            <div v-else class="status">-</div>
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
    min-height: 80px;
    border: 1px solid #ddd;
    padding: 5px;
}

.date-number {
    font-weight: bold;
}

/* STATUS */
.status {
    text-align: center;
    font-size: 12px;
    font-weight: bold;
}

.absent {
    color: red;
}

.weekend {
    color: orange;
}

.leave {
    color: blue;
}

.holiday {
    color: purple;
}

/* TIME CENTER */
.time-wrapper {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 60px;
}

.in-time {
    font-size: 12px;
    color: green;
}
.out-time {
    font-size: 12px;
    color: red;
}

.form-label {
    font-weight: 600;
    letter-spacing: 0.4px;
}

.multiselect-design .multiselect__tags:hover {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
