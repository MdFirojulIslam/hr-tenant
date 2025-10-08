<script setup>
import { ref, defineProps, getCurrentInstance, onMounted } from "vue";
import api from "@/api";

const { isOpen, month, year, userId } = defineProps([
    "isOpen",
    "month",
    "year",
    "userId",
]);

const instance = getCurrentInstance();

const closeModal = () => {
    instance.emit("modal-close");
};

const trashedExpense = ref("");

const mapExpense = (status) => {
    const statusMap = {
        da: "Daily Allowance",
        oe: "Other Expense",
        ta: "Travel Allowance",
    };
    return statusMap[status];
};

const getData = async () => {
    try {
        const response = await api.get("/trashed-expense", {
            params: {
                month: month,
                year: year,
                userId: userId,
            },
        });
        trashedExpense.value = response.data;
    } catch (err) {
        console.error = err.message || "Error fetching data";
    }
};

onMounted(() => getData());
</script>

<template>
    <div v-if="isOpen" class="modal-mask col-lg-9 col-md-9 grid-margin stretch-card" ref="target">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end">
                    <button class="custom-btn btn-12 p-2" @click.stop="closeModal">
                        <i class="fa-solid fa-x"></i> 
                    </button>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Date</th>
                                <th>Weekday</th>
                                <th>Particular</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="ver-align" v-for="(item, index) in trashedExpense" :key="item.id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.Date }}</td>
                                <td>{{ item.weekday }}</td>
                                <td>{{ mapExpense(item.Tr_Type) }}</td>
                                <td>{{ item.Description }}</td>
                                <td>{{ item.Amount }}</td>
                            </tr>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
#BrandModal,
.modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
