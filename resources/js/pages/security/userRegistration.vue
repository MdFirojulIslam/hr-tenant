<script setup>
import { onMounted, ref } from "vue";
import Swal from "sweetalert2";
import { useRouter } from "vue-router";
import api from "@/api";

const router = useRouter();

const department = ref([]);
const empp = ref([]);

const error = ref([]);

const form = ref({
    department: "",
    employee_Id: "",
    email: "",
    username: "",
    password: "",
    c_password: "",
});

const getData = async () => {
    try {
        const response = await api.get("/department");
        department.value = response.data;
    } catch (err) {
        if (err.response && err.response.status === 401) {
            router.push({ name: "Login" });
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

const getEmail = async (id) => {
    try {
        const response = await api.get(`/empEmail/${id}`);
        form.value.email = response.data.email;
        form.value.username = response.data.username;
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        if (typeof form.value[key] === "string") {
            form.value[key] = "";
        } else {
            form.value[key] = null; // or any other default value you prefer
        }
    });
};

const register = async () => {
    try {
        const response = await api.post("/register", form.value);
        if (response.data.success) {
            Swal.fire({
                position: "middle",
                icon: "success",
                title: response.data.message,
                showConfirmButton: false,
                timer: 1500,
            });
            const responseEmail = await api.post("/user-mail", form.value);
            resetForm();
        }
    } catch (e) {
        error.value = e.response.data.errors;
    }
};

onMounted(() => getData());
</script>

<template>
    <div class="form-wrapper">
        <div class="form-container">
            <img
                src="/public/storage/uploads/1718087744_artisan.png"
                height="150px"
                width="150px"
            />
            <form class="form" @submit.prevent="register">
                <select
                    name="status"
                    class="input"
                    v-model="form.department"
                    @change="getEmployee(form.department)"
                >
                    <option value="" disabled selected>
                        Choose Department
                    </option>
                    <option
                        v-for="dept in department"
                        :key="dept.id"
                        :value="dept.id"
                    >
                        {{ dept.Name }}
                    </option>
                </select>
                <select
                    class="input"
                    name="status"
                    id=""
                    v-model="form.employee_Id"
                    @change="getEmail(form.employee_Id)"
                >
                    <option value="" disabled selected>Choose Employee</option>
                    <option v-for="e in empp" :key="e.id" :value="e.id">
                        {{ e.Full_Name }}
                    </option>
                </select>
                <input
                    type="email"
                    class="input"
                    placeholder="Email"
                    v-model="form.email"
                />
                <span class="text-danger px-4">{{ error.username ? error.username[0] : '' }}</span>
                <input
                    type="text"
                    class="input"
                    placeholder="Username"
                    v-model="form.username"
                />
                <span class="text-danger px-4">{{ error.password ? error.password[0] : '' }}</span>
                <input
                    type="password"
                    class="input"
                    placeholder="Password"
                    v-model="form.password"
                />
                <span class="text-danger px-4">{{ error.c_password ? error.c_password[0] : '' }}</span>
                <input
                    type="password"
                    class="input"
                    placeholder="Password"
                    v-model="form.c_password"
                />
                <button class="form-btn">Register</button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.form-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 80vh;
}

.form-container {
    width: 350px;
    height: 620px;
    background-color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
    border-radius: 10px;
    box-sizing: border-box;
    padding: 20px 30px;
}

.form {
    width: 100%;
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.input {
    border-radius: 20px;
    border: 1px solid #c0c0c0;
    outline: 0 !important;
    box-sizing: border-box;
    padding: 10px 15px;
    color: #747474;
    margin-bottom: 12px;
}

.page-link {
    text-decoration: underline;
    margin: 0;
    text-align: end;
    color: #747474;
    text-decoration-color: #747474;
}

.page-link-label {
    cursor: pointer;
    font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
        "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
    font-size: 9px;
    font-weight: 700;
}

.page-link-label:hover {
    color: #000;
}

.form-btn {
    padding: 10px 15px;
    font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
        "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
    border-radius: 20px;
    border: 0 !important;
    outline: 0 !important;
    background: teal;
    color: white;
    cursor: pointer;
    box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
}

select {
    -webkit-appearance: none; /* Safari and Chrome */
    -moz-appearance: none; /* Firefox */
    appearance: none; /* Standard */
}
</style>
