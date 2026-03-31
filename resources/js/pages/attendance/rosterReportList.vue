<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";
const rosterReports = ref([]);
const searchDate = ref("");

const getTodayDate = () => {
    const today = new Date();
    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(
        2,
        "0",
    )}-${String(today.getDate()).padStart(2, "0")}`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, "0");
    const month = date.toLocaleString("en-US", { month: "short" });
    const year = date.getFullYear();
    return `${day} ${month}, ${year}`;
};

const formatTime = (timeStr) => {
    if (!timeStr) return "";
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
    try {
        const response = await api.get("/roster-report-list", {
            params: {
                searchDate: searchDate.value,
            },
        });

        rosterReports.value = response.data.data ?? response.data;

        console.log("Roster Reports:", rosterReports.value);
    } catch (error) {
        console.error(
            "Error fetching roster reports:",
            error.response?.data || error.message,
        );
    }
};

const handleDateChange = () => {
    getData();
};

onMounted(() => {
    searchDate.value = getTodayDate();
    getData();
});
</script>

<template>
    <div class="boarder rounded container-fluid d-flex flex-column p-0 m-0">
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="m-0 header-design">Roster Report List</h2>
            <div>
                <button
                    type="button"
                    @click="rosterSetup()"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm"
                >
                    <i class="fa-solid fa-plus me-2"></i> Download
                </button>
            </div>
        </div>
        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <!-- Filters -->
                <div class="row g-3 mb-3">
                    <!-- Employee -->

                    <div class="col-lg-3 position-relative">
                        <input
                            type="date"
                            class="form-control field-height field-background-colour"
                            v-model="searchDate"
                            @change="handleDateChange"
                        />
                    </div>
                </div>

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
                                        IN TIME DIFF
                                    </th>
                                    <th width="8%" class="text-center">
                                        OUT TIME DIFF
                                    </th>
                                    <th width="8%" class="text-center">
                                        WORKING HOURS
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(roster, index) in rosterReports"
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
                                        {{ formatDate(roster.attendance_date) }}
                                    </td>
                                    <td class="text-center">
                                        {{
                                            formatTime(
                                                roster.actual_in ??
                                                    roster.app_in_time,
                                            )
                                        }}
                                    </td>

                                    <td class="text-center">
                                        {{
                                            formatTime(
                                                roster.actual_out ??
                                                    roster.app_out_time,
                                            )
                                        }}
                                    </td>
                                    <td class="text-center">
                                        {{ roster.in_diff }}
                                    </td>
                                    <td class="text-center">
                                        {{ roster.out_diff }}
                                    </td>
                                    <td class="text-center">
                                        {{ roster.working_hours }}
                                    </td>
                                </tr>
                            </tbody>

                            <input
                                type="date"
                                class="form-control field-height field-background-colour"
                                v-model="searchDate"
                                @change="handleDateChange"
                            />
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
