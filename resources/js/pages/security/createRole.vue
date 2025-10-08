<script setup>
import { ref, onMounted, watch } from "vue";
import api from "../../api";

const permissions = ref([]);
const users = ref([]);
const roles = ref([]);
const attendences = ref([]);

const error = ref([]);

const form = ref({
    department: "",
    Employee_Id: "",
    From_Date: "",
    To_Date: "",
});

const getData = async () => {
    try {
        const [responsePermission, responseUser, responseRole] =
            await axios.all([
                api.get("/permission"),
                api.get("/get-user"),
                api.get("/get-role"),
            ]);

        permissions.value = responsePermission.data;
        users.value = responseUser.data;
        roles.value = responseRole.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
    }
};

const getUser = async (id) => {
    try {
        const response = await axios.get(`/api/emp/${id}`);
        empp.value = response.data;
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        if (typeof form.value[key] === "string") {
            form.value[key] = "";
        }
    });
};

const submit = async () => {
    try {
        const response = await axios.post(`/api/attendence/edit`, form.value);
        attendences.value = response.data;
        if (response.data.success) {
            resetForm();
            alert("Successfully Inserted Attendence");
        }
    } catch (error) {
        console.error("Error submitting Attendence:", error);
    }
};

onMounted(() => getData());
</script>

<template>
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="container">
            <div class="card">
                <div class="card-body">
                    <div class="text-center">
                        <h1 class="mb-5">Role Management</h1>
                    </div>
                    <div class="d-flex">
                        <div class="col-lg-4">
                            <form @submit.prevent="roleCreate">
                                <div class="row">
                                    <h3 class="text-center mb-4">
                                        Create Role
                                    </h3>
                                    <div class="form-group">
                                        <label for="exampleInputEmail1"
                                            >Role Name</label
                                        >
                                        <input
                                            type="text"
                                            class="form-control"
                                        />
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1"
                                            >Permissions</label
                                        >
                                        <div class="d-flex align-items-center">
                                            <div
                                                class="d-flex align-items-center"
                                                v-for="permission in permissions"
                                                :key="permission.id"
                                            >
                                                <input
                                                    type="checkbox"
                                                    v-model="inactive"
                                                />
                                                <label class="px-2 pt-0">{{
                                                    permission.name
                                                }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-lg-12">
                                        <input
                                            type="submit"
                                            class="btn-submit"
                                        />
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-lg-4"></div>

                        <div class="col-lg-4">
                            <h3 class="text-center mb-4">Assign Role</h3>
                            <form @submit.prevent="roleSubmit">
                                <div class="form-group">
                                    <label for="exampleInputEmail1"
                                        >User Name</label
                                    >
                                    <select class="form-control" id="">
                                        <option selected disabled>
                                            select
                                        </option>
                                        <option
                                            v-for="user in users"
                                            :key="user.id"
                                            :value="user.id"
                                        >
                                            {{ user.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group mt-2">
                                    <label for="exampleInputEmail1"
                                        >Role Name</label
                                    >
                                    <select class="form-control" id="">
                                        <option selected disabled>
                                            select
                                        </option>
                                        <option
                                            v-for="role in roles"
                                            :key="role.id"
                                            :value="role.id"
                                        >
                                            {{ role.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg-12 mt-3">
                                    <input type="submit" class="btn-submit" />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-body">
                    <h4 class="card-title">Attendence table</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(attend, index) in attendences"
                                    :key="attend.id"
                                >
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ attend.Date }}</td>
                                    <td>{{ attend.Time_In }}</td>
                                    <td>{{ attend.Time_Out }}</td>
                                    <td>{{ attend.Status }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <br />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
