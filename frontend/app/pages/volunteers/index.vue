<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'

const api = useApiStore()
const volunteers = ref<Volunteer[]>([])
const loading = ref(false)
const total = ref(0)

const search = ref('')
const accommodationFilter = ref('all')
const mealFilter = ref('all')
const classificationFilter = ref('all')

const deleteModalOpen = ref(false)
const selectedVolunteerById = ref<Volunteer | null>(null)

type Volunteer = {
  id: number;
  name: string;
  start_datetime: Date;
  end_datetime: Date;
  contact: string;
  email: string;
  num_elements: number
  mission: string;
  team_identification: string
  classification: string
  has_accommodation: boolean;
  location: string;
  has_meal: boolean;
  meal_notes: string;
  meal_location: string;
  incident_id: number;
  created_at: Date;
  updated_at: Date;
  deleted_at: Date;
}

const classificationMap: Record<string, string> = {
  single: 'Individual',
  org: 'Organização',
  misc: 'Outro'
}

const columns: TableColumn<Volunteer>[] = [
  {
    accessorFn: (row) => `#${row.id}`,
    header: "ID",
  },
  {
    accessorKey: "team_identification",
    header: "Equipa",
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'max-w-[180px] truncate' },
        row.original.team_identification
      )
    }
  },
  {
    accessorKey: "name",
    header: "Responsável",
    cell: ({ row }) => {
      return h('div', { class: 'flex flex-col' }, [
        h('span', { class: 'font-medium text-highlighted' }, row.original.name),
        h('span', { class: 'text-xs text-muted' }, row.original.email),
      ])
    }
  },
  {
    accessorKey: "contact",
    header: "Contacto",
  },
  {
    accessorKey: "classification",
    header: "Grupo",
    cell: ({ row }) => {
      return h('div', { class: 'flex flex-col' }, [
        h('span', { class: 'font-medium text-highlighted' }, classificationMap[row.original.classification]),
        h('span', { class: 'text-xs text-muted' }, `${ row.original.num_elements } elemento(s)`),
      ])
    }
  },
  {
    accessorKey: "has_accommodation",
    header: () => h('div', { class: 'text-center w-full' }, 'Alojamento'),
    meta: { class: 'text-center' },
    cell: ({ row }) => {
      const value = row.original.has_accommodation

      const color = value ? 'success' : 'error'
      const label = value ? 'Sim' : 'Não'

      return h(
        'div',
        { class: 'flex justify-center' },
        h(UBadge, { class: 'capitalize, rounded-full', variant: 'subtle', color }, () => label)
      )
    }
  },
  {
    accessorKey: "has_meal",
    header: () => h('div', { class: 'text-center w-full' }, 'Refeição'),
    meta: { class: 'text-center' },
    cell: ({ row }) => {
      const value = row.original.has_meal

      const color = value ? 'success' : 'error'
      const label = value ? 'Sim' : 'Não'

      return h(
        'div',
        { class: 'flex justify-center' },
        h(UBadge, { class: 'capitalize, rounded-full', variant: 'subtle', color }, () => label)
      )
    }
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        h(UButton, {
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/volunteers/${row.original.id}`)
          }
        }),
        h(UButton, {
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          onClick: () => {
            selectedVolunteerById.value = row.original
            deleteModalOpen.value = true
          }
        })
      )
    }
  }
]

const pagination = ref({
  pageIndex: 0,
  pageSize: 10
})

const fetch = async() => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.value.pageIndex + 1,
      per_page: pagination.value.pageSize,
      filter: {}
    }
    if (search.value) {
      params.filter.search = search.value
    }
    if (classificationFilter.value !== 'all') {
      params.filter.classification = classificationFilter.value
    }
    if (accommodationFilter.value !== 'all') {
      params.filter.has_accommodation = accommodationFilter.value
    }
    if (mealFilter.value !== 'all') {
      params.filter.has_meal = mealFilter.value
    }
    const res = await api.getVolunteers(params)

    volunteers.value = res.data.data
    total.value = res.data.meta.total
    pagination.value.pageSize = res.data.meta.per_page
  } catch (e) {
    console.error("Erro ao carregar entidades: ", e)
  } finally {
    loading.value = false
  }
}

watch(pagination, fetch, {deep: true})

watch([search, accommodationFilter, mealFilter, classificationFilter], () => {
  pagination.value.pageIndex = 0
  fetch()
})

onMounted(fetch)
</script>

<template>
  <UDashboardPanel id="voluntario">
    <template #header>
      <UDashboardNavbar title="Voluntários">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <VolunteersAddModal @created="fetch" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar voluntários..."
        />
        <div class="flex flex-wrap items-center gap-1.5">
          <USelect
            v-model="classificationFilter"
            :items="[
              { label: 'Classificação', value: 'all' },
              { label: 'Individual', value: 'single' },
              { label: 'Organização', value: 'org' },
              { label: 'Outro', value: 'misc' }
            ]"
          />
          <USelect
            v-model="accommodationFilter"
            :items="[
              { label: 'Alojamento', value: 'all' },
              { label: 'Sim', value: true },
              { label: 'Não', value: false }
            ]"
          />
          <USelect
            v-model="mealFilter"
            :items="[
              { label: 'Refeição', value: 'all' },
              { label: 'Sim', value: true },
              { label: 'Não', value: false }
            ]"
          />
        </div>
      </div>
      <div class="overflow-x-auto">
        <UTable
          :data="volunteers"
          :columns="columns"
          :loading="loading"
          v-model:pagination="pagination"
          :pagination-options="{
            getPaginationRowModel: getPaginationRowModel(),
            rowCount: total,
            manualPagination: true,
          }"
          :ui="{
            base: 'table-fixed border-separate border-spacing-0',
            thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
            tbody: '[&>tr]:last:[&>td]:border-b-0',
            th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
            td: 'border-b border-default',
            separator: 'h-0'
          }"
          class="w-full min-w-[640px]"
        />
      </div>
      <div class="flex justify-end border-t border-default pt-4 mt-auto">
        <UPagination
          :page="pagination.pageIndex + 1"
          :items-per-page="pagination.pageSize"
          :total="total"
          @update:page="(p) => (pagination.pageIndex = p - 1)"
        />
      </div>
      <VolunteersDeleteModal
        v-if="selectedVolunteerById"
        v-model:open="deleteModalOpen"
        :id="selectedVolunteerById?.id"
        :team_identification="selectedVolunteerById?.team_identification"
        @deleted="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
