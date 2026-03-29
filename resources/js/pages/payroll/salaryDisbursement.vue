<script setup>
import { ref, onMounted, computed, watch } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

/* ================= STATE ================= */
const disburse = ref([]);
const bankTotal = ref(0);
const cashTotal = ref(0);
const error = ref([]);

const monthStatus = ref("PENDING");

const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const searchQuery = ref("");

const bankFormat = ref("");
const cashFormat = ref("");

/* ================= STATIC DATA ================= */
const years = [
    "2022",
    "2023",
    "2024",
    "2025",
    "2026",
    "2027",
    "2028",
    "2029",
    "2030",
    "2031",
    "2032",
    "2033",
    "2034",
    "2035",
];

const months = [
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
];

const selectedMonthName = computed(() => months[selectedMonth.value - 1]);

/* ================= COMPLETE ================= */
const setcomplete = async () => {
    const result = await Swal.fire({
        title: "Confirm Disbursement",
        html: `<p>Confirm disbursement for <strong>${selectedMonthName.value} ${selectedYear.value}</strong>?</p>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, Confirm",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#048a1fd5",
        cancelButtonColor: "#bd360dd5",
    });

    if (result.isConfirmed) {
        try {
            await api.post("/salary-disbursement-complete", {
                year: selectedYear.value,
                month: selectedMonth.value,
            });

            Swal.fire(
                "Completed!",
                `Disbursement for ${selectedMonthName.value} ${selectedYear.value} completed.`,
                "success",
            );

            getData();
        } catch {
            Swal.fire("Error", "Failed to complete disbursement", "error");
        }
    }
};

/* ================= FETCH DATA ================= */
const getData = async () => {
    try {
        const response = await api.get("/salary-disbursement", {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        disburse.value = response.data.salaries;
        bankTotal.value = response.data.bank_total;
        cashTotal.value = response.data.cash_total;

        if (disburse.value.length > 0) {
            monthStatus.value = String(
                disburse.value[0].disbursement_status,
            ).toUpperCase();
        } else {
            monthStatus.value = "PENDING";
        }
    } catch (err) {
        error.value = err.message || "Failed to load data";
    }
};

const changeDate = () => getData();

/* ================= FILTER ================= */
const filteredDisbursement = computed(() => {
    if (!searchQuery.value) return disburse.value;

    const q = searchQuery.value.toLowerCase();

    return disburse.value.filter(
        (s) =>
            (s.full_name || "").toLowerCase().includes(q) ||
            String(s.employee_id || "").includes(q) ||
            String(s.bank_account_no || "").includes(q),
    );
});

/* ================= DOWNLOAD ================= */
const downloadFile = (response, filename) => {
    const blob = new Blob([response.data], {
        type: response.headers["content-type"],
    });

    const url = window.URL.createObjectURL(blob);

    const link = document.createElement("a");
    link.href = url;
    link.download = filename;

    document.body.appendChild(link);
    link.click();

    window.URL.revokeObjectURL(url);
    document.body.removeChild(link);
};

const downloadBank = async (type) => {
    try {
        const response = await api.get(`/salary-disbursement-bank-${type}`, {
            responseType: "blob",
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        downloadFile(
            response,
            `Salary_Disbursement_Bank_${selectedYear.value}_${selectedMonth.value}.${type === "word" ? "docx" : type}`,
        );
    } catch {
        Swal.fire("Download failed!", "Please try again.", "error");
    }
};

const downloadCash = async (type) => {
    try {
        const response = await api.get(`/salary-disbursement-cash-${type}`, {
            responseType: "blob",
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });

        downloadFile(
            response,
            `Salary_Disbursement_Cash_${selectedYear.value}_${selectedMonth.value}.${type === "word" ? "docx" : type}`,
        );
    } catch {
        Swal.fire("Download failed!", "Please try again.", "error");
    }
};

/* ================= WATCH ================= */
watch(bankFormat, (format) => {
    if (!format) return;
    downloadBank(format);
    bankFormat.value = "";
});

watch(cashFormat, (format) => {
    if (!format) return;
    downloadCash(format);
    cashFormat.value = "";
});

/* ================= INIT ================= */
onMounted(() => getData());
</script>

<template>
    <div class="container-fluid border rounded p-3">
        <h4 class="mb-3 fw-bold">Monthly Salary Disbursement</h4>

        <!-- CONTROLS -->
        <div class="row g-3 align-items-end">
            <div class="col-6 col-md-1">
                <label class="label-class">Salary Year</label>
                <select
                    class="form-control border-color"
                    v-model="selectedYear"
                    @change="changeDate"
                >
                    <option v-for="y in years" :key="y" :value="y">
                        {{ y }}
                    </option>
                </select>
            </div>

            <div class="col-6 col-md-1">
                <label class="label-class">Salary Month</label>
                <select
                    class="form-control border-color"
                    v-model="selectedMonth"
                    @change="changeDate"
                >
                    <option v-for="(m, i) in months" :key="m" :value="i + 1">
                        {{ m }}
                    </option>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="label-class">Search</label>
                <input
                    class="form-control border-color"
                    placeholder="Name, ID, Bank A/C"
                    v-model="searchQuery"
                />
            </div>

            <div class="col-6 col-md-1">
                <label class="label-class">Bank Total</label>
                <div class="total-amount">{{ bankTotal }}</div>
            </div>

            <div class="col-6 col-md-1">
                <label class="label-class">Cash Total</label>
                <div class="total-amount">{{ cashTotal }}</div>
            </div>

            <div class="col-12 col-md-1 ms-auto">
                <label class="label-class">Download Bank</label>
                <select
                    class="form-control border-color selected-option"
                    v-model="bankFormat"
                >
                    <option disabled value="">Select Format</option>
                    <option value="pdf">PDF</option>
                    <option value="xlsx">Excel</option>
                    <option value="word">Word</option>
                </select>
            </div>

            <div class="col-12 col-md-1">
                <label class="label-class">Download Cash</label>
                <select
                    class="form-control border-color selected-option"
                    v-model="cashFormat"
                >
                    <option disabled value="">Select Format</option>
                    <option value="pdf">PDF</option>
                    <option value="xlsx">Excel</option>
                    <option value="word">Word</option>
                </select>
            </div>

            <div class="col-12 col-md-1 d-grid">
                <button
                    class="set-button"
                    @click="setcomplete"
                    :disabled="monthStatus === 'COMPLETED'"
                >
                    RELEASE PAYMENT
                </button>
            </div>

            <div class="col-12 col-md-3" v-if="monthStatus === 'COMPLETED'">
                <button class="completed-button w-100" disabled>
                    DISBURSE COMPLETED
                </button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-responsive mt-4">
            <div class="fw-semibold mb-2">
                Total Employees: {{ filteredDisbursement.length }}
            </div>

            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light text-nowrap">
                    <tr>
                        <th class="text-center">S/N</th>
                        <th class="text-center">EMPLOYEE ID</th>
                        <th>EMPLOYEE NAME</th>
                        <th class="text-center">BANK / CASH</th>
                        <th class="text-center">NET SALARY</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="(item, i) in filteredDisbursement"
                        :key="i"
                        class="text-nowrap"
                    >
                        <td class="text-center">{{ i + 1 }}</td>
                        <td class="text-center">ABNB-{{ item.employee_id }}</td>
                        <td class="text-nowrap">{{ item.full_name }}</td>
                        <td class="text-center">
                            {{ item.bank_account_no ?? "Cash" }}
                        </td>
                        <td class="text-center">{{ item.net_pay }}</td>
                    </tr>

                    <tr v-if="filteredDisbursement.length === 0">
                        <td colspan="5" class="text-center py-3">
                            No data found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style>
.border-color {
    border: 1.5px solid #059223d5;
}

.label-class {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 3px;
}

.selected-option {
    background: #bfcec2d5;
    font-weight: 600;
}

.total-amount {
    background: rgba(45, 141, 64, 0.85);
    color: #fff;
    padding: 6px 10px;
    border-radius: 4px;
    border: 1px solid #1d2c20d5;
    text-align: center;
    font-weight: 600;
}

.set-button {
    background: #bd360dd5;
    color: #fff;
    font-weight: 600;
    border: none;
    padding: 7px 10px;
    border-radius: 4px;
}

.completed-button {
    background: #048a1fd5;
    color: #000;
    font-weight: 600;
    border: none;
    padding: 7px 10px;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .table {
        font-size: 12px;
    }

    .total-amount {
        font-size: 12px;
    }
}
</style>
