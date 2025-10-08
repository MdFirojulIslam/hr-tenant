<script setup>
import { defineProps } from "vue";

// Define the props to accept node data from the parent
const props = defineProps({
    nodes: {
        type: Array,
        required: true,
    },
});

// Toggle the expanded state of a node
const toggleNode = (node) => {
    node.expanded = !node.expanded;
};
</script>

<template>
    <div>
        <div v-for="node in nodes" :key="node.id">
            <div class="tree-node-header m-2" @click="toggleNode(node)">
                <span v-if="node.children && node.children.length">
                    {{ node.expanded ? "▼" : "▶" }}
                </span>
                <div class="card d-flex" style="width: 350px;">
                    <div class="card-body d-flex align-items-center">
                        <img :src="node.img_url" alt="Image" height="50px" width="50px"
                            style="border-radius: 10%; margin-right: 10px;" />
                        <div class="d-flex flex-column">
                            <h4 class="mb-0">{{ node.Full_Name }}</h4>
                            <small class="text-muted">{{ node.dept_name }}</small>
                            <small class="text-muted">{{ node.des_name }}</small>
                        </div>
                    </div>
                </div>

            </div>
            <transition name="slide-fade">
                <div v-if="node.expanded" class="tree-children">
                    <RecursiveTree :nodes="node.children" />
                </div>
            </transition>
        </div>
    </div>
</template>

<style scoped>
.tree-node {
    margin-left: 20px;
}

.tree-node-header {
    cursor: pointer;
    font-family: Arial, sans-serif;
    line-height: 1.5;
    display: flex;
    align-items: center;
}

.tree-node-header img {
    margin-right: 10px;
}

.tree-children {
    margin-left: 20px;
    border-left: 1px solid #000;
    padding-left: 10px;
}

/* Transition for expand/collapse */
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.3s ease-in;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}
</style>
