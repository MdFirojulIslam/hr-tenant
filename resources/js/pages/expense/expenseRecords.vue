<script setup>
import { ref, onMounted, defineAsyncComponent } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;

const expenses = ref("");
const error = ref("");

const allowFromData = ref(null);


const allowanceComponent = defineAsyncComponent(() =>
    import("../expense/component/otherExpenseForm.vue")
);

const selectedMonth = ref(new Date().getMonth());
const selectedYear = ref(new Date().getFullYear());

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
]);

const allowanceId = ref();
const allowanceModel = ref(false);

const allowanceOpen = () => {
    allowanceModel.value = true;
};
const allowanceClose = () => {
    allowanceModel.value = false;
    getData();
};

const editallowanceForm = async (id) => {
    const allowanceData = expenses.value.allowance.find((allowance) => allowance.id === id);
    if (allowanceData) {
        allowFromData.value = allowanceData;
        allowanceId.value = id;
        allowanceOpen();
    } else {
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "Unable to find leave data.",
        });
    }
};

const travelFromData = ref(null);

const travelComponent = defineAsyncComponent(() =>
    import("../expense/component/travelForm.vue")
);

const travelId = ref();
const travelModel = ref(false);

const travelOpen = () => {
    travelModel.value = true;
};
const travelClose = () => {
    travelModel.value = false;
    getData();
};

const edittravelForm = async (id) => {
    const travelData = expenses.value.travel.find((travel) => travel.id === id);
    if (travelData) {
        travelFromData.value = travelData;
        travelId.value = id;
        travelOpen();
    } else {
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "Unable to find leave data.",
        });
    }
};


const trashComponent = defineAsyncComponent(() =>
    import("../expense/component/viewTrash.vue")
);

const trashModel = ref(false);

const trashOpen = () => {
    trashModel.value = true;
};
const trashClose = () => {
    trashModel.value = false;
    getData();
};

const generateDays = () => {
    const year = selectedYear.value;
    const month = selectedMonth.value;
    getData();
}

const getData = async () => {
    try {
        const response = await api.get("/tada-expense", {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value
            },
        });
        expenses.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};


const approveExpenses = async () => {
    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to submit the expense?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            const response = await api.get("/approve-expense", {
                params: {
                    month: selectedMonth.value,
                    year: selectedYear.value,
                    id: user.id,
                },
            });
            if (response.data.success) {
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Your Expense for this Month Submitted",
                    showConfirmButton: false,
                    timer: 1500,
                });
                await getData();
            }
        }
    } catch (err) {
        error.value = err.message || "Error approving expenses";
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

const viewReport = () => {
    const month = selectedMonth.value;
    const year = selectedYear.value;

    window.open(`/expense-report?month=${month}&year=${year}`, '_blank');
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
    <component v-if="allowanceModel" :is="allowanceComponent" :isOpen="allowanceModel" :allowanceData="allowFromData"
        :allowId="allowanceId" @modal-close="allowanceClose" name="first-modal" />

    <component v-if="travelModel" :is="travelComponent" :isOpen="travelModel" :travelData="travelFromData"
        :travelId="travelId" @modal-close="travelClose" name="first-modal" />

    <component v-if="trashModel" :is="trashComponent" :isOpen="trashModel" :month="selectedMonth" :year="selectedYear"
        :userId="user.id" @modal-close="trashClose" name="first-modal" />

    <div class="card mt-4">
        <div class="card-body">
            <div class="text-center">
                <h1 class="mb-1">Monthly Expense</h1>
            </div>
            <div class="row d-flex justify-content-between">
                <div class="col-lg-11">
                    <div class="d-flex">
                        <div class="col-lg-2 mb-3">
                            <label for="">Select a Year</label>
                            <select class="form-control" name="status" v-model="selectedYear" @change="generateDays">
                                <option v-for="(year, index) in years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-2 mb-3 mx-2">
                            <label for="">Select a Month</label>
                            <select class="form-control" name="status" v-model="selectedMonth" @change="generateDays">
                                <option v-for="(month, index) in months" :key="month" :value="index">
                                    {{ month }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-lg-1 mt-4">
                    <button class="btn btn-danger" @click="trashOpen()"><i class="fa-solid fa-trash"></i></button>
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
                                            <th>Particular</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="ver-align" v-for="(item, index) in expenses.allowance"
                                            :key="item.id">
                                            <td>{{ index + 1 }}</td>
                                            <td>{{ item.Date }}</td>
                                            <td>{{ mapExpense(item.Tr_Type) }}</td>
                                            <td>{{ item.Description }}</td>
                                            <td>{{ item.Amount }}</td>
                                            <td class="d-flex justify-content-center">
                                                <div v-if="item.File_Path">
                                                    <button @click="openAttachment(item.File_Path)"
                                                        class="btn btn-info btn-sm mx-1">
                                                        <i class="fa-solid fa-file-arrow-down"></i>
                                                    </button>
                                                </div>
                                                <div v-if="item.Status == 0">
                                                    <button class="btn btn-warning btn-sm mx-1"
                                                        @click="editallowanceForm(item.id)">
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </button>
                                                </div>
                                                <div v-if="item.Status === 0">
                                                    <button class="custom-btn btn-12 mx-2"
                                                        @click="deleteExpense(item.id)">
                                                        <i class="fa-solid fa-trash"></i>
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
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Vehicle</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="ver-align" v-for="(item, index) in expenses.travel" :key="item.id">
                                            <td>{{ index + 1 }}</td>
                                            <td>{{ item.Date }}</td>
                                            <td>{{ item.From }}</td>
                                            <td>{{ item.To }}</td>
                                            <td>{{ item.Vehicle }}</td>
                                            <td>{{ item.Amount }}</td>
                                            <td class="d-flex justify-content-center">
                                                <div v-if="item.File_Path">
                                                    <button @click="openAttachment(item.File_Path)"
                                                        class="btn btn-info btn-sm mx-1">
                                                        <i class="fa-solid fa-file-arrow-down"></i>
                                                    </button>
                                                </div>
                                                <div v-if="item.Status == 0">
                                                    <button class="btn btn-warning btn-sm mx-1"
                                                        @click="edittravelForm(item.id)">
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </button>
                                                </div>
                                                <div v-if="item.Status === 0">
                                                    <button class="custom-btn btn-12 mx-2"
                                                        @click="deleteExpense(item.id)">
                                                        <i class="fa-solid fa-trash"></i>
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
            <div class="d-flex justify-content-between">
                <button class="btn btn-primary" @click="viewReport()">View Report</button>
                <button v-if="expenses.travel && expenses.travel.length > 0 && expenses.travel[0].Status == 0"
                    class="btn btn-success mx-2" @click="approveExpenses()">Submit Expense</button>
            </div>
        </div>
    </div>
</template>
