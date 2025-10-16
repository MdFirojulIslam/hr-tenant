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
    from_date: "",
    to_date: "",
    leave_name: "", // will hold leave_type_id
    status: "", // "Pending" / "Approved" / "Rejected"
    duration: "",
    purpose: "",
});

watch(
    () => editStore,
    (item) => {
        if (item) {
            form.value.Full_Name = item.Full_Name || "";
            form.value.from_date = item.from_date || "";
            form.value.to_date = item.to_date || "";
            form.value.leave_type = item.leave_type_id || ""; // maps to dropdown value
            form.value.status = item.status || ""; // actual leave status
            form.value.duration = item.daysBetween || "";
            form.value.purpose = item.purpose || "";
        }
    },
    { immediate: true }
);

const computeDuration = async () => {
    if (form.value.from_date && form.value.to_date) {
        const from = new Date(form.value.from_date);
        const to = new Date(form.value.to_date);
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
                                <label for="employee_name">Employee Name</label>
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
                                <label for="leave_type">Leave Type</label>
                                <select
                                    v-model="form.leave_type"
                                    class="form-control"
                                >
                                    <option value="">All Type</option>
                                    <option value="1">Casual</option>
                                    <option value="2">Sick</option>
                                    <option value="3">Earned</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 mb-2">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >Leave Status</label
                                >
                                <select
                                    class="form-control"
                                    v-model="form.status"
                                >
                                    <option value="Approved (SV)">
                                        Approved (SV)
                                    </option>
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
                                    v-model="form.purpose"
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
                                    v-model="form.from_date"
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
                                    v-model="form.to_date"
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
