<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import Swal from "sweetalert2";

import api from "@/api";
import { getEmployeeImage } from "@/pages/services/apiServices";

const props = defineProps({
    mode: {
        type: String,
        default: "create",
        validator: (value) => ["create", "edit", "view"].includes(value),
    },
    eventData: {
        type: Object,
        default: null,
    },
    preselect: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["close", "saved"]);

const DEFAULT_AVATAR = "/default-avatar.png";

const EVENT_NAME_SUGGESTIONS = [
    "Show Cause",
    "Warning",
    "Suspension",
    "Rustication",
    "Termination",
    "Promotion",
    "Transfer",
    "Increment",
    "Demotion",
    "Other",
];

const modalTitle = computed(() => {
    if (props.mode === "edit") return "Edit Employee HR Event";
    if (props.mode === "view") return "Employee HR Event Details";
    return "New Employee HR Event";
});

const modalSubtitle = computed(() => {
    if (props.mode === "edit") return "Update the HR action recorded for this employee.";
    if (props.mode === "view") return "Read-only view of the recorded HR action.";
    return "Record an HR action against an employee.";
});

const isView = computed(() => props.mode === "view");
const isEditable = computed(() => props.mode !== "view");

const submitting = ref(false);
const loadingDepartments = ref(true);
const loadingEmployees = ref(false);
const departmentsError = ref("");
const employeesError = ref("");
const serverError = ref("");
const errors = ref({});

const departments = ref([]);
const employees = ref([]);
const employeeImage = ref(null);
const imageLoading = ref(false);

const form = ref({
    department_id: null,
    employee_id: null,
    event_date: "",
    new_event_date: "",
    event_name: "",
    event_details: "",
    remarks: "",
});

const imageCache = new Map();

const selectedDepartment = computed({
    get: () =>
        departments.value.find(
            (dept) => String(dept.department_id) === String(form.value.department_id),
        ) || null,
    set: (value) => {
        form.value.department_id = value ? value.department_id : null;
    },
});

const selectedEmployee = computed({
    get: () =>
        employees.value.find(
            (emp) => String(emp.employee_id) === String(form.value.employee_id),
        ) || null,
    set: (value) => {
        form.value.employee_id = value ? value.employee_id : null;
    },
});

const employeePlaceholder = computed(() => {
    if (!form.value.department_id) return "Select a department first";
    if (loadingEmployees.value) return "Loading employees...";
    if (employees.value.length === 0) return "No employees in this department";
    return "Select Employee Name";
});

const employeeInitials = computed(() => {
    const name = selectedEmployee.value?.employee_name?.trim();
    if (!name) return "";
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join("");
});

const normalizeImage = (value) => {
    if (!value || typeof value !== "string") return null;
    const url = value.trim();
    if (!url) return null;
    if (/^(https?:)?\/\//i.test(url) || url.startsWith("data:")) return url;
    return url.startsWith("/") ? url : `/${url}`;
};

const employeeImageSrc = computed(() => employeeImage.value || DEFAULT_AVATAR);

const resetEmployee = () => {
    form.value.employee_id = null;
    employeeImage.value = null;
};

const loadDepartments = async () => {
    loadingDepartments.value = true;
    departmentsError.value = "";

    try {
        const res = await api.get("/department");
        const rows = Array.isArray(res.data) ? res.data : [];
        departments.value = rows
            .map((dept) => ({
                department_id: dept.id ?? dept.department_id ?? null,
                department_name: dept.Name ?? dept.department_name ?? "",
            }))
            .filter((dept) => dept.department_id && dept.department_name)
            .sort((a, b) => a.department_name.localeCompare(b.department_name));
    } catch (err) {
        departments.value = [];
        departmentsError.value =
            err.response?.data?.message || "Unable to load departments. Please retry.";
    } finally {
        loadingDepartments.value = false;
    }
};

const loadEmployees = async (departmentId) => {
    employees.value = [];
    employeesError.value = "";

    if (!departmentId) return;

    loadingEmployees.value = true;

    try {
        const res = await api.get(`/employee-list-by-department/${departmentId}`);
        const rows = Array.isArray(res.data) ? res.data : [];
        employees.value = rows
            .map((emp) => ({
                employee_id: emp.employee_id ?? emp.id,
                employee_name: emp.employee_name ?? emp.Full_Name ?? "",
            }))
            .filter((emp) => emp.employee_id && emp.employee_name)
            .sort((a, b) => a.employee_name.localeCompare(b.employee_name));
    } catch (err) {
        employees.value = [];
        employeesError.value =
            err.response?.data?.message || "Unable to load employees. Please retry.";
    } finally {
        loadingEmployees.value = false;
    }
};

const loadEmployeeImage = async (employeeId) => {
    if (!employeeId) {
        employeeImage.value = null;
        return;
    }

    const key = String(employeeId);

    if (imageCache.has(key)) {
        employeeImage.value = imageCache.get(key);
        return;
    }

    imageLoading.value = true;

    try {
        const url = await getEmployeeImage(employeeId);
        const normalized = normalizeImage(url);
        imageCache.set(key, normalized);
        employeeImage.value = normalized;
    } catch {
        employeeImage.value = null;
    } finally {
        imageLoading.value = false;
    }
};

const onDepartmentChange = async (value) => {
    errors.value = { ...errors.value, department_id: undefined, employee_id: undefined };
    resetEmployee();
    await loadEmployees(value ? value.department_id : null);
};

const onEmployeeChange = async (value) => {
    errors.value = { ...errors.value, employee_id: undefined };
    await loadEmployeeImage(value ? value.employee_id : null);
};

const clearDepartment = () => {
    selectedDepartment.value = null;
    onDepartmentChange(null);
};

const clearEmployee = () => {
    selectedEmployee.value = null;
    onEmployeeChange(null);
};

const resetForm = () => {
    form.value = {
        department_id: null,
        employee_id: null,
        event_date: "",
        new_event_date: "",
        event_name: "",
        event_details: "",
        remarks: "",
    };
    employeeImage.value = null;
    errors.value = {};
    serverError.value = "";
    employees.value = [];
};

const hydrateFromEvent = async (row) => {
    form.value.department_id = row.department_id ?? null;
    if (form.value.department_id) {
        await loadEmployees(form.value.department_id);
    }
    form.value.employee_id = row.employee_id ?? null;
    employeeImage.value = normalizeImage(row.employee_image);
    if (employeeImage.value) {
        imageCache.set(String(row.employee_id), employeeImage.value);
    }
    form.value.event_date = row.event_date || "";
    form.value.new_event_date = row.new_event_date || "";
    form.value.event_name = row.event_name || "";
    form.value.event_details = row.event_details || "";
    form.value.remarks = row.remarks || "";
};

const hydratePreselect = async () => {
    const departmentId = props.preselect?.department_id ?? null;
    const employeeId = props.preselect?.employee_id ?? null;

    if (!departmentId) return;

    form.value.department_id = departmentId;
    await loadEmployees(departmentId);

    if (!employeeId) return;

    const match = employees.value.find(
        (emp) => String(emp.employee_id) === String(employeeId),
    );

    if (match) {
        form.value.employee_id = match.employee_id;
        await loadEmployeeImage(match.employee_id);
    }
};

const collectErrors = () => {
    const found = {};

    if (!form.value.department_id) {
        found.department_id = "Please select a department.";
    }

    if (!form.value.employee_id) {
        found.employee_id = "Please select an employee.";
    }

    if (!form.value.event_date) {
        found.event_date = "Please select the event date.";
    }

    if (!form.value.event_name || !form.value.event_name.trim()) {
        found.event_name = "Please enter the event name.";
    } else if (form.value.event_name.trim().length > 255) {
        found.event_name = "Event name must be 255 characters or fewer.";
    }

    if (!form.value.event_details || !form.value.event_details.trim()) {
        found.event_details = "Please enter the event description.";
    } else if (form.value.event_details.trim().length > 255) {
        found.event_details = "Event description must be 255 characters or fewer.";
    }

    if (form.value.remarks && form.value.remarks.length > 255) {
        found.remarks = "Remarks must be 255 characters or fewer.";
    }

    if (
        form.value.new_event_date &&
        form.value.event_date &&
        form.value.new_event_date < form.value.event_date
    ) {
        found.new_event_date = "New event date cannot be before the event date.";
    }

    return found;
};

const applyServerErrors = (payload) => {
    const fieldErrors = payload?.errors;

    if (fieldErrors && typeof fieldErrors === "object") {
        const mapped = {};
        Object.entries(fieldErrors).forEach(([field, messages]) => {
            mapped[field] = Array.isArray(messages) ? messages[0] : String(messages);
        });
        errors.value = { ...errors.value, ...mapped };
    }

    return payload?.message || "Something went wrong. Please try again.";
};

const buildPayload = () => ({
    department_id: form.value.department_id || null,
    employee_id: form.value.employee_id,
    event_date: form.value.event_date,
    new_event_date: form.value.new_event_date || null,
    event_name: form.value.event_name.trim(),
    event_details: form.value.event_details.trim(),
    remarks: form.value.remarks ? form.value.remarks.trim() || null : null,
});

const submit = async () => {
    if (!isEditable.value || submitting.value) return;

    errors.value = {};
    serverError.value = "";

    const found = collectErrors();

    if (Object.keys(found).length > 0) {
        errors.value = found;
        Swal.fire({
            icon: "warning",
            title: "Please complete the required fields",
            text: Object.values(found)[0],
            confirmButtonText: "OK",
        });
        return;
    }

    const editing = props.mode === "edit";

    const confirmation = await Swal.fire({
        title: editing
            ? "Are you sure you want to update this event?"
            : "Are you sure you want to create this event?",
        text: `${form.value.event_name.trim()} — ${
            selectedEmployee.value?.employee_name || "Selected employee"
        }`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: editing ? "Yes, update it" : "Yes, create it",
        cancelButtonText: "No, cancel",
        reverseButtons: true,
        focusCancel: !editing,
    });

    if (!confirmation.isConfirmed) return;

    submitting.value = true;

    try {
        const payload = buildPayload();

        const res = editing
            ? await api.put("/update_event", { id: props.eventData?.id, ...payload })
            : await api.post("/create_event", payload);

        if (res.data && res.data.success) {
            submitting.value = false;

            Swal.fire({
                icon: "success",
                title: editing
                    ? "Event updated successfully!"
                    : "Event created successfully!",
                showConfirmButton: false,
                timer: 1600,
                timerProgressBar: true,
            });

            emit("saved", { mode: props.mode, event: res.data.data });
            emit("close");
        } else {
            serverError.value = applyServerErrors(res.data);
            Swal.fire("Unable to save", serverError.value, "error");
        }
    } catch (err) {
        const status = err.response?.status;
        const message = applyServerErrors(err.response?.data);

        if (status === 401) {
            Swal.fire("Session expired", "Please sign in again.", "error");
        } else if (status === 422 || status === 404) {
            Swal.fire("Unable to save", message, "error");
        } else {
            serverError.value = message;
            Swal.fire("Unable to save", message, "error");
        }
    } finally {
        submitting.value = false;
    }
};

const requestClose = () => {
    if (submitting.value) return;
    emit("close");
};

const onKeydown = (event) => {
    if (event.key === "Escape") {
        requestClose();
    }
};

let previousBodyOverflow = "";

onMounted(async () => {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    document.addEventListener("keydown", onKeydown);

    if (props.mode === "view" || props.mode === "edit") {
        if (!props.eventData) {
            serverError.value = "No event data was supplied.";
        } else {
            await hydrateFromEvent(props.eventData);
        }
    } else {
        await hydratePreselect();
    }

    await loadDepartments();
});

onBeforeUnmount(() => {
    document.body.style.overflow = previousBodyOverflow;
    document.removeEventListener("keydown", onKeydown);
    resetForm();
});
</script>

<template>
    <Teleport to="body">
        <div class="hre-overlay" @mousedown.self="requestClose">
            <div
                class="hre-modal"
                role="dialog"
                aria-modal="true"
                :class="{ 'hre-modal--view': isView }"
            >
                <header class="hre-modal__head">
                    <div>
                        <h3 class="hre-modal__title">{{ modalTitle }}</h3>
                        <p class="hre-modal__subtitle">{{ modalSubtitle }}</p>
                    </div>
                    <button
                        type="button"
                        class="hre-modal__close"
                        aria-label="Close"
                        :disabled="submitting"
                        @click="requestClose"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </header>

                <div v-if="serverError" class="hre-alert hre-alert--danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ serverError }}</span>
                </div>

                <div v-if="departmentsError" class="hre-alert hre-alert--danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ departmentsError }}</span>
                </div>

                <div class="hre-modal__body">
                    <div class="hre-employee-row">
                        <div class="hre-field hre-field--dept">
                            <label class="hre-label" for="hre-department">
                                Department <span class="hre-req">*</span>
                            </label>
                            <div class="hre-select">
                                <Multiselect
                                    id="hre-department"
                                    v-model="selectedDepartment"
                                    :options="departments"
                                    label="department_name"
                                    track-by="department_id"
                                    :searchable="true"
                                    :close-on-select="true"
                                    :clear-on-select="false"
                                    :show-labels="false"
                                    :allow-empty="true"
                                    :disabled="!isEditable || loadingDepartments"
                                    :loading="loadingDepartments"
                                    placeholder="Select Department"
                                    @update:model-value="onDepartmentChange"
                                />
                                <button
                                    v-if="selectedDepartment && isEditable"
                                    type="button"
                                    class="hre-select__clear"
                                    aria-label="Clear department"
                                    @click="clearDepartment"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <small
                                v-if="errors.department_id"
                                class="hre-error-text"
                            >
                                {{ errors.department_id }}
                            </small>
                        </div>

                        <div class="hre-field hre-field--emp">
                            <label class="hre-label" for="hre-employee">
                                Employee <span class="hre-req">*</span>
                            </label>
                            <div class="hre-select">
                                <Multiselect
                                    id="hre-employee"
                                    v-model="selectedEmployee"
                                    :options="employees"
                                    label="employee_name"
                                    track-by="employee_id"
                                    :searchable="true"
                                    :close-on-select="true"
                                    :clear-on-select="false"
                                    :show-labels="false"
                                    :allow-empty="true"
                                    :disabled="!isEditable || !form.department_id || loadingEmployees"
                                    :loading="loadingEmployees"
                                    :placeholder="employeePlaceholder"
                                    @update:model-value="onEmployeeChange"
                                />
                                <button
                                    v-if="selectedEmployee && isEditable"
                                    type="button"
                                    class="hre-select__clear"
                                    aria-label="Clear employee"
                                    @click="clearEmployee"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <small
                                v-if="errors.employee_id"
                                class="hre-error-text"
                            >
                                {{ errors.employee_id }}
                            </small>
                            <small
                                v-else-if="employeesError"
                                class="hre-error-text"
                            >
                                {{ employeesError }}
                            </small>
                        </div>

                        <div class="hre-avatar-panel">
                            <div class="hre-avatar">
                                <img
                                    v-if="employeeImage"
                                    :src="employeeImageSrc"
                                    :alt="
                                        selectedEmployee
                                            ? selectedEmployee.employee_name
                                            : 'Employee photo'
                                    "
                                />
                                <span v-else-if="imageLoading" class="hre-avatar__spinner"></span>
                                <span v-else class="hre-avatar__initials">
                                    {{ employeeInitials || "?" }}
                                </span>
                            </div>
                            <div class="hre-avatar-meta">
                                <span class="hre-avatar-meta__label">
                                    Employee Photo
                                </span>
                                <span class="hre-avatar-meta__name">
                                    {{
                                        selectedEmployee
                                            ? selectedEmployee.employee_name
                                            : "No employee selected"
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="hre-grid">
                        <div class="hre-field">
                            <label class="hre-label" for="hre-event-date">
                                Event Date <span class="hre-req">*</span>
                            </label>
                            <input
                                id="hre-event-date"
                                type="date"
                                class="hre-input"
                                :class="{ 'is-invalid': errors.event_date }"
                                :disabled="!isEditable"
                                v-model="form.event_date"
                            />
                            <small
                                v-if="errors.event_date"
                                class="hre-error-text"
                            >
                                {{ errors.event_date }}
                            </small>
                        </div>

                        <div class="hre-field">
                            <label class="hre-label" for="hre-new-event-date">
                                New Event Date
                                <span class="hre-optional">(optional)</span>
                            </label>
                            <input
                                id="hre-new-event-date"
                                type="date"
                                class="hre-input"
                                :class="{ 'is-invalid': errors.new_event_date }"
                                :disabled="!isEditable"
                                v-model="form.new_event_date"
                            />
                            <small
                                v-if="errors.new_event_date"
                                class="hre-error-text"
                            >
                                {{ errors.new_event_date }}
                            </small>
                        </div>

                        <div class="hre-field hre-field--full">
                            <label class="hre-label" for="hre-event-name">
                                Event Name <span class="hre-req">*</span>
                            </label>
                            <input
                                id="hre-event-name"
                                type="text"
                                class="hre-input"
                                :class="{ 'is-invalid': errors.event_name }"
                                :disabled="!isEditable"
                                :readonly="isView"
                                maxlength="255"
                                list="hre-event-name-options"
                                placeholder="e.g. Show Cause, Warning, Promotion"
                                v-model="form.event_name"
                            />
                            <datalist id="hre-event-name-options">
                                <option
                                    v-for="name in EVENT_NAME_SUGGESTIONS"
                                    :key="name"
                                    :value="name"
                                ></option>
                            </datalist>
                            <small
                                v-if="errors.event_name"
                                class="hre-error-text"
                            >
                                {{ errors.event_name }}
                            </small>
                        </div>

                        <div class="hre-field hre-field--full">
                            <label class="hre-label" for="hre-event-details">
                                Event Description
                                <span class="hre-req">*</span>
                            </label>
                            <textarea
                                id="hre-event-details"
                                rows="3"
                                maxlength="255"
                                class="hre-input hre-input--textarea"
                                :class="{ 'is-invalid': errors.event_details }"
                                :disabled="!isEditable"
                                :readonly="isView"
                                placeholder="Describe the reason and details of this HR event"
                                v-model="form.event_details"
                            ></textarea>
                            <small
                                v-if="errors.event_details"
                                class="hre-error-text"
                            >
                                {{ errors.event_details }}
                            </small>
                        </div>

                        <div class="hre-field hre-field--full">
                            <label class="hre-label" for="hre-remarks">
                                Remarks
                                <span class="hre-optional">(optional)</span>
                            </label>
                            <textarea
                                id="hre-remarks"
                                rows="2"
                                maxlength="255"
                                class="hre-input hre-input--textarea"
                                :class="{ 'is-invalid': errors.remarks }"
                                :disabled="!isEditable"
                                :readonly="isView"
                                placeholder="Any additional note (optional)"
                                v-model="form.remarks"
                            ></textarea>
                            <small
                                v-if="errors.remarks"
                                class="hre-error-text"
                            >
                                {{ errors.remarks }}
                            </small>
                        </div>
                    </div>

                    <dl v-if="isView && eventData" class="hre-audit">
                        <div class="hre-audit__item">
                            <dt>Employee ID</dt>
                            <dd>{{ eventData.employee_code || "—" }}</dd>
                        </div>
                        <div class="hre-audit__item">
                            <dt>Designation</dt>
                            <dd>{{ eventData.designation_name || "—" }}</dd>
                        </div>
                        <div class="hre-audit__item">
                            <dt>Created By</dt>
                            <dd>
                                {{ eventData.created_by_name || "—" }}
                                <span
                                    v-if="eventData.created_at"
                                    class="hre-audit__time"
                                >
                                    {{ eventData.created_at }}
                                </span>
                            </dd>
                        </div>
                        <div class="hre-audit__item">
                            <dt>Last Updated</dt>
                            <dd>
                                {{ eventData.updated_by_name || "—" }}
                                <span
                                    v-if="eventData.updated_at"
                                    class="hre-audit__time"
                                >
                                    {{ eventData.updated_at }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <footer class="hre-modal__foot">
                    <span v-if="isView" class="hre-readonly-hint">
                        <i class="fa-solid fa-lock"></i>
                        Read-only mode
                    </span>
                    <span v-else></span>

                    <div class="hre-foot-actions">
                        <button
                            type="button"
                            class="hre-btn hre-btn--ghost"
                            :disabled="submitting"
                            @click="requestClose"
                        >
                            {{ isView ? "Close" : "Cancel" }}
                        </button>

                        <button
                            v-if="isEditable"
                            type="button"
                            class="hre-btn hre-btn--primary"
                            :disabled="submitting"
                            @click="submit"
                        >
                            <span
                                v-if="submitting"
                                key="saving"
                                class="hre-btn__spinner"
                                aria-hidden="true"
                            ></span>
                            <span v-else key="idle" class="hre-btn__icon">
                                <i
                                    class="fa-solid"
                                    :class="
                                        mode === 'edit'
                                            ? 'fa-arrows-rotate'
                                            : 'fa-check'
                                    "
                                ></i>
                            </span>
                            {{
                                submitting
                                    ? "Saving..."
                                    : mode === "edit"
                                      ? "Update Event"
                                      : "Create Event"
                            }}
                        </button>
                    </div>
                </footer>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.hre-overlay {
    position: fixed;
    inset: 0;
    z-index: 1055;
    background: rgba(15, 23, 42, 0.55);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 24px 16px;
    overflow-y: auto;
    animation: hre-fade 0.16s ease;
}

@keyframes hre-fade {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.hre-modal {
    width: 100%;
    max-width: 860px;
    margin: auto;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 48px);
    animation: hre-rise 0.2s ease;
}

@keyframes hre-rise {
    from {
        opacity: 0;
        transform: translateY(14px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hre-modal--view {
    max-width: 760px;
}

.hre-modal__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 22px 14px;
    border-bottom: 1px solid #eef2f6;
}

.hre-modal__title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -0.2px;
}

.hre-modal__subtitle {
    margin: 4px 0 0;
    font-size: 12.5px;
    color: #6b7280;
}

.hre-modal__close {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border: 1px solid #e6ebf0;
    background: #ffffff;
    color: #64748b;
    border-radius: 9px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition:
        background 0.15s ease,
        color 0.15s ease,
        border-color 0.15s ease;
}

.hre-modal__close:hover:not(:disabled) {
    background: #f1f5f9;
    color: #1f2937;
    border-color: #d3dbe4;
}

.hre-modal__close:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.hre-alert {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin: 14px 22px 0;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 13px;
    line-height: 1.45;
}

.hre-alert--danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
}

.hre-modal__body {
    padding: 18px 22px;
    overflow-y: auto;
    flex: 1 1 auto;
}

.hre-employee-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 14px;
    align-items: start;
    padding-bottom: 18px;
    margin-bottom: 18px;
    border-bottom: 1px dashed #e6ebf0;
}

.hre-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.hre-label {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #64748b;
}

.hre-req {
    color: #dc2626;
}

.hre-optional {
    font-weight: 500;
    text-transform: none;
    letter-spacing: 0;
    color: #94a3b8;
}

.hre-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid #d3dbe4;
    border-radius: 9px;
    background: #ffffff;
    padding: 8px 12px;
    font-size: 13.5px;
    color: #1f2937;
    outline: none;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.hre-input:focus:not(:disabled) {
    border-color: #1a7f5a;
    box-shadow: 0 0 0 3px rgba(26, 127, 90, 0.12);
}

.hre-input:disabled,
.hre-input[readonly] {
    background: #f8fafc;
    color: #475569;
    cursor: not-allowed;
}

.hre-input--textarea {
    resize: vertical;
    min-height: 62px;
    line-height: 1.5;
}

.hre-input.is-invalid {
    border-color: #ef4444;
}

.hre-input.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.14);
}

.hre-error-text {
    color: #dc2626;
    font-size: 11.5px;
    font-weight: 500;
}

.hre-select {
    position: relative;
}

.hre-select__clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;
    width: 22px;
    height: 22px;
    border: none;
    border-radius: 6px;
    background: #e2e8f0;
    color: #475569;
    font-size: 11px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}

.hre-select__clear:hover {
    background: #dc2626;
    color: #ffffff;
}

.hre-select :deep(.multiselect__tags) {
    min-height: 40px !important;
    height: auto !important;
    padding: 5px 36px 5px 10px !important;
    border: 1px solid #d3dbe4 !important;
    border-radius: 9px !important;
    background: #ffffff;
}

.hre-select :deep(.multiselect__tags:focus-within) {
    border-color: #1a7f5a !important;
    box-shadow: 0 0 0 3px rgba(26, 127, 90, 0.12);
}

.hre-select :deep(.multiselect__single) {
    font-size: 13.5px;
    color: #1f2937;
    line-height: 26px !important;
    padding: 0 !important;
    margin: 0 !important;
}

.hre-select :deep(.multiselect__input) {
    font-size: 13.5px;
    color: #1f2937;
    padding: 0 !important;
    margin: 0 !important;
}

.hre-select :deep(.multiselect__placeholder) {
    color: #94a3b8 !important;
    font-size: 13.5px;
    line-height: 26px !important;
    padding: 0 !important;
    margin: 0 !important;
}

.hre-select :deep(.multiselect__select) {
    width: 30px;
}

.hre-select :deep(.multiselect__select::before) {
    display: none;
}

.hre-select :deep(.multiselect__content-wrapper) {
    max-height: 260px;
    margin-top: 4px;
    border-radius: 10px;
    border: 1px solid #e6ebf0;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
}

.hre-select :deep(.multiselect__option) {
    font-size: 13.5px;
    padding: 8px 12px;
}

.hre-select :deep(.multiselect__option--highlighted) {
    background: #eef7f3;
    color: #146a4a;
}

.hre-select :deep(.multiselect__option--selected) {
    background: #e6f4ee;
    color: #146a4a;
    font-weight: 600;
}

.hre-select :deep(.multiselect--disabled .multiselect__tags) {
    background: #f8fafc;
    cursor: not-allowed;
}

.hre-avatar-panel {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: #f8fafc;
    border: 1px solid #e6ebf0;
    border-radius: 12px;
    min-width: 220px;
}

.hre-avatar {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: #e2e8f0;
    overflow: hidden;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 0 2px #ffffff, 0 0 0 3px #e2e8f0;
}

.hre-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.hre-avatar__initials {
    font-size: 18px;
    font-weight: 700;
    color: #64748b;
}

.hre-avatar__spinner,
.hre-btn__spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.45);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: hre-spin 0.7s linear infinite;
}

.hre-btn__icon {
    display: contents;
}

.hre-avatar__spinner {
    border-color: rgba(26, 127, 90, 0.25);
    border-top-color: #1a7f5a;
}

@keyframes hre-spin {
    to {
        transform: rotate(360deg);
    }
}

.hre-avatar-meta {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}

.hre-avatar-meta__label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #94a3b8;
}

.hre-avatar-meta__name {
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.hre-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.hre-field--full {
    grid-column: 1 / -1;
}

.hre-audit {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin: 18px 0 0;
    padding: 14px 16px;
    background: #f8fafc;
    border: 1px solid #e6ebf0;
    border-radius: 12px;
}

.hre-audit__item dt {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #94a3b8;
    margin-bottom: 3px;
}

.hre-audit__item dd {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    overflow-wrap: anywhere;
}

.hre-audit__time {
    display: block;
    font-size: 11.5px;
    font-weight: 400;
    color: #94a3b8;
}

.hre-modal__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 22px;
    border-top: 1px solid #eef2f6;
    background: #f8fafc;
    border-radius: 0 0 16px 16px;
}

.hre-readonly-hint {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    background: #e2e8f0;
    border-radius: 999px;
    padding: 5px 12px;
}

.hre-foot-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.hre-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 20px;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    transition:
        background 0.15s ease,
        border-color 0.15s ease,
        color 0.15s ease,
        transform 0.15s ease;
}

.hre-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.hre-btn--ghost {
    background: #ffffff;
    border-color: #d3dbe4;
    color: #475569;
}

.hre-btn--ghost:hover:not(:disabled) {
    border-color: #1a7f5a;
    color: #1a7f5a;
}

.hre-btn--primary {
    background: #1a7f5a;
    color: #ffffff;
    min-width: 148px;
    justify-content: center;
}

.hre-btn--primary:hover:not(:disabled) {
    background: #146a4a;
    transform: translateY(-1px);
}

.hre-btn--primary:active:not(:disabled) {
    transform: translateY(0);
}

@media (max-width: 900px) {
    .hre-employee-row {
        grid-template-columns: 1fr;
    }

    .hre-avatar-panel {
        min-width: 0;
    }

    .hre-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .hre-modal__head,
    .hre-modal__body,
    .hre-modal__foot {
        padding-left: 16px;
        padding-right: 16px;
    }

    .hre-modal__foot {
        flex-direction: column;
        align-items: stretch;
    }

    .hre-foot-actions {
        width: 100%;
    }

    .hre-btn {
        flex: 1 1 auto;
        justify-content: center;
    }

    .hre-readonly-hint {
        align-self: flex-start;
    }
}
</style>
