<script setup>
import { ref, defineProps, getCurrentInstance, watch } from "vue";
import Swal from "sweetalert2";
import api from "@/api";

const { isOpen, travelData, travelId } = defineProps([
    "isOpen",
    "travelData",
    "travelId",
]);

const fileWarningtravel = ref("");

const form = ref({
    from: "",
    to: "",
    vehicle: "",
    attachment: "",
    amount: 0,
});

watch(
    () => travelData,
    (item) => {
        if (item) {
            form.value.from = item.From || "";
            form.value.to = item.To || "";
            form.value.vehicle = item.Vehicle || "";
            form.value.amount = item.Amount || "";
        }
    },
    { immediate: true }
);

const handleFileAllow = (e, targetItem, key) => {
    const file = e.target.files[0];
    if (file.size > 512000) {
        fileWarningtravel.value = true;
        e.target.value = null;
    } else {
        fileWarningtravel.value = false;
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

        data.append("from", form.value.from);
        data.append("to", form.value.to);
        data.append("vehicle", form.value.vehicle);
        data.append("amount", form.value.amount);
        if (form.value.attachment) {
            data.append("attachment", form.value.attachment);
        }

        const response = await api.post(`/travel-update/${travelId}`, data, {
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
                <form class="forms-sample" @submit.prevent="update">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6 mb-2">
                            <div class="form-group">
                                <label>From</label>
                                <input type="text" class="form-control" v-model="form.from">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 mb-2">
                            <div class="form-group">
                                <label>To</label>
                                <input type="text" class="form-control" v-model="form.to">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 mb-2">
                            <div class="form-group">
                                <label>Vehicle</label>
                                <input type="text" class="form-control" v-model="form.vehicle">
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
                                <label>Attachment
                                    <span v-if="fileWarningtravel" class="text-danger">
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
