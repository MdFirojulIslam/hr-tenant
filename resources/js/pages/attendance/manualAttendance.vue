<script setup>
import { ref } from "vue";
import api from "@/api";
import { useStore } from "vuex";

import dateBetween from "@/pages/components/date_between/dateBetween.vue";
import BaseDept from "@/pages/components/input_field/Basedept.vue";
import BaseDeptEmp from "@/pages/components/input_field/BaseDeptEmp.vue";
import Image from "@/pages/components/image/Image.vue";
import Label from "@/pages/components/label/Label.vue";

import { VueDatePicker } from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

const store = useStore();
const user = store.getters.getUser;
const userID = user.id;

const dailyAttendance = ref(false);

const prepareSummary = async () => {
    const res = await api.get("/prepare-summary");
};

const dailyAttendanceSummary = () => {
    dailyAttendance.value = true;
};

const form = ref({
    employee_id: "",
    department_id: "",
    in_time: "",
    out_time: "",
    attendance_date: "",
    remarks: "",
});

const submit = async () => {
    const res = await api.post("/manual-attendance", form.value);
};
</script>

<template>
    <dateBetween
        v-if="dailyAttendance"
        :daily-attendance="dailyAttendance"
        @close="dailyAttendance = false"
    />
    <div class="border h-100 p-2 m-0">
        <div class="header d-flex gap-4 m-2">
            <div class="header-design">
                <h3>Manual Attendance</h3>
            </div>
            <div class="ms-auto button-div">
                <button @click="prepareSummary()" type="button" class="btn">
                    PREPARE SUMMARY
                </button>
            </div>
            <div class="button-div">
                <button
                    @click="dailyAttendanceSummary()"
                    type="button"
                    class="btn"
                >
                    Sync Daily Attendance
                </button>
            </div>
        </div>
        <div class="form-container">
            <form @submit.prevent="submit">
                <div class="card p-3">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="department-component">
                                <Label label="Select Department*" />
                                <BaseDept
                                    v-model:departmentID="form.department_id"
                                    :userID="userID"
                                    required
                                />
                            </div>
                            <div class="position-relative pt-3">
                                <Label label="Select Employee*" />
                                <BaseDeptEmp
                                    v-model:employeeID="form.employee_id"
                                    :deptID="form.department_id"
                                    :isEdit="false"
                                    required
                                />
                            </div>
                        </div>
                        <div
                            class="col-lg-6 d-flex justify-content-center align-items-center"
                        >
                            <div class="image">
                                <Image
                                    :employeeID="form.employee_id"
                                    width="130px"
                                    height="130px"
                                    circle
                                />
                            </div>
                        </div>
                        <div class="col-lg-6 pt-3">
                            <Label label="Select In Time*" />
                            <input
                                type="time"
                                v-model="form.in_time"
                                class="form-control"
                                required
                            />
                        </div>
                        <div class="col-lg-6 pt-3">
                            <Label label="Select Out Time*" />
                            <input
                                type="time"
                                v-model="form.out_time"
                                class="form-control"
                                required
                            />
                        </div>
                        <div class="col-lg-6 pt-3">
                            <Label label="Select Date*" />
                            <VueDatePicker
                                v-model="form.attendance_date"
                                required
                            />
                        </div>
                        <div class="col-lg-6 pt-3">
                            <Label label="Remarks" />
                            <input
                                type="text"
                                v-model="form.remarks"
                                class="form-control"
                            />
                        </div>
                    </div>
                    <div class="d-flex justify-content-end pt-3">
                        <button type="submit" class="btn button-div">
                            Submit
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.header-design {
    letter-spacing: 1px;
    font-weight: 600;
}
.button-div {
    border: 1px solid black;
    border-radius: 5px;
    background-color: rgb(3, 112, 45);
}

.btn {
    color: white;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.form-container {
    width: 100%;
    min-height: 70vh;

    display: flex;
    justify-content: center;
    align-items: center;
}

.form-container form {
    width: 800px;
    max-width: 100%;
}
</style>
