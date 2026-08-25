<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useStore } from "vuex";
import api from "@/api";
import Swal from "sweetalert2";
import BaseDept from "@/pages/components/input_field/BaseDept.vue";
import BaseDeptEmp from "@/pages/components/input_field/BaseDeptEmp.vue";
import DateField from "@/pages/components/input_field/DateField.vue";
import DayName from "@/pages/components/input_field/DayName.vue";

const store = useStore();
const user = store.getters.getUser;
const userID = user ? user.id : null;

const loading = ref(false);
const downloading = ref(false);

const selectedDepartmentId = ref(null);
const selectedEmployeeId = ref(null);
const selectedTownId = ref(null);

const now = new Date();
const formatDateISO = (d) => d.toISOString().split("T")[0];
const selectedDate = ref(formatDateISO(now));

const selectedDayName = computed(() => {
    if (!selectedDate.value) return "";
    const d = new Date(selectedDate.value + "T00:00:00");
    return d.toLocaleDateString("en-US", { weekday: "long" });
});

const allReportData = ref([]);
const allEmployees = ref([]);
const allDepartments = ref([]);
const allTowns = ref([]);

const getId = (value) => {
    if (
        value === null ||
        value === undefined ||
        value === "" ||
        value === "-"
    ) {
        return null;
    }

    if (typeof value === "object") {
        return (
            value.id ??
            value.employee_id ??
            value.employeeID ??
            value.value ??
            null
        );
    }

    return value;
};

watch(selectedDepartmentId, (newDepartmentId, oldDepartmentId) => {
    const newId = getId(newDepartmentId);
    const oldId = getId(oldDepartmentId);

    if (newId !== oldId) {
        selectedEmployeeId.value = null;
    }
});

const filteredReport = computed(() => {
    const departmentId = getId(selectedDepartmentId.value);
    const employeeId = getId(selectedEmployeeId.value);
    const townId = getId(selectedTownId.value);
    const dateVal = selectedDate.value;

    let data = [...allReportData.value];

    if (departmentId !== null) {
        data = data.filter(
            (row) => Number(row.department_id) === Number(departmentId),
        );
    }

    if (employeeId !== null) {
        data = data.filter(
            (row) => Number(row.eid) === Number(employeeId),
        );
    }

    if (townId !== null) {
        data = data.filter(
            (row) => Number(row.town_id) === Number(townId),
        );
    }

    if (dateVal) {
        data = data.filter(
            (row) => row.date_raw && row.date_raw === dateVal,
        );
    }

    return data;
});

let fetchSeq = 0;

const fetchReport = async () => {
    const seq = ++fetchSeq;
    loading.value = true;
    try {
        const res = await api.get("/meeting-minutes-report");
        if (seq !== fetchSeq) return;
        allReportData.value = res.data?.data?.report ?? [];
        allEmployees.value = res.data?.data?.employees ?? [];
        allDepartments.value = res.data?.data?.departments ?? [];
        allTowns.value = res.data?.data?.towns ?? [];
    } catch (err) {
        if (seq !== fetchSeq) return;
        const message = err.response?.data?.message || "Failed to load report.";
        Swal.fire("Error", message, "error");
    } finally {
        if (seq === fetchSeq) loading.value = false;
    }
};

onMounted(() => {
    fetchReport();
});

const downloadReport = async () => {
    downloading.value = true;
    try {
        const params = { download: "csv" };

        const deptId = getId(selectedDepartmentId.value);
        if (deptId) params.department_id = deptId;

        const empId = getId(selectedEmployeeId.value);
        if (empId) params.employee_id = empId;

        const townId = getId(selectedTownId.value);
        if (townId) params.town_id = townId;

        if (selectedDate.value) params.date = selectedDate.value;

        const res = await api.get("/meeting-minutes-report", {
            params,
            responseType: "blob",
        });

        const blob = new Blob([res.data], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download =
            "meeting_minutes_report_" + dateStamp() + ".csv";
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

const dateStamp = () => {
    const d = new Date();
    return (
        d.getFullYear() +
        "_" +
        String(d.getMonth() + 1).padStart(2, "0") +
        "_" +
        String(d.getDate()).padStart(2, "0")
    );
};

const momModalVisible = ref(false);
const selectedMom = ref("");

const openMomPreview = (text) => {
    selectedMom.value = text || "";
    momModalVisible.value = true;
};

const closeMomPreview = () => {
    momModalVisible.value = false;
    selectedMom.value = "";
};

const previewFileVisible = ref(false);
const selectedFileUrl = ref("");
const selectedFileName = ref("");

const openFilePreview = (url, filename) => {
    if (!url) return;
    selectedFileUrl.value = url;
    selectedFileName.value = filename || "Attached File";

    const ext = (filename || "").split(".").pop()?.toLowerCase() || "";

    if (ext === "pdf") {
        previewFileVisible.value = true;
    } else if (["jpg", "jpeg", "png", "gif", "webp"].includes(ext)) {
        previewFileVisible.value = true;
    } else {
        window.open(url, "_blank");
    }
};

const closeFilePreview = () => {
    previewFileVisible.value = false;
    selectedFileUrl.value = "";
    selectedFileName.value = "";
};

const isImageFile = (filename) => {
    if (!filename) return false;
    const ext = filename.split(".").pop()?.toLowerCase() || "";
    return ["jpg", "jpeg", "png", "gif", "webp"].includes(ext);
};

const isPdfFile = (filename) => {
    if (!filename) return false;
    const ext = filename.split(".").pop()?.toLowerCase() || "";
    return ext === "pdf";
};

const truncateText = (text, maxLen = 60) => {
    if (!text) return "-";
    if (text.length <= maxLen) return text;
    return text.substring(0, maxLen) + "...";
};

</script>

<template>
    <div class="report-wrapper">
        <!-- Top Header & Global Controls -->
        <div class="mb-4">
            <div class="p-1">
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
                >
                    <div class="d-flex align-items-center gap-3">
                        <div
                            class="icon-shape bg-success-subtle text-success rounded-3 p-3 d-flex align-items-center justify-content-center"
                        >
                            <i class="fa-solid fa-clipboard-list fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 font-weight-bold text-dark">
                                Meeting Minutes Report
                            </h4>
                            <p class="text-muted small mb-0">
                                Track and manage meeting minutes records across
                                employees, departments, and towns.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
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
                                    downloading
                                        ? "Exporting..."
                                        : "Export Excel"
                                }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls Panel -->
        <div class="mb-2 mt-5">
            <div class="p-1">
                <div class="row g-3">
                    <div class="d-flex gap-3 flex-wrap">
                        <div>
                            <BaseDept
                                v-model:departmentID="selectedDepartmentId"
                                :userID="userID"
                            />
                        </div>
                        <div>
                            <BaseDeptEmp
                                v-model:employeeID="selectedEmployeeId"
                                :deptID="getId(selectedDepartmentId)"
                                :isEdit="false"
                            />
                        </div>
                        <div>
                            <select
                                class="form-select town-select"
                                v-model="selectedTownId"
                            >
                                <option :value="null">All Towns</option>
                                <option
                                    v-for="town in allTowns"
                                    :key="town.id"
                                    :value="town.id"
                                >
                                    {{ town.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <DateField v-model="selectedDate" type="date" />
                        </div>
                        <div>
                            <DayName v-model="selectedDayName" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="mt-2 mb-2">
            <div class="summary-box d-inline-flex align-items-center gap-4 px-3 py-2">
                <div>
                    <span class="summary-label">Total Records</span>
                    <span class="summary-value ms-2">
                        {{ filteredReport.length }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="mt-2">
            <div class="table-responsive d-lg-block">
                <table
                    class="table table-bordered table-sm custom-payroll-table"
                >
                    <thead>
                        <tr class="text-nowrap">
                            <th
                                scope="col"
                                class="text-center"
                                style="width: 3%"
                            >
                                #
                            </th>
                            <th scope="col">Date</th>
                            <th scope="col">Employee</th>
                            <th scope="col">Department</th>
                            <th scope="col">Town</th>
                            <th scope="col">Start Time</th>
                            <th scope="col">End Time</th>
                            <th scope="col">Meeting With</th>
                            <th scope="col" style="min-width: 180px">Minutes of Meeting</th>
                            <th scope="col" class="text-center">File</th>
                            <th scope="col">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="loading">
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <div
                                        class="d-flex flex-column align-items-center justify-content-center"
                                    >
                                        <div
                                            class="spinner-border text-success mb-2"
                                            role="status"
                                            style="
                                                width: 2rem;
                                                height: 2rem;
                                            "
                                        ></div>
                                        <span class="text-muted small"
                                            >Fetching report data...</span
                                        >
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template
                            v-else-if="filteredReport.length === 0"
                        >
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <div class="empty-state text-muted">
                                        <i
                                            class="fa-regular fa-folder-open display-6 mb-2 text-secondary opacity-50"
                                        ></i>
                                        <p class="mb-0 small fw-medium">
                                            No meeting minutes records found for
                                            the selected filters.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr
                                v-for="(row, idx) in filteredReport"
                                :key="row.id"
                            >
                                <td class="text-center text-muted fw-semibold">
                                    {{ idx + 1 }}
                                </td>
                                <td class="text-nowrap">
                                    {{ row.date || "-" }}
                                </td>
                                <td class="text-nowrap">
                                    {{ row.employee_name || "-" }}
                                </td>
                                <td>
                                    {{ row.department_name || "-" }}
                                </td>
                                <td>
                                    {{ row.town_name || "-" }}
                                </td>
                                <td class="text-nowrap">
                                    {{ row.str_time || "-" }}
                                </td>
                                <td class="text-nowrap">
                                    {{ row.end_time || "-" }}
                                </td>
                                <td>
                                    {{ row.meeting_with || "-" }}
                                </td>
                                <td>
                                    <div
                                        class="mom-cell d-flex align-items-start gap-1"
                                    >
                                        <span
                                            class="mom-truncated"
                                            :title="row.mom"
                                        >
                                            {{ truncateText(row.mom, 60) }}
                                        </span>
                                        <button
                                            v-if="
                                                row.mom &&
                                                row.mom !== '-' &&
                                                row.mom.length > 60
                                            "
                                            type="button"
                                            class="btn-mom-preview"
                                            title="View Full Minutes"
                                            @click="openMomPreview(row.mom)"
                                        >
                                            <i
                                                class="fa-solid fa-up-right-and-down-left-from-center"
                                            ></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button
                                        v-if="row.file_url"
                                        type="button"
                                        class="btn-file-view"
                                        :title="
                                            'View ' +
                                            (row.file || 'File')
                                        "
                                        @click="
                                            openFilePreview(
                                                row.file_url,
                                                row.file,
                                            )
                                        "
                                    >
                                        <i
                                            class="fa-solid"
                                            :class="
                                                isPdfFile(row.file)
                                                    ? 'fa-file-pdf'
                                                    : isImageFile(row.file)
                                                      ? 'fa-file-image'
                                                      : 'fa-paperclip'
                                            "
                                        ></i>
                                    </button>
                                    <span
                                        v-else
                                        class="text-muted"
                                        style="font-size: 0.75rem"
                                        >-</span
                                    >
                                </td>
                                <td class="text-nowrap">
                                    {{ row.created_at || "-" }}
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MoM Preview Modal -->
        <teleport to="body">
            <div
                v-if="momModalVisible"
                class="image-preview-overlay"
                @click.self="closeMomPreview"
            >
                <div class="mom-modal-card">
                    <div class="mom-modal-header">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i
                                class="fa-solid fa-clipboard-list text-success me-2"
                            ></i>
                            Minutes of Meeting
                        </h6>
                        <button
                            type="button"
                            class="mom-modal-close"
                            title="Close"
                            @click="closeMomPreview"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="mom-modal-body">
                        <div class="mom-content">{{ selectedMom }}</div>
                    </div>
                    <div class="mom-modal-footer">
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary px-3"
                            @click="closeMomPreview"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

        <!-- File Preview Modal -->
        <teleport to="body">
            <div
                v-if="previewFileVisible"
                class="image-preview-overlay"
                @click.self="closeFilePreview"
            >
                <div class="file-modal-card">
                    <div class="mom-modal-header">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i
                                class="fa-solid fa-paperclip text-primary me-2"
                            ></i>
                            {{ selectedFileName }}
                        </h6>
                        <button
                            type="button"
                            class="mom-modal-close"
                            title="Close"
                            @click="closeFilePreview"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="file-modal-body">
                        <img
                            v-if="isImageFile(selectedFileName)"
                            :src="selectedFileUrl"
                            class="file-preview-img"
                            @error="
                                $event.target.style.display = 'none';
                            "
                        />
                        <iframe
                            v-else-if="isPdfFile(selectedFileName)"
                            :src="selectedFileUrl"
                            class="file-preview-iframe"
                        ></iframe>
                        <div v-else class="text-center py-4">
                            <i
                                class="fa-solid fa-file fa-3x text-muted mb-3"
                            ></i>
                            <p class="text-muted">
                                Preview not available for this file type.
                            </p>
                            <a
                                :href="selectedFileUrl"
                                target="_blank"
                                class="btn btn-sm btn-primary"
                            >
                                <i class="fa-solid fa-download me-1"></i>
                                Download File
                            </a>
                        </div>
                    </div>
                    <div class="mom-modal-footer">
                        <a
                            :href="selectedFileUrl"
                            target="_blank"
                            class="btn btn-sm btn-primary px-3 me-2"
                        >
                            <i class="fa-solid fa-download me-1"></i>
                            Download
                        </a>
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary px-3"
                            @click="closeFilePreview"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>

<style scoped>
.report-wrapper {
    background-color: #f8fafc;
    min-height: 100vh;
}

.icon-shape {
    width: 48px;
    height: 48px;
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

.custom-payroll-table thead th {
    background-color: #423434;
    color: #ffffff;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding-top: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #eaecf0;
}

.custom-payroll-table tbody td {
    font-size: 0.875rem;
}

.town-select {
    height: 33px;
    font-size: 14px;
    border: 1px solid #d0d5dd;
    border-radius: 5px;
    padding: 5px 8px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: none !important;
    min-width: 160px;
}

.summary-box {
    background: #f1f3f5;
    border: 0.8px solid rgb(196, 194, 194);
    border-radius: 5px;
}

.summary-label {
    font-size: 0.78rem;
    color: #868e96;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #212529;
}

.mom-cell {
    max-width: 260px;
}

.mom-truncated {
    font-size: 0.82rem;
    color: #495057;
    line-height: 1.4;
    word-break: break-word;
}

.btn-mom-preview {
    border: 1px solid #10b981;
    background-color: #ecfdf5;
    color: #10b981;
    border-radius: 4px;
    font-size: 0.6rem;
    width: 20px;
    height: 20px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    flex-shrink: 0;
    cursor: pointer;
    transition:
        background-color 0.15s ease,
        color 0.15s ease,
        box-shadow 0.15s ease;
}

.btn-mom-preview:hover {
    background-color: #10b981;
    color: #fff;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

.btn-file-view {
    border: 1px solid #0d6efd;
    background-color: #eff5ff;
    color: #0d6efd;
    border-radius: 5px;
    font-size: 0.75rem;
    width: 28px;
    height: 28px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    flex-shrink: 0;
    cursor: pointer;
    transition:
        background-color 0.15s ease,
        color 0.15s ease,
        box-shadow 0.15s ease;
}

.btn-file-view:hover {
    background-color: #0d6efd;
    color: #fff;
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.3);
}

.image-preview-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

/* MoM Modal */
.mom-modal-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    width: min(640px, 92vw);
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.mom-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #eaecf0;
    flex-shrink: 0;
}

.mom-modal-close {
    border: none;
    background: transparent;
    color: #333;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition:
        background-color 0.15s ease,
        color 0.15s ease;
}

.mom-modal-close:hover {
    background-color: #ef4444;
    color: #fff;
}

.mom-modal-body {
    padding: 1rem;
    overflow-y: auto;
    max-height: 60vh;
}

.mom-content {
    font-size: 0.88rem;
    line-height: 1.7;
    color: #212529;
    white-space: pre-wrap;
    word-break: break-word;
}

.mom-modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 0.65rem 1rem;
    border-top: 1px solid #eaecf0;
    flex-shrink: 0;
}

/* File Modal */
.file-modal-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    width: min(800px, 92vw);
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.file-modal-body {
    flex: 1;
    overflow: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    max-height: 65vh;
    padding: 0;
}

.file-preview-img {
    max-width: 100%;
    max-height: 65vh;
    object-fit: contain;
}

.file-preview-iframe {
    width: 100%;
    height: 65vh;
    border: none;
}
</style>
