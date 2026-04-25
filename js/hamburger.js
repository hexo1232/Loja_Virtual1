/* hamburger.js — lógica da sidebar e dropdown do usuário
   Incluir em todas as páginas que tenham sidebar:
   <script src="js/hamburger.js" defer></script>
*/
(function () {
    const sidebar  = document.getElementById('sidebar');
    const toggle   = document.getElementById('sidebarToggle');
    const overlay  = document.getElementById('sidebarOverlay');
    const userBtn  = document.getElementById('sidebarUserBtn');
    const dropdown = document.getElementById('sidebarDropdown');

    if (!sidebar || !toggle) return;

    // ── Hamburger toggle ──────────────────────────────────
    function toggleSidebar(force) {
        const aberta = force !== undefined ? force : !sidebar.classList.contains('sidebar--aberta');
        sidebar.classList.toggle('sidebar--aberta', aberta);
        if (overlay) overlay.classList.toggle('sidebar-overlay--visivel', aberta);
        toggle.classList.toggle('sidebar-toggle--ativo', aberta);
        toggle.setAttribute('aria-expanded', String(aberta));
    }

    toggle.addEventListener('click', () => toggleSidebar());
    if (overlay) overlay.addEventListener('click', () => toggleSidebar(false));

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) toggleSidebar(false);
    });

    // ── Dropdown do usuário ───────────────────────────────
    if (userBtn && dropdown) {
        function toggleDropdown(force) {
            const aberto = force !== undefined ? force : !dropdown.classList.contains('sidebar-dropdown--visivel');
            dropdown.classList.toggle('sidebar-dropdown--visivel', aberto);
            userBtn.setAttribute('aria-expanded', String(aberto));
            userBtn.classList.toggle('sidebar-user--ativo', aberto);
        }

        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDropdown();
        });

        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target)) toggleDropdown(false);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') toggleDropdown(false);
        });
    }
})();