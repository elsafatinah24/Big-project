// ================= GRAFIK DASHBOARD =================
// Membuat grafik antrean harian dan jumlah pasien per poli menggunakan Chart.js.

document.addEventListener('DOMContentLoaded', () => {
    const dataDashboard = window.dataDashboard;

    if (!dataDashboard || typeof Chart === 'undefined') {
        return;
    }

    const pengaturanDasar = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 },
                grid: { color: 'rgba(46, 125, 50, 0.08)' },
            },
            x: { grid: { display: false } },
        },
    };

    const elemenAntreanHarian = document.getElementById('grafikAntreanHarian');
    if (elemenAntreanHarian) {
        new Chart(elemenAntreanHarian, {
            type: 'line',
            data: {
                labels: dataDashboard.antreanHarian.label,
                datasets: [{
                    data: dataDashboard.antreanHarian.data,
                    borderColor: '#2e7d32',
                    backgroundColor: 'rgba(102, 187, 106, .18)',
                    fill: true,
                    tension: .35,
                    pointBackgroundColor: '#2e7d32',
                    pointRadius: 4,
                }],
            },
            options: pengaturanDasar,
        });
    }

    const elemenPasienPerPoli = document.getElementById('grafikPasienPerPoli');
    if (elemenPasienPerPoli) {
        new Chart(elemenPasienPerPoli, {
            type: 'bar',
            data: {
                labels: dataDashboard.pasienPerPoli.label,
                datasets: [{
                    data: dataDashboard.pasienPerPoli.data,
                    backgroundColor: '#66bb6a',
                    borderRadius: 7,
                    borderSkipped: false,
                }],
            },
            options: pengaturanDasar,
        });
    }
});
