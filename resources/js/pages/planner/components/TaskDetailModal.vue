<template>
    <div
        v-if="detailTask"
        class="modal fade show d-block task-detail-modal"
        tabindex="-1"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header py-2">
                    <h5 class="modal-title small fw-semibold">
                        <i
                            class="fa-solid fa-list-check me-2 text-primary"
                        ></i>
                        {{ detailForm.task_name || "Untitled task" }}
                    </h5>
                    <button
                        class="btn-close"
                        type="button"
                        aria-label="Close"
                        @click="closeDetailModal"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold"
                                >Task Name</label
                            >
                            <input
                                v-model="detailForm.task_name"
                                type="text"
                                class="form-control form-control-sm"
                                placeholder="Enter task name"
                                @input="debouncedDetailSave('task_name')"
                            />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold"
                                >Assignees</label
                            >
                            <div
                                class="assignee-avatars d-flex flex-wrap align-items-center mb-2"
                            >
                                <span
                                    v-for="(a, i) in detailForm.assignees"
                                    :key="a.id ?? i"
                                    class="avatar-chip"
                                    :title="a.employee?.Full_Name"
                                >
                                    {{ initials(a.employee?.Full_Name) }}
                                </span>
                                <span
                                    v-if="!detailForm.assignees.length"
                                    class="text-muted small"
                                    >None</span
                                >
                            </div>
                            <button
                                v-if="showManageAssignees"
                                class="btn btn-outline-primary btn-sm"
                                type="button"
                                @click="openAssigneePicker(detailTask)"
                            >
                                <i class="fa-solid fa-user-plus me-1"></i
                                >Manage Assignees
                            </button>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold"
                                >Start Date</label
                            >
                            <input
                                v-model="detailForm.start_date"
                                type="date"
                                class="form-control form-control-sm"
                                @change="immediateSave('start_date')"
                            />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold"
                                >Due Date</label
                            >
                            <input
                                v-model="detailForm.due_date"
                                type="date"
                                class="form-control form-control-sm"
                                :class="{
                                    'due-overdue-input': isOverdue(detailForm),
                                }"
                                @change="immediateSave('due_date')"
                            />
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold"
                                >Status</label
                            >
                            <select
                                v-model="detailForm.status"
                                class="form-select form-select-sm"
                                @change="immediateSave('status')"
                            >
                                <option
                                    v-for="s in values.statuses"
                                    :key="s"
                                    :value="s"
                                >
                                    {{ s }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold"
                                >Repeat</label
                            >
                            <select
                                v-model="detailForm.repeat"
                                class="form-select form-select-sm"
                                @change="immediateSave('repeat')"
                            >
                                <option
                                    v-for="r in values.repeats"
                                    :key="r"
                                    :value="r"
                                >
                                    {{ r }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold"
                                >Bucket</label
                            >
                            <select
                                v-model="detailForm.bucket"
                                class="form-select form-select-sm"
                                @change="immediateSave('bucket')"
                            >
                                <option
                                    v-for="b in values.buckets"
                                    :key="b"
                                    :value="b"
                                >
                                    {{ b }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold"
                                >Priority</label
                            >
                            <select
                                v-model="detailForm.priority"
                                class="form-select form-select-sm"
                                @change="immediateSave('priority')"
                            >
                                <option
                                    v-for="p in values.priorities"
                                    :key="p"
                                    :value="p"
                                >
                                    {{ p }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold"
                                >Privacy</label
                            >
                            <select
                                v-model="detailForm.privacy"
                                class="form-select form-select-sm"
                                @change="immediateSave('privacy')"
                            >
                                <option
                                    v-for="pr in values.privacies"
                                    :key="pr"
                                    :value="pr"
                                >
                                    {{ pr }}
                                </option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold"
                                >Notes</label
                            >
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
                                <div
                                    class="d-flex align-items-center justify-content-between mb-2"
                                >
                                    <strong class="small">
                                        Checklists ({{
                                            detailChecklistCompleted
                                        }}
                                        of {{ detailChecklists.length }})
                                    </strong>
                                    <div
                                        class="d-flex align-items-center gap-2"
                                    >
                                        <div
                                            class="form-check form-switch mb-0"
                                        >
                                            <input
                                                id="showChecklistToggle"
                                                class="form-check-input"
                                                type="checkbox"
                                                :checked="showChecklistToggle"
                                                @change="
                                                    $emit(
                                                        'update:showChecklistToggle',
                                                        $event.target.checked,
                                                    )
                                                "
                                            />
                                            <label
                                                class="form-check-label small"
                                                for="showChecklistToggle"
                                            >
                                                Show on card
                                            </label>
                                        </div>
                                        <div
                                            class="input-group input-group-sm"
                                            style="width: auto"
                                        >
                                            <input
                                                :value="newChecklistName"
                                                type="text"
                                                class="form-control"
                                                placeholder="New checklist item"
                                                @input="
                                                    $emit(
                                                        'update:newChecklistName',
                                                        $event.target.value,
                                                    )
                                                "
                                                @keyup.enter="
                                                    addChecklistItem
                                                "
                                            />
                                            <button
                                                class="btn btn-outline-primary"
                                                type="button"
                                                @click="addChecklistItem"
                                            >
                                                <i
                                                    class="fa-solid fa-plus"
                                                ></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="!detailChecklists.length"
                                    class="text-muted small py-2"
                                >
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
                                        @change="
                                            onChecklistToggle(cl, $event)
                                        "
                                    />
                                    <input
                                        class="form-control form-control-sm flex-grow-1 checklist-name-input"
                                        :class="{
                                            'text-decoration-line-through':
                                                cl.completed_status,
                                        }"
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
                            <span class="fw-semibold ms-1">{{
                                detailTask.created_by_info?.Full_Name || "—"
                            }}</span>
                            <span class="mx-2">·</span>
                            <span
                                v-if="detailTask.my_read_status"
                                class="text-success"
                            >
                                <i class="fa-solid fa-check-circle me-1"></i
                                >Read
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" @click="closeDetailModal"></div>
    </div>
</template>

<script setup>
defineProps({
    detailTask: { type: Object, default: null },
    detailForm: { type: Object, required: true },
    detailChecklists: { type: Array, required: true },
    newChecklistName: { type: String, default: "" },
    showChecklistToggle: { type: Boolean, default: true },
    detailChecklistCompleted: { type: Number, default: 0 },
    values: { type: Object, required: true },
    initials: { type: Function, required: true },
    isOverdue: { type: Function, required: true },
    showManageAssignees: { type: Boolean, default: false },
    closeDetailModal: { type: Function, required: true },
    openAssigneePicker: { type: Function, default: null },
    debouncedDetailSave: { type: Function, required: true },
    immediateSave: { type: Function, required: true },
    addChecklistItem: { type: Function, required: true },
    onChecklistToggle: { type: Function, required: true },
    saveChecklistName: { type: Function, required: true },
    removeChecklistItem: { type: Function, required: true },
});

defineEmits(["update:newChecklistName", "update:showChecklistToggle"]);
</script>

<style scoped>
.task-detail-modal {
    z-index: 1050;
}

.task-detail-modal .modal-backdrop {
    z-index: -1;
    background-color: rgba(0, 0, 0, 0.5);
    position: fixed;
    inset: 0;
}

.task-detail-modal .modal-dialog {
    z-index: 1056;
    position: relative;
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

.due-overdue-input {
    border-color: #dc3545;
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
</style>