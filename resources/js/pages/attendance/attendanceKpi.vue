<script setup>
import { ref, computed, onMounted } from "vue";
import api from "@/api";
import { useStore } from "vuex";
import Swal from "sweetalert2";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

const store = useStore();
const user = store.getters.getUser;

const departments = ref([]);
const employees = ref([]);

const selectedDepartment = ref("");
const selectedEmployee = ref(null);
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());

const kpiRows = ref([]);
const loading = ref(false);
const errorMessage = ref("");

const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

const years = computed(() => {
    const current = new Date().getFullYear();
    const list = [];
    for (let year = current; year >= current - 5; year--) {
        list.push(year);
    }
    return list;
});

const activeFilterText = computed(() => {
    const parts = [];
    const dept = departments.value.find(
        (item) =>
            String(item.department_id) === String(selectedDepartment.value),
    );
    if (dept) parts.push(dept.department_name);
    if (selectedEmployee.value?.Full_Name)
        parts.push(selectedEmployee.value.Full_Name);
    parts.push(monthNames[selectedMonth.value - 1]);
    parts.push(selectedYear.value);
    return parts.join(" \u2022 ");
});

const toNumber = (value) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
};

const parseDateParts = (value) => {
    if (!value) return null;
    const parts = String(value).slice(0, 10).split("-");
    if (parts.length !== 3) return null;
    const [y, m, d] = parts.map(Number);
    if (!y || !m || !d) return null;
    return { y, m, d };
};

const isFridayDate = (value) => {
    const parts = parseDateParts(value);
    if (!parts) return false;
    return new Date(parts.y, parts.m - 1, parts.d).getDay() === 5;
};

const classifyStatus = (status) => {
    const normalized = String(status ?? "")
        .trim()
        .toLowerCase();
    if (!normalized) return "absent";
    if (normalized.includes("holiday") || normalized.includes("weekend"))
        return "holiday";
    if (normalized.includes("early")) return "earlyLeave";
    if (normalized.includes("late")) return "late";
    if (normalized.includes("leave")) return "onLeave";
    if (
        normalized.includes("present") ||
        normalized.includes("manual") ||
        normalized.includes("home")
    )
        return "present";
    return "absent";
};

const unwrapPayload = (payload) => {
    if (Array.isArray(payload)) return payload;
    if (!payload || typeof payload !== "object") return [];
    if (Array.isArray(payload.data)) return payload.data;
    if (Array.isArray(payload.rows)) return payload.rows;
    if (payload.data && Array.isArray(payload.data.data))
        return payload.data.data;
    return [];
};

const looksLikeRawRecords = (records) =>
    records.some(
        (record) =>
            record &&
            (record.date !== undefined ||
                record.status !== undefined ||
                record.attendance_status !== undefined),
    );

const filterByPeriod = (records) =>
    records.filter((record) => {
        const parts = parseDateParts(record.date);
        if (!parts) return true;
        if (
            selectedYear.value &&
            Number(parts.y) !== Number(selectedYear.value)
        )
            return false;
        if (
            selectedMonth.value &&
            Number(parts.m) !== Number(selectedMonth.value)
        )
            return false;
        return true;
    });

const aggregateRawRecords = (records) => {
    const grouped = new Map();

    records.forEach((record) => {
        const rawId =
            record.employee_id ??
            record.employeeId ??
            record.emp_id ??
            record.EID ??
            record.id;
        if (rawId === undefined || rawId === null) return;

        const key = String(rawId);
        if (!grouped.has(key)) {
            grouped.set(key, {
                employeeId: rawId,
                employeeName:
                    record.employee_name ??
                    record.Full_Name ??
                    record.full_name ??
                    record.name ??
                    `Employee ${rawId}`,
                employeeCode:
                    record.employee_code ??
                    record.Employee_Id ??
                    record.official_id ??
                    "",
                departmentName:
                    record.department_name ?? record.department ?? "-",
                present: 0,
                late: 0,
                earlyLeave: 0,
                absent: 0,
                onLeave: 0,
                holidays: 0,
                workDays: 0,
            });
        }

        const row = grouped.get(key);
        const name =
            record.employee_name ??
            record.Full_Name ??
            record.full_name ??
            record.name;
        if (name) row.employeeName = name;
        const deptName = record.department_name ?? record.department;
        if (deptName) row.departmentName = deptName;

        const bucket = classifyStatus(
            record.status ?? record.attendance_status,
        );

        if (isFridayDate(record.date) || bucket === "holiday") {
            row.holidays += 1;
            return;
        }

        row.workDays += 1;
        if (bucket === "present") row.present += 1;
        else if (bucket === "late") row.late += 1;
        else if (bucket === "earlyLeave") row.earlyLeave += 1;
        else if (bucket === "onLeave") row.onLeave += 1;
        else row.absent += 1;
    });

    return Array.from(grouped.values());
};

const mapAggregatedRecords = (records) =>
    records.map((record) => ({
        employeeId: record.employee_id ?? record.employeeId ?? record.id,
        employeeName:
            record.employee_name ??
            record.Full_Name ??
            record.full_name ??
            record.name ??
            "-",
        employeeCode:
            record.employee_code ??
            record.Employee_Id ??
            record.official_id ??
            "",
        departmentName: record.department_name ?? record.department ?? "-",
        present: record.present ?? 0,
        late: record.late ?? 0,
        earlyLeave: record.early_leave ?? record.earlyLeave ?? 0,
        absent: record.absent ?? 0,
        onLeave: record.on_leave ?? record.onLeave ?? 0,
        holidays: record.holidays ?? record.holiday ?? 0,
        workDays:
            record.total_work_days ?? record.work_days ?? record.workDays ?? 0,
        totalMarks: record.total_marks ?? record.totalMarks,
    }));

const finalizeRow = (row) => {
    const present = toNumber(row.present);
    const late = toNumber(row.late);
    const earlyLeave = toNumber(row.earlyLeave);
    const absent = toNumber(row.absent);
    const onLeave = toNumber(row.onLeave);
    const totalMarks =
        row.totalMarks !== undefined && row.totalMarks !== null
            ? toNumber(row.totalMarks)
            : present * 1 + late * 0.5 + earlyLeave * 0.5 + onLeave * 1;

    return {
        ...row,
        present,
        late,
        earlyLeave,
        absent,
        onLeave,
        holidays: toNumber(row.holidays),
        workDays: toNumber(row.workDays),
        totalMarks,
    };
};

const normalizeResponse = (payload) => {
    const records = unwrapPayload(payload);
    if (!records.length) return [];

    const isRaw = looksLikeRawRecords(records);
    const source = isRaw ? filterByPeriod(records) : records;
    const mapped = isRaw
        ? aggregateRawRecords(source)
        : mapAggregatedRecords(source);

    return mapped
        .map(finalizeRow)
        .sort((a, b) =>
            String(a.employeeName).localeCompare(String(b.employeeName)),
        );
};

const formatMarks = (value) => toNumber(value).toFixed(1);

const totalWorkDays = computed(() =>
    kpiRows.value.reduce(
        (max, row) => Math.max(max, toNumber(row.workDays)),
        0,
    ),
);

const totalHolidays = computed(() =>
    kpiRows.value.reduce(
        (max, row) => Math.max(max, toNumber(row.holidays)),
        0,
    ),
);

const getDepartments = async () => {
    try {
        const response = await api.get(`/department-list/${user.id}`);
        departments.value = response.data || [];
    } catch (error) {
        console.error("Error loading departments:", error);
    }
};

const getEmployees = async (deptID) => {
    if (!deptID) {
        employees.value = [];
        return;
    }
    try {
        const response = await api.get(`/adjustment-employees/${deptID}`);
        employees.value = response.data || [];
    } catch (error) {
        console.error("Error loading employees:", error);
    }
};

const getKpiData = async () => {
    loading.value = true;
    errorMessage.value = "";
    try {
        const response = await api.get("/attendance-kpi", {
            params: {
                department_id: selectedDepartment.value || undefined,
                employee_id: selectedEmployee.value?.id ?? undefined,
                month: selectedMonth.value || undefined,
                year: selectedYear.value || undefined,
            },
        });
        kpiRows.value = normalizeResponse(response.data);
    } catch (error) {
        errorMessage.value =
            error?.response?.data?.message ||
            "Unable to load attendance KPI data.";
        kpiRows.value = [];
        Swal.fire("Error", errorMessage.value, "error");
    } finally {
        loading.value = false;
    }
};

const onDepartmentChange = async () => {
    selectedEmployee.value = null;
    await getEmployees(selectedDepartment.value);
    getKpiData();
};

const onEmployeeChange = () => {
    getKpiData();
};

const onPeriodChange = () => {
    getKpiData();
};

const resetFilters = async () => {
    selectedDepartment.value = "";
    selectedEmployee.value = null;
    employees.value = [];
    selectedMonth.value = new Date().getMonth() + 1;
    selectedYear.value = new Date().getFullYear();
    getKpiData();
};

onMounted(() => {
    getDepartments();
    getKpiData();
});
</script>

<template>
    <div class="kpi-page">
        <header class="header-bar">
            <div class="header-content">
                <div class="title-group">
                    <i class="fa-solid fa-chart-column header-icon"></i>
                    <div>
                        <h1 class="header-title">Attendance KPI</h1>
                        <p class="header-subtitle">
                            Monthly attendance performance summary
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="btn btn-primary btn-refresh"
                    :disabled="loading"
                    @click="getKpiData"
                >
                    <i
                        class="fa-solid fa-rotate"
                        :class="{ spin: loading }"
                    ></i>
                    <span>{{ loading ? "Loading..." : "Refresh" }}</span>
                </button>
            </div>
        </header>

        <div class="main-body">
            <div class="filter-card">
                <div class="filter-grid">
                    <div class="field-group">
                        <label class="field-label">
                            <i class="fa-solid fa-building"></i> Department
                        </label>
                        <select
                            v-model="selectedDepartment"
                            class="form-select custom-input"
                            @change="onDepartmentChange"
                        >
                            <option value="">All Departments</option>
                            <option
                                v-for="dept in departments"
                                :key="dept.department_id"
                                :value="dept.department_id"
                            >
                                {{ dept.department_name }}
                            </option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label">
                            <i class="fa-solid fa-user"></i> Employee
                        </label>
                        <Multiselect
                            v-model="selectedEmployee"
                            :options="employees"
                            :disabled="!selectedDepartment"
                            label="Full_Name"
                            track-by="id"
                            placeholder="All Employees"
                            class="multiselect-custom"
                            @select="onEmployeeChange"
                            @remove="onEmployeeChange"
                        />
                    </div>

                    <div class="field-group">
                        <label class="field-label">
                            <i class="fa-solid fa-calendar-days"></i> Month
                        </label>
                        <select
                            v-model.number="selectedMonth"
                            class="form-select custom-input"
                            @change="onPeriodChange"
                        >
                            <option
                                v-for="(month, index) in monthNames"
                                :key="month"
                                :value="index + 1"
                            >
                                {{ month }}
                            </option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label">
                            <i class="fa-solid fa-calendar"></i> Year
                        </label>
                        <select
                            v-model.number="selectedYear"
                            class="form-select custom-input"
                            @change="onPeriodChange"
                        >
                            <option
                                v-for="year in years"
                                :key="year"
                                :value="year"
                            >
                                {{ year }}
                            </option>
                        </select>
                    </div>

                    <div class="field-group field-actions">
                        <label class="field-label">&nbsp;</label>
                        <button
                            type="button"
                            class="btn btn-reset"
                            @click="resetFilters"
                        >
                            <i class="fa-solid fa-filter-circle-xmark"></i>
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <div class="legend-card">
                <div class="legend-title">
                    <i class="fa-solid fa-circle-info"></i>
                    Marking Legend
                </div>
                <div class="legend-items">
                    <span class="legend-item">
                        <span class="legend-dot dot-present"></span>
                        Present: 1.0
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot dot-late"></span>
                        Late: 0.5
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot dot-early"></span>
                        Early Leave: 0.5
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot dot-leave"></span>
                        On Leave: 1.0
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot dot-absent"></span>
                        Absent: 0.0
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot dot-holiday"></span>
                        Friday / Holiday: Excluded from working days and marks
                    </span>
                </div>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon summary-icon-work">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Total Work Days</span>
                        <span class="summary-value">{{ totalWorkDays }}</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon summary-icon-holiday">
                        <i class="fa-solid fa-umbrella-beach"></i>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Total Holidays</span>
                        <span class="summary-value">{{ totalHolidays }}</span>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-toolbar">
                    <div class="count-badge">
                        <i class="fa-solid fa-users"></i>
                        <span
                            >Employees:
                            <strong>{{ kpiRows.length }}</strong></span
                        >
                    </div>
                    <div class="active-filter">
                        <i class="fa-solid fa-sliders"></i>
                        {{ activeFilterText }}
                    </div>
                </div>

                <div v-if="loading" class="state-box">
                    <i class="fa-solid fa-spinner spin state-icon"></i>
                    <p>Loading attendance KPI data...</p>
                </div>

                <div v-else-if="errorMessage" class="state-box error-box">
                    <i class="fa-solid fa-triangle-exclamation state-icon"></i>
                    <p>{{ errorMessage }}</p>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="getKpiData"
                    >
                        Try again
                    </button>
                </div>

                <div v-else class="table-scroll-wrapper">
                    <table class="kpi-table">
                        <thead>
                            <tr>
                                <th class="text-center">SN</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th class="text-center">Present</th>
                                <th class="text-center">Late</th>
                                <th class="text-center">Early Leave</th>
                                <th class="text-center">Absent</th>
                                <th class="text-center">Total Marks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, index) in kpiRows"
                                :key="row.employeeId ?? index"
                            >
                                <td class="text-center col-sn">
                                    {{ index + 1 }}
                                </td>
                                <td class="col-employee">
                                    <span class="employee-name">
                                        {{ row.employeeName || "-" }}
                                    </span>
                                    <span
                                        v-if="row.employeeCode"
                                        class="employee-code"
                                    >
                                        {{ row.employeeCode }}
                                    </span>
                                </td>
                                <td class="col-department">
                                    {{ row.departmentName || "-" }}
                                </td>
                                <td class="text-center cell-present">
                                    {{ row.present }}
                                </td>
                                <td class="text-center cell-late">
                                    {{ row.late }}
                                </td>
                                <td class="text-center cell-early">
                                    {{ row.earlyLeave }}
                                </td>
                                <td class="text-center cell-absent">
                                    {{ row.absent }}
                                </td>
                                <td class="text-center col-marks">
                                    {{ formatMarks(row.totalMarks) }}
                                </td>
                            </tr>

                            <tr v-if="kpiRows.length === 0">
                                <td colspan="8" class="empty-cell">
                                    <div class="empty-box">
                                        <i
                                            class="fa-solid fa-folder-open empty-icon"
                                        ></i>
                                        <p>
                                            No attendance KPI data available for
                                            the selected filters.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.kpi-page {
    background-color: #f1f5f9;
    min-height: 100vh;
    font-family:
        -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial,
        sans-serif;
    color: #1e293b;
}

.header-bar {
    background-color: #ffffff;
    padding: 12px 16px;
    border-bottom: 1px solid #cbd5e1;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.title-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    font-size: 1.5rem;
    color: #2563eb;
    background: #eff6ff;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #bfdbfe;
}

.header-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.header-subtitle {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0;
}

.btn-refresh {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #2563eb;
    border: 1px solid #1d4ed8;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 16px;
    border-radius: 6px;
    color: #ffffff;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-refresh:hover:not(:disabled) {
    background-color: #1d4ed8;
}

.btn-refresh:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.main-body {
    padding: 16px;
}

.filter-card {
    margin-bottom: 16px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 12px;
    align-items: end;
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.field-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.field-label i {
    color: #64748b;
    margin-right: 4px;
}

.custom-input {
    height: 34px;
    border-radius: 4px;
    border: 1px solid #94a3b8;
    font-size: 0.85rem;
    padding: 4px 8px;
    color: #0f172a;
    width: 100%;
    background-color: #ffffff;
}

.custom-input:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.btn-reset {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 34px;
    border-radius: 4px;
    border: 1px solid #94a3b8;
    background-color: #ffffff;
    color: #334155;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-reset:hover {
    background-color: #f1f5f9;
    border-color: #64748b;
}

:deep(.multiselect-custom .multiselect__tags) {
    min-height: 34px !important;
    height: 34px;
    padding: 2px 8px;
    border-radius: 4px;
    border: 1px solid #94a3b8;
    display: flex;
    align-items: center;
    font-size: 0.85rem;
}

:deep(.multiselect-custom .multiselect__placeholder) {
    color: #94a3b8;
    font-size: 0.85rem;
    margin-bottom: 0;
}

:deep(.multiselect-custom .multiselect__single),
:deep(.multiselect-custom .multiselect__input) {
    font-size: 0.85rem;
    margin-bottom: 0;
    line-height: 26px;
}

:deep(.multiselect-custom .multiselect__select) {
    height: 32px;
}

:deep(.multiselect-custom.multiselect--disabled) {
    opacity: 0.6;
}

.legend-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 10px 14px;
    margin-bottom: 16px;
}

.legend-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.legend-title i {
    color: #2563eb;
    margin-right: 4px;
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 18px;
}

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: #475569;
}

.legend-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    display: inline-block;
}

.dot-present {
    background-color: #16a34a;
}

.dot-late {
    background-color: #d97706;
}

.dot-early {
    background-color: #ea580c;
}

.dot-leave {
    background-color: #2563eb;
}

.dot-absent {
    background-color: #dc2626;
}

.dot-holiday {
    background-color: #94a3b8;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.summary-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 12px 16px;
}

.summary-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 8px;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.summary-icon-work {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

.summary-icon-holiday {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #cbd5e1;
}

.summary-info {
    display: flex;
    flex-direction: column;
}

.summary-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.table-card {
    background: #ffffff;
    border: 1px solid #94a3b8;
    border-radius: 6px;
    overflow: hidden;
}

.table-toolbar {
    background-color: #f8fafc;
    border-bottom: 1px solid #cbd5e1;
    padding: 8px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.count-badge {
    font-size: 0.8rem;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 6px;
}

.count-badge i {
    color: #2563eb;
}

.active-filter {
    font-size: 0.78rem;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.table-scroll-wrapper {
    overflow-x: auto;
    width: 100%;
}

.kpi-table {
    width: 100%;
    min-width: 760px;
    margin-bottom: 0;
    border-collapse: collapse !important;
    font-size: 0.825rem;
}

.kpi-table th,
.kpi-table td {
    border: 1px solid #cbd5e1 !important;
    padding: 7px 10px !important;
    vertical-align: middle;
}

.kpi-table thead th {
    background-color: #334155 !important;
    color: #ffffff !important;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.kpi-table tbody tr:nth-child(even) {
    background-color: #f8fafc;
}

.kpi-table tbody tr:hover {
    background-color: #e2e8f0 !important;
}

.text-center {
    text-align: center;
}

.col-sn {
    font-weight: 600;
    color: #64748b;
}

.col-employee {
    min-width: 180px;
}

.employee-name {
    display: block;
    font-weight: 600;
    color: #0f172a;
}

.employee-code {
    display: block;
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.72rem;
    color: #64748b;
}

.col-department {
    color: #334155;
    min-width: 140px;
}

.cell-present {
    font-weight: 700;
    color: #166534;
}

.cell-late {
    font-weight: 700;
    color: #b45309;
}

.cell-early {
    font-weight: 700;
    color: #c2410c;
}

.cell-absent {
    font-weight: 700;
    color: #b91c1c;
}

.col-marks {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 700;
    color: #1e40af;
    background-color: #eff6ff;
}

.state-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 48px 16px;
    color: #64748b;
    background-color: #ffffff;
}

.state-box p {
    margin: 0;
    font-size: 0.9rem;
}

.state-icon {
    font-size: 1.8rem;
    color: #2563eb;
}

.error-box {
    color: #b91c1c;
}

.error-box .state-icon {
    color: #dc2626;
}

.spin {
    animation: spin 0.9s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.empty-cell {
    padding: 36px !important;
    background-color: #ffffff !important;
}

.empty-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #64748b;
}

.empty-icon {
    font-size: 2rem;
    color: #94a3b8;
}

.empty-box p {
    margin: 0;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .main-body {
        padding: 8px;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .field-actions {
        align-items: stretch;
    }

    .btn-refresh {
        width: 100%;
        justify-content: center;
    }

    .kpi-table {
        font-size: 0.775rem;
    }

    .kpi-table th,
    .kpi-table td {
        padding: 5px 7px !important;
    }
}
</style>
