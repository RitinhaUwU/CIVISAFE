<script setup lang="ts">
import * as z from 'zod'

const props = defineProps<{
  open: boolean
  modelValue?: any
  entities?: any[]
}>()

const emit = defineEmits<{
  (e: 'update:open', v: boolean): void
  (e: 'save', payload: any): void
}>()

const selectOptionSchema = z.object({
  id: z.number(),
  name: z.string()
})

const schema = z.object({
  human_count: z.coerce.number().min(0),
  vehicle_count: z.coerce.number().min(0),
  entity_id: selectOptionSchema.nullable().refine(v => v !== null, {message: 'A entidade é obrigatória'})})

const form = reactive({
  human_count: 0,
  vehicle_count: 0,
  entity_id: null as any
})

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      Object.assign(form, {
        human_count: val.human_count ?? 0,
        vehicle_count: val.vehicle_count ?? 0,
        entity_id: val.entity ? {id: val.entity.id, name: val.entity.name} : null
      })
    } else {
      Object.assign(form, {
        human_count: 0,
        vehicle_count: 0,
        entity_id: null
      })
    }
  },
  { immediate: true }
)

const toast = useToast()
const close = () => emit('update:open', false)

const submit = () => {
  const result = schema.safeParse(form)

  if (!result.success) {
    result.error.issues.forEach((err) => {
      toast.add({
        title: 'Erro de validação',
        description: err.message,
        color: 'error'
      })
    })
    return
  }

  emit('save', {
    ...result.data,
    entity_id: result.data.entity_id?.id
  })

  close()
}
</script>

<template>
  <UModal
    :open="props.open"
    :title="modelValue?.id ? 'Editar Equipa' : 'Nova Equipa'"
    description="Preencha os dados da equipa"
    @update:open="emit('update:open', $event)"
  >
    <template #body>
      <div class="space-y-4 p-4">
        <UFormField label="Entidade" name="entity_id">
          <USelectMenu
            v-model="form.entity_id"
            :items="props.entities"
            label-key="name"
            class="w-full"
            placeholder="Selecionar entidade"
          />
        </UFormField>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UFormField label="Nº de Veículos" name="vehicle_count">
            <UInputNumber v-model="form.vehicle_count" class="w-full" />
          </UFormField>
          <UFormField label="Nº de Humanos" name="human_count">
            <UInputNumber v-model="form.human_count" class="w-full" />
          </UFormField>
        </div>
      </div>
      <div class="flex justify-between gap-4 pt-4">
        <UButton
          label="Cancelar"
          color="neutral"
          variant="subtle"
          class="flex-1 justify-center"
          @click="close"
        />
        <UButton
          label="Guardar"
          color="primary"
          @click="submit"
          class="flex-1 justify-center"
        />
      </div>
    </template>
  </UModal>
</template>
