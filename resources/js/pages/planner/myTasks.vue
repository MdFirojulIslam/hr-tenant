<template>
    <div class="planner-page my-tasks">
        <!-- ===================== HEADER ===================== -->
        <header class="pl-hero">
            <div class="pl-hero-main">
                <span class="pl-hero-icon" aria-hidden="true">
                    <i class="fa-solid fa-user-check"></i>
                </span>
                <div class="pl-hero-text">
                    <h1 class="pl-hero-title">My Tasks</h1>
                    <p class="pl-hero-sub">Tasks assigned to you</p>
                </div>
            </div>

            <dl class="pl-hero-stats" aria-label="My tasks overview">
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
            mode="my"
            :tasks="filteredTasks"
            :values="VALUES"
            :initials="initials"
            :is-overdue="isOverdue"
            :on-name-input="onNameInput"
            :on-name-blur="onNameBlur"
            :on-notes-input="onNotesInput"
            :on-notes-blur="onNotesBlur"
            :on-field-change="onFieldChange"
            :on-start-date-change="onStartDateChange"
            :on-due-date-change="onDueDateChange"
            :open-detail-modal="openDetailModal"
        />

        <TaskBoard
            v-else
            mode="my"
            :tasks="tasks"
            :initials="initials"
            :is-overdue="isOverdue"
            :show-checklist-toggle="showChecklistToggle"
            :open-detail-modal="openDetailModal"
        />

        <div
            v-if="!loading && !filteredTasks.length && view === 'grid'"
            class="pl-empty empty-state"
        >
            <span class="pl-empty-icon" aria-hidden="true">
                <i class="fa-regular fa-folder-open"></i>
            </span>
            <div class="pl-empty-title">No tasks assigned to you</div>
            <p class="pl-empty-text">
                Adjust the filters above or check back once work is assigned to
                you.
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
            :show-manage-assignees="false"
            :close-detail-modal="closeDetailModal"
            :debounced-detail-save="debouncedDetailSave"
            :immediate-save="immediateSave"
            :add-checklist-item="addChecklistItem"
            :on-checklist-toggle="onChecklistToggle"
            :save-checklist-name="saveChecklistName"
            :remove-checklist-item="removeChecklistItem"
        />
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from "vue";
import Swal from "sweetalert2";
import { usePlanner } from "./composables/usePlanner";
import TaskFiltersToolbar from "./components/TaskFiltersToolbar.vue";
import TaskTable from "./components/TaskTable.vue";
import TaskBoard from "./components/TaskBoard.vue";
import TaskDetailModal from "./components/TaskDetailModal.vue";

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
    await onFieldChange(task, "start_date", {
        target: { value: task.start_date },
    });
}

async function onDueDateChange(task, event) {
    task.due_date = event.target.value;
    await onFieldChange(task, "due_date", { target: { value: task.due_date } });
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
    padding: 0 0 0 auto;
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
        padding-left: 0;
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
}
</style>
