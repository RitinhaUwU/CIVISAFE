<script setup lang="ts">
import * as z from 'zod'
import { usePaginatedSelect } from '~/composables/usePaginatedSelect'
import { useApiStore } from '@/stores/api'

const apiStore = useApiStore()

export const goodsSchema = z.object({
  category_id: z.number({ required_error: 'Selecione a categoria' }).nullable()
    .refine(v => v !== null, 'Selecione a categoria'),
  quantity: z.number().min(0.1, 'A quantidade miníma é 0,1'),
})

export type Good = z.output<typeof goodsSchema>

export const createGood = (): Good => ({
  category_id: null,
  quantity: 0,
})

const goods = defineModel<Good[]>({ required: true })

const categoriesMenu = useTemplateRef('categoriesMenu')
const goodCategories = usePaginatedSelect({
  fetcher: apiStore.getDonationGoodTypes,
  menuRef: categoriesMenu,
  map: (t: any) => ({ id: t.id, name: t.name, unit: t.unit }),
})

const addGood = () => goods.value.push(createGood())

const removeGood = (index: number) => {
  if (goods.value.length > 1) goods.value.splice(index, 1)
}

const suffixForQuantityBox = (index: number) => {
  const category = goodCategories.items.value.find(x => x.id === goods.value[index].category_id)
  if (!category?.unit) return ''
  return `(em ${convertedMeasurementUnit(category.unit)})`
}

onMounted(() => goodCategories.fetchItems())
</script>

<template>
  <UCard title="Lista de Bens">
    <TransitionGroup name="slide" tag="div">
      <UCard class="mb-4" v-for="(item, index) in goods" :key="index">
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
              class="w-full"
            />
          </UFormField>

          <UButton v-if="index !== 0" icon="i-lucide-trash-2" class="w-fit h-fit" @click="removeGood(index)" />
        </div>
      </UCard>

      <UButton icon="i-lucide-plus" class="w-fit flex float-right mb-4" @click="addGood()" key="add-btn" />
    </TransitionGroup>
  </UCard>
</template>

<style scoped>
.slide-enter-active { animation: slideDown .25s ease; }
.slide-leave-active { animation: slideUp .2s ease forwards; }

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-8px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes slideUp {
  from { opacity: 1; transform: translateY(0); }
  to   { opacity: 0; transform: translateY(-6px); }
}
</style>
