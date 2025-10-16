<script setup>
import { ref, defineProps, getCurrentInstance, watch } from "vue";
import Swal from "sweetalert2";
import api from "../../../api";

const { isOpen, editStore, updateinfo, EID, holiId } = defineProps([
    "isOpen",
    "editStore",
    "updateinfo",
    "EID",
    "holiId",
]);

const error = ref([]);

const form = ref({
    name: "",
    from_date: "",
    to_date: "",
    duration: "",
});

watch(
    () => editStore,
    (item) => {
        if (item) {
            form.value.name = item.holiday_name || "";
            form.value.from_date = item.from_date || "";
            form.value.to_date = item.to_date || "";
            form.value.duration = item.duration || "";
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

const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        if (typeof form.value[key] === "string") {
            form.value[key] = "";
        } else {
            form.value[key] = null; // or any other default value you prefer
        }
    });
};

const closeModal = () => {
    instance.emit("modal-close");
};

const create = async () => {
    try {
        const response = await api.post("/holiday", form.value);
        if (response.data.success) {
            instance.emit("modal-close");
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been Saved",
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
        }
    } catch (error) {
        console.error("Error creating store:", error);
    }
};

const update = async () => {
    try {
        const response = await api.put(`/holiday/${holiId}`, form.value);
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

const submit = () => {
    if (updateinfo === "Save") {
        create();
    } else if (updateinfo === "Update") {
        update();
    } else {
        console.error("Invalid updateinfo value:", updateinfo);
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
                <form class="forms-sample" @submit.prevent="submit">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >Name of the Holiday*</label
                                >
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.name"
                                />
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
                            <i class="fa-solid fa-check"></i> | {{ updateinfo }}
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
