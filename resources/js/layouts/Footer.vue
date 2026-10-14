<template>
    <footer class="footer">
        <div class="footer-inner">
            All Rights Reserved ·
            <b class="footer-brand-name">Artisan IT</b>
            © {{ new Date().getFullYear() }}
        </div>
    </footer>
</template>

<script setup></script>

<style scoped>
.footer {
    flex-shrink: 0;
    margin-top: auto;
    width: 100%;
    background-color: transparent;
    padding: 18px 8px 0 8px;
    font-size: 11px;
    color: #5b6b70;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
}

.footer-inner {
    line-height: 1.3;
}

.footer-brand-name {
    font-weight: 700;
    color: #1b2a2f;
}
</style>
