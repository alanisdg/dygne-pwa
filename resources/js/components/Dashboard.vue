<template>
  <div class="py-6">
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
        <div v-if="loadingDevices" class="text-gray-500 text-sm">Cargando dispositivos…</div>
        <div v-else-if="devicesError" class="text-red-600 text-sm">{{ devicesError }}</div>
        <ul v-else class="divide-y divide-gray-100">
          <li v-for="d in devices" :key="d.id" class="py-3 flex items-center justify-between">
            <div>
              <p class="font-medium">{{ d.name }}</p>
              <p class="text-sm text-gray-500">IMEI: {{ d.imei }}</p>
            </div>
          </li>
          <li v-if="devices.length === 0" class="py-3 text-sm text-gray-500">Sin dispositivos</li>
        </ul>
      </div>
    </div>
  </div>
  </template>

<script setup>
import { ref, onMounted } from 'vue'

const email = ref('')
const loading = ref(false)
const message = ref('')
const messageClass = ref('text-gray-600')

// Devices state
const devices = ref([])
const loadingDevices = ref(false)
const devicesError = ref('')

onMounted(() => {
  email.value = localStorage.getItem('auth_email') || '(sin email)'
  fetchDevices()
})

async function logout() {
  loading.value = true
  message.value = ''
  try {
    const token = localStorage.getItem('auth_token')
    if (token) {
      // Intentar cerrar sesión en API remota si existe endpoint
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
    // Redirigir al login tras breve pausa
    setTimeout(() => {
      window.location.href = '/'
    }, 300)
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
    // Esperamos un array de objetos con name e imei
    devices.value = Array.isArray(data) ? data : (data.data || [])
  } catch (e) {
    devicesError.value = e.message || 'No se pudieron cargar los dispositivos'
  } finally {
    loadingDevices.value = false
  }
}
</script>
