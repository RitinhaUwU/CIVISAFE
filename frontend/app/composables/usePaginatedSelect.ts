import { ref, nextTick } from 'vue'
import { watchDebounced, useInfiniteScroll } from '@vueuse/core'

interface CursorPaginatedResponse<T> {
  data: T[]
  next_cursor: string | null
  prev_cursor: string | null
}

interface UsePaginatedSelectOptions<T, Mapped> {
  fetcher: (params: any) => Promise<CursorPaginatedResponse<T>>
  map: (item: T) => Mapped
  menuRef: any
  filters?: () => Record<string, any>
}

export function usePaginatedSelect<T, Mapped>({ fetcher, map, menuRef, filters }: UsePaginatedSelectOptions<T, Mapped>) {
  const items = ref<Mapped[]>([])
  const nextCursor = ref<string | null>(null)
  const prevCursor = ref<string | null>(null)
  const loading = ref(false)
  const search = ref('')
  const prepended = ref<any[]>([])

  const prependSelected = (itemsToAdd: any[]) => {
    const existingIds = new Set(items.value.map((i: any) => i.id))
    const toAdd = itemsToAdd.filter(i => !existingIds.has(i.id))

    prepended.value = [...prepended.value, ...toAdd]
    items.value = [...toAdd, ...items.value]
  }

  const fetchItems = async (loadMore = false) => {
    if (loading.value) return

    loading.value = true

    try {
      const res = await fetcher({
        per_page: 10,
        cursor: loadMore ? nextCursor.value : null,
        filter: {
          ...(search.value ? {search: search.value} : {}),
          ...(filters?.() ?? {})
        }
      })

      nextCursor.value = res.data.meta.next_cursor
      prevCursor.value = res.data.meta.prev_cursor

      const mapped = res.data.data.map(map)

      if (loadMore) {
        const existingIds = new Set(items.value.map((i: any) => i.id))
        const merged = mapped.filter((i: any) => !existingIds.has(i.id))
        items.value.push(...merged)
      } else {
        const prependedIds = new Set(prepended.value.map((i: any) => i.id))
        const merged = mapped.filter((i: any) => !prependedIds.has(i.id))
        items.value = [...prepended.value]
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

    nextCursor.value = null
    prevCursor.value = null

    await fetchItems(false)
  }

  watchDebounced(search, async () => {
    await reset()
  }, { debounce: 300 })

  watch(() => menuRef.value?.viewportRef, async (el) => {
    if (!el) return

    await nextTick()

    useInfiniteScroll(
      el,
      async () => {
        if (!nextCursor.value) return
        await fetchItems(true)
      },
      {
        distance: 10,
        canLoadMore: () => !loading.value && !!nextCursor.value
      }
    )
  }, { immediate: true })

  return {
    items,
    loading: computed(() => loading.value),
    search,
    reset,
    fetchItems,
    prependSelected
  }
}
