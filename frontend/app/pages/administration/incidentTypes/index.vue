<script setup lang="ts">
import {useApiStore} from "@/stores/api";
import type {TableColumn} from "@nuxt/ui";
import {getPaginationRowModel} from "@tanstack/table-core";

const api = useApiStore();
const categories = ref<Category[]>([]);
const loading = ref(false);
const total = ref(0);

type Category = {
  code: number;
  species: string;
  type: string;
  description: string;
  is_active: boolean;
  created_at: Date;
  updated_at: Date;
};

const columns: TableColumn<Category>[] = [
  {
    accessorKey: "code",
    header: "Código",
  },
  {
    accessorKey: "species",
    header: "Espécie"
  },
  {
    accessorKey: "type",
    header: "Tipo",
  },
  {
    accessorKey: "updated_at",
    header: "Última Atualização",
    cell: ({row}) => {
      return new Date(row.getValue("updated_at")).toLocaleString("pt-PT");
    },
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
    const res = await api.getIncidentTypes({
      page: pagination.value.pageIndex + 1,
      per_page: pagination.value.pageSize,
    });
    categories.value = res.data.data;
    total.value = res.data.meta.total;

    pagination.value.pageSize = res.data.meta.per_page;
  } catch (e) {
    console.error("Erro ao carregar tipos de ocorrências: ", e);
  } finally {
    loading.value = false;
  }
}

watch(pagination, fetch, {deep: true});

onMounted(fetch);
</script>

<template>
  <UDashboardPanel id="tipos-ocorrencias">
    <template #body>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Tipos de Ocorrências</h2>
          <p class="text-sm text-muted max-w-md">Lista de todas os Tipos de Ocorrências.</p>
        </div>
        <UButton
          icon="i-lucide-plus"
          label="Novo Tipo"
          color="primary"
        />
      </div>

      <div class="overflow-x-auto">
        <UTable
          :data="categories"
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
