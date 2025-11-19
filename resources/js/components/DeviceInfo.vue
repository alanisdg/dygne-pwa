<template>
  <div class="rounded-t-3xl sm:rounded-3xl bg-[#050814] border border-white/5 shadow-sm p-4 sm:p-5 -mt-4 sm:mt-4 relative z-10 text-gray-100">
    <p v-if="error" class="text-red-400 text-sm">{{ error }}</p>
    <template v-else>
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-300">Nombre: <span class="font-medium text-gray-100">{{ name || '(sin nombre)' }}</span></p>
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
          <p class="text-gray-400">Satélites</p>
          <p class="font-medium text-gray-100">{{ lastdrop.satelites }}</p>
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
import { Clock3, Gauge } from 'lucide-vue-next'

const props = defineProps({
  name: String,
  lastdrop: Object,
  externalMapUrl: String,
  error: String,
})
</script>
