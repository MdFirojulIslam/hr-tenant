<script setup>
import { defineAsyncComponent, ref, onMounted } from "vue";
import api from "@/api";
import { useStore } from "vuex";
import "vue-multiselect/dist/vue-multiselect.min.css";
const store = useStore();
const user = store.getters.getUser;

const rosterSchedules = ref([]);
const department = ref([]);
const searchDept = ref("");
const searchEmp = ref("");

const selectedRoster = ref(null);

const isLoading = ref(false);
const error = ref(null);

const rosterSetupComponent = defineAsyncComponent(
    () => import("./component/rosterSetup.vue"),
);

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, "0");
    const month = date.toLocaleString("en-US", { month: "short" });
    const year = date.getFullYear();
    return `${day} ${month}, ${year}`;
};

const formatTime = (timeStr) => {
    const [hour, minute] = timeStr.split(":");

    const date = new Date();
    date.setHours(hour, minute);

    return date.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
};

const getData = async () => {
    isLoading.value = true;
    try {
        const [deptRes, rosterRes] = await Promise.all([
            api.get(`/department-list/${user.id}`),
            api.get("/roster-schedule"),
        ]);

        department.value = deptRes.data;
        rosterSchedules.value = rosterRes.data;
        console.log(rosterSchedules.value);
    } catch (err) {
        error.value = err.message;
    } finally {
        isLoading.value = false;
    }
};

const rosterSetupModal = ref(false);
const rosterSetup = () => {
    rosterSetupModal.value = true;
};
const closeRosterSetupModal = () => {
    rosterSetupModal.value = false;
    selectedRoster.value = null;
};
const editRoster = (roster) => {
    selectedRoster.value = roster;
    rosterSetupModal.value = true;
};
onMounted(() => {
    getData();
});
</script>

<template>
    <component
        v-if="rosterSetupModal"
        :is="rosterSetupComponent"
        :isOpen="rosterSetupModal"
        :editData="selectedRoster"
        @close="closeRosterSetupModal"
        @refresh="getData"
    />
    <div class="boarder rounded container-fluid d-flex flex-column p-0 m-0">
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="m-0 header-design">Roster Setup List</h2>
            <div>
                <button
                    type="button"
                    @click="rosterSetup()"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm"
                >
                    <i class="fa-solid fa-plus me-2"></i> New Setup
                </button>
            </div>
        </div>
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <!-- Filters -->

                <!-- Salary Table -->
                <div class="mt-3">
                    <!-- Total Employees Row -->
                    <div
                        class="d-flex align-items-center py-2 fw-semibold border-bottom bg-light"
                    >
                        <span class="me-2">Total Employees:</span>
                        <span
                            class="bg-white border rounded px-2 py-1 fw-medium"
                        >
                        </span>
                    </div>
                    <div class="table-responsive shadow-sm bg-light">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr class="text-nowrap">
                                    <th width="3%" class="text-center">S/N</th>
                                    <th width="8%" class="text-center">
                                        EMPLOYEE ID
                                    </th>
                                    <th>EMPLOYEE NAME</th>
                                    <th>DEPARTMENT</th>
                                    <th width="8%" class="text-center">DATE</th>
                                    <th width="8%" class="text-center">
                                        IN TIME
                                    </th>
                                    <th width="8%" class="text-center">
                                        OUT TIME
                                    </th>
                                    <th width="8%" class="text-center">
                                        WORK FROM
                                    </th>
                                    <th width="5%" class="text-center">
                                        ACTION
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="(
                                        roster, index
                                    ) in rosterSchedules.data"
                                    :key="roster.id"
                                    class="text-nowrap"
                                >
                                    <td class="text-center">{{ index + 1 }}</td>
                                    <td class="text-center">
                                        ABNB-{{ roster.official_id }}
                                    </td>
                                    <td>{{ roster.employee_name }}</td>
                                    <td>{{ roster.department_name }}</td>
                                    <td class="text-center">
                                        {{ formatDate(roster.effective_from) }}
                                    </td>
                                    <td class="text-center">
                                        {{ formatTime(roster.time_in) }}
                                    </td>
                                    <td class="text-center">
                                        {{ formatTime(roster.time_out) }}
                                    </td>
                                    <td class="text-center">
                                        {{ roster.work_from }}
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button
                                            class="btn btn-sm btn-light border me-1"
                                            title="Edit"
                                            @click="editRoster(roster)"
                                        >
                                            <i
                                                class="fa-regular fa-pen-to-square text-warning"
                                            ></i>
                                        </button>
                                        <button
                                            class="btn btn-sm btn-light border"
                                            title="Delete"
                                            @click="
                                                deleteRoster(roster.roster_id)
                                            "
                                        >
                                            <i
                                                class="fa-solid fa-trash text-danger"
                                            ></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-end mt-0"></div>
            </div>
        </div>
    </div>
</template>

<style>
.table-responsive .table,
.table-responsive .table tbody,
.table-responsive .table tr,
.table-responsive .table td {
    /* background-color: rgba(234, 236, 234, 0.2) !important; */
    background: #ffffffd5 !important;
    color: #000000;
    border: 0.8px solid #7e7c7c63;
    letter-spacing: 0.3px;
}

.field-hover:hover {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.table tbody tr:hover > td {
    background-color: #717c73c7 !important;
}

.table thead th {
    background-color: #4e4e4e;
    font-weight: 700;
    color: #fff;
}

.table thead {
    height: 30px;
}

.field-height {
    height: 35px;
}
.placeholder-text {
    color: #999; /* faded text for placeholder */
    font-size: 14px;
}
.multiselect-design .multiselect__placeholder {
    color: #999;
    font-size: 14px;
    margin-bottom: 0px;
    align-items: center;
}

.multiselect-design .multiselect__tags {
    min-height: 35px !important; /* override default min-height */
    height: 35px; /* exact height */
    padding: 0 8px; /* adjust left/right padding */
    border-radius: 5px;
    border: 0.8px solid #b3a9a963;
    background: #c4c2c250;
    display: flex; /* align input vertically */
    align-items: center;
    font-size: 14px;
}

/* Input inside the multiselect */
.multiselect-design .multiselect__input,
.multiselect-design .multiselect__single {
    height: 100%; /* fill the tags container height */
    line-height: 35px; /* match container for vertical centering */
    padding: 0; /* remove extra padding */
    margin: 0;
    background: transparent; /* match container bg */
    border: 0; /* remove default border */
    flex: 1; /* grow to fill remaining space */
}

.multiselect-design .multiselect__select {
    height: 35px;
}

.field-background-colour {
    background: #c4c2c250;
    border: 0.8px solid #b3a9a963;
}
.header-design {
    letter-spacing: 1px;
    padding-top: 5px;
    /* background-color: #d6d6d6f5;

    border-radius: 5px; */
}
</style>
