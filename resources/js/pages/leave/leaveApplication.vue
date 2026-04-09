<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import api from "@/api";

const leave_information = ref([]);

const error = ref([]);
const fileSizeWarning = ref();
const leaveType = ref();
const selectedType = ref("");
const selectedStatus = ref("");

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
        error.value = err.message || "Error fetching data";
    } finally {
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);

    const day = String(date.getDate()).padStart(2, "0"); // 01–09
    const month = date.toLocaleString("en-US", { month: "short" }); // Jan, Feb, Mar...
    const year = date.getFullYear();

    return `${day} ${month}, ${year}`;
};

const filteredData = computed(() => {
    // Make sure leave_information has data
    if (!leave_information.value || !leave_information.value.leaves) {
        return [];
    }

    // Convert leaves object into an array
    const leaveArray = Object.values(leave_information.value.leaves);

    // Filter based on selected type & status
    return leaveArray.filter((item) => {
        const matchLeaveType = selectedType.value
            ? item.leave_type === selectedType.value
            : true;
        const matchLeaveStatus = selectedStatus.value
            ? item.status === selectedStatus.value
            : true;

        return matchLeaveType && matchLeaveStatus;
    });
});

const computedTotalDays = computed(() => {
    if (!form.value.from_date || !form.value.to_date) return 0;

    const startDate = new Date(form.value.from_date);
    const endDate = new Date(form.value.to_date);

    // +1 to include both start and end dates
    const diffTime = endDate - startDate;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

    return diffDays > 0 ? diffDays : 0;
});

const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        if (typeof form.value[key] === "string") {
            form.value[key] = "";
        } else {
            form.value[key] = null;
        }
    });
};

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
    form.value.status = "Pending";
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
        error.value = err.response.data.errors;
    }
    getData();
};

const totalDays = (start, end) => {
    const startDate = new Date(start);
    const endDate = new Date(end);
    const diffTime = Math.abs(endDate - startDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};

const openAttachment = (url) => {
    window.open(url, "_blank");
};

onMounted(() => getData());
</script>

<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <h1 class="mb-5">Leave Application Form</h1>
                        </div>
                        <div class="row">
                            <!-- Form Section -->
                            <div class="col-lg-6 col-md-12 col-sm-12 mb-4">
                                <form
                                    class="forms-sample"
                                    @submit.prevent="submit"
                                >
                                    <div class="row">
                                        <div
                                            class="col-lg-6 col-md-6 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >Employee ID</label
                                                >
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    :value="
                                                        leave_information.employee
                                                            ? 'ABNB' +
                                                              leave_information
                                                                  .employee
                                                                  .Employee_Id
                                                            : ''
                                                    "
                                                    readonly
                                                />
                                            </div>
                                        </div>
                                        <div
                                            class="col-lg-6 col-md-6 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >Employee Name</label
                                                >
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    :value="
                                                        leave_information.employee
                                                            ? leave_information
                                                                  .employee
                                                                  .Full_Name
                                                            : ''
                                                    "
                                                    readonly
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div
                                            class="col-lg-6 col-md-6 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="fromDate"
                                                    >From Date</label
                                                >
                                                <input
                                                    type="date"
                                                    class="form-control"
                                                    id="fromDate"
                                                    v-model="form.from_date"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            class="col-lg-6 col-md-6 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="toDate"
                                                    >To Date</label
                                                >
                                                <input
                                                    type="date"
                                                    class="form-control"
                                                    id="toDate"
                                                    v-model="form.to_date"
                                                />
                                            </div>
                                        </div>

                                        <div
                                            class="col-lg-6 col-md-6 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label>Total Days: </label>
                                                <span class="fw-bold">{{
                                                    computedTotalDays
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <div class="form-group">
                                                <label for="purpose"
                                                    >Purpose/Reason</label
                                                >
                                                <textarea
                                                    class="form-control"
                                                    id="purpose"
                                                    rows="2"
                                                    v-model="form.purpose"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div
                                            class="col-lg-6 col-md-6 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="leaveType"
                                                    >Leave Type</label
                                                >
                                                <select
                                                    class="form-control"
                                                    v-model="form.leave_type_id"
                                                >
                                                    <option selected disabled>
                                                        select
                                                    </option>
                                                    <option
                                                        v-for="leave in leaveType"
                                                        :key="leave.id"
                                                        :value="leave.id"
                                                    >
                                                        {{ leave.leave_name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div
                                            class="col-lg-6 col-md-6 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="documentUpload">
                                                    Documents (If sick leave is
                                                    more than 2 days)
                                                    <div
                                                        v-if="fileSizeWarning"
                                                        class="text-danger"
                                                    >
                                                        File size exceeds 500
                                                        KB. Please choose a
                                                        smaller file.
                                                    </div>
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    id="documentUpload"
                                                    @change="getImage"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <button
                                                type="submit"
                                                class="btn btn-primary btn-block"
                                            >
                                                Submit
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Employee Image Section -->
                            <div
                                class="col-lg-4 col-md-12 col-sm-12 d-flex justify-content-center align-items-start mb-4"
                            >
                                <div class="img-container">
                                    <img
                                        :src="
                                            leave_information.employee
                                                ? leave_information.employee
                                                      .img_url
                                                : ''
                                        "
                                        class="img-fluid rounded"
                                        style="
                                            max-height: 220px;
                                            max-width: 220px;
                                        "
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leave List Section -->
            <div class="col-12 mt-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <h1 class="mb-5">Leave List</h1>
                        </div>
                        <div class="row mb-3 d-flex justify-content-end">
                            <div class="col-md-4 col-sm-6 mb-2">
                                <label for="leaveTypeFilter">Leave Type</label>
                                <select
                                    v-model="selectedType"
                                    class="form-control"
                                >
                                    <option value="">All Type</option>
                                    <option value="Casual">Casual</option>
                                    <option value="Sick">Sick</option>
                                    <option value="Earned">Earned</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6 mb-2">
                                <label for="leaveStatusFilter"
                                    >Leave Status</label
                                >
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
                                    <tr class="text-nowrap">
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
                                    <tr
                                        class="text-nowrap"
                                        v-for="(l, index) in filteredData"
                                        :key="l.id"
                                    >
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ formatDate(l.from_date) }}</td>
                                        <td>{{ formatDate(l.to_date) }}</td>
                                        <td>{{ l.daysBetween }}</td>
                                        <td>{{ l.leave_type }}</td>
                                        <td>{{ l.purpose }}</td>
                                        <td>{{ l.status }}</td>
                                        <td v-if="l.attachment_url">
                                            <button
                                                @click="
                                                    openAttachment(
                                                        l.attachment_url,
                                                    )
                                                "
                                                class="btn btn-info"
                                            >
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
