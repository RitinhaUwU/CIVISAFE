<script setup lang="ts">
import type {DonationLog} from "~/types";
import type {TableColumn} from "@nuxt/ui";
import {UButton} from "#components";
import {useApiStore} from "~/stores/api";
import {useToast} from "@nuxt/ui/composables";

const searchBind = defineModel();

const donations = ref<DonationLog[]>([])
const nextCursor = ref<string|null>(null)
const loading = ref(false)

const columns: TableColumn<DonationLog>[] = [
  {
    accessorKey: "date",
    header: "Data"
  },
  {
    accessorKey: "name",
    header: "Nome",
    cell: ({row}) => {
      return h('div', {class: 'flex flex-col'}, [
        h('span', {class: 'font-medium text-highlighted'}, row.original.name),
        h('span', {class: 'text-xs text-muted'}, row.original.email),
      ])
    }
  },
  {
    accessorKey: "donor_type",
    header: "Tipo de Doador",
    cell: ({row}) => {
      switch (row.original.donor_type) {
        case "single":
          return h('div', {class: 'flex flex-col'}, "Pessoa Singular")

        case "company":
          return h('div', {class: 'flex flex-col'}, "Empresa")

        case "org":
          return h('div', {class: 'flex flex-col'}, "ONG")

        case "misc":
          return h('div', {class: 'flex flex-col'}, "Outro")

        default:
          return h('div', {class: 'flex flex-col'}, row.original.donor_type)
      }
    }
  },
  {
    accessorKey: "contact",
    header: "Telefone",
  },
  {
    id: 'actions',
    cell: ({row}) => {
      return h(
        'div',
        {class: 'text-right'},
        //@ts-ignore
        h(UButton, {
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/donations/${row.original.id}`)
          }
        })
      )
    }
  }
]

const fetch = async (loadMore: boolean = false) => {
  if (loading.value) return

  loading.value = true
  try {
    const params: any = {
      per_page: 10,
      ...(loadMore && nextCursor.value ? { cursor: nextCursor.value } : {})
    }

    if (searchBind.value) {
      params.filter = {
        search: searchBind.value
      }
    }
    const res = await useApiStore().getDonationLogs(params)

    if(loadMore)
    {
      const existing = new Set(donations.value.map(d => d.id))
      donations.value.push(...res.data.data.filter((d: DonationLog) => !existing.has(d.id)))
    }
    else
    {
      donations.value = res.data.data
    }

    nextCursor.value = res.data.meta.next_cursor
  } catch (e) {
    useToast().add({
      title: 'Erro',
      description: 'Erro ao carregar as doações',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}

watchDebounced(searchBind, async () => {
  nextCursor.value = null
  donations.value = []
  await fetch(false)
}, {debounce: 300})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {
  fetch()

  useInfiniteScroll(
    scrollContainer,
    () => {
      if(nextCursor.value == null) return;
      fetch(true)
    },
    {
      distance: 200,
      canLoadMore: () => !loading.value && nextCursor.value !== null
    }
  )
})
</script>

<template>
  <UEmpty
    v-if="donations.length == 0"
    icon="i-lucide-book-open"
    title="Sem Doações Registadas"
    description="De momento não existem quaisquer doações registadas"
    variant="naked"
    class="h-80"
  />
  <div v-else ref="scrollContainer" class="overflow-x-auto max-h-150 overflow-y-auto">
    <UTable
      :data="donations"
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
      class="w-full"
    />
  </div>
</template>

<style scoped>

</style>
