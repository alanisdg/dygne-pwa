<template>
  <div class="min-h-screen bg-gray-50 p-0 sm:p-4">
    <div class="max-w-3xl mx-auto">
      <div class="flex items-center gap-3 px-4 pt-4 sm:px-0 sm:pt-0 mb-3">
        <a href="/app" class="inline-flex items-center text-sm text-gray-600 hover:text-black">
          ← Volver
        </a>
        <h1 class="text-xl font-semibold">{{ loading ? 'Cargando…' : (name || `Device #${id}`) }}</h1>
      </div>

      <!-- Top half: Map -->
      <div class="h-[50vh] bg-gray-200">
        <div v-if="hasAnyCoords" class="h-full w-full">
          <div ref="mapEl" class="h-full w-full"></div>
        </div>
        <div v-else class="h-full w-full flex items-center justify-center text-gray-500 text-sm">
          Sin coordenadas para mostrar
        </div>
      </div>

      <!-- Report range form -->
      <div class="bg-white shadow-sm rounded-b-xl sm:rounded-xl p-4 sm:p-5 mt-0 sm:mt-4">
        <form @submit.prevent="onSubmitRange" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
          <div class="sm:col-span-2">
            <label class="block text-sm text-gray-600 mb-1">Fecha inicial</label>
            <input v-model="startDateLocal" type="datetime-local" class="w-full border rounded px-3 py-2 text-sm" />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm text-gray-600 mb-1">Fecha final</label>
            <input v-model="endDateLocal" type="datetime-local" class="w-full border rounded px-3 py-2 text-sm" />
          </div>
          <div class="sm:col-span-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 disabled:opacity-50" :disabled="loadingRange">
              {{ loadingRange ? 'Cargando…' : 'Consultar recorrido' }}
            </button>
          </div>
        </form>

        <!-- Media toggles -->
        <div class="mt-4 flex items-center gap-2">
          <button
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
          <button
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
          <button
            type="button"
            @click="toggleVideos"
            :class="[
              'px-3 py-1.5 rounded text-sm border transition',
              showVideos ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-500 border-gray-200 opacity-70'
            ]"
            title="Mostrar/Ocultar videos"
          >
            🎥 Videos
          </button>
        </div>
      </div>

      <!-- Media modal -->
      <div v-if="showMediaModal && selectedMedia" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="closeMediaModal"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-[92vw] sm:w-[600px] overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b">
            <h3 class="text-sm font-medium text-gray-700">Detalle de media</h3>
            <div class="flex items-center gap-2">
              <Share v-if="selectedMedia" :media="selectedMedia" />
              <button @click="downloadSelectedMedia" class="px-3 py-1.5 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 disabled:opacity-60" :disabled="downloading || !selectedMedia">
                {{ downloading ? 'Descargando…' : 'Descargar' }}
              </button>
              <button @click="closeMediaModal" class="text-gray-500 hover:text-gray-800 text-sm">✕</button>
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
                <p class="text-gray-500">Capturada</p>
                <p class="font-medium">{{ selectedMedia.captured_at }}</p>
              </div>
              <div>
                <p class="text-gray-500">Latitud</p>
                <p class="font-medium">{{ selectedMedia.latitude }}</p>
              </div>
              <div>
                <p class="text-gray-500">Longitud</p>
                <p class="font-medium">{{ selectedMedia.longitude }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom: compact info -->
      <div class="rounded-t-xl sm:rounded-xl bg-white shadow-sm p-4 sm:p-5 -mt-4 sm:mt-4 relative z-10">
        <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
        <template v-else>
          <div class="flex items-center justify-between">
            <p class="text-gray-700">Nombre: <span class="font-medium">{{ name || '(sin nombre)' }}</span></p>
            <a v-if="lastdrop" :href="externalMapUrl" target="_blank" class="text-sm text-blue-600 hover:underline">Abrir en Maps</a>
          </div>

          <div v-if="lastdrop" class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
            <div class="rounded-lg border border-gray-100 p-3">
              <p class="text-gray-500">Actualizado</p>
              <p class="font-medium">{{ lastdrop.update_time }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 p-3">
              <p class="text-gray-500">Velocidad</p>
              <p class="font-medium">{{ lastdrop.speed }} km/h</p>
            </div>
            <div class="rounded-lg border border-gray-100 p-3">
              <p class="text-gray-500">Coordenadas</p>
              <p class="font-medium">{{ lastdrop.lat }}, {{ lastdrop.lng }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 p-3">
              <p class="text-gray-500">Heading</p>
              <p class="font-medium">{{ lastdrop.heading }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 p-3">
              <p class="text-gray-500">Satélites</p>
              <p class="font-medium">{{ lastdrop.satelites }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 p-3">
              <p class="text-gray-500">RSSI</p>
              <p class="font-medium">{{ lastdrop.rssi }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 p-3">
              <p class="text-gray-500">Batería</p>
              <p class="font-medium">{{ lastdrop.powerBat }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 p-3">
              <p class="text-gray-500">Alimentación</p>
              <p class="font-medium">{{ lastdrop.powerSupply }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 p-3">
              <p class="text-gray-500">Odómetro total</p>
              <p class="font-medium">{{ lastdrop.odometroTotal }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 p-3">
              <p class="text-gray-500">Odómetro reporte</p>
              <p class="font-medium">{{ lastdrop.odometroReporte }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 p-3 sm:col-span-3">
              <p class="text-gray-500">Estado</p>
              <p class="font-medium">{{ lastdrop.stoped ? 'Detenido' : 'En movimiento' }}</p>
            </div>
          </div>
          <p v-else class="mt-4 text-gray-500">Sin datos recientes (lastdrop).</p>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import Share from '@/components/Share.vue'
 

const props = defineProps({ id: String })
const name = ref('')
const loading = ref(true)
const error = ref('')
const lastdrop = ref(null)
const drops = ref([])
const media = ref([])
const mapEl = ref(null)
import io from 'socket.io-client' 
let gmap = null
let routePolyline = null
let photoMarkers = []
let videoMarkers = []
let lastDropMarker = null
const deviceImei = ref('')
const startDateLocal = ref('')
const endDateLocal = ref('')
const loadingRange = ref(false)
const showMediaModal = ref(false)
const selectedMedia = ref(null)
const showVideos = ref(true)
const showFrontPhotos = ref(true)
const showRearPhotos = ref(true)
const downloading = ref(false)
const hasAnyCoords = computed(() => {
  if (lastdrop.value && lastdrop.value.lat != null && lastdrop.value.lng != null) return true
  if (drops.value && drops.value.length > 0) return drops.value.some(d => d.lat != null && d.lng != null)
  if (media.value && media.value.length > 0) return media.value.some(m => m.latitude != null && m.longitude != null)
  return false
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
         const socket = io.connect('https://dygne.com:3002', { secure: true });

 
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
  if (drop && drop.imei === deviceImei.value) {
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
    const pos = { lat: Number(drop.lat), lng: Number(drop.lng) }
    if (lastDropMarker) {
      lastDropMarker.setPosition(pos)
    } else if (gmap) {
      const maps = window.google.maps
      lastDropMarker = new maps.Marker({
        position: pos,
        map: gmap,
        icon: { path: maps.SymbolPath.CIRCLE, scale: 8, fillColor: '#f43f5e', fillOpacity: 1, strokeColor: '#ffffff', strokeWeight: 2 },
        title: 'Último punto',
      })
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
  const key = import.meta.env.VITE_GOOGLE_MAPS_API_KEY
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
  gmap = new maps.Map(mapEl.value, { center, zoom: 14, mapTypeId: 'roadmap' })
}

function drawRouteAndMedia() {
  if (!gmap) return
  const maps = window.google.maps
  const path = (drops.value || [])
    .filter(p => p.lat != null && p.lng != null)
    .map(p => ({ lat: Number(p.lat), lng: Number(p.lng) }))
  console.log('drawRouteAndMedia path points', path.length)

  if (routePolyline) {
    routePolyline.setMap(null)
    routePolyline = null
  }
  // Clear previous media markers
  clearMediaMarkers()
  if (path.length > 0) {
    routePolyline = new maps.Polyline({
      path,
      geodesic: true,
      strokeColor: '#2563EB',
      strokeOpacity: 0.9,
      strokeWeight: 4,
    })
    routePolyline.setMap(gmap)
  }

  // Update last drop marker
  if (!lastDropMarker && lastdrop.value && lastdrop.value.lat != null && lastdrop.value.lng != null) {
    const pos = { lat: Number(lastdrop.value.lat), lng: Number(lastdrop.value.lng) }
    lastDropMarker = new maps.Marker({
      position: pos,
      map: gmap,
      icon: { path: maps.SymbolPath.CIRCLE, scale: 8, fillColor: '#f43f5e', fillOpacity: 1, strokeColor: '#ffffff', strokeWeight: 2 },
      title: 'Último punto',
    })
  }

  const bounds = new maps.LatLngBounds()
  let anyBounds = false
  path.forEach(pt => { bounds.extend(pt); anyBounds = true })

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
  console.log('processing media for markers, total', (media.value||[]).length, 'showFront', showFrontPhotos.value, 'showRear', showRearPhotos.value, 'showVideos', showVideos.value)
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
    if ((isVideo && !showVideos.value)) {
      console.log(`[media ${idx}] hidden by toggle`, ext)
      return
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

function toggleVideos() {
  showVideos.value = !showVideos.value
  updateMediaMarkers({ fit: false })
}

function toApiDateTime(localValue) {
  // localValue expected format: 'YYYY-MM-DDTHH:MM'
  if (!localValue) return ''
  return localValue.replace('T', ' ') + ':00'
}

async function onSubmitRange() {
  try {
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
    console.log('range drops count', drops.value.length)
    console.log('range media count', media.value.length, 'by ext', media.value.reduce((acc,m)=>{const e=(m.extension||'').toLowerCase();acc[e]=(acc[e]||0)+1;return acc},{}) )
    if (gmap) drawRouteAndMedia()
  } catch (e) {
    console.error('Error al consultar rango', e)
    error.value = e.message || 'No se pudo cargar el recorrido en el rango solicitado'
  } finally {
    loadingRange.value = false
  }
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
