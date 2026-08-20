<script setup>
defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "Day",
    },
});
</script>

<template>
    <input
        type="text"
        class="form-control form-control-sm day-name bg-light"
        :value="modelValue"
        readonly
        :placeholder="placeholder"
    />
</template>

<style scoped>
.day-name {
    font-size: 14px;
    font-weight: 500;
    height: 33px;
    width: 100px;
    border-radius: 5px;
}
</style>
