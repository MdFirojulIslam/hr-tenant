<template>
    <div class="planner-page">
        <!-- ===================== HEADER ===================== -->
        <header class="pl-hero">
            <div class="pl-hero-main">
                <span class="pl-hero-icon" aria-hidden="true">
                    <i class="fa-solid fa-list-check"></i>
                </span>
                <div class="pl-hero-text">
                    <h1 class="pl-hero-title">Planner</h1>
                    <p class="pl-hero-sub">Task assignment &amp; follow-up</p>
                </div>
            </div>

            <dl class="pl-hero-stats" aria-label="Planner overview">
                <div class="pl-hero-stat">
                    <dt>Tasks</dt>
                    <dd>{{ tasks.length }}</dd>
                </div>
                <div class="pl-hero-stat">
                    <dt>In progress</dt>
                    <dd class="is-progress">
                        {{
                            tasks.filter((t) => t.status === "In progress")
                                .length
                        }}
                    </dd>
                </div>
                <div class="pl-hero-stat">
                    <dt>Completed</dt>
                    <dd class="is-done">
                        {{
                            tasks.filter((t) => t.status === "Completed").length
                        }}
                    </dd>
                </div>
                <div class="pl-hero-stat">
                    <dt>Overdue</dt>
                    <dd :class="{ 'is-overdue': tasks.some(isOverdue) }">
                        {{ tasks.filter(isOverdue).length }}
                    </dd>
                </div>
            </dl>

            <button
                class="btn btn-primary pl-hero-action"
                type="button"
                @click="addNewTask"
            >
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Add task</span>
            </button>
        </header>

        <TaskFiltersToolbar
            v-model:view="view"
            :privacy="privacy"
            v-model:statusFilter="statusFilter"
            v-model:employeeSearch="employeeSearch"
            :loading="loading"
            @toggle-privacy="togglePrivacy"
            @reload="reload"
        />

        <div
            v-if="savingError"
            class="alert alert-danger py-2 small pl-alert"
            role="alert"
        >
            <i
                class="fa-solid fa-circle-exclamation me-1"
                aria-hidden="true"
            ></i
            >{{ savingError }}
        </div>

        <div v-if="loading && !tasks.length" class="pl-skeleton-grid">
            <div v-for="n in 3" :key="n" class="pl-panel sk-column">
                <div class="pl-skeleton sk-column-head"></div>
                <div class="sk-cards">
                    <div
                        v-for="c in n === 2 ? 3 : 2"
                        :key="c"
                        class="pl-skeleton sk-card"
                    >
                        <div class="pl-skeleton sk-line sk-line-title"></div>
                        <div class="pl-skeleton sk-line sk-line-meta"></div>
                        <div class="pl-skeleton sk-line sk-line-short"></div>
                    </div>
                </div>
            </div>
        </div>

        <TaskTable
            v-else-if="view === 'grid'"
            mode="assign"
            :tasks="filteredTasks"
            :values="VALUES"
            :initials="initials"
            :is-overdue="isOverdue"
            :can-delete="canDelete"
            :on-name-input="onNameInput"
            :on-name-blur="onNameBlur"
            :on-notes-input="onNotesInput"
            :on-notes-blur="onNotesBlur"
            :on-field-change="onFieldChange"
            :on-start-date-change="onStartDateChange"
            :on-due-date-change="onDueDateChange"
            :open-detail-modal="openDetailModal"
            :add-inline-below="addInlineBelow"
            :add-new-task="addNewTask"
            :confirm-delete="confirmDelete"
            :open-assignee-picker="openAssigneePicker"
        />

        <TaskBoard
            v-else
            mode="assign"
            :tasks="tasks"
            :initials="initials"
            :is-overdue="isOverdue"
            :show-checklist-toggle="showChecklistToggle"
            :open-detail-modal="openDetailModal"
            :open-assignee-picker="openAssigneePicker"
            :add-new-task="addNewTask"
        />

        <div
            v-if="!loading && !filteredTasks.length && view === 'grid'"
            class="pl-empty empty-state"
        >
            <span class="pl-empty-icon" aria-hidden="true">
                <i class="fa-regular fa-folder-open"></i>
            </span>
            <div class="pl-empty-title">No tasks found</div>
            <p class="pl-empty-text">
                Adjust the filters above or create a task to get started.
            </p>
        </div>

        <TaskDetailModal
            :detail-task="detailTask"
            :detail-form="detailForm"
            :detail-checklists="detailChecklists"
            v-model:new-checklist-name="newChecklistName"
            v-model:show-checklist-toggle="showChecklistToggle"
            :detail-checklist-completed="detailChecklistCompleted"
            :values="VALUES"
            :initials="initials"
            :is-overdue="isOverdue"
            :show-manage-assignees="true"
            :close-detail-modal="closeDetailModal"
            :open-assignee-picker="openAssigneePicker"
            :debounced-detail-save="debouncedDetailSave"
            :immediate-save="immediateSave"
            :add-checklist-item="addChecklistItem"
            :on-checklist-toggle="onChecklistToggle"
            :save-checklist-name="saveChecklistName"
            :remove-checklist-item="removeChecklistItem"
        />

        <TaskAssigneeModal
            :task="assigneePickerTask"
            :employees="employees"
            :initials="initials"
            :picker-saving="pickerSaving"
            :save-assignee-picker="saveAssigneePicker"
            @close="assigneePickerTask = null"
        />
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from "vue";
import Swal from "sweetalert2";
import { useStore } from "vuex";
import { usePlanner } from "./composables/usePlanner";
import TaskFiltersToolbar from "./components/TaskFiltersToolbar.vue";
import TaskTable from "./components/TaskTable.vue";
import TaskBoard from "./components/TaskBoard.vue";
import TaskDetailModal from "./components/TaskDetailModal.vue";
import TaskAssigneeModal from "./components/TaskAssigneeModal.vue";

const store = useStore();

const {
    tasks,
    employees,
    loading,
    saving,
    savingError,
    currentEid,
    VALUES,
    initials,
    isOverdue,
    loadTasks,
    loadEmployees,
    createTask,
    updateTask,
    deleteTask,
    saveAssignees,
    removeAssignee,
    addChecklist,
    updateChecklist,
    deleteChecklist,
    markRead,
    buildNewTask,
} = usePlanner();

const view = ref("grid");
const privacy = ref(null);
const statusFilter = ref("All");
const employeeSearch = ref("");
const showChecklistToggle = ref(true);

const detailTask = ref(null);
const detailForm = reactive({});
const detailChecklists = ref([]);
const newChecklistName = ref("");

const assigneePickerTask = ref(null);
const pickerSaving = ref(false);

const isAdmin = computed(() =>
    [1, 2].includes(Number(store.getters.getRoleId)),
);

onMounted(async () => {
    await Promise.all([loadTasks(buildParams()), loadEmployees()]);
});

function buildParams() {
    const params = { view: "assign" };
    if (privacy.value) params.privacy = privacy.value;
    if (statusFilter.value && statusFilter.value !== "All")
        params.status = statusFilter.value;
    if (employeeSearch.value) params.employee = employeeSearch.value;
    return params;
}

const filteredTasks = computed(() => {
    const q = employeeSearch.value.trim().toLowerCase();
    return tasks.value.filter((task) => {
        if (!q) return true;
        return task.assignees.some((a) =>
            (a.employee?.Full_Name || "").toLowerCase().includes(q),
        );
    });
});

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
    if (!task.id) {
        await createInlineTask(task);
        return;
    }
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

    if (!task.id) {
        await createInlineTask(task);
        return;
    }

    task.saving_state = "saving";
    try {
        await updateTask(task.id, { [field]: value });
        task.saving_state = "saved";
        setTimeout(() => (task.saving_state = ""), 1500);
        if (field === "status") {
            let wasOverdue = isOverdue(task);
            task._wasOverdue = wasOverdue;
            if (value === "Completed") {
                await markRead(task.id);
            }
        }
    } catch (e) {
        task.saving_state = "error";
    }
}

async function onStartDateChange(task, event) {
    task.start_date = event.target.value;
    if (!task.id) {
        await createInlineTask(task);
        return;
    }
    await onFieldChange(task, "start_date", {
        target: { value: task.start_date },
    });
}

async function onDueDateChange(task, event) {
    task.due_date = event.target.value;
    if (!task.id) {
        await createInlineTask(task);
        return;
    }
    await onFieldChange(task, "due_date", { target: { value: task.due_date } });
}

async function addNewTask() {
    const task = buildNewTask();
    tasks.value.unshift(task);
    try {
        const created = await persistNewTask(task);
        await nextTick();
    } catch (e) {
        tasks.value = tasks.value.filter((t) => t !== task);
    }
}

async function addInlineBelow(index) {
    const task = buildNewTask();
    const at = index + 1;
    tasks.value.splice(at, 0, task);
    try {
        await persistNewTask(task);
        await nextTick();
    } catch (e) {
        tasks.value = tasks.value.filter((t) => t !== task);
    }
}

async function persistNewTask(task) {
    const data = {
        task_name: task.task_name || "",
        start_date: task.start_date,
        due_date: task.due_date,
        status: task.status,
        repeat: task.repeat,
        bucket: task.bucket,
        priority: task.priority,
        privacy: task.privacy,
        notes: task.notes,
    };
    const created = await createTask(data);
    task.id = created.id;
    task.is_new = false;
    task.created_by = created.created_by;
    task.created_by_info = created.created_by_info;
    return created;
}

async function createInlineTask(task) {
    await persistNewTask(task);
    if (task.assignees.length) {
        await saveAssignees(
            task.id,
            task.assignees.map((a) => Number(a.assigned_to || a)),
        );
    }
}

function canDelete(task) {
    return (
        isAdmin.value || Number(task.created_by) === Number(currentEid.value)
    );
}

function confirmDelete(task) {
    Swal.fire({
        title: "Delete task?",
        text: `"${task.task_name || "Untitled task"}" will be permanently deleted.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete",
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await deleteTask(task.id);
                Swal.fire("Deleted", "Task has been removed.", "success");
            } catch (e) {
                Swal.fire("Error", "Could not delete task.", "error");
            }
        }
    });
}

function openAssigneePicker(task) {
    assigneePickerTask.value = task;
}

async function saveAssigneePicker() {
    const task = assigneePickerTask.value;
    if (!task) return;

    if (!task.id) {
        await persistNewTask(task);
    }

    const ids = task.assignees.map((a) =>
        Number(a.assigned_to || a.employee?.id),
    );
    pickerSaving.value = true;
    try {
        const updated = await saveAssignees(task.id, ids);
        task.assignees = updated.map((u) => ({
            id: u.id,
            assigned_to: u.assigned_to,
            employee: u.assignee,
            read_status: u.read_status,
            is_current_user: u.is_current_user,
        }));
        applyPickerUpdates(task);
        Swal.fire({
            toast: true,
            position: "top-end",
            icon: "success",
            title: "Assignees updated",
            showConfirmButton: false,
            timer: 1000,
        });
        assigneePickerTask.value = null;
    } catch (e) {
        Swal.fire("Error", "Could not update assignees.", "error");
    } finally {
        pickerSaving.value = false;
    }
}

function applyPickerUpdates(task) {
    if (!task.id) return;
    updateTask(task.id, { task_name: task.task_name }).catch(() => {});
    const idx = tasks.value.findIndex((t) => t.id === task.id);
    if (idx !== -1) tasks.value[idx] = { ...tasks.value[idx], ...task };
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
        tasks.value[idx].assignees = [...detailForm.assignees];
        const completed = detailChecklists.value.filter(
            (c) => c.completed_status,
        ).length;
        tasks.value[idx].checklist_progress = {
            completed,
            total: detailChecklists.value.length,
        };
    }
}

const detailChecklistCompleted = computed(
    () => detailChecklists.value.filter((c) => c.completed_status).length,
);

async function addChecklistItem() {
    const name = newChecklistName.value.trim();
    if (!name || !detailTask.value) return;
    if (!detailTask.value.id) return;

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
        detailChecklists.value = detailChecklists.value.filter(
            (c) => c.id !== cl.id,
        );
        syncDetailToTask();
    } catch (e) {
        Swal.fire("Error", "Could not remove checklist item.", "error");
    }
}

function debouncedDetailSave(field) {
    clearTimeout(detailForm._timer);
    detailForm._timer = setTimeout(async () => {
        if (!detailTask.value?.id) return;
        try {
            await updateTask(detailTask.value.id, {
                [field]: detailForm[field],
            });
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

watch(detailTask, (nt) => {
    if (nt) {
        nt.assignees = nt.assignees || [];
        nt.checklists = nt.checklists || [];
        nt.checklist_progress = nt.checklist_progress || {
            completed: 0,
            total: 0,
        };
    }
});
</script>

<style scoped>
@import "./styles/planner-theme.css";

/* --- Page canvas ---------------------------------------------------------- */
.planner-page {
    /* Full-bleed module background (compensates the layout container
       `.container-fluid.px-2.py-3` padding exactly: 6px x / 12px y) */
    margin: -12px -6px;
    padding: 14px 14px 18px;
    min-height: 100%;
    background-color: var(--pl-bg);
    color: var(--pl-text);
    font-family: inherit;
    font-size: 14px;
    line-height: 1.5;
}

/* --- Hero header ---------------------------------------------------------- */
.pl-hero {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px 20px;
    margin-bottom: 14px;
    padding: 14px 16px;
    background-color: var(--pl-surface);
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-lg);
    box-shadow: var(--pl-shadow-sm);
}

.pl-hero-main {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.pl-hero-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border: 1px solid var(--pl-accent-border);
    border-radius: var(--pl-radius);
    background-color: var(--pl-accent-soft);
    color: var(--pl-accent-text);
    font-size: 15px;
}

.pl-hero-text {
    min-width: 0;
}

.pl-hero-title {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: -0.02em;
    color: var(--pl-text);
}

.pl-hero-sub {
    margin: 2px 0 0;
    font-size: 12.5px;
    font-weight: 500;
    line-height: 1.35;
    color: var(--pl-muted);
}

/* --- Overview stats ------------------------------------------------------- */
.pl-hero-stats {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0;
    padding: 0;
}

.pl-hero-stat {
    display: flex;
    align-items: baseline;
    gap: 7px;
    padding: 5px 11px;
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-sm);
    background-color: var(--pl-surface-alt);
}

.pl-hero-stat dt {
    font-size: 11px;
    font-weight: 500;
    line-height: 1.3;
    color: var(--pl-muted);
    white-space: nowrap;
}

.pl-hero-stat dd {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.3;
    color: var(--pl-text);
    font-variant-numeric: tabular-nums;
}

.pl-hero-stat dd.is-progress {
    color: var(--pl-accent-text);
}

.pl-hero-stat dd.is-done {
    color: var(--pl-success-text);
}

.pl-hero-stat dd.is-overdue {
    color: var(--pl-danger-text);
}

/* --- Primary action ------------------------------------------------------- */
.pl-hero-action {
    gap: 7px;
    min-height: var(--pl-control-h);
    padding: 0 14px;
    margin-left: auto;
    font-weight: 600;
    letter-spacing: 0.005em;
}

.pl-hero-action > i {
    font-size: 11.5px;
}

.pl-hero-action:hover {
    box-shadow:
        0 4px 14px rgba(var(--pl-accent-rgb), 0.32),
        var(--pl-shadow-xs);
}

/* --- Inline error --------------------------------------------------------- */
.pl-alert {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 14px;
    padding: 10px 14px !important;
    border: 1px solid var(--pl-danger-border);
    border-radius: var(--pl-radius);
    background-color: var(--pl-danger-soft);
    color: var(--pl-danger-text) !important;
    font-size: 13px;
    box-shadow: none;
}

.pl-alert > i {
    margin-top: 1px;
    font-size: 12px;
}

/* --- Empty state ---------------------------------------------------------- */
.empty-state {
    margin-top: 4px;
    color: var(--pl-text);
}

.empty-state .pl-empty-icon {
    color: var(--pl-muted);
}

/* --- Loading skeletons (presentation only) -------------------------------- */
.pl-skeleton-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.sk-column {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 260px;
    padding: 12px;
    border-radius: var(--pl-radius-lg);
    background-color: var(--pl-surface-alt);
}

.sk-column-head {
    width: 46%;
    height: 16px;
}

.sk-cards {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.sk-card {
    display: flex;
    flex-direction: column;
    gap: 9px;
    padding: 12px;
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius);
    background-color: var(--pl-surface);
}

.sk-line {
    height: 10px;
}

.sk-line-title {
    width: 82%;
    height: 12px;
}

.sk-line-meta {
    width: 55%;
}

.sk-line-short {
    width: 34%;
}

@media (max-width: 1199.98px) {
    .pl-skeleton-grid {
        grid-template-columns: repeat(3, minmax(300px, 1fr));
        overflow-x: auto;
        padding-bottom: 10px;
    }
}

@media (max-width: 575.98px) {
    .pl-skeleton-grid {
        grid-template-columns: repeat(3, minmax(84vw, 1fr));
        gap: 10px;
    }
}

/* --- Responsive ----------------------------------------------------------- */
@media (max-width: 1199.98px) {
    .pl-hero-stats {
        order: 3;
        width: 100%;
    }
}

@media (max-width: 991.98px) {
    .pl-hero-action {
        margin-left: auto;
    }
}

@media (max-width: 767.98px) {
    .pl-hero {
        gap: 12px;
        padding: 13px 14px;
    }

    .pl-hero-stats {
        gap: 6px;
    }

    .pl-hero-stat {
        flex: 1 1 calc(50% - 3px);
        justify-content: space-between;
    }
}

@media (max-width: 575.98px) {
    .planner-page {
        margin: -12px -6px;
        padding: 12px 8px 16px;
    }

    .pl-hero-title {
        font-size: 18px;
    }

    .pl-hero-icon {
        width: 34px;
        height: 34px;
        font-size: 13.5px;
    }

    .pl-hero-action {
        flex: 1 1 100%;
        justify-content: center;
        margin-left: 0;
    }
}
</style>
