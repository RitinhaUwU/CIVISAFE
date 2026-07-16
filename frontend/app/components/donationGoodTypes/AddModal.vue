<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '@/stores/api'
import {useAuthStore} from "~/stores/auth";

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  is_type_countable: z.boolean(),
  unit: z.string().optional().nullable(),
  danger_level: z.number().min(1).optional().nullable(),
}).superRefine((data, ctx) => {
  if (data.is_type_countable) {
    if (!data.unit) {
      ctx.addIssue({
        code: 'custom',
        path: ['unit'],
        message: 'O campo Unidade é obrigatório quando o tipo tem uma Unidade associada.',
      });
    }
  }
});

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  is_type_countable: false,
  unit: '',
  danger_level: null,
})

watch(state, () => {
  if(!state.is_type_countable)
  {
    state.unit = null;
    state.danger_level = null;
  }
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  if (!useAuthStore().hasPermission('DONATION_GOODS_TYPES_CREATE')) return;

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  try {
    await apiStore.createDonationGoodType(event.data)

    emit('created')
    open.value = false
    toast.add({
      title: 'Sucesso',
      description: 'Tipo de Bem criado com sucesso',
      color: 'success'
    })

    Object.assign(state, {
      name: '',
      is_type_countable: false,
      unit: '',
      danger_level: null,
    })

  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao criar Tipo de Bem',
      color: 'error'
    })
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Novo Tipo de Bem Doável" description="Adicione um Novo Tipo de Bem Doável">
    <UButton
      icon="i-lucide-plus"
      label="Novo Bem Doável"
      color="primary"
    />
    <template #body>
      <UForm :state="state" :schema="schema" class="space-y-5" @submit="onSubmit">
        <UFormField label="Nome" name="name">
          <UInput v-model="state.name" class="w-full" />
        </UFormField>
        <UFormField
          label="Tem Unidade Associada?"
          description="Se o Tipo de Bem é quantificável/medível"
          name="is_type_countable">
          <div class="flex items-center gap-3">
            <USwitch
              v-model="state.is_type_countable"
              checked-icon="i-lucide-check"
              unchecked-icon="i-lucide-x"
            />
            <span class="text-sm font-medium">{{ state.is_type_countable ? 'Sim' : 'Não' }}</span>
          </div>
        </UFormField>
        <template v-if="state.is_type_countable">
          <UFormField label="Unidade" name="unit">
            <USelect
              v-model="state.unit"
              class="w-full"
              :items="[
                { label: 'Litros', value: 'liters' },
                { label: 'Quilos', value: 'kilos' },
                { label: 'Unidades', value: 'units' },
                { label: 'Metros', value: 'linear_meters' },
                { label: 'Metros Quadrados', value: 'squared_meters' }
              ]"
            />
          </UFormField>
          <UFormField label="Número mínimo" description="(Opcional) Quantidade crítica para mostrar alertas na dashboard" name="danger_level">
            <div class="flex flex-row gap-2">
              <UInputNumber v-model="state.danger_level" class="w-full" :min="1" />
              <UButton label="Limpar" @click="state.danger_level=null"></UButton>
            </div>
          </UFormField>
        </template>
        <div class="flex flex-col-reverse sm:flex-row justify-between gap-3 pt-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="subtle"
            class="flex-1 justify-center"
            @click="open = false"
          />
          <UButton
            label="Guardar"
            color="primary"
            type="submit"
            class="flex-1 justify-center"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
