<script setup lang="ts">
import z from "zod";
import {suffixForQuantityBox} from "~/utils";
import type {DonationGoodType} from "~/types";
import type {FormSubmitEvent} from "@nuxt/ui";

const open = ref(false)
const goodCategories = ref<DonationGoodType[]>([]);

const schema = z.object({
  category_id: z.number({error: 'Selecione a categoria'})
    .refine(v => v !== null, 'Selecione a categoria'),
  quantity: z.number({error: 'Indique uma quantidade válida'}).min(0.1, "A quantidade miníma é 0,1"),
  adjustment_type: z.enum(['add', 'remove'], {error: 'Selecione a operação'}),
  reason: z.string('Indique o motivo').min(1, 'Desenvolva o motivo'),
  obs: z.string().nullable().optional(),
});

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  adjustment_type: undefined,
  category_id: null,
  quantity: 0,
  reason: undefined,
  obs: ''
})

const handleSave = async (event: FormSubmitEvent<Schema>) => {
  if (!useAuthStore().hasRole('module_donations') && !useAuthStore().hasRole('admin')) {
    await useRouter().push('/inicio');
    throw new Error('User does not have access to the donations module');
  }

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  try
  {
    await useApiStore().postStockAudit(event.data)

    clearForm();
    open.value = false;
  }
  catch (e) {
    useToast().add({
      title: 'Erro ao Fazer a Atualização do Stock',
      description: 'Ocorreu um Erro ao Atualizar o Stock',
      color: 'error'
    })
    console.error(e)
  }
}

const clearForm = () => {
  Object.assign(state, {
    adjustment_type: undefined,
    category_id: null,
    quantity: 0,
    reason: undefined,
    obs: undefined
  })
}

watch(open, () => {
  if(open.value === false)
  {
    clearForm()
  }
});

onMounted(async () => {
  if (!useAuthStore().hasRole('module_donations') && !useAuthStore().hasRole('admin')) {
    await useRouter().push('/inicio');
    throw new Error('User does not have access to the donations module');
  }

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível carregar os dados sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  goodCategories.value = (await useApiStore().getAllDonationGoodTypes()).data.data;
})
</script>

<template>
  <UModal v-model:open="open" title="Registar Atualização de Stock" :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-2xl' }">
    <UButton icon="i-lucide-plus" label="Registar Atualização de Stock"/>
    <template #body>
      <UForm :state="state" :schema="schema" class="space-y-5" @submit="handleSave">
        <UFormField name="adjustment_type">
          <div class="grid grid-cols-2 gap-2 mb-5">
            <UButton
              color="neutral"
              variant="ghost"
              :ui="{ base: 'justify-start focus-visible:ring-0 focus:outline-none' }"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg border transition-colors duration-300"
              :class="state.adjustment_type === 'add' ? 'border-success/40 bg-success-50 text-success-700 hover:bg-success-100 dark:bg-success-950 dark:text-success-300 dark:hover:bg-success-900' : 'border-default bg-elevated/50 text-muted hover:bg-elevated hover:text-toned'"
              @click="state.adjustment_type = 'add'"
            >
              <span class="size-7 rounded-md flex items-center justify-center bg-success-100 dark:bg-success-900 shrink-0">
                <UIcon name="i-lucide-plus" class="size-4 text-success-600 dark:text-success-400"/>
              </span>
              <div class="flex flex-col items-baseline gap-1">
                <p class="text-sm font-medium text-highlighted">Adicionar</p>
                <p class="text-xs text-muted">Adicionar Stock ao Sistema</p>
              </div>
            </UButton>
            <UButton
              color="neutral"
              variant="ghost"
              :ui="{ base: 'justify-start focus-visible:ring-0 focus:outline-none' }"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg border transition-colors duration-300"
              :class="state.adjustment_type === 'remove' ? 'border-error/40 bg-error-50 text-error-700 hover:bg-error-100 dark:bg-error-950 dark:text-error-300 dark:hover:bg-error-900' : 'border-default bg-elevated/50 text-muted hover:bg-elevated hover:text-toned'"
              @click="state.adjustment_type = 'remove'"
            >
              <span class="size-7 rounded-md flex items-center justify-center bg-error-100 dark:bg-error-900 shrink-0">
                <UIcon name="i-lucide-minus" class="size-4 text-error-600 dark:text-error-400"/>
              </span>
              <div class="flex flex-col items-baseline">
                <p class="text-sm font-medium text-highlighted">Remover</p>
                <p class="text-xs text-muted">Remover Stock do Sistema</p>
              </div>
            </UButton>
          </div>
        </UFormField>
        <div class="grid grid-cols-2 gap-5">
          <UFormField label="Categoria" name="category_id" required>
            <USelectMenu
              v-model="state.category_id"
              :items="goodCategories"
              label-key="name"
              value-key="id"
              class="w-full"
              placeholder="Selecione uma Categoria..."
            />
          </UFormField>
          <UFormField :label="`Quantidade ${suffixForQuantityBox(goodCategories, state.category_id)}`" name="quantity" required>
            <UInputNumber
              data-testid="good-quantity-input"
              v-model="state.quantity"
              :min="0"
              :step="suffixForQuantityBox(goodCategories, state.category_id, true) === 'Unidades' ? 1 : 0.1"
              :format-options="{ minimumFractionDigits: 0, maximumFractionDigits: 1 }"
              :max="state.category_id ? Infinity : 0"
              class="w-full"/>
          </UFormField>
        </div>
        <UFormField label="Motivo" name="reason" class="mt-2" required>
          <USelect
            class="w-full"
            v-model="state.reason"
            :items="[
              {
                label: 'Correção de Diferença',
                value: 'diffCorrection',
              },
              {
                label: 'Item Danificado',
                value: 'brokenItem',
              },
              {
                label: 'Perda/Roubo',
                value: 'lost',
              },
              {
                label: 'Outro',
                value: 'other',
              }
            ]"
          />
        </UFormField>
        <UFormField label="Observações" name="obs">
          <UTextarea class="w-full" v-model="state.obs" placeholder="Aqui pode escrever alguma informação adicional que queira registar"/>
        </UFormField>
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
