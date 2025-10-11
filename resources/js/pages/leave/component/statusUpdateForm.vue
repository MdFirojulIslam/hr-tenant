<script setup>
import { ref, defineProps, getCurrentInstance, watch } from "vue";
import Swal from "sweetalert2";
import api from "@/api";

const { isOpen, editStore, leave_type, stId } = defineProps([
    "isOpen",
    "editStore",
    "leave_type",
    "stId",
]);
console.log(editStore);

const form = ref({
    Full_Name: "",
    From_Date: "",
    To_Date: "",
    Leave_Type: "",
    Status: "",
    duration: "",
    Purpose: "",
});

watch(
    () => editStore,
    (item) => {
        if (item) {
            form.value.Full_Name = item.Full_Name || "";
            form.value.From_Date = item.From_Date || "";
            form.value.To_Date = item.To_Date || "";
            form.value.Leave_Type = item.leave_id || "";
            form.value.Status = item.Status || "";
            form.value.duration = item.daysBetween || "";
            form.value.Purpose = item.Purpose || "";
        }
    },
    { immediate: true }
);

const computeDuration = async () => {
    if (form.value.From_Date && form.value.To_Date) {
        const from = new Date(form.value.From_Date);
        const to = new Date(form.value.To_Date);
        const differenceInTime = to.getTime() - from.getTime();
        // Calculate the difference in days
        form.value.duration =
            Math.ceil(differenceInTime / (1000 * 3600 * 24)) + 1;
    } else {
        form.value.duration = null;
    }
};

const instance = getCurrentInstance();

const closeModal = () => {
    instance.emit("modal-close");
};

const update = async () => {
    try {
        const response = await api.put(`/leave/${stId}`, form.value);
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
        await api.get(`/leave-status-mail/${stId}`);
        window.location.reload();
    } catch (error) {
        console.error("Error updating store:", error);
    }
};
</script>

<template>
    <div
        v-if="isOpen"
        class="modal-mask col-lg-6 col-md-6 grid-margin stretch-card"
        ref="target"
    >
        <div class="card">
            <div class="card-body">
                <form class="forms-sample" @submit.prevent="update">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 mb-2">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >Employee Name</label
                                >
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.Full_Name"
                                    readonly
                                />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6 mb-2">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >Leave Type</label
                                >
                                <select
                                    name=""
                                    id=""
                                    class="form-control"
                                    v-model="form.Leave_Type"
                                >
                                    <option
                                        v-for="item in leave_type"
                                        :key="item.id"
                                        :value="item.id"
                                    >
                                        {{ item.Name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 mb-2">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >Leave Status</label
                                >
                                <select
                                    name=""
                                    id=""
                                    class="form-control"
                                    v-model="form.Status"
                                >
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 mb-2">
                            <div class="form-group">
                                <label for="exampleInputEmail1">Purpose</label>
                                <textarea
                                    type="text"
                                    class="form-control"
                                    v-model="form.Purpose"
                                    rows="2"
                                    readonly
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >From Date*</label
                                >
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="form.From_Date"
                                    @change="computeDuration"
                                />
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1">To Date*</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="form.To_Date"
                                    @change="computeDuration"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 mt-2">
                            <p>
                                <strong>No of Days: {{ form.duration }}</strong>
                            </p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="custom-btn btn-13 p-2">
                            <i class="fa-solid fa-check"></i> | Save
                        </button>
                        <button
                            type="reset"
                            class="custom-btn btn-12 p-2"
                            @click.stop="closeModal"
                        >
                            <i class="fa-solid fa-x"></i> | Cancel
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
