<template>
  <div class="min-h-screen bg-black text-gray-100 p-4 sm:p-6">
    <div class="max-w-4xl mx-auto">
      <div v-if="loading" class="text-sm text-gray-400 text-center py-8">
        Cargando notificación...
      </div>
      <div v-else-if="error" class="text-sm text-red-400 text-center py-8">
        {{ error }}
      </div>
      <div v-else-if="notification" class="bg-[#0a0f1a] border border-white/10 rounded-2xl overflow-hidden">
        <!-- Header -->
        <div class="flex items-start justify-between p-6 border-b border-white/10">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
              <Navigation class="w-6 h-6 text-emerald-400" />
            </div>
            <div>
              <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">{{ notification.action || 'EVENTO DE RASTREO' }}</p>
              <h1 class="text-xl font-semibold text-white">{{ notification.action || 'Inicio de Movimiento' }}</h1>
            </div>
          </div>
          <div class="text-right">
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
              <Clock class="w-3.5 h-3.5" />
              <span>{{ notification.update_time }}</span>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500">
              <Hash class="w-3.5 h-3.5" />
              <span>ID: {{ notification.id }}</span>
            </div>
          </div>
        </div>

        <!-- Mapa -->
        <div
          v-if="notification.lat && notification.lng"
          class="h-[40vh] bg-[#050814] border-b border-white/10"
        >
          <div ref="mapEl" class="h-full w-full"></div>
        </div>

        <!-- Media -->
        <div
          v-if="notification.url"
          class="bg-black/60 border-b border-white/10 flex items-center justify-center"
        >
          <img
            v-if="notification.url && notification.url.toLowerCase().match(/\.(jpg|jpeg|png|gif|webp)(\?.*)?$/)"
            :src="notification.url"
            alt="Media de la notificación"
            class="max-h-[50vh] w-full object-contain"
          />
          <video
            v-else-if="notification.url && notification.url.toLowerCase().match(/\.mp4(\?.*)?$/)"
            :src="notification.url"
            controls
            class="max-h-[50vh] w-full object-contain bg-black"
          ></video>
        </div>

        <!-- Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-0 border-b border-white/10">
          <!-- Ubicación -->
          <div class="p-6 border-r border-white/10">
            <div class="flex items-center gap-2 mb-4">
              <MapPin class="w-4 h-4 text-emerald-400" />
              <h3 class="text-xs uppercase tracking-wider text-gray-400 font-medium">UBICACIÓN</h3>
            </div>
            <div class="space-y-3">
              <div>
                <p class="text-xs text-gray-500 mb-0.5">Latitud</p>
                <p class="text-sm text-white font-mono">{{ notification.lat }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-0.5">Longitud</p>
                <p class="text-sm text-white font-mono">{{ notification.lng }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-0.5">Última Actualización</p>
                <p class="text-sm text-gray-300">{{ notification.update_time }}</p>
              </div>
            </div>
          </div>

          <!-- Dispositivo -->
          <div class="p-6 border-r border-white/10">
            <div class="flex items-center gap-2 mb-4">
              <Smartphone class="w-4 h-4 text-emerald-400" />
              <h3 class="text-xs uppercase tracking-wider text-gray-400 font-medium">DISPOSITIVO</h3>
            </div>
            <div class="space-y-3">
              <div>
                <p class="text-xs text-gray-500 mb-0.5">Nombre</p>
                <p class="text-base text-emerald-400 font-semibold">{{ notification.device?.name || 'Sin nombre' }}</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <p class="text-xs text-gray-500 mb-0.5">Device ID</p>
                  <p class="text-sm text-white font-mono">{{ notification.device_id }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 mb-0.5">IMEI</p>
                  <p class="text-sm text-white font-mono">{{ notification.device?.imei || notification.imei }}</p>
                </div>
              </div>
              <div v-if="notification.device?.lastdrop">
                <p class="text-xs text-gray-500 mb-0.5">Último GPS</p>
                <p class="text-sm text-gray-300">{{ notification.device.lastdrop.update_time }}</p>
              </div>
            </div>
          </div>

          <!-- Cliente -->
          <div class="p-6">
            <div class="flex items-center gap-2 mb-4">
              <User class="w-4 h-4 text-emerald-400" />
              <h3 class="text-xs uppercase tracking-wider text-gray-400 font-medium">CLIENTE</h3>
            </div>
            <div class="space-y-3">
              <div>
                <p class="text-xs text-gray-500 mb-0.5">Nombre</p>
                <p class="text-base text-white font-semibold">{{ notification.customer?.name || 'Sin cliente' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-0.5">Customer ID</p>
                <p class="text-sm text-white font-mono">{{ notification.customer_id }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-6 py-4 bg-black/20">
          <div class="flex items-center gap-2 text-xs text-gray-400">
            <Radio class="w-3.5 h-3.5 text-emerald-400" />
            <span>Señal activa</span>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <Smartphone class="w-3.5 h-3.5" />
            <span>IMEI: {{ notification.imei }}</span>
          </div>
        </div>
      </div>
      <div v-else class="text-sm text-gray-400 text-center py-8">
        No se encontró información de la notificación.
      </div>

      <!-- Botón volver -->
      <div class="mt-6 text-center">
        <a href="/app" class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-gray-100 transition">
          <ArrowLeft class="w-4 h-4" />
          Volver al inicio
        </a>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, nextTick } from 'vue'
import axios from 'axios'
import { Navigation, Clock, Hash, MapPin, Smartphone, User, Radio, ArrowLeft } from 'lucide-vue-next'

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
})

const loading = ref(true)
const error = ref('')
const notification = ref(null)
const email = ref('')

// Google Maps
const mapEl = ref(null)
let gmap = null
let marker = null

async function loadGoogleMaps() {
  if (window.google && window.google.maps) return window.google.maps
  const key = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || "AIzaSyBHfPjWCRBC7GSLANLNaXjlmSHgB9I1ZPQ"
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

async function initMapForNotification() {
  console.log('[Notification] initMapForNotification called', {
    hasNotification: !!notification.value,
    lat: notification.value?.lat,
    lng: notification.value?.lng,
    hasMapEl: !!mapEl.value
  })
  
  if (!notification.value || !notification.value.lat || !notification.value.lng) {
    console.log('[Notification] Missing notification or coordinates')
    return
  }
  
  await nextTick()
  
  if (!mapEl.value) {
    console.log('[Notification] mapEl.value is null after nextTick')
    return
  }
  
  console.log('[Notification] Loading Google Maps...')
  const maps = await loadGoogleMaps()
  if (!maps) {
    console.log('[Notification] Failed to load Google Maps')
    return
  }
  
  console.log('[Notification] Google Maps loaded successfully')

  const lat = Number(notification.value.lat)
  const lng = Number(notification.value.lng)
  const center = { lat, lng }
  
  console.log('[Notification] Creating map with center:', center)

  gmap = new maps.Map(mapEl.value, {
    center,
    zoom: 15,
    mapTypeId: 'roadmap',
    gestureHandling: 'greedy',
    scrollwheel: true,
  })
  
  console.log('[Notification] Map created, adding marker')

  marker = new maps.Marker({
    position: center,
    map: gmap,
    title: `IMEI ${notification.value.imei} - ${notification.value.action || ''}`,
  })

  const info = new maps.InfoWindow({
    content: `
      <div style="min-width:200px;font-size:12px;">
        <div><strong>Acción:</strong> ${notification.value.action || ''}</div>
        <div><strong>IMEI:</strong> ${notification.value.imei}</div>
        <div><strong>Hora:</strong> ${notification.value.update_time}</div>
        <div><strong>Lat:</strong> ${notification.value.lat}</div>
        <div><strong>Lng:</strong> ${notification.value.lng}</div>
      </div>
    `,
  })

  marker.addListener('click', () => {
    info.open({ map: gmap, anchor: marker })
  })
  
  console.log('[Notification] Map initialization complete')
}

onMounted(async () => {
  console.log('[Notification] onMounted called, id:', props.id)
  email.value = localStorage.getItem('auth_email') || ''

  try {
    const token = localStorage.getItem('auth_token')
    if (!token) throw new Error('Sin token de autenticación')

    console.log('[Notification] Fetching notification data...')
    const res = await axios.get(
      `https://app.dygne.com/api/pwa/push/${props.id}`,
      {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      },
    )

    console.log('[Notification] Data received:', res.data)
    notification.value = res.data
    loading.value = false
    
    console.log('[Notification] Calling initMapForNotification...')
    await initMapForNotification()
  } catch (e) {
    console.error('[Notification] Error:', e)
    error.value = e?.message || 'No se pudo cargar la notificación'
    loading.value = false
  }
})
</script>