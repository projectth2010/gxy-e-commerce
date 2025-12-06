<template>
  <div class="chart-container">
    <canvas ref="chart"></canvas>
  </div>
</template>

<script>
import { defineComponent, onMounted, ref, watch } from 'vue';
import { Line } from 'vue-chartjs';
import { 
  Chart as ChartJS, 
  Title, 
  Tooltip, 
  Legend, 
  LineElement, 
  LinearScale, 
  PointElement,
  CategoryScale
} from 'chart.js';

ChartJS.register(
  Title, 
  Tooltip, 
  Legend, 
  LineElement, 
  LinearScale, 
  PointElement,
  CategoryScale
);

export default defineComponent({
  name: 'LineChart',
  components: { Line },
  props: {
    chartData: {
      type: Object,
      required: true,
    },
    options: {
      type: Object,
      default: () => ({}),
    },
    height: {
      type: Number,
      default: 300,
    },
  },
  setup(props) {
    const chart = ref(null);
    let chartInstance = null;

    const renderChart = () => {
      if (chartInstance) {
        chartInstance.destroy();
      }

      const ctx = chart.value.getContext('2d');
      chartInstance = new ChartJS(ctx, {
        type: 'line',
        data: props.chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          ...props.options,
        },
      });
    };

    onMounted(() => {
      renderChart();
    });

    watch(
      () => props.chartData,
      () => {
        renderChart();
      },
      { deep: true }
    );

    return {
      chart,
    };
  },
});
</script>

<style scoped>
.chart-container {
  position: relative;
  height: 100%;
  min-height: 300px;
}
</style>
