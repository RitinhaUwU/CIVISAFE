<script setup lang="ts">
import * as z from 'zod'
import type {FormSubmitEvent} from '@nuxt/ui'
import {useApiStore} from '@/stores/api'
import {CalendarDate} from "@internationalized/date";
import {now} from "@vueuse/core";

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const goodsSchema = z.object({
  category: z.number(),
  quantity: z.number().min(0.1),
});

const schema = z.object({
  date: z.string().min(1, 'A Data é obrigatória'),
  name: z.string().min(1, 'O Nome é obrigatório'),
  contact: z.string().refine(
      value => !value || /^\+?[0-9]+(?: [0-9]+)*$/.test(value),
      'Insira apenas números ou formato +000 000000000'
  ).optional().nullable(),
  email: z.email().optional().nullable(),
  donor_type: z.string().min(1, 'O Tipo de Doador é obrigatório'),
  goods: z.array(goodsSchema).min(1, 'Adicione pelo menos 1 Bem')
});

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  date: new Date().toISOString().split('T')[0],
  name: '',
  contact: '',
  email: '',
  donor_type: '',
  goods: []
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
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
  <UModal
      v-model:open="open"
      title="Registar Doação"
      :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl' }"
  >
    <UButton
        icon="i-lucide-plus"
        label="Registar Doação"
        color="primary"
        size="xs"
    />
    <template #body>
      <UForm
          :state="state"
          :schema="schema"
          class="space-y-5"
          @submit="onSubmit"
      >
        <div class="grid grid-cols-2 gap-5">
          <UFormField label="Data de Receção" name="date" required>
            <UInput type="date" v-model="state.date" class="w-full"/>
          </UFormField>

          <UFormField label="Nome" name="name" required>
            <UInput v-model="state.name" class="w-full"/>
          </UFormField>
        </div>

        <div class="grid grid-cols-3 gap-5">
          <UFormField label="Contacto Telefónico" name="contact">
            <UInput v-model="state.contact" class="w-full"/>
          </UFormField>

          <UFormField label="Email" name="email">
            <UInput v-model="state.email" class="w-full"/>
          </UFormField>

          <UFormField label="Tipo de Doador" name="donor_type" required>
            <USelect v-model="state.donor_type" class="w-full" :items="[
              {
                label: 'Pessoa Singular',
                value: 'single',
              },
              {
                label: 'Organização',
                value: 'org'
              },
              {
                label: 'Diversos',
                value: 'misc'
              }
            ]"/>
          </UFormField>
        </div>

        <UCard title="Lista de Bens">

          <TransitionGroup name="slide" tag="div">

            <div class="grid grid-cols-2 gap-5" v-for="(item, index) in state.goods" :key="item.id">

              <UFormField label="Categoria" name="category" required>
                <USelect v-model="state.donor_type" class="w-full" :items="[
                  {
                    label: 'Categorias',
                    value: 'single',
                  }
                ]"/>
              </UFormField>

              <div>
                <UFormField label="Quantidade" name="quantity" required>
                  <UInputNumber min="0.1" step="0.1" defaultValue="0"/>
                </UFormField>
                <UButton icon="i-lucide-bin" class="w-fit"/>
              </div>


              <UButton icon="i-lucide-plus" class="w-fit"/>

            </div>

          </TransitionGroup>

        </UCard>

        <div class="flex justify-between gap-3 pt-2">
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

<style scoped>
.slide-enter-active {
  animation: slideDown .25s ease;
}

.slide-leave-active {
  animation: slideUp .2s ease forwards;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideUp {
  from {
    opacity: 1;
    transform: translateY(0);
  }
  to {
    opacity: 0;
    transform: translateY(-6px);
  }
}
</style>
