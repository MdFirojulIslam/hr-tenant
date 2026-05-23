<script setup>
import { ref, onMounted, watch, computed } from "vue";
import api from "@/api";
import { RouterLink } from "vue-router";
import dashboardChart from "./commonComponent/dashboardChart.vue";
import ApexCharts from "vue3-apexcharts";

import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

import DataTable from "datatables.net-vue3";
import DataTablesCore from "datatables.net-bs5";

DataTable.use(DataTablesCore);

const dataMode = ref("all");
const dashCount = ref([]);
const pieSeries = ref([]);
const pieChartOptions = ref({});

const attendancePieSeries = ref([]);
const AttendancepieChartOptions = ref({});
const dashattendanceData = ref([]);

const attendanceTableData = ref([]);

const departmentList = ref([]);

const dateInput = ref(null);
const selectedDepartment = ref(null);
const currentDate = ref("");
const selectedDate = ref("");
const dashattendanceDataByDept = ref({});
const openCalendar = () => {
    dateInput.value.showPicker();
};

const getAttendanceTableData = async () => {
    try {
        const response = await api.get("/dashboard-attendance-table-data");
        attendanceTableData.value = response.data;
    } catch (error) {
        console.error("Error fetching attendance table data:", error);
    }
};

const attendanceDataTableData = ref([
    ["EMP001", "Firoj", "IT", 10, 2, 1, 2, "80%"],
    ["EMP002", "John Doe", "HR", 12, 0, 0, 1, "92%"],
    ["EMP003", "Jane Smith", "Finance", 9, 3, 2, 0, "75%"],
]);

const initializeChart = async () => {
    const dashCountres = await api.get("/dashboard-count");
    dashCount.value = dashCountres.data;
};

const setCurrentDate = () => {
    const today = new Date();

    selectedDate.value = today.toISOString().split("T")[0];

    currentDate.value = today.toLocaleDateString("en-US", {
        month: "short",
        day: "2-digit",
        year: "numeric",
    });
};

watch(selectedDate, (newDate) => {
    if (!newDate) return;

    currentDate.value = new Date(newDate).toLocaleDateString("en-US", {
        month: "short",
        day: "2-digit",
        year: "numeric",
    });

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
        console.error("Error fetching dashboard data by department:", error);
    }
};

const getData = async () => {
    try {
        const [responseDept, responseAtendanceData] = await Promise.all([
            api.get("/dashboard-department-list"),
            api.get("/dashboard-attendance-data", {
                params: {
                    date: selectedDate.value,
                },
            }),
        ]);
        departmentList.value = responseDept.data;
        dashattendanceData.value = responseAtendanceData.data;
    } catch (error) {
        console.error("Error fetching dashboard data:", error);
    }
};

const activeAttendanceData = computed(() => {
    return dataMode.value === "dept"
        ? dashattendanceDataByDept.value
        : dashattendanceData.value;
});

watch(activeAttendanceData, (newVal) => {
    if (!newVal) return;

    attendancePieSeries.value = [
        Number(newVal.total_present || 0),
        Number(newVal.total_absent || 0),
        Number(newVal.total_late || 0),
    ];

    AttendancepieChartOptions.value = {
        labels: [
            `Present - ${newVal.total_present || 0}`,
            `Absent - ${newVal.total_absent || 0}`,
            `Late - ${newVal.total_late || 0}`,
        ],
        colors: ["#6de975", "#ff7d4c", "#f7b731"],
    };
});

watch(
    dashCount,
    (newVal) => {
        if (
            newVal &&
            typeof newVal.employee_act === "number" &&
            typeof newVal.employee_inact === "number"
        ) {
            pieSeries.value = [newVal.employee_act, newVal.employee_inact];

            pieChartOptions.value = {
                labels: ["Active Employees", "Inactive Employees"],
                colors: ["#6de975", "#ff7d4c"],
            };
        }
    },
    { immediate: true },
);

onMounted(() => {
    initializeChart();
    setCurrentDate();
});
</script>

<template>
    <main>
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2 py-3">
                <i class="fa-solid fa-gauge dashboard-icon"></i>
                <h2 class="dashboard-title mb-0">Dashboard</h2>
            </div>

            <div class="row dashboard-background mb-3">
                <div col-md-12>
                    <div class="card p-2">
                        <div class="d-flex gap-3">
                            <div class="col-md-3 card p-2">
                                <div
                                    class="d-flex justify-content-between align-items-center"
                                >
                                    <div class="view-data-date">
                                        <div>Date Range</div>
                                        <div>{{ currentDate }}</div>
                                    </div>
                                    <label
                                        class="attendance-calender-select"
                                        @click="openCalendar()"
                                        style="cursor: pointer"
                                    >
                                        <i
                                            class="fa-solid fa-calendar-days"
                                        ></i>
                                        <input
                                            type="date"
                                            ref="dateInput"
                                            v-model="selectedDate"
                                            style="
                                                opacity: 0;
                                                position: absolute;
                                                pointer-events: none;
                                            "
                                        />
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card p-1">
                                    <multiselect
                                        v-model="selectedDepartment"
                                        :options="departmentList"
                                        label="Name"
                                        track-by="id"
                                        :searchable="true"
                                        :close-on-select="true"
                                        :allow-empty="true"
                                        placeholder="Select
                                    Department"
                                        class="mb-2"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mt-2">
                    <div class="card-grid gap-2">
                        <div class="card-design">
                            <div class="p-1">
                                <div class="height-define">
                                    <div class="grid-data">
                                        {{
                                            activeAttendanceData.active_employee
                                        }}
                                    </div>
                                    <div class="grid-data">
                                        {{
                                            activeAttendanceData.gulshan_office_total_employee
                                        }}
                                        /<span class="lob-location-status"
                                            >G</span
                                        >
                                        {{
                                            activeAttendanceData.warehouse_total_employee
                                        }}
                                        /<span class="lob-location-status"
                                            >W</span
                                        >
                                        {{
                                            activeAttendanceData.field_force_total_employee
                                        }}
                                        /<span class="lob-location-status"
                                            >F</span
                                        >
                                    </div>
                                </div>

                                <label class="card-title"
                                    >Total Employees
                                </label>
                            </div>
                        </div>
                        <div class="card-design">
                            <div class="p-1">
                                <div class="height-define">
                                    <div class="grid-data">
                                        P -
                                        {{ activeAttendanceData.total_present }}
                                        ({{ activeAttendanceData.avg_present
                                        }}<span>%</span>)
                                    </div>
                                    <div class="grid-data">
                                        A -
                                        {{ activeAttendanceData.total_absent }}
                                        ({{ activeAttendanceData.avg_absent
                                        }}<span>%</span>)
                                    </div>
                                </div>
                                <label class="card-title"> Attendance </label>
                            </div>
                        </div>
                        <div class="card-design">
                            <div class="p-1">
                                <div class="height-define">
                                    <div class="grid-data">
                                        Late -
                                        {{ activeAttendanceData.total_late }}
                                        ({{ activeAttendanceData.avg_late }}%)
                                    </div>
                                    <div class="grid-data">
                                        {{
                                            activeAttendanceData.average_work_hours
                                        }}
                                        Hours
                                    </div>
                                </div>
                                <label class="card-title">
                                    Average Working hours
                                </label>
                            </div>
                        </div>

                        <!-- <div class="card-design">
                            <div class="p-1">
                                <div class="card-values"> {{ dashCount.employee_inact }}
                                </div>
                                <label for="">Inactive Employees</label>
                            </div>
                        </div> -->

                        <div class="card-design">
                            <div class="p-1">
                                <div class="height-define">
                                    <div class="grid-data">
                                        Approved -
                                        {{ activeAttendanceData.total_leave }}
                                    </div>
                                    <div class="grid-data">
                                        Pending -
                                        {{
                                            activeAttendanceData.total_pending_leave
                                                ? activeAttendanceData.total_pending_leave
                                                : 0
                                        }}
                                    </div>
                                </div>
                                <label class="card-title"> Leave</label>
                            </div>
                        </div>

                        <div class="card-design">
                            <div class="p-1">
                                <div class="height-define">
                                    <div class="grid-data">
                                        {{ activeAttendanceData.averageTenure }}
                                        Years
                                    </div>
                                    <div class="grid-data"></div>
                                </div>
                                <label class="card-title">Average Tenure</label>
                            </div>
                        </div>

                        <!-- <div class="card-design">
                            <div class="p-1">
                                <div class="card-values"> {{ activeAttendanceData.total_absent }} - {{
                                    activeAttendanceData.avg_absent
                                }}%</div>
                                <label> Absent Employee </label>
                            </div>
                        </div> -->

                        <div class="card-design">
                            <div class="p-1">
                                <div class="single-line-height-define">
                                    <div class="grid-data">
                                        {{ activeAttendanceData.total_expense }}
                                        Taka/-
                                    </div>
                                </div>
                                <label class="card-title"> TA/DA (MTD) </label>
                            </div>
                        </div>
                        <div class="card-design">
                            <div class="p-1">
                                <div class="single-line-height-define">
                                    <div class="grid-data">
                                        {{
                                            activeAttendanceData.department_total
                                        }}
                                    </div>
                                </div>
                                <label class="card-title"
                                    >Department Count</label
                                >
                            </div>
                        </div>
                        <div class="card-design">
                            <div class="p-1">
                                <div class="single-line-height-define">
                                    <div class="grid-data">
                                        {{ activeAttendanceData.averageAge }}
                                        Years
                                    </div>
                                </div>
                                <label class="card-title">Average Age</label>
                            </div>
                        </div>
                        <!-- <div class="card-design">
                            <div class="p-1">
                                <div class="card-values"> {{
                                    activeAttendanceData.total_pending_leave ?
                                        activeAttendanceData.total_pending_leave
                                        : 0
                                }}</div>
                                <label> PENDING LEAVE </label>
                            </div>
                        </div> -->
                        <!-- <div class="card-design">
                            <div class="p-1">
                                <div class="card-values"> </div>
                                <label> TOTAL LEAVE </label>
                            </div>
                        </div> -->
                    </div>
                </div>

                <!-- <div class="col-md-6 mt-3">
                    <div class="card p-2 mb-4">
                        <div class="d-flex justify-content-space-between gap-2 mb-2">
                            <div class="col-md-8">
                                <div class="card p-2">
                                    <label class="d-block text-center mb-2 attendance-card-title">
                                        ATTENDANCE SUMMARY
                                    </label>
                                    <div class="">
                                        <ApexCharts type="donut" height="180" :options="AttendancepieChartOptions"
                                            :series="attendancePieSeries" />
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="d-flex justify-content-space-between gap-2 mb-2">
                        </div>
                        <div class="col-md-12 card p-2 mb-2">
                            <div class="d-flex justify-content-between mb-2">
                                <label class="attendance-table-header">EMPLOYEE ATTENDANCE OVERVIEW</label>
                                <label class="attendance-table-header export-excel">
                                    <i class="fa-solid fa-circle-arrow-down"></i>
                                </label>
                            </div>


                            <DataTable :data="attendanceDataTableData" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>EMP ID</th>
                                        <th>EMP NAME</th>
                                        <th>DEPARTMENT</th>
                                        <th>PRESENT</th>
                                        <th>ABSENT</th>
                                        <th>LATE</th>
                                        <th>LEAVE</th>
                                        <th>ATTENDANCE %</th>
                                    </tr>
                                </thead>
                            </DataTable>
                        </div>
                    </div>
                </div> -->
                <!-- <div class="col-xl-6 col-md-6 mt-3 ">
                    <div class="col-xl-12 col-md-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-pie me-1"></i>
                                Employee Active/Inactive
                            </div>
                            <div class="card-body">
                                <ApexCharts type="pie" height="122" :options="pieChartOptions" :series="pieSeries" />
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
            <dashboardChart :dashData="dashCount" />
        </div>
    </main>
</template>

<style scoped>
.dashboard-background {
    background-color: #f6f3f39c;
    padding-top: 7px;
    padding-bottom: 7px;
    border-radius: 5px;
}

.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 12px;
}

.first-dev {
    font-size: 15px;
}

.lob-location-status {
    font-size: 10px;
    margin-right: 6px;
}

.height-define {
    height: 55px;
}

.second-dev {
    font-size: 13px;
}

.single-line-height-define {
    height: 40px;
}

.first-span {
    font-size: 17px;
}

.card-title {
    font-family: sans-serif;
}

.card-design {
    background: #fff;
    border: 1px solid #dbe3ef;
    border-radius: 10px;
    padding: 12px;
    min-height: 92px;
    box-shadow: 0 4px 10px #020b190f;
}

.grid-data {
    font-family: "Times New Roman", Times, serif;
    font-weight: 600;
    font-size: 14px;
}

.card-values {
    font-weight: 500;
    font-size: 18px;
}

.fa-gauge {
    font-size: 20px;
    color: #000000;
    line-height: 1;
}

.dashboard-title {
    font-size: 25px;
    font-weight: 700;
    margin: 0;
    line-height: 1;
}

.attendance-title {
    font-size: 25px;
    font-weight: 700;
    color: #470202;
}

.fa-calendar-days {
    font-size: 25px;
    color: #515251;
}

.fa-calendar-days:hover {
    color: #c20606;
}

.view-data-date {
    font-size: 20px;
    font-weight: 700;
}

.fa-arrow-rotate-right {
    font-size: 25px;
    color: #515251;
}

.centered-content {
    display: flex;
    width: 100%;
    height: 100vh;
    /* Full viewport height */
    text-align: center;
    flex-direction: column;
    /* Ensure content stacks vertically */
}

h1 {
    margin: 0;
    font-size: 50px;
    font-weight: 700;
}

.highlight {
    font-size: 40px;
    font-weight: 700;
    color: #3a6b4c;
}

.number {
    font-size: 40px;
}

.attendance-card-title {
    font-size: 20px;
    font-weight: 700;
}

.fa-circle-check {
    font-size: 25px;
    color: #517453;
}

.fa-user-xmark {
    font-size: 25px;
    color: #ff7d4c;
}

.fa-clock {
    font-size: 25px;
    color: #ff7d4c;
}

.fa-umbrella {
    font-size: 25px;
    color: #517453;
}

.fa-alarm-clock {
    font-size: 25px;
    color: #517453;
}

.card-count {
    font-size: 20px;
    font-weight: 700;
}

.card-percentage {
    font-size: 15px;
    font-weight: 600;
}

.summary-name {
    font-size: 15px;
    font-weight: 600;
}

.attendance-table-header {
    font-size: 20px;
    font-weight: 700;
}

.fa-circle-arrow-down {
    font-size: 20px;
    color: #000000;
    margin-right: 5px;
    cursor: pointer;
}
</style>
