<template>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>Employee Id</th>
                                    <th>Name</th>
                                    <th>Show in Map</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(loc, index) in locationDate"
                                    :key="loc.id"
                                >
                                    <td>{{ index + 1 }}</td>
                                    <td>ABNB{{ loc.Employee_Id }}</td>
                                    <td>{{ loc.Full_Name }}</td>
                                    <td>
                                        <button
                                            class="custom-btn btn-15"
                                            @click="showInMap(loc.latitude,  loc.longitude)"
                                        >
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h1>Location Map</h1>
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from '@/api';

const currentYear = ref(new Date().getFullYear());

const latitude = ref(23.7791053);
const longitude = ref(90.4148914);
const locationDate = ref();


const showInMap = async (latitude, longitude) => {
    const location = { lat: latitude, lng: longitude };
    const map = new google.maps.Map(document.getElementById("map"), {
        center: location,
        zoom: 15,
    });
    new google.maps.Marker({
        position: location,
        map: map,
    });

    // // Initialize the geocoder
    // const geocoder = new google.maps.Geocoder();

    // // Perform reverse geocoding to get the location name
    // geocoder.geocode({ location: location }, (results, status) => {
    //     if (status === "OK") {
    //         if (results[0]) {
    //             const locationName = results[0].formatted_address;
    //             console.log("Location Name:", locationName);
    //             // You can display the location name in the UI if desired
    //             alert(`Location: ${locationName}`);
    //         } else {
    //             console.log("No results found");
    //         }
    //     } else {
    //         console.log("Geocoder failed due to: " + status);
    //     }
    // });
};



const getData = async () => {
    const response = await api.get("/location");
    locationDate.value = response.data;
};

onMounted(() => getData());
</script>

<style scoped>
#map {
    height: 500px;
    width: 100%;
}
</style>
