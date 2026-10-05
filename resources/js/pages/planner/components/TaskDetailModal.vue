<template>
    <div
        v-if="detailTask"
        class="modal fade show d-block task-detail-modal"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="plannerTaskDetailTitle"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <h2 id="plannerTaskDetailTitle" class="visually-hidden">
                    Task details &mdash;
                    {{ detailForm.task_name || "Untitled task" }}
                </h2>

                <!-- ================= HEADER ================= -->
                <div class="modal-header pl-dt-head">
                    <div class="pl-dt-head-main">
                        <span class="pl-plan-name">Task</span>

                        <div class="pl-title-row">
                            <textarea
                                id="plannerDetailTaskName"
                                v-model="detailForm.task_name"
                                class="pl-title-input"
                                rows="1"
                                placeholder="Enter task name"
                                aria-label="Task name"
                                @input="debouncedDetailSave('task_name')"
                            ></textarea>
                        </div>

                        <p class="pl-meta-line mt-3">
                            <i
                                class="fa-regular fa-circle-info"
                                aria-hidden="true"
                            ></i>
                            <span>
                                Created by
                                <strong>{{
                                    detailTask.created_by_info?.Full_Name || "—"
                                }}</strong>
                            </span>
                            <span class="pl-meta-dot" aria-hidden="true">
                                &middot;
                            </span>
                            <span
                                v-if="detailTask.my_read_status"
                                class="pl-meta-read"
                            >
                                <i
                                    class="fa-solid fa-check"
                                    aria-hidden="true"
                                ></i>
                                Read
                            </span>
                            <span v-else class="pl-meta-unread"> Unread </span>
                        </p>

                        <div class="pl-chip-row mt-3">
                            <!-- <span class="pl-label-pill">
                                <i
                                    class="fa-solid fa-tag"
                                    aria-hidden="true"
                                ></i>
                                Add label
                            </span>

                            <span class="pl-chip-sep" aria-hidden="true"></span> -->

                            <span class="pl-assignee-box">
                                <span class="pl-assignee-faces">
                                    <template
                                        v-if="detailForm.assignees.length"
                                    >
                                        <span
                                            v-for="(
                                                a, i
                                            ) in detailForm.assignees.slice(
                                                0,
                                                4,
                                            )"
                                            :key="a.id ?? i"
                                            class="avatar-chip"
                                            :title="a.employee?.Full_Name"
                                        >
                                            {{
                                                initials(a.employee?.Full_Name)
                                            }}
                                        </span>
                                        <span
                                            v-if="
                                                detailForm.assignees.length > 4
                                            "
                                            class="avatar-overflow pl-dt-overflow"
                                            :title="`+${
                                                detailForm.assignees.length - 4
                                            } more`"
                                        >
                                            +{{
                                                detailForm.assignees.length - 4
                                            }}
                                        </span>
                                    </template>
                                </span>
                                <span class="pl-assignee-names">
                                    {{ assigneeLabel }}
                                </span>
                            </span>

                            <button
                                v-if="showManageAssignees"
                                class="btn btn-outline-primary btn-sm pl-manage-assignees"
                                type="button"
                                @click="openAssigneePicker(detailTask)"
                            >
                                <i
                                    class="fa-solid fa-user-plus"
                                    aria-hidden="true"
                                ></i>
                                Manage
                            </button>
                        </div>
                    </div>

                    <button
                        class="pl-close"
                        type="button"
                        aria-label="Close task details"
                        @click="closeDetailModal"
                    >
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- ================= BODY ================= -->
                <div class="modal-body pl-dt-body">
                    <!-- ---------- Task details panel ---------- -->
                    <div id="plPanelDetails" role="tabpanel" tabindex="0">
                        <div class="pl-prop-grid">
                            <!-- Status -->
                            <div class="pl-prop">
                                <label
                                    class="pl-prop-label"
                                    for="plannerDetailStatus"
                                    >Status</label
                                >
                                <div class="pl-control">
                                    <i
                                        class="fa-regular fa-circle pl-control-icon"
                                        aria-hidden="true"
                                    ></i>
                                    <select
                                        id="plannerDetailStatus"
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
                            </div>

                            <!-- Priority -->
                            <div class="pl-prop">
                                <label
                                    class="pl-prop-label"
                                    for="plannerDetailPriority"
                                    >Priority</label
                                >
                                <div class="pl-control">
                                    <span
                                        class="pl-control-dot"
                                        aria-hidden="true"
                                    ></span>
                                    <select
                                        id="plannerDetailPriority"
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
                            </div>

                            <!-- Start date -->
                            <div class="pl-prop">
                                <label
                                    class="pl-prop-label"
                                    for="plannerDetailStartDate"
                                    >Start date</label
                                >
                                <div class="pl-control">
                                    <i
                                        class="fa-regular fa-calendar pl-control-icon"
                                        aria-hidden="true"
                                    ></i>
                                    <input
                                        id="plannerDetailStartDate"
                                        v-model="detailForm.start_date"
                                        type="date"
                                        class="form-control form-control-sm"
                                        @change="immediateSave('start_date')"
                                    />
                                </div>
                            </div>

                            <!-- Due date -->
                            <div class="pl-prop">
                                <label
                                    class="pl-prop-label"
                                    for="plannerDetailDueDate"
                                    >Due date</label
                                >
                                <div class="pl-control">
                                    <i
                                        class="fa-regular fa-calendar pl-control-icon"
                                        aria-hidden="true"
                                    ></i>
                                    <input
                                        id="plannerDetailDueDate"
                                        v-model="detailForm.due_date"
                                        type="date"
                                        class="form-control form-control-sm"
                                        :class="{
                                            'due-overdue-input':
                                                isOverdue(detailForm),
                                        }"
                                        @change="immediateSave('due_date')"
                                    />
                                </div>
                            </div>

                            <!-- Repeat -->
                            <div class="pl-prop">
                                <label
                                    class="pl-prop-label"
                                    for="plannerDetailRepeat"
                                    >Repeat</label
                                >
                                <div class="pl-control">
                                    <i
                                        class="fa-solid fa-rotate pl-control-icon"
                                        aria-hidden="true"
                                    ></i>
                                    <select
                                        id="plannerDetailRepeat"
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
                            </div>

                            <!-- Bucket -->
                            <div class="pl-prop">
                                <label
                                    class="pl-prop-label"
                                    for="plannerDetailBucket"
                                    >Bucket</label
                                >
                                <div class="pl-control">
                                    <i
                                        class="fa-solid fa-box-archive pl-control-icon"
                                        aria-hidden="true"
                                    ></i>
                                    <select
                                        id="plannerDetailBucket"
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
                            </div>

                            <!-- Privacy -->
                            <div class="pl-prop">
                                <label
                                    class="pl-prop-label"
                                    for="plannerDetailPrivacy"
                                    >Privacy</label
                                >
                                <div class="pl-control">
                                    <i
                                        class="fa-solid fa-lock pl-control-icon"
                                        aria-hidden="true"
                                    ></i>
                                    <select
                                        id="plannerDetailPrivacy"
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
                            </div>
                        </div>

                        <!-- ---------- Checklist ---------- -->
                        <section
                            class="pl-section"
                            aria-labelledby="plChecklistTitle"
                        >
                            <div
                                class="d-flex justify-content-between pl-sec-head"
                            >
                                <h3 id="plChecklistTitle" class="pl-sec-title">
                                    <!-- <i
                                        class="fa-regular fa-square-check"
                                        aria-hidden="true"
                                    ></i> -->
                                    Checklist
                                    <span class="pl-section-count">
                                        ({{ detailChecklistCompleted }} of
                                        {{ detailChecklists.length }} items
                                        done)
                                    </span>
                                </h3>
                                <div class="form-check pl-board-toggle mb-0">
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
                                        class="form-check-label"
                                        for="showChecklistToggle"
                                        >Show in board view</label
                                    >
                                </div>
                            </div>

                            <!-- <p
                                v-if="!detailChecklists.length"
                                class="pl-empty-hint"
                            >
                            <span
                                    class="pl-empty-icon pl-hint-icon"
                                    aria-hidden="true"
                                >
                                    <i class="fa-regular fa-square-check"></i>
                                </span>
                            <span>
                                    No checklist items yet. Add the first step
                                    below.
                                </span>
                            </p> -->

                            <ul class="pl-checklist">
                                <li
                                    v-for="cl in detailChecklists"
                                    :key="cl.id"
                                    class="pl-checklist-row"
                                    :class="{
                                        'is-done': cl.completed_status,
                                    }"
                                >
                                    <input
                                        type="checkbox"
                                        class="form-check-input pl-check-box"
                                        :checked="cl.completed_status"
                                        :aria-label="cl.checklist_name"
                                        @change="onChecklistToggle(cl, $event)"
                                    />
                                    <input
                                        class="pl-checklist-text"
                                        :class="{
                                            'is-done': cl.completed_status,
                                        }"
                                        :value="cl.checklist_name"
                                        :aria-label="cl.checklist_name"
                                        @keyup.enter="saveChecklistName(cl)"
                                    />
                                    <button
                                        class="pl-checklist-remove"
                                        type="button"
                                        title="Remove"
                                        aria-label="Remove checklist item"
                                        @click="removeChecklistItem(cl)"
                                    >
                                        <i
                                            class="fa-solid fa-xmark"
                                            aria-hidden="true"
                                        ></i>
                                    </button>
                                </li>
                            </ul>

                            <div class="pl-checklist-add">
                                <i
                                    class="fa-regular fa-circle-plus pl-add-icon"
                                    aria-hidden="true"
                                ></i>
                                <input
                                    :value="newChecklistName"
                                    type="text"
                                    class="pl-checklist-add-input"
                                    placeholder="Add an item"
                                    aria-label="New checklist item"
                                    @input="
                                        $emit(
                                            'update:newChecklistName',
                                            $event.target.value,
                                        )
                                    "
                                    @keyup.enter="addChecklistItem"
                                />
                                <button
                                    class="pl-checklist-add-btn"
                                    type="button"
                                    title="Add checklist item"
                                    aria-label="Add checklist item"
                                    @click="addChecklistItem"
                                >
                                    <i
                                        class="fa-solid fa-plus"
                                        aria-hidden="true"
                                    ></i>
                                </button>
                            </div>
                        </section>

                        <!-- ---------- Notes ---------- -->
                        <section
                            class="pl-section"
                            aria-labelledby="plNotesTitle"
                        >
                            <div class="pl-sec-head">
                                <h3 id="plNotesTitle" class="pl-sec-title">
                                    <!-- <i
                                        class="fa-regular fa-note-sticky"
                                        aria-hidden="true"
                                    ></i> -->
                                    Notes
                                </h3>
                            </div>
                            <textarea
                                id="plannerDetailNotes"
                                v-model="detailForm.notes"
                                class="form-control pl-notes-area"
                                rows="3"
                                placeholder="Add notes..."
                                @input="debouncedDetailSave('notes')"
                            ></textarea>
                        </section>
                    </div>
                </div>

                <!-- ================= FOOTER ================= -->
                <div class="modal-footer pl-dt-foot">
                    <button
                        class="btn btn-outline-secondary pl-footer-close"
                        type="button"
                        @click="closeDetailModal"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" @click="closeDetailModal"></div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
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

const assigneeLabel = computed(() => {
    const list = props.detailForm?.assignees || [];
    if (!list.length) return "No assignee";
    const names = list.map((a) => a.employee?.Full_Name).filter(Boolean);
    if (!names.length) return "No assignee";
    if (names.length === 1) return names[0];
    return `${names[0]} +${names.length - 1}`;
});
</script>

<style scoped>
@import "./../styles/planner-theme.css";

/* --- Overlay -------------------------------------------------------------- */
.task-detail-modal {
    z-index: 1050;
}

.task-detail-modal .modal-backdrop {
    z-index: -1;
    position: fixed;
    inset: 0;
    background-color: rgba(2, 4, 10, 0.72);
    -webkit-backdrop-filter: blur(6px);
    backdrop-filter: blur(6px);
}

.task-detail-modal .modal-dialog {
    z-index: 1056;
    position: relative;
}

/* --- Panel ---------------------------------------------------------------- */
.task-detail-modal .modal-content {
    background-color: var(--pl-bg);
    border: 1px solid var(--pl-border-strong);
    border-radius: 16px;
    box-shadow: var(--pl-shadow-lg);
    overflow: hidden;
    animation: pl-modal-in 160ms ease-out both;
}

@keyframes pl-modal-in {
    from {
        opacity: 0;
        transform: translateY(8px) scale(0.99);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* --- Header --------------------------------------------------------------- */
.task-detail-modal .modal-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 20px 14px;
    background-color: var(--pl-surface);
    border-bottom: 1px solid var(--pl-border);
}

.pl-dt-head-main {
    flex: 1 1 auto;
    min-width: 0;
}

.pl-plan-name {
    display: block;
    margin-bottom: 5px;
    font-size: 11.5px;
    font-weight: 500;
    line-height: 1.3;
    color: var(--pl-muted);
    letter-spacing: 0.01em;
}

.pl-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.pl-title-input {
    flex: 1 1 auto;
    min-width: 0;
    field-sizing: content;
    max-height: 30vh;
    padding: 2px 6px;
    margin: -2px 0;
    overflow-y: auto;
    resize: none;
    border: 1px solid transparent;
    border-radius: var(--pl-radius-sm);
    background-color: transparent;
    color: var(--pl-text);
    font-size: 21px;
    font-weight: 700;
    line-height: 1.3;
    letter-spacing: -0.02em;
    transition: var(--pl-ease);
}

.pl-title-input::placeholder {
    color: var(--pl-faint);
}

.pl-title-input:hover {
    background-color: var(--pl-hover);
}

.pl-title-input:focus {
    outline: none;
    border-color: var(--pl-accent);
    background-color: var(--pl-surface);
    box-shadow: var(--pl-ring);
}

.pl-meta-line {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin: 6px 0 0;
    font-size: 12px;
    line-height: 1.4;
    color: var(--pl-muted);
}

.pl-meta-line > i {
    font-size: 11px;
    color: var(--pl-faint);
}

.pl-meta-line strong {
    font-weight: 600;
    color: var(--pl-text-soft);
}

.pl-meta-dot {
    color: var(--pl-faint);
}

.pl-meta-read {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-weight: 500;
    color: var(--pl-success-text);
}

.pl-meta-unread {
    font-weight: 500;
    color: var(--pl-faint);
}

/* --- Label + assignee row ------------------------------------------------- */
.pl-chip-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
}

.pl-label-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border: 1px dashed var(--pl-border-strong);
    border-radius: 999px;
    background-color: var(--pl-surface-alt);
    color: var(--pl-muted);
    font-size: 12px;
    font-weight: 500;
    line-height: 1.2;
    white-space: nowrap;
}

.pl-label-pill > i {
    font-size: 10.2px;
}

.pl-chip-sep {
    width: 1px;
    height: 18px;
    background-color: var(--pl-border);
}

.pl-assignee-box {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
}

.pl-assignee-faces,
.pl-assignee-faces .avatar-chip + .avatar-chip {
    margin-left: -7px;
}

.pl-dt-overflow {
    color: var(--pl-text-soft);
}

.pl-assignee-names {
    min-width: 0;
    overflow: hidden;
    font-size: 13px;
    font-weight: 500;
    color: var(--pl-text);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pl-manage-assignees {
    flex-shrink: 0;
    min-height: 30px;
    padding: 0 10.4px;
    font-size: 12px;
    font-weight: 500;
}

/* Warm accent avatar, scoped to this panel */
.task-detail-modal .avatar-chip {
    background-image: linear-gradient(140deg, #fbbf24 0%, #f97316 100%);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.45);
}

.task-detail-modal .avatar-chip:hover {
    box-shadow: 0 4px 10px rgba(249, 115, 22, 0.35);
}

/* --- Close ---------------------------------------------------------------- */
.pl-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    margin: -4px -4px 0 0;
    padding: 0;
    border: 1px solid transparent;
    border-radius: var(--pl-radius-sm);
    background-color: transparent;
    color: var(--pl-muted);
    font-size: 15px;
    line-height: 1;
    cursor: pointer;
    transition: var(--pl-ease);
}

.pl-close:hover {
    border-color: var(--pl-border);
    background-color: var(--pl-hover);
    color: var(--pl-text);
}

.pl-close:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

/* --- Body ----------------------------------------------------------------- */
.task-detail-modal .modal-body {
    padding: 18px 20px;
    background-color: var(--pl-bg);
}

.pl-prop-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px 20px;
}

.pl-prop {
    min-width: 0;
}

.pl-prop-label {
    display: block;
    margin-bottom: 6px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--pl-muted);
}

.pl-control {
    position: relative;
}

.task-detail-modal .pl-control .form-select,
.task-detail-modal .pl-control .form-control {
    width: 100%;
    min-height: 36px;
    padding-left: 33px;
    font-weight: 500;
}

.pl-control-icon {
    position: absolute;
    top: 50%;
    left: 11px;
    z-index: 1;
    transform: translateY(-50%);
    font-size: 11.5px;
    color: var(--pl-faint);
    pointer-events: none;
}

.pl-control-dot {
    position: absolute;
    top: 50%;
    left: 12px;
    z-index: 1;
    width: 7px;
    height: 7px;
    transform: translateY(-50%);
    border-radius: 50%;
    background-color: var(--pl-success-text);
    box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.16);
    pointer-events: none;
}

/* Native option list on a dark surface */
.task-detail-modal .form-select option {
    background-color: var(--pl-surface);
    color: var(--pl-text);
}

.task-detail-modal .form-select option:checked {
    background-color: var(--pl-accent);
    color: #fff;
}

/* Native date picker glyph is dark by default */
.task-detail-modal
    input[type="date"].form-control::-webkit-calendar-picker-indicator {
    filter: invert(0.78);
}

/* Dark-mode overrides for shared primitives */
.task-detail-modal .form-control,
.task-detail-modal .form-select {
    background-color: var(--pl-surface);
    border-color: var(--pl-border);
    color: var(--pl-text);
}

/* Keep the accent border on focus (these sit after the shared primitives) */
.task-detail-modal .form-control:focus,
.task-detail-modal .form-select:focus {
    border-color: var(--pl-accent);
    box-shadow: var(--pl-ring);
}

/* --- Sections (Checklist / Notes) ----------------------------------------- */
.pl-section {
    margin-top: 18px;
    padding: 15px 16px;
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-lg);
    background-color: var(--pl-surface);
}

.pl-sec-head {
    padding-bottom: 11px;
    margin-bottom: 11px;
    border-bottom: 1px solid var(--pl-border);
}

.pl-section-count {
    font-size: 12px;
    font-weight: 500;
    color: var(--pl-muted);
}

.pl-board-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding: 0;
}

.pl-board-toggle .form-check-input {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    margin: 0;
    border-radius: var(--pl-radius-xs);
}

.pl-board-toggle .form-check-label {
    margin: 0;
    color: var(--pl-muted);
    white-space: nowrap;
}

.pl-empty-hint {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 6px;
    padding: 2px 2px;
    font-size: 12.5px;
    line-height: 1.45;
    color: var(--pl-muted);
}

.pl-hint-icon {
    width: 30px;
    height: 30px;
    margin-bottom: 0;
    flex-shrink: 0;
    font-size: 12px;
}

/* --- Checklist rows ------------------------------------------------------- */
.pl-checklist {
    padding: 0;
    margin: 0;
    list-style: none;
}

.pl-checklist-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 2px;
    border-bottom: 1px solid var(--pl-border);
}

.pl-checklist-row:last-child {
    border-bottom: 0;
}

.pl-check-box {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    margin: 0;
    border-radius: 50%;
}

.pl-checklist-text {
    flex: 1 1 auto;
    min-width: 0;
    min-height: 26px;
    padding: 2px 6px;
    border: 1px solid transparent;
    border-radius: var(--pl-radius-xs);
    background-color: transparent;
    font-size: 13px;
    font-weight: 500;
    line-height: 1.4;
    color: var(--pl-text);
    transition: var(--pl-ease);
}

.pl-checklist-text:hover {
    border-color: var(--pl-border);
    background-color: var(--pl-surface-alt);
}

.pl-checklist-text:focus {
    outline: none;
    border-color: var(--pl-accent);
    background-color: var(--pl-surface-alt);
    box-shadow: var(--pl-ring);
}

.pl-checklist-text.is-done {
    color: var(--pl-muted);
    text-decoration: line-through;
    text-decoration-color: var(--pl-faint);
}

.pl-checklist-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    padding: 0;
    border: 1px solid transparent;
    border-radius: var(--pl-radius-xs);
    background-color: transparent;
    color: var(--pl-danger-text);
    font-size: 11px;
    line-height: 1;
    cursor: pointer;
    opacity: 0.55;
    transition: var(--pl-ease);
}

.pl-checklist-row:hover .pl-checklist-remove,
.pl-checklist-remove:focus-visible {
    opacity: 1;
}

.pl-checklist-remove:hover {
    background-color: var(--pl-danger-soft);
    border-color: var(--pl-danger-border);
}

.pl-checklist-remove:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring-danger);
}

/* --- Add checklist item --------------------------------------------------- */
.pl-checklist-add {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    padding: 4px 6px 4px 10px;
    border: 1px solid transparent;
    border-radius: var(--pl-radius-sm);
    transition: var(--pl-ease);
}

.pl-checklist-add:hover,
.pl-checklist-add:focus-within {
    border-color: var(--pl-border);
    background-color: var(--pl-surface-alt);
}

.pl-checklist-add:focus-within {
    border-color: var(--pl-accent);
}

.pl-add-icon {
    font-size: 12.5px;
    color: var(--pl-faint);
}

.pl-checklist-add:focus-within .pl-add-icon {
    color: var(--pl-accent-text);
}

.pl-checklist-add-input {
    flex: 1 1 auto;
    min-width: 0;
    padding: 4px 2px;
    border: 0;
    background-color: transparent;
    color: var(--pl-text);
    font-size: 13px;
}

.pl-checklist-add-input:focus {
    outline: none;
    box-shadow: var(--pl-ring);
    border-radius: var(--pl-radius-xs);
}

.pl-checklist-add-input::placeholder {
    color: var(--pl-faint);
}

.pl-checklist-add-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    padding: 0;
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-xs);
    background-color: var(--pl-surface);
    color: var(--pl-text-soft);
    font-size: 10.2px;
    line-height: 1;
    cursor: pointer;
    transition: var(--pl-ease);
}

.pl-checklist-add-btn:hover {
    border-color: var(--pl-accent);
    background-color: var(--pl-accent);
    color: #fff;
}

.pl-checklist-add-btn:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

/* --- Notes ---------------------------------------------------------------- */
.pl-notes-area {
    width: 100%;
    min-height: 80px;
    padding: 9px 11px;
    resize: vertical;
    background-color: var(--pl-surface-alt);
    font-size: 13px;
}

.pl-notes-area:focus {
    background-color: var(--pl-surface);
}

/* --- Footer --------------------------------------------------------------- */
.task-detail-modal .modal-footer {
    justify-content: flex-end;
    padding: 12px 20px;
    background-color: var(--pl-surface);
    border-top: 1px solid var(--pl-border);
}

.pl-footer-close {
    min-height: 34px;
    padding: 0 16px;
    font-weight: 500;
}

/* --- Responsive ----------------------------------------------------------- */
@media (max-width: 991.98px) {
    .pl-prop-grid {
        gap: 14px 16px;
    }

    .task-detail-modal .modal-header,
    .task-detail-modal .modal-body,
    .task-detail-modal .modal-footer {
        padding-left: 18px;
        padding-right: 18px;
    }
}

@media (max-width: 767.98px) {
    .pl-prop-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .pl-title-input {
        font-size: 18px;
    }

    .task-detail-modal .modal-content {
        max-height: 92vh;
    }

    .task-detail-modal .modal-body {
        overflow-y: auto;
    }
}

@media (max-width: 575.98px) {
    .task-detail-modal .modal-dialog {
        margin: 0;
        min-height: 100%;
        padding: 8px;
    }

    .task-detail-modal .modal-header,
    .task-detail-modal .modal-body,
    .task-detail-modal .modal-footer,
    .task-detail-modal .modal-header {
        padding-top: 14px;
    }

    .pl-chip-row {
        gap: 8px;
    }

    .pl-chip-sep {
        display: none;
    }

    .pl-section {
        padding: 13px;
    }

    .pl-sec-head {
        gap: 8px;
    }

    .pl-footer-close {
        width: 100%;
    }
}
</style>
