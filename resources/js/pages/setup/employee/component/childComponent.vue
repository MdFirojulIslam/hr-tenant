<script setup>
import { ref, defineProps, getCurrentInstance, watch, onMounted } from "vue";
import Swal from "sweetalert2";
import api from '@/api';

const { isOpen, editStore, updateinfo, EID, Id } = defineProps([
    "isOpen",
    "editStore",
    "updateinfo",
    "EID",
    "Id",
]);
console.log(editStore);

const form = ref({
    eid: EID,
    childName: null,
    nid: "",
    email: "",
    contactNo: "",
    dob: "",
});


const instance = getCurrentInstance();

const closeModal = () => {
    instance.emit("modal-close");
};

watch(
    () => editStore,
    (item) => {
        if (item) {
            form.value.childName = item.Child_Name || "";
            form.value.nid = item.NID || "";
            form.value.email = item.Email || "";
            form.value.contactNo = item.Contact_No || "";
            form.value.dob = item.DOB || "";
        }
    },
    { immediate: true }
);

const create = async () => {
    try {
        const response = await api.post("/child", form.value);
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
        const response = await api.put(`/child/${Id}`, form.value);
        if (response.data.success) {
            instance.emit("modal-close");
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been Updated",
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
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
    <div v-if="isOpen" class="modal-mask col-lg-6 col-md-6 grid-margin stretch-card" ref="target">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Children Information</h4>
                <form class="forms-sample" @submit.prevent="submit">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1">Child Name*</label>
                                <input type="text" class="form-control" v-model="form.childName" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1">NID/Birth Certificate*</label>
                                <input type="text" class="form-control" v-model="form.nid" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1">Date of Birth</label>
                                <input type="date" class="form-control" v-model="form.dob" />
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1">Contact No</label>
                                <input type="text" class="form-control" v-model="form.contactNo" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label for="exampleInputEmail1">Email Id</label>
                                <input type="email" class="form-control" v-model="form.email" />
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="custom-btn btn-13 m-3 p-2">
                        <i class="fa-solid fa-check"></i> | {{ updateinfo }}
                    </button>
                    <button type="reset" class="custom-btn btn-12 m-3 p-2" @click.stop="closeModal">
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
