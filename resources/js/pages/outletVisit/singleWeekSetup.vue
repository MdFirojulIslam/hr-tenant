<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "@/api";
import Swal from "sweetalert2";
import Label from "@/pages/components/label/Label.vue";
import DistributorName from "@/pages/components/input_field/DistributorName.vue";
import EmployeeName from "@/pages/components/input_field/EmployeeName.vue";
import OutletCode from "@/pages/components/input_field/OutletCode.vue";

const route = useRoute();
const router = useRouter();

const weeks = [1, 2, 3, 4];
const days = [
    "Saturday",
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
];

const initialDay = (() => {
    const day = String(route.query.day || "").toLowerCase();
    return days.find((d) => d.toLowerCase() === day) || "Saturday";
})();

const selectedWeek = ref(Number(route.query.week) || 1);
const selectedDay = ref(initialDay);
const distributor_id = ref(
    route.query.distributor_id ? Number(route.query.distributor_id) : null,
);
const employee_id = ref(
    route.query.employee_id ? Number(route.query.employee_id) : null,
);

const outletRows = ref([
    {
        is_marked: false,
        outlet_code: null,
        outlet_name: null,
        is_inactive: false,
        is_visited: false,
    },
]);

const isEditMode = ref(false);
const isLoading = ref(false);

const currentDayName = computed(() => selectedDay.value.toLowerCase());

const emptyRow = () => ({
    is_marked: false,
    outlet_code: null,
    outlet_name: null,
    is_inactive: false,
    is_visited: false,
});

let loadTimer = null;
let requestSeq = 0;

const loadDaySetup = async () => {
    if (!distributor_id.value || !employee_id.value) {
        isEditMode.value = false;
        return;
    }

    const seq = ++requestSeq;
    isLoading.value = true;

    try {
        const res = await api.get(
            `/outlet-visit-single-week/${employee_id.value}/${distributor_id.value}/${selectedWeek.value}/${currentDayName.value}`,
        );
        if (seq !== requestSeq) return;

        const outlets = res.data?.data?.outlets ?? [];
        isEditMode.value = outlets.length > 0;
        outletRows.value = outlets.length
            ? outlets.map((outlet) => ({
                  is_marked: false,
                  outlet_code: outlet.outlet_code,
                  outlet_name: outlet.outlet_name,
                  is_inactive: false,
                  is_visited: false,
              }))
            : [emptyRow()];
    } catch (err) {
        if (seq !== requestSeq) return;

        isEditMode.value = false;
        outletRows.value = [emptyRow()];
        const message =
            err.response?.data?.message || "Failed to load setup data.";
        if (err.response) Swal.fire("Error", message, "error");
    } finally {
        if (seq === requestSeq) isLoading.value = false;
    }
};

const scheduleLoad = () => {
    if (loadTimer) clearTimeout(loadTimer);
    isEditMode.value = false;
    outletRows.value = [emptyRow()];
    loadTimer = setTimeout(loadDaySetup, 250);
};

watch([selectedWeek, selectedDay, distributor_id, employee_id], scheduleLoad);

const addMore = () => {
    outletRows.value.push(emptyRow());
};

const promptRemoveRow = (index) => {
    if (outletRows.value.length > 1) {
        outletRows.value.splice(index, 1);
    }
};

const buildPayload = () => {
    if (!distributor_id.value) {
        Swal.fire("Warning", "Please select a distributor.", "warning");
        return null;
    }

    if (!employee_id.value) {
        Swal.fire("Warning", "Please select an employee.", "warning");
        return null;
    }

    const rows = outletRows.value.filter(
        (row) => row.outlet_code && row.outlet_name,
    );

    const seenCodes = new Set();
    for (const row of rows) {
        const code = String(row.outlet_code).trim();
        if (seenCodes.has(code)) {
            Swal.fire(
                "Warning",
                `Outlet "${code}" is already added for Week ${selectedWeek.value} - ${selectedDay.value}. Every outlet must be unique within the same week and day.`,
                "warning",
            );
            return null;
        }
        seenCodes.add(code);
    }

    return {
        distributor_id: distributor_id.value,
        employee_id: employee_id.value,
        week_no: selectedWeek.value,
        day_name: currentDayName.value,
        outlets: rows.map((row) => ({
            outlet_code: row.outlet_code,
            outlet_name: row.outlet_name,
        })),
    };
};

const isOutletDuplicate = (outletCode, currentIndex) => {
    const code = String(outletCode).trim();
    return outletRows.value.some(
        (row, index) =>
            index !== currentIndex &&
            row.outlet_code &&
            String(row.outlet_code).trim() === code,
    );
};

const showOutletDuplicateError = (outletCode) => {
    Swal.fire(
        "Warning",
        `Outlet "${outletCode}" is already added for Week ${selectedWeek.value} - ${selectedDay.value}. Every outlet must be unique within the same week and day.`,
        "warning",
    );
};

let pendingRejectedOutlet = false;

const onSelectOutlet = (index, outletCode, row) => {
    if (!outletCode) {
        pendingRejectedOutlet = false;
        row.outlet_code = null;
        row.outlet_name = null;
        return;
    }

    if (isOutletDuplicate(outletCode, index)) {
        pendingRejectedOutlet = true;
        showOutletDuplicateError(outletCode);
        return;
    }

    pendingRejectedOutlet = false;
    row.outlet_code = outletCode;
};

const onOutletNameChange = (row, outletName) => {
    if (pendingRejectedOutlet) {
        pendingRejectedOutlet = false;
        return;
    }
    row.outlet_name = outletName;
};

const save = async () => {
    const payload = buildPayload();
    if (!payload) return;

    const actionLabel = isEditMode.value ? "Update" : "Save";
    const result = await Swal.fire({
        title: "Confirmation",
        html: `<p>Are you sure you want to ${actionLabel.toLowerCase()} the ${selectedDay.value} setup for Week ${selectedWeek.value}?</p>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: `Yes, ${actionLabel}`,
        cancelButtonText: "Cancel",
        confirmButtonColor: "#059223d5",
        cancelButtonColor: "#bd360dd5",
    });

    if (!result.isConfirmed) return;

    try {
        const res = await api.post(
            "/outlet-visit-single-week-assignment",
            payload,
        );
        const message =
            res.data?.message ||
            `Week ${selectedWeek.value} ${selectedDay.value} setup ${actionLabel.toLowerCase()}d successfully.`;
        Swal.fire("Success!", message, "success");
        isEditMode.value = true;
    } catch (err) {
        const message =
            err.response?.data?.message ||
            `Failed to ${actionLabel.toLowerCase()} the setup. Please try again.`;
        Swal.fire("Error", message, "error");
    }
};

const goBackToList = () => {
    router.push({ name: "SetupList" });
};

onMounted(loadDaySetup);
</script>

<template>
    <div
        class="border rounded container-fluid d-flex flex-column p-0 m-0 position-relative"
    >
        <!-- PAGE HEADER -->
        <div
            class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom flex-wrap gap-2"
        >
            <h2 class="header-design p-1 d-flex align-items-center gap-2">
                <i class="fa-solid fa-calendar-day icon-design"></i>
                Single Week Setup
            </h2>

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                @click="goBackToList"
            >
                <i class="fa-solid fa-arrow-left me-1"></i>
                Back to List
            </button>
        </div>

        <!-- SELECTIONS -->
        <div class="px-2 mt-4">
            <div
                class="setup-section p-1 d-flex flex-wrap gap-4 align-items-end"
            >
                <!-- WEEK SELECTION -->
                <div>
                    <Label label="Select Week" />
                    <div class="week-tabs">
                        <button
                            v-for="week in weeks"
                            :key="week"
                            type="button"
                            class="week-tab"
                            :class="{ active: selectedWeek === week }"
                            @click="selectedWeek = week"
                        >
                            Week {{ week }}
                        </button>
                    </div>
                </div>

                <!-- DAY SELECTION -->
                <div>
                    <Label label="Select Day" />
                    <div class="week-tabs">
                        <button
                            v-for="day in days"
                            :key="day"
                            type="button"
                            class="week-tab"
                            :class="{ active: selectedDay === day }"
                            @click="selectedDay = day"
                        >
                            {{ day }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SETUP INFORMATION -->
        <div class="px-2 mt-4">
            <div class="setup-section p-1">
                <div class="d-flex gap-4">
                    <div>
                        <Label label="Distributor Name*" />
                        <DistributorName
                            v-model:distributor_id="distributor_id"
                        />
                    </div>

                    <div>
                        <Label label="Employee Name*" />
                        <EmployeeName v-model:employee_id="employee_id" />
                    </div>
                </div>
            </div>
        </div>

        <!-- SELECTED DAY SETUP -->
        <div class="p-2">
            <div class="row g-2">
                <div class="col-xl-6 col-12">
                    <div class="day-card border rounded">
                        <!-- DAY HEADER WITH ADD ICON BUTTON -->
                        <div
                            class="day-title d-flex justify-content-between align-items-center"
                        >
                            <span>{{ selectedDay }}</span>
                            <button
                                type="button"
                                class="btn btn-xs btn-success add-btn-icon"
                                title="Add Row"
                                @click="addMore"
                            >
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>

                        <div class="p-2">
                            <!-- TABLE STRUCTURE -->
                            <div class="table-responsive no-scrollbar">
                                <table
                                    class="table table-bordered custom-table mb-0 align-middle"
                                >
                                    <thead>
                                        <tr>
                                            <th
                                                style="width: 8%"
                                                class="text-center"
                                            >
                                                Mark
                                            </th>
                                            <th style="width: 25%">
                                                Outlet Code
                                            </th>
                                            <th style="width: 47%">
                                                Outlet Name
                                            </th>
                                            <th
                                                style="width: 14%"
                                                class="text-center"
                                            >
                                                Not Visited
                                            </th>
                                            <th
                                                style="width: 6%"
                                                class="text-center"
                                            >
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(row, index) in outletRows"
                                            :key="index"
                                        >
                                            <!-- MARK CHECKBOX -->
                                            <td class="text-center">
                                                <input
                                                    v-model="row.is_marked"
                                                    class="form-check-input custom-checkbox"
                                                    type="checkbox"
                                                />
                                            </td>

                                            <!-- OUTLET CODE -->
                                            <td>
                                                <OutletCode
                                                    :outlet_code="
                                                        row.outlet_code
                                                    "
                                                    :outlet_name="
                                                        row.outlet_name
                                                    "
                                                    :distributor_id="
                                                        distributor_id
                                                    "
                                                    @update:outlet_code="
                                                        onSelectOutlet(
                                                            index,
                                                            $event,
                                                            row,
                                                        )
                                                    "
                                                    @update:outlet_name="
                                                        onOutletNameChange(
                                                            row,
                                                            $event,
                                                        )
                                                    "
                                                />
                                            </td>

                                            <!-- OUTLET NAME -->
                                            <td>
                                                <input
                                                    v-model="row.outlet_name"
                                                    type="text"
                                                    class="form-control"
                                                    readonly
                                                />
                                            </td>

                                            <!-- VISITED CHECKBOX -->
                                            <td class="text-center">
                                                <input
                                                    v-model="row.is_visited"
                                                    class="form-check-input custom-checkbox"
                                                    type="checkbox"
                                                />
                                            </td>

                                            <!-- ACTION (REMOVE) -->
                                            <td class="text-center">
                                                <button
                                                    v-if="outletRows.length > 1"
                                                    type="button"
                                                    class="btn btn-xs btn-outline-danger btn-icon"
                                                    title="Remove"
                                                    @click="
                                                        promptRemoveRow(index)
                                                    "
                                                >
                                                    <i
                                                        class="fa-solid fa-trash"
                                                    ></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <tr v-if="isLoading">
                                            <td
                                                colspan="5"
                                                class="text-center py-2"
                                            >
                                                Loading...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAVE / UPDATE -->
        <div class="text-center py-2">
            <button
                type="button"
                class="btn btn-sm btn-success px-4"
                @click="save"
            >
                <i class="fa-solid fa-floppy-disk me-1"></i>
                {{ isEditMode ? "Update" : "Save" }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.header-design {
    letter-spacing: 0.5px;
    font-weight: 700;
    font-size: 18px;
    color: #212529;
}

.icon-design {
    color: #059223d5;
    font-size: 16px;
}

.form-label {
    font-weight: 600;
    letter-spacing: 0.2px;
    font-size: 11px;
    margin-bottom: 2px;
}

.week-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.week-tab {
    padding: 3px 12px;
    border: 1px solid #c4c2c2;
    border-radius: 12px;
    background: #ffffff;
    color: #495057;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.3px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.week-tab:hover {
    border-color: #059223d5;
    color: #059223d5;
}

.week-tab.active {
    background-color: #059223d5;
    border-color: #059223d5;
    color: #ffffff;
}

.day-card {
    background: #ffffff;
    overflow: visible !important;
    position: relative;
}

.day-title {
    background: #4e4e4e;
    color: #ffffff;
    padding: 3px 8px;
    font-size: 11px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    font-weight: 600;
}

.add-btn-icon {
    width: 20px;
    height: 20px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 3px;
    font-size: 10px;
}

.no-scrollbar {
    overflow: visible !important;
}

.custom-table {
    margin-bottom: 0;
    border-color: #e9ecef;
    width: 100% !important;
    table-layout: fixed;
}

.custom-table th {
    background-color: #f1f3f5;
    color: #495057;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 2px;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    white-space: nowrap;
}

.custom-table tbody td {
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    padding-left: 2px !important;
    padding-right: 2px !important;
    background-color: #ffffff;
    line-height: 1 !important;
    vertical-align: middle !important;
    overflow: visible !important;
    position: relative;
}

.custom-table tbody td :deep(.form-group),
.custom-table tbody td :deep(.mb-3),
.custom-table tbody td :deep(.mb-2),
.custom-table tbody td :deep(.mb-1) {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

.custom-table tbody td :deep(.multiselect__content-wrapper),
.custom-table tbody td :deep(.dropdown-menu),
.custom-table tbody td :deep(.select-options-container),
.custom-table tbody td :deep(.select2-container) {
    z-index: 9999 !important;
    position: absolute !important;
}

.custom-table tbody td :deep(.multiselect) {
    min-height: 26px !important;
    height: 26px !important;
    margin-bottom: 0 !important;
    width: 100% !important;
}

.custom-table tbody td :deep(.multiselect__tags) {
    min-height: 26px !important;
    height: 26px !important;
    padding: 0 4px !important;
    margin-bottom: 0 !important;
    display: flex !important;
    align-items: center !important;
}

.custom-table tbody td :deep(.multiselect__placeholder),
.custom-table tbody td :deep(.multiselect__single),
.custom-table tbody td :deep(.multiselect__input) {
    margin-bottom: 0 !important;
    padding: 0 !important;
    line-height: 24px !important;
    font-size: 11px !important;
}

.custom-table tbody td :deep(.form-control),
.custom-table tbody td :deep(.form-select) {
    height: 26px !important;
    min-height: 26px !important;
    padding: 2px 4px !important;
    margin-bottom: 0 !important;
    font-size: 11px !important;
    width: 100% !important;
}

.custom-checkbox {
    width: 14px;
    height: 14px;
    cursor: pointer;
    margin: 0 !important;
    vertical-align: middle;
    float: none !important;
}

.btn-xs {
    font-size: 10px;
    border-radius: 3px;
}

.btn-icon {
    padding: 3px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>
