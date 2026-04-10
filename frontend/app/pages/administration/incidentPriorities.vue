<script setup lang="ts">
import {useApiStore} from "@/stores/api";
import type {TableColumn} from "@nuxt/ui";
import {getPaginationRowModel} from "@tanstack/table-core";

const api = useApiStore();
const priorities = ref<Priority[]>([]);
const loading = ref(false);
const total = ref(0);

type Priority = {
  id: number;
  name: string;
  description: string;
  hex_color: string;
  is_active: boolean;
};

const columns: TableColumn<Priority>[] = [
  {
    accessorKey: "name",
    header: "Nome",
  },
  {
    accessorKey: "description",
    header: "Descrição",
  },
  {
    accessorKey: "is_active",
    header: "Ativa?",
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
            console.log('Editar', row.original)
          }
        }),
        h(UButton, {
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          onClick: () => {
            console.log('Apagar', row.original)

            toast.add({
              title: 'Customer deleted',
              description: 'The customer has been deleted.'
            })
          }
        })
      )
    }
  }
];

const pagination = ref({
  pageIndex: 0,
  pageSize: 10,
});

const fetch = async() => {
  loading.value = true;
  try {
    const res = await api.getIncidentPriorities({
      page: pagination.value.pageIndex + 1,
      per_page: pagination.value.pageSize,
    });
    priorities.value = res.data.data;
    total.value = res.data.meta.total;

    pagination.value.pageSize = res.data.meta.per_page;
  } catch (e) {
    console.error("Erro ao carregar prioridades: ", e);
  } finally {
    loading.value = false;
  }
}

watch(pagination, fetch, {deep: true});

onMounted(fetch);
</script>

<template>
  <UDashboardPanel id="prioridades-ocorrencias">
    <template #body>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Prioridades de Ocorrências</h2>
          <p class="text-sm text-muted max-w-md">Lista de todas os Tipos de Prioridades.</p>
        </div>
        <UButton
          icon="i-lucide-plus"
          label="Nova Prioridade"
          color="primary"
        />
      </div>

      <div class="overflow-x-auto">
        <UTable
          :data="priorities"
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
          class="w-full min-w-[500px]"
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
    </template>
  </UDashboardPanel>
</template>
