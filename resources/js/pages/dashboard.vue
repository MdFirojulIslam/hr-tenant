<script setup>
import { ref, onMounted, watch } from "vue";
import api from "@/api";
import { RouterLink } from "vue-router";
import dashboardChart from "./commonComponent/dashboardChart.vue";
import ApexCharts from "vue3-apexcharts";

const dashCount = ref([]);
const pieSeries = ref([]);
const pieChartOptions = ref({});

const initializeChart = async () => {
    const dashCountres = await api.get("/dashboard-count");
    dashCount.value = dashCountres.data;
};

watch(dashCount, (newVal) => {
    if (
        newVal &&
        typeof newVal.employee_act === 'number' &&
        typeof newVal.employee_inact === 'number'
    ) {
        pieSeries.value = [
            newVal.employee_act,
            newVal.employee_inact
        ];

        pieChartOptions.value = {
            labels: ['Active Employees', 'Inactive Employees'],
            colors: ["#6de975", "#ff7d4c"],
        };
    }
}, { immediate: true });


onMounted(() => initializeChart());
</script>

<template>
    <main>
        <div class="container-fluid px-4">
            <h1 class="mt-4">Dashboard</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>

            <div class="row">
                <div class="col-xl-8 col-md-8">
                    <div class="row">
                        <div class="col-xl-4 col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h3>{{ dashCount.employee_act }}</h3>
                                    <h5>Employee Count</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h3>{{ dashCount.department }}</h3>
                                    <h5>Department Count</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h3>{{ dashCount.employee_inact }}</h3>
                                    <h5>Inactive Employees</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="row">
                        <div class="col-xl-4 col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h3>{{ dashCount.pending_leave }}</h3>
                                    <h5>Pending Leave Count</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h3>{{ dashCount.average_age }} Years</h3>
                                    <h5>Average Age</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h3>{{ dashCount.average_tenure }} Years</h3>
                                    <h5>Average Employee Tenure</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-8">
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
            </div>
            <dashboardChart :dashData="dashCount" />
        </div>
    </main>
</template>

<style scoped>
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
</style>