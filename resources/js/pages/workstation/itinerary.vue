<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";
import { useRouter } from "vue-router";

const store = useStore();
const user = store.getters.getUser;
const router = useRouter();

const itineraries_items = ref([]);
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
const error = ref("");

// ----------------------------
// NAVIGATION FUNCTIONS
// ----------------------------
const viewItems = (id) => {
    router.push(`/workstation/itinerary/view/${id}`);
};

const editItems = (id) => {
    router.push(`/workstation/itinerary/edit/${id}`);
};

const newitineraryOpen = () => {
    router.push(`/workstation/itinerary/new`); // <-- make sure this route exists in router.js
};

// ----------------------------
// API ACTIONS
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

    try {
        await api.post(`/itinerary/submit/${id}`);
        Swal.fire({
            icon: "success",
            title: "Submitted Successfully!",
            timer: 1500,
            showConfirmButton: false,
        });
        getData();
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: err.message || "Something went wrong",
        });
    }
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

    try {
        await api.post(`/itinerary/accept/${id}`);
        Swal.fire({
            icon: "success",
            title: "Accepted Successfully!",
            timer: 1500,
            showConfirmButton: false,
        });
        getData();
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: err.message || "Something went wrong",
        });
    }
};

const rejectedItems = async (id) => {
    const { value: remarks, isConfirmed } = await Swal.fire({
        title: "Write Remarks",
        input: "text",
        inputPlaceholder: "Enter rejection remarks...",
        showCancelButton: true,
        confirmButtonText: "Reject",
        preConfirm: (value) => {
            if (!value || value.trim() === "") {
                Swal.showValidationMessage("Remarks cannot be empty!");
            }
            return value;
        },
    });

    if (!isConfirmed) return;

    try {
        await api.post(`/itinerary/reject/${id}`, { remarks });
        Swal.fire({
            icon: "success",
            title: "Rejected Successfully!",
            text: "The itinerary has been rejected.",
            timer: 1500,
            showConfirmButton: false,
        });
        getData();
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: err.message || "Something went wrong!",
        });
    }
};

// ----------------------------
// COMPUTED & DATA FETCHING
// ----------------------------
const visibleItineraries = computed(() =>
    itineraries_items.value.filter((item) => {
        const isEmployee = Number(item.employee_id) === Number(user.id);
        const isSupervisor = Number(item.supervisor_id) === Number(user.id);

        if (isEmployee) return true;
        if (isSupervisor && item.status !== "save") return true;
        return false;
    })
);

const getData = async (
    year = selectedYear.value,
    month = selectedMonth.value
) => {
    try {
        const res = await api.get("/itinerary-items", {
            params: { year, month },
        });
        itineraries_items.value = res.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const changeDate = () => {
    getData(selectedYear.value, selectedMonth.value);
};

onMounted(() => getData());
</script>

<template>
    <div class="border rounded p-3">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Itinerary List</h3>
            <div>
                <button class="btn btn-success me-2" @click="newitineraryOpen">
                    <i class="fa-solid fa-plus me-2"></i>New Itinerary
                </button>
            </div>
        </div>

        <div class="col-lg-12 mt-3">
            <div class="mb-3">
                <div class="row g-3 mb-3">
                    <div class="col-lg-1 position-relative">
                        <label> Select Year </label>
                        <select
                            class="form-control border-color"
                            v-model="selectedYear"
                            @change="changeDate"
                        >
                            <option
                                v-for="year in years"
                                :key="year"
                                :value="year"
                            >
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
                            <option
                                v-for="(month, index) in months"
                                :key="month"
                                :value="index + 1"
                            >
                                {{ month }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-6">
                    <div class="table-responsiv width-define">
                        <table
                            class="table table-bordered table-hover table-sm"
                        >
                            <thead class="border table-light">
                                <tr>
                                    <th>SL</th>
                                    <th>NAME</th>
                                    <th>DESIGNATION</th>
                                    <th>SUBMITTED DATE</th>
                                    <th>STATUS</th>
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
                                    <td>{{ item.submitted_date }}</td>
                                    <td>{{ item.status }}</td>
                                    <td class="text-center">
                                        <button
                                            v-if="
                                                (item.status == 'save' ||
                                                    item.status ==
                                                        'rejected') &&
                                                Number(item.employee_id) ===
                                                    Number(user.id)
                                            "
                                            @click="
                                                editItems(
                                                    item.itinerary_logs_id
                                                )
                                            "
                                            class="btn btn-sm edit-btn me-1"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            @click="
                                                viewItems(
                                                    item.itinerary_logs_id
                                                )
                                            "
                                            class="btn btn-sm view-btn me-1"
                                        >
                                            View
                                        </button>

                                        <button
                                            v-if="
                                                (item.status == 'save' ||
                                                    item.status ==
                                                        'rejected') &&
                                                Number(item.employee_id) ===
                                                    Number(user.id)
                                            "
                                            @click="
                                                saveItems(
                                                    item.itinerary_logs_id
                                                )
                                            "
                                            class="btn btn-sm submit-btn me-1"
                                        >
                                            Submit
                                        </button>

                                        <button
                                            v-if="
                                                item.status != 'approved' &&
                                                Number(item.supervisor_id) ===
                                                    Number(user.id)
                                            "
                                            @click="
                                                acceptItems(
                                                    item.itinerary_logs_id
                                                )
                                            "
                                            class="btn btn-sm accept-btn me-1"
                                        >
                                            Accept
                                        </button>

                                        <button
                                            v-if="
                                                item.status != 'approved' &&
                                                item.status != 'rejected' &&
                                                Number(item.supervisor_id) ===
                                                    Number(user.id)
                                            "
                                            @click="
                                                rejectedItems(
                                                    item.itinerary_logs_id
                                                )
                                            "
                                            class="btn btn-sm reject-btn"
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
    border-radius: none;
}
.width-define {
    width: 100%;
    margin: 0;
}
.edit-btn {
    background-color: #0066ff !important;
    color: white !important;
    border: none !important;
}
.view-btn {
    background-color: #6c757d !important;
    color: white !important;
    border: none !important;
}
.submit-btn {
    background-color: #ff9900 !important;
    color: black !important;
    border: none !important;
}
.accept-btn {
    background-color: #28a745 !important;
    color: white !important;
    border: none !important;
}
.reject-btn {
    background-color: #dc3545 !important;
    color: white !important;
    border: none !important;
}
</style>
