<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'

export interface SelectOption {
  id: number | string
  label: string
}

const props = withDefaults(defineProps<{
  modelValue: SelectOption | null
  items: SelectOption[]
  placeholder?: string
  label?: string
  required?: boolean
}>(), {
  placeholder: 'Selecionar...',
  label: '',
  required: false
})

const emit = defineEmits<{
  'update:modelValue': [value: SelectOption | null]
  'create': [option: SelectOption]
}>()

const query = ref('')
const open = ref(false)
const searchInput = ref<HTMLInputElement | null>(null)
const wrapper = ref<HTMLDivElement | null>(null)

const filteredItems = computed(() =>
  query.value
    ? props.items.filter(i =>
        i.label.toLowerCase().includes(query.value.toLowerCase())
      )
    : props.items
)

async function toggle() {
  open.value = !open.value
  if (open.value) {
    await nextTick()
    searchInput.value?.focus()
  }
}

function close() {
  open.value = false
  query.value = ''
}

function select(item: SelectOption) {
  emit('update:modelValue', item)
  close()
}

/* function create() {
  if (!query.value.trim()) return

  const newItem: SelectOption = {
    id: Date.now(),
    label: query.value.trim()
  }

  emit('create', newItem)
  emit('update:modelValue', newItem)
  close()
} */

function clickOutside(e: MouseEvent) {
  if (wrapper.value && !wrapper.value.contains(e.target as Node)) {
    close()
  }
}

onMounted(() => document.addEventListener('mousedown', clickOutside))
onUnmounted(() => document.removeEventListener('mousedown', clickOutside))
</script>

<template>
  <div class="flex flex-col w-full">
    <label v-if="label" class="text-xs font-medium text-default mb-1">
      {{ label }}
      <span v-if="required" class="text-error ml-1">*</span>
    </label>

    <div ref="wrapper" class="relative">
      <button
        type="button"
        class="w-full h-9 px-3 flex items-center justify-between
               border border-default
               bg-default
               rounded-md text-sm text-default
               transition
               hover:border-muted
               focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
        @click="toggle"
      >
        <span class="truncate">
          <template v-if="modelValue">
            {{ modelValue.label }}
          </template>
          <span class="text-muted" v-else>
            {{ placeholder }}
          </span>
        </span>

        <svg
          class="w-5 h-5 text-muted transition-transform"
          :class="{ 'rotate-180': open }"
          viewBox="0 0 20 20"
          fill="currentColor"
        >
          <path
            fill-rule="evenodd"
            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
            clip-rule="evenodd"
          />
        </svg>
      </button>

      <div
        v-if="open"
        class="absolute mt-1 w-full
               bg-elevated
               border border-default
               rounded-md shadow-lg z-[9999]"
      >
        <div class="p-2 border-b border-default">
          <input
            ref="searchInput"
            v-model="query"
            class="w-full text-sm outline-none bg-transparent text-default placeholder:text-muted"
            placeholder="Pesquisar..."
          >
        </div>

        <div class="max-h-48 overflow-y-auto p-1">
          <div
            v-for="item in filteredItems"
            :key="item.id"
            class="px-3 py-2 text-sm cursor-pointer rounded
                   text-default
                   hover:bg-muted/50"
            :class="{
              'bg-primary/10 text-primary': modelValue?.id === item.id
            }"
            @click="select(item)"
          >
            {{ item.label }}
          </div>

          <div
            v-if="filteredItems.length === 0"
            class="text-center text-xs text-muted py-2"
          >
            Nenhum resultado
          </div>
        </div>

        <div class="border-t border-default p-1">
          <button
            class="w-full text-left px-3 py-2 text-sm
                   text-primary
                   rounded
                   hover:bg-primary/10"
          >
            Adicionar "{{ query }}"
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
