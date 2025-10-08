<template>
    <div class="d-flex justify-content-end mb-3">
        <button @click="goBack" class="btn btn-dark">Go Back</button>
    </div>
  </template>
  
  <script>
  export default {
    methods: {
      goBack() {
        this.$router?.go(-1); // Navigates to the previous route if using Vue Router
        //window.history.back();
      },
    },
  };
  </script>
  
  <style scoped>

  </style>
  