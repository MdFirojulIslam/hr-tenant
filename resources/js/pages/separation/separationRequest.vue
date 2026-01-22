<script setup>
import { onMounted, ref, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, "0");
    const month = date.toLocaleString("en-US", { month: "short" });
    const year = date.getFullYear();
    return `${day} ${month}, ${year}`;
};

const form = ref({
    date_of_separation: "",
    reason: "",
    cause: "",
});

const separation_info = ref({});

const reasons = ["Resignation", "Retirement", "Termination"];

const getData = async () => {
    try {
        const response = await api.get(`/separation/${user.id}`);
        separation_info.value = response.data[0];
    } catch (error) {
        console.error("Error fetching data:", error);
    }
};

const submit = async () => {
    try {
        const response = await api.post("/separation-request", form.value);
        if (response.data.success) {
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Application has been saved",
                showConfirmButton: false,
                timer: 1500,
            });
            // await api.get(`/separation-req-mail/${user.id}`);
            resetForm();
        }
    } catch (error) {
        console.error("Error submitting separation request:", error);
        alert("Failed to submit separation request.");
    }
};

const resetForm = () => {
    form.value.date_of_separation = "";
    form.value.reason = "";
    form.value.cause = "";
};

const serviceDuration = computed(() => {
    const doj = separation_info.value.date_of_joining;
    const sepDate = form.value.date_of_separation;

    if (!doj || !sepDate) return "";

    const start = new Date(doj);
    const end = new Date(sepDate);

    let years = end.getFullYear() - start.getFullYear();
    let months = end.getMonth() - start.getMonth();
    let days = end.getDate() - start.getDate();

    if (days < 0) {
        months--;
        days += new Date(end.getFullYear(), end.getMonth(), 0).getDate();
    }

    if (months < 0) {
        years--;
        months += 12;
    }

    let result = "";
    if (years > 0) result += `${years} years `;
    result += `${months} months ${days} days`;

    return result;
});

onMounted(() => {
    getData();
});
</script>

<template>
    <div
        class="d-flex justify-content-center align-items-center"
        style="height: 100vh"
    >
        <div class="card" style="height: 500px; width: 500px">
            <h5 class="card-title justify-content-center d-flex mt-3 heading">
                Separation Request
            </h5>

            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="d-flex flex-row mt-5 gap-3">
                        <div class="d-flex flex-column col-lg-6">
                            <label class="form-label font-size mb-0"
                                >Date of Hire</label
                            >
                            <input
                                class="form-group field-hover"
                                :value="
                                    formatDate(separation_info.date_of_joining)
                                "
                                readonly
                            />
                        </div>

                        <div class="d-flex flex-column col-lg-6 pe-3">
                            <label class="form-label font-size mb-0"
                                >Date of Separation*</label
                            >
                            <input
                                class="form-group field-hover"
                                v-model="form.date_of_separation"
                                type="date"
                            />
                        </div>
                    </div>

                    <div class="my-3">
                        <span class="font-size">
                            Service Duration
                            <b class="font-size">{{ serviceDuration }}</b>
                        </span>
                    </div>

                    <div class="my-3">
                        <label class="form-label font-size mb-0"
                            >Reason for Separation* :</label
                        >
                        <div class="d-flex gap-3 ms-2">
                            <div
                                class="form-check"
                                v-for="reason in reasons"
                                :key="reason"
                            >
                                <input
                                    class="form-check-input font-size"
                                    type="radio"
                                    :value="reason"
                                    v-model="form.reason"
                                />
                                <label class="font-size" :for="reason">{{
                                    reason
                                }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column my-3">
                        <label class="form-label font-size mb-0"
                            >Causes (If Applicable)</label
                        >
                        <textarea
                            v-model="form.cause"
                            class="form-group field-hover"
                            cols="7"
                            rows="7"
                        ></textarea>
                    </div>

                    <div class="text-end mt-5">
                        <button class="submit-btn font-size">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style>
.heading {
    font-weight: 600;
    font-size: 24px;
}
.submit-btn {
    border: 1px solid #0d2b14;
    border-radius: 10%;
    padding: 5px 10px;
}
.submit-btn:hover {
    background-color: #217533;
}
.font-size {
    font-size: 16px;
}
.field-hover:hover {
    border: 1px solid #097a22;
}
</style>
