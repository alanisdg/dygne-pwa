<template>
  <div class="min-h-screen bg-black text-gray-100 p-0 sm:p-4 relative">
    <div class="max-w-3xl mx-auto">
      <div class="flex items-center gap-3 px-4 pt-4 sm:px-0 sm:pt-0 mb-4">
        <a href="/app" class="inline-flex items-center text-sm text-gray-400 hover:text-gray-100">
          ← Volver
        </a>
        <h1 class="text-xl font-semibold tracking-tight">{{ loading ? 'Cargando…' : (name || `Device #${id}`) }}</h1>
      </div>

      <!-- Top half: Map -->
      <div class="h-[50vh] bg-[#050814]  overflow-hidden border border-white/5">
        <div v-if="hasAnyCoords" class="h-full w-full">
          <div ref="mapEl" class="h-full w-full"></div>
        </div>
        <div v-else class="h-full w-full flex items-center justify-center text-gray-400 text-sm">
          Sin coordenadas para mostrar
        </div>
      </div>

      <!-- Report range: quick + custom tabs -->
      <div class="bg-[#050814] border border-white/5 shadow-sm rounded-b-3xl sm:rounded-3xl p-4 sm:p-5 mt-0 sm:mt-4">
        <!-- Tabs -->
        <div class="border-b border-white/10 mb-4 flex gap-3 text-sm">
          <button
            type="button"
            class="px-3 py-2 border-b-2"
            :class="rangeTab === 'quick' ? 'border-blue-500 text-blue-300' : 'border-transparent text-gray-500'"
            @click="rangeTab = 'quick'"
          >Rangos rápidos</button>
          <button
            type="button"
            class="px-3 py-2 border-b-2"
            :class="rangeTab === 'custom' ? 'border-blue-500 text-blue-300' : 'border-transparent text-gray-500'"
            @click="rangeTab = 'custom'"
          >Custom</button>
        </div>

        <!-- Quick ranges -->
        <div v-if="rangeTab === 'quick'" class="space-y-3">
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <button
              type="button"
              class="inline-flex items-center justify-center px-3 py-2 rounded-full text-sm border transition"
              :class="selectedQuickHours === 6
                ? 'border-blue-400/80 bg-blue-500/30 text-blue-50'
                : 'border-white/10 bg-white/5 text-gray-200 hover:bg-blue-500/20 hover:border-blue-400/70'"
              @click="() => { console.log('[Range] click 6 horas'); setQuickRangeHours(6) }"
            >6 horas</button>
            <button
              type="button"
              class="inline-flex items-center justify-center px-3 py-2 rounded-full text-sm border transition"
              :class="selectedQuickHours === 4
                ? 'border-blue-400/80 bg-blue-500/30 text-blue-50'
                : 'border-white/10 bg-white/5 text-gray-200 hover:bg-blue-500/20 hover:border-blue-400/70'"
              @click="() => { console.log('[Range] click 4 horas'); setQuickRangeHours(4) }"
            >4 horas</button>
            <button
              type="button"
              class="inline-flex items-center justify-center px-3 py-2 rounded-full text-sm border transition"
              :class="selectedQuickHours === 3
                ? 'border-blue-400/80 bg-blue-500/30 text-blue-50'
                : 'border-white/10 bg-white/5 text-gray-200 hover:bg-blue-500/20 hover:border-blue-400/70'"
              @click="() => { console.log('[Range] click 3 horas'); setQuickRangeHours(3) }"
            >3 horas</button>
            <button
              type="button"
              class="inline-flex items-center justify-center px-3 py-2 rounded-full text-sm border transition"
              :class="selectedQuickHours === 2
                ? 'border-blue-400/80 bg-blue-500/30 text-blue-50'
                : 'border-white/10 bg-white/5 text-gray-200 hover:bg-blue-500/20 hover:border-blue-400/70'"
              @click="() => { console.log('[Range] click 2 horas'); setQuickRangeHours(2) }"
            >2 horas</button>
          </div>
         </div>

        <!-- Custom range (actual comportamiento) -->
        <form v-else @submit.prevent="onSubmitRange" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
          <div class="sm:col-span-2">
            <label class="block text-sm text-gray-300 mb-1">Fecha inicial</label>
            <input
              v-model="startDateLocal"
              type="datetime-local"
              class="w-full border border-white/10 bg-black rounded px-3 py-2 text-sm text-gray-100"
              @click="openNativePicker('start', $event)"
            />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm text-gray-300 mb-1">Fecha final</label>
            <input
              v-model="endDateLocal"
              type="datetime-local"
              class="w-full border border-white/10 bg-black rounded px-3 py-2 text-sm text-gray-100"
              @click="openNativePicker('end', $event)"
            />
          </div>
          <div class="sm:col-span-4 flex flex-wrap items-center gap-2">
            <button
              type="submit"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-full text-sm hover:bg-blue-700 disabled:opacity-50"
              :disabled="loadingRange"
            >
              {{ loadingRange ? 'Cargando…' : 'Consultar recorrido' }}
            </button>
            <button
              type="button"
              @click="toggleDropMarkers"
              :class="[
                'px-3 py-1.5 rounded-full text-sm border transition',
                showDropMarkers ? 'bg-blue-500/20 text-blue-300 border-blue-400/70' : 'bg-white/5 text-gray-400 border-white/10 opacity-80'
              ]"
              title="Mostrar solo la línea del recorrido"
            >
              Mostrar información del recorrido
              <span
                class="ml-2 inline-block text-[11px] px-2 py-0.5 rounded border"
                :class="showDropMarkers ? 'bg-blue-500/20 border-blue-400/70 text-blue-200' : 'bg-black border-white/10 text-gray-400'"
              >
                {{ showDropMarkers ? 'Encendido' : 'Apagado' }}
              </span>
            </button>
            <button
              type="button"
              class="inline-flex items-center px-3 py-1.5 rounded-full text-sm border border-white/15 bg-white/5 text-gray-100 hover:bg-white/10"
              @click="showReportModal = true"
            >
              Reporte
            </button>
          </div>
        </form>

        <!-- Media toggles -->
        <div v-if="hasFrontPhotos || hasRearPhotos || hasFrontVideos || hasRearVideos" class="mt-4 flex items-center gap-2">
          <button v-if="hasFrontPhotos"
            type="button"
            @click="toggleFrontPhotos"
            :class="[
              'px-3 py-1.5 rounded text-sm border transition',
              showFrontPhotos ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-500 border-gray-200 opacity-70'
            ]"
            title="Mostrar/Ocultar fotos FRONT"
          >
            📷 Front
          </button>
          <button v-if="hasRearPhotos"
            type="button"
            @click="toggleRearPhotos"
            :class="[
              'px-3 py-1.5 rounded text-sm border transition',
              showRearPhotos ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-500 border-gray-200 opacity-70'
            ]"
            title="Mostrar/Ocultar fotos REAR"
          >
            📷 Rear
          </button>
          <button v-if="hasFrontVideos"
            type="button"
            @click="toggleFrontVideos"
            :class="[
              'px-3 py-1.5 rounded text-sm border transition',
              showFrontVideos ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-500 border-gray-200 opacity-70'
            ]"
            title="Mostrar/Ocultar videos FRONT"
          >
            🎥 Front
          </button>
          <button v-if="hasRearVideos"
            type="button"
            @click="toggleRearVideos"
            :class="[
              'px-3 py-1.5 rounded text-sm border transition',
              showRearVideos ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-500 border-gray-200 opacity-70'
            ]"
            title="Mostrar/Ocultar videos REAR"
          >
            🎥 Rear
          </button>
        </div>
      </div>

      <!-- Media modal -->
      <div v-if="showMediaModal && selectedMedia" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/70" @click="closeMediaModal"></div>
        <div class="relative bg-[#050814] border border-white/10 rounded-2xl shadow-xl max-w-2xl w-[92vw] sm:w-[600px] overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
            <h3 class="text-sm font-medium text-gray-100">Detalle de media</h3>
            <div class="flex items-center gap-2">
              <Share v-if="selectedMedia" :media="selectedMedia" />
              <button @click="downloadSelectedMedia" class="px-3 py-1.5 bg-blue-600 text-white rounded-full text-xs hover:bg-blue-700 disabled:opacity-60" :disabled="downloading || !selectedMedia">
                {{ downloading ? 'Descargando…' : 'Descargar' }}
              </button>
              <button @click="closeMediaModal" class="text-gray-400 hover:text-gray-100 text-sm">✕</button>
            </div>
          </div>
          <div class="p-4 space-y-3">
            <div class="w-full">
              <template v-if="(selectedMedia.extension || '').toLowerCase() === '.mp4'">
                <video :src="selectedMedia.url" class="w-full h-auto rounded" controls preload="metadata"></video>
              </template>
              <template v-else>
                <img :src="selectedMedia.url" alt="media" class="w-full h-auto rounded" />
              </template>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
              <div>
                <p class="text-gray-400">Capturada</p>
                <p class="font-medium text-gray-100">{{ selectedMedia.captured_at }}</p>
              </div>
              <div>
                <p class="text-gray-400">Latitud</p>
                <p class="font-medium text-gray-100">{{ selectedMedia.latitude }}</p>
              </div>
              <div>
                <p class="text-gray-400">Longitud</p>
                <p class="font-medium text-gray-100">{{ selectedMedia.longitude }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Report drops modal -->
      <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/70" @click="showReportModal = false"></div>
        <div class="relative bg-[#050814] border border-white/10 rounded-2xl shadow-xl max-w-4xl w-[96vw] sm:w-[900px] max-h-[80vh] overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
            <h3 class="text-sm font-medium text-gray-100">Reporte de recorrido ({{ drops.length }} puntos)</h3>
            <button @click="showReportModal = false" class="text-gray-400 hover:text-gray-100 text-sm">✕</button>
          </div>
          <div class="p-4 max-h-[calc(80vh-48px)] flex flex-col">
            <div v-if="!drops || drops.length === 0" class="text-sm text-gray-400">
              No hay puntos de recorrido para mostrar.
            </div>
            <div v-else class="flex-grow overflow-auto">
              <table class="relative min-w-full text-xs text-left text-gray-200">
                <thead>
                  <tr>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">#</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Fecha</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Lat</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Lng</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Velocidad</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Heading</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Satélites</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">RSSI</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Bat</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Supply</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Odo total</th>
                    <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Odo reporte</th>

                     <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Evento Name</th>
                                          <th class="sticky top-0 px-2 py-1 whitespace-nowrap bg-[#050814] bg-opacity-95 text-[11px] uppercase tracking-wide text-gray-400">Evento Code</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr
                    v-for="(d, idx) in drops"
                    :key="idx"
                    class="hover:bg-white/5"
                  >
                    <td class="px-2 py-1 align-top text-gray-400">{{ idx + 1 }}</td>
                    <td class="px-2 py-1 align-top">
                      {{ d.update_time || d.time || d.timeOfFix || '' }}
                    </td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.lat }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.lng }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.speed }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.heading }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.satelites }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.rssi }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.powerBat }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.powerSupply }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.odometroTotal }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.odometroReporte }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.event_name }}</td>
                    <td class="px-2 py-1 align-top whitespace-nowrap">{{ d.event_code }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-[#050814] border border-white/5 shadow-sm rounded-t-3xl sm:rounded-3xl -mt-4 sm:mt-4 relative z-10">
        <div class="border-b border-white/10 px-4 sm:px-5 pt-4">
          <div class="flex gap-3">
            <button
              class="px-3 py-2 text-sm border-b-2"
              :class="activeTab === 'info' ? 'border-blue-500 text-blue-300' : 'border-transparent text-gray-500'"
              @click="activeTab = 'info'"
              type="button"
            >DeviceInfo</button>
            <button
              class="px-3 py-2 text-sm border-b-2"
              :class="activeTab === 'media' ? 'border-blue-500 text-blue-300' : 'border-transparent text-gray-500'"
              @click="activeTab = 'media'"
              type="button"
              
            >DeviceMedia</button> 
            <button
              class="px-3 py-2 text-sm border-b-2"
              :class="activeTab === 'requested' ? 'border-blue-500 text-blue-300' : 'border-transparent text-gray-500'"
              @click="activeTab = 'requested'"
              type="button"
            >Media solicitada</button>
            <button
              class="px-3 py-2 text-sm border-b-2"
              :class="activeTab === 'trips' ? 'border-blue-500 text-blue-300' : 'border-transparent text-gray-500'"
              @click="activeTab = 'trips'"
              type="button"
            >Viajes</button>
          </div>
        </div>
        <div class="p-0">
          <DeviceInfo
            v-if="activeTab === 'info'"
            :name="name"
            :lastdrop="lastdrop"
            :externalMapUrl="externalMapUrl"
            :error="error"
          />
          <div v-else-if="activeTab === 'media'" class="p-4 sm:p-5">
            <DeviceMedia
              :media="media"
              :imei="deviceImei"
              @select="openMedia"
              @updated="handleMediaUpdated"
              @locate="locateMediaOnMap"
            />
          </div>
          <div v-else-if="activeTab === 'requested'" class="p-4 sm:p-5">
            <RequestedDeviceMedia :imei="deviceImei" @select="openMedia" />
          </div>
          <div v-else-if="activeTab === 'trips'" class="p-4 sm:p-5">
            <DeviceTrips :imei="deviceImei" @select="openMedia" />
          </div>
        </div>
      </div>
    </div>

    <!-- Fullscreen loading overlay while fetching range -->
    <div
      v-if="loadingRange"
      class="fixed inset-0 z-40 flex items-center justify-center bg-black/60 backdrop-blur-sm"
    >
      <div class="flex flex-col items-center gap-3 px-4 py-3 rounded-2xl bg-[#050814] border border-white/10 shadow-lg text-sm">
        <div class="h-6 w-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
        <p class="text-gray-300">Consultando recorrido…</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import Share from '@/components/Share.vue'
import DeviceInfo from '@/components/DeviceInfo.vue'
import DeviceMedia from '@/components/DeviceMedia.vue'
import RequestedDeviceMedia from '@/components/RequestedDeviceMedia.vue'
import DeviceTrips from '@/components/DeviceTrips.vue'
import io from 'socket.io-client'
 
 

const props = defineProps({ id: String })
const name = ref('')
const loading = ref(true)
const error = ref('')
const lastdrop = ref(null)
const drops = ref([])
const media = ref([])
const mapEl = ref(null)
let gmap = null
let routePolyline = null
let photoMarkers = []
let videoMarkers = []
let lastDropMarker = null
let lastDropInfoWindow = null
let dropMarkers = []
const deviceImei = ref('')
const startDateLocal = ref('')
const endDateLocal = ref('')
const loadingRange = ref(false)
const showMediaModal = ref(false)
const selectedMedia = ref(null)
const showReportModal = ref(false)
const requestedMedia = ref([])
const showFrontVideos = ref(true)
const showRearVideos = ref(true)
const showFrontPhotos = ref(true)
const showRearPhotos = ref(true)
const downloading = ref(false)
const showDropMarkers = ref(false)
const rangeTab = ref('quick')
const selectedQuickHours = ref(null)
const activeTab = ref('info')
const hasAnyCoords = computed(() => {
  if (lastdrop.value && lastdrop.value.lat != null && lastdrop.value.lng != null) return true
  if (drops.value && drops.value.length > 0) return drops.value.some(d => d.lat != null && d.lng != null)
  if (media.value && media.value.length > 0) return media.value.some(m => m.latitude != null && m.longitude != null)
  return false
})

const hasFrontPhotos = computed(() => {
  return (media.value || []).some(m => (m.extension || '').toLowerCase() === '.jpeg' && (m.type || '').toUpperCase().includes('FRONT'))
})

const hasRearPhotos = computed(() => {
  return (media.value || []).some(m => (m.extension || '').toLowerCase() === '.jpeg' && (m.type || '').toUpperCase().includes('REAR'))
})

const hasFrontVideos = computed(() => {
  return (media.value || []).some(m => (m.extension || '').toLowerCase() === '.mp4' && (m.type || '').toUpperCase().includes('FRONT'))
})

const hasRearVideos = computed(() => {
  return (media.value || []).some(m => (m.extension || '').toLowerCase() === '.mp4' && (m.type || '').toUpperCase().includes('REAR'))
})

const mapUrl = computed(() => {
  if (!lastdrop.value || lastdrop.value.lat == null || lastdrop.value.lng == null) return ''
  const lat = lastdrop.value.lat
  const lng = lastdrop.value.lng
  // Use Google Maps with marker via q parameter; no API key required for simple embed
  return `https://www.google.com/maps?q=${encodeURIComponent(lat + ',' + lng)}&z=16&output=embed`
})

const externalMapUrl = computed(() => {
  if (!lastdrop.value || lastdrop.value.lat == null || lastdrop.value.lng == null) return '#'
  const lat = lastdrop.value.lat
  const lng = lastdrop.value.lng
  return `https://www.google.com/maps?q=${encodeURIComponent(lat + ',' + lng)}&z=16`
})

onMounted(async () => {
  const cacheKey = `device-name-${props.id}`
  name.value = sessionStorage.getItem(cacheKey) || '';
  const socket = io.connect('https://app.dygne.com:3002', { secure: true });
  const socketCalamp = io.connect('https://app.dygne.com:3004', { secure: true });

  // Eventos de depuración
  socket.on('connect', () => {
    console.log('✅ Conectado al servidor Socket.IO:', socket.id)
  })

  socket.on('connect_error', (err) => {
    console.error('❌ Error al conectar con Socket.IO:', err.message)
  })

socket.on('disconnect', (reason) => {
  console.warn('⚠️ Desconectado del socket:', reason)
})

socket.on('drop', (drop) => {  
  if (drop.imei == deviceImei.value) {
  console.log('llego el bueno', drop)
    lastdrop.value = {
      ...lastdrop.value,
      lat: drop.lat,
      lng: drop.lng,
      speed: drop.speed,
      heading: drop.heading,
      satelites: drop.satelites,
      rssi: drop.rssi,
      powerBat: drop.powerBat,
      powerSupply: drop.powerSupply,
      odometroTotal: drop.odometroTotal,
      odometroReporte: drop.odometroReporte,
      update_time: drop.updateTime || drop.timeOfFix || ''
    }
    // Agregar este drop al recorrido y redibujar la ruta
    drops.value = [...(drops.value || []), {
      ...drop,
      lat: drop.lat,
      lng: drop.lng,
    }]
    if (gmap) drawRouteAndMedia()
    const pos = { lat: Number(drop.lat), lng: Number(drop.lng) }
    if (lastDropMarker) {
      lastDropMarker.setPosition(pos)
      const currentIcon = lastDropMarker.getIcon()
      if (currentIcon && typeof currentIcon === 'object') {
        lastDropMarker.setIcon({
          ...currentIcon,
          rotation: Number(drop.heading) || 0,
        })
      }
      if (gmap) gmap.panTo(pos)
    } else if (gmap) {
      const maps = window.google.maps
      lastDropMarker = new maps.Marker({
        position: pos,
        map: gmap,
        icon: {
          path: maps.SymbolPath.FORWARD_CLOSED_ARROW,
          scale: 5,
          fillColor: '#f97316',
          fillOpacity: 1,
          strokeColor: '#ffffff',
          strokeWeight: 1,
          rotation: Number(drop.heading) || 0,
        },
        title: 'Último punto',
      })
      lastDropMarker.addListener('click', () => {
        if (lastDropInfoWindow) lastDropInfoWindow.close()
        const fecha = lastdrop.value?.update_time || lastdrop.value?.timeOfFix || ''
        const lat = lastdrop.value?.lat ?? ''
        const lng = lastdrop.value?.lng ?? ''
        lastDropInfoWindow = new maps.InfoWindow({
          content: `<div style="min-width:180px"><div><strong>Fecha:</strong> ${fecha}</div><div><strong>Lat:</strong> ${lat}</div><div><strong>Lng:</strong> ${lng}</div></div>`
        })
        lastDropInfoWindow.open({ map: gmap, anchor: lastDropMarker })
      })
      gmap.panTo(pos)
    }
  }
})

socketCalamp.on('drop', (drop) => { 
  console.log(deviceImei.value)
  console.log(drop.imei)
  console.log(drop)
  if (drop.imei == deviceImei.value) {
  console.log('llego el bueno', drop)
    lastdrop.value = {
      ...lastdrop.value,
      lat: drop.lat,
      lng: drop.lng,
      speed: drop.speed,
      heading: drop.heading,
      satelites: drop.satelites,
      rssi: drop.rssi,
      powerBat: drop.powerBat,
      powerSupply: drop.powerSupply,
      odometroTotal: drop.odometroTotal,
      odometroReporte: drop.odometroReporte,
      update_time: drop.update_time
    }
    // Agregar este drop al recorrido y redibujar la ruta
    drops.value = [...(drops.value || []), {
      ...drop,
      lat: drop.lat,
      lng: drop.lng,
    }]
    if (gmap) drawRouteAndMedia()
    const pos = { lat: Number(drop.lat), lng: Number(drop.lng) }
    if (lastDropMarker) {
      lastDropMarker.setPosition(pos)
      const currentIcon = lastDropMarker.getIcon()
      if (currentIcon && typeof currentIcon === 'object') {
        lastDropMarker.setIcon({
          ...currentIcon,
          rotation: Number(drop.heading) || 0,
        })
      }
      if (gmap) gmap.panTo(pos)
    } else if (gmap) {
      const maps = window.google.maps
      lastDropMarker = new maps.Marker({
        position: pos,
        map: gmap,
        icon: {
          path: maps.SymbolPath.FORWARD_CLOSED_ARROW,
          scale: 5,
          fillColor: '#f97316',
          fillOpacity: 1,
          strokeColor: '#ffffff',
          strokeWeight: 1,
          rotation: Number(drop.heading) || 0,
        },
        title: 'Último punto',
      })
      lastDropMarker.addListener('click', () => {
        if (lastDropInfoWindow) lastDropInfoWindow.close()
        const fecha = lastdrop.value?.update_time || lastdrop.value?.timeOfFix || ''
        const lat = lastdrop.value?.lat ?? ''
        const lng = lastdrop.value?.lng ?? ''
        lastDropInfoWindow = new maps.InfoWindow({
          content: `<div style="min-width:180px"><div><strong>Fecha:</strong> ${fecha}</div><div><strong>Lat:</strong> ${lat}</div><div><strong>Lng:</strong> ${lng}</div></div>`
        })
        lastDropInfoWindow.open({ map: gmap, anchor: lastDropMarker })
      })
      gmap.panTo(pos)
    }
  }
})

  try {
    const token = localStorage.getItem('auth_token')
    if (!token) throw new Error('Sin token de autenticación')
    const res = await axios.get(`https://app.dygne.com/api/devices/list/${props.id}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    })
    console.log('devices/list response', res)
    const data = res.data
    const dev = data && (data.data || data)
    name.value = dev?.name || name.value || ''
    lastdrop.value = dev?.lastdrop || null
    deviceImei.value = dev?.imei || ''
    if (name.value) sessionStorage.setItem(cacheKey, name.value)

    console.log(`escuchando gps_response_${deviceImei.value}`)
    socketCalamp.on(`gps_response_${deviceImei.value}`, (message) => {
      console.log(message)

      if (message?.imei === deviceImei.value) {
        window.dispatchEvent(new CustomEvent('gps_response_message', {
          detail: {
            imei: message.imei,
            text: message.message,
          },
        }))
      }
    })
   

    const repRes = await axios.get(`https://app.dygne.com/api/reports/${props.id}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    })
    console.log('reports response', repRes)
    const rep = repRes.data
    drops.value = Array.isArray(rep?.drops) ? rep.drops : []
    media.value = Array.isArray(rep?.media) ? rep.media : []
    console.log('initial drops count', drops.value.length)
    console.log('initial media count', media.value.length, 'by ext', media.value.reduce((acc,m)=>{const e=(m.extension||'').toLowerCase();acc[e]=(acc[e]||0)+1;return acc},{}) )

    if (hasAnyCoords.value && mapEl.value) {
      await initMap()
      drawRouteAndMedia()
    }
  } catch (e) {
    error.value = e.message || 'No se pudo cargar el dispositivo'
  } finally {
    loading.value = false
  }
})

async function loadGoogleMaps() {
  if (window.google && window.google.maps) return window.google.maps
  const key = "AIzaSyCeXjwxFHCE0lo_iaAV27UZf4hzVxmFcgs"
  if (!key) return null
  return new Promise((resolve, reject) => {
    const cbName = '__gmaps_cb_' + Math.random().toString(36).slice(2)
    window[cbName] = () => {
      resolve(window.google.maps)
      delete window[cbName]
    }
    const s = document.createElement('script')
    s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&callback=${cbName}`
    s.async = true
    s.onerror = reject
    document.head.appendChild(s)
  })
}

async function initMap() {
  const maps = await loadGoogleMaps()
  if (!maps || !mapEl.value) return
  const center = (lastdrop.value && lastdrop.value.lat != null && lastdrop.value.lng != null)
    ? { lat: Number(lastdrop.value.lat), lng: Number(lastdrop.value.lng) }
    : (drops.value[0] ? { lat: Number(drops.value[0].lat), lng: Number(drops.value[0].lng) } : { lat: 0, lng: 0 })
  gmap = new maps.Map(mapEl.value, { center, zoom: 14, mapTypeId: 'roadmap', gestureHandling: 'greedy', // permite zoom con scroll directamente
  scrollwheel: true  })
}

function drawRouteAndMedia() {
  if (!gmap) return
  const maps = window.google.maps
  const dropsWithCoords = (drops.value || [])
    .filter(p => p.lat != null && p.lng != null)
  const path = dropsWithCoords
    .map(p => ({ lat: Number(p.lat), lng: Number(p.lng) }))
  console.log('drawRouteAndMedia path points', path.length)

  if (routePolyline) {
    // routePolyline puede ser un solo polyline o un arreglo de polylines
    if (Array.isArray(routePolyline)) {
      routePolyline.forEach(pl => pl && pl.setMap(null))
    } else {
      routePolyline.setMap(null)
    }
    routePolyline = null
  }
  // Clear previous media markers
  clearMediaMarkers()
  if (path.length > 1) {
    const polylines = []
    for (let i = 0; i < dropsWithCoords.length - 1; i++) {
      const p1 = dropsWithCoords[i]
      const p2 = dropsWithCoords[i + 1]
      const speedVal = Number(p2.speed ?? p1.speed ?? 0)
      let color = '#2563EB' // azul por defecto 0-50
      if (speedVal > 100) {
        color = '#ef4444' // rojo
      } else if (speedVal > 50) {
        color = '#f97316' // naranja
      }

      const segment = new maps.Polyline({
        path: [
          { lat: Number(p1.lat), lng: Number(p1.lng) },
          { lat: Number(p2.lat), lng: Number(p2.lng) },
        ],
        geodesic: true,
        strokeColor: color,
        strokeOpacity: 0.9,
        strokeWeight: 4,
      })
      segment.setMap(gmap)
      polylines.push(segment)
    }
    routePolyline = polylines
  }

  // Update last drop marker
  if (!lastDropMarker && lastdrop.value && lastdrop.value.lat != null && lastdrop.value.lng != null) {
    const pos = { lat: Number(lastdrop.value.lat), lng: Number(lastdrop.value.lng) }
    lastDropMarker = new maps.Marker({
      position: pos,
      map: gmap,
      icon: {
        path: maps.SymbolPath.FORWARD_CLOSED_ARROW,
        scale: 5,
        fillColor: '#f97316',
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 1,
        rotation: Number(lastdrop.value?.heading) || 0,
      },
      title: 'Último punto',
    })
    lastDropMarker.addListener('click', () => {
      if (lastDropInfoWindow) lastDropInfoWindow.close()
      const fecha = lastdrop.value?.update_time || lastdrop.value?.timeOfFix || ''
      const lat = lastdrop.value?.lat ?? ''
      const lng = lastdrop.value?.lng ?? ''
      const speed = lastdrop.value?.speed ?? ''
      lastDropInfoWindow = new maps.InfoWindow({
        content: `<div style="min-width:180px">
          <div><strong>Fecha:</strong> ${fecha}</div>
          <div><strong>Lat:</strong> ${lat}</div>
          <div><strong>Lng:</strong> ${lng}</div>
          <div><strong>Velocidad:</strong> ${speed} km/h</div>
        </div>`
      })
      lastDropInfoWindow.open({ map: gmap, anchor: lastDropMarker })
    })
  }

  const bounds = new maps.LatLngBounds()
  let anyBounds = false
  path.forEach(pt => { bounds.extend(pt); anyBounds = true })

  ;(dropMarkers || []).forEach(mk => mk.setMap(null))
  dropMarkers = []
  if (showDropMarkers.value) {
    ;(drops.value || [])
      .filter(p => p.lat != null && p.lng != null)
      .forEach(p => {
        const pos = { lat: Number(p.lat), lng: Number(p.lng) }
        const mk = new maps.Marker({
          position: pos,
          map: gmap,
          icon: { path: maps.SymbolPath.CIRCLE, scale: 4, fillColor: '#2563EB', fillOpacity: 1, strokeColor: '#ffffff', strokeWeight: 1 },
          title: (p.time || p.update_time || p.captured_at || '')
        })
        mk.addListener('click', () => {
          if (lastDropInfoWindow) lastDropInfoWindow.close()
          const fecha = p.time || p.update_time || p.captured_at || ''
          const lat = p.lat ?? ''
          const lng = p.lng ?? ''
          const speed = p.speed ?? ''
          lastDropInfoWindow = new maps.InfoWindow({
            content: `<div style="min-width:220px;font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;color:#111827;font-size:12px;">
              <div><strong>Fecha:</strong> ${fecha}</div>
              <div><strong>Lat:</strong> ${lat}</div>
              <div><strong>Lng:</strong> ${lng}</div>
              <div><strong>Velocidad:</strong> ${speed} km/h</div>
              <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                <button
                  id="request-photo-front"
                  type="button"
                  style="display:inline-block;padding:4px 10px;border-radius:9999px;border:none;background:#2563EB;color:white;font-size:11px;cursor:pointer;"
                >
                  Foto frontal
                </button>
                <button
                  id="request-photo-interior"
                  type="button"
                  style="display:inline-block;padding:4px 10px;border-radius:9999px;border:none;background:#0f766e;color:white;font-size:11px;cursor:pointer;"
                >
                  Foto interior
                </button>
              </div>
            </div>`
          })
          lastDropInfoWindow.open({ map: gmap, anchor: mk })
          maps.event.addListenerOnce(lastDropInfoWindow, 'domready', () => {
            const frontBtn = document.getElementById('request-photo-front')
            const interiorBtn = document.getElementById('request-photo-interior')
            if (frontBtn) {
              frontBtn.addEventListener('click', () => requestPhotoFromMap('front'))
            }
            if (interiorBtn) {
              interiorBtn.addEventListener('click', () => requestPhotoFromMap('interior'))
            }
          })
        })
        dropMarkers.push(mk)
      })
  }

  // Update last drop marker
  if (!lastDropMarker && lastdrop.value && lastdrop.value.lat != null && lastdrop.value.lng != null) {
    const pos = { lat: Number(lastdrop.value.lat), lng: Number(lastdrop.value.lng) }
    lastDropMarker = new maps.Marker({
      position: pos,
      map: gmap,
      icon: {
        path: maps.SymbolPath.FORWARD_CLOSED_ARROW,
        scale: 5,
        fillColor: '#f97316',
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 1,
        rotation: Number(lastdrop.value?.heading) || 0,
      },
      title: 'Último punto',
    })
    lastDropMarker.addListener('click', () => {
      if (lastDropInfoWindow) lastDropInfoWindow.close()
      const fecha = lastdrop.value?.update_time || lastdrop.value?.timeOfFix || ''
      const lat = lastdrop.value?.lat ?? ''
      const lng = lastdrop.value?.lng ?? ''
      lastDropInfoWindow = new maps.InfoWindow({
        content: `<div style="min-width:180px"><div><strong>Fecha:</strong> ${fecha}</div><div><strong>Lat:</strong> ${lat}</div><div><strong>Lng:</strong> ${lng}</div></div>`
      })
      lastDropInfoWindow.open({ map: gmap, anchor: lastDropMarker })
    })
  }

  const added = updateMediaMarkers({ fit: false })
  if (added.any) anyBounds = true
  if (added.boundsToExtend) added.boundsToExtend.forEach(pos => bounds.extend(pos))

  if (lastDropMarker) {
    bounds.extend(lastDropMarker.getPosition())
    anyBounds = true
  } else if (!anyBounds && lastdrop.value && lastdrop.value.lat != null && lastdrop.value.lng != null) {
    bounds.extend({ lat: Number(lastdrop.value.lat), lng: Number(lastdrop.value.lng) })
    anyBounds = true
  }
  if (anyBounds) gmap.fitBounds(bounds)
}

async function requestPhotoFromMap(side) {
  if (!deviceImei.value) return

  const token = localStorage.getItem('auth_token')
  if (!token) {
    console.error('No se encontró auth_token en localStorage')
    return
  }

  let commandSuffix = '1,1'
  if (side === 'interior') commandSuffix = '1,2'

  const url = `https://app.dygne.com/api/devices/${encodeURIComponent(deviceImei.value)}/send-command?command=camreq:${commandSuffix}`

  try {
    const response = await axios.post(
      url,
      {
        type: side,
        extension: 'jpeg',
      },
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        }
      }
    )
    console.log('requestPhotoFromMap response', response?.data)
  } catch (e) {
    console.error('Error solicitando foto desde mapa', e)
  }
}

function handleMediaUpdated(newMedia) {
  media.value = Array.isArray(newMedia) ? newMedia : []
  if (gmap) drawRouteAndMedia()
}

function locateMediaOnMap(m) {
  if (!gmap || !m) return
  const lat = m.latitude ?? m.lat
  const lng = m.longitude ?? m.lng
  if (lat == null || lng == null) return
  const pos = { lat: Number(lat), lng: Number(lng) }
  gmap.panTo(pos)
  gmap.setZoom(10)
  try {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (e) {
    // ignore if not in browser
  }
}

function clearMediaMarkers() {
  ;(photoMarkers || []).forEach(mk => mk.setMap(null))
  ;(videoMarkers || []).forEach(mk => mk.setMap(null))
  photoMarkers = []
  videoMarkers = []
}

function updateMediaMarkers({ fit }) {
  if (!gmap) return { any: false, boundsToExtend: [] }
  const maps = window.google.maps
  clearMediaMarkers()
  console.log('processing media for markers, total', (media.value||[]).length, 'showFrontPhotos', showFrontPhotos.value, 'showRearPhotos', showRearPhotos.value, 'showFrontVideos', showFrontVideos.value, 'showRearVideos', showRearVideos.value)
  const boundsToExtend = []
  let any = false
  ;(media.value || []).forEach((m, idx) => {
    const lat = m.latitude
    const lng = m.longitude
    const ext = (m.extension || '').toLowerCase()
    const hasCoords = lat != null && lng != null
    const isPhoto = ext === '.jpeg'
    const isVideo = ext === '.mp4'
    const t = (m.type || '').toUpperCase()
    const isFront = t.includes('FRONT')
    const isRear = t.includes('REAR')
    console.log(`[media ${idx}] ext=`, ext, 'lat=', lat, 'lng=', lng, 'hasCoords=', hasCoords)
    if (!hasCoords) {
      console.log(`[media ${idx}] skipped: missing coordinates for ext`, ext)
      return
    }
    if (isVideo) {
      if (isFront && !showFrontVideos.value) { console.log(`[media ${idx}] hidden by FRONT video toggle`); return }
      if (isRear && !showRearVideos.value) { console.log(`[media ${idx}] hidden by REAR video toggle`); return }
    }
    if (isPhoto) {
      if (isFront && !showFrontPhotos.value) { console.log(`[media ${idx}] hidden by FRONT toggle`); return }
      if (isRear && !showRearPhotos.value) { console.log(`[media ${idx}] hidden by REAR toggle`); return }
      if (!isFront && !isRear) { console.log(`[media ${idx}] photo has no FRONT/REAR tag in type`, m.type); }
    }
    if (isPhoto || isVideo) {
      const pos = { lat: Number(lat), lng: Number(lng) }
      const labelIcon = isVideo ? '🎥' : '📷'
      const mk = new maps.Marker({
        position: pos,
        map: gmap,
        label: { text: labelIcon, fontSize: '14px' },
        title: m.type || 'Media',
      })
      mk.addListener('click', () => {
        console.log('marker clicked media', m)
        selectedMedia.value = m
        showMediaModal.value = true
      })
      if (isVideo) videoMarkers.push(mk)
      else photoMarkers.push(mk)
      boundsToExtend.push(pos)
      any = true
    } else {
      console.log(`[media ${idx}] skipped: unsupported extension`, ext)
    }
  })
  return { any, boundsToExtend }
}

function toggleFrontPhotos() {
  showFrontPhotos.value = !showFrontPhotos.value
  updateMediaMarkers({ fit: false })
}

function toggleRearPhotos() {
  showRearPhotos.value = !showRearPhotos.value
  updateMediaMarkers({ fit: false })
}

function toggleFrontVideos() {
  showFrontVideos.value = !showFrontVideos.value
  updateMediaMarkers({ fit: false })
}

function toggleRearVideos() {
  showRearVideos.value = !showRearVideos.value
  updateMediaMarkers({ fit: false })
}

function toggleDropMarkers() {
  showDropMarkers.value = !showDropMarkers.value
  if (gmap) drawRouteAndMedia()
}

function toApiDateTime(localValue) {
  // localValue expected format: 'YYYY-MM-DDTHH:MM'
  if (!localValue) return ''
  return localValue.replace('T', ' ') + ':00'
}

function toLocalInputValue(date) {
  if (!date) return ''
  const pad = (n) => String(n).padStart(2, '0')
  const year = date.getFullYear()
  const month = pad(date.getMonth() + 1)
  const day = pad(date.getDate())
  const hours = pad(date.getHours())
  const minutes = pad(date.getMinutes())
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

function setQuickRangeHours(hours) {
  try {
    const now = new Date()
    const end = now
    const start = new Date(now.getTime() - hours * 60 * 60 * 1000)
    startDateLocal.value = toLocalInputValue(start)
    endDateLocal.value = toLocalInputValue(end)
    selectedQuickHours.value = hours
    // reutilizamos la lógica existente
    onSubmitRange()
  } catch (e) {
    console.error('Error calculando rango rápido', e)
  }
}

function openNativePicker(which, evt) {
  console.log('[Range] openNativePicker', which, 'loadingRange=', loadingRange.value)
  const el = evt?.target
  if (el && typeof el.showPicker === 'function') {
    try {
      el.showPicker()
    } catch (e) {
      console.warn('showPicker error', e)
    }
  }
}

async function onSubmitRange() {
  try {
    console.log('[Range] onSubmitRange start, loadingRange before =', loadingRange.value, 'startDateLocal=', startDateLocal.value, 'endDateLocal=', endDateLocal.value)
    loadingRange.value = true
    const token = localStorage.getItem('auth_token')
    if (!token) throw new Error('Sin token de autenticación')
    const start_date = toApiDateTime(startDateLocal.value)
    const end_date = toApiDateTime(endDateLocal.value)
    const url = `https://app.dygne.com/api/reports/${props.id}`
    const res = await axios.get(url, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
      params: { start_date, end_date },
    })
    console.log('reports range response', res)
    const data = res.data
    drops.value = Array.isArray(data?.drops) ? data.drops : []
    media.value = Array.isArray(data?.media) ? data.media : []

    const resReq = await axios.get(url, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
      params: { start_date, end_date, type: 'request' },
    })
    const dataReq = resReq.data
    requestedMedia.value = Array.isArray(dataReq?.media) ? dataReq.media : []
    console.log('range drops count', drops.value.length)
    console.log('range media count', media.value.length, 'by ext', media.value.reduce((acc,m)=>{const e=(m.extension||'').toLowerCase();acc[e]=(acc[e]||0)+1;return acc},{}) )
    if (gmap) drawRouteAndMedia()
  } catch (e) {
    console.error('Error al consultar rango', e)
    error.value = e.message || 'No se pudo cargar el recorrido en el rango solicitado'
  } finally {
    console.log('[Range] onSubmitRange finally, setting loadingRange=false')
    loadingRange.value = false
  }
}

function openMedia(m) {
  selectedMedia.value = m
  showMediaModal.value = true
}

function closeMediaModal() {
  showMediaModal.value = false
  selectedMedia.value = null
}

async function downloadSelectedMedia() {
  if (!selectedMedia.value || !selectedMedia.value.url) return
  try {
    downloading.value = true
    const url = selectedMedia.value.url
    const filenameBase = selectedMedia.value.filename || 'media'
    const ext = (selectedMedia.value.extension || '').toLowerCase() || (url.split('.').pop() ? '.' + url.split('.').pop() : '')
    const filename = filenameBase + ext
    const res = await axios.get(url, { responseType: 'blob' })
    const blob = new Blob([res.data])
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = filename
    document.body.appendChild(a)
    a.click()
    a.remove()
    setTimeout(() => URL.revokeObjectURL(a.href), 2000)
  } catch (e) {
    console.error('Error descargando media', e)
  } finally {
    downloading.value = false
  }
}
</script>
