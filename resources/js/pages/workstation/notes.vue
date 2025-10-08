<template>
    <component v-if="noteComponent" :is="noteComponent" :isOpen="noteModel" :editNote="selectedNote"
        @modal-close="noteClose" />

    <component v-if="noteShareComponent" :is="noteShareComponent" :isOpen="noteShareModel" :editNote="selectedNote"
        @modal-close="noteShareClose" />

    <div class="d-flex justify-content-end">
        <button class="btn btn-danger" @click="
            router.push({
                name: 'TrashedNote',
            })
            ">
            <i class="fa-solid fa-trash"></i>
        </button>
    </div>

    <div class="d-flex flex-column align-items-center">
        <div class="note-card">
            <input type="text" @click="noteVisibility = true" placeholder="Title" class="title-input"
                v-model="form.title" />
            <textarea ref="textarea" placeholder="Take a note..." class="note-input" @input="autoResize"
                v-if="noteVisibility" v-model="form.content"></textarea>
            <div class="d-flex justify-content-between" v-if="noteVisibility">
                <button @click="saveNote()" class="action-btn">Save</button>
                <button @click="closeNote()" class="action-btn">Close</button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-start mt-5 row">
        <p class="mx-3">Pinned</p>
        <hr />
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-6 px-2" v-for="note in notes.pinNotes" :key="note.id">
                <TransitionGroup name="list" tag="div">
                    <div class="card p-0 flex-column">
                        <div class="card-header text-center sticky-top bg-white d-flex justify-content-between"
                            style="z-index: 2">
                            <div>
                                <h5>{{ note.Title }}</h5>
                            </div>
                            <div @click.stop="pinNote(note.id, note.Pin ? 0 : 1)">
                                <img src="/public/storage/uploads/pin.png" height="20px" width="20px" alt="" />
                            </div>
                        </div>
                        <div class="card-body flex-grow-1 overflow-auto position-relative" style="z-index: 1"
                            @click="noteOpen(note)">
                            <p v-html="formatContent(note.Content)"></p>
                        </div>
                        <div class="card-footer text-muted sticky-bottom bg-white d-flex justify-content-between"
                            style="z-index: 2">
                            <div>
                                <small>Created At:
                                    {{
                                        new Date(note.created_at).toLocaleString()
                                    }}</small>
                            </div>
                            <div class="dropdown">
                                <img v-if="note.shared_user_images[0]" class="mx-2 rounded-circle"
                                    :src="note.shared_user_images[0].img_url" alt="" height="20px" width="20px" />
                                <img v-if="note.shared_user_images[1]" class="mx-2 rounded-circle"
                                    :src="note.shared_user_images[1].img_url" alt="" height="20px" width="20px" />
                                <img v-if="note.shared_user_images[2]" class="mx-2 rounded-circle"
                                    :src="note.shared_user_images[2].img_url" alt="" height="20px" width="20px" />
                                <small v-if="note.shared_user_images[3]" class="mx-2">+</small>
                                <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" @click.prevent="noteShareOpen(note)">Share</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#"
                                            @click.prevent="deleteNote(note.id)">Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-start mt-5 row">
        <p class="mx-3">Shared</p>
        <hr />
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-6 px-2" v-for="note in notes.sharedNotes" :key="note.id">
                <div class="card p-0 flex-column">
                    <div class="card-header text-center sticky-top bg-white d-flex justify-content-between"
                        style="z-index: 2">
                        <div>
                            <h5>{{ note.Title }}</h5>
                        </div>
                        <div>
                            <img v-if="note.creator_img" class="mx-2 rounded-circle" :src="note.creator_img" alt=""
                                height="20px" width="20px" />
                        </div>
                    </div>
                    <div class="card-body flex-grow-1 overflow-auto position-relative" style="z-index: 1"
                        @click="noteOpen(note)">
                        <p v-html="formatContent(note.Content)"></p>
                    </div>
                    <div class="card-footer text-muted sticky-bottom bg-white d-flex justify-content-between"
                        style="z-index: 2">
                        <div>
                            <small>Created At:
                                {{
                                    new Date(note.created_at).toLocaleString()
                                }}
                                &nbsp;</small>
                        </div>
                        <div>
                            <img v-if="note.shared_user_images[0]" class="mx-2 rounded-circle"
                                :src="note.shared_user_images[0].img_url" alt="" height="20px" width="20px" />
                            <img v-if="note.shared_user_images[1]" class="mx-2 rounded-circle"
                                :src="note.shared_user_images[1].img_url" alt="" height="20px" width="20px" />
                            <img v-if="note.shared_user_images[2]" class="mx-2 rounded-circle"
                                :src="note.shared_user_images[2].img_url" alt="" height="20px" width="20px" />
                            <small v-if="note.shared_user_images[3]" class="mx-2">+</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-start mt-5 row">
        <p class="mx-3">Others</p>
        <hr />
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-6 px-2" v-for="note in notes.unPinNotes" :key="note.id">
                <TransitionGroup name="list" tag="div">
                    <div class="card p-0 flex-column">
                        <div class="card-header text-center sticky-top bg-white d-flex justify-content-between"
                            style="z-index: 2">
                            <div>
                                <h5>{{ note.Title }}</h5>
                            </div>
                            <div @click.stop="pinNote(note.id, note.Pin ? 0 : 1)">
                                <img src="/public/storage/uploads/unpin.png" height="20px" width="20px" alt="" />
                            </div>
                        </div>
                        <div class="card-body flex-grow-1 overflow-auto position-relative" style="z-index: 1"
                            @click="noteOpen(note)">
                            <p v-html="formatContent(note.Content)"></p>
                        </div>
                        <div class="card-footer text-muted sticky-bottom bg-white d-flex justify-content-between"
                            style="z-index: 2">
                            <div>
                                <small>Created At:
                                    {{
                                        new Date(note.created_at).toLocaleString()
                                    }}</small>
                            </div>
                            <div class="dropdown">
                                <img v-if="note.shared_user_images[0]" class="mx-2 rounded-circle"
                                    :src="note.shared_user_images[0].img_url" alt="" height="20px" width="20px" />
                                <img v-if="note.shared_user_images[1]" class="mx-2 rounded-circle"
                                    :src="note.shared_user_images[1].img_url" alt="" height="20px" width="20px" />
                                <img v-if="note.shared_user_images[2]" class="mx-2 rounded-circle"
                                    :src="note.shared_user_images[2].img_url" alt="" height="20px" width="20px" />
                                <small v-if="note.shared_user_images[3]" class="mx-2">+</small>
                                <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" @click.prevent="noteShareOpen(note)">Share</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#"
                                            @click.prevent="deleteNote(note.id)">Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, defineAsyncComponent, computed } from "vue";
import api from "@/api";
import router from "../../router";

const noteComponent = defineAsyncComponent(() =>
    import("./component/noteForm.vue")
);

const noteShareComponent = defineAsyncComponent(() =>
    import("./component/noteShareForm.vue")
);

const noteModel = ref(false);
const noteShareModel = ref(false);
const selectedNote = ref("");

const noteOpen = (note) => {
    selectedNote.value = note;
    noteModel.value = true;
};

const noteClose = () => {
    noteModel.value = false;
    getData();
};

const noteShareOpen = (note) => {
    selectedNote.value = note;
    noteShareModel.value = true;
};

const noteShareClose = () => {
    noteShareModel.value = false;
    getData();
};

const textarea = ref(null);
const noteVisibility = ref(false);
const notes = ref([]);
const maxRows = 15;
const lineHeight = 24;

const form = ref({
    title: "",
    content: "",
});

const autoResize = () => {
    if (textarea.value) {
        textarea.value.style.height = "auto";
        const newHeight = textarea.value.scrollHeight;
        const maxHeight = maxRows * lineHeight;

        textarea.value.style.height = `${Math.min(newHeight, maxHeight)}px`;
        textarea.value.style.overflowY =
            newHeight > maxHeight ? "auto" : "hidden";
    }
};

const resetForm = () => {
    form.value.title = "";
    form.value.content = "";
    noteVisibility.value = false;
};

const closeNote = () => {
    resetForm();
};

const saveNote = async () => {
    try {
        const response = await api.post("/notes", form.value);
        if (response.data.success) {
            resetForm();
            getData(); // Refresh notes list
        }
    } catch (error) {
        console.error("Error creating store:", error);
    }
};

const deleteNote = async (id) => {
    try {
        const response = await api.delete(`/notes/${id}`);
        getData();
    } catch (error) {
        console.error("Error creating store:", error);
    }
};

const pinNote = async (id, pin) => {
    try {
        await api.get(`/pin-notes?id=${id}&pin=${pin}`);
        getData();
    } catch (error) {
        console.error("Error creating store:", error);
    }
};

const formatContent = (content) => {
    if (content) {
        return content.replace(/\r\n|\n|\r/g, "<br>");
    }
    return "";
};

const getData = async () => {
    try {
        const response = await api.get("/notes");
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
    background-color: #f5f5f5;
}

*::-webkit-scrollbar {
    width: 6px;
    background-color: #f5f5f5;
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
    width: 100%;
    /* Allow height to adjust based on content */
    overflow-y: auto;
    padding: 10px;
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

.list-move {
    transition: all 0.3s ease;
}

.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateY(30px);
}


@media screen and (max-width: 600px) {
    .note-card {
        width: 95%;
    }
}
</style>
