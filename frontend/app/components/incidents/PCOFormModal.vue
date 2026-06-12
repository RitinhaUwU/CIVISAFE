<script setup lang="ts">
import { computed, watch } from 'vue'
import * as z from 'zod'

const props = defineProps<{
  open: boolean
  modelValue?: any
}>()

const emit = defineEmits<{
  (e: 'update:open', v: boolean): void
  (e: 'save', payload: any): void
}>()

const schema = z.object({
  function_pco: z.string().min(1, 'Selecione uma função'),
  resp_pco: z.string().min(2, 'Nome demasiado curto'),
  category_pco: z.string().min(1, 'Categoria obrigatória'),
  contact1_pco: z.string().refine(value => !value || /^\+?[0-9]+(?: [0-9]+)*$/.test(value), 'Insira apenas números ou formato +000 000000000'),
  contact2_pco: z.string().refine(value => !value || /^\+?[0-9]+(?: [0-9]+)*$/.test(value), 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  localization_pco: z.string().optional().nullable(),
  rob_pco: z.string().optional().nullable(),
  srp_pco: z.string().optional().nullable(),
  activation_pco_datetime: z.string().optional().nullable(),
  start_pco_datetime: z.string().min(1, 'A data de início é obrigatória'),
  end_pco_datetime: z.string().optional().nullable(),
})

//https://stackoverflow.com/questions/30166338/setting-value-of-datetime-local-from-date
// Converte o ISO que vem da API para um objeto Date.
const toDatetimeLocal = (value?: string | null) => {
  if (!value) return ''

  return new Date(value).toISOString().slice(0, 16) // toISOString() -> Transforma a data em formato padrão
}

const form = reactive({
  function_pco: '',
  resp_pco: '',
  category_pco: '',
  contact1_pco: '',
  contact2_pco: '',
  localization_pco: '',
  rob_pco: '',
  srp_pco: '',
  activation_pco_datetime: '',
  start_pco_datetime: '',
  end_pco_datetime: '',
})

const resetForm = () => {
  Object.assign(form, {
    function_pco: '',
    resp_pco: '',
    category_pco: '',
    contact1_pco: '',
    contact2_pco: '',
    localization_pco: '',
    rob_pco: '',
    srp_pco: '',
    activation_pco_datetime: '',
    start_pco_datetime: '',
    end_pco_datetime: '',
  })
}

const isEditMode = computed(() => !!props.modelValue?.id)

watch(() => [props.modelValue, props.open], ([val, open]) => {
    if (!open) return resetForm()

    if (val?.id) {
      Object.assign(form, {
        function_pco: val.function_pco ?? '',
        resp_pco: val.resp_pco ?? '',
        category_pco: val.category_pco ?? '',
        contact1_pco: val.contact1_pco ?? '',
        contact2_pco: val.contact2_pco ?? '',
        localization_pco: val.localization_pco ?? '',
        rob_pco: val.rob_pco ?? '',
        srp_pco: val.srp_pco ?? '',
        activation_pco_datetime: toDatetimeLocal(val.activation_pco_datetime),
        start_pco_datetime: toDatetimeLocal(val.start_pco_datetime),
        end_pco_datetime: toDatetimeLocal(val.end_pco_datetime),
      })
    } else {
      resetForm()
    }
  }
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

  emit('save', result.data)
  close()
}
</script>

<template>
  <UModal
    :open="props.open"
    :title="modelValue?.id ? 'Editar Função no PCO' : 'Nova Função no PCO'"
    :description="modelValue?.id ? 'Atualize os dados da função operacional' : 'Preencha os dados da nova função operacional'"
    @update:open="emit('update:open', $event)"
    :ui="{
      content: 'sm:max-w-4xl'
    }"
  >
    <template #body>
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <UFormField label="Função" name="function_pco">
            <USelect
              v-model="form.function_pco"
              :items="[
                { label: 'COS', value: 'COS' },
                { label: 'Oficial Operações', value: 'Oficial Operações' },
                { label: 'Oficial Logística', value: 'Oficial Logística' },
                { label: 'Oficial Planeamento', value: 'Oficial Planeamento' },
                { label: 'Oficial Operações Aéreas', value: 'Oficial Operações Aéreas' },
                { label: 'Adjunto Segurança', value: 'Adjunto Segurança' },
                { label: 'Adjunto Relações Públicas', value: 'Adjunto Relações Públicas' },
                { label: 'Adjunto Ligação', value: 'Adjunto Ligação' }
              ]"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Responsável" name="resp_pco">
            <UInput v-model="form.resp_pco" class="w-full" />
          </UFormField>
          <UFormField label="Categoria" name="category_pco">
            <UInput v-model="form.category_pco" class="w-full" />
          </UFormField>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <UFormField label="Contacto 1" name="contact1_pco">
            <UInput v-model="form.contact1_pco" class="w-full" />
          </UFormField>
          <UFormField label="Contacto 2" name="contact2_pco">
            <UInput v-model="form.contact2_pco" class="w-full" />
          </UFormField>
          <UFormField label="Localização" name="localization_pco">
            <UInput v-model="form.localization_pco" class="w-full" />
          </UFormField>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <UFormField label="ROB" name="rob_pco">
            <UInput v-model="form.rob_pco" class="w-full" />
          </UFormField>
          <UFormField label="SRP" name="srp_pco">
            <UInput v-model="form.srp_pco" class="w-full" />
          </UFormField>
          <UFormField label="Data Ativação" name="activation_pco_datetime">
            <UInput type="datetime-local" v-model="form.activation_pco_datetime" class="w-full" />
          </UFormField>
          <UFormField label="Data Início" name="start_pco_datetime">
            <UInput type="datetime-local" v-model="form.start_pco_datetime" class="w-full" />
          </UFormField>
          <UFormField label="Data Fim" name="end_pco_datetime">
            <UInput type="datetime-local" v-model="form.end_pco_datetime" class="w-full" />
          </UFormField>
        </div>
      </div>
    </template>
    <template #footer>
      <div class="flex justify-end gap-2 w-full">
        <UButton label="Cancelar" color="neutral" variant="soft" @click="close" />
        <UButton :label="modelValue?.id ? 'Guardar' : 'Adicionar'" color="primary" @click="submit" />
      </div>
    </template>
  </UModal>
</template>
