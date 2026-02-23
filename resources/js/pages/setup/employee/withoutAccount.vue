<script setup>
import { ref, onMounted, computed } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import router from "../../../router";
import api from "@/api";

const employees = ref([]);
const isLoading = ref(true);
const error = ref(null);
const empEdit = ref(null);
const heading = ref(null);
const filter = ref("");

const getData = async (page = 1) => {
    try {
        const response = await api.get(
            `/employee-without-account?page=${page}`,
        );
        employees.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

const allEmployee = () => router.push(`/setup/employee/employeeList`);
const haveAccount = () => router.push(`/setup/employee/active-employee`);
const inactive = () => router.push(`/setup/employee/inactive`);

// const haveAccount = async (page = 1) => {
//     try {
//         const accountHaveRes = await api.get(`/employee-have-account?page=${page}`);
//         employees.value = accountHaveRes.data;

//         console.log("Have Account Response:", accountHaveRes.data);
//     } catch (err) {
//         console.error("Error navigating to Have Account page:", err);
//     }
// };

// const withoutAccount = async () => {
//     try {
//         const accountWithoutRes = await api.get(`/employee-without-account?page=${page}`);
//         employees.value = accountWithoutRes.data;
//     } catch (err) {
//         console.error("Error navigating to Without Account page:", err);
//     }
// }

// const inactive = async () => {
//     try {
//         const inactiveRes = await api.get(`/employee-inactive?page=${page}`);
//         employees.value = inactiveRes.data;
//     } catch (err) {
//         console.error("Error navigating to Inactive page:", err);
//     }
// };

const cvPdf = async (id) => {
    window.open(`/cv-pdf/${id}`, "_blank");
};

const searchemp = async () => {
    if (filter.value.length > 0) {
        try {
            const response = await api.get("/without-account-search", {
                params: {
                    query: filter.value,
                },
            });
            employees.value = response.data;
        } catch (error) {
            console.error(error);
        }
    } else {
        getData();
    }
};

const editHandler = async (id) => {
    try {
        const response = await api.get(`/employee/${id}/edit`);
        empEdit.value = response.data;
        heading.value = "Update";
        openModal();
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const mapMaritalStatus = (status) => {
    const statusMap = {
        M: "Married",
        U: "Unmarried",
    };

    return statusMap[status] || "N/A";
};

const mapGender = (status) => {
    const statusMap = {
        M: "Male",
        F: "Female",
        O: "Other",
    };

    return statusMap[status] || "N/A";
};

const submitHandler = async () => {
    await getData();
};

onMounted(() => {
    getData();
});
</script>

<template>
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex">
                    <div class="mr-auto p-1">
                        <h4 class="card-title m-2">
                            Unregistered Active Employee List
                        </h4>
                    </div>
                    <div class="p-2">
                        <Router-Link :to="{ name: 'Employee' }">
                            <button
                                style="float: left"
                                type="button"
                                class="btn-plus"
                            >
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </Router-Link>
                    </div>

                    <div class="p-2">
                        <input
                            type="text"
                            class="form-control"
                            id="exampleInputUsername1"
                            placeholder="Search..."
                            v-model.trim="filter"
                            @input="searchemp"
                        />
                    </div>
                    <div class="ms-auto all-employee button-hover">
                        <button @click="allEmployee()">All EMPLOYEE</button>
                    </div>
                    <div class="ms-4 active-account button-hover">
                        <button @click="haveAccount()">HAVE ACCOUNT</button>
                    </div>
                    <div class="ms-4 without-account">
                        <button @click="haveAccount()" disabled>
                            WITHOUT ACCOUNT
                        </button>
                    </div>
                    <div class="ms-4 inactive-account button-hover">
                        <button @click="inactive()">INACTIVE</button>
                    </div>
                </div>

                <div class="d-flex align-items-center px-2 mt-5 fw-semibold">
                    <span class="me-2">Total Employees:</span>
                    <span class="bg-white border rounded px-3 fw-medium">{{
                        employees.total
                    }}</span>
                </div>
                <div class="table-responsive shadow-sm bg-light mt-3">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="table-head">
                            <tr class="thead-text">
                                <th>S/N</th>
                                <th class="text-center">IMAGE</th>
                                <th>ID</th>
                                <th>NAME</th>
                                <th>DEPARTMENT</th>
                                <th class="text-center">DOJ</th>
                                <th class="text-center">CONTACT</th>
                                <th>OFFICIAL MAIL</th>
                                <th class="text-center">MACHINE ID</th>
                                <th class="text-center">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                class="ver-align"
                                v-for="(employee, index) in employees.data"
                                :key="employee.id"
                            >
                                <td class="text-center">
                                    {{ employees.from + index }}
                                </td>
                                <template v-if="employee.img_url">
                                    <td class="text-center">
                                        <img
                                            :src="employee.img_url"
                                            height="40"
                                            width="40"
                                            style="border-radius: 50%"
                                        />
                                    </td>
                                </template>
                                <template v-else>
                                    <td class="text-center">No image</td>
                                </template>
                                <td>ABNB-{{ employee.Employee_Id }}</td>
                                <td>{{ employee.Full_Name }}</td>
                                <td>{{ employee.department_name }}</td>
                                <td class="text-center">{{ employee.DOJ }}</td>
                                <td class="text-center">
                                    {{ employee.Contact_No }}
                                </td>
                                <td>{{ employee.Official_Email }}</td>
                                <td class="text-center">{{ employee.id }}</td>
                                <td class="text-center">
                                    <button
                                        class="custom-btn btn-13 mx-1"
                                        @click="
                                            router.push(
                                                `/setup/employee/empdetails/${employee.id}`,
                                            )
                                        "
                                    >
                                        <i class="fa-regular fa-user"></i>
                                    </button>
                                    <button
                                        class="custom-btn btn-15 mx-1"
                                        @click="
                                            router.push({
                                                name: 'Employee',
                                                params: { id: employee.id },
                                            })
                                        "
                                    >
                                        <i
                                            class="fa-regular fa-pen-to-square"
                                        ></i>
                                    </button>
                                    <button
                                        class="custom-btn btn-13 mx-1"
                                        @click="cvPdf(employee.id)"
                                    >
                                        <i class="fa-regular fa-file"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Bootstrap4Pagination
                        :data="employees"
                        :limit="2"
                        @pagination-change-page="getData"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.thead-text {
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.table thead th {
    background-color: #4a784688;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.5px;
    height: 30px;
    padding: 6px 10px;
    vertical-align: middle;
}

.table-hover tbody tr:hover td {
    background-color: rgba(9, 71, 42, 0.5);
    color: #ffffff;
}

.all-employee button {
    background-color: rgba(0, 128, 0, 0.5);
    border: 1.5px solid #00000088;
    border-radius: 5px;
    height: 30px;
    /* color: #ffffff; */
    letter-spacing: 1.5px;
    font-weight: 800;
}

.active-account button {
    background-color: #4a784688;
    border: 1.5px solid #00000088;
    border-radius: 5px;
    height: 30px;
    /* color: #ffffff; */
    letter-spacing: 1.5px;
    font-weight: 800;
}

.without-account button {
    background-color: #f1f110;
    border: 1.5px solid #00000088;
    border-radius: 5px;
    height: 30px;
    /* color: #ffffff; */
    cursor: not-allowed;
    letter-spacing: 1.5px;
    font-weight: 800;
}

.inactive-account button {
    background-color: #ee0101e6;
    border: 1.5px solid #00000088;
    border-radius: 5px;
    height: 30px;
    /* color: #ffffff; */
    letter-spacing: 1.5px;
    font-weight: 800;
}

.button-hover:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}

.table tr {
    height: 55px;
}
</style>
