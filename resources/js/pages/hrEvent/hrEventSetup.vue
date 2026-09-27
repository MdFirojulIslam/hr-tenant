<script setup>
import { ref, computed, onMounted, defineAsyncComponent } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

import api from "@/api";

const HrEventModal = defineAsyncComponent(
    () => import("./components/hrEventModal.vue"),
);

const PER_PAGE = 20;

const loading = ref(true);
const loadingDepartments = ref(true);
const loadingEmployees = ref(false);
const listError = ref("");
const departmentsError = ref("");
const employeesError = ref("");

const events = ref([]);
const pagination = ref({});
const total = ref(0);
const currentPage = ref(1);
const lastPage = ref(1);
const from = ref(null);
const to = ref(null);

const departments = ref([]);
const filterEmployees = ref([]);

const selectedDepartmentId = ref(null);
const selectedEmployeeId = ref(null);

const showModal = ref(false);
const modalMode = ref("create");
const modalEvent = ref(null);
const modalPreselect = ref({});

const tableRows = computed(() => events.value || []);
const hasRows = computed(() => tableRows.value.length > 0);
const hasFilters = computed(
    () => !!selectedDepartmentId.value || !!selectedEmployeeId.value,
);
const recordSummary = computed(() => {
    if (loading.value) return "Loading records...";
    if (total.value === 1) return "1 record";
    return `${total.value} records`;
});

const selectedDepartment = computed({
    get: () =>
        departments.value.find(
            (dept) => String(dept.department_id) === String(selectedDepartmentId.value),
        ) || null,
    set: (value) => {
        selectedDepartmentId.value = value ? value.department_id : null;
    },
});

const selectedEmployee = computed({
    get: () =>
        filterEmployees.value.find(
            (emp) => String(emp.employee_id) === String(selectedEmployeeId.value),
        ) || null,
    set: (value) => {
        selectedEmployeeId.value = value ? value.employee_id : null;
    },
});

const employeeFilterPlaceholder = computed(() => {
    if (!selectedDepartmentId.value) return "Select a department first";
    if (loadingEmployees.value) return "Loading employees...";
    if (filterEmployees.value.length === 0)
        return "No employees in this department";
    return "All Employees";
});

const normalizeFilterError = (err, fallback) =>
    err.response?.data?.message || err.message || fallback;

const loadDepartments = async () => {
    loadingDepartments.value = true;
    departmentsError.value = "";

    try {
        const res = await api.get("/department");
        const rows = Array.isArray(res.data) ? res.data : [];
        departments.value = rows
            .map((dept) => ({
                department_id: dept.id ?? dept.department_id ?? null,
                department_name: dept.Name ?? dept.department_name ?? "",
            }))
            .filter((dept) => dept.department_id && dept.department_name)
            .sort((a, b) => a.department_name.localeCompare(b.department_name));
    } catch (err) {
        departments.value = [];
        departmentsError.value = normalizeFilterError(
            err,
            "Unable to load departments.",
        );
    } finally {
        loadingDepartments.value = false;
    }
};

const loadFilterEmployees = async (departmentId) => {
    filterEmployees.value = [];
    employeesError.value = "";

    if (!departmentId) return;

    loadingEmployees.value = true;

    try {
        const res = await api.get(`/employee-list-by-department/${departmentId}`);
        const rows = Array.isArray(res.data) ? res.data : [];
        filterEmployees.value = rows
            .map((emp) => ({
                employee_id: emp.employee_id ?? emp.id,
                employee_name: emp.employee_name ?? emp.Full_Name ?? "",
            }))
            .filter((emp) => emp.employee_id && emp.employee_name)
            .sort((a, b) => a.employee_name.localeCompare(b.employee_name));
    } catch (err) {
        filterEmployees.value = [];
        employeesError.value = normalizeFilterError(
            err,
            "Unable to load employees.",
        );
    } finally {
        loadingEmployees.value = false;
    }
};

const loadEvents = async (page = 1) => {
    loading.value = true;
    listError.value = "";

    try {
        const res = await api.get("/show_event", {
            params: {
                page,
                per_page: PER_PAGE,
                ...(selectedDepartmentId.value
                    ? { department_id: selectedDepartmentId.value }
                    : {}),
                ...(selectedEmployeeId.value
                    ? { employee_id: selectedEmployeeId.value }
                    : {}),
            },
        });

        const payload = res.data || {};

        events.value = Array.isArray(payload.data) ? payload.data : [];
        pagination.value = payload;
        total.value = Number(payload.total) || 0;
        currentPage.value = Number(payload.current_page) || 1;
        lastPage.value = Number(payload.last_page) || 1;
        from.value = payload.from ?? null;
        to.value = payload.to ?? null;
    } catch (err) {
        events.value = [];
        pagination.value = {};
        total.value = 0;
        currentPage.value = 1;
        lastPage.value = 1;
        listError.value = normalizeFilterError(
            err,
            "Unable to load HR events. Please try again.",
        );
    } finally {
        loading.value = false;
    }
};

const onDepartmentFilterChange = async (value) => {
    selectedEmployeeId.value = null;
    await loadFilterEmployees(value ? value.department_id : null);
    await loadEvents(1);
};

const onEmployeeFilterChange = async () => {
    await loadEvents(1);
};

const clearFilters = async () => {
    selectedDepartmentId.value = null;
    selectedEmployeeId.value = null;
    filterEmployees.value = [];
    employeesError.value = "";
    await loadEvents(1);
};

const clearDepartmentFilter = async () => {
    selectedDepartment.value = null;
    await onDepartmentFilterChange(null);
};

const clearEmployeeFilter = async () => {
    selectedEmployee.value = null;
    await onEmployeeFilterChange();
};

const onPageChange = async (page) => {
    await loadEvents(page);
};

const openCreateModal = () => {
    modalMode.value = "create";
    modalEvent.value = null;
    modalPreselect.value = {
        department_id: selectedDepartmentId.value,
        employee_id: selectedEmployeeId.value,
    };
    showModal.value = true;
};

const openEditModal = (row) => {
    modalMode.value = "edit";
    modalEvent.value = row;
    modalPreselect.value = {};
    showModal.value = true;
};

const openViewModal = (row) => {
    modalMode.value = "view";
    modalEvent.value = row;
    modalPreselect.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    modalEvent.value = null;
    modalPreselect.value = {};
};

const onModalSaved = async ({ mode }) => {
    closeModal();
    await loadEvents(mode === "create" ? 1 : currentPage.value || 1);
};

const serialNumber = (index) =>
    (from.value ? from.value - 1 : 0) + index + 1;

const formatDate = (value) => {
    if (!value) return "—";

    const [year, month, day] = String(value).slice(0, 10).split("-");

    if (!year || !month || !day) return value;

    const months = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
    ];

    const monthIndex = Number(month);

    if (monthIndex < 1 || monthIndex > 12) return value;

    return `${day} ${months[monthIndex - 1]} ${year}`;
};

const truncate = (value, length = 60) => {
    const text = String(value ?? "").trim();

    if (!text) return "—";
    if (text.length <= length) return text;

    return `${text.slice(0, length)}...`;
};

const eventBadgeClass = (name) => {
    const value = String(name ?? "").toLowerCase();

    if (value.includes("termination") || value.includes("rustication")) {
        return "hre-badge--danger";
    }

    if (value.includes("suspension") || value.includes("warning")) {
        return "hre-badge--warning";
    }

    if (value.includes("promotion") || value.includes("increment")) {
        return "hre-badge--success";
    }

    if (value.includes("transfer") || value.includes("demotion")) {
        return "hre-badge--info";
    }

    return "hre-badge--neutral";
};

onMounted(async () => {
    await Promise.all([loadDepartments(), loadEvents(1)]);
});
</script>

<template>
    <div class="hre-page">
        <div class="hre-heading">
            <div class="hre-heading__text">
                <h1 class="hre-heading__title">Employee HR Event</h1>
                <p class="hre-heading__subtitle">
                    Record and track HR actions &mdash; show cause, warning,
                    suspension, promotion, transfer and more.
                </p>
            </div>
            <button type="button" class="hre-btn-add" @click="openCreateModal">
                <i class="fa-solid fa-plus"></i>
                New Event
            </button>
        </div>

        <div class="hre-toolbar">
            <div class="hre-filters">
                <div class="hre-filter">
                    <label class="hre-filter__label" for="hre-filter-department">
                        Department
                    </label>
                    <div class="hre-select">
                        <Multiselect
                            id="hre-filter-department"
                            v-model="selectedDepartment"
                            :options="departments"
                            label="department_name"
                            track-by="department_id"
                            :searchable="true"
                            :close-on-select="true"
                            :clear-on-select="false"
                            :show-labels="false"
                            :allow-empty="true"
                            :disabled="loadingDepartments"
                            :loading="loadingDepartments"
                            placeholder="All Departments"
                            @update:model-value="onDepartmentFilterChange"
                        />
                        <button
                            v-if="selectedDepartment"
                            type="button"
                            class="hre-select__clear"
                            aria-label="Clear department filter"
                            @click="clearDepartmentFilter"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <small
                        v-if="departmentsError"
                        class="hre-filter__error"
                    >
                        {{ departmentsError }}
                    </small>
                </div>

                <div class="hre-filter hre-filter--grow">
                    <label class="hre-filter__label" for="hre-filter-employee">
                        Employee Name
                    </label>
                    <div class="hre-select">
                        <Multiselect
                            id="hre-filter-employee"
                            v-model="selectedEmployee"
                            :options="filterEmployees"
                            label="employee_name"
                            track-by="employee_id"
                            :searchable="true"
                            :close-on-select="true"
                            :clear-on-select="false"
                            :show-labels="false"
                            :allow-empty="true"
                            :disabled="!selectedDepartmentId || loadingEmployees"
                            :loading="loadingEmployees"
                            :placeholder="employeeFilterPlaceholder"
                            @update:model-value="onEmployeeFilterChange"
                        />
                        <button
                            v-if="selectedEmployee"
                            type="button"
                            class="hre-select__clear"
                            aria-label="Clear employee filter"
                            @click="clearEmployeeFilter"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <small v-if="employeesError" class="hre-filter__error">
                        {{ employeesError }}
                    </small>
                </div>

                <div class="hre-filter hre-filter--actions">
                    <span class="hre-count">{{ recordSummary }}</span>
                    <button
                        v-if="hasFilters"
                        type="button"
                        class="hre-btn-ghost"
                        @click="clearFilters"
                    >
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="hre-content">
            <div v-if="loading" class="hre-skeleton">
                <div
                    class="hre-skeleton__row"
                    v-for="n in 6"
                    :key="n"
                ></div>
            </div>

            <div v-else-if="listError" class="hre-state hre-state--error">
                <div class="hre-state__icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="hre-state__title">Could not load HR events</h3>
                <p class="hre-state__text">{{ listError }}</p>
                <button
                    type="button"
                    class="hre-btn-ghost"
                    @click="loadEvents(1)"
                >
                    Try Again
                </button>
            </div>

            <div v-else-if="!hasRows" class="hre-state">
                <div class="hre-state__icon">
                    <i class="fa-regular fa-file-lines"></i>
                </div>
                <h3 class="hre-state__title">No HR events found</h3>
                <p class="hre-state__text">
                    {{
                        hasFilters
                            ? "No records match the selected filters."
                            : "Create the first HR event to get started."
                    }}
                </p>
                <button
                    v-if="hasFilters"
                    type="button"
                    class="hre-btn-ghost"
                    @click="clearFilters"
                >
                    Clear Filters
                </button>
                <button
                    v-else
                    type="button"
                    class="hre-btn-ghost"
                    @click="openCreateModal"
                >
                    New Event
                </button>
            </div>

            <div v-else class="hre-table-wrap">
                <table class="hre-table">
                    <thead>
                        <tr>
                            <th class="hre-th--serial">Serial</th>
                            <th>Employee Name</th>
                            <th>Department Name</th>
                            <th>Event Name</th>
                            <th>Event Date</th>
                            <th>Description</th>
                            <th class="hre-th--actions">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in tableRows" :key="row.id">
                            <td class="hre-td--serial">
                                {{ serialNumber(index) }}
                            </td>
                            <td>
                                <span class="hre-strong">
                                    {{ row.employee_name || "—" }}
                                </span>
                                <span
                                    v-if="row.employee_code"
                                    class="hre-muted hre-muted--block"
                                >
                                    {{ row.employee_code }}
                                </span>
                            </td>
                            <td>
                                {{ row.department_name || "—" }}
                            </td>
                            <td>
                                <span
                                    class="hre-badge"
                                    :class="eventBadgeClass(row.event_name)"
                                >
                                    {{ row.event_name || "—" }}
                                </span>
                            </td>
                            <td class="hre-td--date">
                                {{ formatDate(row.event_date) }}
                            </td>
                            <td
                                class="hre-td--description"
                                :title="row.event_details || ''"
                            >
                                {{ truncate(row.event_details) }}
                            </td>
                            <td>
                                <div class="hre-row-actions">
                                    <button
                                        type="button"
                                        class="hre-icon-btn"
                                        title="View Event"
                                        aria-label="View event"
                                        @click="openViewModal(row)"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="hre-icon-btn hre-icon-btn--edit"
                                        title="Edit Event"
                                        aria-label="Edit event"
                                        @click="openEditModal(row)"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="hre-pagination"
                v-if="!loading && hasRows && lastPage > 1"
            >
                <Bootstrap4Pagination
                    :data="pagination"
                    :limit="1"
                    @pagination-change-page="onPageChange"
                />
            </div>
        </div>

        <HrEventModal
            v-if="showModal"
            :key="`${modalMode}-${modalEvent ? modalEvent.id : 'new'}`"
            :mode="modalMode"
            :event-data="modalEvent"
            :preselect="modalPreselect"
            @close="closeModal"
            @saved="onModalSaved"
        />
    </div>
</template>

<style scoped>
.hre-page {
    padding: 8px 2px 24px;
}

.hre-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.hre-heading__title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -0.3px;
}

.hre-heading__subtitle {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 13px;
    max-width: 640px;
}

.hre-btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #1a7f5a;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13.5px;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.08);
    transition:
        background 0.2s ease,
        transform 0.15s ease,
        box-shadow 0.2s ease;
}

.hre-btn-add:hover {
    background: #146a4a;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(26, 127, 90, 0.25);
}

.hre-btn-add:active {
    transform: translateY(0);
}

.hre-toolbar {
    background: #ffffff;
    border: 1px solid #e6ebf0;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
    margin-bottom: 18px;
}

.hre-filters {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    flex-wrap: wrap;
}

.hre-filter {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 230px;
}

.hre-filter--grow {
    flex: 1 1 260px;
    max-width: 340px;
}

.hre-filter--actions {
    flex-direction: row;
    align-items: center;
    gap: 12px;
    min-width: 0;
    margin-left: auto;
    padding-top: 20px;
}

.hre-filter__label {
    font-size: 11.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #64748b;
}

.hre-filter__error {
    color: #dc2626;
    font-size: 11.5px;
    font-weight: 500;
}

.hre-select {
    position: relative;
}

.hre-select__clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;
    width: 22px;
    height: 22px;
    border: none;
    border-radius: 6px;
    background: #e2e8f0;
    color: #475569;
    font-size: 11px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}

.hre-select__clear:hover {
    background: #dc2626;
    color: #ffffff;
}

.hre-select :deep(.multiselect__tags) {
    min-height: 40px !important;
    height: auto !important;
    padding: 5px 36px 5px 10px !important;
    border: 1px solid #d3dbe4 !important;
    border-radius: 9px !important;
    background: #ffffff;
}

.hre-select :deep(.multiselect__tags:focus-within) {
    border-color: #1a7f5a !important;
    box-shadow: 0 0 0 3px rgba(26, 127, 90, 0.12);
}

.hre-select :deep(.multiselect__single) {
    font-size: 13.5px;
    color: #1f2937;
    line-height: 26px !important;
    padding: 0 !important;
    margin: 0 !important;
}

.hre-select :deep(.multiselect__input) {
    font-size: 13.5px;
    color: #1f2937;
    padding: 0 !important;
    margin: 0 !important;
}

.hre-select :deep(.multiselect__placeholder) {
    color: #94a3b8 !important;
    font-size: 13.5px;
    line-height: 26px !important;
    padding: 0 !important;
    margin: 0 !important;
}

.hre-select :deep(.multiselect__select) {
    width: 30px;
}

.hre-select :deep(.multiselect__select::before) {
    display: none;
}

.hre-select :deep(.multiselect__content-wrapper) {
    max-height: 260px;
    margin-top: 4px;
    border-radius: 10px;
    border: 1px solid #e6ebf0;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
}

.hre-select :deep(.multiselect__option) {
    font-size: 13.5px;
    padding: 8px 12px;
}

.hre-select :deep(.multiselect__option--highlighted) {
    background: #eef7f3;
    color: #146a4a;
}

.hre-select :deep(.multiselect__option--selected) {
    background: #e6f4ee;
    color: #146a4a;
    font-weight: 600;
}

.hre-select :deep(.multiselect--disabled .multiselect__tags) {
    background: #f8fafc;
    cursor: not-allowed;
}

.hre-count {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 7px 14px;
    white-space: nowrap;
}

.hre-btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    border: 1px solid #d3dbe4;
    background: #ffffff;
    color: #475569;
    border-radius: 9px;
    font-weight: 600;
    font-size: 12.5px;
    cursor: pointer;
    white-space: nowrap;
    transition:
        border-color 0.15s ease,
        color 0.15s ease,
        background 0.15s ease;
}

.hre-btn-ghost:hover {
    border-color: #1a7f5a;
    color: #1a7f5a;
    background: #ffffff;
}

.hre-content {
    min-height: 320px;
}

.hre-table-wrap {
    background: #ffffff;
    border: 1px solid #e6ebf0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
    overflow-x: auto;
}

.hre-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.hre-table thead th {
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    text-align: left;
    padding: 12px 14px;
    border-bottom: 1px solid #e6ebf0;
    white-space: nowrap;
}

.hre-th--serial {
    width: 78px;
}

.hre-th--actions {
    width: 108px;
}

.hre-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
}

.hre-table tbody tr:last-child td {
    border-bottom: none;
}

.hre-table tbody tr {
    transition: background 0.15s ease;
}

.hre-table tbody tr:hover {
    background: #f8fafc;
}

.hre-td--serial {
    color: #94a3b8;
    font-weight: 600;
}

.hre-td--date {
    white-space: nowrap;
    color: #475569;
    font-weight: 500;
}

.hre-td--description {
    max-width: 280px;
    color: #64748b;
}

.hre-strong {
    font-weight: 600;
    color: #1f2937;
}

.hre-muted {
    color: #94a3b8;
    font-size: 11.5px;
    font-weight: 500;
}

.hre-muted--block {
    display: block;
    margin-top: 2px;
}

.hre-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 11px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.2px;
    white-space: nowrap;
}

.hre-badge--danger {
    color: #b91c1c;
    background: #fef2f2;
}

.hre-badge--warning {
    color: #b45309;
    background: #fffbeb;
}

.hre-badge--success {
    color: #15803d;
    background: #ecfdf3;
}

.hre-badge--info {
    color: #1d4ed8;
    background: #eff6ff;
}

.hre-badge--neutral {
    color: #475569;
    background: #f2f4f7;
}

.hre-row-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.hre-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    border-radius: 8px;
    font-size: 12.5px;
    cursor: pointer;
    transition:
        border-color 0.15s ease,
        color 0.15s ease,
        background 0.15s ease;
}

.hre-icon-btn:hover {
    border-color: #1a7f5a;
    color: #1a7f5a;
    background: #ffffff;
}

.hre-icon-btn--edit:hover {
    border-color: #2563eb;
    color: #2563eb;
}

.hre-state {
    text-align: center;
    background: #ffffff;
    border: 1px dashed #d3dbe4;
    border-radius: 14px;
    padding: 48px 20px;
}

.hre-state--error {
    border-style: solid;
    border-color: #fecaca;
}

.hre-state__icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    font-size: 24px;
}

.hre-state--error .hre-state__icon {
    background: #fef2f2;
    color: #dc2626;
}

.hre-state__title {
    margin: 0;
    font-size: 17px;
    color: #1f2937;
    font-weight: 700;
}

.hre-state__text {
    margin: 6px 0 18px;
    font-size: 13px;
    color: #64748b;
}

.hre-skeleton {
    background: #ffffff;
    border: 1px solid #e6ebf0;
    border-radius: 14px;
    overflow: hidden;
}

.hre-skeleton__row {
    height: 46px;
    border-bottom: 1px solid #f1f5f9;
    background: linear-gradient(90deg, #eef2f6 25%, #f7fafc 50%, #eef2f6 75%);
    background-size: 200% 100%;
    animation: hre-shimmer 1.4s infinite;
}

.hre-skeleton__row:last-child {
    border-bottom: none;
}

@keyframes hre-shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.hre-pagination {
    display: flex;
    justify-content: center;
    margin-top: 22px;
}

.hre-pagination :deep(.pagination) {
    margin: 0;
    justify-content: center;
    flex-wrap: wrap;
    gap: 2px;
}

.hre-pagination :deep(.page-link) {
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    padding: 7px 13px;
    border-radius: 8px;
    margin: 0 2px;
    background: #ffffff;
    transition:
        border-color 0.15s ease,
        color 0.15s ease;
}

.hre-pagination :deep(.page-link:hover) {
    border-color: #1a7f5a;
    color: #1a7f5a;
    background: #ffffff;
}

.hre-pagination :deep(.active .page-link) {
    background: #1a7f5a;
    border-color: #1a7f5a;
    color: #ffffff;
}

.hre-pagination :deep(.disabled .page-link) {
    color: #cbd5e1;
    background: #f8fafc;
}

@media (max-width: 992px) {
    .hre-filter {
        min-width: 200px;
    }

    .hre-filter--actions {
        margin-left: 0;
        padding-top: 0;
    }
}

@media (max-width: 768px) {
    .hre-filters {
        width: 100%;
    }

    .hre-filter,
    .hre-filter--grow {
        flex: 1 1 100%;
        max-width: none;
        width: 100%;
    }

    .hre-filter--actions {
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .hre-td--description {
        max-width: 200px;
    }
}

@media (max-width: 480px) {
    .hre-heading {
        flex-direction: column;
    }

    .hre-btn-add {
        width: 100%;
        justify-content: center;
    }
}
</style>
