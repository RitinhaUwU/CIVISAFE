import { ref } from 'vue'
import { watchDebounced, useInfiniteScroll } from '@vueuse/core'

interface PaginatedResponse<T> {
  data: {
    data: T[]
    meta: { last_page: number }
  }
}

interface UsePaginatedSelectOptions<T, Mapped> {
  fetcher: (params: any) => Promise<PaginatedResponse<T>>
  map: (item: T) => Mapped
  menuRef: any
  filters?: () => Record<string, any>
}

export function usePaginatedSelect<T, Mapped>({ fetcher, map, menuRef, filters }: UsePaginatedSelectOptions<T, Mapped>) {
  const items = ref<Mapped[]>([])
  const page = ref(1)
  const lastPage = ref(Infinity)
  const loading = ref(false)
  const search = ref('')
  const prepended = ref<any[]>([])

  const prependSelected = (itemsToAdd: any[]) => {
    const existingIds = new Set(items.value.map((i: any) => i.id))
    const toAdd = itemsToAdd.filter(i => !existingIds.has(i.id))

    prepended.value = [...toAdd]
    items.value = [...toAdd, ...items.value]
  }

  const fetchItems = async (loadMore = false) => {
    if (loading.value) return

    loading.value = true

    try {
      const res = await fetcher({
        page: page.value,
        per_page: 10,
        filter: {
          ...(search.value ? {search: search.value} : {}),
          ...(filters?.() ?? {})
        }
      })

      lastPage.value = res.data.meta.last_page
      const mapped = res.data.data.map(map)

      if (loadMore) {
        const existingIds = new Set(items.value.map((i: any) => i.id))
        const merged = mapped.filter((i: any) => !existingIds.has(i.id))
        items.value = [...items.value, ...merged]
      } else {
        const prependedIds = new Set(prepended.value.map((i: any) => i.id))
        const merged = mapped.filter((i: any) => !prependedIds.has(i.id))
        items.value = [...prepended.value, ...merged]
      }
    }
    finally {
      loading.value = false
    }
  }

  const reset = async (clearPrepended = false) => {
    if (clearPrepended) {
      prepended.value = []
    }

    items.value = [...prepended.value]
    page.value = 1
    lastPage.value = Infinity

    await fetchItems()
  }

  watchDebounced(search, async () => {
      page.value = 1
      await fetchItems()
    },
    { debounce: 300 } )

  useInfiniteScroll(
    () => menuRef.value?.viewportRef,
    async () => {
      if (page.value >= lastPage.value) return
      page.value++
      await fetchItems(true)
    },
    { canLoadMore: () => !loading.value && page.value < lastPage.value }
  )

  return {
    items,
    page,
    loading: computed(() => loading.value),
    search,
    reset,
    fetchItems,
    prependSelected
  }
}
