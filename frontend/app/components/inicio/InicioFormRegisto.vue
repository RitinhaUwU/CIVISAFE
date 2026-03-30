<script setup lang="ts">
import SearchableSelect from './SearchableSelect.vue'

const open = ref(false)

const options = ref([
  { id: 1, label: 'Exemplo1' },
  { id: 2, label: 'Exemplo2' },
  { id: 3, label: 'Exemplo3' }
])

const form = reactive({
  geral: {
    ocorrenciaMajor: false,
    num_ocorrencia: '',
    data_inicio: '',
    hora_inicio: '',
    data_fim: '',
    hora_fim: '',
    estado: '',
    prioridade: null,
    tipo_ocorrencia: null,
    associar_evento: '',
    coordenadas: '',
    localidade: '',
    distrito: '',
    concelho: '',
    freguesia: '',
    ponto_referencia: '',
    descricao: ''
  },

  contacto: {
    fonte_alerta: '',
    nome_contacto: '',
    tel_contacto: '',
    responsavel: '',
    posto: ''
  },

  logistica: {
    entidade: null,
    num_operacionais: 1,
    num_viaturas: 1
  }
})

const items = [
  {
    label: 'Geral',
    icon: 'i-lucide-users',
    slot: 'geral'
  },
  {
    label: 'Contactos',
    icon: 'i-lucide-contact',
    slot: 'contacto'
  },
  {
    label: 'Logística',
    icon: 'i-lucide-van',
    slot: 'logistica'
  }
]

defineShortcuts({
  o: () => open.value = !open.value
})
</script>

<template>
  <UButton
    label="Registar ocorrência"
    icon="i-lucide-plus"
    size="md"
    class="justify-center"
    @click="open = true"
  />
  <UModal v-model:open="open">
    <template #content>
      <div class="p-4 space-y-4">
        <h2 class="text-lg font-semibold">
          Registo de Ocorrências
        </h2>

        <UTabs :items="items">
          <!-- Geral -->
          <template #geral>
            <UForm class="grid grid-cols-1 lg:grid-cols-2 gap-x-10 gap-y-6 mt-4">
              <UCheckbox
                v-model="form.geral.ocorrenciaMajor"
                label="Ocorrência Major"
                class="lg:col-span-2"
              />
              <div class="space-y-5">
                <UFormField label="Nº Ocorrência:" name="num_ocorrencia">
                  <UInput v-model="form.geral.num_ocorrencia" class="w-full" />
                </UFormField>
                <div class="grid grid-cols-2 gap-4 items-end">
                  <UFormField label="Data Alerta:" name="data_inicio">
                    <UInputDate v-model="form.geral.data_inicio" class="w-full" />
                  </UFormField>
                  <UFormField label="Hora:" name="hora_inicio">
                    <UInputTime v-model="form.geral.hora_inicio" class="w-full" />
                  </UFormField>
                </div>
                <div class="grid grid-cols-2 gap-4 items-end">
                  <UFormField label="Data Fim:" name="data_fim">
                    <UInputDate v-model="form.geral.data_fim" class="w-full" />
                  </UFormField>
                  <UFormField label="Hora:" name="hora_fim">
                    <UInputTime v-model="form.geral.hora_fim" class="w-full" />
                  </UFormField>
                </div>
                <UFormField label="Estado:" name="estado">
                  <SearchableSelect
                    v-model="form.geral.estado"
                    :items="options"
                    placeholder="Selecionar Estado"
                    create-title="Novo Estado"
                    create-description="Adicione um novo estado ao sistema"
                    create-label="Criar Novo Estado"
                  />
                </UFormField>
                <UFormField label="Prioridade:" name="prioridade">
                  <SearchableSelect
                    v-model="form.geral.prioridade"
                    :items="options"
                    placeholder="Selecionar Prioridade"
                    create-title="Novo estado de prioridade"
                    create-description="Adicione um novo estado de prioridade ao sistema"
                    create-label="Criar Novo Estado de Prioridade"
                  />
                </UFormField>
                <UFormField label="Tipo de Ocorrência:" name="tipo_ocorrencia">
                  <SearchableSelect
                    v-model="form.geral.tipo_ocorrencia"
                    :items="options"
                    placeholder="Selecionar Tipo de Ocorrência"
                    create-title="Novo Tipo de Ocorrência"
                    create-description="Adicione um novo tipo de ocorrência ao sistema"
                    create-label="Criar Novo Tipo de Ocorrência"
                  />
                </UFormField>
                <UFormField label="Associar Evento:" name="associar_evento">
                  <UInput v-model="form.geral.associar_evento" class="w-full" />
                </UFormField>
              </div>
              <div class="space-y-5">
                <UFormField label="Coordenadas:" name="coordenadas">
                  <UInput v-model="form.geral.coordenadas" class="w-full" />
                </UFormField>
                <UFormField label="Localidade:" name="localidade">
                  <UInput v-model="form.geral.localidade" class="w-full" />
                </UFormField>
                <UFormField label="Distrito:" name="distrito">
                  <UInput v-model="form.geral.distrito" class="w-full" />
                </UFormField>
                <UFormField label="Concelho:" name="concelho">
                  <UInput v-model="form.geral.concelho" class="w-full" />
                </UFormField>
                <UFormField label="Freguesia:" name="freguesia">
                  <UInput v-model="form.geral.freguesia" class="w-full" />
                </UFormField>
                <UFormField label="Ponto de Referência:" name="ponto_referencia">
                  <UInput v-model="form.geral.ponto_referencia" class="w-full" />
                </UFormField>
                <UFormField label="Descrição:" name="descricao">
                  <UTextarea
                    v-model="form.geral.descricao"
                    class="w-full resize-none overflow-y-auto"
                  />
                </UFormField>
              </div>
              <div class="lg:col-span-2">
                <!-- mapa aqui -->
              </div>
            </UForm>
          </template>

          <!-- Contacto -->
          <template #contacto>
            <UForm class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
              <div class="flex flex-col gap-4">
                <UFormField label="Fonte de Alerta:" name="fonte_alerta">
                  <UInput v-model="form.contacto.fonte_alerta" class="w-full" />
                </UFormField>
                <UFormField label="Nome do Contacto:" name="nome_contacto">
                  <UInput v-model="form.contacto.nome_contacto" class="w-full" />
                </UFormField>
                <UFormField label="Tlf. Contacto:" name="tel_contacto">
                  <UInput v-model="form.contacto.tel_contacto" class="w-full" />
                </UFormField>
              </div>
              <div class="flex flex-col gap-4">
                <UFormField label="Responsável:" name="responsavel">
                  <UInput class="w-full" />
                </UFormField>
                <UFormField label="Posto:" name="posto">
                  <UInput class="w-full" />
                </UFormField>
              </div>
            </UForm>
          </template>

          <!-- Logística -->
          <template #logistica>
            <UForm class="flex flex-col gap-6 mt-4">
              <UFormField label="Entidade:" name="entidade">
                <SearchableSelect
                  v-model="form.logistica.entidade"
                  :items="options"
                  placeholder="Selecionar Entidade"
                  create-title="Nova Entidade"
                  create-description="Adicione uma nova entidade ao sistema"
                  create-label="Criar Nova Entidade"
                />
              </UFormField>
              <UFormField label="Nº de Operacionais:" name="num_operacionais">
                <UInputNumber v-model="form.logistica.num_operacionais" class="w-full" />
              </UFormField>
              <UFormField label="Nº de Viaturas:" name="num_viaturas">
                <UInputNumber v-model="form.logistica.num_viaturas" class="w-full" />
              </UFormField>
              <UButton
                label="Adicionar Entidade"
                icon="i-lucide-plus"
                size="md"
                class="w-full justify-center"
              />
            </UForm>
          </template>
        </UTabs>
        <div class="flex justify-end gap-2 mt-4">
          <UButton color="neutral" variant="ghost" @click="open = false">
            Cancelar
          </UButton>
          <UButton color="primary">
            Guardar
          </UButton>
        </div>
      </div>
    </template>
  </UModal>
</template>

<style scoped>

</style>
