<template>
    <!-- <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Department Wise Employee
                </div>
                <div class="card-body">
                    <ApexCharts type="area" height="200" :options="areaChartOptions" :series="areaSeries"></ApexCharts>
                </div>
            </div>
        </div>
    </div> -->
    <div class="row">
        <!-- <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Division Wise Employee
                </div>
                <div class="card-body">
                    <ApexCharts
                        type="donut"
                        height="213"
                        :options="pieChartOptions"
                        :series="pieSeries"
                    ></ApexCharts>
                </div>
            </div>
        </div> -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Employee Turnover Rate
                </div>
                <div class="card-body">
                    <ApexCharts
                        type="bar"
                        height="200"
                        :options="barChartOptions"
                        :series="barSeries"
                    ></ApexCharts>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Month Wise TA/DA
                </div>
                <div class="card-body">
                    <ApexCharts
                        type="area"
                        height="200"
                        :options="expenseChartOptions"
                        :series="expenseSeries"
                    >
                    </ApexCharts>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Department Wise Leave
                </div>
                <div class="card-body">
                    <ApexCharts
                        type="area"
                        height="200"
                        :options="areaLeaveChartOptions"
                        :series="areaLeaveSeries"
                    >
                    </ApexCharts>
                </div>
            </div>
        </div> -->
    </div>
    <!-- <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Monthly Attendace Overview
                </div>
                <div class="card-body">
                    <ApexCharts
                        type="area"
                        height="200"
                        :options="attendanceChartOptions"
                        :series="attendanceSeries"
                    >
                    </ApexCharts>
                </div>
            </div>
        </div>
    </div> -->
</template>

<script setup>
import { ref, watch, defineProps } from "vue";
import ApexCharts from "vue3-apexcharts";

const props = defineProps({
    dashData: Object,
});

const areaSeries = ref([]);
const areaChartOptions = ref({});

const areaLeaveSeries = ref([]);
const areaLeaveChartOptions = ref({});

const expenseSeries = ref([]);
const expenseChartOptions = ref({});

const barSeries = ref([]);
const barChartOptions = ref({});

const pieSeries = ref([]);
const pieChartOptions = ref({});

const attendanceSeries = ref([]);
const attendanceChartOptions = ref({});

watch(
    () => props.dashData,
    (newData) => {
        if (!newData || !newData.dept_wise_Emp) return;

        const departmentNames = newData.dept_wise_Emp.map((dept) => dept.Name);
        const employeeCounts = newData.dept_wise_Emp.map(
            (dept) => dept.employee_count,
        );

        const deptNames = newData.dept_wise_leave.map(
            (dept) => dept.department_name,
        );
        const leaveCounts = newData.dept_wise_leave.map(
            (dept) => dept.leave_count,
        );
        const divisionCounts = newData.division_wise_Emp.map(
            (div) => div.employee_count,
        );
        const divisionNames = newData.division_wise_Emp.map(
            (div) => div.Division,
        );
        const turnover_rate = newData.turnover_data.map(
            (item) => item.turnover_rate,
        );
        const turnover_year = newData.turnover_data.map((item) => item.year);
        const expense_months = newData.expense_date.map((item) => item.month);
        const expense_data = newData.expense_date.map(
            (item) => item.total_expense,
        );

        const colors = employeeCounts.map(
            () => "#" + Math.floor(Math.random() * 16777215).toString(16),
        );

        // Set Area Chart
        areaSeries.value = [
            {
                name: "Employees",
                data: employeeCounts,
            },
        ];
        areaChartOptions.value = {
            xaxis: {
                categories: departmentNames,
            },
            colors: ["#1d6460"],
        };

        //Set Dept Wise Leave
        areaLeaveSeries.value = [
            {
                name: "Employees",
                data: leaveCounts,
            },
        ];
        areaLeaveChartOptions.value = {
            xaxis: {
                categories: deptNames,
            },
            colors: ["#1d6460"],
        };

        // Set Expense Area Chart
        expenseSeries.value = [
            {
                name: "Employees",
                data: expense_data,
            },
        ];
        expenseChartOptions.value = {
            xaxis: {
                categories: expense_months,
            },
            colors: ["#1d6460"],
        };

        // Bar Area Chart
        barSeries.value = [
            {
                name: "Employees",
                data: turnover_rate,
            },
        ];
        barChartOptions.value = {
            xaxis: {
                categories: turnover_year,
            },
            colors: ["#1d6460"],
        };

        // Set Pie Chart
        pieSeries.value = divisionCounts;
        pieChartOptions.value = {
            labels: divisionNames,
            colors: ["#6de1e9", "#6de975", "#e98f6d"],
            pie: {
                expandOnClick: false,
            },
        };

        if (newData.attendace_data) {
            const attendanceMonths = Object.values(
                newData.attendace_data.months,
            );

            const presentData = Object.values(newData.attendace_data.present);
            const absentData = Object.values(newData.attendace_data.absent);
            const leaveData = Object.values(newData.attendace_data.leave);

            attendanceSeries.value = [
                {
                    name: "Present",
                    data: presentData,
                },
                {
                    name: "Absent",
                    data: absentData,
                },
                {
                    name: "Leave",
                    data: leaveData,
                },
            ];

            attendanceChartOptions.value = {
                chart: {
                    type: "line",
                    height: 350,
                },
                stroke: {
                    curve: "smooth",
                    width: 2,
                },
                markers: {
                    size: 4,
                },
                xaxis: {
                    categories: attendanceMonths,
                },
                colors: ["#28a745", "#dc3545", "#ffc107"], // green, red, yellow
                legend: {
                    position: "top",
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                },
            };
        }
    },
    { immediate: true }, // Run on initial mount as well
);
</script>
