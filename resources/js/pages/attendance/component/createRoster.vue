<script setup>
import { ref, reactive, onMounted, watch } from "vue";
import Swal from "sweetalert2";
import api from "@/api";
import { useStore } from "vuex";
import { useRouter, useRoute } from "vue-router";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

// ----------------------------
const store = useStore();
const user = store.getters.getUser;

const router = useRouter();
const route = useRoute();

// ----------------------------
const department = ref([]);
const employee = ref([]);
const selectedEmployee = ref(null);
const emp_img = ref(null);

const isEdit = ref(false);
const rosterId = ref(null);

// ----------------------------
// FORM WITH 7 DAYS
const form = reactive({
    department: "",
    eid: "",
    effective_from: "",
    effective_to: "",
    roster_days: [
        {
            day_name: "Saturday",
            time_in: "",
            time_out: "",
            work_from: "office",
            is_weekend: false,
        },
        {
            day_name: "Sunday",
            time_in: "",
            time_out: "",
            work_from: "office",
            is_weekend: false,
        },
        {
            day_name: "Monday",
            time_in: "",
            time_out: "",
            work_from: "office",
            is_weekend: false,
        },
        {
            day_name: "Tuesday",
            time_in: "",
            time_out: "",
            work_from: "office",
            is_weekend: false,
        },
        {
            day_name: "Wednesday",
            time_in: "",
            time_out: "",
            work_from: "office",
            is_weekend: false,
        },
        {
            day_name: "Thursday",
            time_in: "",
            time_out: "",
            work_from: "office",
            is_weekend: false,
        },
        {
            day_name: "Friday",
            time_in: "",
            time_out: "",
            work_from: "office",
            is_weekend: false,
        },
    ],
});

// ----------------------------
// FETCH DEPARTMENT
const getData = async () => {
    const res = await api.get(`/department-list/${user.id}`);
    department.value = res.data;
};

// ----------------------------
// FETCH EMPLOYEE
const getEmployee = async (deptID) => {
    if (!deptID) return;
    const res = await api.get(`/emp/${deptID}`);
    employee.value = res.data;
};

// ----------------------------
// FETCH IMAGE
const getEmployeeImg = async (eid) => {
    const res = await api.get(`/empimg/${eid}`);
    emp_img.value = res.data?.img_url;
};

// ----------------------------
// SET WEEKEND HELPER
const setWeekend = (daysList) => {
    form.roster_days.forEach((day) => {
        day.is_weekend = daysList.includes(day.day_name);
    });
};

// ----------------------------
// FETCH EMPLOYEE WEEKEND (OPTIONAL API)
const getEmployeeWeekend = async (eid) => {
    try {
        const res = await api.get(`/employee-weekend/${eid}`);
        const weekendDays = res.data; // ["Friday", "Saturday"]
        setWeekend(weekendDays);
    } catch (e) {
        console.log("No weekend config found");
    }
};

// ----------------------------
// EDIT LOAD
const getRosterById = async (id) => {
    const res = await api.get(`/roster-setup/${id}`);
    const data = res.data.data; // ✅ FIX

    if (!data.length) return;

    // First row for common info
    const first = data[0];

    form.department = first.department_id;
    await getEmployee(first.department_id);

    form.eid = first.employee_id;
    form.effective_from = first.effective_from;
    form.effective_to = first.effective_to;

    selectedEmployee.value = employee.value.find(
        (emp) => emp.id == first.employee_id,
    );

    getEmployeeImg(first.employee_id);

    // ----------------------------
    // SET 7 DAYS DATA PROPERLY
    // ----------------------------
    form.roster_days = form.roster_days.map((day) => {
        const match = data.find((d) => d.day_name === day.day_name);

        if (match) {
            return {
                day_name: match.day_name,
                time_in: match.time_in ?? "",
                time_out: match.time_out ?? "",
                work_from: match.work_from ?? "office",
                is_weekend: match.weekend == 1,
            };
        }

        return day;
    });
};

// ----------------------------
// SELECT EMPLOYEE
const onEmployeeSelect = (emp) => {
    if (!emp) return;
    form.eid = emp.id;
    getEmployeeImg(emp.id);

    // optional dynamic weekend
    getEmployeeWeekend(emp.id);
};

// ----------------------------
// AUTO CLEAR TIME IF WEEKEND
watch(
    () => form.roster_days,
    (days) => {
        days.forEach((day) => {
            if (day.is_weekend) {
                day.time_in = "";
                day.time_out = "";
            }
        });
    },
    { deep: true },
);

// ----------------------------
// SUBMIT
const submit = async () => {
    try {
        let res;

        if (isEdit.value) {
            res = await api.put(`/roster-setup/${rosterId.value}`, form);
        } else {
            res = await api.post(`/roster-setup`, form);
        }

        if (res.data.success) {
            Swal.fire("Success", res.data.message, "success");
            router.push("/roster-setup");
        }
    } catch (err) {
        console.error(err);
        Swal.fire("Error", "Something went wrong!", "error");
    }
};

// ----------------------------
onMounted(async () => {
    await getData();

    if (route.query.edit) {
        isEdit.value = true;
        rosterId.value = route.query.id;
        await getRosterById(rosterId.value);
    }
});
</script>

<template>
    <div class="border rounded container-fluid d-flex flex-column p-0 m-0">
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h4 class="header-design">
                {{ isEdit ? "Update Roster" : "Roster Setup Form" }}
            </h4>
            <button class="btn btn-primary" @click="router.back()">Back</button>
        </div>

        <!-- EMPLOYEE SECTION -->
        <div class="mb-3 p-3">
            <div class="row mb-2">
                <div class="row col-md-4">
                    <div class="col-md-12">
                        <label class="form-label">Select Department</label>
                        <select
                            class="form-control field-height field-background-colour"
                            :class="{ 'placeholder-text': !form.department }"
                            v-model="form.department"
                            @change="getEmployee(form.department)"
                        >
                            <option value="">Select Department</option>
                            <option
                                v-for="d in department"
                                :key="d.department_id"
                                :value="d.department_id"
                            >
                                {{ d.department_name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-12 mt-3">
                        <label class="form-label">Select Employee</label>
                        <Multiselect
                            v-model="selectedEmployee"
                            :options="employee"
                            label="Full_Name"
                            track-by="id"
                            @update:modelValue="onEmployeeSelect"
                            placeholder="Select employee"
                            class="multiselect-design"
                        />
                    </div>
                </div>

                <div class="col-md-4 text-center mt-4">
                    <img
                        :src="emp_img || '/default-avatar.png'"
                        height="100"
                        width="100"
                        class="border"
                    />
                </div>

                <div class="row col-md-4">
                    <div class="col-md-12">
                        <label class="form-label">Effective From</label>
                        <input
                            type="date"
                            v-model="form.effective_from"
                            class="form-control field-height field-background-colour"
                            :class="{
                                'placeholder-text': !form.effective_from,
                            }"
                        />
                    </div>

                    <div class="col-md-12 mt-3">
                        <label class="form-label">Effective To</label>
                        <input
                            type="date"
                            v-model="form.effective_to"
                            class="form-control field-height field-background-colour"
                            :class="{ 'placeholder-text': !form.effective_to }"
                        />
                    </div>
                </div>
                <div class="col-md-12 mt-5">
                    <div class="row">
                        <div
                            v-for="(day, index) in form.roster_days"
                            :key="index"
                            class="col-md-6 mt-4"
                        >
                            <div
                                class="p-2 border rounded"
                                :class="{ 'roster-bg': day.is_weekend }"
                            >
                                <!-- INNER ROW -->
                                <div class="row align-items-center">
                                    <!-- DAY -->
                                    <div class="col-md-2">
                                        <input
                                            type="text"
                                            v-model="day.day_name"
                                            class="form-control text-center day-name"
                                            readonly
                                        />
                                    </div>

                                    <!-- TIME IN -->
                                    <div class="col-md-3">
                                        <input
                                            type="time"
                                            v-model="day.time_in"
                                            class="form-control"
                                            :disabled="day.is_weekend"
                                        />
                                    </div>

                                    <!-- TIME OUT -->
                                    <div class="col-md-3">
                                        <input
                                            type="time"
                                            v-model="day.time_out"
                                            class="form-control"
                                            :disabled="day.is_weekend"
                                        />
                                    </div>

                                    <!-- WORK FROM -->
                                    <div class="col-md-2">
                                        <select
                                            v-model="day.work_from"
                                            class="form-select"
                                            :disabled="day.is_weekend"
                                        >
                                            <option value="office">
                                                Office
                                            </option>
                                            <option value="home">Home</option>
                                        </select>
                                    </div>

                                    <!-- WEEKEND -->
                                    <div
                                        class="col-md-2 d-flex align-items-center justify-content-center"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="day.is_weekend"
                                            class="me-2"
                                        />
                                        <span>Weekend</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7 DAYS ROSTER -->
        <!-- <div class="card p-3 setwidth">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>DAY</th>
                        <th>TIME IN</th>
                        <th>TIME OUT</th>
                        <th>WORK FROM</th>


                        <th>WEEKEND</th>
                    </tr>
                </thead>


                <tbody>
                    <tr v-for="(day, index) in form.roster_days" :key="index" :class="{ 'roster-bg': day.is_weekend }">
                        <td>
                            <input type="text" v-model="day.day_name" class="form-control text-center" readonly />
                        </td>


                        <td>
                            <input type="time" v-model="day.time_in" class="form-control" :disabled="day.is_weekend" />
                        </td>
                        <td>
                            <input type="time" v-model="day.time_out" class="form-control" :disabled="day.is_weekend" />
                        </td>


                        <td>
                            <select v-model="day.work_from" class="form-select" :disabled="day.is_weekend">
                                <option value="office">Office</option>
                                <option value="home">Home</option>
                            </select>
                        </td>
                        <td>
                            <input type="checkbox" v-model="day.is_weekend" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div> -->

        <!-- BUTTON -->
        <div class="text-end mb-3 me-3">
            <button class="btn btn-success" @click="submit">
                {{ isEdit ? "Update" : "Submit" }}
            </button>
            <button class="btn btn-secondary ms-2" @click="router.back()">
                Cancel
            </button>
        </div>
    </div>
</template>

<style>
.form-label {
    font-weight: 600;
    letter-spacing: 0.4px;
}

.field-hover:hover {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.field-height {
    height: 35px;
}

.placeholder-text {
    color: #999;
    font-size: 14px;
}

.day-name {
    background: #bdc2c7;
    text-transform: uppercase;
}

.multiselect-design .multiselect__placeholder {
    color: #999;
    font-size: 14px;
    margin-bottom: 0px;
    align-items: center;
}

.multiselect-design .multiselect__tags {
    min-height: 35px !important;
    height: 35px;
    padding: 0 8px;
    border-radius: 5px;
    border: 0.8px solid #b3a9a963;
    display: flex;
    align-items: center;
    font-size: 14px;
}

/* Input inside the multiselect */
.multiselect-design .multiselect__input,
.multiselect-design .multiselect__single {
    height: 100%;
    line-height: 35px;
    padding: 0;
    margin: 0;
    background: transparent;
    border: 0;
    flex: 1;
}

.multiselect-design .multiselect__select {
    height: 35px;
}

.field-background-colour {
    border: 0.8px solid #b3a9a963;
}

.header-design {
    letter-spacing: 1px;
    padding-top: 5px;
    font-weight: 700;
}
</style>
