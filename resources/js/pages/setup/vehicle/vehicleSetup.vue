<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const error = ref(null);
const employees = ref(null);
const vehicles = ref(null);
const selectedVehicle = ref(null);

const form = ref({
    id: "",
    vehicle_id: "",
    vehicle_name: "",
    model_name: "",
    reg_no: "",
    engine_no: "",
    chassis_no: "",
    supervisor_id: "",
    tax_expire_date: "",
    fitness_expire_date: "",
    driver_id: "",
    tracking_no: "",
    image: null,
});

const getData = async () => {
    try {
        const response = await api.get("/get-supervisor");
        employees.value = response.data;
        const res = await api.get("/get-vehicle");
        vehicles.value = res.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        if (typeof form.value[key] === "string") {
            form.value[key] = "";
        } else {
            form.value[key] = null;
        }
    });
};

const editInfo = (id) => {
    selectedVehicle.value = vehicles.value.find(vehicle => vehicle.id === id);
    if (selectedVehicle) {
        form.value = {
            id: selectedVehicle.value.id,
            vehicle_id: selectedVehicle.value.Vehicle_Id || "",
            vehicle_name: selectedVehicle.value.Name || "",
            model_name: selectedVehicle.value.Model || "",
            reg_no: selectedVehicle.value.Reg_No || "",
            engine_no: selectedVehicle.value.Engine_No || "",
            chassis_no: selectedVehicle.value.Chassis_No || "",
            supervisor_id: selectedVehicle.value.supervisor?.id || "",
            tax_expire_date: selectedVehicle.value.Tax_Expire_Date || "",
            fitness_expire_date: selectedVehicle.value.Fitness_Expire_Date || "",
            driver_id: selectedVehicle.value.driver?.id || "",
            tracking_no: selectedVehicle.value.Tracking_No || "",
            image: null,
        };
    }
};


const getImage = (e) => {
    form.value.image = e.target.files[0];
};


const submit = async () => {
    try {
        const config = {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        };
        const formdata = new FormData();
        formdata.append("file", form.image);

        for (const key in form.value) {
            if (form.value.hasOwnProperty(key) && key !== "file") {
                formdata.append(key, form.value[key]);
            }
        }

        let response;
        if (form.value.id) {
            // Update existing vehicle
            response = await api.post(`/update-vehicle/${form.value.id}`, formdata, config);
        } else {
            // Create a new vehicle
            response = await api.post("/add-vehicle", formdata, config);
        }
        if (response.data.success) {
            resetForm();
            Swal.fire({
                icon: 'success',
                title: form.value.id ? 'Vehicle Updated Successfully' : 'Vehicle Added Successfully',
                showConfirmButton: false,
                timer: 1500
            });
            getData();
        }
    } catch (err) {
        error.value = err.message || "Error submitting data";
    }
};


onMounted(() => getData());
</script>

<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <h1 class="mb-5">Vehicle Information Setup</h1>
                        </div>
                        <!-- Form Section -->
                        <form class="forms-sample" @submit.prevent="submit">
                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Vehicle ID</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">AV</span>
                                            </div>
                                            <input type="text" class="form-control" id="disabledTextInput" value="Auto"
                                                v-model="form.vehicle_id" readonly />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Vehicle Name</label>
                                        <input type="text" class="form-control" v-model="form.vehicle_name" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Model Name</label>
                                        <input type="text" class="form-control" v-model="form.model_name" />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Registration No</label>
                                        <input type="text" class="form-control" v-model="form.reg_no" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Engine No</label>
                                        <input type="text" class="form-control" v-model="form.engine_no" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Chassis No</label>
                                        <input type="text" class="form-control" v-model="form.chassis_no" />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Supervisor Name</label>
                                        <select class="form-control" v-model="form.supervisor_id">
                                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{
                                                emp.Full_Name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Tax Token Expire Date</label>
                                        <input type="date" class="form-control" v-model="form.tax_expire_date" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Fitness Expire Date</label>
                                        <input type="date" class="form-control" v-model="form.fitness_expire_date" />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Driver Name</label>
                                        <select class="form-control" v-model="form.driver_id">
                                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{
                                                emp.Full_Name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Tracking No</label>
                                        <input type="text" class="form-control" v-model="form.tracking_no" />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1">Vehicle Image</label>
                                        <input type="file" class="form-control" id="documentUpload"
                                            @change="getImage" />
                                    </div>
                                </div>
                            </div>
                        </form>

                        <button class="btn btn-success" @click="submit()" type="submit">Submit</button>

                    </div>
                </div>
            </div>

            <!-- Leave List Section -->
            <div class="col-12 mt-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <h1 class="mb-5">Vehicle List</h1>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Vehicle No</th>
                                        <th>Vehicle Name</th>
                                        <th>Driver Name</th>
                                        <th>Supervisor Name</th>
                                        <th>Tax Token Expire Date</th>
                                        <th>Fitness Expire Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item, index in vehicles" :key="item.id" @click="editInfo(item.id)">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ item.Vehicle_Id }}</td>
                                        <td>{{ item.Name }}</td>
                                        <td>{{ item.driver.Full_Name }}</td>
                                        <td>{{ item.supervisor.Full_Name }}</td>
                                        <td>{{ item.Tax_Expire_Date }}</td>
                                        <td>{{ item.Fitness_Expire_Date }}</td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</template>

<style scoped>
tbody tr {
    cursor: pointer;
}
</style>