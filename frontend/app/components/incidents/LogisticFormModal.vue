<script setup lang="ts">
import * as z from 'zod'
import { usePaginatedSelect } from '@/composables/usePaginatedSelect'
import { useApiStore } from '@/stores/api'
import type { FormSubmitEvent } from '@nuxt/ui'

const props = defineProps<{
  open: boolean
  modelValue?: {
    id: number
    human_count: number
    vehicle_count: number
    entity: { id: number; name: string }
  } | null
}>()

const api = useApiStore()

const emit = defineEmits(['update:open', 'save'])

const isEditing = computed(() => !!props.modelValue)

const selectOptionSchema = z.object({
  id: z.number(),
  name: z.string()
})

const schema = z.object({
  human_count: z.coerce.number().min(0),
  vehicle_count: z.coerce.number().min(0),
  entity_id: selectOptionSchema.nullable().refine((v) => v !== null, { message: 'A entidade é obrigatória' })
})

type Schema = z.output<typeof schema>

const defaultState = () => ({
  human_count: 0,
  vehicle_count: 0,
  entity_id: null as any
})

const state = reactive<Partial<Schema>>(defaultState())

const entitiesMenu = useTemplateRef('entitiesMenu')

const entities = usePaginatedSelect({
  fetcher: api.getEntities,
  menuRef: entitiesMenu,
  map: (e: any) => ({ id: e.id, name: e.name })
})

watch(() => props.open, (open) => {
  if (open && props.modelValue) {
    Object.assign(state, {
      human_count: props.modelValue.human_count,
      vehicle_count: props.modelValue.vehicle_count,
      entity_id: props.modelValue.entity ? { id: Number(props.modelValue.entity.id), name: props.modelValue.entity.name } : null
    })
  }
  else if (open) {
    Object.assign(state, defaultState())
  }
  else {
    Object.assign(state, defaultState())
  }
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  const payload = {
    ...event.data,
    entity_id: event.data.entity_id?.id
  }

  emit('save', payload)
}

onMounted(async () => {
  await entities.fetchItems()
})
</script>

<template>
  <UModal
    :open="props.open"
    :title="isEditing ? 'Editar Equipa' : 'Nova Equipa'"
    :description="isEditing ? 'Edite os dados da equipa' : 'Preencha os dados da equipa'"
    @update:open="emit('update:open', $event)"
  >
    <template #body>
      <UForm :state="state" :schema="schema" @submit="onSubmit">
        <div class="space-y-4 p-4">
          <UFormField label="Entidade" name="entity_id" required>
            <USelectMenu
              ref="entitiesMenu"
              v-model="state.entity_id"
              v-model:search-term="entities.search.value"
              :items="entities.items.value"
              :loading="entities.loading.value"
              label-key="name"
              class="w-full"
              placeholder="Selecionar entidade"
            />
          </UFormField>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <UFormField label="Nº de Veículos" name="vehicle_count" required>
              <UInputNumber v-model="state.vehicle_count" class="w-full" :min="0" />
            </UFormField>
            <UFormField label="Nº de Operacionais" name="human_count" required>
              <UInputNumber v-model="state.human_count" class="w-full" :min="0" />
            </UFormField>
          </div>
        </div>
        <div class="flex justify-between gap-4 pt-4">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="subtle"
            class="flex-1 justify-center"
            @click="emit('update:open', false)"
          />
          <UButton
            :label="isEditing ? 'Atualizar' : 'Guardar'"
            color="primary"
            type="submit"
            class="flex-1 justify-center"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
