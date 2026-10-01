<template>
    <div
        v-if="task"
        class="modal fade show d-block assignee-modal"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="plannerAssigneeTitle"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header">
                    <div class="assignee-header-content">
                        <span class="assignee-header-icon" aria-hidden="true">
                            <i class="fa-solid fa-user-plus"></i>
                        </span>

                        <div class="assignee-header-text">
                            <h5 id="plannerAssigneeTitle" class="modal-title">
                                Assign Employees
                            </h5>

                            <p class="modal-subtitle">
                                Select employees to assign to this task
                            </p>
                        </div>
                    </div>

                    <button
                        class="pl-close"
                        type="button"
                        aria-label="Close"
                        @click="$emit('close')"
                    >
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <!-- Search -->
                    <div class="search-wrapper">
                        <i
                            class="fa-solid fa-magnifying-glass search-icon"
                            aria-hidden="true"
                        ></i>

                        <label
                            class="visually-hidden"
                            for="plannerAssigneeSearch"
                        >
                            Search employees
                        </label>

                        <input
                            id="plannerAssigneeSearch"
                            v-model="assigneeSearch"
                            type="text"
                            class="form-control assignee-search"
                            placeholder="Search by name or employee ID..."
                            autocomplete="off"
                        />

                        <button
                            v-if="assigneeSearch"
                            type="button"
                            class="search-clear"
                            aria-label="Clear search"
                            @click="assigneeSearch = ''"
                        >
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>

                    <!-- Selection summary -->
                    <div class="selection-summary">
                        <div class="selection-info">
                            <span class="selection-icon">
                                <i
                                    class="fa-solid fa-user-check"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <div>
                                <span class="selection-label">
                                    Selected employees
                                </span>

                                <span class="selection-count">
                                    {{ selectedCount }}
                                </span>
                            </div>
                        </div>

                        <button
                            v-if="selectedCount > 0 && assigneeSearch"
                            type="button"
                            class="show-selected-btn"
                            @click="assigneeSearch = ''"
                        >
                            Show all
                        </button>
                    </div>

                    <!-- Employee list -->
                    <div class="assignee-list">
                        <!-- Employee rows -->
                        <button
                            v-for="emp in filteredEmployees"
                            :key="emp.id"
                            type="button"
                            class="assignee-option"
                            :class="{ 'is-assigned': isAssigned(emp.id) }"
                            :aria-pressed="isAssigned(emp.id)"
                            :aria-label="`${
                                isAssigned(emp.id) ? 'Remove' : 'Assign'
                            } ${emp.Full_Name}`"
                            @click="toggleAssignment(emp)"
                        >
                            <div class="employee-main">
                                <span class="avatar-chip">
                                    {{ initials(emp.Full_Name) }}
                                </span>

                                <div class="employee-info">
                                    <div class="employee-name">
                                        {{ emp.Full_Name }}
                                    </div>

                                    <div class="employee-id">
                                        <i
                                            class="fa-regular fa-id-badge"
                                            aria-hidden="true"
                                        ></i>

                                        {{ emp.Employee_Id }}
                                    </div>
                                </div>
                            </div>

                            <span
                                class="assignment-control"
                                :class="
                                    isAssigned(emp.id)
                                        ? 'assigned'
                                        : 'available'
                                "
                                :title="
                                    isAssigned(emp.id)
                                        ? 'Remove ' + emp.Full_Name
                                        : 'Assign ' + emp.Full_Name
                                "
                            >
                                <i
                                    class="fa-solid"
                                    :class="
                                        isAssigned(emp.id)
                                            ? 'fa-check'
                                            : 'fa-plus'
                                    "
                                    aria-hidden="true"
                                ></i>
                            </span>
                        </button>

                        <!-- Empty -->
                        <div
                            v-if="!filteredEmployees.length"
                            class="assignee-empty"
                        >
                            <span class="pl-empty-icon" aria-hidden="true">
                                <i class="fa-solid fa-user-slash"></i>
                            </span>

                            <div class="pl-empty-title">No employees found</div>

                            <div class="pl-empty-text">
                                Try searching with a different name or employee
                                ID.
                            </div>

                            <button
                                v-if="assigneeSearch"
                                type="button"
                                class="empty-clear"
                                @click="assigneeSearch = ''"
                            >
                                Clear search
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <div class="footer-selection">
                        <span class="footer-dot"></span>

                        <span>
                            {{ selectedCount }}
                            {{ selectedCount === 1 ? "employee" : "employees" }}
                            selected
                        </span>
                    </div>

                    <div class="footer-actions">
                        <button
                            class="btn btn-sm btn-outline-secondary"
                            type="button"
                            :disabled="pickerSaving"
                            @click="$emit('close')"
                        >
                            Cancel
                        </button>

                        <button
                            class="btn btn-sm btn-primary pl-assign-submit"
                            type="button"
                            :disabled="pickerSaving"
                            @click="saveAssigneePicker"
                        >
                            <span
                                v-if="pickerSaving"
                                class="spinner-border spinner-border-sm"
                                aria-hidden="true"
                            ></span>

                            <i
                                v-else
                                class="fa-solid fa-check"
                                aria-hidden="true"
                            ></i>

                            <span>
                                {{ pickerSaving ? "Saving..." : "Assign" }}
                            </span>
                        </button>
                    </div>
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

    if (!q) {
        return props.employees;
    }

    return props.employees.filter((employee) => {
        const name = String(employee.Full_Name || "").toLowerCase();
        const employeeId = String(employee.Employee_Id || "").toLowerCase();

        return name.includes(q) || employeeId.includes(q);
    });
});

const selectedCount = computed(() => {
    if (!props.task?.assignees) {
        return 0;
    }

    return props.task.assignees.length;
});

function isAssigned(empId) {
    const task = props.task;

    if (!task || !Array.isArray(task.assignees)) {
        return false;
    }

    return task.assignees.some(
        (assignee) =>
            Number(assignee.assigned_to || assignee.employee?.id) ===
            Number(empId),
    );
}

function toggleAssignment(emp) {
    const task = props.task;

    if (!task) {
        return;
    }

    if (!Array.isArray(task.assignees)) {
        task.assignees = [];
    }

    const idx = task.assignees.findIndex(
        (assignee) =>
            Number(assignee.assigned_to || assignee.employee?.id) ===
            Number(emp.id),
    );

    if (idx !== -1) {
        task.assignees.splice(idx, 1);
        return;
    }

    task.assignees.push({
        id: null,
        assigned_to: emp.id,
        employee: emp,
        read_status: false,
    });
}
</script>

<style scoped>
@import "./../styles/planner-theme.css";

/* ==========================================================================
   Modal
   ========================================================================== */

.assignee-modal {
    z-index: 1050;
}

.assignee-modal .modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: -1;
    background-color: rgba(2, 4, 10, 0.72);
    -webkit-backdrop-filter: blur(5px);
    backdrop-filter: blur(5px);
}

.assignee-modal .modal-dialog {
    width: calc(100% - 32px);
    max-width: 500px;
    margin: 24px auto;
}

.assignee-modal .modal-content {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--pl-border-strong);
    border-radius: 16px;
    background: var(--pl-surface);
    box-shadow: var(--pl-shadow-lg);
    animation: assignee-modal-in 160ms ease-out both;
}

@keyframes assignee-modal-in {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.985);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* ==========================================================================
   Header
   ========================================================================== */

.assignee-modal .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--pl-border);
    background: var(--pl-surface);
}

.assignee-header-content {
    display: flex;
    align-items: center;
    min-width: 0;
    gap: 12px;
}

.assignee-header-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    border: 1px solid var(--pl-accent-border);
    border-radius: var(--pl-radius);
    background: var(--pl-accent-soft);
    color: var(--pl-accent-text);
    font-size: 14px;
}

.assignee-header-text {
    min-width: 0;
}

.assignee-modal .modal-title {
    margin: 0;
    color: var(--pl-text);
    font-size: 15px;
    font-weight: 600;
    line-height: 1.35;
    letter-spacing: -0.005em;
}

.modal-subtitle {
    margin: 2px 0 0;
    color: var(--pl-muted);
    font-size: 12px;
    line-height: 1.4;
}

.assignee-modal .pl-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    flex: 0 0 auto;
    padding: 0;
    border: 1px solid transparent;
    border-radius: var(--pl-radius-sm);
    background: transparent;
    color: var(--pl-muted);
    font-size: 15px;
    line-height: 1;
    cursor: pointer;
    transition:
        background-color 140ms ease,
        border-color 140ms ease,
        color 140ms ease;
}

.assignee-modal .pl-close:hover {
    border-color: var(--pl-border);
    background: var(--pl-hover);
    color: var(--pl-text);
}

.assignee-modal .pl-close:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

/* ==========================================================================
   Body
   ========================================================================== */

.assignee-modal .modal-body {
    padding: 14px 18px;
    background: var(--pl-surface);
}

/* ==========================================================================
   Search
   ========================================================================== */

.search-wrapper {
    position: relative;
    margin-bottom: 12px;
}

.search-icon {
    position: absolute;
    top: 50%;
    left: 13px;
    z-index: 2;
    color: var(--pl-faint);
    font-size: 12px;
    transform: translateY(-50%);
    pointer-events: none;
    transition: color 140ms ease;
}

.search-wrapper:focus-within .search-icon {
    color: var(--pl-accent);
}

.assignee-search {
    width: 100%;
    min-height: 38px;
    padding: 0 38px 0 36px;
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-sm);
    background: var(--pl-surface-alt);
    color: var(--pl-text);
    font-size: 13px;
    transition:
        background-color 140ms ease,
        border-color 140ms ease,
        box-shadow 140ms ease;
}

.assignee-search:hover {
    border-color: var(--pl-border-strong);
}

.assignee-search:focus {
    border-color: var(--pl-accent);
    background: var(--pl-surface);
    box-shadow: var(--pl-ring);
}

.search-clear {
    position: absolute;
    top: 50%;
    right: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    padding: 0;
    border: 0;
    border-radius: var(--pl-radius-xs);
    background: transparent;
    color: var(--pl-muted);
    font-size: 10.4px;
    transform: translateY(-50%);
    cursor: pointer;
    transition:
        background-color 140ms ease,
        color 140ms ease;
}

.search-clear:hover {
    background: var(--pl-surface);
    color: var(--pl-text);
}

.search-clear:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

/* ==========================================================================
   Selection Summary
   ========================================================================== */

.selection-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 38px;
    margin-bottom: 10px;
    padding: 6px 10px;
    border: 1px solid var(--pl-accent-border);
    border-radius: var(--pl-radius-sm);
    background: var(--pl-accent-soft);
}

.selection-info {
    display: flex;
    align-items: center;
    gap: 9px;
}

.selection-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    flex: 0 0 24px;
    border-radius: var(--pl-radius-xs);
    background: var(--pl-accent);
    color: #fff;
    font-size: 10px;
}

.selection-info > div {
    display: flex;
    align-items: center;
    gap: 6px;
}

.selection-label {
    color: var(--pl-text-soft);
    font-size: 11.5px;
    font-weight: 500;
}

.selection-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: var(--pl-accent);
    color: #fff;
    font-size: 10.4px;
    font-weight: 700;
}

.show-selected-btn {
    padding: 3px 8px;
    border: 1px solid var(--pl-accent-border);
    border-radius: var(--pl-radius-xs);
    background: var(--pl-surface);
    color: var(--pl-accent-text);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition:
        background-color 140ms ease,
        color 140ms ease;
}

.show-selected-btn:hover {
    background: var(--pl-accent);
    border-color: var(--pl-accent);
    color: #fff;
}

.show-selected-btn:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

/* ==========================================================================
   Employee List
   ========================================================================== */

.assignee-list {
    max-height: 320px;
    overflow-y: auto;
    overscroll-behavior: contain;
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius);
    background: var(--pl-surface-alt);
    scrollbar-width: thin;
}

.assignee-list::-webkit-scrollbar {
    width: 6px;
}

.assignee-list::-webkit-scrollbar-track {
    background: transparent;
}

.assignee-list::-webkit-scrollbar-thumb {
    border-radius: 10px;
    background: var(--pl-border-strong);
}

/* ==========================================================================
   Employee Row
   ========================================================================== */

.assignee-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    min-height: 56px;
    gap: 12px;
    padding: 8px 11px;
    border: 0;
    border-bottom: 1px solid var(--pl-border);
    border-radius: 0;
    outline: none;
    background: transparent;
    color: inherit;
    text-align: left;
    cursor: pointer;
    transition:
        background-color 140ms ease,
        box-shadow 140ms ease;
}

.assignee-option:last-child {
    border-bottom: 0;
}

.assignee-option:hover {
    background: var(--pl-hover);
}

.assignee-option:focus-visible {
    position: relative;
    z-index: 1;
    box-shadow: inset 0 0 0 2px var(--pl-accent);
}

.assignee-option.is-assigned {
    background: var(--pl-accent-soft);
    box-shadow: inset 3px 0 0 var(--pl-accent);
}

.assignee-option.is-assigned:hover {
    background: var(--pl-accent-soft);
}

.employee-main {
    display: flex;
    align-items: center;
    min-width: 0;
    gap: 10px;
}

.avatar-chip {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    flex: 0 0 32px;
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-sm);
    background: var(--pl-surface);
    color: var(--pl-text-soft);
    font-size: 10.2px;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.assignee-option.is-assigned .avatar-chip {
    border-color: var(--pl-accent-border);
    background: var(--pl-accent);
    color: #fff;
}

.employee-info {
    min-width: 0;
}

.employee-name {
    overflow: hidden;
    margin-bottom: 2px;
    color: var(--pl-text);
    font-size: 12.8px;
    font-weight: 600;
    line-height: 1.3;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.employee-id {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--pl-muted);
    font-size: 10.9px;
    line-height: 1.3;
}

.employee-id i {
    font-size: 9.6px;
}

/* ==========================================================================
   Assignment Button
   ========================================================================== */

.assignment-control {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    flex: 0 0 28px;
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-xs);
    font-size: 10.9px;
    transition:
        background-color 140ms ease,
        border-color 140ms ease,
        color 140ms ease;
}

.assignment-control.available {
    background: var(--pl-surface);
    color: var(--pl-accent-text);
}

.assignee-option:hover .assignment-control.available {
    border-color: var(--pl-accent);
    background: var(--pl-accent);
    color: #fff;
}

.assignment-control.assigned {
    border-color: var(--pl-accent);
    background: var(--pl-accent);
    color: #fff;
}

/* ==========================================================================
   Empty State
   ========================================================================== */

.assignee-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 190px;
    padding: 30px 20px;
    text-align: center;
}

.assignee-empty .pl-empty-icon {
    background: var(--pl-surface);
    color: var(--pl-muted);
}

.empty-clear {
    margin-top: 12px;
    padding: 5px 10px;
    border: 1px solid var(--pl-accent-border);
    border-radius: var(--pl-radius-xs);
    background: var(--pl-accent-soft);
    color: var(--pl-accent-text);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition:
        background-color 140ms ease,
        color 140ms ease;
}

.empty-clear:hover {
    background: var(--pl-accent);
    border-color: var(--pl-accent);
    color: #fff;
}

.empty-clear:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

/* ==========================================================================
   Footer
   ========================================================================== */

.assignee-modal .modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 18px;
    border-top: 1px solid var(--pl-border);
    background: var(--pl-surface-alt);
}

.footer-selection {
    display: flex;
    align-items: center;
    min-width: 0;
    gap: 7px;
    color: var(--pl-muted);
    font-size: 11px;
}

.footer-dot {
    width: 6px;
    height: 6px;
    flex: 0 0 6px;
    border-radius: 50%;
    background: var(--pl-accent);
    box-shadow: 0 0 0 3px rgba(var(--pl-accent-rgb), 0.18);
}

.footer-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.assignee-modal .modal-footer .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    gap: 6px;
    padding: 0 14px;
    border-radius: var(--pl-radius-sm);
    font-size: 12px;
    font-weight: 600;
}

.assignee-modal .modal-footer .btn:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

.pl-assign-submit {
    min-width: 96px;
}

.pl-assign-submit .spinner-border {
    width: 12px;
    height: 12px;
    border-width: 2px;
}

/* ==========================================================================
   Responsive
   ========================================================================== */

@media (max-width: 575.98px) {
    .assignee-modal .modal-dialog {
        width: calc(100% - 20px);
        margin: 10px auto;
    }

    .assignee-modal .modal-content {
        border-radius: 14px;
    }

    .assignee-modal .modal-header {
        padding: 14px 15px;
    }

    .assignee-modal .modal-body {
        padding: 13px 15px;
    }

    .assignee-modal .modal-footer {
        padding: 11px 15px;
    }

    .assignee-list {
        max-height: 55vh;
    }

    .footer-selection {
        display: none;
    }

    .footer-actions {
        width: 100%;
    }

    .footer-actions .btn {
        flex: 1;
    }
}

@media (max-width: 360px) {
    .assignee-header-icon {
        width: 32px;
        height: 32px;
        flex-basis: 32px;
    }

    .modal-subtitle {
        display: none;
    }

    .assignee-option {
        padding: 8px;
    }

    .avatar-chip {
        width: 30px;
        height: 30px;
        flex-basis: 30px;
    }
}
</style>
