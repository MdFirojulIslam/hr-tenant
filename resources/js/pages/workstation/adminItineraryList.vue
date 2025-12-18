<script setup>
import { ref, onMounted, computed, nextTick } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";
import { useRoute, useRouter } from "vue-router";
import { status } from "nprogress";

const store = useStore();
const user = store.getters.getUser;
const router = useRouter();
const route = useRoute();

const log_status = ref(null);

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
    "2031",
    "2032",
    "2033",
    "2034",
    "2035",
]);
const months = ref([
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
]);
const selectedYear = ref(new Date().getFullYear());
const selectedMonth = ref(new Date().getMonth() + 1);

// ----------------------
// FORM DATA
// ----------------------
const forms = ref([
    {
        date: "",
        day: "",
        town: "",
        location: "",
        visitWith: "",
        objective: "",
        meetingWith: "",
        startTime: "",
        endTime: "",
        remarks: "",
        marked: false,
    },
]);

const town_options = ref([]);
const error = ref(null);
const supervisorApproval = ref([]);
const supervisorID = ref(null);
const itinerary_logsID = ref(null);

// ----------------------
// API FUNCTIONS
// ----------------------
const getData = async () => {
    try {
        const res = await api.get("/itinerary-options");
        town_options.value = res.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const loadItinerary = async () => {
    if (!itineraryId.value) return;

    try {
        const res = await api.get(`/itinerary/${itineraryId.value}`);
        log_status.value = res.data[0]?.itinerary_status ?? null;
        supervisorID.value = res.data[0]?.supervisor_id;
        itinerary_logsID.value = res.data[0]?.itinerary_logs_id;
        forms.value = res.data.map((d) => ({
            employee_id: d.created_by,
            itinerary_id: d.itinerary_id,
            itinerary_status: d.itinerary_status,
            itinerary_logs_id: d.itinerary_logs_id,
            date: d.updated_visit_date ?? d.visit_date,
            day: new Date(
                d.updated_visit_date ?? d.visit_date
            ).toLocaleDateString("en-US", {
                weekday: "long",
            }),
            town: d.updated_town_id ?? d.town_id,
            location: d.updated_location ?? d.location,
            visitWith: d.updated_visit_with ?? d.visit_with,
            objective: d.updated_visit_objective ?? d.visit_objective,
            meetingWith: d.updated_meeting_with ?? d.meeting_with,
            startTime: d.updated_visit_start_time ?? d.visit_start_time,
            endTime: d.updated_visit_end_time ?? d.visit_end_time,
            remarks: d.supervisor_remarks,
            log_status: d.itinerary_status,
            marked: false,
        }));
        nextTick(() => resizeAllTextareas());
    } catch (err) {
        error.value = "Failed to load itinerary data.";
    }
};

console.log("itinerary status", log_status.value);

// ----------------------
// HELPER FUNCTIONS
// ----------------------
const updateDay = (form) => {
    form.day = form.date
        ? new Date(form.date).toLocaleDateString("en-US", { weekday: "long" })
        : "";
};

// Auto-resize function
const autoResize = (el) => {
    el.style.height = "auto";
    el.style.height = el.scrollHeight + "px";
};

// Resize all textarea (for preserving height on new row add)
const resizeAllTextareas = () => {
    setTimeout(() => {
        document.querySelectorAll("textarea").forEach((el) => {
            autoResize(el);
        });
    }, 50);
};

// ----------------------
// ON MOUNT
// ----------------------
onMounted(() => {
    getData();
    loadItinerary();
});
</script>

<template>
    <div class="itinerary-form">
        <h3 class="mb-3">{{ heading }}</h3>
        <div v-if="!itineraryId" class="row g-3 mb-3">
            <div class="col-lg-1 position-relative">
                <label> Select Year </label>
                <select
                    class="form-control border-color"
                    v-model="selectedYear"
                    @change="changeDate"
                >
                    <option v-for="year in years" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
            </div>
            <div class="col-lg-1">
                <label>Select Month</label>
                <select
                    class="form-control border-color"
                    v-model="selectedMonth"
                    @change="changeDate"
                >
                    <option
                        v-for="(month, index) in months"
                        :key="month"
                        :value="index + 1"
                    >
                        {{ month }}
                    </option>
                </select>
            </div>
            <div class="col-lg-3 position-relative">
                <label>Employees</label>
                <select
                    class="form-control border-color"
                    v-model="searchEmp"
                    @change="changeDate"
                >
                    <option :disabled value="">Select Employee Name</option>
                    <option
                        v-for="emp in employeeIDs"
                        :value="emp.employee_id"
                        :key="emp.employee_id"
                    >
                        {{ emp.employee_name }}
                    </option>
                </select>

                <button
                    v-if="searchEmp"
                    class="btn btn-sm btn-link position-absolute top-0 end-0 mt-4 me-1"
                    @click="
                        searchEmp = '';
                        changeDate();
                    "
                >
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>

        <div class="card mb-3 shadow-sm">
            <table class="table table-hover table-sm table-bordered">
                <thead class="table-light">
                    <tr class="text-center text-nowrap">
                        <th style="width: 3%">SL</th>
                        <th style="width: 5%">DATE</th>
                        <th style="width: 5%">DAY</th>
                        <th style="width: 5%">TOWN/DISTRIBUTOR</th>
                        <th style="width: 150px">LOCATION & MARKET</th>
                        <th style="width: 150px">VISIT WITH SO/SSO</th>
                        <th style="width: 150px">OBJECTIVE</th>
                        <th style="width: 150px">MEETING WITH</th>
                        <th style="width: 5%">START TIME</th>
                        <th style="width: 5%">END TIME</th>
                        <th style="width: 150px">REMARKS</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(form, index) in forms" :key="index">
                        <td class="text-center">{{ index + 1 }}</td>
                        <td>
                            <input
                                type="date"
                                v-model="form.date"
                                @change="updateDay(form)"
                                class="form-control"
                                disabled
                            />
                        </td>
                        <td>
                            <input
                                type="text"
                                v-model="form.day"
                                class="form-control"
                                disabled
                            />
                        </td>
                        <td>
                            <input
                                type="text"
                                v-model="form.town"
                                class="form-control"
                                disabled
                            />
                        </td>

                        <td>
                            <textarea
                                v-model="form.location"
                                class="form-control"
                                rows="1"
                                @input="autoResize($event.target)"
                                style="max-width: 150px; overflow: hidden"
                                disabled
                            ></textarea>
                        </td>
                        <td>
                            <textarea
                                v-model="form.visitWith"
                                class="form-control"
                                rows="1"
                                @input="autoResize($event.target)"
                                style="max-width: 150px; overflow: hidden"
                                disabled
                            ></textarea>
                        </td>
                        <td>
                            <textarea
                                v-model="form.objective"
                                class="form-control"
                                rows="1"
                                @input="autoResize($event.target)"
                                style="max-width: 150px; overflow: hidden"
                                disabled
                            ></textarea>
                        </td>
                        <td>
                            <textarea
                                v-model="form.meetingWith"
                                class="form-control"
                                rows="1"
                                @input="autoResize($event.target)"
                                style="max-width: 150px; overflow: hidden"
                                disabled
                            ></textarea>
                        </td>

                        <td>
                            <input
                                type="time"
                                v-model="form.startTime"
                                class="form-control"
                                disabled
                            />
                        </td>
                        <td>
                            <input
                                type="time"
                                v-model="form.endTime"
                                class="form-control"
                                disabled
                            />
                        </td>
                        <td>
                            <textarea
                                v-model="form.remarks"
                                class="form-control"
                                rows="1"
                                @input="autoResize($event.target)"
                                style="max-width: 150px; overflow: hidden"
                            ></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="error" class="text-danger mt-2">{{ error }}</div>
    </div>
</template>

<style>
.itinerary-form .table input,
.itinerary-form .table select,
.itinerary-form .table textarea {
    min-width: 80px;
    width: 100%;
    box-sizing: border-box;
}
</style>
