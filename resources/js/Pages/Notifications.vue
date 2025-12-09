<template>
  <div class="min-h-screen bg-black text-gray-100 p-0 sm:p-4">
    <div class="max-w-3xl mx-auto space-y-4">
      <AppHeader title="Notificaciones" :email="email" backHref="/app" />

      <div class="bg-[#050814] border border-white/5 rounded-3xl p-4 sm:p-5">
        <div v-if="loading" class="text-sm text-gray-400">Cargando notificaciones...</div>
        <div v-else-if="error" class="text-sm text-red-400">{{ error }}</div>
        <div v-else-if="!notifications.length" class="text-sm text-gray-400">
          No hay notificaciones para mostrar.
        </div>
        <div v-else class="divide-y divide-white/5">
          <a
            v-for="n in notifications"
            :key="n.id"
            :href="`/notification/${n.id}`"
            class="block py-3 first:pt-0 last:pb-0 hover:bg-white/5 rounded-xl px-2 -mx-2 transition"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-0.5">
                <p class="text-sm font-medium text-gray-100 truncate">
                  {{ n.action || 'Notificación' }}
                </p>
                <p class="text-xs text-gray-400">
                  IMEI: {{ n.imei }} · Lat: {{ n.lat }} · Lng: {{ n.lng }}
                </p>
              </div>
              <div class="text-[11px] text-gray-400 whitespace-nowrap">
                {{ n.update_time || n.created_at }}
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import AppHeader from '@/components/AppHeader.vue'

const email = ref('')
const loading = ref(true)
const error = ref('')
const notifications = ref([])

onMounted(async () => {
  email.value = localStorage.getItem('auth_email') || ''

  try {
    const token = localStorage.getItem('auth_token')
    if (!token) throw new Error('Sin token de autenticación')

    const { data, status } = await axios.get('https://app.dygne.com/api/pwa/all', {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    })

    notifications.value = Array.isArray(data) ? data : (data.data || [])
  } catch (e) {
    error.value = e?.message || 'No se pudieron cargar las notificaciones'
  } finally {
    loading.value = false
  }
})
</script>
