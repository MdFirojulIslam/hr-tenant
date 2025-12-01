<template>
    <!-- Modal Wrapper -->
    <div
        v-if="isOpen"
        class="modal d-block"
        tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5)"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Update Leave Status</h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <form @submit.prevent="updateLeave">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label>Employee Name</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.Full_Name"
                                    readonly
                                />
                            </div>

                            <div class="col-md-6">
                                <label>Leave Type</label>
                                <select
                                    v-model="form.leave_type"
                                    class="form-control"
                                >
                                    <option value="">Select Type</option>
                                    <option
                                        v-for="type in leave_type"
                                        :key="type.id"
                                        :value="type.id"
                                    >
                                        {{ type.leave_name }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label>From Date</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="form.from_date"
                                />
                            </div>

                            <div class="col-md-6">
                                <label>To Date</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="form.to_date"
                                />
                            </div>

                            <div class="col-md-6">
                                <label>Days</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.duration"
                                    readonly
                                />
                            </div>

                            <div class="col-md-6">
                                <label>Status</label>
                                <select
                                    v-model="form.status"
                                    class="form-control"
                                >
                                    <option value="Pending">Pending</option>
                                    <option value="Approved (SV)">
                                        Approved (SV)
                                    </option>
                                    <option value="Approved">Approved</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label>Purpose</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.purpose"
                                    rows="3"
                                    readonly
                                ></textarea>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="mt-4 text-end">
                            <button
                                type="button"
                                class="btn btn-secondary me-2"
                                @click="closeModal"
                            >
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-success">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import Swal from "sweetalert2";
import api from "@/api";

// Props & emits
const props = defineProps({
    isOpen: Boolean,
    editStore: Object,
    leave_type: Array,
    stId: [Number, String],
});
const emit = defineEmits(["modal-close", "submit"]);

// Reactive form
const form = ref({
    Full_Name: "",
    from_date: "",
    to_date: "",
    leave_type: "",
    duration: "",
    status: "",
    purpose: "",
});

// Watch for editStore updates
watch(
    () => props.editStore,
    (item) => {
        if (item) {
            form.value.Full_Name = item.Full_Name || "";
            form.value.from_date = item.from_date || "";
            form.value.to_date = item.to_date || "";
            form.value.leave_type = item.leave_type_id || "";
            form.value.status = item.status || "";
            form.value.duration = item.daysBetween || "";
            form.value.purpose = item.purpose || "";
        }
    },
    { immediate: true, deep: true }
);

// Close modal
const closeModal = () => {
    emit("modal-close");
};

// Update leave API
const updateLeave = async () => {
    try {
        const confirm = await Swal.fire({
            title: "Confirm Update",
            text: "Are you sure you want to update this leave?",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes, Update",
        });

        if (!confirm.isConfirmed) return;

        if (!props.stId) {
            Swal.fire("Error!", "Leave ID not found.", "error");
            return;
        }

        // ✅ Ask HR if they want to send approval mail
        const hrApproval = await Swal.fire({
            title: "Send HR Approval Mail?",
            text: "Do you want to send an email for HR approval?",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "Yes, Send Mail",
            cancelButtonText: "No, Skip",
        });

        // ✅ Update leave first
        const response = await api.put(`/leave/${props.stId}`, form.value);

        if (response) {
            // ✅ If HR chose "Yes", trigger the mail API
            if (hrApproval.isConfirmed) {
                await api.get(`/hr-approval/${props.stId}`);
                Swal.fire(
                    "Mail Sent!",
                    "HR approval mail has been sent.",
                    "success"
                );
            }
            Swal.fire("Success!", "Leave updated successfully.", "success");
            emit("submit");
            emit("modal-close");
        }
    } catch (error) {
        if (error.response) {
            Swal.fire(
                "Error!",
                error.response.data.message || "Failed to update leave.",
                "error"
            );
        } else if (error.request) {
            Swal.fire("Error!", "No response from server.", "error");
        } else {
            Swal.fire("Error!", error.message, "error");
        }
    }
};
</script>

<style scoped>
/* Backdrop overlay */
.modal {
    z-index: 1050;
}
.modal-backdrop {
    z-index: 1040;
}
</style>
