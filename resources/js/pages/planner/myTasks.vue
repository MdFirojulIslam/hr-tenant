<template>
    <div class="planner-page my-tasks">
        <div class="page-header mb-3">
            <h4 class="mb-0"><i class="fa-solid fa-user-check me-2"></i>My Tasks</h4>
            <span class="text-muted small">Tasks assigned to you</span>
        </div>

        <div class="planner-toolbar card mb-3">
            <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                <button
                    class="btn btn-sm"
                    :class="view === 'grid' ? 'btn-primary' : 'btn-outline-primary'"
                    type="button"
                    @click="view = 'grid'"
                >
                    <i class="fa-solid fa-table-cells me-1"></i>Grid
                </button>
                <button
                    class="btn btn-sm"
                    :class="view === 'board' ? 'btn-primary' : 'btn-outline-primary'"
                    type="button"
                    @click="view = 'board'"
                >
                    <i class="fa-solid fa-columns me-1"></i>Board
                </button>

                <div class="vr d-none d-sm-block"></div>

                <button
                    class="btn btn-sm"
                    :class="privacy === 'Private' ? 'btn-secondary text-white' : 'btn-outline-secondary'"
                    type="button"
                    @click="togglePrivacy('Private')"
                >
                    <i class="fa-solid fa-lock me-1"></i>Private
                </button>
                <button
                    class="btn btn-sm"
                    :class="privacy === 'Shared' ? 'btn-secondary text-white' : 'btn-outline-secondary'"
                    type="button"
                    @click="togglePrivacy('Shared')"
                >
                    <i class="fa-solid fa-users me-1"></i>Shared
                </button>

                <div class="vr d-none d-sm-block"></div>

                <select v-model="statusFilter" class="form-select form-select-sm" style="width: auto">
                    <option value="All">All</option>
                    <option value="Not started">Not started</option>
                    <option value="In progress">In progress</option>
                    <option value="Completed">Completed</option>
                </select>

                <div class="employee-search position-relative">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        v-model="employeeSearch"
                        type="text"
                        class="form-control form-control-sm"
                        style="padding-left: 30px; width: 200px"
                        placeholder="Employee name..."
                    />
                </div>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <span v-if="loading" class="small text-muted">
                        <span class="spinner-border spinner-border-sm me-1"></span>Loading...
                    </span>
                    <button class="btn btn-sm btn-outline-secondary" type="button" title="Refresh" @click="reload">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="savingError" class="alert alert-danger py-2 small">
            <i class="fa-solid fa-circle-exclamation me-1"></i>{{ savingError }}
        </div>

        <div v-if="view === 'grid'" class="planner-grid card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover planner-table align-middle mb-0">
                        <thead>
                            <tr class="table-light">
                                <th style="width: 30px">
                                    <input type="checkbox" class="form-check-input" />
                                </th>
                                <th style="min-width: 200px">Task Name</th>
                                <th style="min-width: 120px">Assign To</th>
                                <th style="min-width: 110px">Start Date</th>
                                <th style="min-width: 110px">Due Date</th>
                                <th>Bucket</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Privacy</th>
                                <th style="min-width: 150px">Notes</th>
                                <th style="width: 40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(task, index) in filteredTasks" :key="task.id">
                                <tr
                                    class="planner-row"
                                    :class="{ 'text-muted': !task.id }"
                                    @mouseenter="hoverRow = task.id"
                                    @mouseleave="hoverRow = null"
                                >
                                    <td>
                                        <input type="checkbox" class="form-check-input" />
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="flex-grow-1">
                                                <textarea
                                                    class="form-control form-control-sm planner-inline-input"
                                                    rows="1"
                                                    :value="task.task_name"
                                                    placeholder="Task name"
                                                    @blur="onNameBlur(task)"
                                                    @input="onNameInput(task, $event)"
                                                ></textarea>
                                            </div>
                                            <span v-if="task.my_read_status" class="text-success small" title="Read">
                                                <i class="fa-solid fa-check-circle"></i>
                                            </span>
                                            <span v-else class="text-warning small" title="Unread">
                                                <i class="fa-solid fa-circle"></i>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="assignee-avatars d-flex align-items-center">
                                                <span
                                                    v-for="(a, i) in task.assignees"
                                                    :key="a.id ?? i"
                                                    class="avatar-chip"
                                                    :title="a.employee?.Full_Name || 'Assigned'"
                                                >
                                                    {{ initials(a.employee?.Full_Name) }}
                                                </span>
                                                <span v-if="!task.assignees.length" class="text-muted small">—</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            type="date"
                                            class="form-control form-control-sm"
                                            :value="task.start_date"
                                            @change="onStartDateChange(task, $event)"
                                        />
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <input
                                                type="date"
                                                class="form-control form-control-sm"
                                                :class="{ 'due-overdue': isOverdue(task) }"
                                                :value="task.due_date"
                                                @change="onDueDateChange(task, $event)"
                                            />
                                            <span
                                                v-if="isOverdue(task)"
                                                class="badge bg-danger ms-1"
                                                title="Overdue"
                                            >
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <select
                                            class="form-select form-select-sm"
                                            :value="task.bucket"
                                            @change="onFieldChange(task, 'bucket', $event)"
                                        >
                                            <option v-for="b in VALUES.buckets" :key="b" :value="b">{{ b }}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="badge" :class="statusBadge(task.status)">
                                            {{ task.status }}
                                        </span>
                                        <select
                                            class="form-select form-select-sm status-select mt-1"
                                            :value="task.status"
                                            @change="onFieldChange(task, 'status', $event)"
                                        >
                                            <option v-for="s in VALUES.statuses" :key="s" :value="s">{{ s }}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select
                                            class="form-select form-select-sm"
                                            :class="priorityClass(task.priority)"
                                            :value="task.priority"
                                            @change="onFieldChange(task, 'priority', $event)"
                                        >
                                            <option v-for="p in VALUES.priorities" :key="p" :value="p">{{ p }}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ task.privacy }}</span>
                                    </td>
                                    <td>
                                        <textarea
                                            class="form-control form-control-sm planner-inline-input"
                                            rows="1"
                                            :value="task.notes"
                                            placeholder="Add notes..."
                                            @blur="onNotesBlur(task)"
                                            @input="onNotesInput(task, $event)"
                                        ></textarea>
                                    </td>
                                    <td>
                                        <button
                                            class="btn btn-sm btn-light text-primary"
                                            type="button"
                                            title="Open detail"
                                            @click="openDetailModal(task)"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-else class="planner-board">
            <div class="row g-3">
                <div
                    v-for="col in boardColumns"
                    :key="col.status"
                    class="col-md-4"
                >
                    <div class="board-column card h-100">
                        <div class="card-header py-2 d-flex align-items-center">
                            <span class="dot me-2" :class="col.dotClass"></span>
                            <strong class="small">{{ col.status }}</strong>
                            <span class="badge bg-light text-dark ms-2">{{ boardTasks(col.status).length }}</span>
                        </div>
                        <div class="card-body bg-light border-top">
                            <div class="board-cards">
                                <div
                                    v-for="task in boardTasks(col.status)"
                                    :key="task.id"
                                    class="board-card card mb-2 shadow-sm"
                                    @click="openDetailModal(task)"
                                >
                                    <div class="card-body p-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="fw-semibold small board-task-name">
                                                {{ task.task_name || "Untitled task" }}
                                            </div>
                                            <span
                                                class="badge"
                                                :class="priorityBadge(task.priority)"
                                                style="font-size: 0.65rem"
                                            >
                                                {{ task.priority }}
                                            </span>
                                        </div>
                                        <div
                                            class="small mt-1"
                                            :class="{ 'text-danger fw-semibold': isOverdue(task) }"
                                        >
                                            <i class="fa-regular fa-calendar me-1"></i>
                                            <span v-if="isOverdue(task)">
                                                Overdue · {{ task.due_date }}
                                                <i class="fa-solid fa-triangle-exclamation ms-1"></i>
                                            </span>
                                            <span v-else>{{ task.due_date || "No due date" }}</span>
                                        </div>

                                        <div v-if="showChecklistToggle" class="mt-2">
                                            <div class="d-flex align-items-center small text-muted">
                                                <i class="fa-regular fa-square-check me-1"></i>
                                                <button
                                                    class="btn btn-link btn-sm p-0"
                                                    type="button"
                                                    @click="toggleChecklistVisibility(task)"
                                                >
                                                    {{ task.checklist_progress.completed }}/{{ task.checklist_progress.total }}
                                                    <i
                                                        class="fa-solid ms-1"
                                                        :class="task._showChecklists ? 'fa-chevron-up' : 'fa-chevron-down'"
                                                    ></i>
                                                </button>
                                            </div>
                                            <div v-if="task._showChecklists" class="checklist-items mt-1">
                                                <div
                                                    v-for="cl in task.checklists"
                                                    :key="cl.id"
                                                    class="small d-flex align-items-center"
                                                >
                                                    <i
                                                        class="fa-regular me-1"
                                                        :class="cl.completed_status ? 'fa-square-check text-success' : 'fa-square text-muted'"
                                                    ></i>
                                                    <span :class="{ 'text-decoration-line-through text-muted': cl.completed_status }">
                                                        {{ cl.checklist_name }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center mt-2">
                                            <div class="assignee-avatars d-flex align-items-center flex-wrap">
                                                <span
                                                    v-for="(a, i) in task.assignees"
                                                    :key="a.id ?? i"
                                                    class="avatar-chip"
                                                    :title="a.employee?.Full_Name || 'Assigned'"
                                                >
                                                    {{ initials(a.employee?.Full_Name) }}
                                                </span>
                                            </div>
                                            <span
                                                class="ms-2 small"
                                                :class="task.my_read_status ? 'text-success' : 'text-warning'"
                                                :title="task.my_read_status ? 'You have read this' : 'You have not read this'"
                                            >
                                                <i class="fa-solid" :class="task.my_read_status ? 'fa-check-circle' : 'fa-circle'"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!boardTasks(col.status).length" class="text-center text-muted small py-4">
                                    No tasks
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!loading && !filteredTasks.length && view === 'grid'" class="empty-state text-center py-5 text-muted">
            <i class="fa-regular fa-folder-open fa-2x mb-2"></i>
            <div>No tasks assigned to you</div>
        </div>

        <div v-if="detailTask" class="modal fade show d-block task-detail-modal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content shadow">
                    <div class="modal-header py-2">
                        <h5 class="modal-title small fw-semibold">
                            <i class="fa-solid fa-list-check me-2 text-primary"></i>
                            {{ detailForm.task_name || "Untitled task" }}
                        </h5>
                        <button class="btn-close" type="button" aria-label="Close" @click="closeDetailModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Task Name</label>
                                <input
                                    v-model="detailForm.task_name"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Enter task name"
                                    @input="debouncedDetailSave('task_name')"
                                />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Assignees</label>
                                <div class="assignee-avatars d-flex flex-wrap align-items-center mb-2">
                                    <span
                                        v-for="(a, i) in detailForm.assignees"
                                        :key="a.id ?? i"
                                        class="avatar-chip"
                                        :title="a.employee?.Full_Name"
                                    >
                                        {{ initials(a.employee?.Full_Name) }}
                                    </span>
                                    <span v-if="!detailForm.assignees.length" class="text-muted small">None</span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Start Date</label>
                                <input
                                    v-model="detailForm.start_date"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @change="immediateSave('start_date')"
                                />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Due Date</label>
                                <input
                                    v-model="detailForm.due_date"
                                    type="date"
                                    class="form-control form-control-sm"
                                    :class="{ 'due-overdue-input': isOverdue(detailForm) }"
                                    @change="immediateSave('due_date')"
                                />
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Status</label>
                                <select
                                    v-model="detailForm.status"
                                    class="form-select form-select-sm"
                                    @change="immediateSave('status')"
                                >
                                    <option v-for="s in VALUES.statuses" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Repeat</label>
                                <select
                                    v-model="detailForm.repeat"
                                    class="form-select form-select-sm"
                                    @change="immediateSave('repeat')"
                                >
                                    <option v-for="r in VALUES.repeats" :key="r" :value="r">{{ r }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Bucket</label>
                                <select
                                    v-model="detailForm.bucket"
                                    class="form-select form-select-sm"
                                    @change="immediateSave('bucket')"
                                >
                                    <option v-for="b in VALUES.buckets" :key="b" :value="b">{{ b }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Priority</label>
                                <select
                                    v-model="detailForm.priority"
                                    class="form-select form-select-sm"
                                    @change="immediateSave('priority')"
                                >
                                    <option v-for="p in VALUES.priorities" :key="p" :value="p">{{ p }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Privacy</label>
                                <select
                                    v-model="detailForm.privacy"
                                    class="form-select form-select-sm"
                                    @change="immediateSave('privacy')"
                                >
                                    <option v-for="pr in VALUES.privacies" :key="pr" :value="pr">{{ pr }}</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Notes</label>
                                <textarea
                                    v-model="detailForm.notes"
                                    class="form-control form-control-sm"
                                    rows="2"
                                    placeholder="Add notes..."
                                    @input="debouncedDetailSave('notes')"
                                ></textarea>
                            </div>

                            <div class="col-md-12">
                                <div class="checklist-section">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <strong class="small">
                                            Checklists ({{ detailChecklistCompleted }} of {{ detailChecklists.length }})
                                        </strong>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check form-switch mb-0">
                                                <input
                                                    id="showChecklistToggle"
                                                    v-model="showChecklistToggle"
                                                    class="form-check-input"
                                                    type="checkbox"
                                                />
                                                <label
                                                    class="form-check-label small"
                                                    for="showChecklistToggle"
                                                >
                                                    Show on card
                                                </label>
                                            </div>
                                            <div class="input-group input-group-sm" style="width: auto">
                                                <input
                                                    v-model="newChecklistName"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="New checklist item"
                                                    @keyup.enter="addChecklistItem"
                                                />
                                                <button
                                                    class="btn btn-outline-primary"
                                                    type="button"
                                                    @click="addChecklistItem"
                                                >
                                                    <i class="fa-solid fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="!detailChecklists.length" class="text-muted small py-2">
                                        No checklists yet.
                                    </div>
                                    <div
                                        v-for="cl in detailChecklists"
                                        :key="cl.id"
                                        class="checklist-item d-flex align-items-center mb-1"
                                    >
                                        <input
                                            type="checkbox"
                                            class="form-check-input me-2"
                                            :checked="cl.completed_status"
                                            @change="onChecklistToggle(cl, $event)"
                                        />
                                        <input
                                            class="form-control form-control-sm flex-grow-1 checklist-name-input"
                                            :class="{ 'text-decoration-line-through': cl.completed_status }"
                                            :value="cl.checklist_name"
                                            @keyup.enter="saveChecklistName(cl)"
                                        />
                                        <button
                                            class="btn btn-sm btn-link text-danger"
                                            type="button"
                                            title="Remove"
                                            @click="removeChecklistItem(cl)"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 small text-muted">
                                <span>Created by:</span>
                                <span class="fw-semibold ms-1">{{ detailTask.created_by_info?.Full_Name || "—" }}</span>
                                <span class="mx-2">·</span>
                                <span v-if="detailTask.my_read_status" class="text-success">
                                    <i class="fa-solid fa-check-circle me-1"></i>Read
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show" @click="closeDetailModal"></div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from "vue";
import Swal from "sweetalert2";
import { usePlanner } from "./composables/usePlanner";

const {
    tasks,
    loading,
    savingError,
    VALUES,
    initials,
    isOverdue,
    loadTasks,
    updateTask,
    addChecklist,
    updateChecklist,
    deleteChecklist,
    markRead,
} = usePlanner();

const view = ref("grid");
const privacy = ref(null);
const statusFilter = ref("All");
const employeeSearch = ref("");
const hoverRow = ref(null);
const showChecklistToggle = ref(true);

const detailTask = ref(null);
const detailForm = reactive({});
const detailChecklists = ref([]);
const newChecklistName = ref("");

onMounted(() => {
    loadTasks(buildParams());
});

function buildParams() {
    const params = { view: "my" };
    if (privacy.value) params.privacy = privacy.value;
    if (statusFilter.value && statusFilter.value !== "All") params.status = statusFilter.value;
    if (employeeSearch.value) params.employee = employeeSearch.value;
    return params;
}

const filteredTasks = computed(() => {
    const q = employeeSearch.value.trim().toLowerCase();
    return tasks.value.filter((task) => {
        if (!q) return true;
        return task.assignees.some((a) =>
            (a.employee?.Full_Name || "").toLowerCase().includes(q)
        );
    });
});

const boardColumns = [
    { status: "Not started", dotClass: "bg-secondary" },
    { status: "In progress", dotClass: "bg-warning" },
    { status: "Completed", dotClass: "bg-success" },
];

function boardTasks(status) {
    return tasks.value.filter((t) => t.status === status);
}

async function reload() {
    await loadTasks(buildParams());
}

function togglePrivacy(value) {
    privacy.value = privacy.value === value ? null : value;
}

watch([privacy, statusFilter], () => {
    loadTasks(buildParams());
});

function onNameInput(task, event) {
    task.task_name = event.target.value;
    task._dirtyName = true;
}

function onNameBlur(task) {
    if (!task._dirtyName) return;
    delete task._dirtyName;
    saveTextField(task, "task_name");
}

function onNotesInput(task, event) {
    task.notes = event.target.value;
    task._dirtyNotes = true;
}

function onNotesBlur(task) {
    if (!task._dirtyNotes) return;
    delete task._dirtyNotes;
    saveTextField(task, "notes");
}

const saveTextField = async (task, field) => {
    task.saving_state = "saving";
    try {
        await updateTask(task.id, { [field]: task[field] });
        task.saving_state = "saved";
        setTimeout(() => (task.saving_state = ""), 1500);
    } catch (e) {
        task.saving_state = "error";
    }
};

async function onFieldChange(task, field, event) {
    const value = event.target.value;
    task[field] = value;
    task.saving_state = "saving";
    try {
        await updateTask(task.id, { [field]: value });
        task.saving_state = "saved";
        setTimeout(() => (task.saving_state = ""), 1500);
    } catch (e) {
        task.saving_state = "error";
    }
}

async function onStartDateChange(task, event) {
    task.start_date = event.target.value;
    await onFieldChange(task, "start_date", { target: { value: task.start_date } });
}

async function onDueDateChange(task, event) {
    task.due_date = event.target.value;
    await onFieldChange(task, "due_date", { target: { value: task.due_date } });
}

function statusBadge(status) {
    if (status === "Completed") return "bg-success";
    if (status === "In progress") return "bg-warning text-dark";
    return "bg-secondary";
}

function priorityClass(priority) {
    if (priority === "Urgent") return "border-danger text-danger";
    if (priority === "Important") return "border-warning text-warning";
    return "";
}

function priorityBadge(priority) {
    if (priority === "Urgent") return "bg-danger";
    if (priority === "Important") return "bg-warning text-dark";
    if (priority === "Low") return "bg-secondary";
    return "bg-primary";
}

function openDetailModal(task) {
    if (!task.id) return;
    detailTask.value = task;
    Object.assign(detailForm, {
        task_name: task.task_name,
        start_date: task.start_date,
        due_date: task.due_date,
        status: task.status,
        repeat: task.repeat,
        bucket: task.bucket,
        priority: task.priority,
        privacy: task.privacy,
        notes: task.notes,
        assignees: [...task.assignees],
    });
    detailChecklists.value = [...task.checklists];
    if (task.id) {
        markRead(task.id);
        const updTask = tasks.value.find((t) => t.id === task.id);
        if (updTask) updTask.my_read_status = true;
        detailTask.value.my_read_status = true;
    }
}

function closeDetailModal() {
    detailTask.value = null;
}

function syncDetailToTask() {
    if (!detailTask.value) return;
    const idx = tasks.value.findIndex((t) => t.id === detailTask.value.id);
    if (idx !== -1) {
        tasks.value[idx] = { ...tasks.value[idx], ...detailForm };
        tasks.value[idx].checklists = [...detailChecklists.value];
        const completed = detailChecklists.value.filter((c) => c.completed_status).length;
        tasks.value[idx].checklist_progress = {
            completed,
            total: detailChecklists.value.length,
        };
    }
}

const detailChecklistCompleted = computed(() =>
    detailChecklists.value.filter((c) => c.completed_status).length
);

async function addChecklistItem() {
    const name = newChecklistName.value.trim();
    if (!name || !detailTask.value?.id) return;
    try {
        const created = await addChecklist(detailTask.value.id, name);
        detailChecklists.value.push(created);
        newChecklistName.value = "";
        syncDetailToTask();
    } catch (e) {
        Swal.fire("Error", "Could not add checklist item.", "error");
    }
}

async function onChecklistToggle(cl, event) {
    if (!detailTask.value?.id) return;
    const checked = event.target.checked;
    const originalStatus = cl.completed_status;
    cl.completed_status = checked;
    try {
        await updateChecklist(detailTask.value.id, cl.id, {
            completed_status: checked,
        });
        if (!checked) cl.completed_by = null;
        syncDetailToTask();
    } catch (e) {
        cl.completed_status = originalStatus;
        Swal.fire("Error", "Could not update checklist.", "error");
    }
}

async function saveChecklistName(cl) {
    if (!detailTask.value?.id || !cl?.id) return;
    try {
        await updateChecklist(detailTask.value.id, cl.id, {
            checklist_name: cl.checklist_name,
        });
        syncDetailToTask();
    } catch (e) {
        Swal.fire("Error", "Could not update checklist name.", "error");
    }
}

async function removeChecklistItem(cl) {
    if (!detailTask.value?.id || !cl?.id) return;
    try {
        await deleteChecklist(detailTask.value.id, cl.id);
        detailChecklists.value = detailChecklists.value.filter((c) => c.id !== cl.id);
        syncDetailToTask();
    } catch (e) {
        Swal.fire("Error", "Could not remove checklist item.", "error");
    }
}

function toggleChecklistVisibility(task) {
    task._showChecklists = !task._showChecklists;
}

function debouncedDetailSave(field) {
    clearTimeout(detailForm._timer);
    detailForm._timer = setTimeout(async () => {
        if (!detailTask.value?.id) return;
        try {
            await updateTask(detailTask.value.id, { [field]: detailForm[field] });
            syncDetailToTask();
        } catch (e) {
            savingError.value = e.response?.data?.message || e.message;
        }
    }, 600);
}

async function immediateSave(field) {
    if (!detailTask.value?.id) return;
    try {
        await updateTask(detailTask.value.id, { [field]: detailForm[field] });
        syncDetailToTask();
    } catch (e) {
        savingError.value = e.response?.data?.message || e.message;
    }
}
</script>

<style scoped>
.planner-page {
    font-family: inheritable, sans-serif;
}

.employee-search .search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 0.8rem;
}

.planner-table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}

.planner-inline-input {
    resize: none;
    border: 1px solid transparent;
    background: transparent;
    transition: border-color 0.15s;
}

.planner-inline-input:hover,
.planner-inline-input:focus {
    border-color: #ced4da;
    background-color: #fff;
}

.due-overdue {
    border-color: #dc3545;
    color: #dc3545;
}

.due-overdue-input {
    border-color: #dc3545;
}

.assignee-avatars {
    gap: 2px;
}

.avatar-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #2563eb;
    color: #fff;
    font-size: 0.6rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    flex-shrink: 0;
    cursor: default;
}

.board-card {
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}

.board-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
}

.board-task-name {
    word-break: break-word;
}

.board-column .card-header {
    background-color: #fff;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.task-detail-modal {
    z-index: 1050;
}

.task-detail-modal .modal-backdrop {
    z-index: -1;
    background-color: rgba(0, 0, 0, 0.5);
    position: fixed;
    inset: 0;
}

.checklist-name-input {
    border: 1px solid transparent;
    background-color: transparent;
}

.checklist-name-input:hover,
.checklist-name-input:focus {
    border-color: #ced4da;
    background-color: #fff;
}

.task-detail-modal .modal-dialog {
    z-index: 1056;
    position: relative;
}
</style>
