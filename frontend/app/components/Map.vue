<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import type { Map, TileLayer, Marker } from 'leaflet'

const mapContainer = ref<HTMLElement | null>(null)
let map: Map | null = null

const props = withDefaults(defineProps<{
  center?: [number, number]
  zoom?: number
}>(), {
  center: () => [39.917504, -8.145675],
  zoom: 15
})

const emit = defineEmits<{
  'map-click': [{ lat: number, lng: number }]
}>()

onMounted(async () => {
  const L = await import('leaflet')

  if (!mapContainer.value) return

  map = L.map(mapContainer.value).setView(props.center, props.zoom)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
  }).addTo(map)

  map.on('click', (e) => {
    const coords = {
      lat: Number(e.latlng.lat.toFixed(6)),
      lng: Number(e.latlng.lng.toFixed(6))
    }

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
  height: 600px;
  border-radius: 12px;
  overflow: hidden;
}
</style>
