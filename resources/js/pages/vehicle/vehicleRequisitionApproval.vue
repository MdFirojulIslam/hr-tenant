<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;

const error = ref(null);
const requisition_info = ref([]);
const loading = ref(false);

const getData = async () => {
    try {
        const response = await api.get("/vehicle-requisition");
        requisition_info.value = response.data;

    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const vehicleApproval = async (id, status) => {
    loading.value = true;
    try {
        const response = await api.get(`/vehicle-requisition-approval`, {
            params: { id, status },
        });
        if (response.data.success) {
            loading.value = false;
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Approval is send to HR",
                showConfirmButton: false,
                timer: 1500,
            });
            await api.get(`/vehicle-requition-hr-mail/${id}`);
            window.location.reload();
        }
    } catch (err) {
    }
};


onMounted(() => {
    getData();
});
</script>

<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <h1 class="mb-5">Vehicle Requisition Information</h1>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Employee Name</th>
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
                                    <tr v-for="item, index in requisition_info" :key="item.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ item.employee.Full_Name }}</td>
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
                                        <td v-if="item.Status == 'pending' && item.employee.official.Supervisor_Id == user.id">
                                            <button class="custom-btn btn-13 mx-1"
                                            @click="vehicleApproval(item.id, 'approved')">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button class="custom-btn btn-12 mx-1"
                                            @click="vehicleApproval(item.id, 'rejected')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                        <td v-else-if="item.Status == 'approved' && item.vehicle.supervisor.id == user.id">
                                            <button class="custom-btn btn-13 mx-1"
                                            @click="vehicleApproval(item.id, 'complete')">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button class="custom-btn btn-12 mx-1"
                                            @click="vehicleApproval(item.id, 'rejected')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                        <td v-else>...</td>
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
