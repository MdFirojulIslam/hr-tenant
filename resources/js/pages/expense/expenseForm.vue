<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import api from "@/api";
import Multiselect from "vue-multiselect";
import Swal from "sweetalert2";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;

const route = useRoute();

const loading = ref(false);
const dateError = ref(false);
const townData = ref([]);
const lockPermission = ref();
const fileWarningtravel = ref();
const fileWarningallowance = ref();
const selectedTown = ref("");
const selectedDate = ref();
const maxDate = computed(() => new Date().toISOString().split("T")[0]);
const minDate = ref("");
// const minDate = computed(() => {
//     const today = new Date();
//     today.setDate(today.getDate() - 3);
//     return today.toISOString().split("T")[0];
// });

const travels = ref([
    {
        town_id: "",
        date: "",
        from: "",
        to: "",
        vehicle: "",
        attachment: "",
        amount: 0,
    },
]);

const addTravel = () => {
    travels.value.push({
        town_id: "",
        date: "",
        from: "",
        to: "",
        vehicle: "",
        attachment: "",
        amount: 0,
    });
};

const removeTravel = (index) => {
    travels.value.splice(index, 1);
};

const total_travel_amount = computed(() => {
    return travels.value.reduce(
        (acc, item) => acc + parseFloat(item.amount || 0),
        0,
    );
});

const handleFileTravel = (e, targetItem, key) => {
    const file = e.target.files[0];
    if (file.size > 512000) {
        fileWarningtravel.value = true;
        e.target.value = null;
    } else {
        fileWarningtravel.value = false;
        targetItem[key] = file;
    }
};

const allowance = ref([
    {
        town_id: "",
        date: "",
        particular: "",
        description: "",
        attachment: "",
        amount: 0,
    },
]);

const addAllowance = () => {
    allowance.value.push({
        town_id: "",
        date: "",
        particular: "",
        description: "",
        attachment: "",
        amount: 0,
    });
};

const removeAllowance = (index) => {
    allowance.value.splice(index, 1);
};

const total_allowance_amount = computed(() => {
    return allowance.value.reduce(
        (acc, item) => acc + parseFloat(item.amount || 0),
        0,
    );
});

const handleFileAllow = (e, targetItem, key) => {
    const file = e.target.files[0];
    if (file.size > 512000) {
        fileWarningallowance.value = true;
        e.target.value = null;
    } else {
        fileWarningallowance.value = false;
        targetItem[key] = file;
    }
};

const getData = async () => {
    const responsetown = await api.get(`/fetch-user-town/${user.id}`);
    townData.value = responsetown.data;

    const permission = await api.get(`/lock-permission/${user.id}`);
    lockPermission.value = permission.data;

    if (lockPermission.value.Date_Lock == 1) {
        const today = new Date();
        today.setDate(today.getDate() - 3);
        minDate.value = today.toISOString().split("T")[0];
    }
};

const formattedTownData = computed(() => {
    return townData.value.map((item) => ({
        id: item.town.id,
        Name: item.town.Name,
    }));
});

const submit = async () => {
    try {
        loading.value = true;
        dateError.value = false;

        if (!selectedDate.value) {
            dateError.value = true;
            loading.value = false;
            return;
        }

        const formData = new FormData();

        // Validate and append allowance data
        for (let index = 0; index < allowance.value.length; index++) {
            const item = allowance.value[index];

            if (item.amount > 0) {
                if (item.particular === "da" && item.amount > 200) {
                    Swal.fire({
                        icon: "error",
                        title: "Validation Error",
                        text: "Daily Allowance cannot be more than 200",
                    });
                    loading.value = false;
                    return; // This will now exit the entire `submit` function
                }

                formData.append(
                    `allowance[${index}][town_id]`,
                    item.town_id || selectedTown.value.id,
                );
                formData.append(
                    `allowance[${index}][date]`,
                    item.date || selectedDate.value,
                );
                formData.append(
                    `allowance[${index}][particular]`,
                    item.particular,
                );
                formData.append(
                    `allowance[${index}][description]`,
                    item.description,
                );
                formData.append(`allowance[${index}][amount]`, item.amount);
                if (item.attachment) {
                    formData.append(
                        `allowance[${index}][attachment]`,
                        item.attachment,
                    );
                }
            }
        }

        // Append travels data
        for (let index = 0; index < travels.value.length; index++) {
            const item = travels.value[index];

            if (item.amount > 0) {
                formData.append(
                    `travels[${index}][town_id]`,
                    item.town_id || selectedTown.value.id,
                );
                formData.append(
                    `travels[${index}][date]`,
                    item.date || selectedDate.value,
                );
                formData.append(`travels[${index}][from]`, item.from);
                formData.append(`travels[${index}][to]`, item.to);
                formData.append(`travels[${index}][vehicle]`, item.vehicle);
                formData.append(`travels[${index}][amount]`, item.amount);
                if (item.attachment) {
                    formData.append(
                        `travels[${index}][attachment]`,
                        item.attachment,
                    );
                }
            }
        }

        // Send the data to your backend API
        const response = await api.post("/tada-expense", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        if (response.data.success) {
            loading.value = false;
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been saved",
                showConfirmButton: false,
                timer: 1500,
            });
            window.location.reload();
        }
    } catch (err) {
        loading.value = false;
        Swal.fire({
            position: "middle",
            icon: "error",
            title: err.response.data.message,
            showConfirmButton: true,
        });
    }
};
//:min="minDate" :max="maxDate"
onMounted(() => getData());
</script>

<template>
    <div v-if="loading" class="overlay-loading">
        <div class="loader"></div>
    </div>
    <div class="container">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <h1 class="text-center mb-4">Employee Information</h1>
                    <div class="col-lg-6 text-center">
                        <h2>{{ user.Full_Name }}</h2>
                        <h5>{{ user.dept_name }}</h5>
                        <h5>{{ user.des_name }}</h5>
                    </div>
                    <div class="col-lg-6 text-center">
                        <img
                            :src="user.img_url"
                            alt=""
                            style="height: 80px; width: 80px"
                        />
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-center">
                <div class="col-lg-3 px-2">
                    <label
                        >Date<span v-if="dateError" class="text-danger">
                            You must select a date
                        </span></label
                    >
                    <input
                        type="date"
                        class="form-control py-2"
                        v-model="selectedDate"
                        @change="checkDate"
                        :min="minDate"
                        :max="maxDate"
                        required
                    />
                </div>
                <div class="col-lg-3 px-2">
                    <label>Town</label>
                    <multiselect
                        class="custom-multiselect"
                        v-model="selectedTown"
                        :options="formattedTownData"
                        :close-on-select="true"
                        :clear-on-select="false"
                        placeholder="Select Towns"
                        label="Name"
                        track-by="id"
                    >
                    </multiselect>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <h1 class="text-center mb-3">Other Allowance</h1>
                <div v-for="(item, index) in allowance" :key="index">
                    <div class="card mb-3">
                        <div class="card-body row mb-3">
                            <div
                                class="col-lg-3 col-md-3 col-sm-6 d-flex flex-wrap"
                            >
                                <div class="col-2 mt-4">
                                    <button class="btn btn-primary">
                                        {{ index + 1 }}
                                    </button>
                                </div>
                                <div class="col-10">
                                    <label>Particulars</label>
                                    <select
                                        v-model="item.particular"
                                        class="form-control"
                                    >
                                        <option selected disabled>
                                            select
                                        </option>
                                        <option value="da">
                                            Daily Allowance
                                        </option>
                                        <option value="oe">
                                            Other Expense
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div
                                class="col-lg-2 col-md-2 col-sm-6"
                                v-if="item.particular == 'oe'"
                            >
                                <label>Description</label>
                                <select
                                    v-model="item.description"
                                    class="form-control"
                                >
                                    <option value="Hotel Rent">
                                        Hotel Rent
                                    </option>
                                    <option value="DFF Lunch">DFF Lunch</option>
                                    <option value="Destribution Lunch">
                                        Destribution Lunch
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6">
                                <label for="exampleInputEmail1" class=""
                                    >Attachment
                                    <span
                                        v-if="fileWarningallowance"
                                        class="text-danger"
                                    >
                                        File size exceeds 500 KB.
                                    </span></label
                                >
                                <input
                                    type="file"
                                    class="form-control"
                                    id="disabledTextInput"
                                    @change="
                                        (e) =>
                                            handleFileAllow(
                                                e,
                                                item,
                                                'attachment',
                                            )
                                    "
                                />
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-6">
                                <label>Amount</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="item.amount"
                                />
                            </div>
                            <div class="col-lg-1 col-md-3 col-sm-6">
                                <button
                                    class="btn btn-danger mx-2 mt-4"
                                    @click.prevent="removeAllowance(index)"
                                >
                                    X
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-9">
                        <div class="mb-4">
                            <button
                                class="btn btn-success mt-4"
                                @click="addAllowance"
                            >
                                +
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <label>Total Amount</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="0"
                            :value="total_allowance_amount"
                            readonly
                        />
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <h1 class="text-center mb-3">Travel Expense</h1>
                <div
                    class="row mb-3"
                    v-for="(item, index) in travels"
                    :key="index"
                >
                    <div class="card mb-3">
                        <div class="card-body row mb-3">
                            <div
                                class="col-lg-2 col-md-3 col-sm-6 d-flex flex-wrap"
                            >
                                <div class="col-2 mt-4">
                                    <button class="btn btn-primary">
                                        {{ index + 1 }}
                                    </button>
                                </div>
                                <div class="col-10">
                                    <label>From</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="item.from"
                                    />
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6">
                                <label>To</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="item.to"
                                />
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6">
                                <label>Vehicle</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="item.vehicle"
                                />
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6">
                                <label for="exampleInputEmail1" class=""
                                    >Attachment
                                    <span
                                        v-if="fileWarningtravel"
                                        class="text-danger"
                                    >
                                        File size exceeds 500 KB.
                                    </span></label
                                >
                                <input
                                    type="file"
                                    class="form-control"
                                    id="disabledTextInput"
                                    @change="
                                        (e) =>
                                            handleFileTravel(
                                                e,
                                                item,
                                                'attachment',
                                            )
                                    "
                                />
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6">
                                <label>Amount</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="item.amount"
                                />
                            </div>
                            <div class="col-lg-1 col-md-3 col-sm-6">
                                <button
                                    class="btn btn-danger mx-2 mt-4"
                                    @click.prevent="removeTravel(index)"
                                >
                                    X
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-9">
                        <div class="mb-4">
                            <button
                                class="btn btn-success mt-4"
                                @click="addTravel"
                            >
                                +
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <label>Total Amount</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="0"
                            :value="total_travel_amount"
                            readonly
                        />
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <button
                type="submit"
                class="btn btn-primary mb-4 mx-2"
                @click="submit"
            >
                Submit
            </button>
        </div>
    </div>
</template>
