<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'
import * as z from 'zod'
import type {BreadcrumbItem} from '@nuxt/ui/components/Breadcrumb.vue'
import {createBlobURL} from '@/utils'
import AddFileModal from "~/components/facilities/AddFileModal.vue";
import {usePaginatedSelect} from "~/composables/usePaginatedSelect";

const route = useRoute()
const apiStore = useApiStore()

const saving = ref(false)

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

const toast = useToast()

const fetchDonation = async () => {
  const routeID = route.params.id;
  if (typeof routeID !== 'string') {
    toast.add({
      title: 'Doação inválida',
      description: 'O Caminho que o trouxe aqui aponta para uma doação inválida',
      color: 'error'
    });
    await useRouter().push('/facilities');
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

  saving.value = true
  try {
    await apiStore.updateDonationLog(parseInt(<string>route.params.id), state)

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
  } finally {
    saving.value = false
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

onMounted(() => {
  if(!useAuthStore().hasPermission('DONATION_LOG_LIST')){
    useRouter().push('/inicio');
    return;
  }

  fetchDonation()
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Instalação
        </p>
        <div class="flex items-center justify-between w-full gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight truncate max-w-full">
            {{ state.name }}
          </h1>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items" />
      <UTabs :items="tabs" class="w-full">
        <template #geral>
          <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-8">
            <div class="space-y-6">
              <section class="space-y-2">
                <h2 class="font-bold">Dados Gerais</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <UFormField label="Nome" class="sm:col-span-2">
                    <UInput v-model="state.name" class="w-full" />
                  </UFormField>
                  <UFormField label="Email">
                    <UInput v-model="state.email" class="w-full" />
                  </UFormField>
                  <UFormField label="Telefone">
                    <UInput v-model="state.contact" class="w-full" />
                  </UFormField>
                  <UFormField label="Sede" class="sm:col-span-2">
                    <UInput v-model="state.address" class="w-full" />
                  </UFormField>
                </div>
              </section>
              <div class="h-px border-t border-stone-200 dark:border-stone-800" />
              <section class="space-y-2">
                <h2 class="font-bold">Descrição</h2>
                <UTextarea v-model="state.description" :rows="5" class="w-full" />
              </section>
            </div>
            <section class="space-y-6">
              <h2 class="font-bold">Logotipo</h2>
              <UFileUpload v-model="fileState.image" v-slot="{ open, removeFile }" accept="image/png, image/jpeg, image/jpg">
                <div class="relative w-full aspect-square rounded-xl border-2 border-dashed border-stone-300 dark:border-stone-700 hover:border-primary-400 dark:hover:border-primary-500 transition-colors overflow-hidden cursor-pointer bg-stone-50 dark:bg-stone-900" @click="!facilityLogoURL && open()">
                  <template v-if="facilityLogoURL">
                    <img
                      :src="facilityLogoURL"
                      alt="Logotipo"
                      class="w-full h-full object-contain p-4"
                    />
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 opacity-0 hover:opacity-100 transition-opacity bg-black/40 rounded-xl">
                      <UButton icon="i-lucide-pencil" label="Alterar" color="neutral" variant="solid" size="sm" @click.stop="open()" />
                      <UButton icon="i-lucide-rotate-ccw" label="Restaurar" color="primary" variant="solid" size="sm" @click.stop="removeFile()" />
                    </div>
                  </template>
                  <template v-else>
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center">
                      <div class="p-3 rounded-full bg-stone-100 dark:bg-stone-800">
                        <UIcon name="i-lucide-image-plus" class="size-6 text-muted" />
                      </div>
                      <p class="text-sm font-medium text-default">Carregar logotipo</p>
                      <p class="text-xs text-muted">JPG, JPEG, PNG · máx. 2 MB</p>
                    </div>
                  </template>
                </div>
                <div v-if="fileState.image" class="flex items-center gap-1.5 mt-2 px-1 text-xs text-muted">
                  <UIcon name="i-lucide-file-image" class="size-3 shrink-0" />
                  <span class="truncate">{{ fileState.image.name }}</span>
                  <span class="ml-auto shrink-0">{{ formatBytes(fileState.image.size) }}</span>
                </div>
              </UFileUpload>
            </section>
            <UButton
              icon="i-lucide-save"
              color="primary"
              size="xl"
              :loading="saving"
              @click="handleSave"
              class="fixed bottom-6 right-6 z-1000 rounded-full w-16 h-16 shadow-lg flex items-center justify-center"
            />
          </div>
        </template>
        <template #files>
          <div class="space-y-6 pt-4">
            <section class="space-y-2">
              <div class="flex justify-end mb-6">
                <FacilitiesAddFileModal :facility-id="state.id ?? 0" @uploaded="fetchDonation" />
              </div>
              <template v-if="state.documents?.length">
                <div v-for="doc in state.documents" :key="doc.id" class="group flex items-center justify-between rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 px-4 py-3 shadow-sm transition hover:shadow-md hover:border-stone-300 dark:hover:border-stone-700">
                  <div class="flex items-center gap-3 min-w-0">
                    <UIcon name="i-lucide-file" class="size-5 shrink-0 text-muted" />
                    <div class="min-w-0">
                      <p class="text-sm font-medium truncate">{{ doc.name }}</p>
                      <p class="text-xs text-muted">{{ formatBytes(doc.size) }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-1 shrink-0 ml-4">
                    <UButton
                      data-testid="download-document"
                      icon="i-lucide-download"
                      color="neutral"
                      variant="ghost"
                      size="sm"
                      @click="downloadDocument(doc.id, doc.name)"
                    />
                    <UButton
                      data-testid="delete-document"
                      icon="i-lucide-trash-2"
                      color="error"
                      variant="ghost"
                      size="sm"
                      @click="deleteDocument(doc.id)"
                    />
                  </div>
                </div>
              </template>
              <div v-else class="text-center py-6 text-sm text-stone-400">
                Sem ficheiros carregados
              </div>
            </section>
          </div>
        </template>
      </UTabs>
    </div>
  </div>
</template>

<style scoped>

</style>
