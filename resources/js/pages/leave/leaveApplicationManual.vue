<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import api from "@/api";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;

// ----------------- Reactive State -----------------
const department = ref([]);
const employee = ref([]);
const emp_img = ref([]); // Employee image array

const error = ref([]);
const fileSizeWarning = ref(false);
const leaveType = ref([]);
const officialLeaveData = ref([]); // dynamic leave info table

const leave = ref([]);
const selectedType = ref("");
const selectedStatus = ref("");

// Form data
const form = ref({
    department: "",
    employee_id: "",
    from_date: "",
    to_date: "",
    purpose: "",
    leave_type_id: "",
    status: "",
    file: null,
});

// ----------------- Fetching Data -----------------
const getData = async () => {
    try {
        const [responsedept, responsetype] = await axios.all([
            api.get(`/department-list-leave/${user.id}`),
            api.get("/leave-type"),
        ]);
        console.log("Departments:", responsedept.data);
        department.value = responsedept.data;
        leaveType.value = responsetype.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

// Get employees by department
const getEmployee = async (id) => {
    if (!id) {
        employee.value = [];
        return;
    }
    try {
        const response = await api.get(`/emp/${id}`);
        employee.value = response.data;
    } catch (err) {
        console.error("Error fetching employees:", err);
    }
};

// Get employee image and leave data
const getEmployeeImg = async (id) => {
    if (!id) return;
    try {
        const [responseimg, responseleave] = await axios.all([
            api.get(`/empimg/${id}`),
            api.get(`/leave/${id}`),
        ]);
        emp_img.value = responseimg.data;
        leave.value = responseleave.data;
        await getLeaveInfo(id);
    } catch (err) {
        console.error("Error fetching employee info:", err);
    }
};

const computedTotalDays = computed(() => {
    if (!form.value.from_date || !form.value.to_date) return 0;

    const startDate = new Date(form.value.from_date);
    const endDate = new Date(form.value.to_date);

    // +1 to include both start and end dates
    const diffTime = endDate - startDate;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

    return diffDays > 0 ? diffDays : 0;
});

// Get personal leave info for dynamic table
const getLeaveInfo = async (id) => {
    if (!id) return;
    try {
        const res = await api.get(`/personal-leave-details/${id}`);
        const data = res.data;

        const leaveArray = [];
        for (const type in data.entitled_leave) {
            leaveArray.push({
                leave_name: type.charAt(0).toUpperCase() + type.slice(1),
                total_max_days: Number(data.entitled_leave[type]),
                used_leave: Number(data.used_leave[type] || 0),
            });
        }
        officialLeaveData.value = leaveArray;
    } catch (err) {
        console.error(err);
    }
};

// ----------------- Computed -----------------
const filteredData = computed(() => {
    return leave.value.filter((item) => {
        const typeMatch =
            !selectedType.value || item.leave_type.Name === selectedType.value;
        const statusMatch =
            !selectedStatus.value || item.Status === selectedStatus.value;
        return typeMatch && statusMatch;
    });
});

// ----------------- Helper Functions -----------------
const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        if (typeof form.value[key] === "string") form.value[key] = "";
        else form.value[key] = null;
    });
    fileSizeWarning.value = false;
};

const getImage = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 512000) {
        fileSizeWarning.value = true;
        e.target.value = null;
    } else if (file) {
        fileSizeWarning.value = false;
        form.value.file = file;
    }
};

// Calculate total leave days
const totalDays = (start, end) => {
    const startDate = new Date(start);
    const endDate = new Date(end);
    const diffTime = Math.abs(endDate - startDate);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // include start day
};

// Open attachment in new tab
const openAttachment = (url) => {
    if (url) window.open(url, "_blank");
};

// ----------------- Submit Form -----------------
const submit = async () => {
    form.value.status = "Pending";
    const config = {
        headers: {
            "Content-Type": "multipart/form-data",
        },
    };
    const formdata = new FormData();
    if (form.value.file) formdata.append("file", form.value.file);
    for (const key in form.value) {
        if (form.value.hasOwnProperty(key) && key !== "file") {
            formdata.append(key, form.value[key]);
        }
    }

    try {
        const response = await api.post("/leave", formdata, config);
        if (response.data.success) {
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Application has been saved",
                showConfirmButton: false,
                timer: 1500,
            });
            await api.get(`/leave-req-mail/${response.data.id}`);
            resetForm();
        }
    } catch (err) {
        error.value = err.response?.data?.errors || err.message;
    }
    await getData();
};

// ----------------- Lifecycle -----------------
onMounted(() => getData());
</script>

<template>
    <div class="container-fluid">
        <div class="row">
            <!-- Form Section -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-center mb-4">Leave Application (HR)</h3>
                        <form @submit.prevent="submit">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label>Department</label>
                                    <select
                                        v-model="form.department"
                                        @change="getEmployee(form.department)"
                                        class="form-control"
                                    >
                                        <option value="" disabled selected>
                                            Select
                                        </option>
                                        <option
                                            v-for="dept in department"
                                            :key="dept.department_id"
                                            :value="dept.department_id"
                                        >
                                            {{ dept.department_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label>Employee Name</label>
                                    <select
                                        v-model="form.employee_id"
                                        @change="
                                            getEmployeeImg(form.employee_id)
                                        "
                                        class="form-control"
                                    >
                                        <option value="" disabled selected>
                                            Select
                                        </option>
                                        <option
                                            v-for="e in employee"
                                            :key="e.id"
                                            :value="e.id"
                                        >
                                            {{ e.Full_Name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label>From Date</label>
                                    <input
                                        type="date"
                                        v-model="form.from_date"
                                        class="form-control"
                                    />
                                </div>
                                <div class="col-6">
                                    <label>To Date</label>
                                    <input
                                        type="date"
                                        v-model="form.to_date"
                                        class="form-control"
                                    />
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12 mb-3">
                                <div class="form-group">
                                    <label>Total Days: </label>
                                    <span class="fw-bold ms-1">{{
                                        computedTotalDays
                                    }}</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>Purpose/Reason</label>
                                <textarea
                                    v-model="form.purpose"
                                    class="form-control"
                                    rows="2"
                                ></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label>Leave Type</label>
                                    <select
                                        v-model="form.leave_type_id"
                                        class="form-control"
                                    >
                                        <option value="" disabled selected>
                                            Select
                                        </option>
                                        <option
                                            v-for="lt in leaveType"
                                            :key="lt.id"
                                            :value="lt.id"
                                        >
                                            {{ lt.leave_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label
                                        >Documents (if sick leave >2
                                        days)</label
                                    >
                                    <div
                                        v-if="fileSizeWarning"
                                        class="text-danger mb-1"
                                    >
                                        File size exceeds 500 KB. Please choose
                                        a smaller file.
                                    </div>
                                    <input
                                        type="file"
                                        @change="getImage"
                                        class="form-control"
                                    />
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Submit
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Leave Summary -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="text-center mb-2">Leave Summary</h5>
                <table class="leave-status">
                    <thead class="table_head">
                        <tr>
                            <th>Leave Type</th>
                            <th>Entitled</th>
                            <th>Enjoyed</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(info, index) in officialLeaveData"
                            :key="index"
                        >
                            <th>{{ info.leave_name }}</th>
                            <td>{{ info.total_max_days }}</td>
                            <td>{{ info.used_leave }}</td>
                            <td>{{ info.total_max_days - info.used_leave }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Employee Image -->
            <div
                class="col-lg-2 col-md-6 mb-4 d-flex justify-content-center align-items-center"
            >
                <div v-if="emp_img.length">
                    <img
                        :src="emp_img[0].img_url"
                        class="img-fluid rounded"
                        style="max-width: 220px; max-height: 220px"
                    />
                </div>
            </div>

            <!-- Leave List -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-center mb-4">Leave List</h3>
                        <div class="row mb-3 d-flex justify-content-end">
                            <div class="col-md-4 col-sm-6 mb-2">
                                <label>Leave Type</label>
                                <select
                                    v-model="selectedType"
                                    class="form-control"
                                >
                                    <option value="">All Type</option>
                                    <option
                                        v-for="lt in leaveType"
                                        :key="lt.id"
                                        :value="lt.Name"
                                    >
                                        {{ lt.leave_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6 mb-2">
                                <label>Leave Status</label>
                                <select
                                    v-model="selectedStatus"
                                    class="form-control"
                                >
                                    <option value="">All Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>From Date</th>
                                        <th>To Date</th>
                                        <th>Total Days</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Documents</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(l, index) in filteredData"
                                        :key="l.id || index"
                                    >
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ l.from_date }}</td>
                                        <td>{{ l.to_date }}</td>
                                        <td>
                                            {{
                                                totalDays(
                                                    l.from_date,
                                                    l.to_date,
                                                )
                                            }}
                                        </td>
                                        <td>{{ l.purpose }}</td>
                                        <td>{{ l.Status }}</td>
                                        <td>
                                            <button
                                                v-if="l.attachment_url"
                                                @click="
                                                    openAttachment(
                                                        l.attachment_url,
                                                    )
                                                "
                                                class="btn btn-info btn-sm"
                                            >
                                                <i class="fa fa-download"></i>
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
