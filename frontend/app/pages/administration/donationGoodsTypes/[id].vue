<script setup lang="ts">
import {useRoute} from 'vue-router'
import {useApiStore} from '@/stores/api'
import * as z from "zod";
import type {BreadcrumbItem} from "@nuxt/ui/components/Breadcrumb.vue";

const route = useRoute()
const api = useApiStore()
const toast = useToast()

const saving = ref(false)

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

const fetchGoodType = async () => {
  if (!useAuthStore().hasPermission('DONATION_GOODS_TYPES_LIST')) {
    await useRouter().push('/inicio');
    return;
  }

  const routeID = route.params.id;
  if (typeof routeID !== 'string') {
    toast.add({
      title: 'Tipo de Bem inválido',
      description: 'O Caminho que o trouxe aqui aponta para um Tipo de Bem inválido',
      color: 'error'
    });
    await useRouter().push('/administration/donationGoodsTypes');
    return;
  }

  const res = await api.getDonationGoodType(parseInt(routeID));

  Object.assign(state, res.data.data)
}

const handleSave = async () => {
  if (!useAuthStore().hasPermission('DONATION_GOODS_TYPES_UPDATE')) return

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

  saving.value = true
  try {
    await api.updateDonationGoodType(parseInt(<string>route.params.id), state)

    toast.add({
      title: 'Sucesso',
      description: 'Tipo de Bem atualizado.',
      color: 'success'
    })
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao atualizar',
      color: 'error'
    })
  } finally {
    saving.value = false
  }
}

watch(state, () => {
  //Para o nome no breadcrumb
  if (items.value.length !== 1) {
    items.value.pop()
  }

  items.value.push({
    label: `Dados de ${state?.name}`
  })

  //Para limpar os campos por de trás do switch
  if(!state.is_type_countable)
  {
    state.unit = null;
    state.danger_level = null;
  }
})

const items = ref<BreadcrumbItem[]>([
  {
    label: 'Tipos de Bens Doáveis',
    icon: 'i-lucide-blocks',
    to: '/administration/donationGoodsTypes',
  }
])

onMounted(fetchGoodType)
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Tipo de Bem
        </p>
        <div class="flex items-center justify-between w-full gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight truncate max-w-full">
            {{ state.name }}
          </h1>
          <div class="flex items-center gap-2">
            <UButton
              label="Guardar"
              color="primary"
              :loading="saving"
              @click="handleSave"
              :disabled="!useAuthStore().hasPermission('DONATION_GOODS_TYPES_UPDATE')"
            />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items"/>
      <div class="grid grid-cols-1 gap-8">
        <div class="space-y-6">
          <section class="space-y-2">
            <h2 class="font-bold">Dados Gerais</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <UFormField label="Nome" class="sm:col-span-2">
                <UInput v-model="state.name" class="w-full"/>
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
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <template v-if="state.is_type_countable">
                  <UFormField label="Unidade">
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

                  <UFormField
                    label="Número mínimo"
                    description="(Opcional) Quantidade crítica para mostrar alertas na dashboard"
                    name="danger_level"
                  >
                    <UInputNumber v-model="state.danger_level" class="w-full" min="1" />
                    <UButton label="Limpar" @click="state.danger_level=null"></UButton>
                  </UFormField>

                </template>
              </div>
            </div>
            Última Atualização: {{ new Date(state.updated_at).toLocaleString('pt-PT') }}
          </section>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
