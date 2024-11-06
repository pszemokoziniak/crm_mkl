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
    // Render chart when the component is mounted
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
