<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const error = ref(null);
const loading = ref(false);
const submitting = ref(false);
const subordinates = ref([]);
const tasks = ref([]);

const form = ref({
    eid: "",
    task_name: "",
    description: "",
    task_assigned_at: "",
});

const filters = ref({
    eid: "",
    status: "",
    q: "",
    from: "",
    to: "",
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
    form.value = {
        eid: "",
        task_name: "",
        description: "",
        task_assigned_at: defaultAssignedAt(),
    };
};

const loadSubordinates = async () => {
    const res = await api.get("/task-board/subordinates");
    subordinates.value = res.data.data || [];
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

const submit = async () => {
    if (
        !form.value.eid ||
        !form.value.task_name ||
        !form.value.task_assigned_at
    ) {
        Swal.fire(
            "Warning",
            "Employee, task name, and assigned at are required.",
            "warning",
        );
        return;
    }
    if (
        !subordinates.value.some(
            (emp) => String(emp.id) === String(form.value.eid),
        )
    ) {
        Swal.fire(
            "Warning",
            "You may only assign tasks to employees under your supervision.",
            "warning",
        );
        return;
    }

    submitting.value = true;
    try {
        const payload = {
            eid: form.value.eid,
            task_name: form.value.task_name,
            description: form.value.description,
            task_assigned_at:
                form.value.task_assigned_at.replace("T", " ") + ":00",
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

const filteredCount = computed(() => tasks.value.length);

onMounted(async () => {
    resetForm();
    try {
        await Promise.all([loadSubordinates(), loadTasks()]);
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
                        <h2 class="mb-4">Task Assign Panel</h2>
                        <p class="text-muted">
                            Assign tasks only to employees under your
                            supervision. Subordinates update status and remarks
                            from the mobile Task Board.
                        </p>
                        <div
                            v-if="subordinates.length === 0"
                            class="alert alert-info"
                        >
                            No employees are currently under your supervision.
                        </div>
                        <form @submit.prevent="submit">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Employee</label>
                                    <select
                                        class="form-control"
                                        v-model="form.eid"
                                        :disabled="subordinates.length === 0"
                                    >
                                        <option value="">
                                            {{
                                                subordinates.length === 0
                                                    ? "No subordinates available"
                                                    : "Select employee"
                                            }}
                                        </option>
                                        <option
                                            v-for="emp in subordinates"
                                            :key="emp.id"
                                            :value="emp.id"
                                        >
                                            {{ emp.Full_Name }}
                                            <span v-if="emp.Employee_Id">
                                                ({{ emp.Employee_Id }})
                                            </span>
                                        </option>
                                    </select>
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
                            </div>
                            <button
                                type="submit"
                                class="btn btn-success mt-3"
                                :disabled="
                                    submitting || subordinates.length === 0
                                "
                            >
                                Assign Task
                            </button>
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
                                        v-for="emp in subordinates"
                                        :key="'f-' + emp.id"
                                        :value="emp.id"
                                    >
                                        {{ emp.Full_Name }}
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
                                        <th>Employee</th>
                                        <th>Task Name</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Assigned At</th>
                                        <th>Finished At</th>
                                        <th>Remarks</th>
                                        <th>Created By</th>
                                        <th>Updated By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loading">
                                        <td colspan="9">Loading…</td>
                                    </tr>
                                    <tr v-else-if="tasks.length === 0">
                                        <td colspan="9">No tasks found.</td>
                                    </tr>
                                    <tr v-for="item in tasks" :key="item.id">
                                        <td>{{ item.employee_name }}</td>
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
                                            {{ item.created_by_name || "—" }}
                                        </td>
                                        <td>
                                            {{ item.updated_by_name || "—" }}
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
