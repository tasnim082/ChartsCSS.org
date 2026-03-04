/**
 * IT Support Management System - App JS
 */

$(function () {
    // ── Theme Toggle ─────────────────────────────────────────────────────────
    $('#themeToggle').on('click', function () {
        $.post(IT.baseUrl + 'ajax/theme.php', { action: 'toggle' }, function (res) {
            if (res.theme) {
                const html = document.documentElement;
                html.setAttribute('data-bs-theme', res.theme);
                document.body.classList.toggle('theme-dark', res.theme === 'dark');
                document.body.classList.toggle('theme-light', res.theme === 'light');
                const icon = res.theme === 'dark' ? 'sun' : 'moon';
                $('#themeToggle i').attr('class', `bi bi-${icon}-fill`);
            }
        }, 'json');
    });

    // ── Auto-dismiss alerts ───────────────────────────────────────────────────
    setTimeout(function () {
        $('.alert-auto-dismiss').fadeOut('slow', function () { $(this).remove(); });
    }, 4000);

    // ── Confirm delete ────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete-confirm', function (e) {
        if (!confirm('Are you sure you want to delete this record?')) {
            e.preventDefault();
        }
    });

    // ── Tooltips ──────────────────────────────────────────────────────────────
    const tooltipElems = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipElems.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // ── Category row highlight (table click to view) ──────────────────────────
    $(document).on('click', '.issue-row', function () {
        window.location.href = $(this).data('href');
    });

    // ── Report form: date range quick picks ──────────────────────────────────
    $('#quickToday').on('click', function () {
        const today = new Date().toISOString().slice(0, 10);
        $('#dateFrom, #dateTo').val(today);
    });

    $('#quickThisMonth').on('click', function () {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10);
        $('#dateFrom').val(firstDay);
        $('#dateTo').val(lastDay);
    });

    // ── Responsive table cards on XS ─────────────────────────────────────────
    // handled via CSS
});

/**
 * Shared namespace
 */
window.IT = window.IT || {};

/**
 * Build a doughnut chart for category distribution
 * @param {string} canvasId  - canvas element id
 * @param {Object} data      - { labels, counts, colors }
 * @param {string} baseUrl   - base URL for click navigation
 */
IT.buildCategoryChart = function (canvasId, data, baseUrl) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.counts,
                backgroundColor: data.colors,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? Math.round(context.raw / total * 100) : 0;
                            return ` ${context.label}: ${context.raw} (${pct}%)`;
                        }
                    }
                }
            },
            onClick: function (evt, elements) {
                if (elements.length > 0) {
                    const idx = elements[0].index;
                    const cat = data.labels[idx];
                    window.location.href = baseUrl + 'problems/list.php?category=' + encodeURIComponent(cat);
                }
            }
        }
    });
    return chart;
};

/**
 * Build a bar chart for monthly trend
 */
IT.buildMonthlyChart = function (canvasId, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Issues',
                data: data.counts,
                backgroundColor: '#4e73df',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, font: { size: 10 } },
                    grid: { drawBorder: false }
                },
                x: {
                    ticks: { font: { size: 10 } },
                    grid: { display: false }
                }
            },
            onClick: function (evt, elements) {
                if (elements.length > 0) {
                    const idx = elements[0].index;
                    const month = data.monthValues[idx];
                    window.location.href = IT.baseUrl + 'problems/list.php?month=' + month;
                }
            }
        }
    });
};

/**
 * Build status pie chart
 */
IT.buildStatusChart = function (canvasId, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.counts,
                backgroundColor: ['#e74a3b', '#f6c23e', '#1cc88a', '#858796'],
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                }
            },
            onClick: function (evt, elements) {
                if (elements.length > 0) {
                    const idx = elements[0].index;
                    const status = data.labels[idx];
                    window.location.href = IT.baseUrl + 'problems/list.php?status=' + encodeURIComponent(status);
                }
            }
        }
    });
};
