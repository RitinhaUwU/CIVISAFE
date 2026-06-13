<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import type {Facilities} from "@/types";
import {UButton} from "#components";
import type {BreadcrumbItem} from "@nuxt/ui/components/Breadcrumb.vue";
import {useRoute} from "nuxt/app";

const toast = useToast()
const api = useApiStore()

const facility = ref<Facilities>()
const loading = ref(false)

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
          disabled: !useAuthStore().hasPermission('FACILITIES_FILES_DELETE'),
          onClick: () => {
            //TODO: Implementar funcionalidade de eliminar o ficheiro
          }
        })
      )
    }
  }
]

const fetch = async() => {
  if(!useAuthStore().hasPermission('FACILITIES_LIST')){
    await useRouter().push('/inicio');
    return;
  }

  try{
    const routeID = useRoute().params.id;
    if (typeof routeID !== 'string') {
      toast.add({
        title: 'Utilizador inválido',
        description: 'O Caminho que o trouxe aqui aponta para um utilizador inválido',
        color: 'error'
      });
      await useRouter().push('/users');
      return;
    }

    facility.value = (await api.getFacility(parseInt(routeID))).data.data
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
      <UDashboardNavbar :title="`Ficheiros - ${facility?.name}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <!-- TODO: Implementar upload de ficheiros -->
          <UButton
            label="Adicionar Ficheiro"
            icon="i-lucide-plus"
            color="primary"
            v-if="!useAuthStore().hasPermission('FACILITIES_FILES_UPLOAD')"
          />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UBreadcrumb :items="items" />
      <div class="overflow-x-auto">
        <UTable
          :data="facility"
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
