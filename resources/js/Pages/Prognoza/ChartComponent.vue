<template>
  <div class="chart-wrap">
    <canvas ref="canvas" aria-label="Zestawienie liczby pracowników" role="img"></canvas>
  </div>
</template>

<script>
import { Chart } from 'chart.js/auto'; // Import Chart.js

export default {
  props: {
    chartData: {
      type: Object,
      required: true,
    },
    chartOptions: {
      type: Object,
      required: false,
      default: () => ({}),
    },
    chartMax: {
      type: Number,
      required: false,
      default: 200,
    },
  },
  mounted() {
    const ranges = this.chartData.ranges || []

    this.chart = new Chart(this.$refs.canvas, {
      type: 'bar', // You can use different chart types like 'line', 'pie', etc.
      data: this.chartData,
      options: {
        ...this.chartOptions,
        responsive: true,
        // Wysokość bierze się ze stałej wysokości kontenera (.chart-wrap), a nie
        // z proporcji <canvas>. Przy nawigacji SPA (Inertia) proporcja bywała
        // liczona zanim layout się ustabilizował i wykres puchł na cały ekran;
        // pełne przeładowanie mierzyło poprawnie, stąd "po odświeżeniu jest ok".
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            max: this.chartMax,
          },
          x: {
            ticks: {
              autoSkip: true,
              maxRotation: 60,
              minRotation: 60,
            },
          },
        },
        plugins: {
          ...(this.chartOptions.plugins || {}),
          tooltip: {
            callbacks: {
              // Na osi jest sam początek tygodnia — pełny zakres pokazujemy tutaj.
              title: (items) => ranges[items[0].dataIndex] ?? items[0].label,
            },
          },
        },
      },
    })
  },
  beforeUnmount() {
    if (this.chart) {
      this.chart.destroy()
      this.chart = null
    }
  },
}
</script>

<style scoped>
.chart-wrap {
  position: relative;
  width: 100%;
  height: 260px;
}
</style>
