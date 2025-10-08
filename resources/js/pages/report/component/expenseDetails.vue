<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import { useRoute } from "vue-router";
import Swal from "sweetalert2";


const route = useRoute();
const empId = parseInt(route.params.id);
const month = parseInt(route.params.month);
const year = parseInt(route.params.year);

// const expenses = ref("");
const expenses = ref({ travel: [], allowance: [] });
const empData = ref("");
const error = ref("");

const canEdit = route.query.canEdit === 'true';

const getData = async () => {
    try {
        const response = await api.get("/tada-expense", {
            params: {
                month: month,
                year: year,
                id: empId,
            },
        });
        expenses.value = response.data;

        const responseEmp = await api.get(`/get-employee/${empId}`);
        empData.value = responseEmp.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};



const mapExpense = (status) => {
    const statusMap = {
        da: "Daily Allowance",
        oe: "Other Expense",
    };
    return statusMap[status];
};

const openAttachment = (url) => {
    window.open(url, "_blank");
};

const detailsReport = () => {
    window.open(
        `/expense-report?month=${month}&year=${year}&eid=${empId}&detail=detail`,
        "_blank"
    );
};

const summaryReport = () => {
    window.open(
        `/expense-report?month=${month}&year=${year}&eid=${empId}`,
        "_blank"
    );
};


// Approve function to update statuses to 1
const approveExpenses = async () => {
    try {
        const response = await api.get("/approve-expense", {
            params: {
                month: month,
                year: year,
                id: empId,
            },
        });

        if (response.data.success) {
            Swal.fire({
                icon: "success",
                title: "Employee Expense Approved",
                showConfirmButton: false,
                timer: 1500,
            });
            await getData();
        }
    } catch (err) {
        error.value = err.message || "Error approving expenses";
    }
};

const deleteExpense = async (id) => {
    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to Delete this expense?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            const response = await api.delete(`/delete-expense/${id}`);
            if (response.data.success) {
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Information has been Deleted",
                    showConfirmButton: false,
                    timer: 1500,
                });
                await getData();
            }
        }
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

onMounted(() => getData());
</script>

<template>
    <div class="card mt-4">
        <div class="card-body">
            <div class="text-center">
                <h1 class="mb-1">Monthly Expense</h1>
            </div>
            <div class="row d-flex justify-content-start">
                <div class="col-lg-2 mb-3">
                    <img
                        :src="empData.img_url ? empData.img_url : ''"
                        class="img-fluid"
                        style="max-height: 100px; max-width: 90px"
                    />
                </div>
                <div class="col-lg-5 mb-3">
                    <h3>{{ empData.Full_Name }}</h3>
                    <h5>{{ empData.dept_name }}</h5>
                    <h5>{{ empData.des_name }}</h5>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="text-center mb-4">Other Allowance</h3>
                            <div class="table-responsive">
                                <table class="table table-bordered text-center">
                                    <thead>
                                        <tr>
                                            <th>S/N</th>
                                            <th>Date</th>
                                            <th>Weekday</th>
                                            <th>Particular</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            class="ver-align"
                                            v-for="(
                                                item, index
                                            ) in expenses.allowance"
                                            :key="item.id"
                                        >
                                            <td>{{ index + 1 }}</td>
                                            <td>{{ item.Date }}</td>
                                            <td>{{ item.weekday }}</td>
                                            <td>
                                                {{ mapExpense(item.Tr_Type) }}
                                            </td>
                                            <td>{{ item.Description }}</td>
                                            <td>{{ item.Amount }}</td>
                                            <td
                                                class="d-flex justify-content-center"
                                            >
                                                <div v-if="item.File_Path">
                                                    <button
                                                        @click="
                                                            openAttachment(
                                                                item.File_Path
                                                            )
                                                        "
                                                        class="btn btn-info btn-sm mx-1"
                                                    >
                                                        <i
                                                            class="fa-solid fa-file-arrow-down"
                                                        ></i>
                                                    </button>
                                                </div>
                                                <div v-if="canEdit">
                                                    <button
                                                        class="custom-btn btn-12 mx-2"
                                                        @click="
                                                            deleteExpense(
                                                                item.id
                                                            )
                                                        "
                                                    >
                                                        <i
                                                            class="fa-solid fa-trash"
                                                        ></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 col-md-12 col-sm-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="text-center mb-4">Travel Expense</h3>
                            <div class="table-responsive">
                                <table class="table table-bordered text-center">
                                    <thead>
                                        <tr>
                                            <th>S/N</th>
                                            <th>Date</th>
                                            <th>Weekday</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Vehicle</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            class="ver-align"
                                            v-for="(
                                                item, index
                                            ) in expenses.travel"
                                            :key="item.id"
                                        >
                                            <td>{{ index + 1 }}</td>
                                            <td>{{ item.Date }}</td>
                                            <td>{{ item.weekday }}</td>
                                            <td>{{ item.From }}</td>
                                            <td>{{ item.To }}</td>
                                            <td>{{ item.Vehicle }}</td>
                                            <td>{{ item.Amount }}</td>
                                            <td
                                                class="d-flex justify-content-center"
                                            >
                                                <div v-if="item.File_Path">
                                                    <button
                                                        @click="
                                                            openAttachment(
                                                                item.File_Path
                                                            )
                                                        "
                                                        class="btn btn-info btn-sm mx-1"
                                                    >
                                                        <i
                                                            class="fa-solid fa-file-arrow-down"
                                                        ></i>
                                                    </button>
                                                </div>
                                                <div v-if="canEdit">
                                                    <button
                                                        class="custom-btn btn-12 mx-2"
                                                        @click="
                                                            deleteExpense(
                                                                item.id
                                                            )
                                                        "
                                                    >
                                                        <i
                                                            class="fa-solid fa-trash"
                                                        ></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary" @click="detailsReport()">
                Details Report
            </button>
            <button class="btn btn-dark mx-3" @click="summaryReport()">
                Summary Report
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
