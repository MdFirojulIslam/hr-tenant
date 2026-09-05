<template>
    <div class="planner-board">
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
                        <span class="badge bg-light text-dark ms-2">{{
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
                                            style="font-size: 0.65rem"
                                        >
                                            {{ task.priority }}
                                        </span>
                                    </div>
                                    <div
                                        class="small mt-1"
                                        :class="{
                                            'text-danger fw-semibold':
                                                isOverdue(task),
                                        }"
                                    >
                                        <i
                                            class="fa-regular fa-calendar me-1"
                                        ></i>
                                        <span v-if="isOverdue(task)">
                                            Overdue · {{ task.due_date }}
                                            <i
                                                class="fa-solid fa-triangle-exclamation ms-1"
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
                                            class="d-flex align-items-center small text-muted"
                                        >
                                            <i
                                                class="fa-regular fa-square-check me-1"
                                            ></i>
                                            <button
                                                class="btn btn-link btn-sm p-0"
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
                                                ></i>
                                            </button>
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
                                        class="d-flex align-items-center mt-2"
                                    >
                                        <div
                                            class="assignee-avatars d-flex align-items-center flex-wrap"
                                        >
                                            <span
                                                v-for="(
                                                    a, i
                                                ) in task.assignees"
                                                :key="a.id ?? i"
                                                class="avatar-chip"
                                                :title="
                                                    a.employee?.Full_Name ||
                                                    'Assigned'
                                                "
                                            >
                                                {{
                                                    initials(
                                                        a.employee
                                                            ?.Full_Name,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <button
                                            v-if="mode === 'assign'"
                                            class="btn btn-sm btn-outline-secondary ms-auto board-assignee-btn"
                                            type="button"
                                            title="Assign employees"
                                            @click.stop="
                                                openAssigneePicker(task)
                                            "
                                        >
                                            <i class="fa-solid fa-user"></i>
                                        </button>
                                        <span
                                            v-else
                                            class="ms-2 small"
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
                                            ></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="!boardTasks(col.status).length"
                                class="text-center text-muted small py-4"
                            >
                                No tasks
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="mode === 'assign'"
            class="d-flex justify-content-end mt-3"
        >
            <button
                class="btn btn-primary btn-sm"
                type="button"
                @click="addNewTask"
            >
                <i class="fa-solid fa-plus me-1"></i>Add new task
            </button>
        </div>
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
.planner-board .board-card {
    cursor: pointer;
    transition:
        transform 0.12s ease,
        box-shadow 0.12s ease;
}

.planner-board .board-card:hover {
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

.planner-board .board-assignee-btn {
    font-size: 0.7rem;
}

.planner-board .checklist-items {
    padding-left: 2px;
}
</style>