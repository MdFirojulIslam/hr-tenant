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
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import BaseDept from "@/pages/components/input_field/BaseDept.vue";
import BaseDeptEmp from "@/pages/components/input_field/BaseDeptEmp.vue";

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
    const str = String(value).trim();
    if (str === "") return false;
    const parts = str.split(",").map((p) => p.trim());
    if (parts.length !== 2) return false;
    const lat = Number(parts[0]);
    const lng = Number(parts[1]);
    return (
        Number.isFinite(lat) && Number.isFinite(lng) && (lat !== 0 || lng !== 0)
    );
};

const parseCoordinates = (coordStr) => {
    if (!coordStr) return null;
    const str = String(coordStr).trim();
    const parts = str.split(",").map((p) => p.trim());
    if (parts.length !== 2) return null;
    const lat = Number(parts[0]);
    const lng = Number(parts[1]);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    if (lat === 0 && lng === 0) return null;
    return { lat, lng };
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
    const container = document.getElementById("mi-map-container");
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
    const coords = parseCoordinates(row.location_coordinate);
    if (!coords) return;

    selectedMapLocation.value = {
        lat: coords.lat,
        lng: coords.lng,
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
const selectedBrandId = ref(null);
const selectedSize = ref("");

const allReportData = ref([]);
const allEmployees = ref([]);
const allDepartments = ref([]);
const allBrands = ref([]);
const allSizes = ref([]);

const selectedBrand = computed({
    get() {
        return (
            allBrands.value.find((b) => b.id === selectedBrandId.value) || null
        );
    },
    set(value) {
        selectedBrandId.value = value ? value.id : null;
    },
});

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
    const brandId = getId(selectedBrandId.value);
    const sizeVal = selectedSize.value;

    let data = [...allReportData.value];

    if (departmentId !== null) {
        data = data.filter(
            (row) => Number(row.department_id) === Number(departmentId),
        );
    }

    if (employeeId !== null) {
        data = data.filter(
            (row) => Number(row.employee_id) === Number(employeeId),
        );
    }

    if (brandId !== null) {
        data = data.filter((row) => Number(row.brand_id) === Number(brandId));
    }

    if (sizeVal && sizeVal !== "") {
        data = data.filter(
            (row) => String(row.size).trim() === String(sizeVal).trim(),
        );
    }

    return data;
});

let fetchSeq = 0;

const fetchReport = async () => {
    const seq = ++fetchSeq;
    loading.value = true;
    try {
        const res = await api.get("/market-intelligence-report");
        if (seq !== fetchSeq) return;
        allReportData.value = res.data?.data?.report ?? [];
        allEmployees.value = res.data?.data?.employees ?? [];
        allDepartments.value = res.data?.data?.departments ?? [];
        allSizes.value = res.data?.data?.sizes ?? [];
    } catch (err) {
        if (seq !== fetchSeq) return;
        const message = err.response?.data?.message || "Failed to load report.";
        Swal.fire("Error", message, "error");
    } finally {
        if (seq === fetchSeq) loading.value = false;
    }
};

const fetchBrands = async () => {
    try {
        const res = await api.get("/market-intelligence-brands");
        allBrands.value = res.data?.data ?? [];
    } catch (err) {
        allBrands.value = [];
    }
};

watch(
    [selectedDepartmentId, selectedEmployeeId, selectedBrandId, selectedSize],
    () => {},
);

onMounted(() => {
    fetchReport();
    fetchBrands();
});

const downloadReport = async () => {
    downloading.value = true;
    try {
        const params = { download: "csv" };

        const deptId = getId(selectedDepartmentId.value);
        if (deptId) params.department_id = deptId;

        const empId = getId(selectedEmployeeId.value);
        if (empId) params.employee_id = empId;

        const brandId = getId(selectedBrandId.value);
        if (brandId) params.brand_id = brandId;

        if (selectedSize.value) params.size = selectedSize.value;

        const res = await api.get("/market-intelligence-report", {
            params,
            responseType: "blob",
        });

        const blob = new Blob([res.data], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = "market_intelligence_report_" + dateStamp() + ".csv";
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

const imageUrl = (row) => {
    if (row.product_image_url) return row.product_image_url;
    return null;
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
                            <i
                                class="fa-solid fa-magnifying-glass-chart fs-4"
                            ></i>
                        </div>
                        <div>
                            <h4 class="mb-1 font-weight-bold text-dark">
                                Market Intelligence Report
                            </h4>
                            <p class="text-muted small mb-0">
                                Track and manage market intelligence data across
                                outlets, brands, and regions.
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
                            <div class="multiselect-brand-container">
                                <Multiselect
                                    v-model="selectedBrand"
                                    :options="allBrands"
                                    label="name"
                                    track-by="id"
                                    :searchable="true"
                                    :close-on-select="true"
                                    :show-labels="false"
                                    :clear-on-select="false"
                                    :allow-empty="true"
                                    placeholder="Select Brand"
                                    class="multiselect-input"
                                />
                                <button
                                    v-if="selectedBrand"
                                    @click="selectedBrand = null"
                                    type="button"
                                    class="clear-btn"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        <div>
                            <select
                                class="form-select size-select"
                                v-model="selectedSize"
                            >
                                <option value="">All Sizes</option>
                                <option
                                    v-for="sz in allSizes"
                                    :key="sz"
                                    :value="sz"
                                >
                                    {{ sz }}
                                </option>
                            </select>
                        </div>
                    </div>
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
                            <th scope="col">Brand</th>
                            <th scope="col">Product Name</th>
                            <th scope="col">Size</th>
                            <th scope="col">Outlet Name</th>
                            <th scope="col">Outlet Location</th>
                            <th scope="col" class="text-center">Map</th>
                            <th scope="col">Division</th>
                            <th scope="col">District</th>
                            <th scope="col">Country of Origin</th>
                            <th class="text-center" scope="col">TP</th>
                            <th class="text-center" scope="col">MRP</th>
                            <th class="text-center" scope="col">Submitted</th>
                            <th scope="col" class="text-center">Image</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="loading">
                            <tr>
                                <td colspan="14" class="text-center py-5">
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
                        </template>
                        <template v-else-if="filteredReport.length === 0">
                            <tr>
                                <td colspan="14" class="text-center py-5">
                                    <div class="empty-state text-muted">
                                        <i
                                            class="fa-regular fa-folder-open display-6 mb-2 text-secondary opacity-50"
                                        ></i>
                                        <p class="mb-0 small fw-medium">
                                            No market intelligence records found
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
                                <td>{{ row.brand_name || "-" }}</td>
                                <td>{{ row.product_name || "-" }}</td>
                                <td>{{ row.size || "-" }}</td>
                                <td>{{ row.outlet_name || "-" }}</td>
                                <td style="max-width: 160px">
                                    <span
                                        class="text-truncate d-inline-block"
                                        style="max-width: 160px"
                                        :title="row.outlet_location"
                                    >
                                        {{ row.outlet_location || "-" }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button
                                        v-if="
                                            isValidCoordinate(
                                                row.location_coordinate,
                                            )
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
                                <td>{{ row.division || "-" }}</td>
                                <td>{{ row.district || "-" }}</td>
                                <td>{{ row.country_of_origin || "-" }}</td>
                                <td class="text-center">
                                    {{
                                        row.tp
                                            ? Number(row.tp).toFixed(2)
                                            : "0.00"
                                    }}
                                </td>
                                <td class="text-center">
                                    {{
                                        row.mrp
                                            ? Number(row.mrp).toFixed(2)
                                            : "0.00"
                                    }}
                                </td>
                                <td class="text-center">
                                    {{ row.created_at }}
                                </td>
                                <td class="text-center">
                                    <img
                                        v-if="imageUrl(row)"
                                        :src="imageUrl(row)"
                                        class="report-thumb"
                                        height="40"
                                        width="40"
                                        @click="openImagePreview(imageUrl(row))"
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
                        id="mi-map-container"
                        class="map-modal-canvas"
                    ></div>
                    <div v-if="selectedMapLocation" class="map-modal-details">
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
                            <strong>{{ selectedMapLocation.name }}</strong>
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

.multiselect-brand-container {
    position: relative;
    display: flex;
    align-items: center;
}

.multiselect-brand-container .multiselect-input {
    width: 280px !important;
}

:deep(.multiselect-brand-container .multiselect__tags) {
    min-height: 33px !important;
    height: 33px !important;
    padding: 5px 40px 0 8px !important;
}

:deep(.multiselect-brand-container .multiselect__single) {
    margin-bottom: 0 !important;
    line-height: 22px !important;
}

:deep(.multiselect-brand-container .multiselect__input) {
    margin-bottom: 0 !important;
    padding: 0 !important;
}

:deep(.multiselect-brand-container .multiselect__content-wrapper) {
    max-height: 300px !important;
}

:deep(.multiselect-brand-container .multiselect__placeholder) {
    color: #000000 !important;
    font-size: 14px;
    padding-top: 0px;
}

:deep(.multiselect-brand-container .multiselect__select::before) {
    display: none;
}

.clear-btn {
    position: absolute;
    right: 10px;
    background: #949894;
    padding: 3px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
    color: #ffffff;
    height: 25px;
    top: 3px;
    border: none;
}

.clear-btn:hover {
    background: #ac0000;
    color: rgb(244, 238, 238);
}

.size-select {
    height: 33px;
    font-size: 14px;
    border: 1px solid #d0d5dd;
    border-radius: 5px;
    padding: 5px 8px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: none !important;
    min-width: 140px;
}
</style>
