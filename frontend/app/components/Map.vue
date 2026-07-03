<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import type { Map, Marker } from 'leaflet'
import { useDebounceFn } from '@vueuse/core'

const mapContainer = ref<HTMLElement | null>(null)

let map: Map | null = null
let selectedMarker: Marker | null = null

const markers: Marker[] = []

const props = withDefaults(defineProps<{
  center?: [number, number]
  zoom?: number
  incidents?: any[],
  dropMarkerOnClick?: boolean
  selectedCoords?: [number, number]
}>(), {
  center: () => [39.917504, -8.145675], // Centra em Pedrógão Grande
  zoom: 15,
  incidents: () => [],
  dropMarkerOnClick: false
})

const emit = defineEmits<{
  'map-click': [{ lat: number, lng: number }]
  'bounds-change': [{ north: number, south: number, east: number, west: number }]
}>()

function emitBounds() {
  if (!map) return

  const bounds = map.getBounds()
  const sw = bounds.getSouthWest()
  const ne = bounds.getNorthEast()

  emit('bounds-change', {
    north: ne.lat,
    south: sw.lat,
    east: ne.lng,
    west: sw.lng
  })
}

const debouncedEmitBounds = useDebounceFn(emitBounds, 500)

function createIcons(L: any) {
  const incidentIcon = L.icon({
    iconUrl: '/markers/incident-blue.png',
    iconSize: [30, 30],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34]
  })

  const selectedIcon = L.icon({
    iconUrl: '/markers/selected-grey.png',
    iconSize: [30, 30],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34]
  })

  return {
    incidentIcon,
    selectedIcon
  }
}

function renderMarkers(L: any) {
  if (!map) return

  const { incidentIcon } = createIcons(L)

  markers.forEach(marker => marker.remove())
  markers.length = 0

  props.incidents.forEach((incident) => {
    if (!incident.coordinates) return

    const [lat, lng] = incident.coordinates
      .split(',')
      .map((v: string) => Number(v.trim()))

    if (Number.isNaN(lat) || Number.isNaN(lng)) return

    const marker = L.marker([lat, lng], {
      icon: incidentIcon
    })
      .addTo(map)
      .bindPopup(`
        <div style="line-height: 1.8;">
          <b>Identificador: </b><span>${incident.is_major ? incident.identifier : `${incident.parentIncident?.identifier ? `(Major ${incident.parentIncident.identifier}) ` : ''}${incident.incidentType?.type ?? 'Ocorrência'}${incident.address ? ` - ${incident.address}${incident.municipality ? `, ${incident.municipality}` : ''}` : ''}`}</span><br>
          <b>Tipo: </b><span>${incident.incidentType?.code} - ${incident.incidentType?.species} - ${incident.incidentType?.type}</span><br>
          <b>Estado: </b><span>${incident.incidentState?.name}</span><br>
          <a href="/incidents/${incident.id}/dashboard">Ver ocorrência</a>
        </div>
      `)
    markers.push(marker)
  })
}

onMounted(async () => {
  const L = await import('leaflet')

  if (!mapContainer.value) return

  const { selectedIcon } = createIcons(L)

  map = L.map(mapContainer.value).setView(props.selectedCoords ?? props.center, props.zoom)

  map.on('moveend', debouncedEmitBounds)
  emitBounds()

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
  }).addTo(map)

  if(props.selectedCoords) {
    selectedMarker = L.marker(props.selectedCoords, {
      icon: selectedIcon
    }).addTo(map)
  }

  renderMarkers(L)

  map.on('click', (e) => {
    const coords = {
      lat: Number(e.latlng.lat.toFixed(6)),
      lng: Number(e.latlng.lng.toFixed(6))
    }

    if(props.dropMarkerOnClick) {
      if (selectedMarker) {
        selectedMarker.remove()
      }

      selectedMarker = L.marker([coords.lat, coords.lng], {
        icon: selectedIcon
      }).addTo(map!)
    }

    emit('map-click', coords)
  })
})

watch(
  () => props.incidents,
  async () => {
    const L = await import('leaflet')
    renderMarkers(L)
  },
  { deep: true }
)

watch(
  () => props.center,
  async (center) => {
    if (!map) return

    const L = await import('leaflet')
    const { selectedIcon } = createIcons(L)

    map.setView(center, map.getZoom())

    if (selectedMarker) {
      selectedMarker.remove()
    }

    selectedMarker = L.marker(center, {
      icon: selectedIcon
    }).addTo(map)
  },
  { deep: true }
)

onBeforeUnmount(() => {
  map?.off('moveend', debouncedEmitBounds)
  map?.remove()
  map = null
})
</script>

<template>
  <div ref="mapContainer" class="map-container" />
</template>

<style scoped>
.map-container {
  width: 100%;
  height: calc(100vh - 80px);
  border-radius: 12px;
  overflow: hidden;
}
</style>
