<script setup lang="ts">
import type {EditorToolbarItem} from "@nuxt/ui/components/EditorToolbar.vue";
import {useApiStore} from "@/stores/api";

const open = ref(false)
const rules = ref();
const editAccess = useAuthStore().hasPermission('SETTING_DONATION_DISTRIBUTION_RULES_UPDATE');

const toolbarItems: EditorToolbarItem[][] = [
  [
    {
      icon: 'i-lucide-heading',
      tooltip: { text: 'Cabeçalhos' },
      content: {
        align: 'start'
      },
      items: [
        {
          kind: 'heading',
          level: 1,
          icon: 'i-lucide-heading-1',
          label: 'Cabeçalho 1'
        },
        {
          kind: 'heading',
          level: 2,
          icon: 'i-lucide-heading-2',
          label: 'Cabeçalho 2'
        },
        {
          kind: 'heading',
          level: 3,
          icon: 'i-lucide-heading-3',
          label: 'Cabeçalho 3'
        },
        {
          kind: 'heading',
          level: 4,
          icon: 'i-lucide-heading-4',
          label: 'Cabeçalho 4'
        }
      ]
    }
  ],
  [
    {
      kind: 'mark',
      mark: 'bold',
      icon: 'i-lucide-bold',
      tooltip: { text: 'Negrito' }
    },
    {
      kind: 'mark',
      mark: 'italic',
      icon: 'i-lucide-italic',
      tooltip: { text: 'Itálico' }
    },
    {
      kind: 'mark',
      mark: 'underline',
      icon: 'i-lucide-underline',
      tooltip: { text: 'Sublinhado' }
    },
    {
      kind: 'mark',
      mark: 'strike',
      icon: 'i-lucide-strikethrough',
      tooltip: { text: 'Rasurado' }
    }
  ]
]

const saveRules = async() => {
  if(!await checkServerAccess())
  {
    useToast().add({
      title: 'Funcionalidade Indisponível Offline',
      description: 'Como está sem ligação à internet, não é possível atualizar as regras/regulamento',
      color: 'error'
    })
    return;
  }

  try
  {
    await useApiStore().updateDistributionRules({body: rules.value})

    useToast().add({
      title: 'Sucesso!',
      description: 'Alterações guardadas com sucesso!',
      color: 'success'
    })
  }
  catch (e) {
    useToast().add({
      title: 'Erro ao guardar alterações',
      description: 'Ocorreu um erro ao guardar as alterações às regras/regulamento',
      color: 'error'
    })
    console.error(e);
  }
}

onMounted(async () => {
  try
  {
    rules.value = (await useApiStore().getDistributionRules()).data.state;
  }
  catch (e) {
    useToast().add({
      title: 'Ocorreu um Erro',
      description: 'Erro ao carregar Regras/Regulamento',
      color: 'error'
    })
    console.error(e)
  }
})
</script>

<template>
  <UModal
    v-model:open="open"
    title="Regulamento "
    :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl' }"
  >
    <UButton
      icon="i-lucide-scroll"
      label="Regulamento"
      size="xl"
      class="h-fit p-4"
    />
    <template #body>
      <UEditor
        v-slot="{ editor }"
        class="w-full min-h-50 border border-muted rounded-2xl overflow-y-auto"
        v-model="rules"
        :editable="editAccess"
        contentType="markdown"
        autofocus
      >
        <UEditorToolbar
          :editor="editor"
          :items="toolbarItems"
          v-show="editAccess"
          class="border-b border-muted py-2 px-4 sm:px-8 overflow-x-auto sticky"
        />
      </UEditor>
    </template>

    <template #footer v-if="editAccess">
      <UButton
        class="float float-right"
        icon="i-lucide-save"
        label="Guardar Texto"
        @click="saveRules"
      />
    </template>
  </UModal>
</template>

<style scoped>

</style>
