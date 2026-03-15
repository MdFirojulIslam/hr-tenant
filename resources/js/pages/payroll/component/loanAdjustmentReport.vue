<script setup>
import { ref, defineProps, defineEmits, watch } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

defineProps(["isOpen"]);
const emit = defineEmits(["modal-close"]);

// ----------------------
// Mode
// ----------------------
const reportMode = ref("monthly"); // "monthly" or "total"

// ----------------------
// Monthly fields
// ----------------------
const reportMonth = ref(new Date().getMonth() + 1); // default to current month
const reportYear = ref(new Date().getFullYear());
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
const years = ["2025", "2026", "2027"];
const reportFormat = ref("pdf");

// ----------------------
// Total report fields
// ----------------------
const totalFromDate = ref("");
const totalToDate = ref("");
const totalFormat = ref("pdf");

// ----------------------
// Close modal
// ----------------------
const closeModal = () => emit("modal-close");

// ----------------------
// Download report
// ----------------------
const downloadReport = async () => {
    try {
        let params = {};

        if (reportMode.value === "monthly") {
            if (!reportMonth.value || !reportYear.value) {
                Swal.fire("Warning", "Select Month and Year", "warning");
                return;
            }
            params = {
                mode: "monthly",
                month: reportMonth.value,
                year: reportYear.value,
                format: reportFormat.value,
            };
        } else if (reportMode.value === "total") {
            if (!totalFromDate.value || !totalToDate.value) {
                Swal.fire("Warning", "Select From and To Dates", "warning");
                return;
            }
            params = {
                mode: "total",
                from_date: totalFromDate.value,
                to_date: totalToDate.value,
                format: totalFormat.value,
            };
        }

        const response = await api.get("/loan-adjustment-report", {
            params,
            responseType: "blob",
        });

        const blob = new Blob([response.data]);
        const link = document.createElement("a");
        link.href = window.URL.createObjectURL(blob);
        link.download =
            (reportMode.value === "monthly"
                ? reportFormat.value
                : totalFormat.value) === "pdf"
                ? "loan_adjustment_report.pdf"
                : "loan_adjustment_report.xlsx";
        link.click();

        closeModal();
    } catch (error) {
        Swal.fire("Error", "Download failed", "error");
    }
};
</script>

<template>
    <div v-if="isOpen" class="modal fade show d-block" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header">
                    <button
                        class="btn monthly-button"
                        :class="{
                            'btn-success': reportMode === 'monthly',
                            'btn-secondary': reportMode !== 'monthly',
                        }"
                        @click="reportMode = 'monthly'"
                    >
                        MONTHLY
                    </button>
                    <button
                        class="btn total-button ms-3"
                        :class="{
                            'btn-success': reportMode === 'total',
                            'btn-secondary': reportMode !== 'total',
                        }"
                        @click="reportMode = 'total'"
                    >
                        TOTAL
                    </button>

                    <button
                        type="button"
                        class="btn-close btn-close-design bordered"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <div v-if="reportMode === 'monthly'" class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Month</label>
                            <select
                                v-model="reportMonth"
                                class="form-control field-background-colour"
                            >
                                <option value="">Select Month</option>
                                <option
                                    v-for="(month, index) in months"
                                    :key="month"
                                    :value="index + 1"
                                >
                                    {{ month }}
                                </option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Year</label>
                            <select
                                v-model="reportYear"
                                class="form-control field-background-colour"
                            >
                                <option value="">Select Year</option>
                                <option
                                    v-for="year in years"
                                    :key="year"
                                    :value="year"
                                >
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Format</label>
                            <select
                                v-model="reportFormat"
                                class="form-control field-background-colour"
                            >
                                <option value="pdf">PDF</option>
                                <option value="xlsx">Excel</option>
                            </select>
                        </div>
                    </div>

                    <div v-else-if="reportMode === 'total'" class="row g-3">
                        <div class="col-6">
                            <label class="form-label">From Date</label>
                            <input
                                type="date"
                                v-model="totalFromDate"
                                class="form-control field-background-colour"
                            />
                        </div>
                        <div class="col-6">
                            <label class="form-label">To Date</label>
                            <input
                                type="date"
                                v-model="totalToDate"
                                class="form-control field-background-colour"
                            />
                        </div>
                        <div class="col-12">
                            <label class="form-label">Format</label>
                            <select
                                v-model="totalFormat"
                                class="form-control field-background-colour"
                            >
                                <option value="pdf">PDF</option>
                                <option value="xlsx">Excel</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeModal">
                        Cancel
                    </button>
                    <button class="btn btn-primary" @click="downloadReport">
                        <i class="fa-solid fa-download me-2"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Backdrop -->
    <div v-if="isOpen" class="modal-backdrop fade show"></div>
</template>

<style scoped>
.modal-header {
    background-color: rgb(77, 117, 84);
    color: white;
    padding: 5px 10px 5px 10px;
}
.total-button {
    background-color: rgb(17, 65, 29); /* normal background */
    color: white; /* text color */
    border: 1px solid black; /* normal border */
}

/* when mouse is over the button */
.total-button:hover {
    background-color: rgb(17, 65, 29); /* normal background */
    border: 1px solid yellow; /* change border on hover */
    color: white; /* text color */
}
.monthly-button {
    background-color: rgb(139, 153, 9);
    border: 1px solid black;
}
.monthly-button:hover {
    background-color: rgb(139, 153, 9);
    border: 1px solid rgb(218, 5, 5);
}
.btn-close-design {
    background-color: rgb(7, 206, 57);
}
.field-background-colour {
    background: #c4c2c250;
    border: 0.8px solid #b3a9a963;
}
.form-label {
    letter-spacing: 0.8px;
    font-weight: 200;
    font-size: 13px;
}
.bordered {
    border: 1px solid white; /* adds a white border */
    border-radius: 5px; /* optional rounded corners */
}
</style>
