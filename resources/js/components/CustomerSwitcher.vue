<template>
  <div class="relative">
    <button
      type="button"
      class="inline-flex items-center justify-center rounded-full bg-white/5 hover:bg-white/10 border border-white/10 p-2 text-gray-200 hover:text-white transition"
      title="Cambiar cliente"
      @click="openModal"
    >
      <RefreshCcw class="w-4 h-4" />
    </button>

    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center"
    >
      <div class="absolute inset-0 bg-black/70" @click="closeModal"></div>
      <div class="relative bg-[#020617] border border-white/10 rounded-2xl shadow-xl w-[90vw] max-w-md p-4 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold text-gray-100">Seleccionar cliente</h3>
          <button class="text-gray-400 hover:text-gray-100 text-sm" @click="closeModal">✕</button>
        </div>

        <div class="text-xs text-gray-300 space-y-2">
          <p>Elige un cliente de la lista.</p>
          <div v-if="loading" class="text-gray-400">Cargando clientes…</div>
          <div v-else-if="error" class="text-red-400">{{ error }}</div>
          <div v-else>
            <select
              v-model="selectedCustomerId"
              class="w-full px-3 py-2 rounded-lg border border-white/10 bg-black/40 text-sm text-gray-100"
            >
              <option value="" disabled>Selecciona un cliente…</option>
              <option
                v-for="c in customers"
                :key="c.id"
                :value="c.id"
              >
                {{ c.name }} (ID {{ c.id }})
              </option>
            </select>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 text-xs">
          <button
            type="button"
            class="px-3 py-1.5 rounded-full border border-white/20 text-gray-200 bg-white/5 hover:bg-white/10"
            @click="closeModal"
          >
            Cerrar
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-full bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60"
            :disabled="!selectedCustomerId"
            @click="confirmSelection"
          >
            Aceptar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RefreshCcw } from 'lucide-vue-next'
import axios from 'axios'

const showModal = ref(false)
const customers = ref([])
const loading = ref(false)
const error = ref('')
const selectedCustomerId = ref('')

function openModal() {
  showModal.value = true
  if (!customers.value.length) {
    fetchCustomers()
  }
}

function closeModal() {
  showModal.value = false
}

async function fetchCustomers() {
  try {
    loading.value = true
    error.value = ''
    const token = localStorage.getItem('auth_token')
    if (!token) {
      error.value = 'Sin token de autenticación.'
      return
    }

    const res = await axios.get('https://app.dygne.com/api/customers', {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
      withCredentials: false,
    })

    console.log('[CustomerSwitcher] GET /api/customers response', res)

    const data = res.data
    customers.value = Array.isArray(data) ? data : (data.data || [])
  } catch (e) {
    console.error('[CustomerSwitcher] Error al cargar customers', e)
    error.value = e.message || 'No se pudieron cargar los clientes.'
  } finally {
    loading.value = false
  }
}

async function confirmSelection() {
  if (!selectedCustomerId.value) return
  try {
    loading.value = true
    error.value = ''

    const token = localStorage.getItem('auth_token')
    if (!token) {
      error.value = 'Sin token de autenticación.'
      return
    }

    const id = selectedCustomerId.value
    console.log('[CustomerSwitcher] PATCH /api/customers/' + id)

    const res = await axios.patch(`https://app.dygne.com/api/customers/${id}`, {}, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
      withCredentials: false,
    })

    console.log('[CustomerSwitcher] PATCH /api/customers/{id} response', res)

    closeModal()
    window.location.reload()
  } catch (e) {
    console.error('[CustomerSwitcher] Error al actualizar customer', e)
    error.value = e.message || 'No se pudo actualizar el cliente.'
  } finally {
    loading.value = false
  }
}
</script>
