<template>
  <div class="min-h-screen bg-black text-gray-100">
    <div class="max-w-3xl mx-auto px-4 py-4">
      <AppHeader title="Instalar Equipo" :email="headerEmail" />

      <div class="mt-4 rounded-xl border border-white/10 bg-white/5 p-4 space-y-4">
        <div class="space-y-2">
          <div class="flex gap-2">
            <input
              v-model.trim="searchQuery"
              type="text"
              class="flex-1 rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm"
              placeholder="Buscar por IMEI o nombre"
            />
            <button class="px-4 py-2 rounded-lg bg-blue-600 text-sm" @click="buscar" :disabled="loading">
              {{ loading ? 'Buscando...' : 'Buscar' }}
            </button>
            <button
              class="px-3 py-2 rounded-lg bg-white/10 border border-white/15 text-xs flex items-center gap-1"
              type="button"
              @click="openScanner"
              :disabled="loading"
            >
              <QrCode :size="16" />
              Escanear
            </button>
          </div>

          <div v-if="searchResults.length" class="rounded-lg border border-white/10 bg-black/40 max-h-52 overflow-auto">
            <button
              v-for="r in searchResults"
              :key="r.id"
              class="w-full text-left px-3 py-2 text-sm hover:bg-white/10"
              @click="selectDevice(r)"
            >
              <div class="text-gray-100">{{ r.name || 'Sin nombre' }}</div>
              <div class="text-xs text-gray-400">IMEI: {{ r.imei }}</div>
            </button>
          </div>
        </div>

        <div v-if="device" class="space-y-3">
          <input v-model="form.name" class="w-full rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm" placeholder="Nombre" />
          <input v-model="form.number" class="w-full rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm" placeholder="Número" />
          <input v-model="form.plate" class="w-full rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm" placeholder="Placa" />

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <select v-model="form.chip_id" class="rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm">
              <option value="">Sin chip</option>
              <option v-for="chip in chips" :key="chip.id" :value="String(chip.id)">{{ chip.name }}</option>
            </select>

            <select v-model="form.model_device_id" class="rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm">
              <option value="">Sin modelo</option>
              <option v-for="m in models" :key="m.id" :value="String(m.id)">{{ m.name }}</option>
            </select>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-300 mb-1">Fecha de instalación</label>
              <input v-model="form.installed_date" type="date" class="w-full rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-xs text-gray-300 mb-1">Transferir a cliente</label>
              <select v-model="form.customer_id" class="w-full rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm">
                <option value="">Sin cambio</option>
                <option v-for="c in customers" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="block text-xs text-gray-300">Foto del equipo</label>
            <input
              type="file"
              accept="image/*"
              capture="environment"
              class="w-full rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm"
              @change="onPictureChange"
            />
            <img v-if="picturePreview" :src="picturePreview" class="mx-auto max-h-56 rounded border border-white/10" />
          </div>

          <button class="w-full py-2 rounded-lg bg-emerald-600 text-sm" @click="guardar" :disabled="saving">
            {{ saving ? 'Guardando...' : 'Guardar instalación' }}
          </button>
        </div>

        <p v-if="message" class="text-xs" :class="messageType === 'ok' ? 'text-emerald-300' : 'text-red-300'">{{ message }}</p>
      </div>
    </div>

    <div v-if="scannerOpen" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
      <div class="w-full max-w-md rounded-xl border border-white/15 bg-zinc-900 p-4 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold">Escanear QR</h3>
          <button class="text-xs text-gray-300" type="button" @click="closeScanner">Cerrar</button>
        </div>

        <p class="text-xs text-gray-400">Apunta al QR del equipo. Si detecta IMEI lo completa y busca automáticamente.</p>

        <video ref="scanVideoRef" class="w-full rounded-lg border border-white/10 bg-black" autoplay playsinline muted></video>

        <p v-if="scannerError" class="text-xs text-red-300">{{ scannerError }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import axios from 'axios'
import { QrCode } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'

const API = 'https://app.dygne.com/api'
const headerEmail = ref('')
const searchQuery = ref('')
const searchResults = ref([])
const device = ref(null)
const loading = ref(false)
const saving = ref(false)
const message = ref('')
const messageType = ref('ok')

const chips = ref([])
const models = ref([])
const users = ref([])
const customers = ref([])

const form = ref({
  name: '',
  number: '',
  plate: '',
  chip_id: '',
  model_device_id: '',
  installed_date: '',
  customer_id: '',
  picture: null,
})

const picturePreview = ref('')

const scannerOpen = ref(false)
const scannerError = ref('')
const scanVideoRef = ref(null)
let scanStream = null
let scanTimer = null
let qrDetector = null

onMounted(async () => {
  headerEmail.value = localStorage.getItem('auth_email') || ''
  await loadCatalogs()

  if ('BarcodeDetector' in window) {
    try {
      qrDetector = new window.BarcodeDetector({ formats: ['qr_code'] })
    } catch (_) {
      qrDetector = null
    }
  }
})

onUnmounted(() => {
  closeScanner()
})

function authHeaders() {
  const token = localStorage.getItem('auth_token')
  return { Authorization: `Bearer ${token}`, Accept: 'application/json' }
}

async function loadCatalogs() {
  try {
    const { data } = await axios.get(`${API}/devices/install-catalogs`, { headers: authHeaders() })
    chips.value = data?.chips || []
    models.value = data?.models || []
    users.value = data?.users || []
    customers.value = data?.customers || []

    if (!customers.value.length) {
      const customersRes = await axios.get(`${API}/customers`, { headers: authHeaders() })
      customers.value = Array.isArray(customersRes.data)
        ? customersRes.data
        : (customersRes.data?.data || [])
    }
  } catch (e) {
    try {
      const customersRes = await axios.get(`${API}/customers`, { headers: authHeaders() })
      customers.value = Array.isArray(customersRes.data)
        ? customersRes.data
        : (customersRes.data?.data || [])
    } catch (_) {}
  }
}

async function buscar() {
  message.value = ''
  loading.value = true
  searchResults.value = []
  try {
    const { data } = await axios.get(`${API}/devices/find`, {
      params: { q: searchQuery.value || '' },
      headers: authHeaders(),
    })

    const list = Array.isArray(data) ? data : []
    searchResults.value = list

    if (list.length === 1) {
      await loadDeviceByImei(list[0].imei)
      searchResults.value = []
    }

    if (!list.length) {
      messageType.value = 'error'
      message.value = 'No se encontraron equipos con ese IMEI o nombre.'
    }
  } catch (e) {
    messageType.value = 'error'
    message.value = 'Error buscando equipos.'
  } finally {
    loading.value = false
  }
}

async function selectDevice(row) {
  searchQuery.value = row?.imei || row?.name || ''
  searchResults.value = []
  await loadDeviceByImei(row?.imei)
}

async function loadDeviceByImei(imei) {
  const { data } = await axios.get(`${API}/device/${encodeURIComponent(imei)}`)
  if (!data?.id) throw new Error('No encontrado')

  device.value = data
  form.value.name = data.name || ''
  form.value.number = data.number || ''
  form.value.plate = data.plate || ''
  form.value.chip_id = data.chip_id ? String(data.chip_id) : ''
  form.value.model_device_id = data.model_device_id ? String(data.model_device_id) : ''
  form.value.installed_date = data.installed_date || ''
  form.value.customer_id = data.customer_id ? String(data.customer_id) : ''
  form.value.picture = null
  picturePreview.value = data.picture ? (String(data.picture).startsWith('http') ? data.picture : `https://app.dygne.com/storage/${String(data.picture).replace(/^\/+/, '')}`) : ''
}

function onPictureChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  form.value.picture = file
  picturePreview.value = URL.createObjectURL(file)
}

function extractImei(text) {
  const normalized = String(text || '').trim()
  if (!normalized) return ''

  const imeiMatch = normalized.match(/\b\d{15}\b/)
  if (imeiMatch) return imeiMatch[0]

  return normalized
}

async function openScanner() {
  scannerError.value = ''
  scannerOpen.value = true

  await nextTick()

  if (!navigator.mediaDevices?.getUserMedia) {
    scannerError.value = 'Tu navegador no soporta cámara para escaneo.'
    return
  }

  if (!qrDetector) {
    scannerError.value = 'Tu navegador no soporta lectura QR automática. Usa IMEI manual.'
    return
  }

  try {
    scanStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' } },
      audio: false,
    })

    if (scanVideoRef.value) {
      scanVideoRef.value.srcObject = scanStream
      await scanVideoRef.value.play()
    }

    scanTimer = setInterval(scanQrFrame, 400)
  } catch (e) {
    scannerError.value = 'No se pudo abrir la cámara.'
  }
}

function closeScanner() {
  scannerOpen.value = false

  if (scanTimer) {
    clearInterval(scanTimer)
    scanTimer = null
  }

  if (scanStream) {
    scanStream.getTracks().forEach((t) => t.stop())
    scanStream = null
  }

  if (scanVideoRef.value) {
    scanVideoRef.value.srcObject = null
  }
}

async function scanQrFrame() {
  if (!scannerOpen.value || !scanVideoRef.value || !qrDetector) return

  try {
    const codes = await qrDetector.detect(scanVideoRef.value)
    if (!codes?.length) return

    const raw = codes[0]?.rawValue || ''
    const imei = extractImei(raw)
    if (!imei) return

    searchQuery.value = imei
    closeScanner()
    await buscar()
  } catch (_) {
    // ignore frame errors while scanning
  }
}

async function guardar() {
  if (!device.value?.id) return
  saving.value = true
  message.value = ''

  try {
    const fd = new FormData()
    fd.append('_method', 'PUT')
    fd.append('name', form.value.name || '')
    fd.append('number', form.value.number || '')
    fd.append('plate', form.value.plate || '')
    fd.append('chip_id', form.value.chip_id || '')
    fd.append('model_device_id', form.value.model_device_id || '')
    fd.append('installed_date', form.value.installed_date || '')
    fd.append('customer_id', form.value.customer_id || '')
    if (form.value.picture) fd.append('picture', form.value.picture)

    await axios.post(`${API}/devices/${device.value.id}`, fd, {
      headers: { ...authHeaders(), 'Content-Type': 'multipart/form-data' },
    })

    messageType.value = 'ok'
    message.value = 'Equipo actualizado correctamente.'
    await loadDeviceByImei(device.value.imei)
  } catch (e) {
    messageType.value = 'error'
    message.value = e?.response?.data?.message || 'No se pudo actualizar el equipo.'
  } finally {
    saving.value = false
  }
}
</script>
