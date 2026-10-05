<template>
    <div class="planner-toolbar">
        <div class="pl-toolbar-inner">
            <div class="pl-toolbar-group">
                <div class="pl-seg" role="group" aria-label="View mode">
                    <button
                        type="button"
                        :aria-pressed="view === 'grid'"
                        @click="$emit('update:view', 'grid')"
                    >
                        <i
                            class="fa-solid fa-table-cells"
                            aria-hidden="true"
                        ></i
                        >Grid
                    </button>
                    <button
                        type="button"
                        :aria-pressed="view === 'board'"
                        @click="$emit('update:view', 'board')"
                    >
                        <i class="fa-solid fa-columns" aria-hidden="true"></i
                        >Board
                    </button>
                </div>

                <div
                    class="pl-toggles"
                    role="group"
                    aria-label="Privacy filter"
                >
                    <button
                        class="pl-toggle"
                        :class="{ 'is-on': privacy === 'Private' }"
                        type="button"
                        :aria-pressed="privacy === 'Private'"
                        @click="$emit('toggle-privacy', 'Private')"
                    >
                        <i class="fa-solid fa-lock" aria-hidden="true"></i
                        >Private
                    </button>
                    <button
                        class="pl-toggle"
                        :class="{ 'is-on': privacy === 'Shared' }"
                        type="button"
                        :aria-pressed="privacy === 'Shared'"
                        @click="$emit('toggle-privacy', 'Shared')"
                    >
                        <i class="fa-solid fa-users" aria-hidden="true"></i
                        >Shared
                    </button>
                </div>
            </div>

            <div class="pl-filters">
                <div class="pl-filter pl-filter-search">
                    <label class="visually-hidden" for="plannerEmployeeSearch"
                        >Search tasks</label
                    >
                    <i
                        class="fa-solid fa-magnifying-glass search-icon"
                        aria-hidden="true"
                    ></i>
                    <input
                        id="plannerEmployeeSearch"
                        :value="employeeSearch"
                        type="search"
                        class="form-control form-control-sm pl-employee-search"
                        placeholder="Search tasks..."
                        autocomplete="off"
                        @input="
                            $emit('update:employeeSearch', $event.target.value)
                        "
                    />
                    <button
                        v-if="employeeSearch"
                        class="search-clear"
                        type="button"
                        title="Clear search"
                        aria-label="Clear search"
                        @click="$emit('update:employeeSearch', '')"
                    >
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="pl-filter">
                    <label class="visually-hidden" for="plannerStatusFilter"
                        >Filter by status</label
                    >
                    <i
                        class="fa-regular fa-circle-half-stroke pl-filter-icon"
                        aria-hidden="true"
                    ></i>
                    <select
                        id="plannerStatusFilter"
                        :value="statusFilter"
                        class="form-select form-select-sm pl-status-filter"
                        :class="{ 'is-set': statusFilter !== 'All' }"
                        @change="
                            $emit('update:statusFilter', $event.target.value)
                        "
                    >
                        <option value="All">All</option>
                        <option value="Not started">Not started</option>
                        <option value="In progress">In progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>

            <div class="pl-toolbar-end">
                <span
                    v-if="loading"
                    class="pl-loading"
                    role="status"
                    aria-live="polite"
                >
                    <span class="pl-loading-dot" aria-hidden="true"></span>
                    <span>Loading</span>
                </span>
                <button
                    class="pl-icon-btn pl-refresh"
                    type="button"
                    title="Refresh"
                    aria-label="Refresh tasks"
                    @click="$emit('reload')"
                >
                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
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
@import "./../styles/planner-theme.css";

/* --- Toolbar surface ------------------------------------------------------ */
.planner-toolbar {
    margin-bottom: 14px;
    background-color: var(--pl-surface);
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-lg);
    box-shadow: var(--pl-shadow-sm);
}

.pl-toolbar-inner {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px 12px;
    padding: 10px 12px;
}

.pl-toolbar-group {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.pl-seg {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

/* --- Privacy toggles ------------------------------------------------------ */
.pl-toggles {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

.pl-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: var(--pl-control-h);
    padding: 0 11px;
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius-sm);
    background-color: var(--pl-surface);
    color: var(--pl-muted);
    font-size: 12px;
    font-weight: 500;
    line-height: 1;
    white-space: nowrap;
    cursor: pointer;
    transition:
        background-color 140ms ease,
        border-color 140ms ease,
        color 140ms ease,
        box-shadow 140ms ease;
}

.pl-toggle > i {
    font-size: 10.5px;
}

.pl-toggle:hover {
    border-color: var(--pl-border-strong);
    background-color: var(--pl-hover);
    color: var(--pl-text);
}

.pl-toggle.is-on {
    border-color: var(--pl-accent-border);
    background-color: var(--pl-accent-soft);
    color: var(--pl-accent-text);
}

.pl-toggle:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

/* --- Filters -------------------------------------------------------------- */
.pl-filters {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    min-width: 0;
    margin-left: auto;
}

.pl-filter {
    position: relative;
    min-width: 0;
}

.pl-filter-icon,
.search-icon {
    position: absolute;
    top: 50%;
    left: 11px;
    z-index: 1;
    transform: translateY(-50%);
    color: var(--pl-faint);
    font-size: 11px;
    pointer-events: none;
    transition: color 140ms ease;
}

.pl-filter:focus-within .pl-filter-icon,
.pl-filter:focus-within .search-icon {
    color: var(--pl-accent);
}

.pl-status-filter {
    width: auto;
    min-width: 138px;
    padding-left: 31px;
}

/* A non-default selection is signalled by the accent border + tint */
.pl-status-filter.is-set {
    border-color: var(--pl-accent-border);
    background-color: var(--pl-accent-soft);
    color: var(--pl-accent-text);
    font-weight: 500;
}

.pl-employee-search {
    width: 226px;
    max-width: 100%;
    padding-left: 32px;
    padding-right: 32px;
}

/* Native search clear affordance duplicated by our own button */
.pl-employee-search::-webkit-search-cancel-button {
    -webkit-appearance: none;
    appearance: none;
}

.search-clear {
    position: absolute;
    top: 50%;
    right: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 21px;
    height: 21px;
    padding: 0;
    transform: translateY(-50%);
    border: 0;
    border-radius: var(--pl-radius-xs);
    background-color: transparent;
    color: var(--pl-muted);
    font-size: 10px;
    cursor: pointer;
    transition:
        background-color 140ms ease,
        color 140ms ease;
}

.search-clear:hover {
    background-color: var(--pl-surface-alt);
    color: var(--pl-text);
}

.search-clear:focus-visible {
    outline: none;
    box-shadow: var(--pl-ring);
}

/* --- Right aligned actions ------------------------------------------------ */
.pl-toolbar-end {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.pl-loading {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--pl-muted);
    font-size: 11.5px;
    font-weight: 500;
    white-space: nowrap;
}

.pl-loading-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: var(--pl-accent);
    box-shadow: 0 0 0 3px rgba(var(--pl-accent-rgb), 0.18);
    animation: pl-pulse 1.1s ease-in-out infinite;
}

@keyframes pl-pulse {
    0%,
    100% {
        opacity: 0.35;
    }
    50% {
        opacity: 1;
    }
}

.pl-refresh:hover > i {
    animation: pl-spin-once 600ms ease;
}

@keyframes pl-spin-once {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* --- Responsive ----------------------------------------------------------- */
@media (max-width: 991.98px) {
    .pl-filters {
        margin-left: 0;
    }
}

@media (max-width: 767.98px) {
    .pl-toolbar-inner {
        padding: 10px;
        gap: 8px;
    }

    .pl-toolbar-group {
        flex: 1 1 100%;
        flex-wrap: wrap;
    }

    .pl-toolbar-end {
        margin-left: auto;
    }
}

@media (max-width: 575.98px) {
    .pl-filters {
        width: 100%;
    }

    .pl-filters > .pl-filter {
        flex: 1 1 100%;
    }

    .pl-status-filter,
    .pl-employee-search {
        width: 100%;
        min-width: 0;
    }

    .pl-toggles,
    .pl-toggle,
    .pl-seg,
    .pl-seg > button {
        flex: 1 1 auto;
    }
}
</style>
