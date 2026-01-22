<script setup>
import { ref, onMounted, computed, watch, defineAsyncComponent } from "vue";
import { useRoute, useRouter } from "vue-router";
import Swal from "sweetalert2";
import api from "@/api";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;
const router = useRouter();
const route = useRoute();

// Reactive state
const department = ref([]);
const leaveType = ref([]);
const allLeave = ref([]);
const employeesByDept = ref([]);
const employee = ref([]);
const officialLeaveData = ref([]); // Dynamic leave summary table
const earnedLeave = ref("");

const selectedDept = ref("");
const selectedEmp = ref("");
const selectedType = ref("");
const selectedStatus = ref("Pending");

const error = ref(null);

// Modal and edit state
const editModal = ref(false);
const selectedLeave = ref(null);
const EditLeaveModal = defineAsyncComponent(() =>
    import("@/pages/leave/component/statusUpdateForm.vue")
);

// Helper functions
const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, "0");
    const month = date.toLocaleString("en-US", { month: "short" });
    const year = date.getFullYear();
    return `${day} ${month}, ${year}`;
};

const openAttachment = (url) => url && window.open(url, "_blank");

const closeEditModal = () => {
    editModal.value = false;
};

const editLeave = (leave) => {
    selectedLeave.value = leave;
    editModal.value = true;
};

const editLeaveStatus = async (id, status) => {
    try {
        const { data } = await api.put("/leave-approval", { id, status });

        if (data.success) {
            Swal.fire({
                position: "center",
                icon: "success",
                title: data.message || "Update sent to HR",
                showConfirmButton: false,
                timer: 1500,
            });

            const leaveIndex = allLeave.value.findIndex((l) => l.id === id);
            if (leaveIndex !== -1) {
                allLeave.value[leaveIndex].status = status;
                allLeave.value[leaveIndex].notification = 1;
            }

            selectedDept.value = "";
            selectedEmp.value = "";
            selectedType.value = "";
            selectedStatus.value = "Pending";
            employee.value = [];
            employeesByDept.value = [];
            earnedLeave.value = "";

            await api.get(`/hr-approval/${id}`);
            window.location.reload();
        }
    } catch (err) {
        Swal.fire({ icon: "error", title: "Update Failed", text: err.message });
    }
};

// Fetch core data
const getData = async () => {
    try {
        const [deptRes, typeRes, leaveRes] = await Promise.all([
            api.get("/department"),
            api.get("/leave-type"),
            api.get("/all-leave?page=1"),
        ]);
        department.value = deptRes.data;
        leaveType.value = typeRes.data;
        allLeave.value = leaveRes.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

// Fetch employees by department
const getEmployee = async (deptId) => {
    if (!deptId) {
        employeesByDept.value = [];
        return;
    }
    try {
        const res = await api.get(`/emp/${deptId}`);
        employeesByDept.value = res.data;
    } catch (err) {
        console.error(err);
    }
};

// Fetch dynamic leave info for selected employee
const getLeaveInfo = async (id) => {
    try {
        const res = await api.get(`/personal-leave-details/${id}`);
        const data = res.data;

        // Transform API response into array for dynamic table
        const leaveArray = [];
        for (const type in data.entitled_leave) {
            leaveArray.push({
                leave_name: type.charAt(0).toUpperCase() + type.slice(1), // Capitalize
                total_max_days: Number(data.entitled_leave[type]),
                used_leave: Number(data.used_leave[type] || 0),
            });
        }
        officialLeaveData.value = leaveArray;
    } catch (err) {
        console.error(err);
    }
};

// Filter leave table
const filteredData = computed(() =>
    allLeave.value.filter((item) => {
        const deptMatch =
            !selectedDept.value || item.dept_id == selectedDept.value;
        const empMatch = !selectedEmp.value || item.EID == selectedEmp.value;
        const typeMatch =
            !selectedType.value || item.leave_type_id == selectedType.value;
        const statusMatch =
            !selectedStatus.value || item.status == selectedStatus.value;
        return deptMatch && empMatch && typeMatch && statusMatch;
    })
);

// Initialize selected query from URL
const setQuerySelection = async () => {
    if (!department.value.length || !leaveType.value.length) return;

    let needUpdate = false;

    if (route.query.department_name) {
        const dept = department.value.find(
            (d) => d.Name === route.query.department_name
        );
        if (dept) {
            selectedDept.value = dept.id;
            await getEmployee(dept.id);
            needUpdate = true;

            if (route.query.full_name) {
                const emp = employeesByDept.value.find(
                    (e) => e.Full_Name === route.query.full_name
                );
                if (emp) {
                    selectedEmp.value = emp.id;
                    await getLeaveInfo(emp.id); // fetch leave dynamically
                }
            }
        }
    }

    if (needUpdate) {
        router.replace({ path: route.path, query: {} });
    }
};

// Watch route query changes
watch(route, () => setQuerySelection(), { immediate: true });

// On component mount
onMounted(async () => {
    await getData();
    await setQuerySelection();
});
</script>

<template>
    <component
        v-if="editModal"
        :is="EditLeaveModal"
        :isOpen="editModal"
        :editStore="selectedLeave"
        :stId="selectedLeave?.leave_id"
        :leave_type="leaveType"
        @modal-close="closeEditModal"
        @submit="getData"
    />
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
                                            v-for="(
                                                info, index
                                            ) in officialLeaveData"
                                            :key="index"
                                        >
                                            <th>{{ info.leave_name }}</th>
                                            <td>{{ info.total_max_days }}</td>
                                            <td>{{ info.used_leave }}</td>
                                            <td>
                                                {{
                                                    info.total_max_days -
                                                    info.used_leave
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
                            <!-- Department Filter -->
                            <div
                                class="col-lg-3 col-md-4 col-sm-6 mb-3 position-relative"
                            >
                                <label>Department</label>
                                <select
                                    v-model="selectedDept"
                                    @change="
                                        getEmployee(selectedDept);
                                        selectedEmp = '';
                                    "
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
                                <button
                                    v-if="selectedDept"
                                    type="button"
                                    class="btn btn-sm btn-link position-absolute top-50 end-0 me-4 p-0"
                                    @click="
                                        () => {
                                            selectedDept = '';
                                            selectedEmp = '';
                                            employeesByDept = [];
                                        }
                                    "
                                >
                                    <i class="fa fa-times text-danger"></i>
                                </button>
                            </div>

                            <!-- Employee Filter -->
                            <div
                                class="col-lg-3 col-md-4 col-sm-6 mb-3 position-relative"
                            >
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
                                <button
                                    v-if="selectedEmp"
                                    type="button"
                                    class="btn btn-sm btn-link position-absolute top-50 end-0 me-4 p-0"
                                    @click="selectedEmp = ''"
                                >
                                    <i class="fa fa-times text-danger"></i>
                                </button>
                            </div>

                            <!-- Leave Type Filter -->
                            <div
                                class="col-lg-2 col-md-4 col-sm-6 mb-3 position-relative"
                            >
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
                                <button
                                    v-if="selectedType"
                                    type="button"
                                    class="btn btn-sm btn-link position-absolute top-50 end-0 me-4 p-0"
                                    @click="selectedType = ''"
                                >
                                    <i class="fa fa-times text-danger"></i>
                                </button>
                            </div>

                            <!-- Status Filter -->
                            <div
                                class="col-lg-2 col-md-4 col-sm-6 mb-3 position-relative"
                            >
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
                                <button
                                    v-if="selectedStatus"
                                    type="button"
                                    class="btn btn-sm btn-link position-absolute top-50 end-0 me-4 p-0"
                                    @click="selectedStatus = ''"
                                >
                                    <i class="fa fa-times text-danger"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Table -->
                        <div class="table-responsive leave-table">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr class="text-nowrap">
                                        <th>S/N</th>
                                        <th>Employee Name</th>
                                        <th>From Date</th>
                                        <th>To Date</th>
                                        <th>Total Days</th>
                                        <th>Leave Type</th>
                                        <th>Status</th>
                                        <th>Purpose</th>
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
                                        <td>{{ l.purpose }}</td>
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
                                                (l.notification === 0 ||
                                                    l.notification === null ||
                                                    l.notification ===
                                                        undefined) &&
                                                (l.status === 'Pending' ||
                                                    l.status === 'Rejected') &&
                                                l.Supervisor_Id == user.id
                                            "
                                        >
                                            <button
                                                class="custom-btn btn-13 mx-1"
                                                @click.stop="
                                                    editLeaveStatus(
                                                        l.leave_id,
                                                        'Approved (SV)'
                                                    )
                                                "
                                            >
                                                <i
                                                    class="fa-solid fa-check"
                                                ></i>
                                            </button>
                                            <button
                                                class="custom-btn btn-15"
                                                @click="editLeave(l)"
                                            >
                                                <i
                                                    class="fa-regular fa-pen-to-square"
                                                ></i>
                                            </button>
                                            <button
                                                class="custom-btn btn-12 mx-1"
                                                @click="
                                                    editLeaveStatus(
                                                        l.leave_id,
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
    max-height: 500px;
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
