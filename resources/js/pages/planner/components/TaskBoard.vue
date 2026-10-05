<template>
    <div class="planner-board">
        <div class="row g-3">
            <div v-for="col in boardColumns" :key="col.status" class="col-md-4">
                <div class="board-column card h-100">
                    <div class="card-header py-2 d-flex align-items-center">
                        <span
                            class="dot me-2"
                            :class="col.dotClass"
                            aria-hidden="true"
                        ></span>
                        <strong class="small board-column-title">{{
                            col.status
                        }}</strong>
                        <span class="badge bg-light text-dark ms-2 pl-count">{{
                            boardTasks(col.status).length
                        }}</span>
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
                                    <div
                                        class="d-flex justify-content-between align-items-start"
                                    >
                                        <div
                                            class="fw-semibold small board-task-name"
                                        >
                                            {{
                                                task.task_name ||
                                                "Untitled task"
                                            }}
                                        </div>
                                        <span
                                            class="badge"
                                            :class="
                                                priorityBadge(task.priority)
                                            "
                                        >
                                            {{ task.priority }}
                                        </span>
                                    </div>
                                    <div
                                        class="small mt-1 pl-card-due"
                                        :class="{
                                            'text-danger fw-semibold':
                                                isOverdue(task),
                                        }"
                                    >
                                        <i
                                            class="fa-regular fa-calendar me-1"
                                            aria-hidden="true"
                                        ></i>
                                        <span v-if="isOverdue(task)">
                                            Overdue · {{ task.due_date }}
                                            <i
                                                class="fa-solid fa-triangle-exclamation ms-1"
                                                aria-hidden="true"
                                            ></i>
                                        </span>
                                        <span v-else>{{
                                            task.due_date || "No due date"
                                        }}</span>
                                    </div>

                                    <div
                                        v-if="showChecklistToggle"
                                        class="mt-2"
                                    >
                                        <div
                                            class="d-flex align-items-center small text-muted pl-checklist-head gap-2"
                                        >
                                            <i
                                                class="fa-regular fa-square-check me-1"
                                                aria-hidden="true"
                                            ></i>
                                            <button
                                                class="btn btn-link btn-sm p-0 checklist-toggle"
                                                type="button"
                                                @click="
                                                    toggleChecklistVisibility(
                                                        task,
                                                    )
                                                "
                                            >
                                                {{
                                                    task.checklist_progress
                                                        .completed
                                                }}/{{
                                                    task.checklist_progress
                                                        .total
                                                }}
                                                <i
                                                    class="fa-solid ms-1"
                                                    :class="
                                                        task._showChecklists
                                                            ? 'fa-chevron-up'
                                                            : 'fa-chevron-down'
                                                    "
                                                    aria-hidden="true"
                                                ></i>
                                            </button>
                                        </div>
                                        <div
                                            v-if="task.checklist_progress.total"
                                            class="pl-progress"
                                            role="presentation"
                                        >
                                            <span
                                                class="pl-progress-bar"
                                                :style="{
                                                    width:
                                                        Math.round(
                                                            (task
                                                                .checklist_progress
                                                                .completed /
                                                                task
                                                                    .checklist_progress
                                                                    .total) *
                                                                100,
                                                        ) + '%',
                                                }"
                                            ></span>
                                        </div>
                                        <div
                                            v-if="task._showChecklists"
                                            class="checklist-items mt-1"
                                        >
                                            <div
                                                v-for="cl in task.checklists"
                                                :key="cl.id"
                                                class="small d-flex align-items-center"
                                            >
                                                <i
                                                    class="fa-regular me-1"
                                                    :class="
                                                        cl.completed_status
                                                            ? 'fa-square-check text-success'
                                                            : 'fa-square text-muted'
                                                    "
                                                    aria-hidden="true"
                                                ></i>
                                                <span
                                                    :class="{
                                                        'text-decoration-line-through text-muted':
                                                            cl.completed_status,
                                                    }"
                                                >
                                                    {{ cl.checklist_name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="d-flex align-items-center mt-2 pl-card-foot"
                                    >
                                        <div
                                            class="assignee-avatars d-flex align-items-center flex-wrap"
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
                                        </div>
                                        <button
                                            v-if="mode === 'assign'"
                                            class="btn btn-sm btn-outline-secondary ms-auto board-assignee-btn"
                                            type="button"
                                            title="Assign employees"
                                            aria-label="Assign employees"
                                            @click.stop="
                                                openAssigneePicker(task)
                                            "
                                        >
                                            <i
                                                class="fa-solid fa-user-plus"
                                                aria-hidden="true"
                                            ></i>
                                        </button>
                                        <span
                                            v-else
                                            class="ms-2 small pl-read-state"
                                            :class="
                                                task.my_read_status
                                                    ? 'text-success'
                                                    : 'text-warning'
                                            "
                                            :title="
                                                task.my_read_status
                                                    ? 'You have read this'
                                                    : 'You have not read this'
                                            "
                                        >
                                            <i
                                                class="fa-solid"
                                                :class="
                                                    task.my_read_status
                                                        ? 'fa-check-circle'
                                                        : 'fa-circle'
                                                "
                                                aria-hidden="true"
                                            ></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="!boardTasks(col.status).length"
                                class="board-empty text-center text-muted small py-4"
                            >
                                <i
                                    class="fa-regular fa-inbox d-block mb-2"
                                    aria-hidden="true"
                                ></i>
                                No tasks
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- <div v-if="mode === 'assign'" class="d-flex justify-content-end mt-3">
            <button
                class="btn btn-primary btn-sm pl-add-task"
                type="button"
                @click="addNewTask"
            >
                <i class="fa-solid fa-plus" aria-hidden="true"></i>Add new task
            </button>
        </div> -->
    </div>
</template>

<script setup>
const props = defineProps({
    mode: { type: String, required: true },
    tasks: { type: Array, required: true },
    initials: { type: Function, required: true },
    isOverdue: { type: Function, required: true },
    showChecklistToggle: { type: Boolean, default: true },
    openDetailModal: { type: Function, required: true },
    openAssigneePicker: { type: Function, default: null },
    addNewTask: { type: Function, default: null },
});

const boardColumns = [
    { status: "Not started", dotClass: "bg-secondary" },
    { status: "In progress", dotClass: "bg-warning" },
    { status: "Completed", dotClass: "bg-success" },
];

function boardTasks(status) {
    return props.tasks.filter((t) => t.status === status);
}

function priorityBadge(priority) {
    if (priority === "Urgent") return "bg-danger";
    if (priority === "Important") return "bg-warning text-dark";
    if (priority === "Low") return "bg-secondary";
    return "bg-primary";
}

function toggleChecklistVisibility(task) {
    task._showChecklists = !task._showChecklists;
}
</script>

<style scoped>
@import "./../styles/planner-theme.css";

/* --- Board surface -------------------------------------------------------- */
.planner-board .board-column {
    background-color: var(--pl-surface);
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-lg);
    box-shadow: var(--pl-shadow-sm);
    overflow: hidden;
}

.planner-board .board-column .card-header {
    background-color: var(--pl-surface);
    border-bottom: 1px solid var(--pl-border);
    padding: 10px 14px;
}

.board-column-title {
    font-size: 0.8125rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: var(--pl-text);
}

.pl-count {
    min-width: 22px;
    justify-content: center;
    padding: 0 0.45em;
}

/* --- Status dot ----------------------------------------------------------- */
.dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.dot.bg-secondary {
    background-color: #cbd5e1 !important;
}

.dot.bg-warning {
    background-color: #f59e0b !important;
}

.dot.bg-success {
    background-color: #10b981 !important;
}

/* --- Column body ---------------------------------------------------------- */
.planner-board .board-column .card-body {
    background-color: var(--pl-bg) !important;
    border-top: 0;
    padding: 12px;
}

.planner-board .board-cards {
    display: flex;
    flex-direction: column;
}

.board-empty {
    color: var(--pl-muted);
    line-height: 1.5;
}

.board-empty > i {
    font-size: 1.1rem;
    opacity: 0.7;
}

/* --- Task card ------------------------------------------------------------ */
.planner-board .board-card {
    background-color: var(--pl-surface);
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius);
    box-shadow: var(--pl-shadow-xs);
    cursor: pointer;
    transition: var(--pl-ease);
}

.planner-board .board-card:hover {
    transform: translateY(-1px);
    border-color: var(--pl-border-strong);
    box-shadow: var(--pl-shadow-md);
}

.planner-board .board-card:active {
    transform: translateY(0);
    box-shadow: var(--pl-shadow-sm);
}

.planner-board .board-card > .card-body {
    padding: 12px;
}

.board-task-name {
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.4;
    color: var(--pl-text);
    word-break: break-word;
}

/* --- Due / overdue -------------------------------------------------------- */
.pl-card-due {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border: 1px solid transparent;
    border-radius: 999px;
    background-color: transparent;
    font-size: 0.75rem;
    line-height: 1.5;
    color: var(--pl-muted);
}

.pl-card-due > i:first-child {
    font-size: 0.7rem;
}

.pl-card-due.text-danger {
    background-color: var(--pl-danger-soft);
    border-color: var(--pl-danger-border);
    color: var(--pl-danger-text) !important;
    font-weight: 600;
}

/* --- Checklist ------------------------------------------------------------ */
.pl-checklist-head {
    gap: 0;
    margin-left: 9px;
    font-size: 0.75rem;
}

.planner-board .checklist-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.25em;
    padding: 1px 6px;
    margin-left: -6px;
    border: 1px solid transparent;
    border-radius: var(--pl-radius-xs);
    color: var(--pl-muted);
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
    transition: var(--pl-ease);
}

.planner-board .checklist-toggle:hover {
    background-color: var(--pl-surface-alt);
    border-color: var(--pl-border);
    color: var(--pl-text);
    text-decoration: none;
}

.planner-board .checklist-toggle > i {
    font-size: 0.6rem;
}

.pl-progress {
    position: relative;
    height: 4px;
    margin-top: 6px;
    border-radius: 999px;
    background-color: var(--pl-surface-alt);
    overflow: hidden;
}

.pl-progress-bar {
    display: block;
    height: 100%;
    border-radius: 999px;
    background-color: var(--pl-accent);
    transition: var(--pl-ease);
}

.planner-board .checklist-items {
    padding-left: 4px;
    border-left: 2px solid var(--pl-border);
    margin-top: 6px;
}

.planner-board .checklist-items > div {
    padding: 2px 0;
    font-size: 0.75rem;
    line-height: 1.45;
    color: var(--pl-text-soft);
}

.planner-board .checklist-items > div > i {
    font-size: 0.7rem;
}

/* --- Card footer ---------------------------------------------------------- */
.pl-card-foot {
    padding-top: 10px;
    border-top: 1px solid var(--pl-border);
}

.planner-board .board-assignee-btn {
    width: 28px;
    min-height: 28px;
    padding: 0;
    font-size: 0.7rem;
    flex-shrink: 0;
}

.planner-board .pl-read-state {
    font-size: 0.75rem;
}

/* --- Add task ------------------------------------------------------------- */
.pl-add-task {
    min-height: 34px;
    padding: 0 0.85rem;
}

/* --- Responsive ----------------------------------------------------------- */
@media (max-width: 1199.98px) {
    .planner-board .board-column .card-body {
        padding: 10px;
    }

    .planner-board .board-card > .card-body {
        padding: 10px;
    }
}

@media (max-width: 575.98px) {
    .planner-board .board-column .card-header {
        padding: 9px 12px;
    }
}
</style>
