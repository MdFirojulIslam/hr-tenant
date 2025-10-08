<script setup>
import axios from "axios";
import { onMounted, ref } from "vue";
import Swal from "sweetalert2";
import { useRouter } from 'vue-router';
import api from "../api";

const router = useRouter();

const department = ref([]);
const empp = ref([]);

const error = ref([]);

const form = ref({
    department: "",
    employee_Id: "",
    name: "",
    email: "",
    password: "",
    c_password: "",
});

const getData = async () => {
    try {
        const response = await api.get("/department");
        department.value = response.data;
    } catch (err) {
        if (err.response && err.response.status === 401) {
            router.push({ name: 'Login' });
        } else {
            error.value = err.message || "Error fetching data";
        }
    } finally {
    }
};

const getEmployee = async (id) => {
    try {
        const response = await api.get(`/emp/${id}`);
        empp.value = response.data;
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

const resetForm = () => {
    Object.keys(employee.value).forEach((key) => {
        if (typeof employee.value[key] === "string") {
            employee.value[key] = "";
        } else {
            employee.value[key] = null; // or any other default value you prefer
        }
    });
};

const register = async () => {
    await api
        .post("/register", form.value)
        .then((res) => {
            if (res.data.success) {
                router.push({ name: "Login" });
                resetForm();
                Swal.fire({
                    title: "Success",
                    text: "Registered Successfully",
                    icon: "success",
                    confirmButtonText: "Ok"
                });
            }
        })
        .catch((e) => {
            error.value = e.response.data.message;
        });
};

onMounted(() => getData());
</script>
<template>
    <div class="text-center">
        <img
            src="/public/storage/uploads/1718087744_artisan.png"
            height="300px"
            width="350px"
        />
        <h1>Artisan Human Resource Information System</h1>
    </div>
    <div class="container-scroller mt-5">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="row w-100 m-0">
                <div
                    class="content-wrapper full-page-wrapper d-flex align-items-center auth login-bg"
                >
                    <div class="card col-lg-4 mx-auto">
                        <div class="card-body px-5 py-5">
                            <h3 class="card-title text-center mb-3">
                                Register
                            </h3>
                            <form @submit.prevent="register">
                                <div class="form-group">
                                    <label>Department</label>
                                    <select
                                        class="form-control"
                                        name="status"
                                        id=""
                                        v-model="form.department"
                                        @change="getEmployee(form.department)"
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
                                <div class="form-group">
                                    <label>Employee Name</label>
                                    <select
                                        class="form-control"
                                        name="status"
                                        id=""
                                        v-model="form.employee_Id"
                                    >
                                        <option
                                            v-for="e in empp"
                                            :key="e.id"
                                            :value="e.id"
                                        >
                                            {{ e.Full_Name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input
                                        type="email"
                                        class="form-control p_input"
                                        v-model="form.email"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <input
                                        type="password"
                                        class="form-control p_input"
                                        v-model="form.password"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <input
                                        type="password"
                                        class="form-control p_input"
                                        v-model="form.c_password"
                                    />
                                </div>

                                <div class="text-left mt-3">
                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-block enter-btn"
                                    >
                                        Register
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- content-wrapper ends -->
            </div>
            <!-- row ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
</template>

