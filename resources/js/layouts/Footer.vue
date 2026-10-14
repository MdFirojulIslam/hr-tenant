<template>
    <footer class="footer">
        <div class="footer-inner">
            <span class="footer-text">All Rights Reserved</span>
            <span class="footer-brand">
                <img
                    src="/public/storage/uploads/1718087744_artisan.png"
                    class="footer-logo"
                    alt="Logo"
                />
                <span class="footer-brand-name">Artisan IT</span>
            </span>
            <span class="footer-text">© {{ new Date().getFullYear() }}</span>
        </div>
    </footer>
</template>

<script setup></script>

<style scoped>
.footer {
    flex-shrink: 0;
    margin-top: auto;
    width: 100%;
    background-color: #f8f8f8;
    border-top: 1px solid #e5e5e5;
    padding: 8px 12px;
    font-family: "Segoe UI Variable", "Segoe UI", system-ui, sans-serif;
    color: #616161;
}

.footer-inner {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 4px 6px;
    line-height: 1.2;
}

.footer-text {
    font-size: 11px;
    color: #616161;
    white-space: nowrap;
}

.footer-brand {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.footer-logo {
    height: 24px;
    width: 24px;
    object-fit: contain;
    flex-shrink: 0;
}

.footer-brand-name {
    font-size: 12px;
    font-weight: 600;
    color: #616161;
    white-space: nowrap;
}
</style>
