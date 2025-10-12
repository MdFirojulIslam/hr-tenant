<script setup>
import { ref, onMounted, computed } from "vue";
import Swal from "sweetalert2";
import api from "@/api";
import axios from "axios";

// ====== Reactive State ======
const employeesByDept = ref([]); // employees filtered by department
const employee = ref([]); // selected employee detail
const department = ref([]);
const leaveType = ref([]);
const allLeave = ref([]);
const error = ref(null);

const earnedLeave = ref("");

// ====== Filter Controls ======
const selectedDept = ref("");
const selectedEmp = ref("");
const selectedType = ref("");
const selectedStatus = ref("");

// ====== Date Formatting ======
const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, "0");
    const month = date.toLocaleString("en-US", { month: "short" });
    const year = date.getFullYear();
    return `${day} ${month}, ${year}`;
};

// ====== Fetch Leave Approval Update ======
const editLeaveStatus = async (id, status) => {
    try {
        await api.get(`/leave-approval`, { params: { id, status } });
        Swal.fire({
            position: "center",
            icon: "success",
            title: "Update sent to HR",
            showConfirmButton: false,
            timer: 1500,
        });

        // Update the leave locally
        const leaveIndex = allLeave.value.findIndex((l) => l.id === id);
        if (leaveIndex !== -1) {
            allLeave.value[leaveIndex].status = status;
            allLeave.value[leaveIndex].notification = 1;
        }

        // Trigger HR approval (optional)
        await api.get(`/hr-approval/${id}`);
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Update Failed",
            text: err.message,
        });
    }
};

const getLeaveInfo = async (id) => {
    try {
        const response = await api.get(`/leave/${id}`);
        employee.value = response.data;

        const responseEarned = await api.get(`/get-earned-leave/${id}`);
        earnedLeave.value = responseEarned.data;
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

// ====== Fetch Department, Leave Types, and All Leaves ======
const getData = async (page = 1) => {
    try {
        const [deptRes, typeRes, leaveRes] = await axios.all([
            api.get("/department"),
            api.get("/leave-type"),
            api.get(`/all-leave?page=${page}`),
        ]);

        department.value = deptRes.data;
        leaveType.value = typeRes.data;
        allLeave.value = leaveRes.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

// ====== Fetch Employees by Department ======
const getEmployee = async (deptId) => {
    if (!deptId) {
        employeesByDept.value = [];
        return;
    }

    try {
        const response = await api.get(`/emp/${deptId}`);
        employeesByDept.value = response.data;
    } catch (err) {
        console.error("Error fetching employees:", err);
    }
};

// ====== Filtered Leave Records ======
const filteredData = computed(() => {
    return allLeave.value.filter((item) => {
        console.log(item);
        const deptMatch =
            !selectedDept.value || item.dept_id == selectedDept.value;
        const empMatch = !selectedEmp.value || item.EID == selectedEmp.value;
        const typeMatch =
            !selectedType.value || item.leave_id == selectedType.value;
        const statusMatch =
            !selectedStatus.value || item.status == selectedStatus.value;
        return deptMatch && empMatch && typeMatch && statusMatch;
    });
});

// ====== Open Attachment in New Tab ======
const openAttachment = (url) => {
    if (!url) return;
    window.open(url, "_blank");
};

// ====== Lifecycle ======
onMounted(async () => {
    await getData();
    console.log("All leaves:", allLeave.value);
});
</script>

<template>
    <div class="container-fluid px-3">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="container">
                <!-- EMPLOYEE DETAILS -->
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <h1 class="mb-4">Supervisor Approval</h1>
                        </div>

                        <div class="row">
                            <!-- LEFT: Employee Info -->
                            <div class="col-lg-8 col-md-12 px-2 mb-4">
                                <section class="section about-section gray-bg">
                                    <div class="container">
                                        <div
                                            class="row align-items-center flex-row-reverse"
                                        >
                                            <div
                                                class="col-lg-7 col-md-6 mb-4 mb-md-0"
                                            >
                                                <div
                                                    v-for="(
                                                        item, index
                                                    ) in employee"
                                                    :key="item.id"
                                                >
                                                    <h3 v-if="index === 0">
                                                        {{ item.Full_Name }}
                                                    </h3>
                                                    <h6 v-if="index === 0">
                                                        {{
                                                            item.designation_name
                                                        }}
                                                    </h6>
                                                    <h6 v-if="index === 0">
                                                        {{
                                                            item.department_name
                                                        }}
                                                    </h6>
                                                </div>
                                            </div>
                                            <div
                                                class="col-lg-5 col-md-6"
                                                v-for="(
                                                    item, index
                                                ) in employee"
                                                :key="item.id"
                                            >
                                                <div
                                                    v-if="index === 0"
                                                    class="about-avatar"
                                                >
                                                    <img
                                                        :src="item.img_url"
                                                        class="img-fluid"
                                                        style="
                                                            max-height: 150px;
                                                            max-width: 130px;
                                                        "
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <!-- RIGHT: Leave Summary -->
                            <div class="col-lg-4 col-md-12 px-2 mb-4">
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
                                            v-for="(info, typeName) in employee"
                                            :key="typeName"
                                        >
                                            <th>{{ info.leave_name }}</th>
                                            <td>
                                                {{
                                                    info.max_leave_days === 0
                                                        ? info.earned_leave
                                                        : info.max_leave_days
                                                }}
                                            </td>
                                            <td>{{ info.leave_count }}</td>
                                            <td>
                                                {{
                                                    info.max_leave_days === 0
                                                        ? info.earned_leave -
                                                          info.leave_count
                                                        : info.max_leave_days -
                                                          info.leave_count
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LEAVE TABLE -->
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="text-center">
                            <h1 class="mb-5">Leave Summary</h1>
                        </div>

                        <!-- Filters -->
                        <div class="row justify-content-end mb-3">
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label>Department</label>
                                <select
                                    v-model="selectedDept"
                                    @change="getEmployee(selectedDept)"
                                    class="form-control"
                                >
                                    <option value="">All Department</option>
                                    <option
                                        v-for="item in department"
                                        :key="item.id"
                                        :value="item.id"
                                    >
                                        {{ item.Name }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label>Employee Name</label>
                                <select
                                    v-model="selectedEmp"
                                    class="form-control"
                                >
                                    <option value="">All Employee</option>
                                    <option
                                        v-for="emp in employeesByDept"
                                        :key="emp.id"
                                        :value="emp.id"
                                    >
                                        {{ emp.Full_Name }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label>Leave Type</label>
                                <select
                                    v-model="selectedType"
                                    class="form-control"
                                >
                                    <option value="">All Type</option>
                                    <option
                                        v-for="type in leaveType"
                                        :key="type.id"
                                        :value="type.id"
                                    >
                                        {{ type.leave_name }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label>Status</label>
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

                        <!-- Table -->
                        <div class="table-responsive leave-table">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Employee Name</th>
                                        <th>From Date</th>
                                        <th>To Date</th>
                                        <th>Total Days</th>
                                        <th>Leave Type</th>
                                        <th>Status</th>
                                        <th>Documents</th>
                                        <th>Operation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(l, index) in filteredData"
                                        :key="l.id"
                                        @click="getLeaveInfo(l.EID)"
                                    >
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ l.Full_Name }}</td>
                                        <td>{{ formatDate(l.from_date) }}</td>
                                        <td>{{ formatDate(l.to_date) }}</td>
                                        <td>{{ l.daysBetween }}</td>
                                        <td>{{ l.leave_name }}</td>
                                        <td>{{ l.status }}</td>
                                        <td>
                                            <button
                                                v-if="l.attachment_url"
                                                @click.stop="
                                                    openAttachment(
                                                        l.attachment_url
                                                    )
                                                "
                                                class="btn btn-info btn-sm mx-1"
                                            >
                                                <i
                                                    class="fa-solid fa-file-arrow-down"
                                                ></i>
                                            </button>
                                            <span v-else>-</span>
                                        </td>
                                        <td
                                            v-if="
                                                l.notification == 0 &&
                                                l.status == 'Pending'
                                            "
                                        >
                                            <button
                                                class="custom-btn btn-13 mx-1"
                                                @click.stop="
                                                    editLeaveStatus(
                                                        l.id,
                                                        'Approved'
                                                    )
                                                "
                                            >
                                                <i
                                                    class="fa-solid fa-check"
                                                ></i>
                                            </button>
                                            <button
                                                class="custom-btn btn-12 mx-1"
                                                @click.stop="
                                                    editLeaveStatus(
                                                        l.id,
                                                        'Rejected'
                                                    )
                                                "
                                            >
                                                <i
                                                    class="fa-solid fa-xmark"
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
</template>

<style>
.leave-table {
    max-height: 400px;
}

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

.input-group-text {
    padding: 8px;
}

tr:hover {
    cursor: pointer;
}

tr:hover td {
    background-color: #d3d9d9 !important;
}

.img-fluid {
    border-radius: 8px;
    object-fit: cover;
}

@media (max-width: 768px) {
    .container-fluid {
        padding: 10px;
    }

    .card {
        margin-bottom: 20px;
    }
}
</style>
