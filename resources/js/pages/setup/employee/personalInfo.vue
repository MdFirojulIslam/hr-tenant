<script setup>
import { ref, onMounted, watch, defineAsyncComponent } from "vue";
import { useStore } from "vuex";
import { useRoute } from "vue-router";
import Swal from "sweetalert2";
import api from '@/api';

const route = useRoute();
const store = useStore();
const empId = parseInt(route.params.id);
const empEdit = ref([]);
const eid = empId || store.state.employeeId;

const childData = ref(null);
const children = ref([]);
const childId = ref();
const heading = ref(null);
const childModel = ref(false);

const childOpened = (title) => {
    heading.value = title;
    childModel.value = true;
};

const childClose = () => {
    childModel.value = false;
    getData();
};

const childComponent = defineAsyncComponent(() =>
    import("../employee/component/childComponent.vue")
);

const editChild = async (id) => {
    const Data = children.value.find((child) => child.id === id);
    if (Data) {
        childData.value = Data; 
        childId.value = id;
        childOpened("Update");
    }
};

const nominee = ref({
    eid: empId || store.state.employeeId,
    nomineeName: "",
    dob: "",
    contactNo: "",
    email: "",
    nid: "",
    share: "",
    presentAddress: "",
});

const getData = async () => {
    try {
        const [
            responseChild,
            responseNominee,
        ] = await axios.all([
            api.get(`/child/${empId}/edit`),
            api.get(`/nominee/${empId}/edit`),
        ]);
        empEdit.value = responseNominee.data;
        children.value = responseChild.data;
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

watch(empEdit, (newEmpData) => {
    if (newEmpData) {
        newEmpData.Nominee_Name
            ? (nominee.value.nomineeName = newEmpData.Nominee_Name)
            : (nominee.value.nomineeName = "");
        newEmpData.DOB
            ? (nominee.value.dob = newEmpData.DOB)
            : (nominee.value.dob = "");
        newEmpData.Contact_No
            ? (nominee.value.contactNo = newEmpData.Contact_No)
            : (nominee.value.contactNo = "");
        newEmpData.Email
            ? (nominee.value.email = newEmpData.Email)
            : (nominee.value.email = "");
        newEmpData.NID
            ? (nominee.value.nid = newEmpData.NID)
            : (nominee.value.nid = "");
        newEmpData.Share
            ? (nominee.value.share = newEmpData.Share)
            : (nominee.value.share = "");
        newEmpData.presentAddress
            ? (nominee.value.presentAddress = newEmpData.Personal_Address)
            : (nominee.value.presentAddress = "");
    }
});

const error = ref([]);


const submitForm = async () => {
    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to submit this form?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            const response = await api.post("/nominee", nominee.value);
            if (response.data.success) {
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Information has been saved",
                    showConfirmButton: false,
                    timer: 1500,
                });
            }
        }
    } catch (err) {
        error.value = err.response.data.errors;
    }
};

const update = async () => {
    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to update the information?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            const response = await api.put(`/nominee/${empId}`, nominee.value);
            if (response.data.success) {
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Information has been Updated",
                    showConfirmButton: false,
                    timer: 1500,
                });
            }
        }
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

const submit = () => {
    if (empEdit.value.message == "Empty") {
        submitForm();
    } else if (empId) {
        update();
    } else {
        submitForm();
    }
};

const submitHandler = async () => {
    await getData();
};

onMounted(() => getData());
</script>

<template>
    <component v-if="childModel" :is="childComponent" :isOpen="childModel" :editStore="childData" :updateinfo="heading"
        :EID="eid" :Id="childId" @modal-close="childClose" @submit="submitHandler" name="first-modal" />

    <div class="card mb-3">
        <div class="card-body">
            <div class="mt-5">
                <h2 class="fs-4">Nominee Information</h2>
                <form @submit.prevent="submit">
                    <div class="row mb-3">
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <label for="exampleInputEmail1" class="">Nominee Name*
                                <span class="text-danger">{{
                                    error["nominee.nomineeName"]
                                        ? error["nominee.nomineeName"][0]
                                        : ""
                                }}</span></label>
                            <input type="text" class="form-control" id="disabledTextInput" aria-describedby="emailHelp"
                                v-model="nominee.nomineeName" />
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <label for="exampleInputEmail1" class="">Date of Birth*
                                <span class="text-danger">{{
                                    error["nominee.dob"]
                                        ? error["nominee.dob"][0]
                                        : ""
                                }}</span></label>
                            <input type="date" class="form-control" id="disabledTextInput" aria-describedby="emailHelp"
                                v-model="nominee.dob" />
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <label for="exampleInputEmail1" class="">Contact No*
                                <span class="text-danger">{{
                                    error["nominee.contactNo"]
                                        ? error["nominee.contactNo"][0]
                                        : ""
                                }}</span>
                            </label>
                            <input type="text" class="form-control" id="disabledTextInput" aria-describedby="emailHelp"
                                v-model="nominee.contactNo" />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <label for="exampleInputEmail1" class="">Email Id</label>
                            <input type="text" class="form-control" id="disabledTextInput" aria-describedby="emailHelp"
                                v-model="nominee.email" />
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <label for="exampleInputEmail1" class="">National Id</label>
                            <input type="text" class="form-control" id="disabledTextInput" aria-describedby="emailHelp"
                                v-model="nominee.nid" />
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <label for="exampleInputEmail1" class="">Share %</label>
                            <input type="text" class="form-control" id="disabledTextInput" aria-describedby="emailHelp"
                                v-model="nominee.share" />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <label for="exampleInputEmail1" class="">Present Address</label>
                            <textarea class="form-control" id="exampleFormControlTextarea1" rows="2"
                                v-model="nominee.presentAddress"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="reset" class="custom-btn btn-12 m-3 p-2">
                            <i class="fa-solid fa-x"></i> | Reset
                        </button>
                        <button type="submit" class="custom-btn btn-13 m-3 p-2">
                            <i class="fa-solid fa-check"></i> | Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <hr class="border border-3 border border-dark" />

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-lg-11">
                        <h4 class="card-title m-2">Children Information</h4>
                    </div>
                    <div class="col-lg-1">
                        <button @click="childOpened('Save')" type="button" class="btn-plus">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name of the Child</th>
                                <th>Date of Birth</th>
                                <th>Nid/Birth Certificate</th>
                                <th>Email</th>
                                <th>Contact No</th>
                                <th>Operation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="child in children" :key="child.id">
                                <td>
                                    {{
                                        child.Child_Name ? child.Child_Name : ""
                                    }}
                                </td>
                                <td>
                                    {{ child.DOB ? child.DOB : "" }}
                                </td>
                                <td>
                                    {{ child.NID ? child.NID : "" }}
                                </td>
                                <td>
                                    {{ child.Email ? child.Email : "" }}
                                </td>
                                <td>
                                    {{
                                        child.Contact_No ? child.Contact_No : ""
                                    }}
                                </td>
                                <td>
                                    <button class="custom-btn btn-15" @click="editChild(child.id)">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Input fields */
.form-control {
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(38, 143, 255, 0.25);
}

/* Select fields */
.form-control {
    padding-right: 2.5rem;
    /* Extra space for the dropdown arrow */
}

/* Dropdown arrow disable */
.form-control::-ms-expand {
    display: none;
}

/* Custom select styles */
.form-control {
    background: #f9f9f9;
    background-size: 1rem 1rem;
}

/* File upload styling */
.form-control[type="file"] {
    border: 1px solid #ddd;
    padding: 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    background-color: #f8f9fa;
}

/* File upload hover effect */
.form-control[type="file"]:hover {
    border-color: #007bff;
}

/* Textarea */
textarea.form-control {
    border-radius: 8px;
    border: 1px solid #ddd;
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
    resize: vertical;
}
</style>
