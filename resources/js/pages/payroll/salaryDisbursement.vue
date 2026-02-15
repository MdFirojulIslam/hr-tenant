<script setup>
import { ref, onMounted, computed, watch } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

/* ================= STATE ================= */
const disburse = ref([]); // for salaries data
const bankTotal = ref(0); // total for bank accounts
const cashTotal = ref(0); // total for cash payments

const error = ref([]);

const monthStatus = ref("PENDING"); // Default

const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const searchQuery = ref("");

// Auto-download formats
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

const selectedMonthName = computed(() => {
    return months[selectedMonth.value - 1];
});

const setcomplete = async () => {
    const result = await Swal.fire({
        title: "Confirm Disbursement",
        html: `
            <p>
                Are you sure you want to confirm disbursement for
                <strong>${selectedMonthName.value} ${selectedYear.value}</strong>?
            </p>
        `,
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
                `Disbursement for ${selectedMonthName.value} ${selectedYear.value} has been completed.`,
                "success",
            );

            getData();
        } catch (err) {
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

        // Determine month status (assume all rows share the same status)
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

/* ================= DOWNLOAD HELPER ================= */
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

/* ================= BANK DOWNLOAD ================= */
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

/* ================= CASH DOWNLOAD ================= */
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

/* ================= WATCHERS (AUTO DOWNLOAD) ================= */
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
    <div class="border rounded container-fluid p-3">
        <h2 class="mb-4">Monthly Salary Disbursement</h2>

        <div class="d-flex align-items-end flex-wrap">
            <!-- YEAR -->
            <div class="me-4">
                <label class="label-class">Salary Year</label>
                <select
                    class="form-control border-color date-field"
                    v-model="selectedYear"
                    @change="changeDate"
                >
                    <option v-for="y in years" :key="y" :value="y">
                        {{ y }}
                    </option>
                </select>
            </div>

            <!-- MONTH -->
            <div>
                <label class="label-class">Salary Month</label>
                <select
                    class="form-control border-color date-field"
                    v-model="selectedMonth"
                    @change="changeDate"
                >
                    <option v-for="(m, i) in months" :key="m" :value="i + 1">
                        {{ m }}
                    </option>
                </select>
            </div>

            <!-- SEARCH -->
            <div class="ms-3">
                <label class="label-class">Search</label>
                <input
                    class="form-control border-color"
                    placeholder="Name, ID, Bank A/C"
                    v-model="searchQuery"
                />
            </div>

            <div class="ms-5">
                <label class="label-class">Bank Total</label>
                <div class="total-amount fw-semibold">{{ bankTotal }}</div>
            </div>
            <div class="ms-3">
                <label class="label-class">Cash Total</label>
                <div class="total-amount fw-semibold">{{ cashTotal }}</div>
            </div>

            <!-- BANK DOWNLOAD -->
            <div class="ms-auto me-2">
                <label class="label-class">Download Bank </label>
                <select
                    class="form-control border-color selected-option"
                    v-model="bankFormat"
                >
                    <option disabled value="" class="placeholder-text">
                        -- Select Download Format --
                    </option>
                    <option value="pdf">PDF</option>
                    <option value="xlsx">Excel</option>
                    <option value="word">Word</option>
                </select>
            </div>

            <!-- CASH DOWNLOAD -->
            <div class="">
                <label class="label-class">Download Cash</label>
                <select
                    class="form-control border-color selected-option"
                    v-model="cashFormat"
                >
                    <option disabled value="">
                        -- Select Download Format --
                    </option>

                    <option value="pdf">PDF</option>
                    <option value="xlsx">Excel</option>
                    <option value="word">Word</option>
                </select>
            </div>
            <div class="ms-5" v-if="monthStatus === 'PENDING'">
                <button class="set-button" @click="setcomplete()">
                    RELEASE PAYMENT
                </button>
            </div>
            <div class="ms-2" v-if="monthStatus === 'COMPLETED'">
                <button class="completed-button">DISBURSE COMPLETED</button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="mt-4 table-responsive">
            <div class="mb-2 fw-semibold">
                Total Employees: {{ filteredDisbursement.length }}
            </div>

            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th class="text-center">Bank / Cash</th>
                        <th class="text-center">Net Salary</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in filteredDisbursement" :key="i">
                        <td>{{ i + 1 }}</td>
                        <td>ABNB-{{ item.employee_id }}</td>
                        <td>{{ item.full_name }}</td>
                        <td class="text-center">
                            {{ item.bank_account_no ?? "Cash" }}
                        </td>
                        <td class="text-center">{{ item.net_pay }}</td>
                    </tr>
                    <tr v-if="filteredDisbursement.length === 0">
                        <td colspan="5" class="text-center">No data found</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style>
.set-button {
    background-color: #bd360dd5;
    color: #fff;
    font-weight: 600;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
}
.completed-button {
    background-color: #048a1fd5;
    color: #000;
    font-weight: 600;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
}
.selected-option {
    background-color: #bfcec2d5; /* dropdown background */
    color: #000; /* text color */
    width: 200px;
    font-weight: 600;
}

.total-amount {
    background-color: rgba(45, 141, 64, 0.85);
    color: #ffffff;
    padding: 5px 10px;
    border-radius: 4px;
    border: 1px solid #1d2c20d5;
    width: 70px;
    text-align: center;
}

/* optional focus/hover */
.selected-option:focus {
    outline: none;
    border: 2px solid #059223d5;
}

.border-color {
    border: 1.8px solid #059223d5;
}
.label-class {
    font-weight: 600;
    font-size: 13px;
    padding-bottom: 5px;
}
.date-field {
    width: 100px;
}
</style>
