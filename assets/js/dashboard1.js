$(function () {


  // =====================================
  // Total Transaksi
  // =====================================
  // Ambil data dari PHP
    fetch('/app-binatu-skripsi/fetch/grafik_ringkasan_laundry.php')
  .then(response => response.json())
  .then(data => {
    const categories = data.map(item => item.bulan);
    const jumlahPesanan = data.map(item => item.jumlah_pesanan);
    const pendapatan = data.map(item => item.pendapatan);

      // Konfigurasi chart
      var chartOptions = {
        series: [
          { name: "Barang Masuk:", data: barangMasuk },
          { name: "Barang Keluar:", data: barangKeluar },
        ],
        chart: {
          type: "bar",
          height: 345,
          offsetX: -15,
          toolbar: { show: true },
          foreColor: "#adb0bb",
          fontFamily: 'inherit',
          sparkline: { enabled: false },
        },
        colors: ["#5D87FF", "#49BEFF"],
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: "35%",
            borderRadius: [6],
            borderRadiusApplication: 'end',
            borderRadiusWhenStacked: 'all'
          },
        },
        markers: { size: 0 },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: {
          borderColor: "rgba(0,0,0,0.1)",
          strokeDashArray: 3,
          xaxis: {
            lines: {
              show: false,
            },
          },
        },
        xaxis: {
          type: "category",
          categories: categories,
          labels: {
            style: { cssClass: "grey--text lighten-2--text fill-color" },
          },
        },
        yaxis: {
          show: true,
          min: 0,
          tickAmount: 4,
          labels: {
            style: {
              cssClass: "grey--text lighten-2--text fill-color",
            },
          },
        },
        stroke: {
          show: true,
          width: 3,
          lineCap: "butt",
          colors: ["transparent"],
        },
        tooltip: { theme: "light" },
        responsive: [
          {
            breakpoint: 600,
            options: {
              plotOptions: {
                bar: {
                  borderRadius: 3,
                }
              },
            }
          }
        ]
      };

      // Render chart
      var chart = new ApexCharts(document.querySelector("#chart"), chartOptions);
      chart.render();
    })
    .catch(error => console.error('Error fetching data:', error));





  // =====================================
  // Breakup
  // =====================================
  fetch('/app-pkl/fetch/ambil_transaksi.php')
  .then(response => response.json())
  .then(data => {
    if (data.message) {
      console.error('Pesan dari server:', data.message);
      return;
    }

    // Ekstrak jumlah entri
    const barangMasukEntries = data[0]?.barang_masuk_entries || 0;
    const barangKeluarEntries = data[0]?.barang_keluar_entries || 0;

    // Konfigurasi donut chart
    const breakup = {
      series: [barangMasukEntries, barangKeluarEntries],
      labels: ["Barang Masuk", "Barang Keluar"],
      chart: {
        type: "donut",
        height: 300, // Atur tinggi grafik agar sesuai dengan card
        fontFamily: "'Plus Jakarta Sans', sans-serif",
        foreColor: "#adb0bb",
      },
      plotOptions: {
        pie: {
          donut: {
            size: '75%',
          },
        },
      },
      dataLabels: {
        enabled: true,
        style: {
          fontSize: '14px',
        },
        formatter: (value) => `${value.toFixed(1)}%`, // Tambahkan format persentase
      },
      legend: {
        show: true,
        position: "bottom", // Letakkan legenda di bawah
      },
      colors: ["#5D87FF", "#ecf2ff"],
      tooltip: {
        theme: "dark",
        y: {
          formatter: (value) => `${value} transaksi`,
        },
      },
    };

    // Render chart
    const chart = new ApexCharts(document.querySelector("#breakup"), breakup);
    chart.render();
  })
  .catch(error => console.error('Error fetching data:', error));





  // =====================================
  // Earning
  // =====================================
  var earning = {
    chart: {
      id: "sparkline3",
      type: "area",
      height: 60,
      sparkline: {
        enabled: true,
      },
      group: "sparklines",
      fontFamily: "Plus Jakarta Sans', sans-serif",
      foreColor: "#adb0bb",
    },
    series: [
      {
        name: "Earnings",
        color: "#49BEFF",
        data: [25, 66, 20, 40, 12, 58, 20],
      },
    ],
    stroke: {
      curve: "smooth",
      width: 2,
    },
    fill: {
      colors: ["#f3feff"],
      type: "solid",
      opacity: 0.05,
    },

    markers: {
      size: 0,
    },
    tooltip: {
      theme: "dark",
      fixed: {
        enabled: true,
        position: "right",
      },
      x: {
        show: false,
      },
    },
  };
  new ApexCharts(document.querySelector("#earning"), earning).render();
})
// Ambil elemen dengan ID "currentYear"
const yearElement = document.getElementById("currentYear");

// Set tahun saat ini ke dalam elemen
const currentYear = new Date().getFullYear(); // Mendapatkan tahun saat ini
yearElement.textContent = currentYear;

