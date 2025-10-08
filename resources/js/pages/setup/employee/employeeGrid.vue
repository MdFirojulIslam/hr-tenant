<template>
    <section style="background-color: #eee">
        <div class="text-center container py-5">
            <h2 class="mt-3 mb-3">Artisan Family</h2>
            <div class="row">
                <div
                    class="col-lg-2 col-md-4 mb-4"
                    v-for="employee in employees.data"
                    :key="employee.id"
                >
                    <div class="d-flex justify-content-center">
                        <div class="card">
                            <div
                                class="img-container"
                                v-for="i in employee.emp_img"
                                :key="i.id"
                            >
                                <img
                                    :src="i.img_url ? i.img_url : default_img"
                                    height="190px"
                                    width="190px"
                                    alt="no image"
                                />
                            </div>
                            <div class="card-body" style="height: 110px">
                                <h5 class="card-title mb-3">
                                    {{ employee.Full_Name }}
                                </h5>
                                <p>{{ employee.Contact_No }}</p>
                                <h6 class="mb-3">
                                    {{
                                        employee.Official_Email
                                            ? employee.Official_Email
                                            : ""
                                    }}
                                </h6>
                            </div>
                            <div class="card-footer">
                                <div d-flex>
                                    <button
                                        class="custom-btn btn-13"
                                        @click="
                                            router.push(
                                                `/setup/employee/empdetails/${employee.id}`
                                            )
                                        "
                                    >
                                        <i class="fa-regular fa-user"></i>
                                    </button>
                                    <button
                                        class="custom-btn btn-15 mx-2"
                                        @click="
                                            router.push({
                                                name: 'Employee',
                                                params: { id: employee.id },
                                            })
                                        "
                                    >
                                        <i
                                            class="fa-regular fa-pen-to-square"
                                        ></i>
                                    </button>
                                    <button
                                        class="custom-btn btn-13"
                                        @click="cvPdf(employee.id)"
                                    >
                                        <i class="fa-regular fa-file"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center">
                <Bootstrap4Pagination
                    :data="employees"
                    :limit="2"
                    @pagination-change-page="getData"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import router from "../../../router";
import api from "@/api";

import { useStore } from "vuex";
const store = useStore();

const employees = ref([]);
const isLoading = ref(true);
const error = ref(null);
const empEdit = ref(null);
const heading = ref(null);
const filter = ref("");
const default_img = ref("/storage/uploads/avater.jpg");

const getData = async (page = 1) => {
    try {
        const response = await api.get(`/employee?page=${page}`);
        employees.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

const cvPdf = async (id) => {
    window.open(`/api/cv-pdf/${id}`, "_blank");
};

const searchemp = async () => {
    if (filter.value.length > 0) {
        try {
            const response = await api.get("/employee/search", {
                params: {
                    query: filter.value,
                },
            });
            employees.value = response.data;
        } catch (error) {
            console.error(error);
        }
    } else {
        getData();
    }
};

const editHandler = async (id) => {
    try {
        const response = await api.get(`/employee/${id}/edit`);
        empEdit.value = response.data;
        heading.value = "Update";
        openModal();
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const mapMaritalStatus = (status) => {
    const statusMap = {
        M: "Married",
        U: "Unmarried",
    };

    return statusMap[status] || "N/A";
};

const mapGender = (status) => {
    const statusMap = {
        M: "Male",
        F: "Female",
        O: "Other",
    };

    return statusMap[status] || "N/A";
};

const submitHandler = async () => {
    await getData();
};

onMounted(() => getData());
</script>

<style scoped>
.card {
    width: 100%;
    max-width: 350px;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.img-container {
    width: 100%;
    display: flex;
    justify-content: center;
}

.img-container img {
    width: 100%;
    height: auto;
    object-fit: cover;
    border-bottom: 1px solid #ddd;
}

.card-body {
    padding: 5px;
    text-align: center;
}

.card-footer {
    padding: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f8f9fa;
    border-top: 1px solid #ddd;
}

@media (max-width: 768px) {
    .card {
        max-width: 100%;
    }

    .card-footer {
        justify-content: center;
    }

    .custom-btn {
        margin: 0 5px;
        padding: 8px;
    }
}
</style>
