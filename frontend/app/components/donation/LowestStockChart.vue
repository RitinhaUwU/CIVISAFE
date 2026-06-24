<script setup lang="ts">
import {Bar} from 'vue-chartjs'
import {BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Title, Tooltip} from "chart.js";
import annotationPlugin from 'chartjs-plugin-annotation'
import type {StatisticalStockMetadata} from "@/types";
import {convertedMeasurementUnit} from "~/utils";

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, annotationPlugin)

const props = defineProps({
  lowestStockItems: {
    type: Array<StatisticalStockMetadata>,
    required: true,
  }
})

const labels = computed(() => props.lowestStockItems.map(item => item.name))
const stockValues = computed(() => props.lowestStockItems.map(item => item.stock))
const units = computed(() => props.lowestStockItems.map(item => item.unit))
const dangerLevels = computed(() => props.lowestStockItems.map(item => item.danger_level))

const chartData = computed(() => ({
  labels: labels.value,
  datasets: [
    {
      data: stockValues.value,
      backgroundColor: [
        'rgba(255, 99, 132)',
        'rgba(255, 159, 64)',
        'rgba(255, 205, 86)',
        'rgba(75, 192, 192)',
        'rgba(54, 162, 235)'
      ],
    }
  ]
}))

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (context: any) => `${context.parsed.y} ${convertedMeasurementUnit(units.value[context.dataIndex] ?? 'units')}`
      }
    },
    annotation: {
      annotations: dangerLevels.value.flatMap((val, i) => {
        if (val == null) return []
        return [{
          type: 'line',
          xMin: i - 0.4,
          xMax: i + 0.4,
          yMin: val,
          yMax: val,
          borderColor: '#e74c3c',
          borderWidth: 2
        }]
      })
    }
  },
  scales: {
    y: { beginAtZero: true }
  }
}))
</script>

<template>
  <UEmpty
    v-if="lowestStockItems.length == 0"
    icon="i-lucide-chart-candlestick"
    title="Sem Stock"
    description="De momento não existe stock de nenhuma categoria, pelo que não é possível apresentar esta estatística"
    variant="naked"
    class="h-90"
  />

  <Bar
    v-else
    :data="chartData"
    :options="chartOptions"
    class="h-90"
  />
</template>

<style scoped>

</style>
