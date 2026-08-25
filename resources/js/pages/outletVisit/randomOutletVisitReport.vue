<script setup>
import {
    ref,
    computed,
    onMounted,
    onBeforeUnmount,
    watch,
    nextTick,
} from "vue";
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
const previewImage = ref(null);

const DUMMY_IMAGE =
    "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Crect fill='%23e9ecef' width='100' height='100'/%3E%3Ctext x='50' y='54' text-anchor='middle' font-size='14' fill='%236c757d'%3ENo Image%3C/text%3E%3C/svg%3E";

const openImagePreview = (image) => {
    previewImage.value = image || null;
};

const closeImagePreview = () => {
    previewImage.value = null;
};

const isValidCoordinate = (value) => {
    if (value === null || value === undefined || value === "") return false;
    const num = Number(value);
    return Number.isFinite(num) && num !== 0;
};

const mapModalVisible = ref(false);
const selectedMapLocation = ref(null);
let mapInstance = null;
let mapMarker = null;
let mapResizeTimer = null;

const destroyMap = () => {
    if (mapResizeTimer) {
        clearTimeout(mapResizeTimer);
        mapResizeTimer = null;
    }
    if (mapMarker) {
        mapMarker.setMap(null);
        mapMarker = null;
    }
    if (mapInstance) {
        google.maps.event.clearInstanceListeners(mapInstance);
        mapInstance = null;
    }
};

const renderMap = () => {
    const container = document.getElementById("rov-map-container");
    if (!container || !selectedMapLocation.value) return;

    if (typeof google === "undefined" || !google.maps) {
        Swal.fire("Error", "Map service is not available right now.", "error");
        return;
    }

    destroyMap();

    const position = {
        lat: selectedMapLocation.value.lat,
        lng: selectedMapLocation.value.lng,
    };

    mapInstance = new google.maps.Map(container, {
        center: position,
        zoom: 16,
        mapTypeControl: false,
        streetViewControl: false,
    });

    mapMarker = new google.maps.Marker({
        position: position,
        map: mapInstance,
        title: selectedMapLocation.value.name || "Location",
    });

    mapResizeTimer = setTimeout(() => {
        mapResizeTimer = null;
        if (!mapInstance) return;
        google.maps.event.trigger(mapInstance, "resize");
        mapInstance.setCenter(position);
    }, 100);
};

const openMapModal = async (row) => {
    if (!isValidCoordinate(row.latitude) || !isValidCoordinate(row.longitude))
        return;

    selectedMapLocation.value = {
        lat: Number(row.latitude),
        lng: Number(row.longitude),
        name: row.outlet_name || "N/A",
    };
    mapModalVisible.value = true;
    await nextTick();
    renderMap();
};

const closeMapModal = () => {
    mapModalVisible.value = false;
    destroyMap();
};

onBeforeUnmount(() => {
    destroyMap();
});

const selectedDepartmentId = ref(null);
const selectedEmployeeId = ref(null);
const selectedTown = ref("");

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
    const townVal = selectedTown.value;
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

    if (townVal && townVal !== "") {
        data = data.filter(
            (row) => String(row.town).trim() === String(townVal).trim(),
        );
    }

    if (dateVal) {
        data = data.filter((row) => {
            if (!row.created_at_raw) return false;
            return row.created_at_raw.substring(0, 10) === dateVal;
        });
    }

    return data;
});

let fetchSeq = 0;

const fetchReport = async () => {
    const seq = ++fetchSeq;
    loading.value = true;
    try {
        const res = await api.get("/random-outlet-visit-report");
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

        if (selectedTown.value) params.town = selectedTown.value;

        if (selectedDate.value) params.date = selectedDate.value;

        const res = await api.get("/random-outlet-visit-report", {
            params,
            responseType: "blob",
        });

        const blob = new Blob([res.data], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download =
            "random_outlet_visit_report_" + dateStamp() + ".csv";
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

const remarksModalVisible = ref(false);
const selectedRemarks = ref("");

const openRemarksPreview = (text) => {
    selectedRemarks.value = text || "";
    remarksModalVisible.value = true;
};

const closeRemarksPreview = () => {
    remarksModalVisible.value = false;
    selectedRemarks.value = "";
};

const truncateText = (text, maxLen = 40) => {
    if (!text) return "-";
    if (text === "-") return "-";
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
                            <i class="fa-solid fa-map-pin fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 font-weight-bold text-dark">
                                Random Outlet Visit Report
                            </h4>
                            <p class="text-muted small mb-0">
                                Track and manage random outlet visit data
                                across employees, towns, and outlets.
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
                                v-model="selectedTown"
                            >
                                <option value="">All Towns</option>
                                <option
                                    v-for="town in allTowns"
                                    :key="town"
                                    :value="town"
                                >
                                    {{ town }}
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
            <div
                class="summary-box d-inline-flex align-items-center gap-4 px-3 py-2"
            >
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
                            <th scope="col">Employee</th>
                            <th scope="col">Department</th>
                            <th scope="col">Town</th>
                            <th scope="col">Outlet Name</th>
                            <th scope="col">Location</th>
                            <th scope="col" class="text-center">Map</th>
                            <th scope="col">Remarks</th>
                            <th scope="col" class="text-center">Image</th>
                            <th scope="col">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="loading">
                            <tr>
                                <td colspan="10" class="text-center py-5">
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
                                <td colspan="10" class="text-center py-5">
                                    <div class="empty-state text-muted">
                                        <i
                                            class="fa-regular fa-folder-open display-6 mb-2 text-secondary opacity-50"
                                        ></i>
                                        <p class="mb-0 small fw-medium">
                                            No random outlet visit records found
                                            for the selected filters.
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
                                    {{ row.employee_name || "-" }}
                                </td>
                                <td>
                                    {{ row.department_name || "-" }}
                                </td>
                                <td class="text-nowrap">
                                    {{ row.town || "-" }}
                                </td>
                                <td class="text-nowrap">
                                    {{ row.outlet_name || "-" }}
                                </td>
                                <td>
                                    <span
                                        class="text-truncate d-inline-block location-cell"
                                        :title="row.location_name"
                                    >
                                        {{ row.location_name || "-" }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button
                                        v-if="
                                            isValidCoordinate(row.latitude) &&
                                            isValidCoordinate(row.longitude)
                                        "
                                        type="button"
                                        class="btn-map-view"
                                        title="View on Map"
                                        @click="openMapModal(row)"
                                    >
                                        <i
                                            class="fa-solid fa-map-location-dot"
                                        ></i>
                                    </button>
                                    <span
                                        v-else
                                        class="text-muted"
                                        style="font-size: 0.75rem"
                                        >-</span
                                    >
                                </td>
                                <td>
                                    <div
                                        class="remarks-cell d-flex align-items-start gap-1"
                                    >
                                        <span
                                            class="remarks-truncated"
                                            :title="row.remarks"
                                        >
                                            {{
                                                truncateText(
                                                    row.remarks,
                                                    40,
                                                )
                                            }}
                                        </span>
                                        <button
                                            v-if="
                                                row.remarks &&
                                                row.remarks !== '-' &&
                                                row.remarks.length > 40
                                            "
                                            type="button"
                                            class="btn-remarks-preview"
                                            title="View Full Remarks"
                                            @click="
                                                openRemarksPreview(
                                                    row.remarks,
                                                )
                                            "
                                        >
                                            <i
                                                class="fa-solid fa-up-right-and-down-left-from-center"
                                            ></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <img
                                        v-if="row.image_url"
                                        :src="row.image_url"
                                        class="report-thumb"
                                        height="40"
                                        width="40"
                                        @click="
                                            openImagePreview(row.image_url)
                                        "
                                        @error="
                                            $event.target.src = DUMMY_IMAGE;
                                            $event.target.classList.add(
                                                'report-thumb--dummy',
                                            );
                                        "
                                    />
                                    <img
                                        v-else
                                        :src="DUMMY_IMAGE"
                                        class="report-thumb report-thumb--dummy"
                                        height="40"
                                        width="40"
                                    />
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

        <!-- Image Preview Overlay -->
        <teleport to="body">
            <div
                v-if="previewImage"
                class="image-preview-overlay"
                @click.self="closeImagePreview"
            >
                <div class="image-preview-container">
                    <button
                        class="image-preview-close"
                        @click="closeImagePreview"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <img
                        :src="previewImage"
                        class="image-preview-img"
                        @error="closeImagePreview"
                    />
                </div>
            </div>
        </teleport>

        <!-- Map Modal -->
        <teleport to="body">
            <div
                v-if="mapModalVisible"
                class="image-preview-overlay"
                @click.self="closeMapModal"
            >
                <div class="map-modal-card">
                    <div class="map-modal-header">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i
                                class="fa-solid fa-location-dot text-danger me-2"
                            ></i>
                            Outlet Location
                        </h6>
                        <button
                            type="button"
                            class="map-modal-close"
                            title="Close"
                            @click="closeMapModal"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div
                        v-if="selectedMapLocation"
                        id="rov-map-container"
                        class="map-modal-canvas"
                    ></div>
                    <div
                        v-if="selectedMapLocation"
                        class="map-modal-details"
                    >
                        <span>
                            Latitude:
                            <strong>{{
                                selectedMapLocation.lat.toFixed(7)
                            }}</strong>
                        </span>
                        <span class="ms-3">
                            Longitude:
                            <strong>{{
                                selectedMapLocation.lng.toFixed(7)
                            }}</strong>
                        </span>
                        <span class="ms-3">
                            Outlet:
                            <strong>{{
                                selectedMapLocation.name
                            }}</strong>
                        </span>
                    </div>
                    <div class="map-modal-footer">
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary px-3"
                            @click="closeMapModal"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

        <!-- Remarks Preview Modal -->
        <teleport to="body">
            <div
                v-if="remarksModalVisible"
                class="image-preview-overlay"
                @click.self="closeRemarksPreview"
            >
                <div class="remarks-modal-card">
                    <div class="remarks-modal-header">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i
                                class="fa-solid fa-comment-dots text-success me-2"
                            ></i>
                            Remarks
                        </h6>
                        <button
                            type="button"
                            class="remarks-modal-close"
                            title="Close"
                            @click="closeRemarksPreview"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="remarks-modal-body">
                        <div class="remarks-content">
                            {{ selectedRemarks }}
                        </div>
                    </div>
                    <div class="remarks-modal-footer">
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary px-3"
                            @click="closeRemarksPreview"
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

.location-cell {
    max-width: 160px;
}

.btn-map-view {
    border: 1px solid #0d6efd;
    background-color: #eff5ff;
    color: #0d6efd;
    border-radius: 5px;
    font-size: 0.7rem;
    width: 22px;
    height: 22px;
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

.btn-map-view:hover {
    background-color: #0d6efd;
    color: #fff;
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.3);
}

.remarks-cell {
    max-width: 220px;
}

.remarks-truncated {
    font-size: 0.82rem;
    color: #495057;
    line-height: 1.4;
    word-break: break-word;
}

.btn-remarks-preview {
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

.btn-remarks-preview:hover {
    background-color: #10b981;
    color: #fff;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

.report-thumb {
    border-radius: 50%;
    cursor: pointer;
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease;
    object-fit: cover;
}

.report-thumb:hover {
    transform: scale(1.15);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.4);
}

.report-thumb--dummy {
    opacity: 0.55;
    cursor: default;
}

.report-thumb--dummy:hover {
    transform: none;
    box-shadow: none;
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

.image-preview-container {
    position: relative;
    max-width: 90vw;
    max-height: 85vh;
}

.image-preview-img {
    display: block;
    max-width: 90vw;
    max-height: 85vh;
    border-radius: 12px;
    object-fit: contain;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.image-preview-close {
    position: absolute;
    top: -12px;
    right: -12px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: #fff;
    color: #333;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
    transition: background 0.15s ease;
    z-index: 10;
}

.image-preview-close:hover {
    background: #ef4444;
    color: #fff;
}

.map-modal-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    width: min(640px, 92vw);
    overflow: hidden;
}

.map-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #eaecf0;
}

.map-modal-close {
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

.map-modal-close:hover {
    background-color: #ef4444;
    color: #fff;
}

.map-modal-canvas {
    height: 340px;
    width: 100%;
}

.map-modal-details {
    padding: 0.65rem 1rem;
    font-size: 0.82rem;
    color: #212529;
    border-top: 1px solid #eaecf0;
}

.map-modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 0.65rem 1rem;
    border-top: 1px solid #eaecf0;
}

.remarks-modal-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    width: min(640px, 92vw);
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.remarks-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #eaecf0;
    flex-shrink: 0;
}

.remarks-modal-close {
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

.remarks-modal-close:hover {
    background-color: #ef4444;
    color: #fff;
}

.remarks-modal-body {
    padding: 1rem;
    overflow-y: auto;
    max-height: 60vh;
}

.remarks-content {
    font-size: 0.88rem;
    line-height: 1.7;
    color: #212529;
    white-space: pre-wrap;
    word-break: break-word;
}

.remarks-modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 0.65rem 1rem;
    border-top: 1px solid #eaecf0;
    flex-shrink: 0;
}
</style>
