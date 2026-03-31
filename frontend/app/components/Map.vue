<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import type { Map, TileLayer, Marker } from 'leaflet'

const mapContainer = ref<HTMLElement | null>(null)
let map: Map | null = null

const props = withDefaults(defineProps<{
  center?: [number, number]
  zoom?: number
}>(), {
  center: () => [38.7169, -9.1399], // Lisbon default
  zoom: 13
})

const emit = defineEmits<{
  'map-click': [{ lat: number, lng: number }]
}>()

onMounted(async () => {
  // Dynamically import Leaflet (avoids SSR issues)
  const L = await import('leaflet')

  // Fix default marker icon paths broken by bundlers
  delete (L.Icon.Default.prototype as any)._getIconUrl
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
    shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href
  })

  if (!mapContainer.value) return

  // Initialize map
  map = L.map(mapContainer.value).setView(props.center, props.zoom)

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
  }).addTo(map)

  // Add a marker
  L.marker(props.center)
    .addTo(map)
    .bindPopup('<b>Hello from Nuxt + Leaflet! 🗺️</b><br>Drag the map to explore.')
    .openPopup()

  // Example: click to add markers
  map.on('click', (e) => {
    const coords = {
      lat: e.latlng.lat,
      lng: e.latlng.lng
    }

    L.marker([e.latlng.lat, e.latlng.lng])
      .addTo(map!)
      .bindPopup(`📍 ${e.latlng.lat.toFixed(4)}, ${e.latlng.lng.toFixed(4)}`)
      .openPopup()

    emit('map-click', coords)
  })
})

onBeforeUnmount(() => {
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
  height: 500px;
  border-radius: 12px;
  overflow: hidden;
}
</style>
