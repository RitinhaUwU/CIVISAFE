<script setup lang="ts">

const stats = [{
  title: 'Ocorrências Abertas',
  icon: 'i-lucide-siren',
  value: 0
}, {
  title: 'Conversions',
  icon: 'i-lucide-chart-pie',
  value: 0
}, {
  title: 'Revenue',
  icon: 'i-lucide-circle-dollar-sign',
  value: 0
}, {
  title: 'Orders',
  icon: 'i-lucide-shopping-cart',
  value: 0
}]
</script>

<template>
  <UPageGrid class="lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-px">
    <UPageCard
      v-for="(stat, index) in stats"
      :key="index"
      :icon="stat.icon"
      :title="stat.title"
      variant="subtle"
      :ui="{
        container: 'gap-y-1.5',
        wrapper: 'items-start',
        leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
        title: 'font-normal text-muted text-xs uppercase'
      }"
      class="lg:rounded-none first:rounded-l-lg last:rounded-r-lg hover:z-1"
    >
      <div class="flex items-center gap-2">
        <span class="text-2xl font-semibold text-highlighted">
          {{ stat.value }}
        </span>
      </div>
    </UPageCard>
  </UPageGrid>
</template>
