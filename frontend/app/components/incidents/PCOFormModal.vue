<script setup lang="ts" xmlns="http://www.w3.org/1999/html">
import { computed, watch } from 'vue'
import * as z from 'zod'
import {toDatetimeLocal} from "@/utils"
import type {FormSubmitEvent} from "@nuxt/ui";

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
  resp_pco: z.string().min(1, 'Nome demasiado curto'),
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

type Schema = z.output<typeof schema>

const defaultForm = {
  function_pco: '',
  resp_pco: '',
  category_pco: '',
  contact1_pco: '',
  contact2_pco: '',
  localization_pco: '',
  rob_pco: '',
  srp_pco: '',
  activation_pco_datetime: '',
  start_pco_datetime: toDatetimeLocal(new Date().toISOString()),
  end_pco_datetime: '',
}

const state = reactive<Partial<Schema>>({ ...defaultForm })

function resetForm() {
  Object.assign(state, defaultForm)
}

const isEditMode = computed(() => !!props.modelValue?.id)

watch(() => [props.modelValue, props.open], ([val, open]) => {
    if (!open) return resetForm()

    if (val?.id) {
      Object.assign(state, {
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
const formRef = ref()

const close = () => emit('update:open', false)

async function onSubmit(event: FormSubmitEvent<Schema>) {
  emit('save', event.data)
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
      content: 'sm:max-w-6xl'
    }"
  >
    <template #body>
      <UForm ref="formRef" :schema="schema" :state="state" class="space-y-4" @submit="onSubmit">
        <div class="space-y-4 p-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <UFormField label="Função" name="function_pco" required>
              <USelect
                v-model="state.function_pco"
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
            <UFormField label="Responsável" name="resp_pco" required>
              <UInput v-model="state.resp_pco" class="w-full" />
            </UFormField>
            <UFormField label="Categoria" name="category_pco" required>
              <UInput v-model="state.category_pco" class="w-full" />
            </UFormField>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <UFormField label="Contacto 1" name="contact1_pco" required>
              <UInput v-model="state.contact1_pco" class="w-full" />
            </UFormField>
            <UFormField label="Contacto 2" name="contact2_pco">
              <UInput v-model="state.contact2_pco" class="w-full" />
            </UFormField>
            <UFormField label="Localização" name="localization_pco">
              <UInput v-model="state.localization_pco" class="w-full" />
            </UFormField>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <UFormField label="ROB" name="rob_pco">
              <UInput v-model="state.rob_pco" class="w-full" />
            </UFormField>
            <UFormField label="SRP" name="srp_pco">
              <UInput v-model="state.srp_pco" class="w-full" />
            </UFormField>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <UFormField label="Data Ativação" name="activation_pco_datetime">
              <UInput type="datetime-local" v-model="state.activation_pco_datetime" class="w-full" />
            </UFormField>
            <UFormField label="Data Início" name="start_pco_datetime" required>
              <UInput type="datetime-local" v-model="state.start_pco_datetime" class="w-full" />
            </UFormField>
            <UFormField label="Data Fim" name="end_pco_datetime">
              <UInput type="datetime-local" v-model="state.end_pco_datetime" class="w-full" />
            </UFormField>
          </div>
        </div>
      </UForm>
    </template>
    <template #footer>
      <div class="flex justify-end gap-2 w-full">
        <UButton label="Cancelar" color="neutral" variant="soft" @click="close" />
        <UButton :label="modelValue?.id ? 'Guardar' : 'Adicionar'" color="primary" @click="formRef.submit()" />
      </div>
    </template>
  </UModal>
</template>
