<template>
  <div class="min-h-screen bg-black text-gray-100">
    <div class="max-w-3xl mx-auto px-4 py-4">
      <AppHeader title="Configuración" :email="headerEmail" />
      <div class="mt-4 text-sm text-gray-300">
        <div class="border-b border-white/10 mb-4 flex gap-2 text-xs">
          <button
            type="button"
            class="px-3 py-1.5 rounded-t-lg border-b-2"
            :class="activeTab === 'notifications'
              ? 'border-blue-500 text-blue-300 bg-white/5'
              : 'border-transparent text-gray-400 hover:text-gray-200 hover:bg-white/5'"
            @click="activeTab = 'notifications'"
          >
            Notificaciones
          </button>
        </div>

        <div v-if="activeTab === 'notifications'" class="space-y-3">
          <div class="space-y-2 text-xs">
            <p class="text-gray-400">Enciende o apaga los tipos de notificación que quieras recibir.</p>
            <div class="space-y-1">
              <label class="flex items-center justify-between gap-2 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10">
                <span class="text-gray-200">Movimiento de unidad</span>
                <button
                  type="button"
                  class="relative inline-flex h-5 w-9 items-center rounded-full transition"
                  :class="notifyMovement ? 'bg-emerald-500' : 'bg-gray-500/50'"
                  @click="toggleNotification('notifyMovement')"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition"
                    :class="notifyMovement ? 'translate-x-4' : 'translate-x-0'"
                  />
                </button>
              </label>

              <label class="flex items-center justify-between gap-2 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10">
                <span class="text-gray-200">Solicitud de media</span>
                <button
                  type="button"
                  class="relative inline-flex h-5 w-9 items-center rounded-full transition"
                  :class="notifyMediaRequest ? 'bg-emerald-500' : 'bg-gray-500/50'"
                  @click="toggleNotification('notifyMediaRequest')"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition"
                    :class="notifyMediaRequest ? 'translate-x-4' : 'translate-x-0'"
                  />
                </button>
              </label>

              <div class="mt-1 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10">
                <button
                  type="button"
                  class="w-full flex items-center justify-between text-left text-gray-200 text-xs"
                  @click="toggleGeofencesOpen()"
                >
                  <span>Notificaciones de geocercas</span>
                  <span class="text-[10px] text-gray-400">
                    {{ showGeofences ? 'Ocultar' : 'Mostrar' }}
                  </span>
                </button>

                <div v-if="showGeofences" class="mt-2 space-y-1 text-[11px]">
                  <div v-if="loadingGeofences" class="text-gray-400">
                    Cargando geocercas…
                  </div>
                  <div v-else-if="geofencesError" class="text-red-400">
                    {{ geofencesError }}
                  </div>
                  <div v-else-if="!geofences.length" class="text-gray-500">
                    No hay geocercas disponibles.
                  </div>
                  <div v-else class="space-y-1">
                    <label
                      v-for="g in geofences"
                      :key="g.id || g.name"
                      class="flex items-center justify-between gap-2 px-2 py-1 rounded bg-black/40 border border-white/5"
                    >
                      <span class="truncate text-gray-200">{{ g.name || g.nombre || 'Geocerca' }}</span>
                      <button
                        type="button"
                        class="relative inline-flex h-4 w-8 items-center rounded-full transition"
                        :class="geofenceSwitchOn(getGeofenceKey(g)) ? 'bg-emerald-500' : 'bg-gray-500/50'"
                        @click.stop="toggleGeofenceSwitch(g)"
                      >
                        <span
                          class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition"
                          :class="geofenceSwitchOn(getGeofenceKey(g)) ? 'translate-x-3.5' : 'translate-x-0'"
                        />
                      </button>
                    </label>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div v-if="loadingNotifications" class="text-gray-400 text-xs">
            Cargando configuración de notificaciones…
          </div>
          <div v-else-if="notificationsError" class="text-red-400 text-xs">
            {{ notificationsError }}
          </div>
          <div v-else>
            <div v-if="!userNotifications.length" class="text-gray-500 text-xs">
              No hay configuración de notificaciones para este usuario.
            </div>
            <div v-else class="space-y-2 text-xs">
              <div
                v-for="(n, idx) in userNotifications"
                :key="idx"
                class="rounded-lg border border-white/10 bg-[#050814] px-3 py-2"
              >
                <div class="text-[11px] text-gray-400 mb-1">
                  user_id: {{ n.user_id }}
                </div>
                <pre class="text-[11px] whitespace-pre-wrap break-words text-gray-100 bg-black/40 px-2 py-1 rounded">
{{ formatPayload(n.payload) }}
                </pre>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import AppHeader from '@/components/AppHeader.vue'

const headerEmail = ref('')
const activeTab = ref('notifications')

const userNotifications = ref([])
const loadingNotifications = ref(false)
const notificationsError = ref('')

const currentNotificationId = ref(null)

const notifyMovement = ref(false)
const notifyMediaRequest = ref(false)

const showGeofences = ref(false)
const geofences = ref([])
const loadingGeofences = ref(false)
const geofencesError = ref('')
const geofenceSwitches = ref({})

onMounted(async () => {
  try {
    headerEmail.value = localStorage.getItem('auth_email') || ''
  } catch (e) {
    console.warn('[Configuracion] No se pudo leer auth_email de localStorage', e)
  }

  await fetchUserNotifications()
})

async function fetchUserNotifications() {
  try {
    loadingNotifications.value = true
    notificationsError.value = ''

    const token = localStorage.getItem('auth_token')
    if (!token) {
      notificationsError.value = 'Sin token de autenticación.'
      return
    }

    const { data, status } = await axios.get('https://app.dygne.com/api/user-notifications', {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    })

    console.log('[Configuracion] GET /api/user-notifications response', { status, data })

    if (Array.isArray(data)) {
      userNotifications.value = data
    } else if (data && typeof data === 'object') {
      // Puede venir como { data: [...] } o como un solo objeto
      if (Array.isArray(data.data)) {
        userNotifications.value = data.data
      } else {
        userNotifications.value = [data]
      }
    } else {
      userNotifications.value = []
    }

    // Si existe configuración previa, inicializar los switches desde el payload
    if (userNotifications.value.length) {
      const last = userNotifications.value[userNotifications.value.length - 1]

      currentNotificationId.value = last.id || last.user_notification_id || null

      let payload = last && last.payload
      try {
        if (typeof payload === 'string') {
          payload = JSON.parse(payload)
        } else if (Array.isArray(payload)) {
          // Si el backend regresa [] como payload vacío, normalizar a objeto
          payload = {}
        }
      } catch (e) {
        console.warn('[Configuracion] No se pudo parsear payload de notificaciones', e)
        payload = {}
      }

      if (payload && typeof payload === 'object') {
        notifyMovement.value = !!payload.notifyMovement
        notifyMediaRequest.value = !!payload.notifyMediaRequest

        if (payload.geofences && typeof payload.geofences === 'object') {
          geofenceSwitches.value = { ...payload.geofences }
        }
      }
    }
  } catch (e) {
    console.error('[Configuracion] Error al cargar notificaciones de usuario', e)
    notificationsError.value = e.message || 'No se pudo cargar la configuración de notificaciones.'
  } finally {
    loadingNotifications.value = false
  }
}

function formatPayload(payload) {
  try {
    if (typeof payload === 'string') {
      // Intentar parsear JSON si viene como string
      const parsed = JSON.parse(payload)
      return JSON.stringify(parsed, null, 2)
    }
    return JSON.stringify(payload, null, 2)
  } catch (e) {
    return String(payload ?? '')
  }
}

async function toggleGeofencesOpen() {
  showGeofences.value = !showGeofences.value

  if (showGeofences.value && !geofences.value.length && !loadingGeofences.value) {
    await fetchGeofences()
  }
}

async function fetchGeofences() {
  try {
    loadingGeofences.value = true
    geofencesError.value = ''

    const token = localStorage.getItem('auth_token')
    if (!token) {
      geofencesError.value = 'Sin token de autenticación para geocercas.'
      return
    }

    const { data, status } = await axios.get('https://app.dygne.com/api/geofences', {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    })

    console.log('[Configuracion] GET /api/geofecences response', { status, data })

    geofences.value = Array.isArray(data) ? data : (data.data || [])

    const nextSwitches = { ...geofenceSwitches.value }
    for (const g of geofences.value) {
      const key = getGeofenceKey(g)
      if (key && !(key in nextSwitches)) {
        nextSwitches[key] = false
      }
    }
    geofenceSwitches.value = nextSwitches
  } catch (e) {
    console.error('[Configuracion] Error al cargar geocercas', e)
    geofencesError.value = e.message || 'No se pudieron cargar las geocercas.'
  } finally {
    loadingGeofences.value = false
  }
}

function getGeofenceKey(g) {
  return g.id ?? g.name ?? null
}

function geofenceSwitchOn(key) {
  if (!key) return false
  return !!geofenceSwitches.value[key]
}

async function toggleGeofenceSwitch(g) {
  const key = getGeofenceKey(g)
  if (!key) return
  geofenceSwitches.value = {
    ...geofenceSwitches.value,
    [key]: !geofenceSwitches.value[key],
  }

  await updateNotificationPayload(null, null)
}

async function toggleNotification(field) {
  const mapping = {
    notifyMovement,
    notifyMediaRequest,
  }

  const targetRef = mapping[field]
  if (!targetRef) {
    console.warn('[Configuracion] toggleNotification llamado con campo desconocido', field)
    return
  }

  const newValue = !targetRef.value
  targetRef.value = newValue

  await updateNotificationPayload(field, newValue)
}

async function updateNotificationPayload(field, value) {
  try {
    const token = localStorage.getItem('auth_token')
    if (!token) {
      console.warn('[Configuracion] No hay token para guardar notificaciones')
      return
    }

    const payload = {
      notifyMovement: !!notifyMovement.value,
      notifyMediaRequest: !!notifyMediaRequest.value,
    }

    if (field) {
      payload[field] = !!value
    }

    if (geofenceSwitches.value && Object.keys(geofenceSwitches.value).length) {
      payload.geofences = { ...geofenceSwitches.value }
    }

    // Si ya tenemos un UserNotification existente, actualizamos con PATCH y mandamos el id.
    // Si no existe (usuario nuevo), usamos POST como fallback para crearlo.
    const hasExisting = !!currentNotificationId.value
    const method = hasExisting ? 'patch' : 'post'

    const { data, status } = await axios({
      method,
      url: 'https://app.dygne.com/api/user-notifications' + (hasExisting ? `/${currentNotificationId.value}` : ''),
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      data: {
        id: currentNotificationId.value,
        payload,
      },
    })

    console.log(`[Configuracion] ${method.toUpperCase()} /api/user-notifications response`, { status, data })

    if (data) {
      if (Array.isArray(data)) {
        userNotifications.value = data
      } else if (data.data) {
        userNotifications.value = data.data
      } else {
        // caso en que el endpoint regrese solo un objeto actualizado
        userNotifications.value = [data]
      }

      // Actualizar el id actual en base a la última respuesta
      if (userNotifications.value.length) {
        const last = userNotifications.value[userNotifications.value.length - 1]
        currentNotificationId.value = last.id || last.user_notification_id || currentNotificationId.value
      }
    }
  } catch (e) {
    console.error('[Configuracion] Error al guardar notificaciones de usuario', e)
  }
}
</script>
