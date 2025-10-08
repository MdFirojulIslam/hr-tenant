<script setup>
import { ref, onMounted, defineAsyncComponent } from "vue";
import { useRoute } from "vue-router";
import axios from "axios";
import { useStore } from "vuex";
import api from '@/api';

const academicComponent = defineAsyncComponent(() =>
    import("../employee/component/academicComponent.vue")
);
const trainingComponent = defineAsyncComponent(() =>
    import("../employee/component/trainingComponent.vue")
);
const workComponent = defineAsyncComponent(() =>
    import("../employee/component/workComponent.vue")
);

const route = useRoute();
const store = useStore();

const isLoading = ref(true);
const error = ref(null);
const selectedStore = ref(null);
const heading = ref(null);
const eid = store.state.employeeId || parseInt(route.params.id);
const empId = parseInt(route.params.id);

const academics = ref([]);
const trainings = ref([]);
const works = ref([]);

const academicId = ref();
const trainingId = ref();
const workId = ref();

const academicModel = ref(false);
const trainingModel = ref(false);
const workModel = ref(false);

const academicOpened = (title) => {
    heading.value = title;
    academicModel.value = true;
};
const academicClose = () => {
    academicModel.value = false;
};

const trainingOpened = (title) => {
    heading.value = title;
    trainingModel.value = true;
};

const trainingClose = () => {
    trainingModel.value = false;
};

const workOpened = (title) => {
    heading.value = title;
    workModel.value = true;
};

const workClose = () => {
    workModel.value = false;
};

const getData = async () => {
    try {
        const [responseAcademic, responseTraining, responseWork] =
            await axios.all([
                api.get(`/academic/${eid}`),
                api.get(`/training/${eid}`),
                api.get(`/work/${eid}`),
            ]);

        academics.value = responseAcademic.data;
        trainings.value = responseTraining.data;
        works.value = responseWork.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

const editAcademmic = async (id) => {
    try {
        const response = await api.get(`/academic/${id}/edit`);
        selectedStore.value = response.data;
        academicId.value = id;
        academicOpened("Update");
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const editTraining = async (id) => {
    try {
        const response = await api.get(`/training/${id}/edit`);
        selectedStore.value = response.data;
        trainingId.value = id;
        trainingOpened("Update");
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const editWork = async (id) => {
    try {
        const response = await api.get(`/work/${id}/edit`);
        selectedStore.value = response.data;
        workId.value = id;
        workOpened("Update");
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const submitHandler = async () => {
    await getData();
};
onMounted(() => getData());
</script>

<template>
    <component
        v-if="academicModel"
        :is="academicComponent"
        :isOpen="academicModel"
        :editStore="selectedStore"
        :updateinfo="heading"
        :EID="eid"
        :acaId="academicId"
        @modal-close="academicClose"
        @submit="submitHandler"
        name="first-modal"
    />

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-lg-11">
                        <h4 class="card-title m-2">Academic Information</h4>
                    </div>
                    <div class="col-lg-1">
                        <button
                            @click="academicOpened('Save')"
                            type="button"
                            class="btn-plus"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Level of Education</th>
                                <th>Degree</th>
                                <th>Institute</th>
                                <th>Board</th>
                                <th>Major Group</th>
                                <th>Result</th>
                                <th>Years Of Passing</th>
                                <th>Acheivement</th>
                                <th>Remarks</th>
                                <th>Operation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="academic in academics"
                                :key="academic.id"
                            >
                                <td>
                                    {{
                                        academic.education.degree
                                            ? academic.education.degree.Name
                                            : "N/A"
                                    }}
                                </td>
                                <td>
                                    {{
                                        academic.education
                                            ? academic.education.Name
                                            : "N/A"
                                    }}
                                </td>
                                <td>{{ academic.Institute_Name }}</td>
                                <td>
                                    {{
                                        academic.board
                                            ? academic.board.Name
                                            : "N/A"
                                    }}
                                </td>
                                <td>{{ academic.Group }}</td>
                                <td>
                                    {{ academic.Result }}
                                    {{
                                        academic.scale
                                            ? academic.scale.Name
                                            : "N/A"
                                    }}
                                </td>
                                <td>{{ academic.Year_of_Passing }}</td>
                                <td>{{ academic.Acheivement }}</td>
                                <td>{{ academic.Remarks }}</td>
                                <td>
                                    <button
                                        class="custom-btn btn-15"
                                        @click="editAcademmic(academic.id)"
                                    >
                                        <i
                                            class="fa-regular fa-pen-to-square"
                                        ></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <component
        v-if="trainingModel"
        :is="trainingComponent"
        :isOpen="trainingModel"
        :editStore="selectedStore"
        :updateinfo="heading"
        :EID="eid"
        :trainId="trainingId"
        @modal-close="trainingClose"
        @submit="submitHandler"
        name="first-modal"
    />

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-lg-11">
                        <h4 class="card-title m-2">Training Information</h4>
                    </div>
                    <div class="col-lg-1">
                        <button
                            @click="trainingOpened('Save')"
                            type="button"
                            class="btn-plus"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Training Infomation</th>
                                <th>Organized By</th>
                                <th>Topic Covered</th>
                                <th>Duration</th>
                                <th>Year</th>
                                <th>Remarks</th>
                                <th>Operation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="training in trainings"
                                :key="training.id"
                            >
                                <td>{{ training.Training_Title }}</td>
                                <td>{{ training.Organized_By }}</td>
                                <td>{{ training.Topic_Covered }}</td>
                                <td>{{ training.Duration }}</td>
                                <td>{{ training.Year }}</td>
                                <td>{{ training.Remarks }}</td>
                                <td>
                                    <button
                                        class="custom-btn btn-15"
                                        @click="editTraining(training.id)"
                                    >
                                        <i
                                            class="fa-regular fa-pen-to-square"
                                        ></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <component
        v-if="workModel"
        :is="workComponent"
        :isOpen="workModel"
        :editStore="selectedStore"
        :updateinfo="heading"
        :EID="eid"
        :workId="workId"
        @modal-close="workClose"
        @submit="submitHandler"
        name="first-modal"
    />

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-lg-11">
                        <h4 class="card-title m-2">Work Experience</h4>
                    </div>
                    <div class="col-lg-1">
                        <button
                            @click="workOpened('Save')"
                            style="float: left"
                            type="button"
                            class="btn-plus"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>From Date</th>
                                <th>To Date</th>
                                <th>Duration</th>
                                <th>Last Salary</th>
                                <th>Operations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="work in works" :key="work.id">
                                <td>{{ work.Company_Name }}</td>
                                <td>{{ work.Department }}</td>
                                <td>{{ work.Designation }}</td>
                                <td>{{ work.From_Date }}</td>
                                <td>{{ work.To_Date }}</td>
                                <td>{{ work.Duration }}</td>
                                <td>{{ work.Last_Salary }}</td>
                                <td>
                                    <button
                                        class="custom-btn btn-15"
                                        @click="editWork(work.id)"
                                    >
                                        <i
                                            class="fa-regular fa-pen-to-square"
                                        ></i>
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

