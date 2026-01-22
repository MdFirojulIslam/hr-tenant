<script setup>
import { onMounted, ref, computed } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;

// const form = ref({
//     date_of_separation: "",
//     reason: "",
//     cause: "",
// });

const separation_info = ref({});

const getData = async () => {
    try {
        const response = await api.get(`/separation-info/${user.id}`);
        separation_info.value = response.data;
        console.log("Separation Info:", separation_info.value);
    } catch (error) {
        console.error("Error fetching data:", error);
    }
};

// const submit = async () => {
//     try {
//         const response = await api.post("/separation-request", form.value);
//         if (response.data.success) {
//             Swal.fire({
//                 position: "middle",
//                 icon: "success",
//                 title: "Application has been saved",
//                 showConfirmButton: false,
//                 timer: 1500,
//             });
//             // await api.get(`/separation-req-mail/${user.id}`);
//             resetForm();
//         }
//     } catch (error) {
//         console.error("Error submitting separation request:", error);
//         alert("Failed to submit separation request.");
//     }
// };

// const resetForm = () => {
//     form.value.date_of_separation = "";
//     form.value.reason = "";
//     form.value.cause = "";
// };

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, "0");
    const month = date.toLocaleString("en-US", { month: "short" });
    const year = date.getFullYear();
    return `${day} ${month}, ${year}`;
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
    <div class="container mt-5">
        <div class="row g-5 border position-relative pb-3">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card" style="height: 230px; width: 100%">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="m-3">
                                    <img src="" alt="" class="image-style" />
                                </div>
                                <div class="ms-4 d-flex">
                                    <div class="label">Date of Join</div>
                                    <span class="value"> : 25-10-2025</span>
                                </div>
                                <div class="ms-4 d-flex">
                                    <div class="label">Resign Date</div>
                                    <span class="value"> : 25-10-2025</span>
                                </div>
                                <div class="ms-4 d-flex">
                                    <div class="label">Service Duration</div>
                                    <span class="value">
                                        : 12 month 3 Days</span
                                    >
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div
                                    class="d-flex flex-column justify-content-center align-items-start h-100"
                                >
                                    <div class="mb-2 d-flex">
                                        <div class="label">Employee ID</div>
                                        <span class="value"> : ABNB-20145</span>
                                    </div>
                                    <div class="mb-2 d-flex">
                                        <div class="label">Employee Name</div>
                                        <span class="value">
                                            : Juan Dela Cruz</span
                                        >
                                    </div>
                                    <div class="mb-2 d-flex">
                                        <div class="label">Department</div>
                                        <span class="value"> : IT</span>
                                    </div>
                                    <div class="mb-2 d-flex">
                                        <div class="label">Designation</div>
                                        <span class="value">
                                            : Software Engineer</span
                                        >
                                    </div>
                                    <div class="mb-2 d-flex">
                                        <div class="label">Employee Status</div>
                                        <span class="value"> : Active</span>
                                    </div>
                                    <div class="mb-2 d-flex">
                                        <div class="label">Separation Type</div>
                                        <span class="value"> : Voluntary</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--
                        <div>
                            <label for=""
                                >Separation Reason :
                                <span>Personal Reasons</span></label
                            >
                        </div> -->
                    </div>
                </div>
            </div>

            <!-- <div class="col-lg-6 col-md-6 col-sm-12">
                    <div class="card" style="height: 230px; width: 100%">
                        <div class="card-body">
                            <div
                                class="d-flex align-items-center justify-content-center mt-3"
                            >
                                <h3>HR Approval</h3>
                            </div>

                            <div
                                class="position-absolute bottom-0 end-0 m-3 d-flex gap-2"
                            >
                                <button>Accept</button>
                                <button>Reject</button>
                            </div>
                        </div>
                    </div>
                </div> -->

            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card" style="height: 230px; width: 100%">
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-center align-items-center mt-2 mb-3"
                        >
                            <h3>IT Clearance</h3>
                        </div>
                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">Remove User Email</div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="radio"
                                    name="rehire"
                                    id="yes"
                                />
                                <label class="ms-1 me-3" for="yes">Yes</label>

                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no">No</label>
                            </span>
                        </div>

                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">Remove DMS Account</div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="radio"
                                    name="rehire"
                                    id="yes"
                                />
                                <label class="ms-1 me-3" for="yes">Yes</label>

                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no">No</label>
                            </span>
                        </div>

                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">User Email Backup</div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="radio"
                                    name="rehire"
                                    id="yes"
                                />
                                <label class="ms-1 me-3" for="yes">Yes</label>

                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no">No</label>
                            </span>
                        </div>

                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">Remove HRIS Account</div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="radio"
                                    name="rehire"
                                    id="yes"
                                />
                                <label class="ms-1 me-3" for="yes">Yes</label>

                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no">No</label>
                            </span>
                        </div>

                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">Laptop Name</div>

                            <span class="value d-flex align-items-center">
                                :

                                <input
                                    class="ms-3"
                                    type="radio"
                                    name="rehire"
                                    id="yes"
                                />
                                <label class="ms-1 me-3" for="yes">Yes</label>

                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no">No</label>
                                <span class="ms-3"> <b>[Lenevo]</b> </span>
                            </span>
                        </div>

                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">Other Accesories</div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="radio"
                                    name="rehire"
                                    id="yes"
                                />
                                <label class="ms-1 me-3" for="yes">Yes</label>

                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no">No</label>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card" style="height: 230px; width: 100%">
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-center align-items-center mt-2 mb-3"
                        >
                            <h3>Department Clearance</h3>
                        </div>
                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">Would you rehire</div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="radio"
                                    name="rehire"
                                    id="yes"
                                />
                                <label class="ms-1 me-3" for="yes">Yes</label>

                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no">No</label>
                            </span>
                        </div>

                        <div class="mb-2 d-flex">
                            <div class="dept-label">
                                Would you need replacement
                            </div>
                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="radio"
                                    name="rehire"
                                    id="yes"
                                />
                                <label class="ms-1 me-3" for="yes">Yes</label>

                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no">No</label>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card" style="height: 230px; width: 100%">
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-center align-items-center mt-2 mb-3"
                        >
                            <h3>HR and Payroll</h3>
                        </div>
                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">Total Amount</div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="text"
                                    name="rehire"
                                    id="yes"
                                />
                            </span>
                        </div>
                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">Any benefits</div>
                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="text"
                                    name="rehire"
                                    id="yes"
                                />
                            </span>
                        </div>

                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">Deduction Cause</div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="text"
                                    name="rehire"
                                    id="yes"
                                />
                            </span>
                        </div>

                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">
                                Total Amount of final payment
                            </div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="text"
                                    name="rehire"
                                    id="yes"
                                />
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card" style="height: 200px; width: 100%">
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-center align-items-center mt-2 mb-3"
                        >
                            <h3>Accounts</h3>
                        </div>
                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">Payment Date</div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="date"
                                    name="rehire"
                                    id="yes"
                                />
                            </span>
                        </div>
                        <div class="mb-2 d-flex align-items-center">
                            <div class="dept-label">
                                Final Payment Credited Via
                            </div>

                            <span class="value d-flex align-items-center">
                                :
                                <input
                                    class="ms-3"
                                    type="radio"
                                    name="rehire"
                                    id="yes"
                                />
                                <label class="ms-1 me-3" for="yes"
                                    >Bank Transfer</label
                                >

                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no"
                                    >Bank Deposit</label
                                >
                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no">Cash</label>
                                <input
                                    type="radio"
                                    name="rehire"
                                    id="no"
                                    class="ms-2"
                                />
                                <label class="ms-1" for="no">Bank Check</label>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-2 button-style">
            <button>Submit</button>
        </div>
    </div>
</template>

<style>
.image-style {
    height: 120px;
    width: 120px;
    background-color: gray;
    border-radius: 50%;
    object-fit: cover;
}
.label {
    width: 100px;
}
.value {
    flex: 1;
}
.dept-label {
    width: 170px;
}
</style>
