<script setup>
import { ref, computed, onMounted } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

// Props & Emits
const props = defineProps({
    isOpen: Boolean,
    editStore: Object,
    roleId: Number,
    updateinfo: String,
});
const emit = defineEmits(["modal-close", "refresh-list"]);

// States
const repaymentsInfo = ref([]);
const allChecked = ref(false);
const error = ref("");

const getCurrentMonthDate = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    return `${year}-${month}-01`; // First day of current month
};

// Load API Data
const getData = async () => {
    try {
        const res = await api.get("/repayments-info");
        repaymentsInfo.value = res.data.map((item) => {
            const autoAmount =
                Number(item.principal_amount) / Number(item.tenure_months);
            return {
                id: item.id,
                official_id: item.official_id,
                employee_id: item.employee_id,
                employee_name: item.employee_name,
                department_name: item.department_name,
                principal_amount: item.principal_amount,
                tenure_months: item.tenure_months,
                paid_installments: item.paid_installments,

                // Custom fields
                installment_date:
                    item.installment_date || getCurrentMonthDate(),

                installment_amount: autoAmount.toFixed(2),
                checked: false,
            };
        });
    } catch (err) {
        console.error(err);
    }
};

// Computed — return data directly
const filteredData = computed(() => repaymentsInfo.value);

// Bulk Check
const toggleAll = () => {
    filteredData.value.forEach((row) => {
        row.checked = allChecked.value;
    });
};

// Individual Check Logic
const checkIndividual = () => {
    allChecked.value = filteredData.value.every((row) => row.checked);
};

// Reset Form
const resetForm = () => {
    repaymentsInfo.value = [];
    allChecked.value = false;
    error.value = "";
};

// Close Modal
const closeModal = () => emit("modal-close");

// Save Data
const saveRepayment = async () => {
    const selectedRows = filteredData.value
        .filter((r) => r.checked)
        .map((r) => ({
            employee_id: r.employee_id, // ✅ required for backend
            installment_date: r.installment_date,
            installment_amount: r.installment_amount,
            installment_status: "Paid", // ✅ set status
        }));

    if (selectedRows.length === 0) {
        error.value = "Please select at least one row.";
        return;
    }

    for (let row of selectedRows) {
        if (!row.installment_date || !row.installment_amount) {
            error.value = "Installment date & amount required.";
            return;
        }
        row.installment_status = "Paid";
    }

    try {
        const response = await api.post("/loan-repayment", selectedRows);
        if (response.data.success) {
            Swal.fire({
                icon: "success",
                title: response.data.message,
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
            emit("refresh-list");
            closeModal();
        } else {
            Swal.fire({ icon: "error", title: response.data.message });
        }
    } catch (err) {
        error.value = err.response?.data?.message || err.message;
    }
};

const enableDrag = () => {
    const dialog = document.querySelector(".modal-dialog");
    const header = document.querySelector(".modal-header");

    if (!dialog || !header) return;

    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;
    let originalWidth = 0;

    header.style.cursor = "move";

    header.addEventListener("mousedown", (e) => {
        isDragging = true;

        // lock width
        originalWidth = dialog.offsetWidth;
        dialog.style.width = originalWidth + "px";

        const rect = dialog.getBoundingClientRect();

        // SAVE CURRENT POSITION BEFORE REMOVING transform
        const currentLeft = rect.left;
        const currentTop = rect.top;

        // remove bootstrap centering
        dialog.style.transform = "none";
        dialog.style.margin = 0;
        dialog.style.position = "absolute";

        // SET EXPLICIT LEFT + TOP so modal does NOT jump
        dialog.style.left = currentLeft + "px";
        dialog.style.top = currentTop + "px";

        // mouse offsets
        offsetX = e.clientX - currentLeft;
        offsetY = e.clientY - currentTop;
    });

    document.addEventListener("mousemove", (e) => {
        if (!isDragging) return;

        dialog.style.left = e.clientX - offsetX + "px";
        dialog.style.top = e.clientY - offsetY + "px";
    });

    document.addEventListener("mouseup", () => {
        isDragging = false;
    });
};

// Mounted
onMounted(() => {
    getData();
    enableDrag();
});
</script>

<template>
    <div v-if="isOpen" class="modal fade show d-block" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <!-- Header -->
                <div class="modal-header gradient text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-coin me-2"></i> Loan Repayment
                        Setup
                    </h5>
                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Body -->
                <div class="modal-body overflow-auto" style="max-height: 80vh">
                    <form @submit.prevent="saveRepayment">
                        <div class="card mb-3 shadow-sm">
                            <table
                                class="table table-hover table-sm table-bordered"
                            >
                                <thead class="table-light">
                                    <tr class="text-nowrap">
                                        <th>SL</th>
                                        <th>Official ID</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Principal</th>
                                        <th>Tenure</th>
                                        <th>Total Paid</th>
                                        <th>Installment Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>
                                            <input
                                                type="checkbox"
                                                v-model="allChecked"
                                                @change="toggleAll"
                                            />
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in filteredData"
                                        :key="index"
                                    >
                                        <td>{{ index + 1 }}</td>
                                        <td>ABNB-{{ item.official_id }}</td>
                                        <td>{{ item.employee_name }}</td>
                                        <td>{{ item.department_name }}</td>
                                        <td>{{ item.principal_amount }}</td>
                                        <td>{{ item.tenure_months }}</td>
                                        <td>{{ item.paid_installments }}</td>

                                        <td>
                                            <input
                                                type="date"
                                                class="form-control border-color"
                                                v-model="item.installment_date"
                                            />
                                        </td>

                                        <td>
                                            <input
                                                type="decimal"
                                                class="form-control border-color"
                                                v-model="
                                                    item.installment_amount
                                                "
                                            />
                                        </td>

                                        <td class="text-center">
                                            <span
                                                class="badge bg-success text-white px-3 py-1 paid-badge"
                                                >Paid</span
                                            >
                                        </td>

                                        <td>
                                            <input
                                                type="checkbox"
                                                v-model="item.checked"
                                                @change="checkIndividual"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Buttons -->
                        <div class="d-flex justify-content-end mt-4 gap-2">
                            <button
                                type="submit"
                                class="btn btn-success px-4 py-2"
                            >
                                <i class="bi bi-check-circle me-1"></i>
                                {{ updateinfo }}
                            </button>

                            <button
                                type="button"
                                class="btn btn-warning px-4 py-2"
                                @click="resetForm"
                            >
                                <i class="bi bi-arrow-clockwise me-1"></i> Reset
                            </button>

                            <button
                                type="button"
                                class="btn btn-secondary px-4 py-2"
                                @click="closeModal"
                            >
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </button>
                        </div>

                        <p v-if="error" class="text-danger mt-3 text-center">
                            {{ error }}
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.border-color {
    border: 1.8px solid #059223d5;
    border-radius: 4px;
}
.gradient {
    background: linear-gradient(
        90deg,
        rgb(28, 105, 9) 27%,
        rgb(14, 119, 58) 100%
    );
    color: #fff;
}
.modal {
    background-color: rgba(0, 0, 0, 0.5);
}
.paid-badge {
    font-size: 12px;
    font-weight: 300;
}
</style>
