// ================= JAVASCRIPT UTAMA =================
// Berkas disiapkan sebagai fondasi interaksi umum aplikasi.

// Sidebar Admin dan Dokter tetap statis di tablet/desktop, lalu dapat dibuka
// melalui tombol menu pada layar mobile tanpa bergantung pada class offcanvas.
(() => {
    const batasMobile = window.matchMedia('(max-width: 767.98px)');

    document.querySelectorAll('[data-sidebar-toggle]').forEach((tombol) => {
        const idSidebar = tombol.getAttribute('data-sidebar-toggle');
        const sidebar = idSidebar ? document.getElementById(idSidebar) : null;

        if (!sidebar) {
            return;
        }

        const aturStatus = (terbuka) => {
            sidebar.classList.toggle('sidebar-terbuka', terbuka);
            sidebar.setAttribute('aria-hidden', terbuka ? 'false' : 'true');
            tombol.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
        };

        const tutupSidebar = () => aturStatus(false);

        tombol.addEventListener('click', (event) => {
            event.preventDefault();
            aturStatus(!sidebar.classList.contains('sidebar-terbuka'));
        });

        sidebar.querySelectorAll('[data-sidebar-close]').forEach((tombolTutup) => {
            tombolTutup.addEventListener('click', tutupSidebar);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('sidebar-terbuka')) {
                tutupSidebar();
            }
        });

        const sesuaikanBreakpoint = () => {
            if (batasMobile.matches) {
                tutupSidebar();
            } else {
                sidebar.classList.remove('sidebar-terbuka');
                sidebar.setAttribute('aria-hidden', 'false');
                tombol.setAttribute('aria-expanded', 'false');
            }
        };

        batasMobile.addEventListener('change', sesuaikanBreakpoint);
        sesuaikanBreakpoint();
    });
})();
