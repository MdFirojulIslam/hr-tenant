<script setup>
import { ref, onMounted, defineAsyncComponent } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import api from "@/api";
import Swal from "sweetalert2";

import { useStore } from "vuex";

const store = useStore();
const role_id = store.getters.getRoleId;

const holidayComponent = defineAsyncComponent(() =>
    import("../leave/component/holidayForm.vue")
);

const currentYear = ref(new Date().getFullYear());

const isLoading = ref(true);
const error = ref(null);
const selectedStore = ref(null);
const heading = ref(null);

const holidays = ref([]);
const holidayId = ref();
const holidayModel = ref(false);

const selectedYear = ref(new Date().getFullYear());

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);

    const day = String(date.getDate()).padStart(2, "0"); // 01–09
    const month = date.toLocaleString("en-US", { month: "short" }); // Jan, Feb, Mar...
    const year = date.getFullYear();

    return `${day} ${month}, ${year}`;
};

const years = ref([
    "2022",
    "2023",
    "2024",
    "2025",
    "2026",
    "2027",
    "2028",
    "2029",
    "2030",
]);

const holidayOpen = (title) => {
    heading.value = title;
    if (title === "Save") {
        selectedStore.value = null; // <-- important!
        holidayId.value = null; // optional
    }
    holidayModel.value = true;
};
const holidayClose = () => {
    holidayModel.value = false;
};

const viewReport = (id) => {
    const year = selectedYear.value;
    window.open(`/holiday-report?year=${year}`);
};

const getData = async (page = 1) => {
    try {
        const response = await api.get(
            `/holiday?page=${page}&year=${selectedYear.value}`
        );
        holidays.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

const editholiday = async (id) => {
    try {
        selectedStore.value = holidays.value.find((item) => item.id === id);
        holidayId.value = id;
        holidayOpen("Update");
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const deleteholiday = async (id) => {
    try {
        const response = await api.delete(`/delete-holiday/${id}`);
        console.log(response.data.success);
        if (response.data.success) {
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been Deleted",
                showConfirmButton: false,
                timer: 1500,
            });
            await getData();
        }
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const submitHandler = async () => {
    await getData();
};
onMounted(() => getData());
</script>

<template>
    <div class="container-fluid h-100 d-flex flex-column p-0 m-0">
        <!-- Header -->
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="m-0">Holidays List</h2>
            <div v-if="role_id == 1 || role_id == 2">
                <button
                    @click="holidayOpen('Save')"
                    type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm"
                >
                    <i class="fa-solid fa-plus me-2"></i> Add Holiday
                </button>
            </div>
        </div>

        <!-- Salary Modal -->
        <component
            v-if="holidayModel"
            :is="holidayComponent"
            :isOpen="holidayModel"
            :editStore="selectedStore"
            :updateinfo="heading"
            :holiId="holidayId"
            @modal-close="holidayClose"
            @submit="submitHandler"
            name="first-modal"
        />

        <!-- Table & Filters -->
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <!-- Filters -->
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-lg-3 col-md-4">
                        <div class="form-group">
                            <label
                                for="yearSelect"
                                class="form-label fw-semibold text-secondary"
                            >
                                Select Year
                            </label>
                            <div class="input-group shadow-sm">
                                <select
                                    id="yearSelect"
                                    class="form-select form-select-sm border-0 bg-light fw-medium"
                                    v-model="selectedYear"
                                    @change="getData"
                                >
                                    <option
                                        v-for="year in years"
                                        :key="year"
                                        :value="year"
                                    >
                                        {{ year }}
                                    </option>
                                </select>
                                <button
                                    class="btn btn-outline-secondary btn-sm"
                                    type="button"
                                    v-if="selectedYear"
                                    @click="selectedYear = ''"
                                >
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Table -->
                <div class="border rounded mt-5">
                    <div
                        class="d-flex align-items-center px-2 py-2 fw-semibold border-bottom bg-light"
                    >
                        <span class="me-2">Total Holidays:</span>
                        <span
                            class="bg-white border rounded px-3 py-1 fw-medium"
                            >{{ holidays.total }}</span
                        >
                    </div>

                    <div class="table-responsive px-2 bg-light">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 6%" class="text-nowrap">
                                        SN
                                    </th>
                                    <th style="width: 30%" class="text-nowrap">
                                        HOLIDAY NAMES
                                    </th>
                                    <th
                                        style="width: 20%"
                                        class="text-nowrap text-center"
                                    >
                                        FROM DATE
                                    </th>
                                    <th
                                        style="width: 20%"
                                        class="text-nowrap text-center"
                                    >
                                        TO DATE
                                    </th>
                                    <th
                                        style="width: 20%"
                                        class="text-nowrap text-center"
                                    >
                                        NO OF DAYS
                                    </th>
                                    <th
                                        style="width: 4%"
                                        class="text-nowrap text-center"
                                    >
                                        ACTION
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(holiday, index) in holidays"
                                    :key="holiday.id"
                                >
                                    <td style="width: 6%" class="text-nowrap">
                                        {{ index + 1 }}
                                    </td>
                                    <td style="width: 30%" class="text-nowrap">
                                        {{ holiday.holiday_name }}
                                    </td>
                                    <td
                                        style="width: 20%"
                                        class="text-nowrap text-center"
                                    >
                                        {{ formatDate(holiday.from_date) }}
                                    </td>
                                    <td
                                        style="width: 20%"
                                        class="text-nowrap text-center"
                                    >
                                        {{ formatDate(holiday.to_date) }}
                                    </td>
                                    <td
                                        style="width: 20%"
                                        class="text-nowrap text-center"
                                    >
                                        {{ holiday.duration }}
                                    </td>
                                    <td
                                        class="text-nowrap text-end"
                                        v-if="role_id == 1 || role_id == 2"
                                    >
                                        <button
                                            class="custom-btn btn-15"
                                            @click="editholiday(holiday.id)"
                                        >
                                            <i
                                                class="fa-regular fa-pen-to-square"
                                            ></i>
                                        </button>
                                        <button
                                            class="custom-btn btn-12 mx-2"
                                            @click="deleteholiday(holiday.id)"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                    <td v-else></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
            </div>
        </div>
    </div>
</template>
