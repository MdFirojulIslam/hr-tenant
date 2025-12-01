<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useRoute } from "vue-router";

import { useRouter } from "vue-router";

const router = useRouter();

// ----------------------
// EMIT CLOSE EVENT
// ----------------------
// const emit = defineEmits(["close"]);

// const closeForm = () => {
//     emit("close");
// };

// ----------------------
// RECEIVE PROPS
// ----------------------
const props = defineProps({
    itineraryId: { type: Number, default: null },
    viewMode: { type: Boolean, default: false },
});

const route = useRoute();
const itineraryId = ref(props.itineraryId || route.params.id || null);
const viewMode = ref(props.viewMode);
const mode = viewMode.value ? "view" : itineraryId.value ? "edit" : "new";

const heading = ref(
    viewMode.value
        ? "View Itinerary"
        : itineraryId.value
        ? "Edit Itinerary"
        : "New Itinerary"
);

const saveButtonText = computed(() =>
    mode === "edit" ? "Save Changes" : "Save"
);

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
        marked: false,
    },
]);

const town_options = ref([]);
const error = ref(null);

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
        forms.value = res.data.map((d) => ({
            itinerary_id: d.itinerary_id,
            itinerary_status: d.itinerary_status,
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
            marked: false,
        }));
    } catch (err) {
        error.value = "Failed to load itinerary data.";
    }
};

// ----------------------
// HELPER FUNCTIONS
// ----------------------
const updateDay = (form) => {
    form.day = form.date
        ? new Date(form.date).toLocaleDateString("en-US", { weekday: "long" })
        : "";
};

const addRow = (index) => {
    const parentLogId = forms.value[index].itinerary_logs_id || null;
    forms.value.splice(index + 1, 0, {
        date: "",
        day: "",
        town: "",
        location: "",
        visitWith: "",
        objective: "",
        meetingWith: "",
        startTime: "",
        endTime: "",
        marked: false,
        itinerary_logs_id: parentLogId,
    });
};

const removeRow = (index) => {
    if (forms.value.length > 1) forms.value.splice(index, 1);
};

// ----------------------
// SAVE / UPDATE
// ----------------------
const submitItinerary = async () => {
    try {
        const filteredRows = forms.value.filter((row) => {
            const isNew = !row.itinerary_id;
            const isMarked = row.marked === true;

            // completely empty row skip
            const isEmpty =
                !row.date &&
                !row.town &&
                !row.location &&
                !row.visitWith &&
                !row.objective &&
                !row.meetingWith;

            if (isEmpty) return false;

            return isNew || isMarked;
        });

        if (filteredRows.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "Nothing selected!",
                text: "Please mark or add at least one row.",
            });
            return;
        }

        if (itineraryId.value) {
            await api.put(`/itinerary/${itineraryId.value}`, {
                rows: filteredRows,
            });
            Swal.fire({
                icon: "success",
                title: "Updated Successfully!",
                timer: 1500,
                showConfirmButton: false,
            });
        } else {
            await api.post("/itinerary", { rows: filteredRows });
            Swal.fire({
                icon: "success",
                title: "Saved Successfully!",
                timer: 1500,
                showConfirmButton: false,
            });
        }

        router.push("/workstation/itinerary");
    } catch (err) {
        Swal.fire({ icon: "error", title: "Failed!", text: err.message });
    }
};

// ----------------------
// CLOSE BUTTON
// ----------------------

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

        <div class="card mb-3 shadow-sm">
            <table class="table table-hover table-sm table-bordered">
                <thead class="table-light">
                    <tr class="text-center text-nowrap">
                        <th style="width: 3%">SL</th>
                        <th style="width: 5%">DATE</th>
                        <th style="width: 5%">DAY</th>
                        <th style="width: 5%">TOWN/DISTRIBUTOR</th>
                        <th style="width: 17%">LOCATION & MARKET</th>
                        <th style="width: 10%">VISIT WITH SO/SSO</th>
                        <th style="width: 16%">OBJECTIVE</th>
                        <th style="width: 15%">MEETING WITH</th>
                        <th style="width: 5%">START TIME</th>
                        <th style="width: 5%">END TIME</th>
                        <th style="width: 3%">MARK</th>
                        <th style="width: 5%">ADD/REMOVE</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="(form, index) in forms"
                        :key="index"
                        class="text-nowrap"
                    >
                        <td class="text-center">{{ index + 1 }}</td>
                        <td>
                            <input
                                type="date"
                                v-model="form.date"
                                @change="updateDay(form)"
                                class="form-control"
                                :disabled="viewMode"
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
                            <select
                                v-model="form.town"
                                class="form-control"
                                :disabled="viewMode"
                            >
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
                                :disabled="viewMode"
                            />
                        </td>
                        <td>
                            <input
                                type="text"
                                v-model="form.visitWith"
                                class="form-control"
                                :disabled="viewMode"
                            />
                        </td>
                        <td>
                            <input
                                type="text"
                                v-model="form.objective"
                                class="form-control"
                                :disabled="viewMode"
                            />
                        </td>
                        <td>
                            <input
                                type="text"
                                v-model="form.meetingWith"
                                class="form-control"
                                :disabled="viewMode"
                            />
                        </td>
                        <td>
                            <input
                                type="time"
                                v-model="form.startTime"
                                class="form-control"
                                :disabled="viewMode"
                            />
                        </td>
                        <td>
                            <input
                                type="time"
                                v-model="form.endTime"
                                class="form-control"
                                :disabled="viewMode"
                            />
                        </td>
                        <td class="text-center align-middle">
                            <input
                                type="checkbox"
                                v-model="form.marked"
                                :disabled="viewMode"
                            />
                        </td>
                        <td class="text-center align-middle">
                            <button
                                v-if="!viewMode && index === 0"
                                class="btn btn-sm btn-success"
                                @click="addRow(index)"
                            >
                                <i class="fa fa-plus"></i>
                            </button>
                            <button
                                v-else-if="!viewMode"
                                class="btn btn-sm btn-danger"
                                @click="removeRow(index)"
                            >
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end">
            <button
                v-if="!viewMode"
                class="btn btn-success me-3"
                @click="submitItinerary"
            >
                {{ saveButtonText }}
            </button>

            <button
                class="btn btn-secondary"
                @click="router.push('/workstation/itinerary')"
            >
                Close
            </button>
        </div>

        <div v-if="error" class="text-danger mt-2">{{ error }}</div>
    </div>
</template>

<style>
.itinerary-form .table input,
.itinerary-form .table select {
    min-width: 80px;
}
</style>
