<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";
import { useStore } from "vuex";
import { useRouter } from "vue-router";

import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

const store = useStore();
const user = store.getters.getUser;
const router = useRouter();

const totalRecords = ref(0);
// -------------------------
// STATE
// -------------------------
const rosterSchedules = ref([]);
const department = ref([]);
const employee = ref([]);

const searchDept = ref("");
const selectedEmployee = ref(null);
const searchDay = ref("");

const isLoading = ref(false);

// pagination
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(40);

// -------------------------
// ROUTING
// -------------------------
const rosterSetup = () => {
    router.push({ name: "createRoster" });
};

const editRoster = (roster) => {
    router.push({
        name: "createRoster",
        query: {
            edit: true,
            id: roster.roster_id,
        },
    });
};

// -------------------------
// FORMATTERS
// -------------------------
const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const formatTime = (timeStr) => {
    if (!timeStr) return "-";

    const [hour, minute] = timeStr.split(":");
    const date = new Date();
    date.setHours(hour, minute);

    return date.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
};

// -------------------------
// FETCH EMPLOYEE
// -------------------------
const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        return;
    }
    try {
        const res = await api.get(`/adjustment-employees/${deptID}`);
        employee.value = res.data;
    } catch (err) {
        console.error(err);
    }
};

// -------------------------
// FETCH DATA (WITH FILTERS + PAGINATION)
// -------------------------
const getData = async (page = 1) => {
    isLoading.value = true;

    try {
        const [deptRes, rosterRes] = await Promise.all([
            api.get(`/department-list/${user.id}`),
            api.get("/roster-schedule", {
                params: {
                    page: page,
                    per_page: perPage.value,
                    department_id: searchDept.value || null,
                    employee_id: selectedEmployee.value?.id || null,
                    day_name: searchDay.value || null,
                },
            }),
        ]);

        department.value = deptRes.data || [];

        const resData = rosterRes.data.data;
        rosterSchedules.value = resData.data;

        currentPage.value = resData.current_page;
        lastPage.value = resData.last_page;

        totalRecords.value = resData.total;
    } catch (err) {
        console.error(err);
    } finally {
        isLoading.value = false;
    }
};

// -------------------------
// FILTER HANDLERS
// -------------------------
const onFilterChange = () => {
    currentPage.value = 1;
    getData(1);
};

const onDeptChange = async () => {
    await getEmployee(searchDept.value);
    selectedEmployee.value = null;
    onFilterChange();
};

const onEmployeeChange = () => {
    onFilterChange();
};

const onDayChange = () => {
    onFilterChange();
};

// -------------------------
// PAGINATION LOGIC (CUSTOM UI)
// -------------------------
const goToPage = (page) => {
    if (page < 1 || page > lastPage.value) return;
    getData(page);
};

// show limited pages (like 27 28 29 etc)
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

// -------------------------
onMounted(() => {
    const today = new Date().toLocaleDateString("en-US", { weekday: "long" });
    searchDay.value = today;

    getData(1);
});
</script>

<template>
    <div class="border rounded container-fluid d-flex flex-column p-0 m-0">
        <!-- HEADER -->
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="header-design">Roster/Schedule Setup</h2>

            <button @click="rosterSetup" class="btn btn-success">
                + New Setup
            </button>
        </div>

        <div class="p-3">
            <!-- FILTER -->
            <div class="row g-3 mb-3">
                <!-- Department -->
                <div class="col-lg-3">
                    <label class="form-label">Select Department</label>
                    <select
                        class="form-control field-height field-background-colour"
                        v-model="searchDept"
                        :class="{ 'placeholder-text': !searchDept }"
                        @change="onDeptChange"
                    >
                        <option value="">Select Department</option>
                        <option
                            v-for="dept in department"
                            :key="dept.department_id"
                            :value="dept.department_id"
                        >
                            {{ dept.department_name }}
                        </option>
                    </select>
                </div>

                <!-- Employee -->
                <div class="col-lg-3">
                    <label class="form-label">Select Employee</label>
                    <Multiselect
                        v-model="selectedEmployee"
                        :options="employee"
                        label="Full_Name"
                        track-by="id"
                        placeholder="Select employee"
                        @select="onEmployeeChange"
                        class="multiselect-design"
                    />
                </div>

                <!-- Day -->
                <div class="col-lg-2">
                    <label class="form-label">Day Name</label>
                    <select
                        class="form-control field-height field-background-colour"
                        v-model="searchDay"
                        @change="onDayChange"
                    >
                        <option value="">Select Day</option>
                        <option>Saturday</option>
                        <option>Sunday</option>
                        <option>Monday</option>
                        <option>Tuesday</option>
                        <option>Wednesday</option>
                        <option>Thursday</option>
                        <option>Friday</option>
                    </select>
                </div>
            </div>
            <div
                class="d-flex align-items-center py-2 fw-semibold border-bottom"
            >
                <span class="me-2">Total Employees:</span>
                <span class="border rounded px-2 py-1 fw-medium">
                    {{ totalRecords }}
                </span>
            </div>

            <!-- TABLE -->
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th class="text-center">SN</th>
                            <th class="text-center">EMPLOYEE ID</th>
                            <th>NAME</th>
                            <th>DEPARTMENT</th>
                            <th class="text-center">DAY</th>
                            <th class="text-center">IN</th>
                            <th class="text-center">OUT</th>
                            <th class="text-center">WORK FROM</th>
                            <th class="text-center">ACTION</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(roster, index) in rosterSchedules"
                            :key="roster.roster_id"
                        >
                            <td class="text-center">
                                {{ (currentPage - 1) * perPage + index + 1 }}
                            </td>
                            <td class="text-center">
                                ABNB-{{ roster.official_id }}
                            </td>
                            <td>{{ roster.employee_name }}</td>
                            <td>{{ roster.department_name }}</td>
                            <td class="text-center">{{ roster.day_name }}</td>
                            <td class="text-center">
                                {{ formatTime(roster.time_in) }}
                            </td>
                            <td class="text-center">
                                {{ formatTime(roster.time_out) }}
                            </td>
                            <td class="text-center">{{ roster.work_from }}</td>

                            <td class="text-center">
                                <button
                                    @click="editRoster(roster)"
                                    class="custom-btn btn-15"
                                >
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                            </td>
                        </tr>

                        <tr v-if="rosterSchedules.length === 0">
                            <td colspan="9" class="text-center">
                                No data found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="d-flex justify-content-end mt-3">
                <!-- First -->
                <button
                    class="btn btn-sm btn-outline-primary mx-1"
                    @click="goToPage(1)"
                    :disabled="currentPage === 1"
                >
                    <<
                </button>

                <!-- Prev -->
                <button
                    class="btn btn-sm btn-outline-primary mx-1"
                    @click="goToPage(currentPage - 1)"
                    :disabled="currentPage === 1"
                >
                    <
                </button>

                <!-- Pages -->
                <button
                    v-for="page in visiblePages()"
                    :key="page"
                    class="btn btn-sm mx-1"
                    :class="
                        page === currentPage
                            ? 'btn-primary'
                            : 'btn-outline-primary'
                    "
                    @click="goToPage(page)"
                >
                    {{ page }}
                </button>

                <!-- Next -->
                <button
                    class="btn btn-sm btn-outline-primary mx-1"
                    @click="goToPage(currentPage + 1)"
                    :disabled="currentPage === lastPage"
                >
                    >
                </button>

                <!-- Last -->
                <button
                    class="btn btn-sm btn-outline-primary mx-1"
                    @click="goToPage(lastPage)"
                    :disabled="currentPage === lastPage"
                >
                    >>
                </button>
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

.form-label {
    font-weight: 600;
    letter-spacing: 0.4px;
}

.cross-icon {
    margin-top: 29px;
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
    color: #999;
    font-size: 14px;
}

.multiselect-design .multiselect__placeholder {
    color: #999;
    font-size: 14px;
    margin-bottom: 0px;
    align-items: center;
}

.multiselect-design .multiselect__tags {
    min-height: 35px !important;
    height: 35px;
    padding: 0 8px;
    border-radius: 5px;
    border: 0.8px solid #b3a9a963;
    display: flex;
    align-items: center;
    font-size: 14px;
}

/* Input inside the multiselect */
.multiselect-design .multiselect__input,
.multiselect-design .multiselect__single {
    height: 100%;
    line-height: 35px;
    padding: 0;
    margin: 0;
    background: transparent;
    border: 0;
    flex: 1;
}

.multiselect-design .multiselect__select {
    height: 35px;
}

.field-background-colour {
    border: 0.8px solid #b3a9a963;
}

.header-design {
    letter-spacing: 1px;
    padding-top: 5px;
    font-weight: 700;
}
</style>
