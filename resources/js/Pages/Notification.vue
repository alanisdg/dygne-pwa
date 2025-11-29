<template>
  <div class="min-h-screen bg-black text-gray-100 p-0 sm:p-4">
    <div class="max-w-3xl mx-auto space-y-4">
      <AppHeader :title="`Notificación #${id}`" backHref="/app" :email="email" />

      <!-- Mapa centrado en la lat/lng de la notificación -->
      <div
        v-if="notification && notification.lat && notification.lng"
        class="h-[50vh] bg-[#050814] rounded-3xl overflow-hidden border border-white/5"
      >
        <div ref="mapEl" class="h-full w-full"></div>
      </div>

      <div class="bg-[#050814] border border-white/5 rounded-3xl p-4 sm:p-5">
        <div v-if="loading" class="text-sm text-gray-400">
          Cargando notificación...
        </div>
        <div v-else-if="error" class="text-sm text-red-400">
          {{ error }}
        </div>
        <div v-else-if="notification" class="space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-white/10">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-400">Acción</p>
              <p class="text-lg font-semibold text-gray-100">
                {{ notification.action || 'Sin descripción' }}
              </p>
            </div>
            <div class="text-xs text-gray-400 space-y-1 text-right sm:text-left">
              <div>
                <span class="text-gray-500">Hora:</span>
                <span class="text-gray-200">{{ notification.update_time }}</span>
              </div>
              <div>
                <span class="text-gray-500">IMEI:</span>
                <span class="text-gray-200">{{ notification.imei }}</span>
              </div>
              <div>
                <span class="text-gray-500">ID noti:</span>
                <span class="text-gray-200">{{ notification.id }}</span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
            <div class="bg-black/40 border border-white/5 rounded-2xl p-3">
              <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Marker</p>
              <p class="text-gray-300 text-xs break-all">
                Lat: <span class="text-gray-100">{{ notification.lat }}</span>
              </p>
              <p class="text-gray-300 text-xs break-all">
                Lng: <span class="text-gray-100">{{ notification.lng }}</span>
              </p>
              <p class="text-gray-300 text-xs mt-1">
                Actualizado: <span class="text-gray-100">{{ notification.update_time }}</span>
              </p>
            </div>

            <div class="bg-black/40 border border-white/5 rounded-2xl p-3">
              <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Device</p>
              <p class="text-gray-100 text-sm font-medium">
                {{ notification.device?.name || 'Sin nombre' }}
              </p>
              <p class="text-gray-300 text-xs">IMEI: {{ notification.device?.imei }}</p>
              <p class="text-gray-300 text-xs">Device ID: {{ notification.device_id }}</p>
              <p v-if="notification.device?.lastdrop" class="text-gray-300 text-xs mt-1">
                Último GPS: {{ notification.device.lastdrop.update_time }}
              </p>
            </div>

            <div class="bg-black/40 border border-white/5 rounded-2xl p-3">
              <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Cliente</p>
              <p class="text-gray-100 text-sm font-medium">
                {{ notification.customer?.name || 'Sin cliente' }}
              </p>
              <p class="text-gray-300 text-xs">Customer ID: {{ notification.customer_id }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-gray-400">
          No se encontró información de la notificación.
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import AppHeader from '@/components/AppHeader.vue'

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
  const key = import.meta.env.VITE_GOOGLE_MAPS_API_KEY
  if (!key) return null
  return new Promise((resolve, reject) => {
    const cbName = '__gmaps_cb_' + Math.random().toString(36).slice(2)
    window[cbName] = () => {
      resolve(window.google.maps)
      delete window[cbName]
    }
    const s = document.createElement('script')
    s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(
      key,
    )}&callback=${cbName}`
    s.async = true
    s.onerror = reject
    document.head.appendChild(s)
  })
}

async function initMapForNotification() {
  if (!notification.value || !notification.value.lat || !notification.value.lng) return
  const maps = await loadGoogleMaps()
  if (!maps || !mapEl.value) return

  const lat = Number(notification.value.lat)
  const lng = Number(notification.value.lng)
  const center = { lat, lng }

  gmap = new maps.Map(mapEl.value, {
    center,
    zoom: 15,
    mapTypeId: 'roadmap',
    gestureHandling: 'greedy',
    scrollwheel: true,
  })

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
}

onMounted(async () => {
  email.value = localStorage.getItem('auth_email') || ''

  try {
    const token = localStorage.getItem('auth_token')
    if (!token) throw new Error('Sin token de autenticación')

    const res = await axios.get(
      `https://app.dygne.com/api/pwa/push/${props.id}`,
      {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      },
    )

    notification.value = res.data
    await initMapForNotification()
  } catch (e) {
    error.value = e?.message || 'No se pudo cargar la notificación'
  } finally {
    loading.value = false
  }
})
</script>