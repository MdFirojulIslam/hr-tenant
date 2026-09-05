<template>
    <div class="planner-toolbar card mb-3">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
            <button
                class="btn btn-sm"
                :class="view === 'grid' ? 'btn-primary' : 'btn-outline-primary'"
                type="button"
                @click="$emit('update:view', 'grid')"
            >
                <i class="fa-solid fa-table-cells me-1"></i>Grid
            </button>
            <button
                class="btn btn-sm"
                :class="view === 'board' ? 'btn-primary' : 'btn-outline-primary'"
                type="button"
                @click="$emit('update:view', 'board')"
            >
                <i class="fa-solid fa-columns me-1"></i>Board
            </button>

            <div class="vr d-none d-sm-block"></div>

            <button
                class="btn btn-sm"
                :class="
                    privacy === 'Private'
                        ? 'btn-secondary text-white'
                        : 'btn-outline-secondary'
                "
                type="button"
                @click="$emit('toggle-privacy', 'Private')"
            >
                <i class="fa-solid fa-lock me-1"></i>Private
            </button>
            <button
                class="btn btn-sm"
                :class="
                    privacy === 'Shared'
                        ? 'btn-secondary text-white'
                        : 'btn-outline-secondary'
                "
                type="button"
                @click="$emit('toggle-privacy', 'Shared')"
            >
                <i class="fa-solid fa-users me-1"></i>Shared
            </button>

            <div class="vr d-none d-sm-block"></div>

            <select
                :value="statusFilter"
                class="form-select form-select-sm"
                style="width: auto"
                @change="$emit('update:statusFilter', $event.target.value)"
            >
                <option value="All">All</option>
                <option value="Not started">Not started</option>
                <option value="In progress">In progress</option>
                <option value="Completed">Completed</option>
            </select>

            <div class="employee-search position-relative">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                    :value="employeeSearch"
                    type="text"
                    class="form-control form-control-sm"
                    style="padding-left: 30px; width: 200px"
                    placeholder="Employee name..."
                    @input="$emit('update:employeeSearch', $event.target.value)"
                />
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <span v-if="loading" class="small text-muted">
                    <span class="spinner-border spinner-border-sm me-1"></span
                    >Loading...
                </span>
                <button
                    class="btn btn-sm btn-outline-secondary"
                    type="button"
                    @click="$emit('reload')"
                    title="Refresh"
                >
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    view: { type: String, required: true },
    privacy: { type: String, default: null },
    statusFilter: { type: String, required: true },
    employeeSearch: { type: String, required: true },
    loading: { type: Boolean, default: false },
});

defineEmits([
    "update:view",
    "toggle-privacy",
    "update:statusFilter",
    "update:employeeSearch",
    "reload",
]);
</script>

<style scoped>
.employee-search .search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 0.8rem;
}
</style>