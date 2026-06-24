<script setup lang="ts">
import * as z from 'zod'
import type {FormSubmitEvent} from '@nuxt/ui'
import {useApiStore} from '@/stores/api'
import {CalendarDate} from "@internationalized/date";
import {now} from "@vueuse/core";
import {usePaginatedSelect} from "~/composables/usePaginatedSelect";

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const categoriesMenu = useTemplateRef('categoriesMenu')
const goodCategories = usePaginatedSelect({
  fetcher: apiStore.getDonationGoodTypes,
  menuRef: categoriesMenu,
  map: (t: any) => ({
    id: t.id,
    name: t.name,
    unit: t.unit
  })
})

const goodsSchema = z.object({
  category_id: z.number({required_error: 'Selecione a categoria'}).nullable()
    .refine(v => v !== null, 'Selecione a categoria'),
  quantity: z.number().min(0.1, "A quantidade miníma é 0,1"),
});

type Good = z.output<typeof goodsSchema>

const createGood = (): Good => ({
  category_id: null,
  quantity: 0,
})

const addGood = () => {
  state.goods.push(createGood())
}

const removeGood = (index: number) => {
  if (state.goods.length > 1) {
    state.goods.splice(index, 1)
  }
}

const schema = z.object({
  date: z.string().min(1, 'A Data é obrigatória'),
  name: z.string().min(1, 'O Nome é obrigatório'),
  contact: z.string().min(1, "O Contacto é obrigatório").refine(
    value => /^\+?[0-9]+(?: [0-9]+)*$/.test(value),
    'Insira apenas números ou formato +000 000000000'
  ),
  email: z.string().email().optional().or(z.literal('')).nullable(),
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
    await apiStore.createDonationLog(event.data)

    emit('created')
    open.value = false
    toast.add({
      title: 'Sucesso',
      description: 'Doação registada com sucesso',
      color: 'success'
    })

    Object.assign(state, {
      date: new Date().toISOString().split('T')[0],
      name: '',
      contact: '',
      email: '',
      donor_type: '',
      goods: [{
        category_id: null,
        quantity: 0,
      }]
    })

  } catch (e: any) {
    console.debug(e)
    toast.add({
      title: 'Erro',
      description: 'Erro ao registar Doação',
      color: 'error'
    })
  }
}

const suffixForQuantityBox = (index: number) => {
  const category = goodCategories.items.value.find(x => x.id == state.goods[index].category_id);

  if(!category || !category.unit)
    return '';

  return `(em ${convertedMeasurementUnit(category.unit)})`;
}

onMounted(async () => {
  addGood();
  await goodCategories.fetchItems()
})
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
        <div class="grid grid-cols-3 gap-5">
          <UFormField label="Data de Receção" name="date" required>
            <UInput type="date" v-model="state.date" class="w-full"/>
          </UFormField>

          <UFormField label="Nome" name="name" required class="col-span-2">
            <UInput v-model="state.name" class="w-full"/>
          </UFormField>
        </div>

        <div class="grid grid-cols-3 gap-5">
          <UFormField label="Contacto Telefónico" name="contact" required>
            <UInput v-model="state.contact" class="w-full"/>
          </UFormField>

          <UFormField label="Email" name="email">
            <UInput v-model="state.email" class="w-full"/>
          </UFormField>

          <UFormField label="Tipo de Doador" name="donor_type" required>
            <USelect
              v-model="state.donor_type"
              class="w-full"
              placeholder="Selecione o Tipo de Doador..."
              :items="[
              {
                label: 'Pessoa Singular',
                value: 'single',
              },
              {
                label: 'Empresa',
                value: 'company',
              },
              {
                label: 'Organização Não-Governamental',
                value: 'org'
              },
              {
                label: 'Outro',
                value: 'misc'
              }
            ]"/>
          </UFormField>
        </div>

        <UCard title="Lista de Bens">

          <TransitionGroup name="slide" tag="div">

            <UCard class="mb-4" v-for="(item, index) in state.goods" :key="index">

              <div class="grid grid-cols-2 gap-5">
                <UFormField label="Categoria" :name="`goods.${index}.category_id`" required>
                  <USelectMenu
                    ref="categoriesMenu"
                    v-model="item.category_id"
                    v-model:search-term="goodCategories.search.value"
                    :items="goodCategories.items.value"
                    :loading="goodCategories.loading.value"
                    label-key="name"
                    value-key="id"
                    class="w-full"
                    ignore-filter
                    placeholder="Selecione uma Categoria..."
                  />
                </UFormField>

                <UFormField :label="`Quantidade ${suffixForQuantityBox(index)}`" :name="`goods.${index}.quantity`" required>
                  <UInputNumber
                    v-model="item.quantity"
                    :min="0.1"
                    :step="0.1"
                    :defaultValue="0"
                    class="w-full"/>
                </UFormField>

                <UButton v-if="index !== 0" icon="i-lucide-trash-2" class="w-fit h-fit" @click="removeGood(index)"/>
              </div>

            </UCard>

            <UButton icon="i-lucide-plus" class="w-fit flex float-right mb-4" @click="addGood()" key="add-btn"/>

          </TransitionGroup>

        </UCard>

        <div class="flex justify-end gap-3 pt-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="subtle"
            class="w-fit"
            @click="open = false"
          />
          <UButton
            label="Guardar"
            color="primary"
            type="submit"
            class="w-fit"
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
