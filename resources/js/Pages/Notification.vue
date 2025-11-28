<template>
  <div class="min-h-screen bg-black text-gray-100 flex items-center justify-center">
    <div class="text-center px-4">
      <h1 class="text-2xl font-semibold mb-2">Notificacion {{ id }}</h1>
      <p class="text-gray-400 text-sm">Esta es una vista temporal de prueba para la ruta /notification/{{ id }}.</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  id: {
    type: String,
    required: true,
  },
})
</script>
