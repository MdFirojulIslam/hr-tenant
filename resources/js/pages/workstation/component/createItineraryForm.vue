<script setup>
import { ref, onMounted, watch } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

/* --------------------------------------
   RECEIVE PROPS FROM PARENT
-----------------------------------------*/
const props = defineProps({
    isOpen: Boolean,
    heading: String,
    itineraryId: Number, // <-- ID for edit mode
    viewMode: {
        type: Boolean,
        default: false, // false = editable
    },
});

const emit = defineEmits(["close"]);

const submitItinerary = () => {
    if (props.itineraryId) {
        updateItinerary(); // edit mode
    } else {
        saveItinerary(); // create mode
    }
};

/* --------------------------------------
   MAIN FORM ARRAY (MULTIPLE ROWS)
-----------------------------------------*/
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
    },
]);

/* --------------------------------------
   TOWN OPTIONS DROPDOWN DATA
-----------------------------------------*/
const town_options = ref([]);
const error = ref(null);

/* --------------------------------------
   WATCH FOR itineraryId CHANGES (EDIT MODE)
-----------------------------------------*/
watch(
    () => props.itineraryId,
    async (id) => {
        if (!id) return;

        try {
            const res = await api.get(`/itinerary/${id}`);
            const items = res.data;

            // Convert backend rows → form rows
            forms.value = items.map((d) => ({
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
            }));
        } catch (err) {
            console.error("Error loading itinerary:", err);
        }
    },
    { immediate: true }
);

/* --------------------------------------
   CLOSE MODAL
-----------------------------------------*/
const closeModal = () => {
    emit("close");
};

/* --------------------------------------
   FETCH OPTIONS FOR DROPDOWNS
-----------------------------------------*/
const getData = async () => {
    try {
        const res = await api.get("/itinerary-options");
        town_options.value = res.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

/* --------------------------------------
   AUTO DAY FROM DATE
-----------------------------------------*/
const updateDay = (form) => {
    if (form.date) {
        const d = new Date(form.date);
        form.day = d.toLocaleDateString("en-US", { weekday: "long" });
    } else {
        form.day = "";
    }
};

/* --------------------------------------
   ADD / REMOVE ROWS
-----------------------------------------*/
const addRow = (index) => {
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
    });
};

const removeRow = (index) => {
    if (forms.value.length > 1) {
        forms.value.splice(index, 1);
    }
};

/* --------------------------------------
   RESET FORM (AFTER SAVE)
-----------------------------------------*/
const resetForm = () => {
    forms.value = [
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
        },
    ];
};

/* --------------------------------------
   SAVE ITINERARY (CREATE ONLY)
-----------------------------------------*/
const saveItinerary = async () => {
    try {
        const res = await api.post("/itinerary", { rows: forms.value });

        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: res.data.message,
                showConfirmButton: false,
                timer: 1500,
            });

            resetForm();
            closeModal();
        }
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Failed to save!",
            text: "Please try again.",
        });
    }
};

const updateItinerary = async () => {
    try {
        const res = await api.put(`/itinerary/${props.itineraryId}`, {
            rows: forms.value,
        });

        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: "Updated Successfully!",
                timer: 1500,
                showConfirmButton: false,
            });

            closeModal();
        } else {
            Swal.fire({
                icon: "error",
                title: res.data.message,
            });
        }
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Update Failed!",
            text: err.message,
        });
    }
};

onMounted(() => getData());
</script>

<template>
    <div v-if="isOpen" class="modal fade show d-block" tabindex="-1">
        <div
            class="modal-dialog modal-xl modal-dialog-centered custom-position custom-modal-width"
        >
            <div class="modal-content">
                <div class="modal-header gradient text-white">
                    <h5 class="modal-title">
                        {{ "Itinerary Setup" }}
                    </h5>
                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        @click="closeModal"
                    ></button>
                </div>

                <div
                    class="modal-body"
                    style="max-height: 80vh; overflow-y: auto"
                >
                    <div class="card-body">
                        <div class="card mb-3 shadow-sm">
                            <table
                                class="table table-hover table-sm table-bordered"
                            >
                                <thead class="table-light">
                                    <tr class="text-center text-nowrap">
                                        <th style="width: 3%">SL</th>
                                        <th style="width: 8%">DATE</th>
                                        <th style="width: 7%">DAY</th>
                                        <th style="width: 5%">
                                            TOWN/DISTRIBUTOR
                                        </th>
                                        <th style="width: 20%">
                                            LOCATION & MARKET
                                        </th>
                                        <th style="width: 10%">
                                            VISIT WITH SO/SSO
                                        </th>
                                        <th style="width: 17%">OBJECTIVE</th>
                                        <th style="width: 15%">MEETING WITH</th>
                                        <th style="width: 5%">START TIME</th>
                                        <th style="width: 5%">END TIME</th>
                                        <th style="width: 5%">ADD NEW</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        class="text-nowrap"
                                        v-for="(form, index) in forms"
                                        :key="index"
                                    >
                                        <td class="text-center">
                                            {{ index + 1 }}
                                        </td>
                                        <td>
                                            <input
                                                type="date"
                                                v-model="form.date"
                                                @change="updateDay(form)"
                                                class="form-control"
                                                :disabled="props.viewMode"
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
                                                :disabled="props.viewMode"
                                            >
                                                <option value="">
                                                    Select Town
                                                </option>
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
                                                :disabled="props.viewMode"
                                            />
                                        </td>

                                        <td>
                                            <input
                                                type="text"
                                                v-model="form.visitWith"
                                                class="form-control"
                                                :disabled="props.viewMode"
                                            />
                                        </td>

                                        <td>
                                            <input
                                                type="text"
                                                v-model="form.objective"
                                                class="form-control"
                                                :disabled="props.viewMode"
                                            />
                                        </td>

                                        <td>
                                            <input
                                                type="text"
                                                v-model="form.meetingWith"
                                                class="form-control"
                                                :disabled="props.viewMode"
                                            />
                                        </td>

                                        <td>
                                            <input
                                                type="time"
                                                v-model="form.startTime"
                                                class="form-control"
                                                :disabled="props.viewMode"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="time"
                                                v-model="form.endTime"
                                                class="form-control"
                                                :disabled="props.viewMode"
                                            />
                                        </td>

                                        <td class="text-center align-middle">
                                            <button
                                                v-if="
                                                    !props.viewMode &&
                                                    index === 0
                                                "
                                                class="btn btn-sm btn-success"
                                                @click="addRow(index)"
                                            >
                                                <i class="fa fa-plus"></i>
                                            </button>

                                            <button
                                                v-else-if="!props.viewMode"
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
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        v-if="!props.viewMode"
                        class="btn btn-success me-3"
                        @click="submitItinerary"
                    >
                        Save
                    </button>

                    <button class="btn btn-secondary" @click="closeModal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.gradient {
    background: linear-gradient(
        90deg,
        rgb(28, 105, 9) 27%,
        rgb(14, 119, 58) 100%,
        rgb(7, 134, 24) 91%
    );
}

.modal {
    background-color: rgba(0, 0, 0, 0.5);
}

.custom-position {
    left: 90px;
}

.custom-modal-width {
    max-width: 75% !important;
    width: 75% !important;
}
</style>
