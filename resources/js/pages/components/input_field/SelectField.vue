<script setup>
const props = defineProps({
    modelValue: [Number, String],
    items: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: "Select Option",
    },
    className: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["update:modelValue"]);
</script>

<template>
    <select
        :class="className ? className : 'form-select'"
        :value="modelValue"
        @change="emit('update:modelValue', $event.target.value)"
    >
        <option value="" disabled>{{ placeholder }}</option>
        <option v-for="item in items" :key="item.value" :value="item.value">
            {{ item.name }}
        </option>
    </select>
</template>

<style scoped>
.form-select {
    width: 200px;
    font-size: 14px;
    letter-spacing: 1px;
    border: 0.8px solid rgb(196, 194, 194);
    padding: 5px;
    border-radius: 5px;

    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: none !important;
}
.incnetive-select-field {
    width: 250px;
    height: 38px;
    padding: 8px 12px;
    border: 0.8px solid rgb(196, 194, 194);
    letter-spacing: 1px;

    border-radius: 5px;
    font-size: 14px;

    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: none !important;
}
</style>
