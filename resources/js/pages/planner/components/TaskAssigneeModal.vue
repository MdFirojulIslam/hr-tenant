<template>
    <div v-if="task" class="modal fade show d-block assignee-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header py-2">
                    <h5 class="modal-title small fw-semibold">
                        Assign Employees
                    </h5>
                    <button
                        class="btn-close"
                        type="button"
                        @click="$emit('close')"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <input
                            v-model="assigneeSearch"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Search employees..."
                        />
                    </div>
                    <div
                        class="assignee-list"
                        style="max-height: 280px; overflow-y: auto"
                    >
                        <div
                            v-for="emp in filteredEmployees"
                            :key="emp.id"
                            class="assignee-option d-flex align-items-center justify-content-between py-2 px-2 rounded"
                            :class="{ 'bg-light': isAssigned(emp.id) }"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-chip">{{
                                    initials(emp.Full_Name)
                                }}</span>
                                <div>
                                    <div class="small fw-semibold">
                                        {{ emp.Full_Name }}
                                    </div>
                                    <div class="small text-muted">
                                        {{ emp.Employee_Id }}
                                    </div>
                                </div>
                            </div>
                            <button
                                class="btn btn-sm"
                                :class="
                                    isAssigned(emp.id)
                                        ? 'btn-outline-danger'
                                        : 'btn-outline-primary'
                                "
                                type="button"
                                @click="toggleAssignment(emp)"
                            >
                                <i
                                    class="fa-solid"
                                    :class="
                                        isAssigned(emp.id)
                                            ? 'fa-minus'
                                            : 'fa-plus'
                                    "
                                ></i>
                            </button>
                        </div>
                        <div
                            v-if="!filteredEmployees.length"
                            class="text-center text-muted small py-3"
                        >
                            No employees found
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button
                        class="btn btn-sm btn-secondary"
                        type="button"
                        @click="$emit('close')"
                    >
                        Close
                    </button>
                    <button
                        class="btn btn-sm btn-primary"
                        type="button"
                        :disabled="pickerSaving"
                        @click="saveAssigneePicker"
                    >
                        <span
                            v-if="pickerSaving"
                            class="spinner-border spinner-border-sm me-1"
                        ></span>
                        Assign
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    task: { type: Object, default: null },
    employees: { type: Array, required: true },
    initials: { type: Function, required: true },
    pickerSaving: { type: Boolean, default: false },
    saveAssigneePicker: { type: Function, required: true },
});

defineEmits(["close"]);

const assigneeSearch = ref("");

const filteredEmployees = computed(() => {
    const q = assigneeSearch.value.trim().toLowerCase();
    if (!q) return props.employees;
    return props.employees.filter(
        (e) =>
            e.Full_Name.toLowerCase().includes(q) ||
            String(e.Employee_Id || "").toLowerCase().includes(q),
    );
});

function isAssigned(empId) {
    const task = props.task;
    if (!task) return false;
    return task.assignees.some(
        (a) => Number(a.assigned_to || a.employee?.id) === Number(empId),
    );
}

function toggleAssignment(emp) {
    const task = props.task;
    if (!task) return;
    const idx = task.assignees.findIndex(
        (a) => Number(a.assigned_to || a.employee?.id) === Number(emp.id),
    );
    if (idx !== -1) {
        task.assignees.splice(idx, 1);
    } else {
        task.assignees.push({
            id: null,
            assigned_to: emp.id,
            employee: emp,
            read_status: false,
        });
    }
}
</script>

<style scoped>
.assignee-modal {
    z-index: 1050;
}

.assignee-modal .modal-backdrop {
    z-index: -1;
    background-color: rgba(0, 0, 0, 0.5);
    position: fixed;
    inset: 0;
}

.assignee-option:hover {
    background-color: #f1f3f5;
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
</style>