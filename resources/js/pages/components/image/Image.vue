<script setup>
defineProps({
    src: {
        type: String,
        required: true,
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
    rounded: {
        type: Boolean,
        default: false,
    },
    circle: {
        type: Boolean,
        default: false,
    },
    objectFit: {
        type: String,
        default: "cover",
    },
});
</script>

<template>
    <img
        :src="src"
        :alt="alt"
        :style="{
            width,
            height,
            objectFit,
        }"
        :class="[rounded ? 'rounded' : '', circle ? 'circle' : '']"
    />
</template>

<style scoped>
img {
    display: block;
}

/* normal rounded */
.rounded {
    border-radius: 8px;
}

/* circle avatar */
.circle {
    border-radius: 50%;
    object-fit: cover;
}
</style>
