<script setup>
const value = defineModel({
    type: [String, Number, Date],
    default: "",
});

defineProps({
    placeholder: {
        type: String,
        default: "Enter Value",
    },
    type: {
        type: [String, Number, Date],
        default: "",
    },
    error: {
        type: [Array, String],
        default: null,
    },
});
</script>

<template>
    <input
        v-model="value"
        :type="type"
        :placeholder="placeholder"
        :class="['input-field', { 'input-error': error }]"
    />
</template>

<style scoped>
.input-field {
    width: 120px;
    font-size: 14px;
    letter-spacing: 1px;
    border: 0.8px solid rgb(196, 194, 194);
    padding-left: 5px;
    padding-top: 5px;
    padding-bottom: 5px;
    border-radius: 5px;
}
.input-error {
    border: 1px solid red;
}
</style>
