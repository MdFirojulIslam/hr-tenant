<template>
    <div
        class="planner-grid card"
        :class="{ 'assign-mode': mode === 'assign' }"
    >
        <div class="card-body p-0">
            <div class="table-responsive">
                <table
                    class="table table-hover planner-table align-middle mb-0"
                >
                    <thead>
                        <tr>
                            <th style="width: 34px">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    aria-label="Select task"
                                />
                            </th>
                            <th style="min-width: 250px">Task Name</th>
                            <th style="min-width: 120px">Assign To</th>
                            <th style="min-width: 130px">Start Date</th>
                            <th style="min-width: 130px">Due Date</th>
                            <th>Bucket</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Privacy</th>
                            <th
                                :style="
                                    mode === 'assign'
                                        ? 'min-width: 200px'
                                        : 'min-width: 150px'
                                "
                            >
                                Notes
                            </th>
                            <th style="width: 40px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template
                            v-for="(task, index) in tasks"
                            :key="task.id ?? `new-${index}`"
                        >
                            <tr
                                class="planner-row"
                                :class="{
                                    'planner-new-row': task.is_new,
                                    'text-muted': !task.id,
                                }"
                                @mouseenter="
                                    hoverRow = task.id ?? `new-${index}`
                                "
                                @mouseleave="hoverRow = null"
                            >
                                <td class="position-relative">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        :aria-label="`Select ${
                                            task.task_name || 'Untitled task'
                                        }`"
                                    />
                                    <button
                                        v-if="
                                            mode === 'assign' &&
                                            hoverRow ===
                                                (task.id ?? `new-${index}`)
                                        "
                                        class="btn btn-sm btn-light btn-add-inline position-absolute"
                                        type="button"
                                        title="Add task below"
                                        aria-label="Add task below"
                                        @click="addInlineBelow(index)"
                                    >
                                        <i
                                            class="fa-solid fa-plus"
                                            aria-hidden="true"
                                        ></i>
                                    </button>
                                </td>
                                <td>
                                    <div
                                        v-if="mode === 'my'"
                                        class="d-flex align-items-center gap-2"
                                    >
                                        <div class="flex-grow-1">
                                            <textarea
                                                class="form-control form-control-sm planner-inline-input"
                                                rows="1"
                                                :value="task.task_name"
                                                placeholder="Task name"
                                                @blur="onNameBlur(task)"
                                                @input="
                                                    onNameInput(task, $event)
                                                "
                                            ></textarea>
                                        </div>
                                        <span
                                            v-if="task.my_read_status"
                                            class="pl-read-state pl-is-read"
                                            title="Read"
                                        >
                                            <i
                                                class="fa-solid fa-check-circle"
                                                aria-hidden="true"
                                            ></i>
                                        </span>
                                        <span
                                            v-else
                                            class="pl-read-state pl-is-unread"
                                            title="Unread"
                                        >
                                            <i
                                                class="fa-solid fa-circle"
                                                aria-hidden="true"
                                            ></i>
                                        </span>
                                    </div>
                                    <textarea
                                        v-else
                                        class="form-control form-control-sm planner-inline-input"
                                        rows="1"
                                        :value="task.task_name"
                                        placeholder="Task name"
                                        @blur="onNameBlur(task)"
                                        @input="
                                            onNameInput(task, $event);
                                            autoResize($event);
                                        "
                                    ></textarea>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="assignee-avatars d-flex align-items-center"
                                        >
                                            <span
                                                v-for="(a, i) in task.assignees"
                                                :key="a.id ?? i"
                                                class="avatar-chip"
                                                :title="
                                                    a.employee?.Full_Name ||
                                                    'Assigned'
                                                "
                                            >
                                                {{
                                                    initials(
                                                        a.employee?.Full_Name,
                                                    )
                                                }}
                                            </span>
                                            <span
                                                v-if="!task.assignees.length"
                                                class="pl-empty-cell"
                                                >—</span
                                            >
                                        </div>
                                        <button
                                            v-if="mode === 'assign'"
                                            class="btn btn-sm btn-outline-secondary ms-1 assignee-toggle"
                                            type="button"
                                            title="Assign employees"
                                            aria-label="Assign employees"
                                            @click="openAssigneePicker(task)"
                                        >
                                            <i
                                                class="fa-solid fa-user-plus"
                                                aria-hidden="true"
                                            ></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <input
                                        type="date"
                                        class="form-control form-control-sm"
                                        :value="task.start_date"
                                        @change="
                                            onStartDateChange(task, $event)
                                        "
                                    />
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <input
                                            type="date"
                                            class="form-control form-control-sm"
                                            :class="{
                                                'due-overdue': isOverdue(task),
                                            }"
                                            :value="task.due_date"
                                            @change="
                                                onDueDateChange(task, $event)
                                            "
                                        />
                                        <span
                                            v-if="isOverdue(task)"
                                            class="badge bg-danger ms-1 pl-overdue-tag"
                                            title="Overdue"
                                            aria-label="Overdue"
                                        >
                                            <i
                                                class="fa-solid fa-triangle-exclamation"
                                                aria-hidden="true"
                                            ></i>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <select
                                        class="form-select form-select-sm"
                                        :value="task.bucket"
                                        @change="
                                            onFieldChange(
                                                task,
                                                'bucket',
                                                $event,
                                            )
                                        "
                                    >
                                        <option
                                            v-for="b in values.buckets"
                                            :key="b"
                                            :value="b"
                                        >
                                            {{ b }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <!-- <span
                                        class="badge"
                                        :class="statusBadge(task.status)"
                                    >
                                        {{ task.status }}
                                    </span> -->
                                    <select
                                        class="form-select form-select-sm"
                                        :class="statusClass(task.status)"
                                        :value="task.status"
                                        @change="
                                            onFieldChange(
                                                task,
                                                'status',
                                                $event,
                                            )
                                        "
                                    >
                                        <option
                                            v-for="s in values.statuses"
                                            :key="s"
                                            :value="s"
                                        >
                                            {{ s }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <select
                                        class="form-select form-select-sm"
                                        :class="priorityClass(task.priority)"
                                        :value="task.priority"
                                        @change="
                                            onFieldChange(
                                                task,
                                                'priority',
                                                $event,
                                            )
                                        "
                                    >
                                        <option
                                            v-for="p in values.priorities"
                                            :key="p"
                                            :value="p"
                                        >
                                            {{ p }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <select
                                        v-if="mode === 'assign'"
                                        class="form-select form-select-sm"
                                        :value="task.privacy"
                                        @change="
                                            onFieldChange(
                                                task,
                                                'privacy',
                                                $event,
                                            )
                                        "
                                    >
                                        <option
                                            v-for="pr in values.privacies"
                                            :key="pr"
                                            :value="pr"
                                        >
                                            {{ pr }}
                                        </option>
                                    </select>
                                    <span
                                        v-else
                                        class="badge bg-light text-dark"
                                        >{{ task.privacy }}</span
                                    >
                                </td>
                                <td>
                                    <textarea
                                        class="form-control form-control-sm planner-inline-input"
                                        rows="1"
                                        :value="task.notes"
                                        placeholder="Add notes..."
                                        @blur="onNotesBlur(task)"
                                        @input="
                                            onNotesInput(task, $event);
                                            autoResize($event);
                                        "
                                    ></textarea>
                                </td>
                                <td>
                                    <div
                                        v-if="mode === 'assign'"
                                        class="d-flex align-items-center gap-1 pl-row-actions"
                                    >
                                        <button
                                            class="btn btn-sm btn-light text-primary"
                                            type="button"
                                            title="Open detail"
                                            aria-label="Open task details"
                                            @click="openDetailModal(task)"
                                        >
                                            <i
                                                class="fa-solid fa-eye"
                                                aria-hidden="true"
                                            ></i>
                                        </button>
                                        <button
                                            v-if="task.id && canDelete(task)"
                                            class="btn btn-sm btn-light text-danger"
                                            type="button"
                                            title="Delete task"
                                            aria-label="Delete task"
                                            @click="confirmDelete(task)"
                                        >
                                            <i
                                                class="fa-solid fa-trash"
                                                aria-hidden="true"
                                            ></i>
                                        </button>
                                    </div>
                                    <button
                                        v-else
                                        class="btn btn-sm btn-light text-primary"
                                        type="button"
                                        title="Open detail"
                                        aria-label="Open task details"
                                        @click="openDetailModal(task)"
                                    >
                                        <i
                                            class="fa-solid fa-eye"
                                            aria-hidden="true"
                                        ></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="false"></tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div v-if="mode === 'assign'" class="add-task-bottom">
                <button
                    class="btn btn-link text-primary"
                    type="button"
                    @click="addNewTask"
                >
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>Add new
                    task
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, watch } from "vue";

const props = defineProps({
    mode: { type: String, required: true },
    tasks: { type: Array, required: true },
    values: { type: Object, required: true },
    initials: { type: Function, required: true },
    isOverdue: { type: Function, required: true },
    onNameInput: { type: Function, required: true },
    onNameBlur: { type: Function, required: true },
    onNotesInput: { type: Function, required: true },
    onNotesBlur: { type: Function, required: true },
    onFieldChange: { type: Function, required: true },
    onStartDateChange: { type: Function, required: true },
    onDueDateChange: { type: Function, required: true },
    openDetailModal: { type: Function, required: true },
    canDelete: { type: Function, default: null },
    addInlineBelow: { type: Function, default: null },
    addNewTask: { type: Function, default: null },
    confirmDelete: { type: Function, default: null },
    openAssigneePicker: { type: Function, default: null },
});

const hoverRow = ref(null);

function statusBadge(status) {
    if (status === "Completed") return "bg-success";
    if (status === "In progress") return "bg-warning text-dark";
    return "bg-secondary";
}

function statusClass(status) {
    if (status === "Completed") {
        return "status-completed";
    }

    if (status === "In progress") {
        return "status-in-progress";
    }

    return "";
}

function priorityClass(priority) {
    if (priority === "Urgent") return "border-danger text-danger";
    if (priority === "Important") return "border-warning text-warning";
    return "";
}

function autoResizeTextarea(textarea) {
    if (!textarea) return;

    textarea.style.height = "auto";
    textarea.style.height = `${textarea.scrollHeight}px`;
}

function autoResize(event) {
    autoResizeTextarea(event.target);
}

watch(
    () => props.tasks,
    async () => {
        await nextTick();

        const textareas = document.querySelectorAll(".planner-inline-input");

        textareas.forEach((textarea) => {
            autoResizeTextarea(textarea);
        });
    },
    { deep: true, immediate: true },
);
</script>

<style scoped>
@import "./../styles/planner-theme.css";

/* --- Card surface --------------------------------------------------------- */
.planner-grid {
    overflow: hidden;
    background-color: var(--pl-surface);
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-lg);
    box-shadow: var(--pl-shadow-sm);
}

.planner-grid .table-responsive {
    border-radius: 0;
}

/* --- Table ---------------------------------------------------------------- */
.planner-table {
    --bs-table-bg: transparent;
    --bs-table-hover-bg: transparent;
    --bs-table-color: var(--pl-text);
    --bs-table-border-color: var(--pl-border);
    --bs-table-accent-bg: transparent;
    margin-bottom: 0;
    color: var(--pl-text);
    font-size: 13px;
}

.planner-table thead tr th {
    position: relative;
    z-index: 2;
    padding: 9px 12px;
    background-color: var(--pl-surface-alt);
    border-bottom: 1px solid var(--pl-border);
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.3;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    white-space: nowrap;
    color: var(--pl-muted);
}

.planner-table thead tr th:first-child {
    box-shadow: inset 1px 0 0 var(--pl-border);
}

.planner-table tbody tr td {
    padding: 8px 12px;
    background-color: transparent;
    border-bottom: 1px solid var(--pl-border);
    vertical-align: middle;
    color: var(--pl-text-soft);
    /* the app shell sets `* { font-size: 12px }`, which beats inheritance
       from `.planner-table` — so the size has to be declared on the cells */
    font-size: 13px;
}

.planner-table tbody tr:last-child td {
    border-bottom: 0;
}

.planner-table tbody tr:hover > td {
    background-color: var(--pl-hover);
}

.planner-table tbody tr:hover > td:first-child {
    box-shadow: inset 1px 0 0 var(--pl-border);
}

.planner-table thead tr th:first-child,
.planner-table tbody tr td:first-child {
    padding-left: 10px;
    padding-right: 4px;
}

/* Unsaved / brand new row — soft, calm hint (never an error state) */
.planner-table tbody tr.planner-new-row > td {
    background-color: rgba(var(--pl-accent-rgb), 0.06);
}

.planner-table tbody tr.planner-new-row:hover > td {
    background-color: rgba(var(--pl-accent-rgb), 0.11);
}

.planner-table tbody tr.planner-new-row > td:first-child {
    box-shadow: inset 3px 0 0 var(--pl-accent);
}

/* --- Cell controls -------------------------------------------------------- */
.planner-grid .form-control,
.planner-grid .form-select {
    min-height: 32px;
    padding-top: 4px;
    padding-bottom: 4px;
    background-color: var(--pl-surface-alt);
    border-color: var(--pl-border);
    color: var(--pl-text);
    font-size: 12.5px;
}

.planner-grid .form-control:hover,
.planner-grid .form-select:hover {
    border-color: var(--pl-border-strong);
}

.planner-grid .form-control:focus,
.planner-grid .form-select:focus {
    border-color: var(--pl-accent);
    background-color: var(--pl-surface);
    box-shadow: var(--pl-ring);
}

.pl-empty-cell {
    color: var(--pl-faint);
    font-size: 13px;
}

/* --- Inline editable fields ----------------------------------------------- */
/* .planner-inline-input {
    min-height: 32px;
    padding: 4px 8px;
    resize: none;
    background-color: transparent;
    background-clip: padding-box;
    border: 1px solid transparent;
    border-radius: var(--pl-radius-sm);
    color: var(--pl-text);
    font-size: 13px;
    font-weight: 500;
    line-height: 1.45;
    box-shadow: none;
    transition: var(--pl-ease);
} */

.planner-inline-input {
    min-height: 32px;
    height: auto;
    padding: 6px 8px;
    resize: none;
    overflow: hidden;

    background-color: transparent;
    background-clip: padding-box;
    border: 1px solid transparent;
    border-radius: var(--pl-radius-sm);
    color: var(--pl-text);
    font-size: 13px;
    font-weight: 500;
    line-height: 1.45;
    box-shadow: none;
    transition: var(--pl-ease);
}

.planner-inline-input:hover {
    background-color: var(--pl-surface);
    border-color: var(--pl-border);
}

.planner-inline-input:focus {
    outline: none;
    background-color: var(--pl-surface);
    border-color: var(--pl-accent);
    color: var(--pl-text);
    box-shadow: var(--pl-ring);
}

.planner-inline-input::placeholder {
    color: var(--pl-faint);
    font-weight: 400;
}

/* --- Read / unread indicator --------------------------------------------- */
.pl-read-state {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    flex-shrink: 0;
    font-size: 12px;
    line-height: 1;
}

.pl-read-state.pl-is-read {
    color: var(--pl-success-text) !important;
}

.pl-read-state.pl-is-unread {
    color: var(--pl-warning-text) !important;
}

/* --- Overdue -------------------------------------------------------------- */
.pl-overdue-tag {
    align-self: center;
    flex-shrink: 0;
    padding: 0 7.2px;
}

/* --- Status / priority cells --------------------------------------------- */
.assign-mode .status-select {
    display: block;
    min-height: 28px;
    padding-top: 2px;
    padding-bottom: 2px;
    font-size: 11.5px;
}

.planner-table .form-select.border-danger {
    border-color: var(--pl-danger-border) !important;
    background-color: var(--pl-danger-soft) !important;
    color: var(--pl-danger-text) !important;
    font-weight: 500;
}

.planner-table .form-select.border-danger:focus {
    border-color: var(--pl-danger-text) !important;
    box-shadow: var(--pl-ring-danger);
}

.planner-table .form-select.border-warning {
    border-color: var(--pl-warning-border) !important;
    background-color: var(--pl-warning-soft) !important;
    color: var(--pl-warning-text) !important;
    font-weight: 500;
}

.planner-table .form-select.border-warning:focus {
    border-color: var(--pl-warning-text) !important;
    box-shadow: var(--pl-ring);
}

/* --- Assignee cell -------------------------------------------------------- */
.assignee-avatars .avatar-chip + .avatar-chip {
    margin-left: -7px;
}

.assignee-toggle {
    width: 30px;
    min-height: 30px;
    flex-shrink: 0;
    padding: 0;
}

/* --- Row actions ---------------------------------------------------------- */
.pl-row-actions {
    justify-content: flex-end;
}

.planner-table td:last-child .btn {
    width: 30px;
    min-height: 30px;
    flex-shrink: 0;
    padding: 0;
}

.planner-table td:last-child .btn.text-primary {
    --bs-btn-color: var(--pl-text-soft);
    --bs-btn-hover-color: var(--pl-accent-text);
    --bs-btn-active-color: var(--pl-accent-text);
    --bs-btn-hover-bg: var(--pl-accent-soft);
    --bs-btn-hover-border-color: var(--pl-accent-border);
    --bs-btn-active-bg: var(--pl-accent-soft);
    --bs-btn-active-border-color: var(--pl-accent-border);
    /* `.text-primary` is !important in Bootstrap, so these must be too */
    color: var(--pl-text-soft) !important;
}

.planner-table td:last-child .btn.text-primary:hover,
.planner-table td:last-child .btn.text-primary:active {
    color: var(--pl-accent-text) !important;
}

.planner-table td:last-child .btn.text-danger {
    --bs-btn-color: var(--pl-muted);
    --bs-btn-hover-color: var(--pl-danger-text);
    --bs-btn-active-color: var(--pl-danger-text);
    --bs-btn-hover-bg: var(--pl-danger-soft);
    --bs-btn-hover-border-color: var(--pl-danger-border);
    --bs-btn-active-bg: var(--pl-danger-soft);
    --bs-btn-active-border-color: var(--pl-danger-border);
    color: var(--pl-muted) !important;
}

.planner-table td:last-child .btn.text-danger:hover,
.planner-table td:last-child .btn.text-danger:active {
    color: var(--pl-danger-text) !important;
}

.planner-table td:last-child .btn:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

.planner-table td:last-child .btn.text-danger:focus-visible {
    box-shadow: var(--pl-ring-danger);
}

/* --- Inline "+" affordance ------------------------------------------------ */
.assign-mode .btn-add-inline {
    position: absolute;
    /* top: 50%; */
    top: 100%;
    left: 30px;
    z-index: 5;
    display: none;
    width: 12px;
    height: 10px;
    min-height: 10px;
    padding: 0;
    transform: translateY(-50%);
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-xs);
    background-color: var(--pl-surface);
    color: var(--pl-text-soft);
    font-size: 8.8px;
    box-shadow: var(--pl-shadow-sm);
}

.assign-mode .planner-row:hover .btn-add-inline,
.assign-mode .btn-add-inline {
    display: inline-flex;
}

.assign-mode .btn-add-inline:hover {
    background-color: var(--pl-accent-soft);
    border-color: var(--pl-accent-border);
    color: var(--pl-accent-text);
}

.assign-mode .btn-add-inline:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

/* --- Add new task (bottom) ------------------------------------------------ */
.assign-mode .add-task-bottom {
    background-color: var(--pl-surface);
    border-top: 1px dashed var(--pl-border);
}

.add-task-bottom .btn {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 0.45em;
    width: 100%;
    padding: 10px 16px;
    border-radius: 0;
    color: var(--pl-accent-text) !important;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: var(--pl-ease);
}

.add-task-bottom .btn > i {
    font-size: 11.2px;
}

.add-task-bottom .btn:hover,
.add-task-bottom .btn:focus-visible {
    background-color: var(--pl-hover);
    color: var(--pl-accent-hover) !important;
    text-decoration: none;
}

.add-task-bottom .btn:focus-visible {
    outline: none;
    box-shadow: inset 0 0 0 2px var(--pl-accent-border);
}

.planner-table .form-select.status-completed {
    border-color: var(--pl-success-border) !important;
    background-color: var(--pl-success-soft) !important;
    color: var(--pl-success-text) !important;
    font-weight: 500;
}

.planner-table .form-select.status-completed:focus {
    border-color: var(--pl-success-text) !important;
    box-shadow: var(--pl-ring) !important;
}

.planner-table .form-select.status-in-progress {
    border-color: var(--pl-warning-border) !important;
    background-color: var(--pl-warning-soft) !important;
    color: var(--pl-warning-text) !important;
    font-weight: 500;
}

.planner-table .form-select.status-in-progress:focus {
    border-color: var(--pl-warning-text) !important;
    box-shadow: var(--pl-ring) !important;
}

/* --- Responsive ----------------------------------------------------------- */
@media (max-width: 767.98px) {
    .planner-table thead tr th,
    .planner-table tbody tr td {
        padding: 8px 10px;
    }

    .planner-grid .form-control,
    .planner-grid .form-select {
        min-height: 34px;
        font-size: 13px;
    }
}
</style>
