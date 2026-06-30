<script setup lang="ts">
import type { Incident } from '@/types'

const props = defineProps<{
  incidents: Incident[]
}>()

const open = defineModel<boolean>('open', { default: false })
</script>

<template>
  <USlideover v-model:open="open" side="right">
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h2 class="text-base font-semibold">Incidentes Major Ativos</h2>
        <UButton
          variant="ghost"
          color="neutral"
          icon="i-lucide-x"
          size="sm"
          @click="open = false"
        />
      </div>
    </template>
    <template #body>
      <div class="flex flex-col gap-3">
        <div v-if="incidents.length === 0" class="text-sm text-muted text-center py-8">
          Sem incidentes major ativos.
        </div>
        <div v-for="incident in incidents" :key="incident.id" class="rounded-lg border border-orange-200 dark:border-orange-900 bg-orange-50/50 dark:bg-orange-950/20 p-4 flex flex-col gap-3">
          <div class="flex items-start justify-between">
            <div class="font-semibold">{{ incident.identifier }}</div>
            <UBadge color="primary" variant="soft">
              {{ incident.incidentState?.name }}
            </UBadge>
          </div>
          <div class="space-y-1 text-sm flex-1">
            <div><span class="font-medium">Tipo:</span> {{ incident.incidentType?.code }}</div>
            <div><span class="font-medium">Espécie:</span> {{ incident.incidentType?.species }}</div>
            <div><span class="font-medium">Categoria:</span> {{ incident.incidentType?.type }}</div>
          </div>
          <div class="flex justify-end">
            <UButton
              :to="`/incidents/${incident.id}/dashboard`"
              size="sm"
              icon="i-lucide-arrow-right"
            >
              Ver ocorrência
            </UButton>
          </div>
        </div>
      </div>
    </template>
  </USlideover>
</template>
