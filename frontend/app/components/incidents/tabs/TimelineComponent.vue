<script setup lang="ts">
import type {TimelineItem} from "@nuxt/ui";
import {toDatetimeLocal} from "@/utils";
import z from "zod";
import moment from 'moment/min/moment-with-locales'
import type {AuditLog} from "@/types";
import {useAuthStore} from "~/stores/auth";

moment.locale('pt');
const {$echo} = useNuxtApp();
const timeline = ref<TimelineItem[]>([])
const loadingTimeline = ref(false)
const nextCursor = ref<string | null>(null)

const editingCommentId = ref<number | null>(null)
const editingCommentBody = ref('')
const editingCommentDate = ref('')

const savingComment = ref(false)

const props = defineProps({
  incidentID: {
    type: Number,
    required: true,
  }
})

const commentSchema = z.object({
  body: z.string()
    .min(1, 'O comentário não pode estar vazio')
    .max(4000000, 'Máximo de 4000000 caracteres'),
  manualTime: z.boolean(),
  datetime: z.string().optional(),
}).superRefine((data, ctx) => {
  if (data.manualTime && (!data.datetime || data.datetime.trim() === '')) {
    ctx.addIssue({
      code: 'custom',
      message: 'Este campo é obrigatório quando seleciona para escolher a data/hora manualmente',
      path: ['datetime'],
    });
  }
});

type CommentSchema = z.output<typeof commentSchema>

const addCommentState = reactive<Partial<CommentSchema>>({
  manualTime: false,
  datetime: '',
  body: ''
});

const formatValue = (value: any) => {
  if (value === null || value === undefined || value === '') {
    return '-'
  }

  if (typeof value === 'boolean') {
    return value ? 'Sim' : 'Não'
  }

  return value
}

const fieldLabels: Record<string, string> = {
  identifier: 'Identificador',
  incident_type_id: 'Tipo',
  incident_state_id: 'Estado',
  incident_priority_id: 'Prioridade',
  start_datetime: 'Data Início',
  end_datetime: 'Data Fim',
  coodinates: 'Coordenadas',
  common_place: 'Ponto de Referência',
  address: 'Morada',
  parish: 'Freguesia',
  municipality: 'Municipio',
  district: 'Distrito',
  is_major: 'Ocorrência Major',
  alert_source_relationship: 'Fonte de Alerta',
  alert_source_name: 'Nome do Contacto',
  alert_source_contact: 'Tlf. Contacto',
  obs: 'Observações',
  coordinates_pco: 'Coordenadas do Posto de Comando',
  name_pco: 'Nome do Posto de Comando',

  entity_id: 'Entidade',
  vehicle_count: 'N.º Veículos',
  human_count: 'N.º Operacionais',

  function_pco: 'Função',
  resp_pco: 'Responsável',
  category_pco: 'Categoria',
  contact1_pco: 'Contacto 1',
  contact2_pco: 'Contacto 2',
  localization_pco: 'Localização',
  rob_pco: 'ROB',
  srp_pco: 'SRP',
  activation_pco_datetime: 'Data Ativação',
  start_pco_datetime: 'Data Início',
  end_pco_datetime: 'Data Fim',
}

const moduleIcon = (module: string) => {
  const icons: Record<string, string> = {
    'incidents': 'i-lucide-users',
    'pcos': 'i-lucide-satellite-dish',
    'parties': 'i-lucide-ambulance',
    'comments': 'i-lucide-message-circle'
  }
  return icons[module] ?? 'i-lucide-message-circle'
}

const fetchTimeline = async (loadMore = false) => {
  loadingTimeline.value = true

  try {

    const params: any = {
      per_page: 10,
      ...(loadMore && nextCursor.value ? {cursor: nextCursor.value} : {})
    }

    const res = await useApiStore().getIncidentTimeline(props.incidentID, params)

    const newLogs = res.data.data.map((entry: any) => ({
      ...entry,
      icon: entry.type === 'comment' ? 'i-lucide-message-circle' : moduleIcon(entry.module),
    }))

    if (loadMore) {
      const existingLogs = new Set(timeline.value.map(entry => entry.log_id));
      const existingComments = new Set(timeline.value.map(entry => entry.comment_id));

      timeline.value.push(...newLogs.filter((audit: AuditLog) => {
        if (audit.type === 'log') {
          //@ts-ignore
          return !existingLogs.has(audit.log_id);
        } else if (audit.type === 'comment') {
          //@ts-ignore
          return !existingComments.has(audit.comment_id)
        }

        return false;
      }))
    } else {
      timeline.value = newLogs
    }

    nextCursor.value = res.data.next_cursor
  } finally {
    loadingTimeline.value = false
  }
}

const fieldLabel = (key: string) => {
  return fieldLabels[key] ?? key
}

const startEditComment = (item: any) => {
  editingCommentId.value = item.comment_id
  editingCommentBody.value = item.body
  editingCommentDate.value = toDatetimeLocal(item.date)
}

const cancelEditComment = () => {
  editingCommentId.value = null
  editingCommentBody.value = ''
}

const submitComment = async () => {
  if (!useAuthStore().hasPermission('INCIDENTS_UPDATE')) return;

  if (!addCommentState.body?.trim() || savingComment.value) return

  savingComment.value = true

  try {

    const payload = {
      body: addCommentState.body,
      datetime: addCommentState.manualTime ? moment(addCommentState.datetime).toISOString() : moment().toISOString(),
    }

    await useApiStore().createTimelineComment(props.incidentID, payload)

    useToast().add({
      title: 'Sucesso',
      description: 'Entrada registada',
      color: 'success'
    })

    Object.assign(addCommentState, {
      manualTime: false,
      datetime: '',
      body: ''
    })
  } catch (e: any) {
    useToast().add({
      title: 'Erro',
      description: 'Erro ao registar a entrada',
      color: 'error'
    })
  } finally {
    savingComment.value = false
  }
}

const saveEditComment = async (item: any) => {
  if (!useAuthStore().hasPermission('INCIDENTS_UPDATE')) return;

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  try {
    await useApiStore().updateTimelineComment(props.incidentID, item.comment_id, {
      body: editingCommentBody.value,
      datetime: moment(editingCommentDate.value).toISOString()
    })

    useToast().add({
      title: 'Sucesso',
      description: 'Entrada atualizada',
      color: 'success'
    })

    editingCommentId.value = null
    editingCommentBody.value = ''
  } catch (e: any) {
    useToast().add({
      title: 'Erro',
      description: e.response?.data?.message ?? 'Erro ao atualizar comentário',
      color: 'error'
    })
  }
}

const scrollContainer = ref<HTMLElement | null>(null)

const handleNewTimelineEvent = (event: any) => {
  const existingLogs = new Set(timeline.value.map(entry => entry.log_id));
  const existingComments = new Set(timeline.value.map(entry => entry.comment_id));

  if(event.type === 'log' && !existingLogs.has(event.log_id)) {
    timeline.value.unshift({
      ...event,
      icon: moduleIcon(event.module),
    });
  }

  if(event.type === 'comment') {
    if(!existingComments.has(event.comment_id))
    {
      if(event.event === 'created')
      {
        const entryTime = new Date(event.date).getTime();

        const insertIndex = timeline.value.findIndex(
          (existing: any) => new Date(existing.date).getTime() < entryTime
        );

        const newEntry = {
          ...event,
          icon: 'i-lucide-message-circle',
        };

        if (insertIndex === -1) {
          console.log("unshift")
          timeline.value.unshift(newEntry);
        } else {
          console.log("splice")
          timeline.value.splice(insertIndex, 0, newEntry);
        }
      }
    }
    else
    {
      const idx = timeline.value.findIndex((entry: TimelineItem) => entry.comment_id === event.comment_id);
      if(idx != -1)
      {
        timeline.value[idx].body = event.body;
      }
    }
  }
}

onMounted(async () => {
  if (!useAuthStore().hasPermission('INCIDENTS_LIST')) return;

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível carregar a Fita do Tempo sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  $echo.private('Incident.' + props.incidentID)
    .listen('.timeline.event', handleNewTimelineEvent)

  await fetchTimeline();

  useInfiniteScroll(
    scrollContainer,
    () => {
      if (!nextCursor.value) return
      fetchTimeline(true)
    },
    {
      distance: 200,
      canLoadMore: () => {
        return nextCursor.value != null
      }
    }
  )
})

onUnmounted(() => {
  $echo.leave('Incident.' + props.incidentID)
})
</script>

<template>
  <div class="space-y-6 pt-4">
    <UCard title="Nova Entrada Manual">
      <UForm
        :state="addCommentState"
        :schema="commentSchema"
        @submit="submitComment"
      >
        <div class="grid grid-cols-2">
          <UCheckbox
            label="Indicar Data/Hora manualmente"
            class="col-span-1 mt-6"
            v-model="addCommentState.manualTime"
            name="manualDate"
          />
          <UFormField label="Data da ocorrência" name="datetime" class="col-span-1">
            <UInput
              type="datetime-local"
              v-model="addCommentState.datetime"
              class="w-full"
              :disabled="!addCommentState.manualTime"
            />
          </UFormField>
        </div>
        <UFormField label="Descrição" name="body">
          <UTextarea
            v-model="addCommentState.body"
            placeholder="Escreva uma entrada..."
            :rows="3"
            class="w-full"
          />
        </UFormField>
        <div class="flex justify-end gap-2 mt-2">
          <UButton
            type="submit"
            color="primary"
            label="Guardar"
            :loading="savingComment"
            :disabled="!addCommentState.body?.trim()"
          />
        </div>
      </UForm>
    </UCard>
    <h2 class="font-bold">Fita de Tempo</h2>

    <UEmpty
      v-if="timeline.length === 0"
      icon="i-lucide-book-open"
      title="Fita do Tempo Vazia"
      description="Não existem registos para mostrar"
      variant="naked"
    />

    <div v-else ref="scrollContainer" class="overflow-x-auto max-h-[85vh] overflow-y-auto">
      <UTimeline :items="timeline" size="xl" :ui="{ date: 'float-end ms-1' }">
        <template #title="{ item }">
          <div class="flex flex-col gap-2">
            <div class="flex items-center justify-between gap-2">
              <div>
                <span class="font-semibold">{{ (item as any).username }}</span>
                <span class="font-normal text-muted">&nbsp;{{ (item as any).action }}</span>
              </div>
              <UButton
                v-if="(item as any).type === 'comment'"
                icon="i-lucide-pencil"
                data-testid="edit-comment"
                color="warning"
                variant="ghost"
                size="xs"
                @click="startEditComment(item)"
              />
            </div>
            <div v-if="(item as any).type === 'comment'" class="space-y-2">
              <div v-if="editingCommentId !== (item as any).comment_id"
                   class="text-sm px-3 py-2 ring ring-default rounded-md text-stone-400 shrink-0 dark:text-stone-300">
                {{ (item as any).body }}
              </div>
              <div v-else class="space-y-2">
                <UFormField label="Data da ocorrência">
                  <UInput
                    type="datetime-local"
                    v-model="editingCommentDate"
                    class="w-full"
                  />
                </UFormField>
                <UFormField label="Descrição">
                  <UTextarea v-model="editingCommentBody" :rows="3" class="w-full"/>
                </UFormField>
                <div class="flex justify-end gap-2">
                  <UButton
                    color="neutral"
                    variant="ghost"
                    label="Cancelar"
                    @click="cancelEditComment"
                  />
                  <UButton
                    color="primary"
                    label="Guardar"
                    @click="saveEditComment(item)"
                  />
                </div>
              </div>
            </div>
            <div v-else-if="Object.keys((item as any).changes ?? {}).length">
              <UAccordion
                :items="[{
                        label: 'Ver alterações',
                      }]"
                class="text-sm text-stone-400 shrink-0 dark:text-stone-300"
                :ui="{
                        trigger: 'px-3 py-2 ring ring-default rounded-md bg-default/30',
                        content: 'px-3 py-2'
                      }"
              >
                <template #content>
                  <div class="space-y-1 text-xs px-3 py-2">
                    <div v-for="(value, key) in item.changes" :key="key" class="flex gap-2 flex-wrap">
                      <span class="text-stone-400 shrink-0">{{ fieldLabel(String(key)) }}:</span>
                      <template v-if="key in (item.old_values ?? {})">
                        <span class="line-through text-red-400">{{ formatValue(item.old_values?.[key]) }}</span>
                        <UIcon name="i-lucide-move-right"/>
                      </template>
                      <span class="text-green-500">{{ formatValue(value) }}</span>
                    </div>
                  </div>
                </template>
              </UAccordion>
            </div>
          </div>
        </template>
        <template #date="{ item }">{{ moment(item.date).format("DD/MM/YYYY HH:mm:ss") }}</template>
      </UTimeline>
    </div>

    <div v-if="loadingTimeline" class="flex justify-center py-10">
      <UIcon name="i-lucide-loader-circle" class="animate-spin text-stone-400 size-6"/>
    </div>
  </div>
</template>

<style scoped>

</style>
