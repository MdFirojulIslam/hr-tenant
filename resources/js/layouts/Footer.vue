<template>
    <footer class="footer border-top text-center py-2">
        <div
            class="container d-flex justify-content-center flex-wrap align-items-center"
        >
            <span class="me-2">All Rights Reserved</span>
            <img
                src="/public/storage/uploads/1718087744_artisan.png"
                height="30"
                width="30"
                class="mx-2"
                alt="Logo"
            />
            <span class="fw-bold me-2">Artisan IT</span>
            <span>© {{ new Date().getFullYear() }}</span>
        </div>
    </footer>
</template>

<script setup></script>

<style scoped>
.footer {
    background-color: #e7e6e6;
}
</style>
