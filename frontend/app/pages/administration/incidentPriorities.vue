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
  <UPageCard
    title="Prioridades de Ocorrências"
    description="Lista de todas as Entidades."
    variant="naked"
    orientation="horizontal"
    class="mb-4"
  >
    <UButton
      label="Novo Tipo de Prioridade"
      color="neutral"
      type="submit"
      class="w-fit lg:ms-auto"
    />

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
