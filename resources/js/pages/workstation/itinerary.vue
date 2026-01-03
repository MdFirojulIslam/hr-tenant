<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";
import { useRouter } from "vue-router";

// ----------------------------
// BASIC SETUP
// ----------------------------
const store = useStore();
const user = store.getters.getUser;
const router = useRouter();

const itineraries_items = ref([]);
const employeeIDs = ref([]);
const supervisorID = ref(null);

const years = ref([
    "2022",
    "2023",
    "2024",
    "2025",
    "2026",
    "2027",
    "2028",
    "2029",
    "2030",
    "2031",
    "2032",
    "2033",
    "2034",
    "2035",
]);

const months = ref([
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
]);

const selectedYear = ref(new Date().getFullYear());
const selectedMonth = ref(new Date().getMonth() + 1);
const searchEmp = ref("");

// ----------------------------
// NAVIGATION
// ----------------------------
const viewItems = (id) => router.push(`/workstation/itinerary/view/${id}`);
const editItems = (id) => router.push(`/workstation/itinerary/edit/${id}`);
const newitineraryOpen = () => router.push(`/workstation/itinerary/new`);

const formatMonthYear = (dateString) => {
    const date = new Date(dateString);
    if (isNaN(date)) return "";
    return date.toLocaleString("default", { month: "long", year: "numeric" });
};

// ----------------------------
// API CALLS
// ----------------------------
const getData = async (
    year = selectedYear.value,
    month = selectedMonth.value,
    employee_id = searchEmp.value
) => {
    const res = await api.get("/itinerary-items", {
        params: { year, month, employee_id },
    });
    itineraries_items.value = res.data;
};

const loadEmployees = async () => {
    const [employeeRes, supervisorRes] = await Promise.all([
        api.get("/employee-list"),
        api.get("/supervisor-list"),
    ]);
    // const res = await api.get("/employee-list");
    employeeIDs.value = employeeRes.data;
    supervisorID.value = supervisorRes.data[0]?.supervisor_id;
};

// ----------------------------
// FILTERED LIST
// ----------------------------
const visibleItineraries = computed(() =>
    itineraries_items.value.filter((item) => {
        const isEmployee = Number(item.employee_id) === Number(user.id);
        const isSupervisor = Number(item.supervisor_id) === Number(user.id);
        if (isEmployee) return true;
        if (isSupervisor && item.status !== "SAVE") return true;
        return false;
    })
);

// ----------------------------
// STATUS ACTIONS
// ----------------------------
const saveItems = async (id) => {
    const confirm = await Swal.fire({
        title: "Are you sure?",
        text: "Do you want to Submit this Itinerary?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, Submit",
    });
    if (!confirm.isConfirmed) return;
    await api.get(`/itinerary-sender-mail/${id}`);
    await api.post(`/itinerary/submit/${id}`);
    Swal.fire({
        icon: "success",
        title: "Submitted!",
        timer: 1500,
    });
    Swal.fire({ icon: "success", title: "Submitted!", timer: 1500 });
    getData();
};

const acceptItems = async (id) => {
    const confirm = await Swal.fire({
        title: "Are you sure?",
        text: "Do you want to Accept this Itinerary?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, Accept",
    });
    if (!confirm.isConfirmed) return;
    // await api.get(`/itinerary-accept-mail
    // /${id}`);
    await api.post(`/itinerary/accept/${id}`);
    Swal.fire({ icon: "success", title: "Accepted!", timer: 1500 });
    getData();
};

const rejectedItems = async (id) => {
    const { value: remarks, isConfirmed } = await Swal.fire({
        title: "Write Remarks",
        input: "text",
        inputPlaceholder: "Enter rejection remarks...",
        showCancelButton: true,
        confirmButtonText: "Reject",
        preConfirm: (value) => {
            if (!value.trim()) Swal.showValidationMessage("Remarks required!");
        },
    });

    if (!isConfirmed) return;

    await api.post(`/itinerary/reject/${id}`, { remarks });
    Swal.fire({
        icon: "success",
        title: "Rejected!",
        timer: 1500,
    });

    getData();
};

// ----------------------------
// HANDLERS
// ----------------------------
const changeDate = () => {
    getData(selectedYear.value, selectedMonth.value, searchEmp.value);
};

onMounted(() => {
    loadEmployees();
    getData();
});
</script>

<template>
    <div class="border rounded p-3">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Itinerary List</h3>

            <button class="btn btn-success me-2" @click="newitineraryOpen">
                <i class="fa-solid fa-plus me-2"></i>New Itinerary
            </button>
        </div>

        <div class="col-lg-12 mt-3">
            <div class="mb-3">
                <!-- FILTERS -->
                <div class="row g-3 mb-3">
                    <div class="col-lg-1">
                        <label>Select Year</label>
                        <select
                            class="form-control border-color"
                            v-model="selectedYear"
                            @change="changeDate"
                        >
                            <option v-for="year in years" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>

                    <div class="col-lg-1">
                        <label>Select Month</label>
                        <select
                            class="form-control border-color"
                            v-model="selectedMonth"
                            @change="changeDate"
                        >
                            <option v-for="(m, i) in months" :value="i + 1">
                                {{ m }}
                            </option>
                        </select>
                    </div>

                    <div
                        v-if="Number(supervisorID) == user.id"
                        class="col-lg-3 position-relative"
                    >
                        <label>Employees</label>
                        <select
                            class="form-control border-color"
                            v-model="searchEmp"
                            @change="changeDate"
                        >
                            <option :disabled value="">
                                Select Employee Name
                            </option>
                            <option
                                v-for="emp in employeeIDs"
                                :value="emp.employee_id"
                                :key="emp.employee_id"
                            >
                                {{ emp.employee_name }}
                            </option>
                        </select>

                        <button
                            v-if="searchEmp"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-4 me-1"
                            @click="
                                searchEmp = '';
                                changeDate();
                            "
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="d-flex justify-content-center mt-2">
                    <div class="table-responsive width-define">
                        <table
                            class="table table-bordered table-hover table-sm"
                        >
                            <thead class="table-light">
                                <tr>
                                    <th>SL</th>
                                    <th>NAME</th>
                                    <th>DESIGNATION</th>
                                    <th>DATE</th>
                                    <th>STATUS</th>
                                    <th>REMARKS</th>
                                    <th class="text-center">ACTION</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="(item, index) in visibleItineraries"
                                    :key="item.itinerary_logs_id"
                                >
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ item.employee_name }}</td>
                                    <td>{{ item.designation_name }}</td>
                                    <td>
                                        {{
                                            formatMonthYear(item.submitted_date)
                                        }}
                                    </td>
                                    <td>{{ item.status }}</td>
                                    <td>
                                        {{ item.supervisor_remarks ?? "-" }}
                                    </td>

                                    <td class="text-center">
                                        <button
                                            v-if="
                                                [
                                                    'SAVE',
                                                    'REJECTED',
                                                    'RECHECK',
                                                ].includes(item.status) &&
                                                Number(item.employee_id) ===
                                                    Number(user.id)
                                            "
                                            class="btn btn-sm edit-btn me-1"
                                            @click="
                                                editItems(
                                                    item.itinerary_logs_id
                                                )
                                            "
                                        >
                                            Edit
                                        </button>

                                        <button
                                            class="btn btn-sm view-btn me-1"
                                            @click="
                                                viewItems(
                                                    item.itinerary_logs_id
                                                )
                                            "
                                        >
                                            View
                                        </button>

                                        <button
                                            v-if="
                                                ['SAVE', 'REJECTED'].includes(
                                                    item.status
                                                ) &&
                                                Number(item.employee_id) ===
                                                    Number(user.id)
                                            "
                                            class="btn btn-sm submit-btn me-1"
                                            @click="
                                                saveItems(
                                                    item.itinerary_logs_id
                                                )
                                            "
                                        >
                                            Submit
                                        </button>

                                        <button
                                            v-if="
                                                item.status !== 'APPROVED' &&
                                                Number(item.supervisor_id) ===
                                                    Number(user.id)
                                            "
                                            class="btn btn-sm accept-btn me-1"
                                            @click="
                                                acceptItems(
                                                    item.itinerary_logs_id
                                                )
                                            "
                                        >
                                            Accept
                                        </button>

                                        <button
                                            v-if="
                                                item.status !== 'APPROVED' &&
                                                item.status !== 'REJECTED' &&
                                                Number(item.supervisor_id) ===
                                                    Number(user.id)
                                            "
                                            class="btn btn-sm reject-btn"
                                            @click="
                                                rejectedItems(
                                                    item.itinerary_logs_id
                                                )
                                            "
                                        >
                                            Reject
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
</template>

<style>
.border-color {
    border: 1px solid #059223d5;
}

.width-define {
    width: 100%;
}

.edit-btn {
    background-color: #0066ff !important;
    color: white !important;
}

.view-btn {
    background-color: #6c757d !important;
    color: white !important;
}

.submit-btn {
    background-color: #ff9900 !important;
    color: black !important;
}

.accept-btn {
    background-color: #28a745 !important;
    color: white !important;
}

.reject-btn {
    background-color: #dc3545 !important;
    color: white !important;
}
</style>
