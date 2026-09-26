<script setup>
import { ref, computed, onMounted } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

const loading = ref(false);
const submitting = ref(false);
const error = ref(null);
const events = ref([]);
const departments = ref([]);
const eventTypes = ref(["Holiday", "Meeting", "Meet-Up", "Others"]);
const showForm = ref(false);
const editingId = ref(null);
const picturePreview = ref(null);
const pictureInput = ref(null);

const form = ref({
    event_header: "",
    event_description: "",
    event_type: "Others",
    event_date_from: "",
    event_date_to: "",
    picture: null,
    department_ids: [],
});

const departmentOptions = computed(() =>
    departments.value.map((d) => ({ id: d.id, Name: d.Name })),
);

const selectedDepartments = computed({
    get: () =>
        departmentOptions.value.filter((d) =>
            form.value.department_ids.includes(String(d.id)),
        ),
    set: (value) => {
        form.value.department_ids = (value || []).map((d) => String(d.id));
    },
});

const formatDateTime = (value) => {
    if (!value) return "—";
    const date = new Date(value.replace(" ", "T"));
    if (Number.isNaN(date.getTime())) return value;
    const day = String(date.getDate()).padStart(2, "0");
    const month = date.toLocaleString("en-US", { month: "short" });
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, "0");
    const mins = String(date.getMinutes()).padStart(2, "0");
    return `${day} ${month}, ${year} ${hours}:${mins}`;
};

const toLocalInput = (value) => {
    if (!value) return "";
    return String(value).replace(" ", "T").slice(0, 16);
};

const resetForm = () => {
    form.value = {
        event_header: "",
        event_description: "",
        event_type: "Others",
        event_date_from: "",
        event_date_to: "",
        picture: null,
        department_ids: [],
    };
    editingId.value = null;
    picturePreview.value = null;
    if (pictureInput.value) {
        pictureInput.value.value = "";
    }
};

const closeForm = () => {
    showForm.value = false;
    resetForm();
};

const openCreate = () => {
    resetForm();
    showForm.value = true;
};

const openEdit = (item) => {
    editingId.value = item.id;
    form.value = {
        event_header: item.event_header || "",
        event_description: item.event_description || "",
        event_type: item.event_type || "Others",
        event_date_from: toLocalInput(item.event_date_from),
        event_date_to: toLocalInput(item.event_date_to),
        picture: null,
        department_ids: item.for_all_departments
            ? departments.value.map((d) => String(d.id))
            : (item.for_departments || []).map(String),
    };
    picturePreview.value = item.picture_url || null;
    showForm.value = true;
};

const onPicture = (e) => {
    const file = e.target.files?.[0] || null;
    form.value.picture = file;
    picturePreview.value = file
        ? URL.createObjectURL(file)
        : picturePreview.value;
};

const loadEvents = async () => {
    loading.value = true;
    try {
        const res = await api.get("/event-setup");
        events.value = res.data.data || [];
        departments.value = res.data.departments || [];
        if (res.data.event_types?.length) {
            eventTypes.value = res.data.event_types;
        }
    } catch (err) {
        error.value = err.response?.data?.message || err.message;
    } finally {
        loading.value = false;
    }
};

const validateForm = () => {
    if (!form.value.event_header.trim()) return "Event header is required.";
    if (!form.value.event_date_from) return "Date from is required.";
    if (!form.value.event_date_to) return "Date to is required.";
    if (form.value.event_date_to < form.value.event_date_from) {
        return "Date to must be on or after date from.";
    }
    if (form.value.department_ids.length === 0) {
        return "Select at least one department.";
    }
    if (form.value.picture) {
        const allowed = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
        if (!allowed.includes(form.value.picture.type)) {
            return "Picture must be a JPG, PNG, or WEBP image.";
        }
        if (form.value.picture.size > 512 * 1024) {
            return "Picture must be 512 KB or smaller.";
        }
    }
    return null;
};

const submit = async () => {
    const message = validateForm();
    if (message) {
        Swal.fire("Warning", message, "warning");
        return;
    }

    submitting.value = true;
    try {
        const formData = new FormData();
        formData.append("event_header", form.value.event_header);
        formData.append(
            "event_description",
            form.value.event_description || "",
        );
        formData.append("event_type", form.value.event_type);
        formData.append(
            "event_date_from",
            form.value.event_date_from.replace("T", " ") + ":00",
        );
        formData.append(
            "event_date_to",
            form.value.event_date_to.replace("T", " ") + ":00",
        );
        form.value.department_ids.forEach((id) => {
            formData.append("for_departments[]", id);
        });
        if (form.value.picture) {
            formData.append("picture", form.value.picture);
        }

        const config = { headers: { "Content-Type": "multipart/form-data" } };
        const url = editingId.value
            ? `/event-setup/${editingId.value}`
            : "/event-setup";
        const res = await api.post(url, formData, config);

        if (res.data && res.data.success) {
            const wasEdit = !!editingId.value;
            closeForm();
            Swal.fire({
                icon: "success",
                title: wasEdit ? "Event updated" : "Event created",
                showConfirmButton: false,
                timer: 1500,
            });
            await loadEvents();
        } else {
            Swal.fire(
                "Error",
                res.data.message || "Unable to save event.",
                "error",
            );
        }
    } catch (err) {
        Swal.fire(
            "Error",
            err.response?.data?.message || "Unable to save event.",
            "error",
        );
    } finally {
        submitting.value = false;
    }
};

const removeEvent = async (item) => {
    const result = await Swal.fire({
        title: "Delete this event?",
        text: item.event_header,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Delete",
    });
    if (!result.isConfirmed) return;

    try {
        const res = await api.delete(`/event-setup/${item.id}`);
        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: "Event deleted",
                showConfirmButton: false,
                timer: 1500,
            });
            await loadEvents();
        }
    } catch (err) {
        Swal.fire(
            "Error",
            err.response?.data?.message || "Unable to delete event.",
            "error",
        );
    }
};

const eventStatus = (item) => {
    const now = new Date();
    const to = new Date(String(item.event_date_to).replace(" ", "T"));
    if (Number.isNaN(to.getTime())) return "Scheduled";
    return to >= now ? "Active" : "Ended";
};

onMounted(() => loadEvents());
</script>

<template>
    <div class="container-fluid">
        <div
            class="d-flex justify-content-between align-items-center py-2 px-1 mb-3"
        >
            <h2 class="m-0">Event Setup</h2>
            <button class="btn btn-success" type="button" @click="openCreate">
                <i class="fa-solid fa-plus me-2"></i>Add Event
            </button>
        </div>

        <div class="card mb-4" v-if="showForm">
            <div class="card-body">
                <h4 class="mb-3">
                    {{ editingId ? "Edit Event" : "Create Event" }}
                </h4>
                <form @submit.prevent="submit">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Event Header</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.event_header"
                            />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Event Type</label>
                            <select
                                class="form-control"
                                v-model="form.event_type"
                            >
                                <option
                                    v-for="type in eventTypes"
                                    :key="type"
                                    :value="type"
                                >
                                    {{ type }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Picture (optional)</label>
                            <input
                                ref="pictureInput"
                                type="file"
                                class="form-control"
                                accept="image/*"
                                @change="onPicture"
                            />
                        </div>
                        <div class="col-12">
                            <label class="form-label">Event Description</label>
                            <textarea
                                class="form-control"
                                rows="3"
                                v-model="form.event_description"
                            ></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">From Date/Time</label>
                            <input
                                type="datetime-local"
                                class="form-control"
                                v-model="form.event_date_from"
                            />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Date/Time</label>
                            <input
                                type="datetime-local"
                                class="form-control"
                                v-model="form.event_date_to"
                            />
                        </div>
                        <div class="col-12">
                            <label class="form-label">Departments</label>
                            <Multiselect
                                v-model="selectedDepartments"
                                :options="departmentOptions"
                                label="Name"
                                track-by="id"
                                :multiple="true"
                                :searchable="true"
                                :close-on-select="false"
                                :preserve-search="true"
                                :show-labels="false"
                                :allow-empty="true"
                                :disabled="loading"
                                placeholder="Select departments"
                            />
                        </div>
                        <div class="col-12" v-if="picturePreview">
                            <img
                                :src="picturePreview"
                                alt="Event"
                                style="max-height: 140px"
                                class="rounded"
                            />
                        </div>
                    </div>
                    <div class="mt-3">
                        <button
                            class="btn btn-success me-2"
                            type="submit"
                            :disabled="submitting"
                        >
                            {{ editingId ? "Update" : "Save" }}
                        </button>
                        <button
                            class="btn btn-secondary"
                            type="button"
                            @click="closeForm"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <div class="table-responsive">
                    <table
                        class="table table-bordered text-center align-middle"
                    >
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Type</th>
                                <th>Date From</th>
                                <th>Date To</th>
                                <th>Departments</th>
                                <th>Picture</th>
                                <th>Created By</th>
                                <th>Updated At</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="10">Loading…</td>
                            </tr>
                            <tr v-else-if="events.length === 0">
                                <td colspan="10">No events found.</td>
                            </tr>
                            <tr v-for="item in events" :key="item.id">
                                <td class="text-start">
                                    <strong>{{ item.event_header }}</strong>
                                    <div
                                        class="small text-muted"
                                        v-if="item.event_description"
                                    >
                                        {{ item.event_description }}
                                    </div>
                                </td>
                                <td>{{ item.event_type }}</td>
                                <td>
                                    {{ formatDateTime(item.event_date_from) }}
                                </td>
                                <td>
                                    {{ formatDateTime(item.event_date_to) }}
                                </td>
                                <td>
                                    {{
                                        (item.department_names || []).join(", ")
                                    }}
                                </td>
                                <td>
                                    <img
                                        v-if="item.picture_url"
                                        :src="item.picture_url"
                                        alt=""
                                        style="height: 48px"
                                        class="rounded"
                                    />
                                    <span v-else>—</span>
                                </td>
                                <td>{{ item.created_by_name || "—" }}</td>
                                <td>{{ formatDateTime(item.updated_at) }}</td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="
                                            eventStatus(item) === 'Active'
                                                ? 'bg-success'
                                                : 'bg-secondary'
                                        "
                                    >
                                        {{ eventStatus(item) }}
                                    </span>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-primary me-1"
                                        @click="openEdit(item)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        class="btn btn-sm btn-danger"
                                        @click="removeEvent(item)"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
