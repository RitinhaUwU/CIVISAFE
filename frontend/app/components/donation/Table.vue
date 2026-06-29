<script setup lang="ts">
import type {DonationLog} from "~/types";
import type {TableColumn} from "@nuxt/ui";
import {UButton} from "#components";
import {useApiStore} from "~/stores/api";
import {useToast} from "@nuxt/ui/composables";

const searchBind = defineModel();

const donations = ref<DonationLog[]>([])
const page = ref(1)
const lastPage = ref<number>(Infinity)
const loading = ref(false)
const total = ref(0)

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

const fetch = async () => {
  if (loading.value) return
  if (page.value > lastPage.value) return

  loading.value = true
  try {
    const params: any = {
      page: page.value,
      per_page: 10
    }

    if (searchBind.value) {
      params.filter = {
        search: searchBind.value
      }
    }
    const res = await useApiStore().getDonationLogs(params)

    donations.value.push(...res.data.data)
    total.value = res.data.meta.total
    lastPage.value = res.data.meta.last_page
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

watchDebounced(searchBind, () => {
  page.value = 1
  lastPage.value = Infinity
  donations.value = []
  fetch()
}, {debounce: 300})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {
  fetch()

  useInfiniteScroll(
    scrollContainer,
    () => {
      page.value++
      fetch()
    },
    {
      distance: 200,
      canLoadMore: () => !loading.value && page.value < lastPage.value
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
