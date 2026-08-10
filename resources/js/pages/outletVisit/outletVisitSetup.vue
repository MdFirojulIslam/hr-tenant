<script setup>
import { ref, reactive, computed } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

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
        outletRows[week][day] = [{ outlet_code: null, outlet_name: null }];
    });
});

const addMore = (week, day) => {
    outletRows[week][day].push({ outlet_code: null, outlet_name: null });
};

const removeRow = (week, day, index) => {
    const rows = outletRows[week][day];

    if (rows.length > 1) {
        rows.splice(index, 1);
    }
};

const save = () => {};
</script>

<template>
    <div class="border rounded container-fluid d-flex flex-column p-0 m-0">
        <!-- PAGE HEADER -->
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="header-design mb-0">Outlet Visit Setup</h2>
        </div>

        <!-- SETUP INFORMATION -->
        <div class="px-3 pt-3">
            <div class="setup-section border rounded p-3">
                <h6 class="setup-title">Setup Information</h6>
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label">Distributor Name</label>
                        <Multiselect
                            v-model="selectedDistributor"
                            :options="distributors"
                            label="name"
                            track-by="id"
                            placeholder="Select Distributor Name"
                            class="multiselect-design"
                        />
                    </div>

                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label">Employee Name</label>
                        <Multiselect
                            v-model="selectedEmployee"
                            :options="employees"
                            label="name"
                            track-by="id"
                            placeholder="Select Employee Name"
                            class="multiselect-design"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- WEEK NAVIGATION -->
        <div class="px-3 pt-3">
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

        <!-- SELECTED WEEK DAYS -->
        <div class="p-3">
            <div class="row g-3">
                <div v-for="day in days" :key="day" class="col-xl-6 col-12">
                    <div class="day-card border rounded">
                        <div class="day-title">{{ day }}</div>

                        <div class="p-3">
                            <div
                                v-for="(row, index) in outletRows[selectedWeek][
                                    day
                                ]"
                                :key="index"
                                class="outlet-row border rounded p-2 mb-2"
                            >
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-6 col-12">
                                        <label class="form-label small-label">
                                            Outlet Code
                                        </label>
                                        <Multiselect
                                            v-model="row.outlet_code"
                                            :options="outletCodes"
                                            placeholder="Select Outlet Code"
                                            class="multiselect-design"
                                        />
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <label class="form-label small-label">
                                            Outlet Name
                                        </label>
                                        <Multiselect
                                            v-model="row.outlet_name"
                                            :options="outletNames"
                                            placeholder="Select Outlet Name"
                                            class="multiselect-design"
                                        />
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        outletRows[selectedWeek][day].length > 1
                                    "
                                    class="text-end mt-2"
                                >
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="
                                            removeRow(selectedWeek, day, index)
                                        "
                                    >
                                        <i class="fa-solid fa-trash me-1"></i>
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div class="text-start">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-success"
                                    @click="addMore(selectedWeek, day)"
                                >
                                    <i class="fa-solid fa-plus me-1"></i>
                                    Add More
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAVE -->
        <div class="text-center py-3">
            <button type="button" class="btn btn-success px-5" @click="save">
                <i class="fa-solid fa-floppy-disk me-1"></i>
                Save
            </button>
        </div>
    </div>
</template>

<style scoped>
.header-design {
    letter-spacing: 1px;
    padding-top: 5px;
    font-weight: 700;
    font-size: 22px;
    color: #212529;
}

.form-label {
    font-weight: 600;
    letter-spacing: 0.4px;
}

.small-label {
    font-size: 13px;
    margin-bottom: 4px;
}

.setup-section {
    background: #f8f9fa;
}

.setup-title {
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #059223d5;
    text-transform: uppercase;
    font-size: 14px;
    margin-bottom: 14px;
}

.week-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.week-tab {
    padding: 8px 24px;
    border: 1px solid #c4c2c2;
    border-radius: 20px;
    background: #ffffff;
    color: #495057;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.5px;
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
    overflow: hidden;
}

.day-title {
    background: #4e4e4e;
    color: #ffffff;
    padding: 8px 14px;
    font-size: 15px;
    letter-spacing: 1px;
    text-transform: uppercase;
    font-weight: 600;
}

.outlet-row {
    background: #ffffff;
}

.outlet-row .form-label {
    color: #495057;
}

.multiselect-design :deep(.multiselect__placeholder) {
    color: #999;
    font-size: 14px;
    margin-bottom: 0;
    align-items: center;
}

.multiselect-design :deep(.multiselect__tags) {
    min-height: 35px !important;
    height: 35px;
    padding: 0 8px;
    border-radius: 5px;
    border: 0.8px solid #b3a9a963;
    display: flex;
    align-items: center;
    font-size: 14px;
}

.multiselect-design :deep(.multiselect__input),
.multiselect-design :deep(.multiselect__single) {
    height: 100%;
    line-height: 35px;
    padding: 0;
    margin: 0;
    background: transparent;
    border: 0;
    flex: 1;
}

.multiselect-design :deep(.multiselect__select) {
    height: 35px;
}
</style>
