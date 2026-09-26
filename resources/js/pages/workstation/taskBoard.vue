<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import store from "@/store/store";

const role_id = store.getters.getRoleId;

const error = ref(null);
const loading = ref(false);
const submitting = ref(false);
const editSubmitting = ref(false);
const employees = ref([]);
const selectedEmployees = ref([]);
const tasks = ref([]);

const showEdit = ref(false);
const editingId = ref(null);
const editSelectedEmployees = ref([]);
const editForm = ref({
    task_name: "",
    description: "",
    task_assigned_at: "",
    task_finished_at: "",
    status: "Assigned",
    remarks: "",
});

const form = ref({
    task_name: "",
    description: "",
    task_assigned_at: "",
    status: "Assigned",
    remarks: "",
});

const filters = ref({
    eid: "",
    status: "",
    q: "",
    from: "",
    to: "",
});

const statusOptions = ["Assigned", "Complete", "Incomplete"];

const employeeLabel = (emp) => {
    if (!emp) return "";
    return emp.Employee_Id
        ? `${emp.Full_Name} (${emp.Employee_Id})`
        : emp.Full_Name;
};

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

const statusClass = (status) => {
    if (status === "Complete") return "badge bg-success";
    if (status === "Incomplete") return "badge bg-danger";
    return "badge bg-warning text-dark";
};

const defaultAssignedAt = () => {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
};

const resetForm = () => {
    selectedEmployees.value = [];
    form.value = {
        task_name: "",
        description: "",
        task_assigned_at: defaultAssignedAt(),
        status: "Assigned",
        remarks: "",
    };
};

const closeEdit = () => {
    showEdit.value = false;
    editingId.value = null;
    editSelectedEmployees.value = [];
    editForm.value = {
        task_name: "",
        description: "",
        task_assigned_at: "",
        task_finished_at: "",
        status: "Assigned",
        remarks: "",
    };
};

const openEdit = (item) => {
    editingId.value = item.id;
    editForm.value = {
        task_name: item.task_name || "",
        description: item.description || "",
        task_assigned_at: toLocalInput(item.task_assigned_at),
        task_finished_at: toLocalInput(item.task_finished_at),
        status: item.status || "Assigned",
        remarks: item.remarks || "",
    };
    editSelectedEmployees.value = (item.assignees || []).map((a) => ({
        id: a.id,
        Full_Name: a.Full_Name,
        Employee_Id: a.Employee_Id,
    }));
    showEdit.value = true;
};

const mergeIntoOptions = (list, picked) => {
    const merged = [...list];
    (picked || []).forEach((emp) => {
        if (
            emp &&
            emp.id &&
            !merged.some((e) => String(e.id) === String(emp.id))
        ) {
            merged.push(emp);
        }
    });
    return merged;
};

const createEmployeeOptions = computed(() =>
    mergeIntoOptions(employees.value, selectedEmployees.value),
);
const editEmployeeOptions = computed(() =>
    mergeIntoOptions(employees.value, editSelectedEmployees.value),
);

const normalizeEmployees = (payload) => {
    const rows = Array.isArray(payload?.data)
        ? payload.data
        : Array.isArray(payload)
          ? payload
          : [];

    return rows
        .map((row) => ({
            id: row.id ?? row.employee_id,
            Full_Name:
                row.Full_Name ?? row.employee_name ?? row.full_name ?? "",
            Employee_Id: row.Employee_Id ?? row.employee_code ?? "",
        }))
        .filter((row) => row.id);
};

const loadEmployees = async () => {
    try {
        const res = await api.get("/task-board/employees");
        employees.value = normalizeEmployees(res.data);
    } catch (err) {
        error.value = err.response?.data?.message || err.message;
    }
};

const loadTasks = async () => {
    loading.value = true;
    try {
        const params = {};
        Object.entries(filters.value).forEach(([key, value]) => {
            if (value) params[key] = value;
        });
        const res = await api.get("/task-board", { params });
        tasks.value = res.data.data || [];
    } catch (err) {
        error.value = err.response?.data?.message || err.message;
    } finally {
        loading.value = false;
    }
};

const toDateTimePayload = (value) => {
    if (!value) return null;
    return String(value).replace("T", " ") + ":00";
};

const validateCreate = () => {
    if (selectedEmployees.value.length === 0)
        return "Select at least one employee.";
    if (!form.value.task_name.trim()) return "Task name is required.";
    if (!form.value.description.trim()) return "Description is required.";
    if (!form.value.task_assigned_at) return "Task assigned at is required.";
    const invalid = selectedEmployees.value.some(
        (emp) => !employees.value.some((e) => String(e.id) === String(emp.id)),
    );
    if (invalid) {
        return "You may only assign tasks to employees under your supervision.";
    }
    return null;
};

const submit = async () => {
    const message = validateCreate();
    if (message) {
        Swal.fire("Warning", message, "warning");
        return;
    }

    submitting.value = true;
    try {
        const payload = {
            assigned_to: selectedEmployees.value.map((emp) => emp.id),
            task_name: form.value.task_name,
            description: form.value.description,
            task_assigned_at: toDateTimePayload(form.value.task_assigned_at),
            status: form.value.status,
            remarks: form.value.remarks || null,
        };
        const res = await api.post("/task-board", payload);
        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: "Task assigned successfully",
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
            await loadTasks();
        } else {
            Swal.fire(
                "Error",
                res.data.message || "Unable to assign task.",
                "error",
            );
        }
    } catch (err) {
        Swal.fire(
            "Error",
            err.response?.data?.message || "Unable to assign task.",
            "error",
        );
    } finally {
        submitting.value = false;
    }
};

const saveEdit = async () => {
    if (!editingId.value) return;

    if (
        editSelectedEmployees.value.length === 0 ||
        !editForm.value.task_name.trim() ||
        !editForm.value.description.trim() ||
        !editForm.value.task_assigned_at
    ) {
        Swal.fire(
            "Warning",
            "Employee(s), task name, description, and assigned at are required.",
            "warning",
        );
        return;
    }
    const invalid = editSelectedEmployees.value.some(
        (emp) => !employees.value.some((e) => String(e.id) === String(emp.id)),
    );
    if (invalid) {
        Swal.fire(
            "Warning",
            "You may only assign tasks to employees under your supervision.",
            "warning",
        );
        return;
    }

    editSubmitting.value = true;
    try {
        const payload = {
            assigned_to: editSelectedEmployees.value.map((emp) => emp.id),
            task_name: editForm.value.task_name,
            description: editForm.value.description,
            task_assigned_at: toDateTimePayload(
                editForm.value.task_assigned_at,
            ),
            task_finished_at: toDateTimePayload(
                editForm.value.task_finished_at,
            ),
            status: editForm.value.status,
            remarks: editForm.value.remarks || null,
        };
        const res = await api.put(`/task-board/${editingId.value}`, payload);
        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: "Task updated successfully",
                showConfirmButton: false,
                timer: 1500,
            });
            closeEdit();
            await loadTasks();
        } else {
            Swal.fire(
                "Error",
                res.data.message || "Unable to update task.",
                "error",
            );
        }
    } catch (err) {
        Swal.fire(
            "Error",
            err.response?.data?.message || "Unable to update task.",
            "error",
        );
    } finally {
        editSubmitting.value = false;
    }
};

const removeTask = async (item) => {
    const result = await Swal.fire({
        title: "Delete this task?",
        text: item.task_name,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Delete",
    });
    if (!result.isConfirmed) return;

    try {
        const res = await api.delete(`/task-board/${item.id}`);
        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: "Task deleted",
                showConfirmButton: false,
                timer: 1500,
            });
            await loadTasks();
        }
    } catch (err) {
        Swal.fire(
            "Error",
            err.response?.data?.message || "Unable to delete task.",
            "error",
        );
    }
};

const filteredCount = computed(() => tasks.value.length);

onMounted(async () => {
    resetForm();
    try {
        await Promise.all([loadEmployees(), loadTasks()]);
    } catch (err) {
        error.value = err.response?.data?.message || err.message;
    }
});
</script>

<template>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="mb-4">Task Assign</h2>
                        <p class="text-muted" v-if="role_id == 1">
                            As an administrator you can assign tasks to any
                            employee. One assigner per task. Subordinates update
                            status and remarks from the mobile Task Board.
                        </p>
                        <p class="text-muted" v-else>
                            Assign tasks to one or more employees under your
                            supervision. One assigner per task. Subordinates
                            update status and remarks from the mobile Task
                            Board.
                        </p>
                        <div
                            v-if="employees.length === 0"
                            class="alert alert-info"
                        >
                            {{
                                role_id == 1
                                    ? "No employees are available to assign tasks to."
                                    : "No employees are currently under your supervision."
                            }}
                        </div>
                        <form @submit.prevent="submit">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Employee(s)</label
                                    >
                                    <Multiselect
                                        v-model="selectedEmployees"
                                        :options="createEmployeeOptions"
                                        :multiple="true"
                                        :custom-label="employeeLabel"
                                        track-by="id"
                                        :searchable="true"
                                        :allow-empty="true"
                                        :close-on-select="false"
                                        :preserve-search="true"
                                        :show-labels="false"
                                        :disabled="employees.length === 0"
                                        placeholder="Select employees"
                                    />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Task Name</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.task_name"
                                        maxlength="255"
                                    />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Task Assigned At</label
                                    >
                                    <input
                                        type="datetime-local"
                                        class="form-control"
                                        v-model="form.task_assigned_at"
                                    />
                                </div>
                                <div class="col-12">
                                    <label class="form-label"
                                        >Description</label
                                    >
                                    <textarea
                                        class="form-control"
                                        rows="3"
                                        v-model="form.description"
                                    ></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select
                                        class="form-control"
                                        v-model="form.status"
                                    >
                                        <option
                                            v-for="opt in statusOptions"
                                            :key="opt"
                                            :value="opt"
                                        >
                                            {{ opt }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"
                                        >Remarks (optional)</label
                                    >
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.remarks"
                                        maxlength="5000"
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                class="btn btn-success mt-3"
                                :disabled="submitting || employees.length === 0"
                            >
                                Assign Task
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4" v-if="showEdit">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-body">
                        <h4 class="mb-3">Edit Task</h4>
                        <form @submit.prevent="saveEdit">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Employee(s)</label
                                    >
                                    <Multiselect
                                        v-model="editSelectedEmployees"
                                        :options="editEmployeeOptions"
                                        :multiple="true"
                                        :custom-label="employeeLabel"
                                        track-by="id"
                                        :searchable="true"
                                        :allow-empty="true"
                                        :close-on-select="false"
                                        :preserve-search="true"
                                        :show-labels="false"
                                        placeholder="Select employees"
                                    />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Task Name</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="editForm.task_name"
                                        maxlength="255"
                                    />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Task Assigned At</label
                                    >
                                    <input
                                        type="datetime-local"
                                        class="form-control"
                                        v-model="editForm.task_assigned_at"
                                    />
                                </div>
                                <div class="col-12">
                                    <label class="form-label"
                                        >Description</label
                                    >
                                    <textarea
                                        class="form-control"
                                        rows="3"
                                        v-model="editForm.description"
                                    ></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <select
                                        class="form-control"
                                        v-model="editForm.status"
                                    >
                                        <option
                                            v-for="opt in statusOptions"
                                            :key="opt"
                                            :value="opt"
                                        >
                                            {{ opt }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Task Finished At (optional)</label
                                    >
                                    <input
                                        type="datetime-local"
                                        class="form-control"
                                        v-model="editForm.task_finished_at"
                                    />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Remarks (optional)</label
                                    >
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="editForm.remarks"
                                        maxlength="5000"
                                    />
                                </div>
                            </div>
                            <div class="mt-3">
                                <button
                                    class="btn btn-primary me-2"
                                    type="submit"
                                    :disabled="editSubmitting"
                                >
                                    Update Task
                                </button>
                                <button
                                    class="btn btn-secondary"
                                    type="button"
                                    @click="closeEdit"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-between align-items-center mb-3"
                        >
                            <h2 class="m-0">Assigned Tasks</h2>
                            <span class="text-muted"
                                >{{ filteredCount }} record(s)</span
                            >
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select
                                    class="form-control"
                                    v-model="filters.eid"
                                >
                                    <option value="">All employees</option>
                                    <option
                                        v-for="emp in employees"
                                        :key="'f-' + emp.id"
                                        :value="emp.id"
                                    >
                                        {{ employeeLabel(emp) }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select
                                    class="form-control"
                                    v-model="filters.status"
                                >
                                    <option value="">All statuses</option>
                                    <option value="Assigned">Assigned</option>
                                    <option value="Complete">Complete</option>
                                    <option value="Incomplete">
                                        Incomplete
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="filters.from"
                                />
                            </div>
                            <div class="col-md-2">
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="filters.to"
                                />
                            </div>
                            <div class="col-md-2">
                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Search task name"
                                    v-model="filters.q"
                                />
                            </div>
                            <div class="col-md-1">
                                <button
                                    class="btn btn-primary w-100"
                                    type="button"
                                    @click="loadTasks"
                                >
                                    Filter
                                </button>
                            </div>
                        </div>
                        <div v-if="error" class="alert alert-danger">
                            {{ error }}
                        </div>
                        <div class="table-responsive">
                            <table
                                class="table table-bordered text-center align-middle"
                            >
                                <thead>
                                    <tr>
                                        <th>Assigned To</th>
                                        <th>Task Name</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Assigned At</th>
                                        <th>Finished At</th>
                                        <th>Remarks</th>
                                        <th>Assigned By</th>
                                        <th>Updated By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loading">
                                        <td colspan="10">Loading…</td>
                                    </tr>
                                    <tr v-else-if="tasks.length === 0">
                                        <td colspan="10">No tasks found.</td>
                                    </tr>
                                    <tr v-for="item in tasks" :key="item.id">
                                        <td>
                                            <span
                                                v-for="name in item.assignee_names"
                                                :key="name"
                                                class="badge bg-secondary me-1"
                                            >
                                                {{ name }}
                                            </span>
                                            <span
                                                v-if="
                                                    !item.assignee_names ||
                                                    item.assignee_names
                                                        .length === 0
                                                "
                                            >
                                                {{ item.employee_name || "—" }}
                                            </span>
                                        </td>
                                        <td>{{ item.task_name }}</td>
                                        <td class="text-start">
                                            {{ item.description || "—" }}
                                        </td>
                                        <td>
                                            <span
                                                :class="
                                                    statusClass(item.status)
                                                "
                                            >
                                                {{ item.status }}
                                            </span>
                                        </td>
                                        <td>
                                            {{
                                                formatDateTime(
                                                    item.task_assigned_at,
                                                )
                                            }}
                                        </td>
                                        <td>
                                            {{
                                                formatDateTime(
                                                    item.task_finished_at,
                                                )
                                            }}
                                        </td>
                                        <td class="text-start">
                                            {{ item.remarks || "—" }}
                                        </td>
                                        <td>
                                            {{ item.assigned_by_name || "—" }}
                                        </td>
                                        <td>
                                            {{ item.updated_by_name || "—" }}
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
                                                @click="removeTask(item)"
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
        </div>
    </div>
</template>

<style scoped>
:deep(.multiselect) {
    min-height: 38px;
}
:deep(.multiselect__tags) {
    min-height: 38px;
    padding-top: 8px;
}
</style>
