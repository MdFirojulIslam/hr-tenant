<template>
    <div class="planner-grid card" :class="{ 'assign-mode': mode === 'assign' }">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table
                    class="table table-hover planner-table align-middle mb-0"
                >
                    <thead>
                        <tr class="table-light">
                            <th style="width: 30px">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                />
                            </th>
                            <th style="min-width: 200px">Task Name</th>
                            <th style="min-width: 120px">Assign To</th>
                            <th style="min-width: 110px">Start Date</th>
                            <th style="min-width: 110px">Due Date</th>
                            <th>Bucket</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Privacy</th>
                            <th
                                :style="
                                    mode === 'assign'
                                        ? 'min-width: 180px'
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
                                    />
                                    <button
                                        v-if="
                                            mode === 'assign' &&
                                            hoverRow ===
                                                (task.id ?? `new-${index}`)
                                        "
                                        class="btn btn-sm btn-light btn-add-inline position-absolute"
                                        style="
                                            left: 24px;
                                            top: 50%;
                                            transform: translateY(-50%);
                                        "
                                        type="button"
                                        title="Add task below"
                                        @click="addInlineBelow(index)"
                                    >
                                        <i class="fa-solid fa-plus"></i>
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
                                            class="text-success small"
                                            title="Read"
                                        >
                                            <i
                                                class="fa-solid fa-check-circle"
                                            ></i>
                                        </span>
                                        <span
                                            v-else
                                            class="text-warning small"
                                            title="Unread"
                                        >
                                            <i
                                                class="fa-solid fa-circle"
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
                                        @input="onNameInput(task, $event)"
                                    ></textarea>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="assignee-avatars d-flex align-items-center"
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
                                            <span
                                                v-if="
                                                    !task.assignees.length
                                                "
                                                class="text-muted small"
                                                >—</span
                                            >
                                        </div>
                                        <button
                                            v-if="mode === 'assign'"
                                            class="btn btn-sm btn-outline-secondary ms-1 assignee-toggle"
                                            type="button"
                                            title="Assign employees"
                                            @click="
                                                openAssigneePicker(task)
                                            "
                                        >
                                            <i
                                                class="fa-solid fa-user"
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
                                                'due-overdue':
                                                    isOverdue(task),
                                            }"
                                            :value="task.due_date"
                                            @change="
                                                onDueDateChange(task, $event)
                                            "
                                        />
                                        <span
                                            v-if="isOverdue(task)"
                                            class="badge bg-danger ms-1"
                                            title="Overdue"
                                        >
                                            <i
                                                class="fa-solid fa-triangle-exclamation"
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
                                    <span
                                        class="badge"
                                        :class="statusBadge(task.status)"
                                    >
                                        {{ task.status }}
                                    </span>
                                    <select
                                        class="form-select form-select-sm status-select mt-1"
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
                                        :class="
                                            priorityClass(task.priority)
                                        "
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
                                        @input="onNotesInput(task, $event)"
                                    ></textarea>
                                </td>
                                <td>
                                    <div
                                        v-if="mode === 'assign'"
                                        class="d-flex flex-column gap-1"
                                    >
                                        <button
                                            class="btn btn-sm btn-light text-primary"
                                            type="button"
                                            title="Open detail"
                                            @click="openDetailModal(task)"
                                        >
                                            <i
                                                class="fa-solid fa-eye"
                                            ></i>
                                        </button>
                                        <button
                                            v-if="
                                                task.id && canDelete(task)
                                            "
                                            class="btn btn-sm btn-light text-danger"
                                            type="button"
                                            title="Delete task"
                                            @click="confirmDelete(task)"
                                        >
                                            <i
                                                class="fa-solid fa-trash"
                                            ></i>
                                        </button>
                                    </div>
                                    <button
                                        v-else
                                        class="btn btn-sm btn-light text-primary"
                                        type="button"
                                        title="Open detail"
                                        @click="openDetailModal(task)"
                                    >
                                        <i class="fa-solid fa-eye"></i>
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
                    class="btn btn-link text-primary py-3"
                    type="button"
                    @click="addNewTask"
                >
                    <i class="fa-solid fa-plus me-1"></i>Add new task
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";

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

function priorityClass(priority) {
    if (priority === "Urgent") return "border-danger text-danger";
    if (priority === "Important") return "border-warning text-warning";
    return "";
}
</script>

<style scoped>
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

.assign-mode .planner-table td {
    vertical-align: middle;
}

.assign-mode .planner-row:hover {
    background-color: #f8f9fa;
}

.assign-mode .planner-row:hover .planner-inline-input {
    background-color: #fff;
}

.assign-mode .planner-new-row {
    background-color: #fffbe8;
}

.assign-mode .btn-add-inline {
    display: none;
    z-index: 5;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.assign-mode .planner-row:hover .btn-add-inline,
.assign-mode .planner-row:hover .btn-add-inline {
    display: inline-flex;
}

.assign-mode .add-task-bottom .btn-link {
    font-weight: 500;
}

.assign-mode .status-select {
    display: block;
}
</style>