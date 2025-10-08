<template>
    <div v-if="isOpen" class="modal-mask" @click="handleOutsideClick">
        <div class="modal-container" @click.stop>
            <input type="text" placeholder="Title" class="title-input" v-model="form.title">
            <textarea ref="textarea" rows="10" placeholder="Take a note..." class="note-input"
                v-model="form.content"></textarea>
            <div class="button-group">
                <button @click="updateNote()" class="action-btn">Save</button>
                <button @click="closeModal()" class="action-btn">Close</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, defineEmits, watch, onMounted } from "vue";
import api from "@/api";

const props = defineProps({
    isOpen: Boolean,
    editNote: Object,
});

const emit = defineEmits(["modal-close", "note-saved"]);

const textarea = ref(null);

const form = ref({
    title: "",
    content: "",
});


watch(
    () => props.editNote,
    (note) => {
        if (note) {
            form.value.title = note.Title || "";
            form.value.content = note.Content || "";
        }
    },
    { immediate: true, deep: true }
);

const closeModal = () => {
    emit("modal-close");
};


const updateNote = async () => {
    try {
        const response = await api.put(`/notes/${props.editNote.id}`, form.value);
        if (response.data.success) {
            emit("modal-close");
            emit("note-saved");
        }
    } catch (error) {
        console.error("Error updating note:", error);
    }
};

onMounted(() => {
    if (textarea.value) {
        autoResize();
    }
});
</script>

<style scoped>

*::-webkit-scrollbar-track {
    -webkit-box-shadow: inset 0 0 6px rgba(139, 138, 138, 0.3);
    background-color: #F5F5F5;
}

*::-webkit-scrollbar {
    width: 6px;
    background-color: #F5F5F5;
}

*::-webkit-scrollbar-thumb {
    background-color:  rgba(139, 138, 138, 0.3);
}

.modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-container {
    width: 400px;
    padding: 20px;
    background: white;
    border-radius: 10px;
    text-align: center;
}

.title-input {
    width: 100%;
    border: none;
    font-size: 16px;
    font-weight: bold;
    outline: none;
    padding: 6px;
}

.note-input {
    width: 100%;
    border: none;
    resize: none;
    outline: none;
    padding: 6px;
    overflow: auto;
    min-height: 40px;
    font-size: 14px;
    line-height: 24px;
}

.button-group {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
}

.action-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    color: gray;
}

.action-btn:hover {
    color: black;
}
</style>
