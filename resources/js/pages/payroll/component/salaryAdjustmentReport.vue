<script setup>
import { ref, defineProps, defineEmits } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

defineProps(["isOpen"]);
const emit = defineEmits(["modal-close"]);

const reportMonth = ref(new Date().getMonth() + 1);
const reportYear = ref(new Date().getFullYear());
const reportFormat = ref("pdf");

const years = ["2022", "2023", "2024", "2025", "2026", "2027"];
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

const closeModal = () => emit("modal-close");

const downloadReport = async () => {
    if (!reportMonth.value || !reportYear.value) {
        Swal.fire("Warning", "Select Month and Year", "warning");
        return;
    }

    try {
        const response = await api.get("/salary-adjustment-report", {
            params: {
                month: reportMonth.value,
                year: reportYear.value,
                format: reportFormat.value,
            },
            responseType: "blob",
        });

        const blob = new Blob([response.data]);
        const link = document.createElement("a");
        link.href = window.URL.createObjectURL(blob);

        link.download =
            reportFormat.value === "pdf"
                ? "salary_adjustment_report.pdf"
                : "salary_adjustment_report.xlsx";

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
                    <h5 class="modal-title">Monthly Adjustment Report</h5>

                    <button
                        type="button"
                        class="btn-close btn-close-design bordered"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <div class="row g-3">
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
    padding: 5px 10px;
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
    border: 1px solid white;
    border-radius: 5px;
}
</style>
