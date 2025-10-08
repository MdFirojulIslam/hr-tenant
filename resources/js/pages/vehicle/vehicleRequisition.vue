<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;

const error = ref(null);
const employees = ref([]);
const vehicles = ref([]);
const vehicleInfo = ref(null);
const userRequisition = ref([]);

const form = ref({
    user_id: user.id,
    vehicle_id: "",
    from_date: "",
    to_date: "",
    pickup_time: "",
    drop_time: "",
    pickup_location: "",
    drop_location: "",
    purpose: "",
    description: "",
});

const getData = async () => {
    try {
        const [supervisorRes, vehicleRes, requisitionRes] = await Promise.all([
            api.get("/get-supervisor"),
            api.get("/get-vehicle"),
            api.get("/get-user-requisition")
        ]);
        employees.value = supervisorRes.data;
        vehicles.value = vehicleRes.data;
        userRequisition.value = requisitionRes.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const getVehicle = async (id) => {
    try {
        const response = await api.get(`/get-vehicle/${id}`);
        vehicleInfo.value = response.data;

        const bookingResponse = await api.get(`/vehicle-bookings/${id}`);

        const unavailableRanges = bookingResponse.data.map(b => ({
            from: b.From_Date,
            to: b.To_Date
        }));

        console.log(unavailableRanges);

        flatpickr("#fromDate", {
            minDate: "today",
            disable: unavailableRanges.map(range => ({
                from: range.from,
                to: range.to
            }))
        });

        flatpickr("#toDate", {
            minDate: "today",
            disable: unavailableRanges.map(range => ({
                from: range.from,
                to: range.to
            }))
        });

        const unavailableTimes = bookingResponse.data.map(b => ({
            from: b.Pickup_Time,
            to: b.Drop_Time,
            dateRange: { from: b.From_Date, to: b.To_Date }
        }));


        // Time picker logic
        flatpickr("#pickupTime", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            disable: unavailableTimes.map(time => ({
                from: time.from,
                to: time.to
            }))
        });

        flatpickr("#dropTime", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            disable: unavailableTimes.map(time => ({
                from: time.from,
                to: time.to
            }))
        });
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};



const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        form.value[key] = key === "user_id" ? user.id : "";
    });
};

const submit = async () => {
    try {
        const response = await api.post("/vehicle-requisition", form.value);
        if (response.data.success) {
            resetForm();
            Swal.fire({
                icon: 'success',
                title: 'Vehicle Requisition Added Successfully',
                showConfirmButton: false,
                timer: 1500
            });
            // await api.get(`/vehicle-requition-mail/${id}`);
        } else {
            Swal.fire({
                icon: 'error',
                title: response.data.error,
                showConfirmButton: true,
            });
        }
        getData();
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: "Error processing request",
        });
    }
};


const cancleRequisition = async (id, status) => {
    try {
        const response = await api.get(`/vehicle-requisition-approval`, {
            params: { id, status },
        });
        if (response.data.success) {
            resetForm();
            Swal.fire({
                icon: 'success',
                title: 'Vehicle Requisition Cancle Successfully',
                showConfirmButton: false,
                timer: 1500
            });
            window.location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: response.data.error,
                showConfirmButton: true,
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: "Error processing request",
        });
    }
};

onMounted(() => {
    getData();
});
</script>

<template>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h1 class="text-center mb-5">Vehicle Requisition Form</h1>
                        <div class="row">
                            <div class="col-lg-3 text-center">
                                <div class="card p-2 mb-3">
                                    <h4>{{ user.Full_Name }}</h4>
                                    <h6>{{ user.des_name }}</h6>
                                    <h6>{{ user.dept_name }}</h6>
                                </div>
                                <div class="card p-2" v-if="vehicleInfo">
                                    <img :src="vehicleInfo ? vehicleInfo.image : ''" class="img-fluid rounded-top"
                                        alt="Image unavailable" />

                                </div>
                            </div>
                            <div class="col-lg-9">
                                <div class="card p-2">
                                    <form @submit.prevent="submit">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label>Vehicle Name</label>
                                                <select class="form-control" v-model="form.vehicle_id"
                                                    @change="getVehicle(form.vehicle_id)">
                                                    <option v-for="item in vehicles" :key="item.id" :value="item.id">{{
                                                        item.Name }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label>Driver Name</label>
                                                <input type="text" class="form-control"
                                                    :value="vehicleInfo?.driver?.Full_Name || ''" readonly />
                                            </div>
                                            <div class="col-md-4">
                                                <label>Driver's Contact No</label>
                                                <input type="text" class="form-control"
                                                    :value="vehicleInfo?.driver?.Contact_No || ''" readonly />
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <label>From Date</label>
                                                <input type="date" id="fromDate" class="form-control"
                                                    v-model="form.from_date" />
                                            </div>
                                            <div class="col-md-4">
                                                <label>Pickup Time</label>
                                                <input type="time" id="pickupTime" class="form-control"
                                                    v-model="form.pickup_time" />
                                            </div>
                                            <div class="col-md-4">
                                                <label>Pickup Location</label>
                                                <input type="text" class="form-control"
                                                    v-model="form.pickup_location" />
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <label>To Date</label>
                                                <input type="date" id="toDate" class="form-control"
                                                    v-model="form.to_date" />
                                            </div>
                                            <div class="col-md-4">
                                                <label>Drop Off Time</label>
                                                <input type="time" id="dropTime" class="form-control"
                                                    v-model="form.drop_time" />
                                            </div>
                                            <div class="col-md-4">
                                                <label>Drop Off Location</label>
                                                <input type="text" class="form-control" v-model="form.drop_location" />
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <label>Purpose</label>
                                                <input type="text" class="form-control" v-model="form.purpose" />
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <label>Description</label>
                                                <textarea class="form-control" rows="4"
                                                    v-model="form.description"></textarea>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success mt-4">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h1 class="text-center mb-5">Vehicle Requisition Summary</h1>
                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Vehicle Name</th>
                                        <th>Driver Name</th>
                                        <th>Pickup Date/Time</th>
                                        <th>Drop off Date/Time</th>
                                        <th>Location</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item, index in userRequisition" :key="item.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ item.vehicle.Name }}</td>
                                        <td>{{ item.vehicle.driver.Full_Name }}</td>
                                        <td>
                                            {{ item.From_Date }} / {{ item.Drop_Time }}
                                        </td>
                                        <td>
                                            {{ item.To_Date }} / {{ item.Pickup_Time }}
                                        </td>
                                        <td>{{ item.Pickup_Location }}||{{ item.Drop_Location }}</td>
                                        <td>{{ item.Purpose }}</td>
                                        <td>{{ item.Status }}</td>
                                        <td v-if="item.Status !== 'cancle'"><button class="btn btn-danger"
                                                @click="cancleRequisition(item.id, 'cancle')"><i
                                                    class="fa-solid fa-trash"></i></button></td>
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
