<template>
    <div v-if="isOpen" class="modal-mask" @click="handleOutsideClick">
        <div class="modal-container" @click.stop>
            <label for="multiSelect">Employee Name</label>
            <multiselect class="custom-multiselect" v-model="form.emp_id" :options="employeeData" :multiple="true"
                :close-on-select="false" :clear-on-select="false" :preserve-search="true"
                placeholder="Select Employee Name" label="Full_Name" track-by="id"></multiselect>

            <div class="button-group">
                <button @click="shareNote" class="action-btn">Save</button>
                <button @click="closeModal" class="action-btn">Close</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, defineEmits, defineProps, watch, onMounted } from "vue";
import Multiselect from "vue-multiselect";
import api from "@/api";

const employeeData = ref([]);
const shareIDs = ref([]);
const error = ref();

const props = defineProps({
    isOpen: Boolean,
    editNote: Object,
});

const emit = defineEmits(["modal-close", "note-saved"]);

const form = ref({
    emp_id: [],
    note_id: "",
});

const getData = async () => {
    try {
        const empRes = await api.get("/get-supervisor");
        employeeData.value = empRes.data;

        if (props.editNote?.id) {
            const shareidres = await api.get("/note-share-ids", {
                params: {
                    id: props.editNote?.id,
                },
            });
            shareIDs.value = shareidres.data;
            console.log(shareIDs.value);

            // Set preselected employees
            form.value.emp_id = employeeData.value.filter(emp =>
                shareIDs.value.includes(emp.id)
            );
        }

    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};


watch(
    () => props.isOpen,
    (val) => {
        if (val) {
            console.log(props.editNote.id);
            form.value.note_id = props.editNote.id || "";
            getData();
        }
    }
);

const closeModal = () => {
    form.value.emp_id = null;
    emit("modal-close");
};

const shareNote = async () => {
    try {
        const selectedIds = form.value.emp_id.map(emp => emp.id);
        await api.post(`/notes/share`, {
            emp_ids: selectedIds,
            note_id: form.value.note_id,
        });
        emit("modal-close");
        emit("note-saved");
    } catch (error) {
        console.error("Error sharing note:", error);
    }
};

const handleOutsideClick = (event) => {
    if (event.target.classList.contains("modal-mask")) {
        closeModal();
    }
};
</script>

<style scoped>
*::-webkit-scrollbar-track {
    -webkit-box-shadow: inset 0 0 6px rgba(139, 138, 138, 0.3);
    background-color: #f5f5f5;
}

*::-webkit-scrollbar {
    width: 6px;
    background-color: #f5f5f5;
}

*::-webkit-scrollbar-thumb {
    background-color: rgba(139, 138, 138, 0.3);
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
