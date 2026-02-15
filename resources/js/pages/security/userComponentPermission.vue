<script setup>
import { onMounted, ref } from "vue";
import api from "@/api";

const navigationItems = ref([]);
const error = ref([]);

const getData = async () => {
    try {
        const response = await api.get("/navigation-items");
        navigationItems.value = response.data;
    } catch (err) {
        if (err.response && err.response.status === 401) {
            router.push({ name: "Login" });
        } else {
            error.value = err.message || "Error fetching data";
        }
    }
};

onMounted(() => getData());
</script>

<template>
    <div class="container-fluid border p-4">
        <div>
            <div>
                <h3>Create Access Component</h3>
                <div>
                    <label for="">Menu Name</label>
                    <select name="" id="">
                        <option value="">Menu Name 1</option>
                        <option value="">Menu Name 2</option>
                        <option value="">Menu Name 3</option>
                    </select>
                </div>
                <div>
                    <label for="">Page Name</label>
                    <select name="" id="">
                        <option value="">Page Name 1</option>
                        <option value="">Page Name 2</option>
                        <option value="">Page Name 3</option>
                    </select>
                </div>
                <div>
                    <label for="">Component Name</label>
                    <input type="text" />
                </div>
                <div>
                    <button>Create</button>
                </div>
            </div>
        </div>
        <div>
            <div>
                <h3>Set Access Component</h3>
                <div>
                    <label for="">Department Name</label>
                    <select name="" id="">
                        <option value="">Department Name 1</option>
                        <option value="">Department Name 2</option>
                        <option value="">Department Name 3</option>
                    </select>
                </div>
                <div>
                    <label for="">Employee Name</label>
                    <select name="" id="">
                        <option value="">Employee Name 1</option>
                        <option value="">Employee Name 2</option>
                        <option value="">Employee Name 3</option>
                    </select>
                </div>
                <div>
                    <label for="">Component Name</label>
                    <input type="text" />
                </div>
                <div>
                    <button>Set</button>
                </div>
            </div>
        </div>

        <div>
            <div>
                <h3>Menu Name 1</h3>
                <div>
                    <label for="">Page Name 1 </label>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                </div>
                <div>
                    <label for="">Page Name 2 </label>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div>
                <h3>Menu Name 2</h3>
                <div>
                    <label for="">Page Name 1 </label>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                </div>
                <div>
                    <label for="">Page Name 2 </label>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                    <div>
                        <input type="checkbox" />
                        <span>Component Name 1</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
