<script setup lang="ts">
import SearchableSelect from './SearchableSelect.vue'
import Map from '../Map.vue'

const props = defineProps<{
  modelValue: boolean
  coords: { lat: number, lng: number }
}>()

const emit = defineEmits(['update:modelValue'])

const options = ref([
  { id: 1, label: 'Exemplo1' },
  { id: 2, label: 'Exemplo2' },
  { id: 3, label: 'Exemplo3' }
])

const form = reactive({
  geral: {
    is_major: false,
    identifier: '',
    data_inicio: '',
    hora_inicio: '',
    status_id: '',
    priority_id: null,
    category_id: null,
    incident_id: null,
    alert_source_relationship: '',
    alert_source_name: '',
    alert_source_contact: '',
    coordinates: '',
    address: '',
    district: '',
    municipality: '',
    parish: '',
    common_place: '',
    obs: ''
  },

  posto: {
    coordenadas: '',
    data_montagem: '',
    hora_montagem: '',
    resp_logistica: '',
    resp_operacoes: '',
    resp_posto: '',
    resp_planeamento: ''
  }
})

const tabItems = [
  {
    label: 'Geral',
    icon: 'i-lucide-users',
    slot: 'geral'
  },
  {
    label: 'Posto de Comando',
    icon: 'i-lucide-satellite-dish',
    slot: 'posto'
  }
]

watch(() => props.coords, (newCoords) => {
  if (newCoords) {
    form.geral.coordinates = `${newCoords.lat}, ${newCoords.lng}`
  }
}, { immediate: true })

//TODO: Criar função no Mapa que permita receber coordenadas para criar um ponto e remover o anterior

</script>

<template>
  <UModal
    :open="props.modelValue"
    :ui="{
      content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl'
    }"
    @update:open="emit('update:modelValue', $event)"
  >
    <template #content>
      <div class="flex flex-col h-[90vh]">
        <div class="p-4 space-y-4 overflow-y-auto flex-1">
          <h2 class="text-lg font-semibold">
            Registo de Ocorrências
          </h2>
          <UTabs :items="tabItems">
            <template #geral>
              <div class="mt-4 space-y-6">
                <UCheckbox
                  v-model="form.geral.is_major"
                  label="Ocorrência Major"
                />

                <UForm class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-5 items-start">
                  <div class="space-y-5">
                    <UFormField label="Nº Ocorrência:" name="num_ocorrencia">
                      <UInput v-model="form.geral.identifier" class="w-full" />
                    </UFormField>
                    <UFormField label="Estado:" name="estado">
                      <SearchableSelect
                        v-model="form.geral.status_id"
                        :items="options"
                        placeholder="Selecionar Estado"
                        create-title="Novo Estado"
                        create-description="Adicione um novo estado ao sistema"
                        create-label="Criar Novo Estado"
                      />
                    </UFormField>
                    <UFormField label="Prioridade:" name="prioridade">
                      <SearchableSelect
                        v-model="form.geral.priority_id"
                        :items="options"
                        placeholder="Selecionar Prioridade"
                        create-title="Novo estado de prioridade"
                        create-description="Adicione um novo estado de prioridade ao sistema"
                        create-label="Criar Novo Estado de Prioridade"
                      />
                    </UFormField>
                    <UFormField label="Tipo de Ocorrência:" name="tipo_ocorrencia">
                      <SearchableSelect
                        v-model="form.geral.category_id"
                        :items="options"
                        placeholder="Selecionar Tipo de Ocorrência"
                        create-title="Novo Tipo de Ocorrência"
                        create-description="Adicione um novo tipo de ocorrência ao sistema"
                        create-label="Criar Novo Tipo de Ocorrência"
                      />
                    </UFormField>
                    <UFormField
                      v-if="!form.geral.is_major"
                      label="Associar Evento:"
                      name="associar_evento"
                    >
                      <SearchableSelect
                        v-model="form.geral.incident_id"
                        :items="options"
                        placeholder="Associar Evento"
                        create-title="Novo Evento"
                        create-description="Adicione um novo evento ao sistema"
                        create-label="Criar Novo Evento"
                      />
                    </UFormField>
                    <UFormField label="Descrição:" name="descricao">
                      <UTextarea
                        v-model="form.geral.obs"
                        class="w-full resize-none overflow-y-auto"
                      />
                    </UFormField>
                  </div>

                  <div class="space-y-5">
                    <div class="grid grid-cols-2 gap-4 items-end">
                      <UFormField label="Data Alerta:" name="data_inicio">
                        <UInputDate v-model="form.geral.data_inicio" class="w-full" />
                      </UFormField>
                      <UFormField label="Hora Alerta:" name="hora_inicio">
                        <UInputTime v-model="form.geral.hora_inicio" class="w-full" />
                      </UFormField>
                    </div>
                    <UFormField label="Fonte de Alerta:" name="fonte_alerta">
                      <UInput v-model="form.geral.alert_source_relationship" class="w-full" />
                    </UFormField>
                    <UFormField label="Nome do Contacto:" name="nome_contacto">
                      <UInput v-model="form.geral.alert_source_name" class="w-full" />
                    </UFormField>
                    <UFormField label="Tlf. Contacto:" name="tel_contacto">
                      <UInput v-model="form.geral.alert_source_contact" class="w-full" />
                    </UFormField>
                  </div>

                  <div class="space-y-5">
                    <UFormField label="Coordenadas:" name="coordenadas">
                      <UInput v-model="form.geral.coordinates" class="w-full" />
                    </UFormField>
                    <UFormField label="Localidade:" name="localidade">
                      <UInput v-model="form.geral.address" class="w-full" />
                    </UFormField>
                    <UFormField label="Distrito:" name="distrito">
                      <UInput v-model="form.geral.district" class="w-full" />
                    </UFormField>
                    <UFormField label="Concelho:" name="concelho">
                      <UInput v-model="form.geral.municipality" class="w-full" />
                    </UFormField>
                    <UFormField label="Freguesia:" name="freguesia">
                      <UInput v-model="form.geral.parish" class="w-full" />
                    </UFormField>
                    <UFormField label="Ponto de Referência:" name="ponto_referencia">
                      <UInput v-model="form.geral.common_place" class="w-full" />
                    </UFormField>
                  </div>
                </UForm>
              </div>
            </template>

            <template #posto>
              <div class="mt-4 space-y-6">
                <UForm class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-5 items-start">
                  <div class="space-y-5">
                    <UFormField label="Coordenadas:" name="coordenadas">
                      <UInput v-model="form.posto.coordenadas" class="w-full" />
                    </UFormField>
                    <div class="grid grid-cols-2 gap-4 items-end">
                      <UFormField label="Data Montagem:" name="data_montagem">
                        <UInputDate v-model="form.posto.data_montagem" class="w-full" />
                      </UFormField>
                      <UFormField label="Hora Montagem:" name="hora_montagem">
                        <UInputTime v-model="form.posto.hora_montagem" class="w-full" />
                      </UFormField>
                    </div>
                  </div>
                  <div class="space-y-5">
                    <UFormField label="Resp. Posto de Comando:" name="posto_comando">
                      <UInputMenu v-model="form.posto.resp_posto" :items="options" class="w-full" />
                    </UFormField>
                    <UFormField label="Resp. Célula de Logística:" name="celula_logistica">
                      <UInputMenu v-model="form.posto.resp_logistica" :items="options" class="w-full" />
                    </UFormField>
                  </div>
                  <div class="space-y-5">
                    <UFormField label="Resp. Célula de Operações:" name="celula_operacoes">
                      <UInputMenu v-model="form.posto.resp_operacoes" :items="options" class="w-full" />
                    </UFormField>
                    <UFormField label="Resp. Célula de Planeamento:" name="celula_planeamento">
                      <UInputMenu v-model="form.posto.resp_planeamento" :items="options" class="w-full" />
                    </UFormField>
                  </div>
                </UForm>
              </div>
            </template>
          </UTabs>

          <Map
            :center="[props.coords?.lat, props.coords?.lng]"
            :zoom="13"
            class="w-full h-[400px] rounded-lg"
            @map-click="coordinates => props.coords"
          />

        </div>
        <div class="flex justify-end gap-2 p-4 bg-white shrink-0">
          <UButton color="neutral" variant="ghost" @click="emit('update:modelValue', false)">
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
