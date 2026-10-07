/**
 * Main Portal JavaScript Interactions
 * Student Placement Management System
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const mobileToggle = document.querySelector('.mobile-toggle');
    const sidebar = document.querySelector('.app-sidebar');

    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });

        // Close on outside click on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // 2. Tab Navigation Switcher
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const parentNav = this.closest('.tabs-nav');
            const parentContainer = parentNav ? parentNav.parentElement : document;

            // Deactivate all buttons in this nav
            parentNav.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Switch pane
            parentContainer.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });

    // 3. Auto-Dismiss Flash Alerts after 5 seconds
    const flashAlert = document.querySelector('.alert');
    if (flashAlert) {
        setTimeout(() => {
            flashAlert.style.transition = 'opacity 0.6s ease';
            flashAlert.style.opacity = '0';
            setTimeout(() => flashAlert.remove(), 600);
        }, 5000);
    }

    // 4. Client-side Real-time Search / Filter on Tables & Cards
    const filterInput = document.querySelector('[data-table-search]');
    if (filterInput) {
        const targetTable = document.querySelector(filterInput.getAttribute('data-table-search'));
        if (targetTable) {
            filterInput.addEventListener('keyup', function () {
                const query = this.value.toLowerCase().trim();
                const rows = targetTable.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }
    }

    const cardSearchInput = document.querySelector('[data-card-search]');
    if (cardSearchInput) {
        const cardSelector = cardSearchInput.getAttribute('data-card-search');
        cardSearchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll(cardSelector);
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});
