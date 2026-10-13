<template>
    <div class="chart-grid">
        <Card class="chart-card">
            <template #title>
                <div class="chart-card-header">
                    <span class="chart-card-icon icon-indigo">
                        <i class="pi pi-chart-bar"></i>
                    </span>
                    <div class="chart-card-heading">
                        <h3 class="chart-card-title">Employee Turnover Rate</h3>
                        <p class="chart-card-sub">Yearly turnover trend</p>
                    </div>
                </div>
            </template>
            <template #content>
                <div class="chart-body">
                    <ApexCharts
                        type="bar"
                        height="260"
                        :options="barChartOptions"
                        :series="barSeries"
                    ></ApexCharts>
                </div>
            </template>
        </Card>

        <Card class="chart-card">
            <template #title>
                <div class="chart-card-header">
                    <span class="chart-card-icon icon-green">
                        <i class="pi pi-chart-line"></i>
                    </span>
                    <div class="chart-card-heading">
                        <h3 class="chart-card-title">Month Wise TA/DA</h3>
                        <p class="chart-card-sub">
                            Monthly expense distribution
                        </p>
                    </div>
                </div>
            </template>
            <template #content>
                <div class="chart-body">
                    <ApexCharts
                        type="area"
                        height="260"
                        :options="expenseChartOptions"
                        :series="expenseSeries"
                    >
                    </ApexCharts>
                </div>
            </template>
        </Card>
    </div>
</template>

<script setup>
import { ref, watch, defineProps } from "vue";
import ApexCharts from "vue3-apexcharts";
import Card from "primevue/card";

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
            chart: {
                toolbar: { show: false },
                fontFamily: "inherit",
            },
            dataLabels: { enabled: false },
            stroke: {
                curve: "smooth",
                width: 3,
            },
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 100],
                },
            },
            grid: {
                borderColor: "#e2e8f0",
                strokeDashArray: 4,
            },
            xaxis: {
                categories: expense_months,
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            colors: ["#10b981"],
        };

        // Bar Area Chart
        barSeries.value = [
            {
                name: "Employees",
                data: turnover_rate,
            },
        ];
        barChartOptions.value = {
            chart: {
                toolbar: { show: false },
                fontFamily: "inherit",
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: "42%",
                    borderRadiusApplication: "end",
                },
            },
            dataLabels: { enabled: false },
            grid: {
                borderColor: "#e2e8f0",
                strokeDashArray: 4,
            },
            xaxis: {
                categories: turnover_year,
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            colors: ["#6366f1"],
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

<style scoped>
.chart-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.chart-card {
    border-radius: var(--p-content-border-radius, 12px);
}

.chart-card :deep(.p-card-body) {
    padding: 1.25rem;
}

.chart-card :deep(.p-card-title) {
    margin-bottom: 0.75rem;
}

.chart-card :deep(.p-card-content) {
    padding: 0;
}

.chart-card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.chart-card-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.icon-indigo {
    color: #6366f1;
    background: rgba(99, 102, 241, 0.12);
}

.icon-green {
    color: #10b981;
    background: rgba(16, 185, 129, 0.12);
}

.chart-card-heading {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.chart-card-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--p-text-color, #0f172a);
    line-height: 1.3;
}

.chart-card-sub {
    margin: 0.1rem 0 0;
    font-size: 0.75rem;
    color: var(--p-text-muted-color, #64748b);
}

.chart-body {
    width: 100%;
}

@media (max-width: 991.98px) {
    .chart-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
