<template>
  <div class="min-h-screen bg-black text-gray-100">
    <div class="max-w-3xl mx-auto px-4 py-4">
      <AppHeader title="Instalar Equipo" :email="headerEmail" />

      <div class="mt-4 rounded-xl border border-white/10 bg-white/5 p-4 space-y-4">
        <div class="flex gap-2">
          <input
            v-model.trim="imei"
            type="text"
            class="flex-1 rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm"
            placeholder="Buscar por IMEI"
          />
          <button class="px-4 py-2 rounded-lg bg-blue-600 text-sm" @click="buscar" :disabled="loading">
            {{ loading ? 'Buscando...' : 'Buscar' }}
          </button>
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
            <input v-model="form.installed_date" type="date" class="rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm" />
            <select v-model="form.installed_by_user_id" class="rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm">
              <option value="">Instalado por</option>
              <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
            </select>
          </div>

          <div>
            <input ref="pictureInput" type="file" accept="image/*" capture="environment" class="hidden" @change="onPictureChange" />
            <button class="w-full rounded-lg border border-white/20 bg-black/40 p-2" @click="openCamera">
              <img v-if="picturePreview" :src="picturePreview" class="mx-auto max-h-56 rounded" />
              <span v-else class="text-sm text-gray-300">Tomar foto</span>
            </button>
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
const imei = ref('')
const device = ref(null)
const loading = ref(false)
const saving = ref(false)
const message = ref('')
const messageType = ref('ok')

const chips = ref([])
const models = ref([])
const users = ref([])

const form = ref({
  name: '',
  number: '',
  plate: '',
  chip_id: '',
  model_device_id: '',
  installed_date: '',
  installed_by_user_id: '',
  picture: null,
})

const pictureInput = ref(null)
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
  } catch (_) {}
}

async function buscar() {
  message.value = ''
  loading.value = true
  try {
    const { data } = await axios.get(`${API}/device/${encodeURIComponent(imei.value)}`)
    if (!data?.id) throw new Error('No encontrado')

    device.value = data
    form.value.name = data.name || ''
    form.value.number = data.number || ''
    form.value.plate = data.plate || ''
    form.value.chip_id = data.chip_id ? String(data.chip_id) : ''
    form.value.model_device_id = data.model_device_id ? String(data.model_device_id) : ''
    form.value.installed_date = data.installed_date || ''
    form.value.installed_by_user_id = data.installed_by_user_id ? String(data.installed_by_user_id) : ''
    form.value.picture = null
    picturePreview.value = data.picture ? (String(data.picture).startsWith('http') ? data.picture : `https://app.dygne.com/storage/${String(data.picture).replace(/^\/+/, '')}`) : ''
  } catch (e) {
    messageType.value = 'error'
    message.value = 'No se encontró el equipo por IMEI.'
  } finally {
    loading.value = false
  }
}

function openCamera() {
  pictureInput.value?.click()
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
    fd.append('installed_by_user_id', form.value.installed_by_user_id || '')
    if (form.value.picture) fd.append('picture', form.value.picture)

    await axios.post(`${API}/devices/${device.value.id}`, fd, {
      headers: { ...authHeaders(), 'Content-Type': 'multipart/form-data' },
    })

    messageType.value = 'ok'
    message.value = 'Equipo actualizado correctamente.'
    await buscar()
  } catch (e) {
    messageType.value = 'error'
    message.value = e?.response?.data?.message || 'No se pudo actualizar el equipo.'
  } finally {
    saving.value = false
  }
}
</script>
