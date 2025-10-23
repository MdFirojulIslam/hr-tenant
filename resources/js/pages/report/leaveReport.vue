<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 grid-margin stretch-card">
                <div class="row">
                    <!-- Form Section -->
                    <div class="col-lg-2 col-md-12 col-sm-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center">
                                    <h3 class="mb-5">Leave Report</h3>
                                </div>
                                <form
                                    class="forms-sample"
                                    @submit.prevent="submit"
                                >
                                    <div class="row">
                                        <div
                                            class="col-lg-12 col-md-12 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >Department</label
                                                >
                                                <select
                                                    class="form-control"
                                                    v-model="form.department"
                                                    @change="
                                                        getEmployee(
                                                            form.department
                                                        )
                                                    "
                                                >
                                                    <option selected disabled>
                                                        select
                                                    </option>
                                                    <option
                                                        v-for="dept in department"
                                                        :key="dept.id"
                                                        :value="dept.id"
                                                    >
                                                        {{ dept.Name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div
                                            class="col-lg-12 col-md-12 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >Employee Name</label
                                                >
                                                <select
                                                    class="form-control"
                                                    v-model="form.Employee_Id"
                                                >
                                                    <option
                                                        v-for="e in employee"
                                                        :key="e.id"
                                                        :value="e.id"
                                                    >
                                                        {{ e.Full_Name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div
                                            class="col-lg-12 col-md-12 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >From Date</label
                                                >
                                                <input
                                                    type="date"
                                                    class="form-control"
                                                    v-model="form.from_date"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            class="col-lg-12 col-md-12 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >To Date</label
                                                >
                                                <input
                                                    type="date"
                                                    class="form-control"
                                                    v-model="form.to_date"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            class="col-lg-12 col-md-12 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >Template</label
                                                >
                                                <select
                                                    class="form-control"
                                                    v-model="form.template"
                                                >
                                                    <option
                                                        value="Leave-report"
                                                    >
                                                        Leave Report
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <button
                                                type="submit"
                                                class="btn btn-primary form-control"
                                            >
                                                Preview
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- PDF Preview Section -->
                    <div class="col-lg-10 col-md-12 col-sm-12 mb-4">
                        <div class="card">
                            <div class="card-title text-center">
                                <h2 class="mt-2">Report Viewer</h2>
                            </div>
                        </div>
                        <div
                            class="card-body"
                            style="height: 85vh; border: 2px solid #ddd"
                        >
                            <iframe
                                v-if="pdfUrl"
                                :src="pdfUrl"
                                width="100%"
                                style="height: 85vh"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";

const department = ref([]);
const employee = ref([]);
const pdfUrl = ref("");
const error = ref([]);

const form = ref({
    department: "",
    Employee_Id: "",
    from_date: "",
    to_date: "",
    template: "",
});

const getData = async () => {
    try {
        const response = await api.get("/department");
        department.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const getEmployee = async (id) => {
    try {
        const response = await api.get(`/all-employee/${id}`);
        employee.value = response.data;
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

const submit = async () => {
    const params = new URLSearchParams({
        department: form.value.department,
        Employee_Id: form.value.Employee_Id,
        from_date: form.value.from_date,
        to_date: form.value.to_date,
        template: form.value.template,
    });

    if (form.value.template == "Leave-report") {
        pdfUrl.value = `/leave-report/${params.toString()}`;
    }
};

onMounted(() => getData());
</script>
