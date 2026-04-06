<script setup lang="ts">
import { useApiStore } from '@/stores/api';
import type {TableColumn} from "@nuxt/ui";
import {getPaginationRowModel} from "@tanstack/table-core";

const api = useApiStore();
const entities = ref<Entity[]>([]);
const loading = ref(false);
const total = ref(0);

const deleteModalOpen = ref(false)
const selectedEntityId = ref<number>(null)

type Entity = {
  id: number;
  name: string;
  description: string;
  phone_contact: string;
  email_contact: string;
  address: string;
  logo: string;
  poc_name: string;
  poc_phone: string;
  poc_email: string;
  created_at: Date;
  updated_at: Date;
};

const columns: TableColumn<Entity>[] = [
  {
    accessorKey: "name",
    header: "Nome",
  },
  {
    accessorKey: "phone_contact",
    header: "Telefone",
  },
  {
    accessorKey: "poc_name",
    header: "Nome do Responsável",
  },
  {
    accessorKey: "poc_phone",
    header: "Telefone Responsável",
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
            navigateTo(`/administration/entities/${row.original.id}`)
          }
        }),
        h(UButton, {
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          onClick: () => {
            selectedEntityId.value = row.original.id
            deleteModalOpen.value = true

            toast.add({
              title: 'Uma Entidade foi eliminada',
              description: ''
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
    const res = await api.getEntities({
      page: pagination.value.pageIndex + 1,
      per_page: pagination.value.pageSize,
    });
    entities.value = res.data.data;
    total.value = res.data.meta.total;

    pagination.value.pageSize = res.data.meta.per_page;
  } catch (e) {
    console.error("Erro ao carregar entidades: ", e);
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
          <h2 class="text-lg font-semibold">Entidades</h2>
          <p class="text-sm text-muted max-w-md">Lista de todas as Entidades.</p>
        </div>


        <EntitiesAddModal />
      </div>
    </template>

    <div class="w-full">
      <UTable
        :data="entities"
        :columns="columns"
        :loading="loading"
        v-model:pagination="pagination"
        :pagination-options="{
        getPaginationRowModel: getPaginationRowModel(),
        rowCount: total,
        manualPagination: true,
      }"
        class="w-full"
        :ui="{
          base: 'table-fixed border-separate border-spacing-0',
          thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
          tbody: '[&>tr]:last:[&>td]:border-b-0',
          th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
          td: 'border-b border-default',
          separator: 'h-0'
        }"
      />

      <div class="flex justify-end border-t border-default pt-4">
        <UPagination
          :page="pagination.pageIndex + 1"
          :items-per-page="pagination.pageSize"
          :total="total"
          @update:page="(p) => (pagination.pageIndex = p - 1)"
        />
      </div>
    </div>
    <EntitiesDeleteModal
      v-model:open="deleteModalOpen"
      :id="selectedEntityId"
      @deleted="fetch"
    />
  </UPageCard>
</template>
