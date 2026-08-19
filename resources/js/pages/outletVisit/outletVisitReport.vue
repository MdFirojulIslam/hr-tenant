<template>
    <div class="report-wrapper p-3 p-md-4">
        <!-- Top Header & Global Controls -->
        <div class="card header-card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
                >
                    <div class="d-flex align-items-center gap-3">
                        <div
                            class="icon-shape bg-success-subtle text-success rounded-3 p-3 d-flex align-items-center justify-content-center"
                        >
                            <i class="fa-solid fa-calendar-days fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 font-weight-bold text-dark">
                                Schedule Outlet Visit Report
                            </h4>
                            <p class="text-muted small mb-0">
                                Track and manage employee outlet visits and
                                schedule analytics.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="control-group">
                            <label class="form-label-xs">Year</label>
                            <select
                                v-model="selectedYear"
                                class="form-select form-select-sm custom-select"
                            >
                                <option
                                    v-for="y in yearOptions"
                                    :key="y"
                                    :value="y"
                                >
                                    {{ y }}
                                </option>
                            </select>
                        </div>
                        <div class="control-group">
                            <label class="form-label-xs">Month</label>
                            <select
                                v-model="selectedMonth"
                                class="form-select form-select-sm custom-select"
                            >
                                <option
                                    v-for="(m, idx) in monthNames"
                                    :key="idx"
                                    :value="idx + 1"
                                >
                                    {{ m }}
                                </option>
                            </select>
                        </div>
                        <div class="align-self-end">
                            <button
                                class="btn btn-sm btn-success btn-download d-inline-flex align-items-center gap-2 px-3"
                                @click="downloadReport"
                                :disabled="downloading"
                            >
                                <i
                                    class="fa-solid"
                                    :class="
                                        downloading
                                            ? 'fa-spinner fa-spin'
                                            : 'fa-download'
                                    "
                                ></i>
                                <span>{{
                                    downloading ? "Exporting..." : "Export CSV"
                                }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls Panel -->
        <div class="card filter-card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center mb-3">
                    <i class="fa-solid fa-filter text-muted me-2 small"></i>
                    <span
                        class="text-uppercase text-muted fw-bold extra-small tracking-wider"
                        >Filter Results</span
                    >
                </div>
                <div class="row g-3">
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label class="form-label-xs">Date</label>
                        <input
                            type="date"
                            v-model="selectedDate"
                            class="form-control form-control-sm custom-input"
                            :min="dateMin"
                            :max="dateMax"
                        />
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label class="form-label-xs">Day</label>
                        <input
                            type="text"
                            class="form-control form-control-sm custom-input bg-light"
                            :value="selectedDayName"
                            readonly
                            placeholder="Day"
                        />
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label class="form-label-xs">Employee Name</label>
                        <EmployeeName
                            v-model:employee_id="selectedEmployeeId"
                        />
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label class="form-label-xs">Department Name</label>
                        <BaseDept
                            v-model:departmentID="selectedDepartmentId"
                            :userID="userID"
                        />
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label class="form-label-xs">Outlet Code</label>
                        <Multiselect
                            v-model="selectedOutletCode"
                            :options="outletCodeOptions"
                            label="code"
                            track-by="code"
                            :searchable="true"
                            :close-on-select="true"
                            :clear-on-select="false"
                            :show-labels="false"
                            :allow-empty="true"
                            placeholder="All Outlets"
                            class="multiselect-outlet-filter"
                        />
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label class="form-label-xs">Status</label>
                        <select
                            v-model="selectedStatus"
                            class="form-select form-select-sm custom-select"
                        >
                            <option value="">All Statuses</option>
                            <option value="Visited">Visited</option>
                            <option value="Not Visited">Not Visited</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div
            class="card table-card border-0 shadow-sm overflow-hidden"
        >
            <div class="table-responsive">
                <table
                    class="table table-hover align-middle mb-0 custom-report-table"
                >
                    <thead>
                        <tr>
                            <th
                                scope="col"
                                class="text-center"
                                style="width: 60px"
                            >
                                #
                            </th>
                            <th scope="col">Employee Name</th>
                            <th scope="col">Department Name</th>
                            <th scope="col">Outlet Code</th>
                            <th scope="col">Outlet Name</th>
                            <th scope="col">Check in Location</th>
                            <th scope="col">Check in Time</th>
                            <th scope="col">Check out Time</th>
                            <th
                                scope="col"
                                class="text-center"
                                style="width: 120px"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="9" class="text-center py-5">
                                <div
                                    class="d-flex flex-column align-items-center justify-content-center"
                                >
                                    <div
                                        class="spinner-border text-success mb-2"
                                        role="status"
                                        style="width: 2rem; height: 2rem"
                                    ></div>
                                    <span class="text-muted small"
                                        >Fetching report data...</span
                                    >
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="filteredReport.length === 0">
                            <td colspan="9" class="text-center py-5">
                                <div class="empty-state text-muted">
                                    <i
                                        class="fa-regular fa-folder-open display-6 mb-2 text-secondary opacity-50"
                                    ></i>
                                    <p class="mb-0 small fw-medium">
                                        No schedule visit records found for the
                                        selected filters.
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="(row, idx) in paginatedReport"
                            :key="idx"
                            v-else
                        >
                            <td class="text-center text-muted fw-semibold">
                                {{ (currentPage - 1) * perPage + idx + 1 }}
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    {{ row.employee_name || "-" }}
                                </div>
                            </td>
                            <td>
                                <span
                                    class="badge bg-light text-secondary border fw-normal px-2 py-1"
                                    >{{ row.department || "-" }}</span
                                >
                            </td>
                            <td>
                                <code
                                    class="text-primary bg-primary-subtle px-2 py-1 rounded small"
                                    >{{ row.outlet_code }}</code
                                >
                            </td>
                            <td class="fw-medium text-secondary">
                                {{ row.outlet_name }}
                            </td>
                            <td>
                                <span
                                    class="text-truncate d-inline-block"
                                    style="max-width: 180px"
                                    :title="row.check_in_location"
                                >
                                    <i
                                        class="fa-solid fa-location-dot text-danger me-1 small"
                                        v-if="
                                            row.check_in_location &&
                                            row.check_in_location !== '-'
                                        "
                                    ></i>
                                    {{ row.check_in_location || "-" }}
                                </span>
                            </td>
                            <td>
                                <span class="text-nowrap text-secondary small">
                                    <i
                                        class="fa-regular fa-clock me-1 text-muted"
                                        v-if="
                                            row.check_in && row.check_in !== '-'
                                        "
                                    ></i>
                                    {{ row.check_in || "-" }}
                                </span>
                            </td>
                            <td>
                                <span class="text-nowrap text-secondary small">
                                    <i
                                        class="fa-regular fa-clock me-1 text-muted"
                                        v-if="
                                            row.check_out &&
                                            row.check_out !== '-'
                                        "
                                    ></i>
                                    {{ row.check_out || "-" }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span
                                    class="status-pill d-inline-flex align-items-center gap-1"
                                    :class="
                                        row.status === 'Visited'
                                            ? 'status-visited'
                                            : 'status-not-visited'
                                    "
                                >
                                    <span class="status-dot"></span>
                                    {{ row.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="!loading && filteredReport.length > 0"
                class="d-flex flex-column flex-sm-row justify-content-between align-items-center p-3 gap-2"
            >
                <span class="text-muted small">
                    Showing {{ paginationFrom }} to {{ paginationTo }} of
                    {{ filteredReport.length }} records
                </span>
                <div class="d-flex align-items-center gap-1">
                    <button
                        class="btn btn-sm btn-outline-success"
                        @click="goToPage(1)"
                        :disabled="currentPage === 1"
                    >
                        <i class="fa-solid fa-angles-left"></i>
                    </button>
                    <button
                        class="btn btn-sm btn-outline-success"
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button
                        v-for="page in visiblePages()"
                        :key="page"
                        class="btn btn-sm"
                        :class="
                            page === currentPage
                                ? 'btn-success'
                                : 'btn-outline-success'
                        "
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </button>
                    <button
                        class="btn btn-sm btn-outline-success"
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === lastPage"
                    >
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button
                        class="btn btn-sm btn-outline-success"
                        @click="goToPage(lastPage)"
                        :disabled="currentPage === lastPage"
                    >
                        <i class="fa-solid fa-angles-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useStore } from "vuex";
import api from "@/api";
import Swal from "sweetalert2";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import EmployeeName from "@/pages/components/input_field/EmployeeName.vue";
import BaseDept from "@/pages/components/input_field/BaseDept.vue";

const store = useStore();
const user = store.getters.getUser;
const userID = user ? user.id : null;

const loading = ref(false);
const downloading = ref(false);

const now = new Date();
const selectedYear = ref(now.getFullYear());
const selectedMonth = ref(now.getMonth() + 1);

const yearOptions = computed(() => {
    const current = now.getFullYear();
    const years = [];
    for (let y = current - 5; y <= current + 5; y++) {
        years.push(y);
    }
    return years;
});

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

const formatDateISO = (d) => d.toISOString().split("T")[0];
const selectedDate = ref(formatDateISO(now));
const selectedEmployeeId = ref(null);
const selectedDepartmentId = ref(null);
const selectedOutletCode = ref(null);
const selectedStatus = ref("");

const allReportData = ref([]);

// Pagination
const currentPage = ref(1);
const perPage = ref(20);

const dateMin = computed(() => {
    const d = new Date(selectedYear.value, selectedMonth.value - 1, 1);
    return d.toISOString().split("T")[0];
});

const dateMax = computed(() => {
    const d = new Date(selectedYear.value, selectedMonth.value, 0);
    return d.toISOString().split("T")[0];
});

const selectedDayName = computed(() => {
    if (!selectedDate.value) return "";
    const d = new Date(selectedDate.value + "T00:00:00");
    return d.toLocaleDateString("en-US", { weekday: "long" });
});

const parseOutletInfo = (scheduledOutletVisit) => {
    if (!scheduledOutletVisit || scheduledOutletVisit === "-") {
        return { code: "-", name: "-" };
    }
    const separator = " / ";
    const lastIdx = scheduledOutletVisit.lastIndexOf(separator);
    if (lastIdx === -1) {
        return { code: scheduledOutletVisit, name: scheduledOutletVisit };
    }
    return {
        code: scheduledOutletVisit.substring(lastIdx + separator.length).trim(),
        name: scheduledOutletVisit.substring(0, lastIdx).trim(),
    };
};

const enrichedReport = computed(() => {
    return allReportData.value.map((row) => {
        const outlet = parseOutletInfo(row.scheduled_outlet_visit);
        return {
            ...row,
            outlet_code: outlet.code,
            outlet_name: outlet.name,
        };
    });
});

const outletCodeOptions = computed(() => {
    const seen = new Map();
    enrichedReport.value.forEach((row) => {
        if (
            row.outlet_code &&
            row.outlet_code !== "-" &&
            !seen.has(row.outlet_code)
        ) {
            seen.set(row.outlet_code, { code: row.outlet_code });
        }
    });
    return Array.from(seen.values());
});

const filteredReport = computed(() => {
    let data = enrichedReport.value;

    if (selectedDate.value) {
        data = data.filter((r) => r.date_raw === selectedDate.value);
    }
    if (selectedEmployeeId.value != null) {
        data = data.filter(
            (r) => Number(r.employee_id) === Number(selectedEmployeeId.value),
        );
    }
    if (selectedDepartmentId.value != null) {
        data = data.filter(
            (r) =>
                Number(r.department_id) === Number(selectedDepartmentId.value),
        );
    }
    if (selectedOutletCode.value && selectedOutletCode.value.code) {
        data = data.filter(
            (r) => r.outlet_code === selectedOutletCode.value.code,
        );
    }
    if (selectedStatus.value) {
        data = data.filter((r) => r.status === selectedStatus.value);
    }

    return data;
});

const lastPage = computed(() => {
    return Math.max(1, Math.ceil(filteredReport.value.length / perPage.value));
});

const paginatedReport = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredReport.value.slice(start, start + perPage.value);
});

const paginationFrom = computed(() => {
    if (filteredReport.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const paginationTo = computed(() => {
    return Math.min(
        currentPage.value * perPage.value,
        filteredReport.value.length,
    );
});

const visiblePages = () => {
    const pages = [];
    let start = Math.max(1, currentPage.value - 2);
    let end = Math.min(lastPage.value, currentPage.value + 2);

    if (currentPage.value <= 3) {
        start = 1;
        end = Math.min(5, lastPage.value);
    }

    if (currentPage.value >= lastPage.value - 2) {
        start = Math.max(1, lastPage.value - 4);
        end = lastPage.value;
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
};

const goToPage = (page) => {
    if (page < 1 || page > lastPage.value) return;
    currentPage.value = page;
};

// Reset to page 1 when any filter changes
watch(
    [
        selectedDate,
        selectedEmployeeId,
        selectedDepartmentId,
        selectedOutletCode,
        selectedStatus,
    ],
    () => {
        currentPage.value = 1;
    },
);

let fetchSeq = 0;

const fetchReport = async () => {
    const seq = ++fetchSeq;
    loading.value = true;
    try {
        const res = await api.get("/outlet-visit-report", {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });
        if (seq !== fetchSeq) return;
        allReportData.value = res.data?.data?.report ?? [];
    } catch (err) {
        if (seq !== fetchSeq) return;
        const message = err.response?.data?.message || "Failed to load report.";
        Swal.fire("Error", message, "error");
    } finally {
        if (seq === fetchSeq) loading.value = false;
    }
};

watch([selectedYear, selectedMonth], () => {
    selectedEmployeeId.value = null;
    selectedDepartmentId.value = null;
    selectedOutletCode.value = null;
    selectedStatus.value = "";
    currentPage.value = 1;

    if (
        selectedYear.value === now.getFullYear() &&
        selectedMonth.value === now.getMonth() + 1
    ) {
        selectedDate.value = formatDateISO(now);
    } else {
        const firstDay = new Date(
            selectedYear.value,
            selectedMonth.value - 1,
            1,
        );
        selectedDate.value = formatDateISO(firstDay);
    }
    fetchReport();
});

onMounted(() => {
    fetchReport();
});

const downloadReport = async () => {
    downloading.value = true;
    try {
        const res = await api.get("/outlet-visit-report", {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
                download: "csv",
            },
            responseType: "blob",
        });

        const blob = new Blob([res.data], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download =
            "outlet_visit_report_" +
            selectedYear.value +
            "_" +
            String(selectedMonth.value).padStart(2, "0") +
            ".csv";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    } catch (err) {
        const message =
            err.response?.data?.message || "Failed to download report.";
        Swal.fire("Error", message, "error");
    } finally {
        downloading.value = false;
    }
};
</script>

<style scoped>
.report-wrapper {
    background-color: #f8fafc;
    min-height: 100vh;
}

.header-card,
.filter-card,
.table-card {
    border-radius: 12px;
    background: #ffffff;
}

.icon-shape {
    width: 48px;
    height: 48px;
}

.form-label-xs {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-bottom: 4px;
    display: block;
}

.extra-small {
    font-size: 0.7rem;
}

.tracking-wider {
    letter-spacing: 0.05em;
}

.custom-select,
.custom-input {
    border-color: #e2e8f0;
    border-radius: 8px;
    font-size: 0.84rem;
    color: #1e293b;
    padding-top: 0.4rem;
    padding-bottom: 0.4rem;
    transition: all 0.2s ease;
}

.custom-select:focus,
.custom-input:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
}

.btn-download {
    border-radius: 8px;
    font-size: 0.84rem;
    font-weight: 600;
    padding-top: 0.45rem;
    padding-bottom: 0.45rem;
    background-color: #10b981;
    border-color: #10b981;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
    transition: all 0.2s ease;
}

.btn-download:hover:not(:disabled) {
    background-color: #059669;
    border-color: #059669;
    box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
}

.custom-report-table {
    font-size: 0.84rem;
}

.custom-report-table thead th {
    background-color: #0f172a;
    color: #f8fafc;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 14px;
    border: none;
    white-space: nowrap;
}

.custom-report-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
}

.custom-report-table tbody tr {
    transition: background-color 0.15s ease;
}

.custom-report-table tbody tr:hover {
    background-color: #f8fafc;
}

.status-pill {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.status-visited {
    background-color: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.status-visited .status-dot {
    background-color: #10b981;
}

.status-not-visited {
    background-color: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.status-not-visited .status-dot {
    background-color: #ef4444;
}

.multiselect-outlet-filter :deep(.multiselect__tags) {
    min-height: 34px !important;
    padding: 4px 30px 0 10px;
    border-radius: 8px;
    border-color: #e2e8f0;
    font-size: 0.84rem;
}

.multiselect-outlet-filter :deep(.multiselect__placeholder) {
    color: #94a3b8;
    font-size: 0.84rem;
    margin-bottom: 0;
    padding-top: 1px;
}

.multiselect-outlet-filter :deep(.multiselect__single) {
    font-size: 0.84rem;
    margin-bottom: 0;
}

.multiselect-outlet-filter :deep(.multiselect__select) {
    width: 30px;
    height: 32px;
}
</style>
