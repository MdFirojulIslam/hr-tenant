<script setup>
import { ref, reactive, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import Label from "@/pages/components/label/Label.vue";
import DistributorName from "@/pages/components/input_field/DistributorName.vue";
import EmployeeName from "@/pages/components/input_field/EmployeeName.vue";
import OutletCode from "@/pages/components/input_field/OutletCode.vue";

const distributor_id = ref(null);
const employee_id = ref(null);

const weeks = [1, 2, 3, 4];

const days = [
    "Saturday",
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
];

const selectedWeek = ref(1);
const selectedDistributor = ref(null);
const selectedEmployee = ref(null);

const distributors = ref([
    { id: 1, name: "Dhaka Central Distributor" },
    { id: 2, name: "Chittagong Distribution House" },
    { id: 3, name: "Gazipur Wholesale Traders" },
    { id: 4, name: "Sylhet Agro Distributor" },
    { id: 5, name: "Khulna Food Distributor" },
]);

const employees = ref([
    { id: 1, name: "Rahim Uddin" },
    { id: 2, name: "Karim Mia" },
    { id: 3, name: "Jasmin Akter" },
    { id: 4, name: "Tanvir Hasan" },
    { id: 5, name: "Nazma Begum" },
    { id: 6, name: "Sajib Rahman" },
]);

const outlets = ref([
    { code: "OUT-1001", name: "Al Madina Super Shop" },
    { code: "OUT-1002", name: "Brothers General Store" },
    { code: "OUT-1003", name: "City Corner Mart" },
    { code: "OUT-1004", name: "Daily Needs Store" },
    { code: "OUT-1005", name: "Green Grocery" },
    { code: "OUT-1006", name: "Happy Family Store" },
    { code: "OUT-1007", name: "New Market Traders" },
    { code: "OUT-1008", name: "Rahim General Store" },
]);

const outletCodes = computed(() => outlets.value.map((outlet) => outlet.code));
const outletNames = computed(() => outlets.value.map((outlet) => outlet.name));

const outletRows = reactive({});

weeks.forEach((week) => {
    outletRows[week] = {};
    days.forEach((day) => {
        outletRows[week][day] = [
            {
                is_marked: false,
                outlet_code: null,
                outlet_name: null,
                is_inactive: false,
                is_visited: false,
            },
        ];
    });
});

const addMore = (week, day) => {
    outletRows[week][day].push({
        is_marked: false,
        outlet_code: null,
        outlet_name: null,
        is_inactive: false,
        is_visited: false,
    });
};

/* DELETE MODAL STATE & HANDLERS */
const showDeleteModal = ref(false);
const pendingDeleteTarget = ref({ week: null, day: null, index: null });

const promptRemoveRow = (week, day, index) => {
    pendingDeleteTarget.value = { week, day, index };
    showDeleteModal.value = true;
};

const confirmRemoveRow = () => {
    const { week, day, index } = pendingDeleteTarget.value;
    if (week !== null && day !== null && index !== null) {
        const rows = outletRows[week][day];
        if (rows.length > 1) {
            rows.splice(index, 1);
        }
    }
    closeDeleteModal();
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    pendingDeleteTarget.value = { week: null, day: null, index: null };
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

    const schedules = [];
    const seenCodes = new Set();
    const duplicateCodes = new Set();

    weeks.forEach((week) => {
        days.forEach((day, dayIndex) => {
            const rows = outletRows[week][day].filter(
                (row) => row.outlet_code && row.outlet_name,
            );

            if (rows.length === 0) return;

            const outlets = rows.map((row) => ({
                outlet_code: row.outlet_code,
                outlet_name: row.outlet_name,
            }));

            outlets.forEach((outlet) => {
                if (seenCodes.has(outlet.outlet_code)) {
                    duplicateCodes.add(outlet.outlet_code);
                }
                seenCodes.add(outlet.outlet_code);
            });

            schedules.push({
                outlet_week_schedules_id:
                    (week - 1) * days.length + dayIndex + 1,
                week_no: week,
                day_name: day.toLowerCase(),
                outlets,
            });
        });
    });

    if (duplicateCodes.size > 0) {
        Swal.fire(
            "Warning",
            `Outlet "${[...duplicateCodes].join('", "')}" has been entered more than once. Every outlet must be unique across all weeks.`,
            "warning",
        );
        return null;
    }

    if (schedules.length === 0) {
        Swal.fire("Warning", "No outlet data found to save.", "warning");
        return null;
    }

    return {
        distributor_id: distributor_id.value,
        employee_id: employee_id.value,
        schedules,
    };
};

const save = async () => {
    const payload = buildPayload();
    if (!payload) return;

    const result = await Swal.fire({
        title: "Confirmation",
        html: "<p>Are you sure you want to save this outlet visit setup?</p>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, Save",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#059223d5",
        cancelButtonColor: "#bd360dd5",
    });

    if (!result.isConfirmed) return;

    try {
        const res = await api.post("/outlet-visit-assignment", payload);
        const message =
            res.data?.message || "Outlet visit setup saved successfully.";
        Swal.fire("Success!", message, "success");
    } catch (err) {
        const message =
            err.response?.data?.message ||
            "Failed to save outlet visit setup. Please try again.";
        Swal.fire("Error", message, "error");
    }
};
</script>

<template>
    <div
        class="border rounded container-fluid d-flex flex-column p-0 m-0 position-relative"
    >
        <!-- PAGE HEADER -->
        <div
            class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom"
        >
            <h2 class="header-design p-1 d-flex align-items-center gap-2">
                <i class="fa-solid fa-store icon-design"></i>
                Outlet Visit Setup
            </h2>

            <!-- WEEK NAVIGATION -->
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

        <!-- SELECTED WEEK DAYS -->
        <div class="p-2">
            <div class="row g-2">
                <div v-for="day in days" :key="day" class="col-xl-6 col-12">
                    <div class="day-card border rounded">
                        <!-- DAY HEADER WITH ADD ICON BUTTON -->
                        <div
                            class="day-title d-flex justify-content-between align-items-center"
                        >
                            <span>{{ day }}</span>
                            <button
                                type="button"
                                class="btn btn-xs btn-success add-btn-icon"
                                title="Add Row"
                                @click="addMore(selectedWeek, day)"
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
                                            v-for="(row, index) in outletRows[
                                                selectedWeek
                                            ][day]"
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
                                                    v-model:outlet_code="
                                                        row.outlet_code
                                                    "
                                                    v-model:outlet_name="
                                                        row.outlet_name
                                                    "
                                                    :distributor_id="
                                                        distributor_id
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
                                                    v-if="
                                                        outletRows[
                                                            selectedWeek
                                                        ][day].length > 1
                                                    "
                                                    type="button"
                                                    class="btn btn-xs btn-outline-danger btn-icon"
                                                    title="Remove"
                                                    @click="
                                                        promptRemoveRow(
                                                            selectedWeek,
                                                            day,
                                                            index,
                                                        )
                                                    "
                                                >
                                                    <i
                                                        class="fa-solid fa-trash"
                                                    ></i>
                                                </button>
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

        <!-- SAVE -->
        <div class="text-center py-2">
            <button
                type="button"
                class="btn btn-sm btn-success px-4"
                @click="save"
            >
                <i class="fa-solid fa-floppy-disk me-1"></i>
                Save
            </button>
        </div>

        <!-- CENTERED DELETE CONFIRMATION POP-UP MODAL -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="custom-modal-backdrop">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <i
                            class="fa-solid fa-triangle-exclamation text-warning me-2 fs-5"
                        ></i>
                        <h5 class="m-0 fs-6 fw-bold">Confirmation</h5>
                    </div>
                    <div class="custom-modal-body text-center py-3">
                        <p class="m-0 fs-6 color-dark">
                            Are you sure you want to remove this row?
                        </p>
                    </div>
                    <div
                        class="custom-modal-footer d-flex justify-content-center gap-2"
                    >
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary px-3"
                            @click="closeDeleteModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-danger px-4"
                            @click="confirmRemoveRow"
                        >
                            Okay
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
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

/* CUSTOM CENTERED POP-UP MODAL STYLES */
.custom-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
}

.custom-modal-content {
    background: #ffffff;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
    padding: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    animation: fadeIn 0.2s ease-in-out;
}

.custom-modal-header {
    display: flex;
    align-items: center;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 8px;
}

.custom-modal-footer {
    padding-top: 12px;
    border-top: 1px solid #e9ecef;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
