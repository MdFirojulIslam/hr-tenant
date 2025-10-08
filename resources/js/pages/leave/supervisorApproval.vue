<script setup>
import { ref, onMounted, computed } from "vue";
// import { Bootstrap4Pagination } from "laravel-vue-pagination";
import Swal from "sweetalert2";
import api from "@/api";
import { useStore } from "vuex";

const store = useStore();

const user = store.getters.getUser;

const employee = ref([]);
const deptEmp = ref([]);
const earnedLeave = ref("");
const department = ref([]);
const error = ref([]);
const leaveType = ref();
// const leaveListPagination = ref({});
const selectedStatus = ref("Pending");
const selectedDept = ref("");
const selectedEmp = ref("");
const allLeave = ref([]);
const loading = ref(false);

const editLeaveStatus = async (id, status) => {
    loading.value = true;
    try {
        const response = await api.get(`/supervisor-approval`, {
            params: { id, status },
        });
        if (response.data.success) {
            loading.value = false;
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Update is send to HR",
                showConfirmButton: false,
                timer: 1500,
            });
            await api.get(`/hr-approval/${id}`);
            window.location.reload();
        }
    } catch (err) { }
};

const getDeptEmp = async (id) => {
    try {
        const response = await api.get(`/emp/${id}`);
        deptEmp.value = response.data;
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

const getData = async (page = 1) => {
    try {
        const [responsedept, responsetype, response] = await axios.all([
            api.get("/department"),
            api.get("/leave-type"),
            api.get(`/all-leave?page=${page}`),
        ]);
        department.value = responsedept.data;
        leaveType.value = responsetype.data;
        allLeave.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
    }
};


const filteredData = computed(() => {
    return allLeave.value.filter(item => {
        const matchDept = selectedDept.value ? item.dept_id == selectedDept.value : true;
        const matchEmp = selectedEmp.value ? item.EID == selectedEmp.value : true;
        const matchStatus = selectedStatus.value ? item.status == selectedStatus.value : true;

        return matchDept && matchEmp && matchStatus;
    });
});



const getEmployee = async (id) => {
    try {
        const response = await api.get(`/leave/${id}`);
        employee.value = response.data;

        const responseEarned = await api.get(`/get-earned-leave/${id}`);
        earnedLeave.value = responseEarned.data; // value is 7 
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

// const totalLeaveDays = computed(() => {
//     const totals = {};

//     if (Array.isArray(leaveType.value) && leaveType.value.length > 0) {
//         leaveType.value.forEach(type => {
//             totals[type.leave_name] = {
//                 totalDays: 0,
//                 maxDays:
//                     type.leave_name === "Earned"
//                         ? earnedLeave.value
//                         : type.max_leave_days || 0,
//             };
//         });

//         employee.value.forEach((item) => {
//             const typeName = item.leave_type;
//             if (!totals[typeName]) {
//                 totals[typeName] = {
//                     totalDays: 0,
//                     maxDays: item.max_leave_days,
//                 };
//             }

//             if (item.status === "Approved") {
//                 totals[typeName].totalDays += item.daysBetween;
//             }
//         });
//     }

//     return totals;
// });

const openAttachment = (url) => {
    window.open(url, "_blank");
};

onMounted(() => getData());
</script>

<template>
    <div v-if="loading" class="overlay-loading">
        <div class="loader"></div>
    </div>
    <div class="container-fluid px-3">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="container">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <h1 class="mb-4">Supervisor Approval</h1>
                        </div>
                        <div class="row">
                            <div class="col-lg-8 col-md-12 px-2 mb-4">
                                <section class="section about-section gray-bg" id="about">
                                    <div class="container">
                                        <div class="row align-items-center flex-row-reverse">
                                            <div class="col-lg-7 col-md-6 mb-4 mb-md-0">
                                                <div class="about-text go-to" v-for="(
item, index
                                                    ) in employee" :key="item.id">
                                                    <h3 class="dark-color" v-if="index === 0">
                                                        {{
                                                            item.Full_Name || ""
                                                        }}
                                                    </h3>
                                                    <h6 class="theme-color lead" v-if="index === 0">
                                                        {{
                                                            item.designation ||
                                                            ""
                                                        }}
                                                    </h6>
                                                    <h6 class="theme-color lead" v-if="index === 0">
                                                        {{
                                                            item.department ||
                                                            ""
                                                        }}
                                                    </h6>
                                                </div>
                                            </div>
                                            <div class="col-lg-5 col-md-6" v-for="(
item, index
                                                ) in employee" :key="item.id">
                                                <div class="about-avatar" v-if="index === 0">
                                                    <img :src="item.img_url
                                                        ? item.img_url
                                                        : ''
                                                        " class="img-fluid" style="
                                                            max-height: 150px;
                                                            max-width: 130px;
                                                        " />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>


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
                                        <tr v-for="(
info, typeName
                                            ) in employee" :key="typeName">
                                            <th>{{ info.leave_name }}</th>
                                            <td>
                                                {{ info.max_leave_days === 0 ? info.earned_leave : info.max_leave_days
                                                }}
                                            </td>
                                            <td>{{ info.leave_count }}</td>
                                            <td>
                                                {{ info.max_leave_days === 0
                                                    ? info.earned_leave - info.leave_count
                                                    : info.max_leave_days - info.leave_count }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <div class="text-center">
                            <h1 class="mb-5">Leave Summary</h1>
                        </div>

                        <div class="row d-flex justify-content-center justify-content-md-end mb-3">
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label>Department</label>
                                <div class="position-relative">
                                    <select v-model="selectedDept" class="form-control"
                                        @change="getDeptEmp(selectedDept)">
                                        <option value="">All Department</option>
                                        <option v-for="item in department" :key="item.id" :value="item.id">{{ item.Name
                                        }}</option>
                                    </select>

                                    <!-- Clear button (X) -->
                                    <button v-if="selectedDept" type="button"
                                        class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y me-2"
                                        @click="selectedDept = ''">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <div class="position-relative">
                                    <label>Employee</label>
                                    <select v-model="selectedEmp" class="form-control">
                                        <option value="">All Employee</option>
                                        <option v-for="item in deptEmp" :key="item.id" :value="item.id">{{
                                            item.Full_Name }}
                                        </option>
                                    </select>
                                    <button v-if="selectedEmp" type="button"
                                        class="btn btn-sm btn-link position-absolute top-50 end-0   me-1"
                                        @click="selectedEmp = ''">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <div class="position-relative">
                                    <label>Leave Status</label>
                                    <select v-model="selectedStatus" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Approved (SV)">
                                            Approved (SV)
                                        </option>
                                        <option value="Rejected">Rejected</option>
                                    </select>
                                    <button v-if="selectedStatus" type="button"
                                        class="btn btn-sm btn-link position-absolute top-50 end-0 me-2"
                                        @click="selectedStatus = ''">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>


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
                                    <tr class="ver-align" v-for="(l, index) in filteredData" :key="l.id"
                                        @click="getEmployee(l.EID)">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ l.Full_Name }}</td>
                                        <td>{{ l.from_date }}</td>
                                        <td>{{ l.to_date }}</td>
                                        <td>{{ l.daysBetween }}</td>
                                        <td>{{ l.Leave_Type }}</td>
                                        <td>{{ l.status }}</td>
                                        <td v-if="l.Attachment_Url">
                                            <button @click="
                                                openAttachment(
                                                    l.Attachment_Url
                                                )
                                                " class="btn btn-info btn-sm mx-1">
                                                <i class="fa-solid fa-file-arrow-down"></i>
                                            </button>
                                        </td>
                                        <td v-else>-</td>
                                        <td v-if="
                                            l.Status == 'Pending' &&
                                            l.Supervisor_Id == user.id
                                        ">
                                            <button class="custom-btn btn-13 mx-1" @click="
                                                editLeaveStatus(
                                                    l.id,
                                                    'Approved (SV)'
                                                )
                                                ">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button class="custom-btn btn-12 mx-1" @click="
                                                editLeaveStatus(
                                                    l.id,
                                                    'Rejected'
                                                )
                                                ">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- <div class="d-flex justify-content-end mt-0">
                        <Bootstrap4Pagination :data="leaveListPagination" :limit="2"
                            @pagination-change-page="getData" />
                    </div> -->
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* .leave-table {
    max-height: 200px;
} */

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
