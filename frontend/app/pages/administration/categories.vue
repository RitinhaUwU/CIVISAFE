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
  name: string;
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
    accessorKey: "name",
    header: "Nome",
  },
  {
    accessorKey: "updated_at",
    header: "Última Atualização",
    cell: ({row}) => {
      return new Date(row.getValue("updated_at")).toLocaleString("pt-PT");
    },
  },
];

const pagination = ref({
  pageIndex: 0,
  pageSize: 10,
});

const fetch = async() => {
  loading.value = true;
  try {
    const res = await api.getCategories({
      page: pagination.value.pageIndex + 1,
      per_page: pagination.value.pageSize,
    });
    categories.value = res.data.data;
    total.value = res.data.meta.total;

    pagination.value.pageSize = res.data.meta.per_page;
  } catch (e) {
    console.error("Erro ao carregar categorias: ", e);
  } finally {
    loading.value = false;
  }
}

watch(pagination, fetch, {deep: true});

onMounted(fetch);
</script>

<template>
  <UPageCard
    title="Categorias"
    description="Lista de todas as categorias (tipos) de ocorrências."
    variant="naked"
    orientation="horizontal"
    class="mb-4"
  >
    <UButton
      label="Nova Categoria"
      color="neutral"
      type="submit"
      class="w-fit lg:ms-auto"
    />

    <div class="w-full space-y-4 pb-4">
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
        :ui="{ root: 'w-full' }"
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
