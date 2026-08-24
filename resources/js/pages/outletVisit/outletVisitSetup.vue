<script setup>
import { ref, reactive, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useStore } from "vuex";
import api from "@/api";
import Swal from "sweetalert2";
import Label from "@/pages/components/label/Label.vue";
import DistributorName from "@/pages/components/input_field/DistributorName.vue";
import EmployeeName from "@/pages/components/input_field/EmployeeName.vue";
import OutletCode from "@/pages/components/input_field/OutletCode.vue";

const route = useRoute();
const router = useRouter();
const store = useStore();
const role_id = store.getters.getRoleId;

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

const isEditMode = ref(false);
const isViewMode = ref(false);
const isLoadingSetup = ref(false);

/* SINGLE OUTLET DELETE STATE (EDIT MODE ONLY) */
const canDeleteOutlet = ref(false);
const deleting = ref(false);
const loadedSchedules = ref(null);

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

/* SINGLE OUTLET DELETE (EDIT MODE ONLY) */
const escapeHtml = (value) =>
    String(value ?? "").replace(
        /[&<>"']/g,
        (char) =>
            ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;",
            })[char],
    );

const findMarkedOutlets = () => {
    const marked = [];
    weeks.forEach((week) => {
        days.forEach((day) => {
            (outletRows[week][day] || []).forEach((row) => {
                if (row.is_marked && row.outlet_code && row.outlet_name) {
                    marked.push({
                        week,
                        day,
                        row,
                        outlet_code: String(row.outlet_code).trim(),
                    });
                }
            });
        });
    });
    return marked;
};

const hasExistingAssignment = (week, dayName, outletCode) => {
    const savedOutlets = loadedSchedules.value?.[week]?.[dayName] || [];
    return savedOutlets.some(
        (outlet) => String(outlet.outlet_code).trim() === outletCode,
    );
};

const deleteSelectedOutlet = async () => {
    if (!canDeleteOutlet.value || isViewMode.value || role_id != 1) return;

    if (!distributor_id.value || !employee_id.value) {
        Swal.fire(
            "Warning",
            "Please select both a distributor and an employee first.",
            "warning",
        );
        return;
    }

    const markedOutlets = findMarkedOutlets();

    if (markedOutlets.length === 0) {
        Swal.fire(
            "Warning",
            "Please select an outlet first by ticking its checkbox.",
            "warning",
        );
        return;
    }

    if (markedOutlets.length > 1) {
        Swal.fire(
            "Warning",
            "Only one outlet can be deleted at a time. Please select exactly one outlet.",
            "warning",
        );
        return;
    }

    const target = markedOutlets[0];
    const dayName = target.day.toLowerCase();

    if (!target.week || !target.day || !target.outlet_code) {
        Swal.fire(
            "Error",
            "Invalid outlet selection. Week, day and outlet code are required to delete an assignment.",
            "error",
        );
        return;
    }

    if (
        !loadedSchedules.value ||
        !hasExistingAssignment(target.week, dayName, target.outlet_code)
    ) {
        Swal.fire(
            "Error",
            `Outlet "${target.outlet_code}" does not have an existing assignment for this employee in Week ${target.week} - ${target.day}. Nothing was deleted.`,
            "error",
        );
        return;
    }

    const result = await Swal.fire({
        title: "Are you sure?",
        html: `<p style="margin-bottom:8px">You are about to delete the selected outlet assignment.</p>
            <p style="text-align:left;margin-bottom:8px">
                <strong>Week:</strong> ${escapeHtml(target.week)}<br>
                <strong>Day:</strong> ${escapeHtml(target.day)}<br>
                <strong>Outlet Code:</strong> ${escapeHtml(target.outlet_code)}
            </p>
            <p style="margin-bottom:0">This action cannot be undone.</p>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, Delete",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#bd360dd5",
        cancelButtonColor: "#6c757d",
    });

    if (!result.isConfirmed) return;

    deleting.value = true;

    try {
        const res = await api.delete(
            `/outlet-visit-assignment/${employee_id.value}/${distributor_id.value}`,
            {
                data: {
                    week_number: target.week,
                    day_name: dayName,
                    outlet_code: target.outlet_code,
                    distributor_id: distributor_id.value,
                    employee_id: employee_id.value,
                },
            },
        );

        // Remove only the deleted outlet from the current UI/state.
        const rows = outletRows[target.week][target.day];
        const rowIndex = rows.indexOf(target.row);
        if (rowIndex !== -1) {
            if (rows.length > 1) {
                rows.splice(rowIndex, 1);
            } else {
                rows.splice(rowIndex, 1, emptyRow());
            }
        }

        // Keep the cached assignment snapshot in sync.
        const cached = loadedSchedules.value?.[target.week]?.[dayName];
        if (cached) {
            loadedSchedules.value[target.week][dayName] = cached.filter(
                (outlet) =>
                    String(outlet.outlet_code).trim() !== target.outlet_code,
            );
        }

        Swal.fire(
            "Deleted Successfully!",
            res.data?.message ||
                "The selected outlet assignment has been deleted.",
            "success",
        );
    } catch (err) {
        const message =
            err.response?.data?.message ||
            "Failed to delete the selected outlet assignment. Please try again.";
        Swal.fire("Error", message, "error");
    } finally {
        deleting.value = false;
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

    const schedules = [];

    for (const week of weeks) {
        for (const day of days) {
            const rows = outletRows[week][day].filter(
                (row) => row.outlet_code && row.outlet_name,
            );

            if (rows.length === 0) continue;

            const seenCodes = new Set();
            for (const row of rows) {
                const code = String(row.outlet_code).trim();
                if (seenCodes.has(code)) {
                    Swal.fire(
                        "Warning",
                        `Outlet "${code}" is already added for Week ${week} - ${day}. Every outlet must be unique within the same week and day.`,
                        "warning",
                    );
                    return null;
                }
                seenCodes.add(code);
            }

            schedules.push({
                outlet_week_schedules_id:
                    (week - 1) * days.length + days.indexOf(day) + 1,
                week_no: week,
                day_name: day.toLowerCase(),
                outlets: rows.map((row) => ({
                    outlet_code: row.outlet_code,
                    outlet_name: row.outlet_name,
                })),
            });
        }
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

const isOutletDuplicate = (week, day, outletCode, currentIndex) => {
    const code = String(outletCode).trim();
    const rows = outletRows[week][day] || [];
    return rows.some(
        (row, index) =>
            index !== currentIndex &&
            row.outlet_code &&
            String(row.outlet_code).trim() === code,
    );
};

const showOutletDuplicateError = (week, day, outletCode) => {
    Swal.fire(
        "Warning",
        `Outlet "${outletCode}" is already added for Week ${week} - ${day}. Every outlet must be unique within the same week and day.`,
        "warning",
    );
};

let pendingRejectedOutlet = false;

const onSelectOutlet = (week, day, index, outletCode, row) => {
    if (!outletCode) {
        pendingRejectedOutlet = false;
        row.outlet_code = null;
        row.outlet_name = null;
        return;
    }

    if (isOutletDuplicate(week, day, outletCode, index)) {
        pendingRejectedOutlet = true;
        showOutletDuplicateError(week, day, outletCode);
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

const emptyRow = () => ({
    is_marked: false,
    outlet_code: null,
    outlet_name: null,
    is_inactive: false,
    is_visited: false,
});

const resetOutletRows = () => {
    weeks.forEach((week) => {
        days.forEach((day) => {
            outletRows[week][day] = [emptyRow()];
        });
    });
};

const hasSetupData = (schedules) => {
    if (!schedules) return false;
    return weeks.some((week) =>
        days.some(
            (day) => (schedules[week]?.[day.toLowerCase()] || []).length > 0,
        ),
    );
};

const fillOutletRowsFromData = (schedules) => {
    weeks.forEach((week) => {
        days.forEach((day) => {
            const saved = schedules?.[week]?.[day.toLowerCase()] || [];
            outletRows[week][day] = saved.length
                ? saved.map((outlet) => ({
                      is_marked: false,
                      outlet_code: outlet.outlet_code,
                      outlet_name: outlet.outlet_name,
                      is_inactive: false,
                      is_visited: false,
                  }))
                : [emptyRow()];
        });
    });
};

let setupCheckTimer = null;
let setupCheckSeq = 0;

const checkAndLoadSetup = async () => {
    const employeeId = employee_id.value;
    const distributorId = distributor_id.value;

    if (!employeeId || !distributorId) {
        resetOutletRows();
        isEditMode.value = false;
        return;
    }

    const seq = ++setupCheckSeq;
    isLoadingSetup.value = true;

    try {
        const res = await api.get(
            `/outlet-visit-setup/${employeeId}/${distributorId}`,
        );
        if (seq !== setupCheckSeq) return;

        const schedules = res.data?.data?.schedules;

        if (hasSetupData(schedules)) {
            loadedSchedules.value = schedules;
            fillOutletRowsFromData(schedules);
            isEditMode.value = true;
        } else {
            loadedSchedules.value = null;
            resetOutletRows();
            isEditMode.value = false;
        }
    } catch (err) {
        if (seq !== setupCheckSeq) return;

        loadedSchedules.value = null;
        resetOutletRows();
        isEditMode.value = false;
        const message =
            err.response?.data?.message || "Failed to load outlet visit setup.";
        Swal.fire("Error", message, "error");
    } finally {
        if (seq === setupCheckSeq) isLoadingSetup.value = false;
    }
};

const scheduleSetupCheck = () => {
    if (setupCheckTimer) clearTimeout(setupCheckTimer);
    loadedSchedules.value = null;
    resetOutletRows();
    isEditMode.value = false;
    setupCheckTimer = setTimeout(checkAndLoadSetup, 250);
};

watch([distributor_id, employee_id], scheduleSetupCheck);

onMounted(() => {
    const employeeId = route.query.employee_id;
    const distributorId = route.query.distributor_id;

    if (employeeId) employee_id.value = Number(employeeId);
    if (distributorId) distributor_id.value = Number(distributorId);

    isViewMode.value = route.query.mode === "view";
    canDeleteOutlet.value = route.query.mode === "edit" && !isViewMode.value;
});

const goBackToList = () => {
    router.push({ name: "SetupList" });
};

const save = async () => {
    const payload = buildPayload();
    if (!payload) return;

    const actionLabel = isEditMode.value ? "Update" : "Save";
    const result = await Swal.fire({
        title: "Confirmation",
        html: `<p>Are you sure you want to ${actionLabel.toLowerCase()} this outlet visit setup?</p>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: `Yes, ${actionLabel}`,
        cancelButtonText: "Cancel",
        confirmButtonColor: "#059223d5",
        cancelButtonColor: "#bd360dd5",
    });

    if (!result.isConfirmed) return;

    try {
        let res;
        if (isEditMode.value) {
            res = await api.put(
                `/outlet-visit-assignment/${payload.employee_id}/${payload.distributor_id}`,
                payload,
            );
        } else {
            res = await api.post("/outlet-visit-assignment", payload);
        }
        const message =
            res.data?.message ||
            `Outlet visit setup ${actionLabel.toLowerCase()}d successfully.`;
        Swal.fire("Success!", message, "success");
    } catch (err) {
        const message =
            err.response?.data?.message ||
            `Failed to ${actionLabel.toLowerCase()} outlet visit setup. Please try again.`;
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
                <span
                    v-if="isViewMode"
                    class="badge bg-secondary ms-1"
                    style="font-size: 10px"
                >
                    View Mode
                </span>
                <span
                    v-else-if="isEditMode"
                    class="badge bg-success ms-1"
                    style="font-size: 10px"
                >
                    Edit Mode
                </span>
            </h2>

            <!-- WEEK NAVIGATION -->
            <div class="d-flex align-items-center gap-2">
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
                <button
                    v-if="isEditMode || isViewMode"
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click="goBackToList"
                >
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Back
                </button>
            </div>
        </div>

        <!-- SETUP INFORMATION -->
        <div class="px-2 mt-4">
            <div
                class="setup-section p-1"
                :class="{ 'readonly-block': isViewMode }"
            >
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
                                v-if="!isViewMode"
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
                                                    :disabled="isViewMode"
                                                />
                                            </td>

                                            <!-- OUTLET CODE -->
                                            <td>
                                                <OutletCode
                                                    v-if="!isViewMode"
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
                                                            selectedWeek,
                                                            day,
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
                                                <input
                                                    v-else
                                                    :value="row.outlet_code"
                                                    type="text"
                                                    class="form-control"
                                                    readonly
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
                                                    :disabled="isViewMode"
                                                />
                                            </td>

                                            <!-- ACTION (REMOVE) -->
                                            <td class="text-center">
                                                <button
                                                    v-if="
                                                        !isViewMode &&
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

        <!-- SAVE / DELETE -->
        <div class="text-center py-2">
            <button
                v-if="isViewMode"
                type="button"
                class="btn btn-sm btn-secondary px-4"
                @click="goBackToList"
            >
                <i class="fa-solid fa-arrow-left me-1"></i>
                Back to List
            </button>
            <template v-else>
                <button
                    type="button"
                    class="btn btn-sm btn-success px-4"
                    @click="save"
                >
                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    {{ isEditMode ? "Update" : "Save" }}
                </button>
                <button
                    v-if="canDeleteOutlet && role_id == 1"
                    type="button"
                    class="btn btn-sm btn-outline-danger px-3 ms-2"
                    :disabled="deleting"
                    title="Delete the selected (marked) outlet assignment"
                    @click="deleteSelectedOutlet"
                >
                    <i class="fa-solid fa-trash me-1"></i>
                    Delete Selected Outlet
                </button>
            </template>
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

.readonly-block {
    pointer-events: none;
    opacity: 0.7;
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
