<script setup>
import { ref, defineProps, getCurrentInstance, onMounted } from "vue";
import Swal from "sweetalert2";
import api from "@/api";

const { isOpen, lnId } = defineProps([
    "isOpen",
    "lnId",
]);

const error = ref([]);
const editableRepayments = ref([]);

const form = ref({
    department: "",
    eid: "",
    gross_salary: 0,
});



const getData = async () => {
    try {
        const response = await api.get(`/get-loan-repayment/${lnId}`);
        editableRepayments.value = JSON.parse(JSON.stringify(response.data));
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};



const instance = getCurrentInstance();

const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        if (typeof form.value[key] === "string") {
            form.value[key] = "";
        } else {
            form.value[key] = 0;
        }
    });
};

const closeModal = () => {
    instance.emit("modal-close");
};


const update = async () => {
    try {
        const updatedData = editableRepayments.value.map(({ id, Due_On }) => ({
            id,
            Due_On,
        }));
        const response = await api.post(`/update-repayment-dates`, updatedData);

        if (response.data.success) {
            Swal.fire({
                icon: "success",
                title: "Dates updated successfully",
                showConfirmButton: false,
                timer: 1500,
            });
            closeModal();
        }
    } catch (err) {
        Swal.fire("Error", "Could not update dates", "error");
        console.error(err);
    }
};

const paymentStatus = (status) => {
    const statusMap = {
        0: "Unpaid",
        1: "Paid",
    };
    return statusMap[status] || "N/A";
};

onMounted(() => getData());
</script>

<template>
    <div v-if="isOpen" class="modal-mask col-lg-6 col-md-6 grid-margin stretch-card" ref="target">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive repayment-table">
                    <table class="table table-fixed">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Payment Status</th>
                                <th>Payment Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(item, index) in editableRepayments" :key="item.id">
                                <td>
                                    <input type="date" class="form-control" v-model="editableRepayments[index].Due_On"
                                        :readonly="item.Is_Paid" />
                                </td>
                                <td>{{ item.Amount }}</td>
                                <td>{{ paymentStatus(item.Is_Paid) }}</td>
                                <td>{{ item.Paid_On }}</td>
                            </tr>

                        </tbody>
                    </table>
                </div>


                <div class="d-flex justify-content-between mt-3">
                    <button type="submit" class="custom-btn btn-13 p-2" @click="update">
                        <i class="fa-solid fa-check"></i> | Save
                    </button>
                    <button type="reset" class="custom-btn btn-12 p-2" @click.stop="closeModal">
                        <i class="fa-solid fa-x"></i> | Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.salary-table td input,
.salary-table td select {
    margin: 7px;
}

#BrandModal,
.modal-mask {
    position: fixed;
    z-index: 2000;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-container {
    padding: 20px 30px;
    background-color: #620d0d;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    background-color: blue;
}

.swal2-container {
    z-index: 2001 !important;
    /* Make sure it's higher than .modal-mask */
}

.swal-popup-zindex {
    z-index: 2001 !important;
    /* Ensure it's higher than .modal-mask */
}

.repayment-table {
    width: 500px;
    max-height: 300px;
}

.table-fixed {
    width: 100%;
}

.table-fixed td,
.table-fixed th {
    white-space: nowrap;
    text-align: center;
}
</style>
