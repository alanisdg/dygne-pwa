<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-3xl mx-auto">
      <div class="rounded-xl bg-white shadow-sm p-5">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-semibold">Dashboard</h1>
          <button
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm disabled:opacity-60"
            :disabled="loading"
            @click="logout"
          >{{ loading ? 'Saliendo…' : 'Cerrar sesión' }}</button>
        </div>
        <p class="mt-2 text-gray-600">Has iniciado sesión como:</p>
        <p class="mt-1 font-mono font-medium break-all">{{ email }}</p>

        <div v-if="message" class="mt-4 text-sm" :class="messageClass">{{ message }}</div>

        <div class="mt-6">
          <h2 class="text-lg font-semibold mb-3">Dispositivos</h2>
          <div class="mb-3">
            <input
              v-model="q"
              type="text"
              placeholder="Buscar por nombre…"
              class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-base outline-none focus:ring-2 focus:ring-black/70 focus:border-black/70"
            />
          </div>
          <div v-if="loadingDevices" class="text-gray-500 text-sm">Cargando dispositivos…</div>
          <div v-else-if="devicesError" class="text-red-600 text-sm">{{ devicesError }}</div>
          <ul v-else class="divide-y divide-gray-100">
            <li v-for="d in filtered" :key="d.id" class="py-3 flex items-center justify-between">
              <Link :href="`/devices/${d.id}`" class="flex-1">
                <p class="font-medium">{{ d.name }}</p>
                <p class="text-sm text-gray-500">IMEI: {{ d.imei }}</p>
              </Link>
              <span class="ml-3 text-gray-400">›</span>
            </li>
            <li v-if="filtered.length === 0" class="py-3 text-sm text-gray-500">Sin dispositivos</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const email = ref('')
const loading = ref(false)
const message = ref('')
const messageClass = ref('text-gray-600')

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

onMounted(() => {
  // Protege el acceso: si no hay token, regresa a login
  const token = localStorage.getItem('auth_token')
  if (!token) {
    window.location.href = '/'
    return
  }
  email.value = localStorage.getItem('auth_email') || '(sin email)'
  fetchDevices()
})

async function logout() {
  loading.value = true
  message.value = ''
  try {
    const token = localStorage.getItem('auth_token')
    if (token) {
      await fetch('https://app.dygne.com/api/logout', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        mode: 'cors',
      }).catch(() => {})
    }
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_email')
    message.value = 'Sesión cerrada'
    messageClass.value = 'text-green-600'
    setTimeout(() => { window.location.href = '/' }, 300)
    loading.value = false
  }
}

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
