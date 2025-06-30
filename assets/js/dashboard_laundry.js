// Grafik Laundry Clean Dashboard
// Menampilkan jumlah pesanan (bar) & pendapatan (line) per bulan

document.addEventListener('DOMContentLoaded', function() {
  fetch('/app-binatu-skripsi/fetch/grafik_ringkasan_laundry.php')
    .then(response => response.json())
    .then(data => {
      const categories = data.map(item => item.bulan);
      const jumlahPesanan = data.map(item => item.jumlah_pesanan);
      const pendapatan = data.map(item => item.pendapatan);

      var options = {
        series: [
          { name: 'Jumlah Pesanan', type: 'column', data: jumlahPesanan },
          { name: 'Pendapatan', type: 'line', data: pendapatan }
        ],
        chart: { height: 350, type: 'line', toolbar: { show: true } },
        stroke: { width: [0, 4] },
        xaxis: { categories: categories },
        yaxis: [
          { title: { text: 'Jumlah Pesanan' } },
          { opposite: true, title: { text: 'Pendapatan (Rp)' } }
        ],
        dataLabels: { enabled: true, enabledOnSeries: [1] },
        colors: ['#5D87FF', '#49BEFF'],
        tooltip: { shared: true, intersect: false }
      };
      var chart = new ApexCharts(document.querySelector('#chart'), options);
      chart.render();
    });

  // Set tahun berjalan pada judul jika ada elemen #currentYear
  const yearElement = document.getElementById('currentYear');
  if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
  }

  // Grafik Layanan Terpopuler (Bar Chart)
  fetch('/app-binatu-skripsi/fetch/grafik_layanan_populer.php')
    .then(response => response.json())
    .then(data => {
      const layanan = data.map(item => item.nama_layanan);
      const jumlahPemesanan = data.map(item => item.jumlah_pemesanan);

      var optionsLayanan = {
        series: [{ data: jumlahPemesanan }],
        chart: { type: 'bar', height: 280 },
        plotOptions: { bar: { borderRadius: 4, horizontal: true } },
        dataLabels: { enabled: false },
        xaxis: { categories: layanan },
        colors: ['#FF6384']
      };

      var chartLayanan = new ApexCharts(document.querySelector('#layanan-populer-chart'), optionsLayanan);
      chartLayanan.render();
    })
    .catch(error => console.error('Error fetching popular services data:', error));

  // Grafik Status Pembayaran (Donut Chart)
  fetch('/app-binatu-skripsi/fetch/grafik_status_pembayaran.php')
    .then(response => response.json())
    .then(data => {
      const labels = data.map(item => item.status_pembayaran);
      const series = data.map(item => parseInt(item.jumlah, 10));

      var optionsStatus = {
        series: series,
        chart: { type: 'donut', height: 300 },
        labels: labels,
        responsive: [{
          breakpoint: 480,
          options: {
            chart: { width: 200 },
            legend: { position: 'bottom' }
          }
        }],
        colors: ['#36A2EB', '#FFCE56', '#FF6384', '#4BC0C0']
      };

      var chartStatus = new ApexCharts(document.querySelector('#status-pembayaran-chart'), optionsStatus);
      chartStatus.render();
    })
    .catch(error => console.error('Error fetching payment status data:', error));
});
