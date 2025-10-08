<template>
    <div v-if="loading" class="overlay-loading">
        <div class="loader"></div>
    </div>
    <div>
        <div class="card">
            <div class="card-body text-center">
                <h1 class="text-center">Attendance</h1>
                <p>Name: {{ user.Full_Name }}</p>
                <p>Designation: {{ user.des_name }}</p>
                <button class="btn btn-dark mb-3" @click="startCamera()">Open Camera</button>
                <div v-if="camera" class="text-center video-wrapper">
                    <video ref="video" autoplay playsinline></video>
                    <canvas ref="canvas" style="display: none"></canvas>
                    <div class="text-center mt-3">
                        <button class="btn btn-primary" @click="captureImage">Capture</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <div class="text-center">
                    <label for="" class="">Town Name*</label>
                    <select class="form-control mb-3" v-model="selectedTown">
                        <option selected disabled>
                            select
                        </option>
                        <option v-for="item in townData" :key="item.id" :value="item.town.id">
                            {{ item.town.Name }}
                        </option>
                    </select>

                    <img v-if="image" :src="image" alt="Captured Image" width="300" />
                </div>
                <div class="text-center mt-3" v-if="location">
                    <button class="btn btn-success mx-4" @click="submitAttendance('in')">Check In</button>
                    <button class="btn btn-danger mx-4" @click="submitAttendance('out')">Check Out</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";
import { useStore } from "vuex";
import Swal from "sweetalert2";

const store = useStore();
const user = store.getters.getUser;

const selectedTown = ref("");
const townData = ref([]);

const loading = ref(false);
const video = ref(null);
const canvas = ref(null);
const image = ref(null);
const location = ref(null);
const camera = ref(false);
const isCheckInDisabled = ref(false);

function updateCheckInStatus() {
    const now = new Date();
    const hour = now.getHours();

    isCheckInDisabled.value = hour >= 12;
}

const captureImage = async () => {
    const videoEl = video.value;
    const canvasEl = canvas.value;

    // Set desired dimensions for the resized image
    const desiredWidth = 300;
    const aspectRatio = videoEl.videoHeight / videoEl.videoWidth;
    const desiredHeight = desiredWidth * aspectRatio;

    // Resize the canvas
    canvasEl.width = desiredWidth;
    canvasEl.height = desiredHeight;

    const context = canvasEl.getContext("2d");

    //Flip camera
    context.translate(canvasEl.width, 0);
    context.scale(-1, 1);

    // Draw the video frame to the canvas with the new dimensions
    context.drawImage(videoEl, 0, 0, canvasEl.width, canvasEl.height);

    // Convert the canvas to a Base64 string
    image.value = canvasEl.toDataURL("image/jpeg", 0.7); // Set quality (0.7 reduces file size)
    stopCamera();

    await getLocation();
    camera.value = false;
};


const getData = async () => {
    const response = await api.get(`/fetch-user-town/${user.id}`);
    townData.value = response.data;
};

const getLocation = () => {
    return new Promise((resolve, reject) => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    location.value = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };
                    resolve();
                },
                (error) => reject(error)
            );
        } else {
            console.error("Geolocation is not supported by this browser.");
            reject();
        }
    });
};

const submitAttendance = async (status) => {

    loading.value = true;
    if (!location.value) {
        Swal.fire({
            icon: "error",
            title: "Validation Error",
            text: "Your location is not available.",
        });
        loading.value = false;
        return
    }
    if (!selectedTown.value) {
        Swal.fire({
            icon: "error",
            title: "Validation Error",
            text: "Town cannot be empty.",
        });
        loading.value = false;
        return
    }

    const attendanceData = {
        eid: user.id,
        location: location.value,
        timestamp: new Date().toISOString(),
        status: status,
        town_id: selectedTown.value,
        image: image.value,
    };

    try {
        const response = await api.post("/app-attendance", attendanceData);
        Swal.fire({
            position: "middle",
            icon: "success",
            title: "Attendence Submitted Successfully!",
            showConfirmButton: false,
            timer: 1500,
        });
        loading.value = false;
        image.value = null;
        location.value = null;
    } catch (error) {
        console.error("Error submitting attendance:", error);
        const errorMessage =
            error.response && error.response.data && error.response.data.message
                ? error.response.data.message
                : "Something went wrong. Please try again.";
        loading.value = false;
        Swal.fire({
            position: "middle",
            icon: "error",
            title: errorMessage,
            showConfirmButton: true,
        });
    } finally {
        loading.value = false;
    }
};

const stopCamera = () => {
    if (video.value && video.value.srcObject) {
        const stream = video.value.srcObject;
        const tracks = stream.getTracks();
        tracks.forEach((track) => track.stop()); // Stop each track
        video.value.srcObject = null; // Clear the video source
    }
    camera.value = false;
};

const startCamera = () => {
    camera.value = true;
    navigator.mediaDevices
        .getUserMedia({ video: { facingMode: "user" } })
        .then((stream) => {
            video.value.srcObject = stream;
        })
        .catch((error) => {
            console.error("Error accessing the camera:", error);
        });
};

onMounted(() => {
    getData();
    updateCheckInStatus();
    setInterval(updateCheckInStatus, 60_000);
});
</script>

<style scoped>
.video-wrapper {
    position: relative;
    width: 100%;
    max-width: 500px;
    margin: 0 auto;
    border: 2px solid #ccc;
    border-radius: 8px;
}

video {
    width: 100%;
    object-fit: cover;
}
</style>
