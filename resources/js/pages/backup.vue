<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";
import { RouterLink } from "vue-router";
import dashboardChart from "./commonComponent/dashboardChart.vue";

const dashCount = ref([]);

const initializeChart = async () => {
    const dashCountres = await api.get("/dashboard-count");
    dashCount.value = dashCountres.data;
};

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
                <div class="col-xl-2 col-md-6">
                    <div class="card bg-success bg-gradient text-white mb-4">
                        <div class="card-body d-flex justify-content-between">
                            <div>Head Count</div>
                            <div>{{ dashCount.employee_act }}</div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <router-link class="small text-white stretched-link" :to="{ name: 'EmployeeList' }">View
                                Details</router-link>
                            <div class="small text-white">
                                <i class="fas fa-angle-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-6">
                    <div class="card bg-info text-white mb-4">
                        <div class="card-body d-flex justify-content-between">
                            <div>Department Count</div>
                            <div>{{ dashCount.department }}</div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <router-link class="small text-white stretched-link" :to="{ name: 'EmployeeList' }">View
                                Details</router-link>
                            <div class="small text-white">
                                <i class="fas fa-angle-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-6">
                    <div class="card bg-dark bg-gradient text-white mb-4">
                        <div class="card-body d-flex justify-content-between">
                            <div>Inactive Employees</div>
                            <div>{{ dashCount.employee_inact }}</div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <router-link class="small text-white stretched-link" :to="{ name: 'EmployeeList' }">View
                                Details</router-link>
                            <div class="small text-white">
                                <i class="fas fa-angle-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-6">
                    <div class="card bg-danger text-white mb-4">
                        <div class="card-body d-flex justify-content-between">
                            <div>Leave Count</div>
                            <div>{{ dashCount.leave }}</div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            View Details
                            <div class="small text-white">
                                <i class="fas fa-angle-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-6">
                    <div class="card bg-danger text-white mb-4">
                        <div class="card-body d-flex justify-content-between">
                            <div>Average Age</div>
                            <div>{{ dashCount.average_age }}</div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            View Details
                            <div class="small text-white">
                                <i class="fas fa-angle-right"></i>
                            </div>
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