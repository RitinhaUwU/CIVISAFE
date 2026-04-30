<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'

const toast = useToast()
const api = useApiStore()

const facilities = ref<Facilities[]>([])
const loading = ref(false)

type Facilities = {
  id: number;
  name: number;
  email: string;
  address: string;
  contact: string;
  description: boolean;
  created_at: Date;
  updated_at: Date;
}

const columns: TableColumn<Facilities | null>[] = [
  {
    accessorKey: "name",
    header: "Ficheiros",
  },
  {
    id: 'actions',
    cell: () => {
      return h(
        'div',
        { class: 'text-right' },
        h(UButton, {
          icon: 'i-lucide-x',
          color: 'error',
          variant: 'ghost',
          onClick: () => {

          }
        })
      )
    }
  }
]

const fetch = async() => {
  try{
    const res = await api.getFacilities()

    facilities.value = res.data.data
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao carregar os ficheiros',
      color: 'error'
    })
  }
}

const items = ref<BreadcrumbItem[]>([
  {
    label: 'Instalações',
    icon: 'i-lucide-building-2',
    to: '/facilities'
  },
  {
    label: 'Ficheiros',
    icon: 'i-lucide-files',
  }
])

onMounted(fetch)
</script>

<template>
  <UDashboardPanel id="ficheiros">
    <template #header>
      <UDashboardNavbar :title="`Ficheiros - ${facilities?.name || ''}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton label="Adicionar Ficheiro" icon="i-lucide-plus" color="primary" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UBreadcrumb :items="items" />
      <div class="overflow-x-auto">
        <UTable
          :data="facilities"
          :columns="columns"
          :loading="loading"
          :ui="{
            base: 'table-fixed border-separate border-spacing-0',
            thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
            tbody: '[&>tr]:last:[&>td]:border-b-0',
            th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
            td: 'border-b border-default',
            separator: 'h-0'
          }"
          class="w-full min-w-[500px]"
        />
      </div>
    </template>
  </UDashboardPanel>
</template>
