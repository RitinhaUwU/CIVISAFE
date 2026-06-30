<script setup lang="ts">
import z from "zod";
import type {DonationDistribution, DonationGoodType} from "@/types";
import {suffixForQuantityBox} from "@/utils";

const toast = useToast();
const stockTracker = ref(new Map<number, {
  name: string,
  stock: number,
  danger_level: number | null,
  lastUpdated: number
}>());
const goodCategories = ref<DonationGoodType[]>([]);

/**
 * Websocket event handlers
 */
const handleStockUpdate = async (stockUpdate: { id: number, stock: number, timestamp: number }) => {
  const good = stockTracker.value.get(stockUpdate.id);

  if (good === undefined) {
    const newCategory = (await useApiStore().getDonationGoodType(stockUpdate.id)).data.data;
    stockTracker.value.set(stockUpdate.id, {
      name: newCategory.name,
      stock: stockUpdate.stock,
      danger_level: newCategory.danger_level,
      lastUpdated: stockUpdate.timestamp
    });
  } else {
    if (good.lastUpdated < stockUpdate.timestamp) {
      good.stock = stockUpdate.stock;
      good.lastUpdated = stockUpdate.timestamp;
    }
  }
}

onMounted(async () => {
  if (!await checkServerAccess()) {
    useToast().add({
      title: "O Módulo de Doações só está disponível Online",
      color: "warning"
    })
    await useRouter().push('/inicio');
    return;
  }

  if (!useAuthStore().hasRole('module_donations') && !useAuthStore().hasRole('admin')) {
    await useRouter().push('/inicio');
    throw new Error('User does not have access to the donations module');
  }

  const {$echo} = useNuxtApp();

  $echo.private('DonationStocks')
    .listen('.stock.updated', handleStockUpdate);

  const initialStocks = (await useApiStore().getAllStock()).data.data;
  goodCategories.value = (await useApiStore().getAllDonationGoodTypes()).data.data;

  initialStocks.forEach((stock: { type_id: number, type_name: string, stock: number }) => {
    stockTracker.value.set(stock.type_id, {
      name: stock.type_name,
      stock: stock.stock,
      danger_level: goodCategories.value.findLast(g => g.id == stock.type_id)?.danger_level ?? null,
      lastUpdated: (new Date()).getTime() / 1000
    });
  });
})

/**
 * Relacionado com o formulário de distribuíção
 */
const addGood = () => {
  distributionForm.goods.push({
    // @ts-ignore
    category_id: null,
    quantity: 0,
  })
}

const removeGood = (index: number) => {
  if (distributionForm.goods.length > 1) {
    distributionForm.goods.splice(index, 1)
  }
}

const distributionFormSchema = z.object({
  name: z.string().min(1, 'O Nome é obrigatório'),
  contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000'),
  obs: z.string().nullable().optional(),
  goods: z.array(
    z.object({
      category_id: z.number({error: 'Selecione a categoria'})
        .refine(v => v !== null, 'Selecione a categoria'),
      quantity: z.number({error: 'Indique uma quantidade'}).min(0.1, "A quantidade miníma é 0,1"),
    })).min(1, 'Adicione pelo menos 1 Bem')
});

type DistributionForm = z.output<typeof distributionFormSchema>

const distributionForm = reactive<Partial<DistributionForm>>({
  name: '',
  contact: '',
  obs: '',
  goods: [{
    // @ts-ignore
    category_id: null,
    quantity: 0,
  }]
})

const submitDistribution = async () => {
  //TODO: Meter um full page loader enquanto o processo decorre

  try {
    if (!useAuthStore().hasPermission('DONATION_LOG_CREATE')) return
    if (distributionForm.id !== undefined && !useAuthStore().hasPermission('DONATION_LOG_UPDATE')) return

    if (!await checkServerAccess()) {
      toast.add({
        title: 'Sem ligação à internet!',
        description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
        color: 'error'
      });
      return;
    }

    if (distributionForm.id === undefined) {
      await useApiStore().createDistribution(distributionForm)
    } else {
      await useApiStore().updateDistribution(distributionForm.id, distributionForm)
    }

    toast.add({
      title: 'Sucesso!',
      description: `A entrega de bens a ${distributionForm.name} foi registada com sucesso!`,
      color: 'success',
    });

    clearForm();

  } catch (error) {
    console.error(error)
    toast.add({
      title: 'Erro ao gravar entrega',
      description: 'Ocorreu um erro ao tentar gravar entrega',
      color: 'error'
    })
  }
}

const onRowSelected = (record: DonationDistribution) => {
  console.log(record);
  Object.assign(distributionForm, {
    ...record, goods: record.goods.map(item => ({
      quantity: item.quantity,
      category_id: item.donation_goods_type_id,
    }))
  });
}

const clearForm = () => {
  Object.assign(distributionForm, {
    name: '',
    contact: '',
    obs: '',
    goods: [{
      category_id: null,
      quantity: 0,
    }]
  })
  delete distributionForm.id
}

</script>

<template>

  <UDashboardPanel id="donation-distribution">
    <template #header>
      <UDashboardNavbar title="Doações - Distribuição Pela Comunidade" :ui="{ right: 'gap-3' }">
        <template #leading>
          <UDashboardSidebarCollapse/>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body class="overflow-y-auto h-full">

      <div class="grid grid-cols-4 gap-4">

        <UCard class="col-span-3">

          <template #title>
            {{distributionForm.id === undefined ? "Nova Entrega" : `Entrega a ${distributionForm.name}`}}

            <UButton
              label="Voltar"
              icon="i-lucide-undo-2"
              size="sm"
              class="float-end"
              v-if="distributionForm.id !== undefined"
              @click="clearForm"
            />
          </template>

          <UForm
            :state="distributionForm"
            :schema="distributionFormSchema"
            @submit="submitDistribution()"
            id="distribution-form"
          >
            <div class="grid grid-cols-3 gap-4 mb-4">
              <UFormField label="Nome" name="name" class="col-span-2" required>
                <UInput class="w-full" v-model="distributionForm.name"/>
              </UFormField>

              <UFormField label="Contacto" name="contact" class="col-span-1" required>
                <UInput class="w-full" v-model="distributionForm.contact"/>
              </UFormField>
            </div>

            <UFormField label="Observações" name="obs">
              <UTextarea class="w-full" v-model="distributionForm.obs"/>
            </UFormField>

            <UCard title="Lista de Bens" class="mt-4 mr-1 ml-1 mb-1">

              <TransitionGroup name="slide" tag="div">

                <UCard class="mb-4" v-for="(item, index) in distributionForm.goods" :key="index">

                  <div class="grid grid-cols-2 gap-5">
                    <UFormField label="Categoria" :name="`goods.${index}.category_id`" required>
                      <USelectMenu
                        v-model="item.category_id"
                        :items="goodCategories"
                        label-key="name"
                        value-key="id"
                        class="w-full"
                        placeholder="Selecione uma Categoria..."
                      />
                    </UFormField>

                    <UFormField
                      :label="`Quantidade ${suffixForQuantityBox(goodCategories, distributionForm.goods[index].category_id)}`"
                      :name="`goods.${index}.quantity`"
                      required>
                      <UInputNumber
                        v-model="item.quantity"
                        :min="0"
                        :step="suffixForQuantityBox(goodCategories, distributionForm.goods[index].category_id, true) === 'Unidades' ? 1 : 0.1"
                        :format-options="{ minimumFractionDigits: 0, maximumFractionDigits: 1 }"
                        :max="distributionForm.goods[index].category_id ? stockTracker.get(distributionForm.goods[index].category_id).stock : 0"
                        class="w-full"/>
                      <span
                        v-if="distributionForm.goods[index].category_id"
                        class="float-end"
                      >
                          Stock atual: {{
                          stockTracker.get(distributionForm.goods[index].category_id).stock
                        }} {{ suffixForQuantityBox(goodCategories, distributionForm.goods[index].category_id, true) }}
                        </span>
                    </UFormField>

                    <UButton v-if="distributionForm.goods.length > 1" icon="i-lucide-trash-2" class="w-fit h-fit"
                             @click="removeGood(index)"/>
                  </div>

                </UCard>


              </TransitionGroup>

              <template #footer>
                <UButton
                  icon="i-lucide-plus"
                  label="Adicionar Linha"
                  class="w-fit flex float-right mb-4"
                  @click="addGood()"
                  key="add-btn"
                />
              </template>
            </UCard>
          </UForm>
        </UCard>

        <div class="grid grid-cols-1 gap-4 max-h-fit h-fit sticky top-4 self-start">

          <UButton
            icon="i-lucide-hand-coins"
            :label="distributionForm.id === undefined ? 'Registar Entrega' : 'Guardar Alterações'"
            size="xl"
            class="h-fit p-4"
            type="submit"
            form="distribution-form"
          />

          <DonationDistributionListModal @selected="onRowSelected"/>

          <DonationDistributionRulesModal/>

          <DonationDistributionStocksModal :stockTracker="stockTracker"/>

        </div>

      </div>
    </template>
  </UDashboardPanel>

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
