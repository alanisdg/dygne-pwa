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
        <div v-if="lastdrop" class="h-full w-full">
          <iframe
            class="h-full w-full border-0"
            :src="mapUrl"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
          ></iframe>
        </div>
        <div v-else class="h-full w-full flex items-center justify-center text-gray-500 text-sm">
          Sin coordenadas para mostrar
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

const props = defineProps({ id: String })
const name = ref('')
const loading = ref(true)
const error = ref('')
const lastdrop = ref(null)

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
  name.value = sessionStorage.getItem(cacheKey) || ''

  try {
    const token = localStorage.getItem('auth_token')
    if (!token) throw new Error('Sin token de autenticación')
    const res = await fetch(`https://app.dygne.com/api/devices/list/${props.id}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
      mode: 'cors',
    })
    if (!res.ok) throw new Error(`Error ${res.status} al obtener el dispositivo`)
    const data = await res.json()
    const dev = data && (data.data || data)
    name.value = dev?.name || name.value || ''
    lastdrop.value = dev?.lastdrop || null
    if (name.value) sessionStorage.setItem(cacheKey, name.value)
  } catch (e) {
    error.value = e.message || 'No se pudo cargar el dispositivo'
  } finally {
    loading.value = false
  }
})
</script>
