<template>
    <div class="card">
        <div class="card-body">
            <div class="d-flex gap-3">
                <div class="col-lg-2 mb-3">
                    <label for="">Select a Year</label>
                    <select
                        class="form-control"
                        name="status"
                        v-model="selectedYear"
                        @change="getData"
                    >
                        <option
                            v-for="(year, index) in years"
                            :key="year"
                            :value="year"
                        >
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div class="col-lg-2 mb-3">
                    <label for="">Select a Month</label>
                    <select
                        class="form-control"
                        name="status"
                        v-model="selectedMonth"
                        @change="getData"
                    >
                        <option
                            v-for="(month, index) in months"
                            :key="month"
                            :value="index"
                        >
                            {{ month }}
                        </option>
                    </select>
                </div>
                <div class="ms-auto d-flex align-items-end mb-3">
                    <div class="dropdown">
                        <button
                            class="custom-btn btn-13 export-dropdown-btn dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <i class="fa-solid fa-download"></i> Export
                        </button>
                        <ul class="dropdown-menu export-dropdown-menu">
                            <li>
                                <a
                                    class="dropdown-item"
                                    href="#"
                                    @click.prevent="exportReport('excel')"
                                >
                                    <i
                                        class="fa-solid fa-file-excel text-success"
                                    ></i>
                                    Export as Excel
                                </a>
                            </li>
                            <li>
                                <a
                                    class="dropdown-item"
                                    href="#"
                                    @click.prevent="exportReport('pdf')"
                                >
                                    <i
                                        class="fa-solid fa-file-pdf text-danger"
                                    ></i>
                                    Export as PDF
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Amount</th>
                            <th>Supervisor</th>
                            <th>Head of Department</th>
                            <th>CEO</th>
                            <th>Action</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in userExpense.expenses"
                            :key="item.id"
                            @click="navigateToDetails(item)"
                        >
                            <td>{{ index + 1 }}</td>
                            <td>{{ item.Full_Name }}</td>
                            <td>{{ item.Dept_Name }}</td>
                            <td>{{ item.Des_Name }}</td>
                            <td>{{ item.total_expense }}</td>
                            <td v-if="item.Status >= 2">Approved</td>
                            <td v-else>...</td>
                            <td v-if="item.Status >= 3">Approved</td>
                            <td v-else>...</td>
                            <td v-if="item.Status >= 4">Approved</td>
                            <td v-else>...</td>
                            <td v-if="item.Status == 4">
                                <span class="text-success">Complete</span>
                            </td>
                            <td v-else-if="item.Status == 0">...</td>
                            <td
                                v-else-if="
                                    item.Status == 1 && item.sup_id == user.id
                                "
                            >
                                <button
                                    class="custom-btn btn-15 mx-1"
                                    @click.stop="approveExpenses(item.id)"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </td>
                            <td
                                v-else-if="
                                    item.Status == 2 && item.hod_id == user.id
                                "
                            >
                                <button
                                    class="custom-btn btn-15 mx-1"
                                    @click.stop="approveExpenses(item.id)"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </td>
                            <td
                                v-else-if="
                                    item.Status == 3 && item.ceo_id == user.id
                                "
                            >
                                <button
                                    class="custom-btn btn-15 mx-1"
                                    @click.stop="approveExpenses(item.id)"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </td>
                            <td v-else>Pending</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <button
                                        class="custom-btn btn-13 mx-1"
                                        @click.stop="viewReport(item.id)"
                                    >
                                        <i class="fa-regular fa-file"></i>
                                    </button>
                                    <button
                                        class="custom-btn btn-15 mx-1"
                                        @click.stop="downloadReport(item.id)"
                                    >
                                        <i class="fa-solid fa-download"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">Grant Total</th>
                            <th>{{ userExpense.grand_total }}</th>
                            <th colspan="5"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import router from "../../router";
import Swal from "sweetalert2";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;

const error = ref([]);
const userExpense = ref("");
const Status = ref("");

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

const getData = async () => {
    try {
        const response = await api.get("/users-expense", {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value,
            },
        });
        userExpense.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

// Check if the user can edit the expense
const canEdit = (item) => {
    return (
        (item.Status == 1 && item.sup_id == user.id) ||
        (item.Status == 2 && item.hod_id == user.id) ||
        (item.Status == 3 && item.ceo_id == user.id)
    );
};

const navigateToDetails = (item) => {
    router.push({
        name: "ExpenseDetails",
        params: {
            id: item.id,
            month: selectedMonth.value,
            year: selectedYear.value,
        },
        query: {
            canEdit: canEdit(item),
        },
    });
};

const approveExpenses = async (id) => {
    try {
        const response = await api.get("/approve-expense", {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value,
                id: id,
            },
        });

        if (response.data.success) {
            Swal.fire({
                icon: "success",
                title: response.data.message,
                showConfirmButton: false,
                timer: 1500,
            });
            await getData();
        } else {
            Swal.fire({
                icon: "error",
                title: "Approval Failed",
                text: response.data.message || "An unexpected error occurred.",
                showConfirmButton: true,
            });
        }
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: err.response?.data?.message || "Error approving expenses",
            showConfirmButton: true,
        });
    }
};

const viewReport = (id) => {
    const month = selectedMonth.value;
    const year = selectedYear.value;

    window.open(`/expense-report?month=${month}&year=${year}&eid=${id}`);
};

const downloadReport = (id) => {
    const month = selectedMonth.value;
    const year = selectedYear.value;

    window.open(
        `/expense-report?month=${month}&year=${year}&eid=${id}&down=down`,
    );
};

const exportReport = (format) => {
    const month = selectedMonth.value;
    const year = selectedYear.value;

    window.open(
        `/api/export-expense-summary?month=${month}&year=${year}&format=${format}`,
    );
};

onMounted(() => getData());
</script>

<style scoped>
.export-dropdown-btn {
    height: 32px;
    line-height: 16px;
    padding: 0 16px;
    font-size: 12px;
    white-space: nowrap;
}

.export-dropdown-btn::after {
    border: none;
    margin-left: 4px;
    vertical-align: middle;
    border-top: 4px solid currentColor;
    border-right: 4px solid transparent;
    border-left: 4px solid transparent;
    content: "";
}

.export-dropdown-menu {
    min-width: 180px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    padding: 4px 0;
}

.export-dropdown-menu .dropdown-item {
    padding: 6px 16px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.export-dropdown-menu .dropdown-item:hover {
    background-color: #f0f0f0;
}

tr:hover td {
    cursor: pointer;
    background-color: #d3d9d9 !important;
}

.bg-green {
    background-color: #d4edda;
}
</style>
