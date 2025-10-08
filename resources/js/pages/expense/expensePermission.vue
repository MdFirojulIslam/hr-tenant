<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const permissions = ref([]);
const isLoading = ref(true);

const getData = async () => {
    try {
        const response = await api.get(`/expense-permission`);
        permissions.value = response.data;
    } catch (err) {
        console.error(err.message || "Error fetching data");
    } finally {
        isLoading.value = false;
    }
};

const togglePermission = async (id, type) => {
    // Find the specific item and toggle the value locally
    const item = permissions.value.find(emp => emp.id === id);
    if (!item) return;
    console.log(id);

    // Toggle the value
    if (type === "date") {
        item.Date_Lock = !item.Date_Lock;
    } else if (type === "att") {
        item.Attendance_Lock = !item.Attendance_Lock;
    }

    try {
        // Send API request to update permission
        await api.post('/update-expense-permission', {
            id: id,
            type: type,
            value: type === "date" ? item.Date_Lock : item.Attendance_Lock
        });

        Swal.fire({
            position: "middle",
            icon: "success",
            title: "Permission updated successfully",
            showConfirmButton: false,
            timer: 1000,
        });

    } catch (err) {
        console.error(err.message || "Error updating permission");
        Swal.fire("Error", "Failed to update permission", "error");

        if (type === "date") {
            item.Date_Lock = !item.Date_Lock;
        } else {
            item.Attendance_Lock = !item.Attendance_Lock;
        }
    }
};

onMounted(() => getData());
</script>


<template>
    <div class="container">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Serial</th>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Date Lock</th>
                                    <th>Attendance Lock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in permissions" :key="item.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>ABNB{{ item.Employee_Id }}</td>
                                    <td>{{ item.Full_Name }}</td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" :checked="item.Date_Lock"
                                                @change="togglePermission(item.id, 'date')" />
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" :checked="item.Attendance_Lock"
                                                @change="togglePermission(item.id, 'att')" />
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Smaller Toggle Button Styling */
.switch {
    position: relative;
    display: inline-block;
    width: 40px;
    /* Adjusted width */
    height: 20px;
    /* Reduced height */
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 20px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 14px;
    /* Reduced size */
    width: 14px;
    /* Reduced size */
    left: 3px;
    /* Adjusted position */
    bottom: 3px;
    /* Adjusted position */
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked+.slider {
    background-color: #4caf50;
}

input:checked+.slider:before {
    transform: translateX(20px);
    /* Adjusted movement */
}
</style>
