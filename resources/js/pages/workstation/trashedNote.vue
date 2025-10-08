<template>

    <div class="d-flex justify-content-center mt-5 row">
        <div class="row"> <!-- Removes column gaps -->
            <div class="col-lg-4 col-md-4 col-sm-6 px-2" v-for="note in notes" :key="note.id">
                <div class="card p-0 d-flex flex-column">
                    <div class="card-header text-center sticky-top bg-white d-flex justify-content-between"
                        style="z-index: 2;">
                        <div>
                            <h5>{{ note.Title }}</h5>
                        </div>
                    </div>
                    <div class="card-body flex-grow-1 overflow-auto position-relative" style="z-index: 1;">
                        <p>{{ note.Content }}</p>
                    </div>
                    <div class="card-footer text-muted sticky-bottom bg-white d-flex justify-content-between"
                        style="z-index: 2;">
                        <div>
                            <small>Deleted At: {{ new Date(note.deleted_at).toLocaleString() }}</small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" @click.prevent="restoreNote(note.id)">Restore Note</a></li>
                                <li><a class="dropdown-item" href="#"
                                        @click.prevent="deleteNote(note.id)">Parmanent Delete</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";

const notes = ref([]);
const textarea = ref(null);

const deleteNote = async (id) => {
    try {
        const response = await api.delete(`/notes/${id}`);
        getData();
    } catch (error) {
        console.error("Error creating store:", error);
    }
};


const getData = async () => {
    try {
        const response = await api.get("/trash-notes");
        notes.value = response.data;
    } catch (error) {
        console.error("Error fetching notes:", error);
    }
};

onMounted(() => {
    if (textarea.value) {
        autoResize();
    }
    getData();
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
    background-color: rgba(139, 138, 138, 0.3);
}


.note-card {
    width: 40%;
    padding: 12px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    transition: height 0.2s ease-in-out;
    margin-bottom: 20px;
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
    overflow: hidden;
    min-height: 40px;
    font-size: 14px;
    line-height: 24px;
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

.card:hover {
    cursor: pointer;
}

.card {
    height: 200px;
    /* Allow height to adjust based on content */
    overflow-y: auto;
    word-wrap: break-word;
    padding: 12px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s;
    margin: 5px;
}

.card:hover {
    transform: scale(1.03);
}

.row {
    margin: 0;
}

.col-lg-3,
.col-md-4,
.col-sm-6 {
    padding: 0;
}


@media screen and (max-width: 600px) {
    .note-card {
        width: 95%;
    }
}
</style>
