<template>
  <div class="min-h-screen bg-black text-gray-100">
    <div class="max-w-3xl mx-auto px-4 py-6">
      <!-- Header -->
      <div class="rounded-3xl bg-[#050814] border border-white/5 px-5 py-4 mb-6 flex flex-col gap-1">
        
        <AppHeader class="mt-1" title="Dashboard" :email="email" />
      </div>

      <!-- Devices section -->
      <div class="space-y-4">
        <div class="flex items-center justify-between px-1">
          <div class="flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-500/10 text-blue-400 text-sm">
              ⎍
            </span>
            <h2 class="text-lg font-semibold">Dispositivos</h2>
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
          <Link
            v-for="d in filtered"
            :key="d.id"
            :href="`/devices/${d.id}`"
            class="block rounded-2xl bg-[#050814]   border-blue-500/40 hover:border-blue-400/80 shadow-[0_0_0_1px_rgba(37,99,235,0.2)] px-5 py-4 transition-colors"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <p class="text-base font-semibold text-blue-200 tracking-tight">{{ d.name || 'Sin nombre' }}</p>
                <p class="text-xs text-gray-500">IMEI: {{ d.imei }}</p>
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
          </Link>

          <div v-if="filtered.length === 0" class="text-sm text-gray-500 px-1 py-6 text-center border border-dashed border-white/10 rounded-2xl">
            Sin dispositivos
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import LastUpdateBadge from '@/components/LastUpdateBadge.vue'
import AppHeader from '@/components/AppHeader.vue'
import io from 'socket.io-client'
import { Gauge, CirclePower } from 'lucide-vue-next'

const email = ref('')

// Devices state
const devices = ref([])
const loadingDevices = ref(false)
const devicesError = ref('')
const q = ref('')

const filtered = computed(() => {
  const term = q.value.trim().toLowerCase()
  if (!term) return devices.value
  return devices.value.filter(d => String(d.name || '').toLowerCase().includes(term))
})

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
