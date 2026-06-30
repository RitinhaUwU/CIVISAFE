<script setup lang="ts">
import {Doughnut} from 'vue-chartjs'
import {ArcElement, Chart as ChartJS, Legend, Title, Tooltip} from "chart.js";
import type {StatisticalStockMetadata} from "@/types";
import {convertedMeasurementUnit} from "~/utils";

ChartJS.register(Title, Tooltip, Legend, ArcElement)

const props = defineProps({
  topStockItems: {
    type: Array<StatisticalStockMetadata>,
    required: true,
  }
})

const labels = computed(() => props.topStockItems.map(item => item.name))
const stockValues = computed(() => props.topStockItems.map(item => item.stock))
const units = computed(() => props.topStockItems.map(item => item.unit))

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
        'rgba(54, 162, 235)',
        'rgb(80 80 80)'
      ],
      borderWidth: 0
    }
  ]
}))

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      align: 'start'
    },
    tooltip: {
      callbacks: {
        label: (context: any) => `${context.parsed} ${convertedMeasurementUnit(units.value[context.dataIndex] ?? 'units')}`
      }
    },
  }
}))
</script>

<template>
  <UEmpty
    v-if="topStockItems.length == 0"
    icon="i-lucide-chart-candlestick"
    title="Sem Stock"
    description="De momento não existe stock de nenhuma categoria, pelo que não é possível apresentar esta estatística"
    variant="naked"
    class="h-90"
  />

  <Doughnut
    v-else
    :data="chartData"
    :options="chartOptions"
    class="h-90"
  />
</template>

<style scoped>

</style>
