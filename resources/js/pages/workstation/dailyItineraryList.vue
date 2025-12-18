<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const dailyItems = ref([]);
const employeeIDs = ref([]);
const error = ref(null);

const formatDate = (dateStr) => {
    if (!dateStr) return "-";

    return new Date(dateStr).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const formatTime = (timeStr) => {
    if (!timeStr) return "-";

    const [hour, minute] = timeStr.split(":");

    const date = new Date();
    date.setHours(hour, minute);

    return date.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });
};

// Search fields
const searchDate = ref("");
const searchEmp = ref(""); // employee_id

/* -------------------------
   Fetch itinerary items
--------------------------*/
const getDailyItems = async (
    date = searchDate.value,
    employee_id = searchEmp.value
) => {
    try {
        const res = await api.get("/itinerary-daily-all-items", {
            params: { date, employee_id },
        });
        dailyItems.value = res.data;

        console.log("Daily Items:", dailyItems.value);
    } catch (err) {
        error.value = err?.message || "Failed to load data";
        Swal.fire("Error", error.value, "error");
    }
};

/* -------------------------
   Load employees
--------------------------*/
const loadEmployees = async () => {
    try {
        const res = await api.get("/employee-list");
        employeeIDs.value = res.data;
    } catch (err) {
        Swal.fire("Error", "Failed to load employees", "error");
    }
};

/* -------------------------
   Filtered data
--------------------------*/
const filteredItems = computed(() => {
    return dailyItems.value
        .map((item) => {
            return {
                itinerary_id: item.itinerary_id,
                visit_date: item.updated_visit_date ?? item.visit_date,
                town_name: item.updated_town_name ?? item.town_name,
                location: item.updated_location ?? item.location,
                visit_with: item.updated_visit_with ?? item.visit_with,
                employee_id: item.employee_id,
                visit_objective:
                    item.updated_visit_objective ?? item.visit_objective,
                meeting_with: item.updated_meeting_with ?? item.meeting_with,
                visit_start_time:
                    item.updated_visit_start_time ?? item.visit_start_time,
                visit_end_time:
                    item.updated_visit_end_time ?? item.visit_end_time,
                itinerary_status:
                    item.updated_visit_status ?? item.itinerary_status,
            };
        })
        .filter((item) => {
            const matchDate = searchDate.value
                ? item.visit_date === searchDate.value
                : true;

            const matchEmployee = searchEmp.value
                ? item.employee_id === searchEmp.value
                : true;

            return matchDate && matchEmployee;
        });
});

onMounted(() => {
    getDailyItems();
    loadEmployees();
});
</script>

<template>
    <div class="border rounded p-3">
        <h3 class="mb-3">Daily Itinerary List</h3>

        <!-- Filters -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3">
                <label class="form-label">Search by Date</label>
                <input type="date" v-model="searchDate" class="form-control" />
            </div>

            <div class="col-lg-4 position-relative">
                <label class="form-label">Select Employee</label>
                <select v-model="searchEmp" class="form-control">
                    <option value="">All Employees</option>
                    <option
                        v-for="emp in employeeIDs"
                        :key="emp.employee_id"
                        :value="emp.employee_id"
                    >
                        {{ emp.employee_name }}
                    </option>
                </select>

                <button
                    v-if="searchEmp"
                    class="btn btn-sm btn-link position-absolute top-0 end-0 mt-4 me-2"
                    @click="searchEmp = ''"
                >
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>DATE</th>
                        <th>NAME</th>
                        <th>TOWN</th>
                        <th>LOCATION</th>
                        <th>VISIT WITH</th>
                        <th>OBJECTIVE</th>
                        <th>MEETING WITH</th>
                        <th>START</th>
                        <th>END</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="item in filteredItems" :key="item.itinerary_id">
                        <td>{{ formatDate(item.visit_date) }}</td>
                        <td>{{ item.employee_name }}</td>
                        <td>{{ item.town_id }}</td>
                        <td>{{ item.location }}</td>
                        <td>{{ item.visit_with }}</td>
                        <td>{{ item.visit_objective }}</td>
                        <td>{{ item.meeting_with }}</td>
                        <td>{{ formatTime(item.visit_start_time) }}</td>
                        <td>{{ formatTime(item.visit_end_time) }}</td>
                    </tr>

                    <tr v-if="filteredItems.length === 0">
                        <td colspan="8" class="text-center">
                            No records found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
