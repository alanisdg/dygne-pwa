<template>
  <div class="min-h-screen bg-black text-gray-100">
    <div class="max-w-4xl mx-auto px-4 py-4">
      <AppHeader title="Compartidos" />

      <div class="mt-4 text-sm text-gray-300 space-y-4">
        <div v-if="loading" class="text-gray-400">Cargando shares…</div>
        <div v-else-if="error" class="text-red-400">{{ error }}</div>

        <div v-else-if="!sharesPage">
          <p class="text-gray-400">Sin datos de shares.</p>
        </div>

        <div v-else class="space-y-4">
          <div class="text-xs text-gray-400">
            <div>Pagina actual: {{ sharesPage.current_page }} / {{ sharesPage.last_page }}</div>
            <div>Total: {{ sharesPage.total }}</div>
            <div>Mostrando: {{ sharesPage.from }}–{{ sharesPage.to }}</div>
          </div>

          <div class="space-y-3">
            <div
              v-for="share in sharesPage.data || []"
              :key="share.id"
              class="rounded-2xl border border-white/10 bg-[#050814] p-4 text-xs text-gray-100 space-y-2 relative"
            >
              <button
                type="button"
                class="absolute top-3 right-3 px-2 py-0.5 rounded-full border border-white/20 text-[11px] text-gray-200 bg-white/5 hover:bg-white/10"
                @click="openEditModal(share)"
              >
                Editar
              </button>

              <div class="flex items-center justify-between gap-2 pr-14">
                <div>
                  <div class="font-semibold text-sm">Share #{{ share.id }} – Código {{ share.code }}</div>
                  <div class="text-gray-400">
                    Expira: {{ share.expiration }} ·
                    Estado: {{ share.status }}
                    <span class="ml-1 text-[11px] px-2 py-0.5 rounded-full border border-white/15"
                      :class="share.status === 1 ? 'bg-emerald-500/10 text-emerald-300 border-emerald-400/60' : (share.status === 2 ? 'bg-yellow-500/10 text-yellow-300 border-yellow-400/60' : 'bg-white/5 text-gray-300')"
                    >
                      {{ share.status === 1 ? 'Activo' : (share.status === 2 ? 'Pausa' : 'Desconocido') }}
                    </span>
                  </div>
                </div>
                <div class="text-right text-gray-400">
                  <div>Creado: {{ share.created_at }}</div>
                  <div>Actualizado: {{ share.updated_at }}</div>
                </div>
              </div>

              <div class="text-gray-300">
                <div class="font-medium mb-1">Usuario</div>
                <div v-if="share.user" class="space-y-0.5">
                  <div>Nombre: {{ share.user.name }}</div>
                  <div>Email: {{ share.user.email }}</div>
                  <div>Customer ID: {{ share.user.customer_id }}</div>
                </div>
                <div v-else class="text-gray-500">Sin información de usuario.</div>
              </div>

              <div>
                <div class="font-medium mb-1 text-gray-300">Dispositivos ({{ (share.devices || []).length }})</div>
                <div v-if="share.devices && share.devices.length" class="space-y-1">
                  <div
                    v-for="dev in share.devices"
                    :key="dev.id"
                    class="rounded-lg border border-white/10 bg-black/20 px-3 py-2 flex flex-col gap-0.5"
                  >
                    <div class="flex items-center justify-between gap-2">
                      <div>
                        <div class="font-semibold text-[13px]">{{ dev.name }} (ID {{ dev.id }})</div>
                        <div class="text-[11px] text-gray-400">IMEI: {{ dev.imei }}</div>
                      </div>
                      <div class="text-right text-[11px] text-gray-400">
                        <div v-if="dev.customer">Cliente: {{ dev.customer.name }}</div>
                        <div v-if="dev.engine_status !== undefined">Motor: {{ dev.engine_status }}</div>
                      </div>
                    </div>

                    <div v-if="dev.lastdrop" class="mt-1 grid grid-cols-2 md:grid-cols-4 gap-1 text-[11px] text-gray-300">
                      <div>
                        <span class="text-gray-500">Fecha:</span>
                        <span class="ml-1">{{ dev.lastdrop.update_time }}</span>
                      </div>
                      <div>
                        <span class="text-gray-500">Lat:</span>
                        <span class="ml-1">{{ dev.lastdrop.lat }}</span>
                      </div>
                      <div>
                        <span class="text-gray-500">Lng:</span>
                        <span class="ml-1">{{ dev.lastdrop.lng }}</span>
                      </div>
                      <div>
                        <span class="text-gray-500">Vel:</span>
                        <span class="ml-1">{{ dev.lastdrop.speed }}</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-xs text-gray-500">Sin dispositivos asociados.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center">
      <div class="bg-[#050814] p-4 rounded-2xl w-full max-w-md">
        <h2 class="font-bold text-lg mb-2">Editar Share</h2>

        <form @submit.prevent="saveEdit">
          <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Estado:</label>
            <select v-model="editStatus" class="w-full p-2 rounded-lg border border-white/10 bg-[#050814] text-gray-100">
              <option value="1">Activo</option>
              <option value="2">Pausa</option>
            </select>
          </div>

          <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Dispositivos:</label>
            <div class="space-y-1">
              <div v-for="dev in editingShare.devices" :key="dev.id" class="flex items-center gap-2">
                <input v-model="editDeviceIds" :value="dev.id" type="checkbox" class="w-4 h-4 rounded-full border border-white/10 bg-[#050814] text-gray-100">
                <span>{{ dev.name }} (ID {{ dev.id }})</span>
              </div>
            </div>
          </div>

          <button type="submit" class="w-full p-2 rounded-lg bg-emerald-500 text-gray-100 hover:bg-emerald-600" :disabled="editLoading">
            Guardar
          </button>

          <button type="button" class="w-full p-2 rounded-lg bg-red-500 text-gray-100 hover:bg-red-600 mt-2" @click="deleteShare" :disabled="editLoading">
            Eliminar
          </button>

          <button type="button" class="w-full p-2 rounded-lg bg-gray-500 text-gray-100 hover:bg-gray-600 mt-2" @click="closeEditModal" :disabled="editLoading">
            Cancelar
          </button>

          <div v-if="editError" class="text-red-400 mt-2">{{ editError }}</div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from '@/components/AppHeader.vue'

const sharesPage = ref(null)
const loading = ref(false)
const error = ref('')

const showEditModal = ref(false)
const editingShare = ref(null)
const editStatus = ref(1)
const editDeviceIds = ref([])
const editLoading = ref(false)
const editError = ref('')

onMounted(async () => {
  await fetchShares()
})

async function fetchShares() {
  try {
    loading.value = true
    error.value = ''

    const token = localStorage.getItem('auth_token')
    if (!token) {
      console.warn('[Shares] Sin token de autenticación')
      error.value = 'Sin token de autenticación.'
      return
    }

    const res = await fetch('https://app.dygne.com/api/shares', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
      mode: 'cors',
    })

    const data = await res.json().catch(() => null)
    console.log('[Shares] GET /api/shares response', { status: res.status, data })

    if (!res.ok) {
      throw new Error(`Error ${res.status} al cargar shares`)
    }

    sharesPage.value = data
  } catch (e) {
    console.error('[Shares] Error al cargar shares', e)
    error.value = e.message || 'No se pudieron cargar los shares.'
  } finally {
    loading.value = false
  }
}

function openEditModal(share) {
  editingShare.value = share
  editStatus.value = share.status ?? 1
  editDeviceIds.value = (share.devices || []).map(d => d.id)
  editError.value = ''
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  editingShare.value = null
}

async function saveEdit() {
  if (!editingShare.value) return
  try {
    editError.value = ''
    editLoading.value = true

    const token = localStorage.getItem('auth_token')
    if (!token) {
      editError.value = 'Sin token de autenticación.'
      return
    }

    const body = {
      status: Number(editStatus.value),
      device_ids: editDeviceIds.value,
    }

    const res = await fetch(`https://app.dygne.com/api/shares/${editingShare.value.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(body),
    })

    const data = await res.json().catch(() => null)
    console.log('[Shares] PUT /api/shares/{id} response', { status: res.status, data })

    if (!res.ok) {
      throw new Error(data?.message || `Error ${res.status} al actualizar share`)
    }

    await fetchShares()
    closeEditModal()
  } catch (e) {
    console.error('[Shares] Error al actualizar share', e)
    editError.value = e.message || 'No se pudo actualizar el share.'
  } finally {
    editLoading.value = false
  }
}

async function deleteShare() {
  if (!editingShare.value) return
  if (!confirm('¿Eliminar este share?')) return

  try {
    editError.value = ''
    editLoading.value = true

    const token = localStorage.getItem('auth_token')
    if (!token) {
      editError.value = 'Sin token de autenticación.'
      return
    }

    const res = await fetch(`https://app.dygne.com/api/shares/${editingShare.value.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    })

    const data = await res.json().catch(() => null)
    console.log('[Shares] DELETE /api/shares/{id} response', { status: res.status, data })

    if (!res.ok) {
      throw new Error(data?.message || `Error ${res.status} al eliminar share`)
    }

    await fetchShares()
    closeEditModal()
  } catch (e) {
    console.error('[Shares] Error al eliminar share', e)
    editError.value = e.message || 'No se pudo eliminar el share.'
  } finally {
    editLoading.value = false
  }
}
</script>
