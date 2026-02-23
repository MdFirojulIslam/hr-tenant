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
const searchEmp = ref(""); // employee_id

/* -------------------------
   Fetch itinerary items
--------------------------*/
const getDailyItems = async (employee_id = searchEmp.value) => {
    try {
        const res = await api.get("/itinerary-daily-all-items", {
            params: { employee_id },
        });

        dailyItems.value = res.data;
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
    return dailyItems.value.map((item) => {
        return {
            visit_date: item.visit_date,
            employee_name: item.employee_name,
            town_name: item.town_name,
            location: item.location,
            visit_with: item.visit_with,
            visit_objective: item.visit_objective,
            meeting_with: item.meeting_with,
            visit_start_time: item.visit_start_time,
            visit_end_time: item.visit_end_time,
        };
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
        <div class="d-flex justify-content-between">
            <div class="row g-3 mb-4">
                <div class="position-relative">
                    <select
                        v-model="searchEmp"
                        class="form-control design-input"
                        @change="getDailyItems(searchEmp)"
                    >
                        <option value="">Select Employee Name</option>
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
                        class="btn btn-sm btn-link position-absolute end-0 mt-4 me-2"
                        @click="
                            searchEmp = '';
                            getDailyItems();
                        "
                    >
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive shadow-sm table-wrapper">
            <table class="table table-bordered table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 2%">SN</th>
                        <th class="text-center" style="width: 6%">DATE</th>
                        <th style="width: 10%">NAME</th>
                        <th style="width: 3%">TOWN</th>
                        <th style="width: 10%">LOCATION</th>
                        <th style="width: 10%">VISIT WITH</th>
                        <th style="width: 15%">OBJECTIVE</th>
                        <th style="width: 15%">MEETING WITH</th>
                        <th class="text-center" style="width: 5%">START</th>
                        <th class="text-center" style="width: 5%">END</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="(item, index) in filteredItems"
                        :key="item.itinerary_id"
                    >
                        <td class="text-center">{{ index + 1 }}</td>
                        <td class="text-center">
                            {{ formatDate(item.visit_date) }}
                        </td>
                        <td>{{ item.employee_name }}</td>
                        <td>{{ item.town_name }}</td>
                        <td>{{ item.location }}</td>
                        <td>{{ item.visit_with }}</td>
                        <td>{{ item.visit_objective }}</td>
                        <td>{{ item.meeting_with }}</td>
                        <td class="text-center">
                            {{ formatTime(item.visit_start_time) }}
                        </td>
                        <td class="text-center">
                            {{ formatTime(item.visit_end_time) }}
                        </td>
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

<style scopped>
.design-input {
    width: 300px;
}

/* Hover effect */
.design-input:hover {
    border-color: #3e925e;
}

/* Focus effect */
.design-input:focus {
    border-color: #3e925e;
    box-shadow: 0 0 0 0.2rem rgba(6, 158, 87, 0.678);
    outline: none;
}

.table tbody tr:hover > td {
    background-color: #717c73c7 !important;
}

.table thead th {
    background-color: #4e4e4e;
    font-weight: 700;
    color: #fff;
}
.table thead {
    height: 30px;
}
.table-wrapper {
    overflow-x: auto;
}

.table-wrapper table {
    min-width: 1400px; /* adjust based on your columns */
}
</style>
