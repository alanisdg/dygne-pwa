<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-3xl mx-auto">
      <div class="flex items-center gap-3 mb-4">
        <a href="/app" class="inline-flex items-center text-sm text-gray-600 hover:text-black">
          ← Volver
        </a>
        <h1 class="text-xl font-semibold">{{ name || `Device #${id}` }}</h1>
      </div>

      <div class="rounded-xl bg-white shadow-sm p-5">
        <p class="text-gray-700">Nombre: <span class="font-medium">{{ name || '(cargando...)' }}</span></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ id: String })
const name = ref('')

onMounted(async () => {
  // Si venimos desde Dashboard, podemos opcionalmente guardar el nombre en sessionStorage
  const cacheKey = `device-name-${props.id}`
  name.value = sessionStorage.getItem(cacheKey) || ''
  if (!name.value) {
    // En una siguiente iteración, podríamos pedir /api/devices/{id}
    // Por ahora solo mantenemos el placeholder
  }
})
</script>
