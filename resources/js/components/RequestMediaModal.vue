<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
  >
    <div class="w-full max-w-md mx-4 bg-[#050814] border border-white/10 rounded-2xl shadow-xl p-5 text-gray-100">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold">Solicitar media</h2>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-200 text-sm"
          @click="close"
        >
          Cerrar
        </button>
      </div>

      <div class="flex items-center justify-between gap-2 mb-4">
        <p class="text-xs text-gray-400 break-all">
          IMEI: {{ imei || '—' }}
        </p>
        <span
          v-if="imei"
          class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border"
          :class="[
            checkingAvailability ? 'border-yellow-400/40 text-yellow-300' : '',
            !checkingAvailability && deviceBusy ? 'border-red-500/40 text-red-300' : '',
            !checkingAvailability && !deviceBusy ? 'border-emerald-500/40 text-emerald-300' : ''
          ]"
        >
          <span v-if="checkingAvailability">Revisando...</span>
          <span v-else-if="deviceBusy">Ocupado</span>
          <span v-else>Disponible</span>
        </span>
      </div>

      <div
        class="space-y-4"
        :class="formDisabled ? 'opacity-50 pointer-events-none' : ''"
      >
        <div class="mb-4">
          <p class="text-xs text-gray-300 mb-2">Tipo de media</p>
          <div class="inline-flex rounded-full bg-black/40 border border-white/10 p-1 text-xs">
            <button
              type="button"
              class="px-3 py-1 rounded-full transition"
              :class="type === 'photo' ? 'bg-white text-black' : 'text-gray-300'"
              @click="type = 'photo'"
            >
              Foto
            </button>
            <button
              type="button"
              class="px-3 py-1 rounded-full transition"
              :class="type === 'video' ? 'bg-white text-black' : 'text-gray-300'"
              @click="type = 'video'"
            >
              Video
            </button>
          </div>
        </div>

        <div v-if="type === 'photo'" class="space-y-4">
          <p class="text-xs text-gray-400">
            Se solicitará una foto única al dispositivo asociado a este IMEI.
          </p>
          <div>
            <p class="text-xs text-gray-300 mb-2">Cámara</p>
            <div class="inline-flex rounded-full bg-black/40 border border-white/10 p-1 text-[11px]">
              <button
                type="button"
                class="px-3 py-1 rounded-full transition"
                :class="photoSide === 'front' ? 'bg-white text-black' : 'text-gray-300'"
                @click="photoSide = 'front'"
              >
                Frontal
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded-full transition"
                :class="photoSide === 'interior' ? 'bg-white text-black' : 'text-gray-300'"
                @click="photoSide = 'interior'"
              >
                Interior
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded-full transition"
                :class="photoSide === 'both' ? 'bg-white text-black' : 'text-gray-300'"
                @click="photoSide = 'both'"
              >
                Ambas
              </button>
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <button
              type="button"
              class="px-3 py-1.5 text-xs rounded-lg border border-white/10 text-gray-300 hover:bg-white/5"
              @click="close"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-xs rounded-lg bg-white text-black font-medium hover:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed"
              :disabled="loadingPhoto || !imei || formDisabled"
              @click="requestPhoto"
            >
              {{ loadingPhoto ? 'Solicitando…' : 'Solicitar' }}
            </button>
          </div>
        </div>

        <div v-else class="space-y-4">
          <p class="text-xs text-gray-400">
            Configura la fecha, hora y duración en segundos para la solicitud de video.
          </p>

          <div>
            <p class="text-xs text-gray-300 mb-2">Cámara</p>
            <div class="inline-flex rounded-full bg-black/40 border border-white/10 p-1 text-[11px]">
              <button
                type="button"
                class="px-3 py-1 rounded-full transition"
                :class="videoSide === 'front' ? 'bg-white text-black' : 'text-gray-300'"
                @click="videoSide = 'front'"
              >
                Frontal
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded-full transition"
                :class="videoSide === 'interior' ? 'bg-white text-black' : 'text-gray-300'"
                @click="videoSide = 'interior'"
              >
                Interior
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="space-y-1">
              <label class="block text-gray-300">Fecha</label>
              <input
                type="date"
                v-model="videoDate"
                class="w-full rounded-lg bg-black/40 border border-white/10 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-white/40"
                @click="openNativePicker($event)"
              />
            </div>
            <div class="space-y-1">
              <label class="block text-gray-300">Hora</label>
              <input
                type="time"
                v-model="videoTime"
                class="w-full rounded-lg bg-black/40 border border-white/10 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-white/40"
                @click="openNativePicker($event)"
              />
            </div>
            <div class="space-y-1 sm:col-span-2">
              <label class="block text-gray-300">Duración (segundos)</label>
              <input
                type="number"
                min="1"
                v-model.number="videoSeconds"
                class="w-full rounded-lg bg-black/40 border border-white/10 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-white/40"
              />
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <button
              type="button"
              class="px-3 py-1.5 text-xs rounded-lg border border-white/10 text-gray-300 hover:bg-white/5"
              @click="close"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-xs rounded-lg bg-white text-black font-medium hover:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed"
              :disabled="formDisabled || loadingVideo || !videoDate || !videoSeconds"
              @click="requestVideo"
            >
              {{ loadingVideo ? 'Solicitando…' : 'Solicitar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  imei: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const type = ref('photo')
const photoSide = ref('front')
const videoSide = ref('front')
const videoDate = ref('')
const videoTime = ref('')
const videoSeconds = ref(10)
const loadingPhoto = ref(false)
const loadingVideo = ref(false)
const checkingAvailability = ref(false)
const deviceBusy = ref(false)
const availabilityIntervalId = ref(null)

const formDisabled = computed(() => {
  if (!props.imei) return true
  if (checkingAvailability.value) return true
  if (deviceBusy.value) return true
  return false
})

function close() {
  emit('update:modelValue', false)
  if (availabilityIntervalId.value) {
    clearInterval(availabilityIntervalId.value)
    availabilityIntervalId.value = null
  }
}

async function requestVideo() {
  if (!props.imei || loadingVideo.value || checkingAvailability.value || deviceBusy.value) return

  const token = window.localStorage.getItem('auth_token')
  if (!token) {
    console.error('No se encontró auth_token en localStorage')
    return
  }

  if (!videoDate.value || !videoTime.value || !videoSeconds.value) {
    console.warn('Faltan datos para solicitar video')
    return
  }

  // Construir timestamp (segundos) a partir de la fecha y hora seleccionadas
  const isoString = `${videoDate.value}T${videoTime.value}:00`
  const tsMs = Date.parse(isoString)
  if (Number.isNaN(tsMs)) {
    console.error('Fecha/hora de video inválidas', isoString)
    return
  }
  const timestamp = Math.floor(tsMs / 1000)

  let cameraFlag = '1'
  if (videoSide.value === 'interior') cameraFlag = '2'

  const command = `camreq:0,${cameraFlag},${timestamp},${videoSeconds.value},144.126.211.5,3001`
  const url = `https://app.dygne.com/api/devices/${encodeURIComponent(props.imei)}/send-command?command=${encodeURIComponent(command)}`

  loadingVideo.value = true

  try {
    const response = await axios.post(
      url,
      null,
      {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        }
      }
    )

    const data = response?.data
    console.log('requestVideo response', data)

    if (data && data.success === true) {
      alert('El video ha sido solicitado correctamente.')
      close()
    } else {
      console.warn('Solicitud de video sin success=true', data)
    }
  } catch (err) {
    console.error('Error solicitando video', err)
  } finally {
    loadingVideo.value = false
  }
}

async function requestPhoto() {
  if (!props.imei || loadingPhoto.value || checkingAvailability.value || deviceBusy.value) return

  const token = window.localStorage.getItem('auth_token')
  if (!token) {
    console.error('No se encontró auth_token en localStorage')
    return
  }

  loadingPhoto.value = true
  let commandSuffix = '1,1'
  if (photoSide.value === 'interior') commandSuffix = '1,2'
  else if (photoSide.value === 'both') commandSuffix = '1,3'
  const url = `https://app.dygne.com/api/devices/${encodeURIComponent(props.imei)}/send-command?command=camreq:${commandSuffix}`

  try {
    const response = await axios.post(
      url,
      null,
      {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        }
      }
    )

    const data = response?.data
    console.log('requestPhoto response', data)

    if (data && data.success === true) {
      alert('La foto ha sido solicitada correctamente.')
      close()
    } else {
      console.warn('Solicitud de foto sin success=true', data)
    }
  } catch (err) {
    console.error('Error solicitando foto', err)
  } finally {
    loadingPhoto.value = false
  }
}
async function checkAvailability() {
  if (!props.imei) {
    deviceBusy.value = false
    return
  }

  const token = window.localStorage.getItem('auth_token')
  if (!token) {
    console.error('No se encontró auth_token en localStorage')
    deviceBusy.value = false
    return
  }

  checkingAvailability.value = true

  try {
    const { data } = await axios.get('https://app.dygne.com/api/media/progress', {
      params: { imei: props.imei },
      headers: {
        Authorization: `Bearer ${token}`
      }
    })

    deviceBusy.value = data?.trigger?.status === true
  } catch (err) {
    console.error('Error verificando disponibilidad de media', err)
    deviceBusy.value = false
  } finally {
    checkingAvailability.value = false
  }
}

function openNativePicker(evt) {
  const el = evt?.target
  if (el && typeof el.showPicker === 'function') {
    try {
      el.showPicker()
    } catch (e) {
      console.warn('showPicker error', e)
    }
  }
}

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      checkAvailability()
      if (!availabilityIntervalId.value) {
        availabilityIntervalId.value = setInterval(() => {
          checkAvailability()
        }, 3000)
      }
    } else if (availabilityIntervalId.value) {
      clearInterval(availabilityIntervalId.value)
      availabilityIntervalId.value = null
    }
  }
)

onUnmounted(() => {
  if (availabilityIntervalId.value) {
    clearInterval(availabilityIntervalId.value)
    availabilityIntervalId.value = null
  }
})
</script>
