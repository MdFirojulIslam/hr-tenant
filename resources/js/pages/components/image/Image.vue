<script setup>
import { computed, toRef } from "vue";
import { useEmployeeImage } from "@/pages/composables/useImage";

const props = defineProps({
    employeeID: {
        type: Number,
        default: null,
    },
    src: {
        type: String,
        default: "/default-avatar.png",
    },

    alt: {
        type: String,
        default: "image",
    },

    width: {
        type: String,
        default: "100%",
    },

    height: {
        type: String,
        default: "auto",
    },

    rounded: Boolean,

    circle: Boolean,
});

const { image } = useEmployeeImage(toRef(props, "employeeID"));

const imageSrc = computed(() => {
    return props.employeeID ? image.value : props.src;
});
</script>

<template>
    <img
        :src="imageSrc"
        :alt="alt"
        :style="{
            width,
            height,
        }"
        :class="[rounded ? 'rounded' : '', circle ? 'circle' : '']"
    />
</template>

<style scoped>
.rounded {
    border-radius: 8px;
}

.circle {
    border-radius: 50%;
}
</style>
