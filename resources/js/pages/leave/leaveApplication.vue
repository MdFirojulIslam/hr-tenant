<script setup>
import { ref, onMounted, computed, defineAsyncComponent } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import api from "@/api";
import { asyncComputed } from "@vueuse/core";

const leave_information = ref([]);

const error = ref([]);
const existMsg = ref("");
const fileSizeWarning = ref();
const leaveType = ref();
const selectedType = ref("");
const selectedStatus = ref("");
const loading = ref(false);

//modal-status
const newLeaveApplication = ref(false);
const heading = ref("");

const form = ref({
    department_id: "",
    employee_id: "",
    from_date: "",
    to_date: "",
    purpose: "",
    leave_type_id: "",
    status: "",
    file: null,
});

const getData = async () => {
    try {
        const [responseemp, responsetype] = await axios.all([
            api.get("/leave-information"),
            api.get("/leave-type"),
        ]);
        leave_information.value = responseemp.data;
        leaveType.value = responsetype.data;
        form.value.employee_id = leave_information.value.employee.id;
    } catch (err) {
        console.error(err.message);
    } finally {
    }
};

const leaveSetupComponent = defineAsyncComponent(() =>
    import("./component/LeaveApplicationForm.vue")
);

const leaveSetupOpen = (title) => {
    heading.value = title;
    newLeaveApplication.value = true;
};

const newLeaveSetupClose = () => {
    newLeaveApplication.value = false;
    getData();
};

const filteredData = computed(() => {
    // Check if leave_information.value.employee is defined and not null
    if (!leave_information.value || !leave_information.value.leaves) {
        return [];
    }

    const leaveArray = Object.values(leave_information.value.leaves);

    // Apply filter on the array
    return leaveArray.filter((item) => {
        return (
            (selectedType.value === "" ||
                item.leave_type.Name === selectedType.value) &&
            (selectedStatus.value === "" ||
                item.Status === selectedStatus.value)
        );
    });
});

const totalLeaveDays = computed(() => {
    const totals = {};

    if (Array.isArray(leaveType.value) && leaveType.value.length > 0) {
        leaveType.value.forEach((type) => {
            totals[type.Name] = {
                totalDays: 0,
                maxDays: type.Name === "Earned" ? leave_information.value.earnedLeave : type.Max_Days || 0,
            };
        });

        if (!leave_information.value || !leave_information.value.leaves) {
            return totals;
        }

        const leaveArray = Object.values(leave_information.value.leaves);

        leaveArray.forEach((item) => {
            const typeName = item.leave_type;
            if (!totals[typeName]) {
                totals[typeName] = {
                    totalDays: 0,
                    maxDays: type.Max_Days,
                };
            }

            if (typeName === "Earned Leave") {
                totals[typeName].maxDays = leaveType.value.find(
                    (type) => type.Name === "Earned Leave"
                )?.Max_Days;
            }

            if (item.Status === "Approved") {
                totals[typeName].totalDays += item.daysBetween;
            }
        });
    }

    return totals;
});


const getImage = (e) => {
    const file = e.target.files[0];
    if (file.size > 512000) {
        fileSizeWarning.value = true;
        e.target.value = null;
    } else {
        fileSizeWarning.value = false;
        form.file = e.target.files[0];
    }
};

const submit = async () => {
    form.value.Status = "Pending";
    loading.value = true;

    const selectedLeaveType = leaveType.value.find(
        (leave) => leave.id === form.value.Leave_Type_Id
    );

    if (
        selectedLeaveType &&
        selectedLeaveType.Name === "Sick" &&
        totalDays.value >= 3 &&
        !form.file
    ) {
        Swal.fire({
            icon: "error",
            title: "Validation Error",
            text: "For sick leave of 2 days or more, you must attach a document.",
        });
        loading.value = false;
        return;
    }

    const config = {
        headers: {
            "Content-Type": "multipart/form-data",
        },
    };
    const formdata = new FormData();
    formdata.append("file", form.file);

    for (const key in form.value) {
        if (form.value.hasOwnProperty(key) && key !== "file") {
            formdata.append(key, form.value[key]);
        }
    }

    try {
        const response = await api.post("/leave", formdata, config);
        existMsg.value = response.data.message;
        if (response.data.success) {
            loading.value = false;
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Application has been saved",
                showConfirmButton: false,
                timer: 1500,
            });
            await api.get(`/leave-req-mail/${response.data.id}`);
            window.location.reload();
        }
        loading.value = false;
    } catch (err) {
        loading.value = false;
        error.value = err.response.data.errors;
    }
    getData();
};

const totalDays = computed(() => {
    if (form.value.from_date && form.value.to_date) {
        const startDate = new Date(form.value.from_date);
        const endDate = new Date(form.value.to_date);
        const diffTime = Math.abs(endDate - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays + 1; // Include both start and end dates
    }
    return 0;
});

const openAttachment = (url) => {
    window.open(url, "_blank");
};

onMounted(() => getData());
</script>

<template>
    <div class="container-fluid h-100 d-flex flex-column p-0 m-0">
        <div class="d-flex justify-content-between align-items-center py-2 px-3">
            <h2 class="m-0">Leave List</h2>
            <div>
                <button @click="leaveSetupOpen('Save')" type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm">
                    <i class="fa-solid fa-plus me-2"></i>Leave Application
                </button>
            </div>
        </div>

        <component v-if="newLeaveApplication" :is="leaveSetupComponent" :isOpen="leaveSetupModel" :updateinfo="heading"
            :editStore="selectedStore" :roleId="role_id" @modal-close="newLeaveSetupClose" @refresh-list="getData" />


        <div class="col-lg-12 mt-3">
            <div class="p-3 mb-3">
                <div class="row g-3 mv-3">
                    <div class="col-lg-3 position-relative">
                        <select v-model="selectedType" class="form-control">
                            <option value="">All Type</option>
                            <option value="Casual">Casual</option>
                            <option value="Sick">Sick</option>
                            <option value="Earned">Earned</option>
                            <option value="Other">Other</option>
                        </select>
                        <button v-if="selectedType" type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
                            @click="selectedType = ''">
                            <i class=" fa fa-times"></i>
                        </button>
                    </div>
                    <div class="col-lg-3 position-relative">
                        <select v-model="selectedStatus" class="form-control">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                        <button v-if="selectedStatus" type="button"
                            class="btn btn-sm btn-link position-absolute top-0 end-0 mt-1 me-1"
                            @click="selectedStatus = ''">
                            <i class=" fa fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-if="loading" class="overlay-loading">
        <div class="loader"></div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <!-- Leave List Section -->
            <div class="col-12 mt-4">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>From Date</th>
                                        <th>To Date</th>
                                        <th>Total Days</th>
                                        <th>Leave Type</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Documents</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(l, index) in filteredData" :key="l.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ l.from_date }}</td>
                                        <td>{{ l.to_date }}</td>
                                        <td>{{ l.daysBetween }}</td>
                                        <td>{{ l.leave_type }}</td>
                                        <td>{{ l.purpose }}</td>
                                        <td>{{ l.status }}</td>
                                        <td v-if="l.Attachment_Url">
                                            <button @click="
                                                openAttachment(
                                                    l.Attachment_Url
                                                )
                                                " class="btn btn-info">
                                                <i class="fa fa-download"></i>
                                            </button>
                                        </td>
                                        <td v-else></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.leave-status th,
.leave-status td {
    padding: 10px 3px;
    text-align: center;
    border: 1px solid #bcc7c7;
}

.table_head {
    color: #fff;
    font-weight: 700;
    background: #2bc3b9;
    background: -moz-linear-gradient(-45deg, #2bc3b9 0%, #036931 100%);
    background: -webkit-linear-gradient(-45deg, #2bc3b9 0%, #036931 100%);
    background: linear-gradient(135deg, #2bc3b9 0%, #036931 100%);
    white-space: nowrap;
}

.leave-status tr:nth-child(even) {
    background-color: #f2f2f2;
}

.leave-status {
    width: 100%;
}
</style>
