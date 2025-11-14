<template>
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
</template>

<script setup>
const props = defineProps({
  name: String,
  lastdrop: Object,
  externalMapUrl: String,
  error: String,
})
</script>
