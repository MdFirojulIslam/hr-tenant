<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

import { useRoute } from "vue-router";

const route = useRoute();

const empId = parseInt(route.params.id);
const emp = ref([]);
const error = ref([]);
const emp_img = ref([]);

const getData = async () => {
    try {
        const response = await axios.get(`/api/employee/${empId}/edit`);
        emp.value = response.data;
        const responseimg = await axios.get(`/api/empimg/${empId}`);
        emp_img.value = responseimg.data;
        // console.log(emp_img.value);
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
    }
};

const mapGender = (status) => {
    const statusMap = {
        M: "Male",
        F: "Female",
        O: "Other",
    };

    return statusMap[status] || "N/A";
};

const mapStatus = (status) => {
    const statusMap = {
        M: "Married",
        U: "Unmarried",
        S: "Single",
    };

    return statusMap[status] || "N/A";
};

const mapShift = (status) => {
    const statusMap = {
        D: "Day",
        N: "Night",
    };

    return statusMap[status] || "N/A";
};

onMounted(() => getData());
</script>

<template>
    <div id="print-section">
        <div class="row align-items-center">
            <div class="col-lg-4">
                <div v-for="i in emp_img" :key="i.id">
                    <img :src="i.img_url" class="img-fluid" />
                </div>
            </div>
            <div class="col-lg-8 d-flex justify-content-end">
                <h1 class="pt-3 text-success">{{ emp.Full_Name }}</h1>
            </div>
        </div>
    </div>
</template>

<style scoped>
#print-section {
    margin: 20px;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    border: 1px solid #ddd;
    padding: 8px;
}

.table th {
    background-color: #f2f2f2;
    text-align: left;
}
</style>
