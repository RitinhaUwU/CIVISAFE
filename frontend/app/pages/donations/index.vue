<script setup lang="ts">
import {useApiStore} from "@/stores/api";
import type {DonationLog, StockStatistics} from "@/types";
import LowestStockChart from "@/components/donation/LowestStockChart.vue";
import TopStockChart from "@/components/donation/TopStockChart.vue";

const stockStats = ref<StockStatistics>({
  totalStock: 0,
  stockAlerts: [],
  topStock: [],
  lowestStock: []
});

onMounted(async () => {
  if (!await checkServerAccess()) {
    useToast().add({
      title: "O Módulo de Doações só está disponível Online",
      color: "warning"
    })
    await useRouter().push('/inicio');
    return;
  }

  if (!useAuthStore().hasPermission('DONATION_LOG_LIST')) {
    await useRouter().push('/inicio');
    return;
  }

  stockStats.value = (await useApiStore().getStockStats()).data;
})

</script>

<template>
  <UDashboardPanel id="donations">
    <template #header>
      <UDashboardNavbar title="Doações" :ui="{ right: 'gap-3' }">
        <template #leading>
          <UDashboardSidebarCollapse/>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UPageGrid class="xl:grid-cols-2 gap-4 sm:gap-6 xl:gap-px">
        <UPageCard
          title="Quantidade de Itens em Stock"
          icon="i-lucide-chart-pie"
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
              {{ stockStats.totalStock }}
            </span>
          </div>
        </UPageCard>

        <UPageCard
          title="Alertas de Abastecimento"
          icon="i-lucide-siren"
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
              {{ stockStats.stockAlerts.length }}
            </span>
          </div>
        </UPageCard>
      </UPageGrid>

      <div class="xl:grid grid-cols-2 gap-4">
        <!-- Doações Recentes -->
        <UCard>
          <template #header>
            Doações recentes
            <DonationAddModal class="flex float-right ml-4"/>
          </template>

          <DonationTable
            class="h-80"
            :preview="true"
          />

          <template #footer>
            <DonationAllDonationsModal class="float-end mb-4" />
          </template>
        </UCard>

        <!-- Alertas Abastecimento -->
        <UCard>
          <template #header>
            Alertas de Abastecimento
          </template>

          <UEmpty
            v-if="stockStats.stockAlerts.length == 0"
            icon="i-lucide-check"
            title="Sem Alertas de Abastecimento"
            description="De momento o Stock encontra-se nominal pelo que não existem alertas de categorias com níveis baixos de abastecimento"
            variant="naked"
            class="h-90"
          />

          <UScrollArea
            v-else
            v-slot="{item, index}"
            :items="stockStats.stockAlerts"
            class="h-90"
          >

            <UPageCard
              :title="item.name"
              :description='`${item.stock}/${item.danger_level} ${convertedMeasurementUnit(item.unit)}`'
              :variant="index % 2 === 0 ? 'soft' : 'outline'"
              class="rounded-none"
            />

          </UScrollArea>

        </UCard>

        <UCard>
          <template #header>
            Categorias com mais abastecimento
          </template>

          <TopStockChart
            :topStockItems="stockStats.topStock"
          />
        </UCard>

        <UCard>
          <template #header>
            Categorias com menor abastecimento
          </template>

          <LowestStockChart
            :lowestStockItems="stockStats.lowestStock"
          />
        </UCard>
      </div>
    </template>
  </UDashboardPanel>
</template>
