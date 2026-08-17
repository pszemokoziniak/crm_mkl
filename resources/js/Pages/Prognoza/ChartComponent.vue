<template>
  <div>
    <canvas id="myChart" width="400" height="50" aria-label="Zestwienie praconików" role="img"></canvas>
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
  },
  mounted() {
    const ranges = this.chartData.ranges || []

    new Chart(document.getElementById('myChart'), {
      type: 'bar', // You can use different chart types like 'line', 'pie', etc.
      data: this.chartData,
      options: {
        ...this.chartOptions,
        scales: {
          y: {
            beginAtZero: true,
            max: 200,
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
}
</script>

<style scoped>
canvas {
  max-width: 100%;
  height: auto;
}
</style>
