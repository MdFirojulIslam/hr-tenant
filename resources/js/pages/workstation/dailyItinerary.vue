<script setup>
import { ref, onMounted } from "vue";
import api from "@/api"; // adjust path
import Swal from "sweetalert2"; // optional, for nice alerts

const town_options = ref([]);
const daily_items = ref([]);
const error = ref(null);

const form = ref({
    date: "",
    day: "",
    town: "",
    location: "",
    visitWith: "",
    objective: "",
    meetingWith: "",
    startTime: "",
    endTime: "",
});

// Fetch dropdown data and daily items
const getData = async () => {
    try {
        const [resTowns, resItems] = await Promise.all([
            api.get("/itinerary-options"),
            api.get("/itinerary-daily-items"),
        ]);

        town_options.value = resTowns.data;
        daily_items.value = resItems.data;

        if (daily_items.value.length > 0) {
            const d = daily_items.value[0];

            form.value = {
                logStatus: d.log_status,
                updateStatus: d.status,
                itinerary_id: d.itinerary_id,
                itinerary_logs_id: d.itinerary_logs_id,
                date: d.visit_date,
                day: new Date(d.visit_date).toLocaleDateString("en-US", {
                    weekday: "long",
                }),

                town: d.town_id,
                location: d.location,
                visitWith: d.visit_with,
                objective: d.visit_objective,
                meetingWith: d.meeting_with,
                startTime: d.visit_start_time,
                endTime: d.visit_end_time,
            };
        }
    } catch (err) {
        error.value = err?.message || "Error fetching data";
    }
};

// Auto calculate day if user changes the date
const updateDay = (form) => {
    if (!form.date) {
        form.day = "";
        return;
    }

    const days = [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
    ];

    form.day = days[new Date(form.date).getDay()];
};

// Update button handler
const updateItinerary = async () => {
    try {
        await api.put("/itinerary-daily-update", form.value);

        Swal.fire("Success", "Itinerary updated successfully", "success");
    } catch (err) {
        Swal.fire("Error", err?.message || "Failed to update", "error");
    }
};

onMounted(() => {
    getData();
});
</script>

<template>
    <div class="border rounded p-3 mb-3">
        <h3 class="mb-3">Daily Itinerary Plan</h3>

        <div v-if="error" class="alert alert-danger">
            {{ error }}
        </div>

        <!-- ================= DESKTOP VIEW ================= -->
        <div class="mb-4 shadow-sm d-none d-md-block table-wrapper">
            <table class="table table-hover table-sm table-bordered">
                <thead class="table-light">
                    <tr class="text-center text-nowrap">
                        <th>DATE</th>
                        <th>DAY</th>
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
                    <tr>
                        <td>
                            <input
                                type="date"
                                v-model="form.date"
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
                            <select v-model="form.town" class="form-control">
                                <option value="">Select Town</option>
                                <option
                                    v-for="town in town_options"
                                    :key="town.town_id"
                                    :value="town.town_id"
                                >
                                    {{ town.town_name }}
                                </option>
                            </select>
                        </td>

                        <td>
                            <input
                                type="text"
                                v-model="form.location"
                                class="form-control"
                            />
                        </td>
                        <td>
                            <input
                                type="text"
                                v-model="form.visitWith"
                                class="form-control"
                            />
                        </td>
                        <td>
                            <input
                                type="text"
                                v-model="form.objective"
                                class="form-control"
                            />
                        </td>
                        <td>
                            <input
                                type="text"
                                v-model="form.meetingWith"
                                class="form-control"
                            />
                        </td>
                        <td>
                            <input
                                type="time"
                                v-model="form.startTime"
                                class="form-control"
                            />
                        </td>
                        <td>
                            <input
                                type="time"
                                v-model="form.endTime"
                                class="form-control"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ================= MOBILE VIEW ================= -->
        <div class="d-block d-md-none">
            <div class="card shadow-sm p-3 mb-3">
                <div class="mb-2">
                    <label class="form-label fw-bold">Date</label>
                    <input
                        type="date"
                        v-model="form.date"
                        class="form-control"
                        disabled
                    />
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold">Day</label>
                    <input
                        type="text"
                        v-model="form.day"
                        class="form-control"
                        disabled
                    />
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold">Town / Distributor</label>
                    <select v-model="form.town" class="form-control">
                        <option value="">Select Town</option>
                        <option
                            v-for="town in town_options"
                            :key="town.town_id"
                            :value="town.town_id"
                        >
                            {{ town.town_name }}
                        </option>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold">Location & Market</label>
                    <input
                        type="text"
                        v-model="form.location"
                        class="form-control"
                    />
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold">Visit With</label>
                    <input
                        type="text"
                        v-model="form.visitWith"
                        class="form-control"
                    />
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold">Objective</label>
                    <input
                        type="text"
                        v-model="form.objective"
                        class="form-control"
                    />
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold">Meeting With</label>
                    <input
                        type="text"
                        v-model="form.meetingWith"
                        class="form-control"
                    />
                </div>

                <div class="row">
                    <div class="col-6 mb-2">
                        <label class="form-label fw-bold">Start Time</label>
                        <input
                            type="time"
                            v-model="form.startTime"
                            class="form-control"
                        />
                    </div>

                    <div class="col-6 mb-2">
                        <label class="form-label fw-bold">End Time</label>
                        <input
                            type="time"
                            v-model="form.endTime"
                            class="form-control"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= BUTTONS ================= -->

        <!-- Desktop Button -->
        <div class="d-none d-md-flex justify-content-end mt-3">
            <button class="btn btn-primary px-4" @click="updateItinerary">
                Update
            </button>
        </div>

        <!-- Mobile Sticky Button -->
        <div
            class="d-flex d-md-none position-fixed bottom-0 start-0 w-100 bg-white border-top p-2"
            style="z-index: 1050"
        >
            <button class="btn btn-primary w-100 py-2" @click="updateItinerary">
                Update Itinerary
            </button>
        </div>
    </div>
</template>
