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
  terminates_incident: boolean;
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
    accessorKey: "terminates_incident",
    header: "Termina Ocorrência?",
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
    const res = await api.getIncidentStates({
      page: pagination.value.pageIndex + 1,
      per_page: pagination.value.pageSize,
    });
    priorities.value = res.data.data;
    total.value = res.data.meta.total;

    pagination.value.pageSize = res.data.meta.per_page;
  } catch (e) {
    console.error("Erro ao carregar estados: ", e);
  } finally {
    loading.value = false;
  }
}

watch(pagination, fetch, {deep: true});

onMounted(fetch);
</script>

<template>
  <UPageCard
    variant="naked"
    class="mb-4 w-full max-w-none"
    :ui="{
      container: 'w-full max-w-none',
      header: 'w-full',
      wrapper: 'w-full flex-row items-center justify-between'
    }"
  >

    <template #header>
      <div class="flex items-center justify-between w-full gap-4">
        <div>
          <h2 class="text-lg font-semibold">Estados de Ocorrências</h2>
          <p class="text-sm text-muted max-w-md">Lista de todas os Tipos de Estado de Ocorrências.</p>
        </div>

        <UButton
          icon="i-lucide-plus"
          label="Novo Estado"
          type="submit"
          color="primary"
        />
      </div>
    </template>

    <div class="w-full space-y-4 pb-4">
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
        class="w-full"
      />

      <div class="flex justify-end border-t border-default pt-4 px-4">
        <UPagination
          :page="pagination.pageIndex + 1"
          :items-per-page="pagination.pageSize"
          :total="total"
          @update:page="(p) => (pagination.pageIndex = p - 1)"
        />
      </div>
    </div>

  </UPageCard>
</template>
