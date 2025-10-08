<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";
import RecursiveTree from "./component/recursiveTree.vue"; // Import the recursive tree component

const data = ref([]);
const error = ref("");

const getData = async () => {
  try {
    const response = await api.get("/get-hierarchy");
    data.value = initializeTree(response.data);
  } catch (err) {
    error.value = err.message || "Error fetching data";
  }
};

const initializeTree = (nodes) => {
  return nodes.map((node) => ({
    ...node,
    expanded: false,
    children: node.children ? initializeTree(node.children) : [],
  }));
};

onMounted(() => getData());
</script>

<template>
  <div>
    <h1 class="text-center">Organizational Hierarchy</h1>
    <!-- Error Handling -->
    <div v-if="error" class="error">{{ error }}</div>
    <!-- Render Tree -->
    <div v-else>
      <RecursiveTree :nodes="data" />
    </div>
  </div>
</template>

<style scoped>
/* You can add any custom styles for the parent component here */
.error {
  color: red;
  text-align: center;
}
</style>
