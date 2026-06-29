<script setup>
defineProps({
    label: {
        type: [String],
        default: "Given Label Name",
    },
});
</script>

<template>
    <div>
        <label class="form-label custom-label">{{ label }}</label>
    </div>
</template>

<style scoped>
.custom-label {
    font-size: 12px;
    font-weight: 600;
    font-family: system-ui;
}
</style>
