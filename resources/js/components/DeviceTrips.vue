<template>
  <div class="text-sm text-gray-100 space-y-3">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-base">Viajes por geocerca</h2>
      <span v-if="loading" class="text-xs text-gray-400">Cargando viajes…</span>
    </div>

    <div v-if="error" class="text-xs text-red-400 bg-red-500/10 border border-red-500/40 rounded px-3 py-2">
      {{ error }}
    </div>

    <div v-if="!loading && trips.length === 0 && !error" class="text-xs text-gray-400">
      No hay viajes para mostrar en este rango.
    </div>

    <ul v-else class="space-y-2 max-h-72 overflow-y-auto pr-1">
      <li
        v-for="(trip, idx) in trips"
        :key="idx"
        class="border border-white/10 rounded-lg px-3 py-2 bg-white/5 hover:bg-white/10 transition-colors text-xs"
      >
        <div class="flex items-center justify-between gap-2">
          <div class="font-medium text-gray-100 truncate">
            {{ trip.from || trip.from_geofence?.name || 'N/A' }}
            <span class="mx-1 text-gray-500">→</span>
            {{ trip.to || trip.to_geofence?.name || 'N/A' }}
          </div>
          <div class="flex items-center gap-2">
            <div class="text-[11px] text-blue-300 whitespace-nowrap">
              {{ trip.time || 'Sin tiempo' }}
            </div>
            <button
              type="button"
              class="px-2 py-1 text-[11px] rounded-full border border-white/15 bg-white/5 hover:bg-white/10 text-gray-100 whitespace-nowrap"
              @click="openMediaModal(trip)"
            >
              Media ({{ (trip.media || []).length }})
            </button>
          </div>
        </div>
        <div class="mt-1 flex items-center justify-between text-[11px] text-gray-400">
          <div class="flex flex-wrap gap-x-2 gap-y-1">
            <span v-if="trip.from_geofence" class="inline-flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
              {{ trip.from_geofence.name }}
            </span>
            <span v-if="trip.to_geofence" class="inline-flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
              {{ trip.to_geofence.name }}
            </span>
          </div>
          <div class="text-[11px] text-gray-400 whitespace-nowrap">
            Media total: {{ (trip.media || []).length }}
          </div>
        </div>
      </li>
    </ul>
  </div>
  <!-- Modal de media por viaje -->
  <div
    v-if="showMediaModal && selectedTripMedia.length"
    class="fixed inset-0 z-40 flex items-center justify-center bg-black/70"
  >
    <div
      class="relative bg-[#050814] border border-white/10 rounded-2xl shadow-xl max-w-xl w-[92vw] sm:w-[520px] max-h-[80vh] overflow-hidden"
    >
      <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
        <h3 class="text-sm font-medium text-gray-100">
          Media del viaje ({{ selectedTripMedia.length }})
        </h3>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-100 text-sm"
          @click="closeMediaModal"
        >
          ✕
        </button>
      </div>
      <div class="p-3 max-h-[calc(80vh-48px)] overflow-y-auto space-y-2 text-xs text-gray-100">
        <div
          v-for="(m, i) in selectedTripMedia"
          :key="i"
          class="flex items-center gap-3 p-2 border border-white/10 bg-black/40 rounded-lg hover:bg-white/5 cursor-pointer"
          @click="handleSelectMedia(m)"
        >
          <div class="w-12 h-12 bg-black/60 rounded overflow-hidden flex items-center justify-center border border-white/10">
            <template v-if="(m.extension || '').toLowerCase() === '.mp4'">
              <span class="text-lg">🎥</span>
            </template>
            <template v-else>
              <img :src="m.url" alt="media" class="w-full h-full object-cover" />
            </template>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-medium truncate">
              {{ mediaLabel(m) }}
            </p>
            <p class="text-[11px] text-gray-400 truncate">
              {{ m.captured_at ?? m.update_time ?? m.time ?? '' }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Componente simple temporal para la pestaña "Viajes"
import { onMounted, ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  imei: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(['select'])

const loading = ref(false)
const error = ref('')
const trips = ref([])
const showMediaModal = ref(false)
const selectedTripMedia = ref([])

async function fetchTrips() {
  if (!props.imei) return
  loading.value = true
  error.value = ''

  try {
    const token = localStorage.getItem('auth_token')
    if (!token) {
      throw new Error('Sin token de autenticación')
    }

    const res = await axios.get(`https://app.dygne.com/api/devices/${props.imei}/geofence-trips`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    })

    const data = res?.data || {}
    trips.value = Array.isArray(data.trips) ? data.trips : []
    console.log('geofence-trips', { imei: props.imei, trips: trips.value.length })
  } catch (e) {
    console.error('Error al cargar geofence-trips', e)
    error.value = e?.response?.data?.message || e?.message || 'No se pudieron cargar los viajes'
  } finally {
    loading.value = false
  }
}

function openMediaModal(trip) {
  const items = Array.isArray(trip?.media) ? trip.media : []
  selectedTripMedia.value = items
  if (items.length > 0) {
    showMediaModal.value = true
  }
}

function closeMediaModal() {
  showMediaModal.value = false
  selectedTripMedia.value = []
}

function mediaLabel(m) {
  const ext = (m?.extension || '').toLowerCase()
  const t = (m?.type || '').toUpperCase()
  const isVideo = ext === '.mp4'
  const isPhoto = ext === '.jpeg'
  const isFront = t.includes('FRONT')
  const isRear = t.includes('REAR')

  const base = isVideo ? 'Video' : (isPhoto ? 'Foto' : 'Media')
  const side = isFront ? 'Frontal' : (isRear ? 'Interior' : '')
  return side ? `${base} ${side}` : base
}

function handleSelectMedia(m) {
  emit('select', m)
}

onMounted(fetchTrips)

watch(
  () => props.imei,
  (newVal, oldVal) => {
    if (newVal && newVal !== oldVal) {
      fetchTrips()
    }
  }
)
</script>

