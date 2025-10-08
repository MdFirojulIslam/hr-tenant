<script setup>
import { ref, onMounted } from "vue";
import { useRoute, RouterView } from "vue-router";
import { useStore } from "vuex";

const store = useStore();
const route = useRoute();
const empId = route.params.id ? parseInt(route.params.id) : null; // Handle optional id

const empEdit = ref([]);
</script>

<template>
    <div>
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <router-link
                    class="nav-link"
                    active-class="active"
                    :to="{
                        name: 'Employee',
                        params: empId ? { id: empId } : {},
                        query: { response: empEdit },
                    }"
                    >Employee Information</router-link
                >
            </li>
            <li class="nav-item">
                <router-link
                    class="nav-link"
                    active-class="active"
                    :to="empId ? { name: 'Personal', params: { id: empId } } : { name: 'Personal' }"
                    >Personal Information</router-link
                >
            </li>
            <li class="nav-item">
                <router-link
                    class="nav-link"
                    active-class="active"
                    :to="empId ? { name: 'Professional', params: { id: empId } } : { name: 'Professional' }"
                    >Professional Information</router-link
                >
            </li>
            <li class="nav-item">
                <router-link
                    class="nav-link"
                    active-class="active"
                    :to="empId ? { name: 'Official', params: { id: empId } } : { name: 'Official' }"
                    >Official Information</router-link
                >
            </li>
        </ul>
        <RouterView />
    </div>
</template>

<style>
.nav-link:hover {
    color: rgb(44, 103, 116);
}
</style>
