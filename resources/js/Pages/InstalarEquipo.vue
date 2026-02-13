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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
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

onMounted(async () => {
  headerEmail.value = localStorage.getItem('auth_email') || ''
  await loadCatalogs()
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
