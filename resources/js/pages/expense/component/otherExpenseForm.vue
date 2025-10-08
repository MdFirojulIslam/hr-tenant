<script setup>
import { ref, defineProps, getCurrentInstance, watch } from "vue";
import Swal from "sweetalert2";
import api from "@/api";

const { isOpen, allowanceData, allowId } = defineProps([
    "isOpen",
    "allowanceData",
    "allowId",
]);

const fileWarningallowance = ref("");

const form = ref({
    particular: "",
    description: "",
    attachment: "",
    amount: "",
});

const amountLimit = ref(false);

watch(
    () => allowanceData,
    (item) => {
        if (item) {
            form.value.particular = item.Tr_Type || "";
            form.value.description = item.Description || "";
            form.value.amount = item.Amount || "";
        }
    },
    { immediate: true }
);

const handleFileAllow = (e, targetItem, key) => {
    const file = e.target.files[0];
    if (file.size > 512000) {
        fileWarningallowance.value = true;
        e.target.value = null;
    } else {
        fileWarningallowance.value = false;
        form.value.attachment = file;
    }
};

const instance = getCurrentInstance();

const closeModal = () => {
    instance.emit("modal-close");
};

const update = async () => {
    try {
        const data = new FormData();

        if (form.value.particular  == 'da') {
            if (form.value.amount > 200) {
                amountLimit.value = true;
                return;
            }
        }

        data.append("particular", form.value.particular);
        data.append("description", form.value.description);
        data.append("amount", form.value.amount);
        if (form.value.attachment) {
            data.append("attachment", form.value.attachment);
        }

        const response = await api.post(`/other-expense-update/${allowId}`, data, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        if (response.data.success) {
            instance.emit("modal-close");
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been Updated",
                showConfirmButton: false,
                timer: 1500,
            });
        }
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

</script>

<template>
    <div v-if="isOpen" class="modal-mask col-lg-6 col-md-6 grid-margin stretch-card" ref="target">
        <div class="card">
            <div class="card-body">
                <span class="text-danger" v-if="amountLimit">Daily Allowance cannot be more than 200 BDT</span>
                <form class="forms-sample" @submit.prevent="update">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6 mb-2">
                            <div class="form-group">
                                <label>Particulars</label>
                                <select name="" id="" class="form-control" v-model="form.particular">
                                    <option value="da">Daily Allowance</option>
                                    <option value="oe">Other Expenses</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 mb-2">
                            <div class="form-group">
                                <label>Amount</label>
                                <input type="number" class="form-control" v-model="form.amount">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 mb-2">
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" class="form-control" v-model="form.description">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 mb-2">
                            <div class="form-group">
                                <label>Attachment
                                    <span v-if="fileWarningallowance" class="text-danger">
                                        File size exceeds 500 KB.
                                    </span></label>
                                <input type="file" class="form-control" id="disabledTextInput"
                                    @change="e => handleFileAllow(e, item, 'attachment')" />
                            </div>
                        </div>
                    </div>


                    <div class="d-flex justify-content-between">
                        <button type="reset" class="custom-btn btn-12 p-2" @click.stop="closeModal">
                            <i class="fa-solid fa-x"></i> | Cancel
                        </button>
                        <button type="submit" class="custom-btn btn-13 p-2">
                            <i class="fa-solid fa-check"></i> | Save
                        </button>
                    </div>
                </form>
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

.modal-container {
    width: 300px;
    padding: 20px 30px;
    background-color: #620d0d;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    background-color: blue;
}
</style>
