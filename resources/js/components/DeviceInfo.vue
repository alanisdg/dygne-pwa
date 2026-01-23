<template>
  <div class="rounded-t-3xl sm:rounded-3xl bg-[#050814] border border-white/5 shadow-sm p-4 sm:p-5 -mt-4 sm:mt-4 relative z-10 text-gray-100">
    <p v-if="error" class="text-red-400 text-sm">{{ error }}</p>
    <template v-else>
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-300">Nombre: <span class="font-medium text-gray-100">{{ name || '(sin nombre)' }}<span v-if="device && device.imei"> - {{ device.imei }}</span></span></p>
        <a v-if="lastdrop" :href="externalMapUrl" target="_blank" class="text-xs sm:text-sm text-blue-300 hover:text-blue-200 hover:underline">Abrir en Maps</a>
      </div>

      <div v-if="lastdrop" class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
        <div class="rounded-xl border border-white/10 bg-black/40 p-3">
          <div class="flex items-center justify-between mb-1">
            <p class="text-gray-400">Actualizados</p>
            <Clock3 class="w-3.5 h-3.5 text-blue-300" />
          </div>
          <p class="font-medium text-gray-100">{{ lastdrop.update_time }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-black/40 p-3">
          <div class="flex items-center justify-between mb-1">
            <p class="text-gray-400">Velocidad</p>
            <Gauge class="w-3.5 h-3.5 text-orange-300" />
          </div>
          <p class="font-medium text-gray-100">{{ lastdrop.speed }} km/h</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-black/40 p-3">
          <p class="text-gray-400">Coordenadas</p>
          <p class="font-medium text-gray-100">{{ lastdrop.lat }}, {{ lastdrop.lng }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-black/40 p-3">
          <p class="text-gray-400">Heading</p>
          <p class="font-medium text-gray-100">{{ lastdrop.heading }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-black/40 p-3">
          <p class="text-gray-400 mb-1">Satélites</p>
          <div class="flex items-center gap-1 text-xs text-gray-400" v-if="lastdrop.satelites != null || lastdrop.satellites != null">
            <div class="flex items-end gap-[1px] h-3">
              <span
                v-for="n in 5"
                :key="n"
                class="w-[2px] rounded-sm"
                :class="[
                  n <= satLevel ? satColorClass : 'bg-gray-700/60',
                  n === 1 ? 'h-1' : n === 2 ? 'h-1.5' : n === 3 ? 'h-2' : n === 4 ? 'h-2.5' : 'h-3'
                ]"
              ></span>
            </div> 
          </div>
          <p v-else class="font-medium text-gray-500 text-xs">Sin info de satélites</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-black/40 p-3">
          <p class="text-gray-400">RSSI</p>
          <p class="font-medium text-gray-100">{{ lastdrop.rssi }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-black/40 p-3">
          <p class="text-gray-400">Batería</p>
          <p class="font-medium text-gray-100">{{ lastdrop.powerBat }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-black/40 p-3">
          <p class="text-gray-400">Alimentación</p>
          <p class="font-medium text-gray-100">{{ lastdrop.powerSupply }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-black/40 p-3">
          <p class="text-gray-400">Odómetro total</p>
          <p class="font-medium text-gray-100">{{ lastdrop.odometroTotal }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-black/40 p-3">
          <p class="text-gray-400">Odómetro reporte</p>
          <p class="font-medium text-gray-100">{{ lastdrop.odometroReporte }}</p>
        </div>
        <div class="rounded-xl border border-white/10 bg-black/40 p-3 sm:col-span-3">
          <p class="text-gray-400">Estado</p>
          <p class="font-medium text-gray-100">{{ lastdrop.stoped ? 'Detenido' : 'En movimiento' }}</p>
        </div>
      </div>
      <p v-else class="mt-4 text-sm text-gray-400">Sin datos recientes (lastdrop).</p>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Clock3, Gauge } from 'lucide-vue-next'

const props = defineProps({
  name: String,
  lastdrop: Object,
  externalMapUrl: String,
  error: String,
  device: Object,
})

const satCount = computed(() => {
  const raw = props.lastdrop?.satelites ?? props.lastdrop?.satellites ?? 0
  const num = Number(raw)
  return Number.isNaN(num) ? 0 : num
})

const satLevel = computed(() => {
  const s = satCount.value
  if (s <= 0) return 0
  if (s <= 3) return 1
  if (s <= 5) return 2
  if (s <= 7) return 3
  if (s <= 10) return 4
  return 5
})

const satColorClass = computed(() => {
  const level = satLevel.value
  if (level <= 1) return 'bg-red-500'
  if (level === 2) return 'bg-yellow-400'
  return 'bg-emerald-400'
})
</script>
