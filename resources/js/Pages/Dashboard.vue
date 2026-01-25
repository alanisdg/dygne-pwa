<template>
  <div class="min-h-screen bg-[#0f1418] text-gray-100">
    <div class="max-w-3xl mx-auto px-4 ">
      <!-- Header -->
      <div class="px-4 py-3 mb-5 flex flex-col gap-1">
        
        <AppHeader class="mt-1" title="Dashboard" :email="email" />
      </div>

      <!-- Devices section -->
      <div class="space-y-4">
        <div class="flex items-center justify-between px-1">
          <div class="flex items-center gap-2">
             
          </div>
          <span class="text-xs text-gray-400" v-if="filtered.length">{{ filtered.length }} activos</span>
        </div>

        <div>
          <input
            v-model="q"
            type="text"
            placeholder="Buscar por nombre…"
            class="w-full rounded-full border border-white/10 bg-[#050814] px-4 py-2 text-sm placeholder:text-gray-500 outline-none focus:ring-2 focus:ring-blue-500/70 focus:border-blue-500/70"
          />
        </div>

        <div v-if="loadingDevices" class="text-sm text-gray-400 px-1">Cargando dispositivos…</div>
        <div v-else-if="devicesError" class="text-sm text-red-400 px-1">{{ devicesError }}</div>

        <div v-else class="space-y-4">
          <div
            v-for="d in filtered"
            :key="d.id"
            class="block rounded-lg bg-[#1e2734] border px-5 py-4 transition-colors cursor-pointer select-none relative"
            :class="isDeviceSelected(d.id)
              ? 'border-emerald-400/80 shadow-[0_0_0_1px_rgba(16,185,129,0.5)] bg-emerald-500/10'
              : 'border-[#161c25] hover:border-[#1e2630]'"
            @click="handleCardClick(d)"
            @mousedown.passive="onPressStart($event, d)"
            @touchstart.passive="onPressStart($event, d)"
            @mouseup="onPressEnd"
            @mouseleave="onPressEnd"
            @touchend="onPressEnd"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <p class="text-base font-semibold text-blue-200 tracking-tight">{{ d.name || 'Sin nombre' }}</p>
                <p class="text-xs text-gray-500">IMEI: {{ d.imei }}</p>
              </div>
              <div class="absolute top-3 right-3 flex items-center justify-center w-7 h-7 rounded-full border border-white/15 bg-black/40">
                <Camera v-if="d.has_camera" class="w-3.5 h-3.5 text-emerald-300" />
                <CameraOff v-else class="w-3.5 h-3.5 text-gray-500" />
              </div>
             </div>

            <div class="mt-3 flex flex-wrap items-center gap-4 text-xs">
              <div class="flex items-center gap-2 text-emerald-400" v-if="d.status">
                <span class="inline-block h-6 w-6 rounded-full bg-emerald-500/10 flex items-center justify-center text-sm">📡</span>
                <span class="font-medium">{{ d.status }} </span>
              </div>
              <div class="flex flex-wrap items-center gap-3 text-gray-300" v-if="d.lastdrop && d.lastdrop.speed != null">
                <div class="flex items-center gap-2">
                  <span class="inline-flex h-6 w-6 rounded-full bg-orange-500/10 items-center justify-center text-orange-300">
                    <Gauge class="w-3.5 h-3.5" />
                  </span>
                  <span class="font-medium">{{ d.lastdrop.speed }} km/h</span>
                </div>
                <div v-if="d.engine_status !== undefined" class="flex items-center gap-1 text-xs">
                  <span
                    class="inline-flex h-6 w-6 rounded-full items-center justify-center"
                    :class="d.engine_status == 1 ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/5 text-gray-400'"
                  >
                    <CirclePower class="w-3.5 h-3.5" />
                  </span>
                  <span :class="d.engine_status == 1 ? 'text-emerald-300' : 'text-gray-400'">
 
                  </span>
                </div>
                <div
                  v-if="d.lastdrop && (d.lastdrop.satelites != null || d.lastdrop.satellites != null)"
                  class="flex items-center gap-1 text-xs text-gray-400"
                >
                  <div class="flex items-end gap-[1px] h-3">
                    <span
                      v-for="n in 5"
                      :key="n"
                      class="w-[2px] rounded-sm"
                      :class="[
                        n <= getSatLevel(d) ? getSatColorClass(d) : 'bg-gray-700/60',
                        n === 1 ? 'h-1' : n === 2 ? 'h-1.5' : n === 3 ? 'h-2' : n === 4 ? 'h-2.5' : 'h-3'
                      ]"
                    ></span>
                  </div> 
                </div>
              </div>
              <LastUpdateBadge v-if="d.lastdrop && d.lastdrop.update_time" :value="d.lastdrop.update_time" />
            </div>
          </div>

          <div v-if="filtered.length === 0" class="text-sm text-gray-500 px-1 py-6 text-center border border-dashed border-white/10 rounded-2xl">
            Sin dispositivos
          </div>
        </div>
      </div>
    </div>
  </div>

  <div
    v-if="isMultiSelecting && selectedDevices.length"
    class="fixed inset-x-0 bottom-0 z-30 bg-[#020617] border-t border-white/10 px-4 py-3 flex items-center justify-between gap-3"
  >
    <div class="flex-1 min-w-0 text-xs text-gray-200">
      <p class="mb-1">{{ selectedDevices.length }} seleccionados</p>
      <div class="flex items-center gap-2 flex-wrap">
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60"
          :disabled="shareGenerating || !selectedDevices.length"
          @click="openShareModal"
        >
          {{ shareGenerating ? 'Generando link…' : 'Compartir' }}
        </button>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="px-3 py-1.5 rounded-full text-xs border border-white/20 text-gray-200 bg-white/5 hover:bg-white/10"
        @click="clearSelection"
      >
        Cancelar
      </button>
    </div>
  </div>

  <div
    v-if="showShareModal"
    class="fixed inset-0 z-40 flex items-center justify-center"
  >
    <div class="absolute inset-0 bg-black/70" @click="closeShareModal"></div>
    <div class="relative bg-[#020617] border border-white/10 rounded-2xl shadow-xl w-[92vw] max-w-md p-4 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-100">Compartir dispositivos seleccionados</h3>
        <button class="text-gray-400 hover:text-gray-100 text-sm" @click="closeShareModal">✕</button>
      </div>

      <div class="text-xs text-gray-300">
        <p class="mb-2">Selecciona por cuántas horas será válida la liga de seguimiento.</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="h in [1,3,6,24]"
            :key="h"
            type="button"
            class="px-3 py-1.5 rounded-full border text-xs"
            :class="shareHours === h
              ? 'bg-blue-600 text-white border-blue-400'
              : 'bg-white/5 text-gray-200 border-white/15 hover:border-blue-400/70'"
            @click="shareHours = h"
          >
            {{ h }} h
          </button>
        </div>
      </div>

      <div class="text-xs text-gray-300">
        <p class="mb-1">Elige qué dispositivos incluir en la liga:</p>
        <div class="max-h-40 overflow-y-auto rounded-lg border border-white/10 bg-black/20 px-2 py-2 space-y-1">
          <div
            v-for="dev in selectedDevices"
            :key="dev.id"
            class="flex items-center gap-2 text-[11px] text-gray-200"
          >
            <input
              v-model="shareDeviceIds"
              :value="dev.id"
              type="checkbox"
              class="w-3.5 h-3.5 rounded border border-white/20 bg-black"
            />
            <span class="truncate">
              {{ dev.name || 'Sin nombre' }} (ID {{ dev.id }})
            </span>
          </div>
          <div v-if="!selectedDevices.length" class="text-gray-500 text-[11px]">
            No hay dispositivos seleccionados.
          </div>
        </div>
      </div>

      <div v-if="shareError" class="text-xs text-red-400">
        {{ shareError }}
      </div>

      <div v-if="shareUrl" class="space-y-2">
        <label class="block text-xs text-gray-400">Liga generada</label>
        <div class="flex items-center gap-2">
          <input
            type="text"
            readonly
            class="flex-1 px-2 py-1.5 text-xs rounded border border-white/15 bg-black/40 text-gray-100 truncate"
            :value="shareUrl"
          />
          <button
            type="button"
            class="px-3 py-1.5 rounded-full text-xs bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60"
            :disabled="!shareUrl"
            @click="shareUrlWithNavigator"
          >
            Share
          </button>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs border border-white/20 text-gray-200 bg-white/5 hover:bg-white/10"
          @click="closeShareModal"
        >
          Cerrar
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60"
          :disabled="shareGenerating || !shareDeviceIds.length"
          @click="generateShareUrl"
        >
          {{ shareGenerating ? 'Generando…' : 'Generar link' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import LastUpdateBadge from '@/components/LastUpdateBadge.vue'
import AppHeader from '@/components/AppHeader.vue'
import io from 'socket.io-client'
import { Gauge, CirclePower, Camera, CameraOff } from 'lucide-vue-next'

const email = ref('')

// Devices state
const devices = ref([])
const loadingDevices = ref(false)
const devicesError = ref('')
const q = ref('')

const isMultiSelecting = ref(false)
const selectedDeviceIds = ref([])

const showShareModal = ref(false)
const shareHours = ref(1)
const shareGenerating = ref(false)
const shareUrl = ref('')
const shareError = ref('')
const shareDeviceIds = ref([])

const filtered = computed(() => {
  const term = q.value.trim().toLowerCase()
  if (!term) return devices.value
  return devices.value.filter(d => String(d.name || '').toLowerCase().includes(term))
})

const selectedDevices = computed(() => {
  const ids = selectedDeviceIds.value
  if (!Array.isArray(ids) || !ids.length) return []
  return devices.value.filter(d => ids.includes(d.id))
})

function isDeviceSelected(id) {
  return selectedDeviceIds.value.includes(id)
}

function toggleDeviceSelection(id) {
  const current = selectedDeviceIds.value.slice()
  const idx = current.indexOf(id)
  if (idx === -1) {
    current.push(id)
  } else {
    current.splice(idx, 1)
  }
  selectedDeviceIds.value = current
  if (!current.length) {
    isMultiSelecting.value = false
  }
}

function clearSelection() {
  selectedDeviceIds.value = []
  isMultiSelecting.value = false
}

function openShareModal() {
  shareError.value = ''
  shareUrl.value = ''
  shareHours.value = 1
  // Inicializar la selección del modal con los dispositivos actualmente seleccionados
  shareDeviceIds.value = selectedDeviceIds.value.slice()
  showShareModal.value = true
}

function closeShareModal() {
  showShareModal.value = false
}

async function generateShareUrl() {
  try {
    shareError.value = ''
    shareUrl.value = ''
    if (!shareDeviceIds.value.length) {
      shareError.value = 'No hay dispositivos seleccionados.'
      return
    }

    const token = localStorage.getItem('auth_token')
    if (!token) {
      shareError.value = 'Sin token de autenticación.'
      return
    }

    shareGenerating.value = true

    const body = {
      device_ids: shareDeviceIds.value,
      hours: Number(shareHours.value) || 1,
    }

    console.log('[Dashboard] generateShareUrl body', body)

    const res = await fetch('https://app.dygne.com/api/shares', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(body),
    })

    if (!res.ok) {
      const text = await res.text().catch(() => '')
      console.error('[Dashboard] Error response from /api/shares', res.status, text)
      throw new Error(`Error ${res.status} al generar la liga`)
    }

    const data = await res.json().catch(() => ({}))
    console.log('[Dashboard] /api/shares response', data)

    const url = data?.url || data?.data?.url || ''
    if (!url) {
      throw new Error('La respuesta no contiene una URL válida')
    }
    shareUrl.value = url
  } catch (e) {
    shareError.value = e.message || 'No se pudo generar la liga de seguimiento.'
  } finally {
    shareGenerating.value = false
  }
}

async function shareUrlWithNavigator() {
  if (!shareUrl.value) return
  const url = shareUrl.value

  try {
    if (navigator.share) {
      const title = 'Seguimiento de dispositivos'
      const text = `Liga de seguimiento válida por ${shareHours.value} horas.`
      await navigator.share({ title, text, url })
      return
    }
  } catch (e) {
    console.error('[Dashboard] Error usando navigator.share', e)
  }

  try {
    window.open(url, '_blank')
  } catch (e) {
    console.error('[Dashboard] Error abriendo URL compartida', e)
  }
}

function getSatCount(d) {
  if (!d || !d.lastdrop) return 0
  const raw = d.lastdrop.satelites ?? d.lastdrop.satellites ?? 0
  const num = Number(raw)
  return Number.isNaN(num) ? 0 : num
}

function getSatLevel(d) {
  const s = getSatCount(d)
  if (s <= 0) return 0
  if (s <= 3) return 1
  if (s <= 5) return 2
  if (s <= 7) return 3
  if (s <= 10) return 4
  return 5
}

function getSatColorClass(d) {
  const level = getSatLevel(d)
  if (level <= 1) return 'bg-red-500'
  if (level === 2) return 'bg-yellow-400'
  return 'bg-emerald-400'
}

const LONG_PRESS_MS = 600
const longPressTimer = ref(null)
const lastInteractionWasLongPress = ref(false)

function onPressStart(event, device) {
  console.log('[Dashboard] onPressStart', { id: device.id, name: device.name, type: event.type })
  if (longPressTimer.value) {
    clearTimeout(longPressTimer.value)
  }
  longPressTimer.value = setTimeout(() => {
    console.log('[Dashboard] LONG PRESS TRIGGERED', { id: device.id, name: device.name })
    isMultiSelecting.value = true
    lastInteractionWasLongPress.value = true
    if (!isDeviceSelected(device.id)) {
      console.log('[Dashboard] Selecting from long press', { id: device.id })
      toggleDeviceSelection(device.id)
    }
  }, LONG_PRESS_MS)
}

function onPressEnd() {
  console.log('[Dashboard] onPressEnd')
  if (longPressTimer.value) {
    clearTimeout(longPressTimer.value)
    longPressTimer.value = null
  }
}

function handleCardClick(device) {
  console.log('[Dashboard] handleCardClick', {
    id: device.id,
    name: device.name,
    isMultiSelecting: isMultiSelecting.value,
    selectedIds: selectedDeviceIds.value.slice(),
    lastInteractionWasLongPress: lastInteractionWasLongPress.value,
  })

  if (lastInteractionWasLongPress.value) {
    console.log('[Dashboard] Ignoring click immediately after long press')
    lastInteractionWasLongPress.value = false
    return
  }

  if (isMultiSelecting.value) {
    console.log('[Dashboard] Toggling selection from click in multi-select mode')
    toggleDeviceSelection(device.id)
    return
  }
  console.log('[Dashboard] Navigating to device detail')
  router.visit(`/devices/${device.id}`)
}

let socketGps = null
let socketCalamp = null

function setupSockets() {
  if (socketGps || socketCalamp) return
  try {
    const opts = { secure: true }
    socketGps = io.connect('https://app.dygne.com:3002', opts)
    socketCalamp = io.connect('https://app.dygne.com:3004', opts)

    const handleDrop = (drop) => {
      if (!drop || !drop.imei) return
      const idx = devices.value.findIndex(d => String(d.imei) === String(drop.imei))
      if (idx === -1) return
      const current = devices.value[idx] || {}
      const prevLast = current.lastdrop || {}
      const updatedLast = {
        ...prevLast,
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
        update_time: drop.update_time || drop.updateTime || drop.timeOfFix || prevLast.update_time || '',
      }
      const updated = { ...current, lastdrop: updatedLast }
      const copy = devices.value.slice()
      copy.splice(idx, 1, updated)
      devices.value = copy
    }

    socketGps.on('drop', handleDrop)
    socketCalamp.on('drop', handleDrop)
  } catch (e) {
    console.error('[Dashboard] Error configurando sockets', e)
  }
}

onUnmounted(() => {
  try {
    if (socketGps) {
      socketGps.disconnect()
      socketGps = null
    }
    if (socketCalamp) {
      socketCalamp.disconnect()
      socketCalamp = null
    }
  } catch (e) {
    console.warn('[Dashboard] Error desconectando sockets', e)
  }
})

onMounted(() => {
  // Protege el acceso: si no hay token, regresa a login
  const token = localStorage.getItem('auth_token')
  if (!token) {
    window.location.href = '/'
    return
  }
  email.value = localStorage.getItem('auth_email') || '(sin email)'
  fetchDevices().then(() => {
    setupSockets()
  }).catch(() => {
    // Incluso si falla la carga inicial, intentamos conectar sockets para futuros datos
    setupSockets()
  })
})

async function fetchDevices() {
  devicesError.value = ''
  loadingDevices.value = true
  try {
    const token = localStorage.getItem('auth_token')
    if (!token) throw new Error('Sin token de autenticación')
    const res = await fetch('https://app.dygne.com/api/devices', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
      mode: 'cors',
    })
    if (!res.ok) throw new Error(`Error ${res.status} al obtener dispositivos`)
    const data = await res.json()
    devices.value = Array.isArray(data) ? data : (data.data || [])
  } catch (e) {
    devicesError.value = e.message || 'No se pudieron cargar los dispositivos'
  } finally {
    loadingDevices.value = false
  }
}
</script>
