window.addEventListener('DOMContentLoaded', event => {
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    const sidebar = document.querySelector('#layoutSidenav_nav');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }

    document.addEventListener('click', (event) => {
        if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
            // If clicked outside of sidebar and toggle button, close sidebar
            if (document.body.classList.contains('sb-sidenav-toggled')) {
                document.body.classList.remove('sb-sidenav-toggled');
                localStorage.setItem('sb|sidebar-toggle', false);
            }
        }
    });
});

