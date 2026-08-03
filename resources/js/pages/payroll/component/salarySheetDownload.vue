<template>
    <div>
        <!-- Modal Wrapper -->
        <div
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            @click.self="emit('modal-close')"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <!-- Simple, Clean Header -->
                    <div class="modal-header border-bottom bg-light px-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i
                                class="fa-solid fa-file-export text-primary fs-5"
                            ></i>
                            <h5 class="modal-title fw-semibold text-dark mb-0">
                                Export Payroll ({{ monthName }}
                                {{ props.selectedYear }})
                            </h5>
                        </div>
                        <button
                            type="button"
                            class="btn-close shadow-none"
                            @click="emit('modal-close')"
                            aria-label="Close"
                        ></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label
                                class="form-label fw-bold small text-secondary"
                            >
                                Select Export Document
                            </label>

                            <!-- Grouped Select Dropdown -->
                            <div class="input-group">
                                <span
                                    class="input-group-text bg-light text-secondary border-end-0"
                                >
                                    <i class="fa-solid fa-file-lines"></i>
                                </span>
                                <select
                                    class="form-select border-start-0 custom-select"
                                    v-model="selectedTemplate"
                                >
                                    <option value="" disabled>
                                        -- Choose a template --
                                    </option>

                                    <optgroup label="Main Statement">
                                        <option value="salary_sheet">
                                            Master Salary Sheet (.xlsx)
                                        </option>
                                    </optgroup>

                                    <optgroup label="Bank Payment Templates">
                                        <option value="bank_excel">
                                            Bank Excel Statement (.xlsx)
                                        </option>
                                        <option value="bank_pdf">
                                            Bank PDF Document (.pdf)
                                        </option>
                                        <option value="bank_word">
                                            Bank Word Document (.docx)
                                        </option>
                                    </optgroup>

                                    <optgroup label="Cash Payment Templates">
                                        <option value="cash_excel">
                                            Cash Excel Statement (.xlsx)
                                        </option>
                                        <option value="cash_pdf">
                                            Cash PDF Document (.pdf)
                                        </option>
                                        <option value="cash_word">
                                            Cash Word Document (.docx)
                                        </option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <!-- Format Badge Helper -->
                        <div
                            v-if="selectedTemplate"
                            class="alert alert-light border d-flex align-items-center gap-2 mb-0 py-2 px-3"
                        >
                            <i :class="selectedFormatIcon" class="fs-5"></i>
                            <span class="small text-secondary">
                                You are downloading template:
                                <strong class="text-dark">{{
                                    selectedFormatLabel
                                }}</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-top bg-light px-4 py-2.5">
                        <button
                            type="button"
                            class="btn btn-outline-secondary px-3 btn-sm fw-semibold"
                            @click="emit('modal-close')"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="btn btn-primary px-4 btn-sm fw-semibold d-flex align-items-center gap-2"
                            :disabled="isLoading"
                            @click="download"
                        >
                            <span
                                v-if="isLoading"
                                class="spinner-border spinner-border-sm"
                                role="status"
                            ></span>
                            <i v-else class="fa-solid fa-download"></i>
                            <span>{{
                                isLoading ? "Downloading..." : "Download"
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backdrop -->
        <div class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const props = defineProps({
    selectedYear: {
        type: [String, Number],
        required: true,
    },
    selectedMonth: {
        type: [String, Number],
        required: true,
    },
});

const emit = defineEmits(["modal-close"]);

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

const monthName = computed(() => months[Number(props.selectedMonth) - 1] || "");

const selectedTemplate = ref("salary_sheet");
const isLoading = ref(false);

// Format Info Helpers
const selectedFormatIcon = computed(() => {
    if (
        selectedTemplate.value.includes("excel") ||
        selectedTemplate.value === "salary_sheet"
    ) {
        return "fa-solid fa-file-excel text-success";
    }
    if (selectedTemplate.value.includes("pdf")) {
        return "fa-solid fa-file-pdf text-danger";
    }
    if (selectedTemplate.value.includes("word")) {
        return "fa-solid fa-file-word text-primary";
    }
    return "fa-solid fa-file";
});

const selectedFormatLabel = computed(() => {
    const map = {
        salary_sheet: "Salary Sheet (Excel)",
        bank_excel: "Bank Disbursement (Excel)",
        bank_pdf: "Bank Disbursement (PDF)",
        bank_word: "Bank Disbursement (Word)",
        cash_excel: "Cash Disbursement (Excel)",
        cash_pdf: "Cash Disbursement (PDF)",
        cash_word: "Cash Disbursement (Word)",
    };
    return map[selectedTemplate.value] || "";
});

const download = async () => {
    if (!selectedTemplate.value) {
        Swal.fire({
            icon: "warning",
            title: "Please select a template.",
            confirmColor: "#0d6efd",
        });
        return;
    }

    isLoading.value = true;

    try {
        switch (selectedTemplate.value) {
            case "salary_sheet":
                await downloadSalary();
                break;
            case "bank_excel":
                await downloadBank("excel");
                break;
            case "bank_pdf":
                await downloadBank("pdf");
                break;
            case "bank_word":
                await downloadBank("word");
                break;
            case "cash_excel":
                await downloadCash("excel");
                break;
            case "cash_pdf":
                await downloadCash("pdf");
                break;
            case "cash_word":
                await downloadCash("word");
                break;
        }
        emit("modal-close");
    } finally {
        isLoading.value = false;
    }
};

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

    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
};

const downloadSalary = async () => {
    try {
        const response = await api.get("/export-salary-sheet", {
            responseType: "blob",
            params: {
                year: props.selectedYear,
                month: props.selectedMonth,
            },
        });

        downloadFile(
            response,
            `Salary_Sheet_${props.selectedYear}_${props.selectedMonth}.xlsx`,
        );
    } catch {
        Swal.fire({
            icon: "error",
            title: "Download failed!",
            text: "Please try again.",
            confirmColor: "#0d6efd",
        });
    }
};

const downloadBank = async (type) => {
    try {
        const endpoint =
            type === "excel"
                ? "/salary-disbursement-bank-xlsx"
                : type === "pdf"
                  ? "/salary-disbursement-bank-pdf"
                  : "/salary-disbursement-bank-word";

        const response = await api.get(endpoint, {
            responseType: "blob",
            params: {
                year: props.selectedYear,
                month: props.selectedMonth,
            },
        });

        const ext = type === "excel" ? "xlsx" : type === "pdf" ? "pdf" : "docx";

        downloadFile(
            response,
            `Salary_Disbursement_Bank_${props.selectedYear}_${props.selectedMonth}.${ext}`,
        );
    } catch (error) {
        console.error(error);
        Swal.fire("Download failed!", "Please try again.", "error");
    }
};

const downloadCash = async (type) => {
    try {
        const endpoint =
            type === "excel"
                ? "/salary-disbursement-cash-xlsx"
                : type === "pdf"
                  ? "/salary-disbursement-cash-pdf"
                  : "/salary-disbursement-cash-word";

        const response = await api.get(endpoint, {
            responseType: "blob",
            params: {
                year: props.selectedYear,
                month: props.selectedMonth,
            },
        });

        const ext = type === "excel" ? "xlsx" : type === "pdf" ? "pdf" : "docx";

        downloadFile(
            response,
            `Salary_Disbursement_Cash_${props.selectedYear}_${props.selectedMonth}.${ext}`,
        );
    } catch (error) {
        console.error(error);
        Swal.fire("Download failed!", "Please try again.", "error");
    }
};

// const downloadBank = async (type) => {
//     try {
//         const response = await api.get(`/salary-disbursement-bank-${type}`, {
//             responseType: "blob",
//             params: {
//                 year: props.selectedYear,
//                 month: props.selectedMonth,
//             },
//         });

//         const ext =
//             type === "word" ? "docx" : type === "excel" ? "xlsx" : "pdf";
//         downloadFile(
//             response,
//             `Salary_Disbursement_Bank_${props.selectedYear}_${props.selectedMonth}.${ext}`,
//         );
//     } catch {
//         Swal.fire("Download failed!", "Please try again.", "error");
//     }
// };

// const downloadCash = async (type) => {
//     try {
//         const response = await api.get(`/salary-disbursement-cash-${type}`, {
//             responseType: "blob",
//             params: {
//                 year: props.selectedYear,
//                 month: props.selectedMonth,
//             },
//         });

//         const ext =
//             type === "word" ? "docx" : type === "excel" ? "xlsx" : "pdf";
//         downloadFile(
//             response,
//             `Salary_Disbursement_Cash_${props.selectedYear}_${props.selectedMonth}.${ext}`,
//         );
//     } catch {
//         Swal.fire("Download failed!", "Please try again.", "error");
//     }
// };
</script>

<style scoped>
.custom-select {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}

.custom-select:focus {
    border-color: #0d6efd;
    box-shadow: none;
}

optgroup {
    font-weight: 700;
    color: #6c757d;
}

option {
    font-weight: 400;
    color: #212529;
    padding: 4px 8px;
}
</style>
