<script setup lang="ts">
import {useRoute} from 'vue-router'
import {useApiStore} from '@/stores/api'
import * as z from 'zod'
import type {BreadcrumbItem} from '@nuxt/ui/components/Breadcrumb.vue'
import type {DonationGoodType} from "@/types";
import {suffixForQuantityBox} from "@/utils";

const route = useRoute()
const apiStore = useApiStore()
const toast = useToast()
const goodCategories = ref<DonationGoodType[]>([]);

const goodsSchema = z.object({
  category_id: z.number().refine(v => v !== null, 'Selecione a categoria'),
  quantity: z.number().min(0.1, "A quantidade miníma é 0,1"),
});

const schema = z.object({
  date: z.string().min(1, 'A Data é obrigatória'),
  name: z.string().min(1, 'O Nome é obrigatório'),
  contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000'),
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

const addGood = () => {
  state.goods.push({
    category_id: null,
    quantity: 0,
  })
}

const removeGood = (index: number) => {
  if (state.goods.length > 1) {
    state.goods.splice(index, 1)
  }
}

const fetchDonation = async () => {
  const routeID = route.params.id;
  if (typeof routeID !== 'string') {
    toast.add({
      title: 'Doação inválida',
      description: 'O Caminho que o trouxe aqui aponta para uma doação inválida',
      color: 'error'
    });
    await useRouter().push('/donations');
    return;
  }

  const data = (await apiStore.getDonationLog(parseInt(routeID))).data.data

  Object.assign(state, data)
}

const handleSave = async () => {
  if (!useAuthStore().hasPermission('DONATION_LOG_UPDATE')) return

  if (!await checkServerAccess()) {
    toast.add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  const result = schema.safeParse(state)

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

  try {

    const data = (await apiStore.updateDonationLog(parseInt(<string>route.params.id), state)).data.data;

    Object.assign(state, data)

    toast.add({
      title: 'Sucesso',
      description: 'Doação atualizada',
      color: 'success'
    })
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao atualizar',
      color: 'error'
    })
  }
}

const items = ref<BreadcrumbItem[]>([
  {
    label: 'Doações',
    icon: 'i-lucide-building-2',
    to: '/donations'
  },
  {
    label: 'Detalhes da Doação',
    icon: 'i-lucide-building',
  }
])

onMounted(async () => {
  if (!await checkServerAccess()) {
    useToast().add({
      title: "O Módulo de Doações só está disponível Online",
      color: "warning"
    })
    await useRouter().push('/inicio');
    return;
  }

  if (!useAuthStore().hasPermission('DONATION_LOG_LIST')) {
    await useRouter().push('/inicio');
    return;
  }
  goodCategories.value = (await apiStore.getAllDonationGoodTypes()).data.data;
  await fetchDonation()
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Doação
        </p>
        <div class="flex items-center justify-between w-full gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight truncate max-w-full">
            Doação de {{ state.name }} em {{ new Date(state.date).toLocaleDateString() }}
          </h1>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items"/>
      <div class="space-y-6">
        <UForm
          :state="state"
          :schema="schema"
          class="space-y-5"
          @submit="handleSave"
        >
          <div class="flex justify-end gap-3 pt-2">
            <UButton
              label="Guardar"
              color="primary"
              type="submit"
              class="w-fit"
            />
          </div>
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
                      :items="goodCategories"
                      label-key="name"
                      value-key="id"
                      class="w-full"
                      placeholder="Selecione uma Categoria..."
                    />
                  </UFormField>

                  <UFormField :label="`Quantidade ${suffixForQuantityBox(goodCategories, item.category_id)}`" :name="`goods.${index}.quantity`"
                              required>
                    <UInputNumber
                      v-model="item.quantity"
                      :min="0"
                      :step="suffixForQuantityBox(goodCategories, item.category_id, true) === 'Unidades' ? 1 : 0.1"
                      :format-options="{ minimumFractionDigits: 0, maximumFractionDigits: 1 }"
                      :defaultValue="0"
                      class="w-full"/>
                  </UFormField>

                  <UButton v-if="state.goods.length > 1" icon="i-lucide-trash-2" class="w-fit h-fit" @click="removeGood(index)"/>
                </div>

              </UCard>

              <UButton icon="i-lucide-plus" class="w-fit flex float-right mb-4" @click="addGood()" key="add-btn"/>

            </TransitionGroup>

            <span>Inicialmente registado por {{ state?.user?.name }} em {{
                new Date(state?.created_at).toLocaleString()
              }}</span><br>
            <span>Última atualização a {{ new Date(state?.updated_at).toLocaleString() }}</span>
          </UCard>
        </UForm>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
