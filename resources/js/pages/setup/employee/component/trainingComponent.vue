<script setup>
import { ref, onMounted, defineProps, getCurrentInstance } from "vue";
import { onClickOutside } from "@vueuse/core";
import { useStore } from "vuex";
import axios from "axios";
import Swal from "sweetalert2";
import api from '@/api';

const store = useStore();
const { isOpen, editStore, updateinfo, EID, trainId } = defineProps([
    "isOpen",
    "editStore",
    "updateinfo",
    "EID",
    "trainId",
]);

const target = ref(null);
const error = ref([]);
const scales = ref([]);

const form = ref({
    eid: EID,
    traningTitle: "",
    organizedBy: "",
    topicCovered: "",
    duration: "",
    year: "",
    remarks: "",
});

const instance = getCurrentInstance();

const getData = async () => {
    try {
        const responseScale = await api.get("/scale");
        scales.value = responseScale.data;
        if (editStore) {
            form.value = {
                traningTitle: editStore.Training_Title,
                organizedBy: editStore.Organized_By,
                topicCovered: editStore.Topic_Covered,
                duration: editStore.Duration,
                year: editStore.Year,
                remarks: editStore.Remarks,
            };
        }
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
    }
};

onMounted(() => {
    onClickOutside(target, () => instance.emit("modal-close")), getData();
});

const closeModal = () => {
    instance.emit("modal-close");
};

const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        if (typeof form.value[key] === "string") {
            form.value[key] = "";
        } else {
            form.value[key] = null; // or any other default value you prefer
        }
    });
};

const create = async () => {
    try {
        const response = await api.post("/training", form.value);
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
        // Handle the error, e.g., display an error message
    }
};

const update = async () => {
    try {
        const response = await api.put(`/training/${trainId}`, form.value);
        if (response.data.success) {
            instance.emit("modal-close");
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been Updated",
                showConfirmButton: false,
                timer: 1500,
            });
            // resetForm();
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
        class="modal-mask col-md-6 grid-margin stretch-card"
        ref="target"
    >
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Training Information</h4>
                <form class="forms-sample" @submit.prevent="submit">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >Training Title</label
                                >
                                <input
                                    type="text"
                                    class="form-control"
                                    id="exampleInputEmail1"
                                    v-model="form.traningTitle"
                                />
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >Organized By</label
                                >
                                <input
                                    type="text"
                                    class="form-control"
                                    id="exampleInputEmail1"
                                    placeholder="Organization Name"
                                    v-model="form.organizedBy"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >Topic Covered</label
                                >
                                <textarea
                                    name=""
                                    id=""
                                    cols="30"
                                    rows="3"
                                    class="form-control"
                                    v-model="form.topicCovered"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >Duration</label
                                >
                                <input
                                    type="text"
                                    class="form-control"
                                    id="exampleInputEmail1"
                                    v-model="form.duration"
                                />
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1"
                                    >Year</label
                                >
                                <input
                                    type="text"
                                    class="form-control"
                                    id="exampleInputEmail1"
                                    v-model="form.year"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1">Remarks</label>
                                <textarea
                                    name=""
                                    id=""
                                    cols="30"
                                    rows="3"
                                    class="form-control"
                                    placeholder=""
                                    v-model="form.remarks"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="custom-btn btn-13 m-3 p-2">
                        <i class="fa-solid fa-check"></i> | {{ updateinfo }}
                    </button>
                    <button
                        type="reset"
                        class="custom-btn btn-12 m-3 p-2"
                        @click.stop="closeModal"
                    >
                        <i class="fa-solid fa-x"></i> | Cancel
                    </button>
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
