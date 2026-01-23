<template>
  <div class="bg-[#050814] border border-white/5 shadow-sm rounded-3xl p-4 sm:p-5 text-gray-100 space-y-3">
      <div class="flex justify-between items-center gap-3">
        <p class="text-sm font-medium text-gray-100">Media del dispositivo</p>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3 py-1.5 text-xs rounded-lg border border-white/15 bg-white/5 hover:bg-white/10 text-gray-100"
            @click="showRequestModal = true"
          >
            Solicitar media
          </button>
          <button
            type="button"
            class="px-3 py-1.5 text-xs rounded-lg border border-white/15 bg-white/5 hover:bg-white/10 text-gray-100"
            @click="loadUpdatedMedia"
          >
            Solicitar media actualizada
          </button>
        </div>
      </div> 
      <div
        v-if="mediaLocal && mediaLocal.length > 0"
        class="flex items-center justify-between pt-2 mt-1 border-t border-white/5 text-[11px] text-gray-400"
      >
        <button
          type="button"
          class="px-2 py-1 rounded border border-white/15 bg-white/5 hover:bg-white/10"
          @click="loadUpdatedMedia(currentPage - 1)"
        >
          Anterior
        </button>

        <span>
          Página {{ currentPage }} de {{ lastPage }}
        </span>

        <button
          type="button"
          class="px-2 py-1 rounded border border-white/15 bg-white/5 hover:bg-white/10"
          @click="loadUpdatedMedia(currentPage + 1)"
        >
          Siguiente
        </button>
      </div>
      <div v-if="!mediaLocal || mediaLocal.length === 0" class="text-sm text-gray-400">Sin media para este dispositivo.</div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div
          v-for="(m, idx) in mediaLocal"
          :key="idx"
          role="button"
          tabindex="0"
          class="flex items-center gap-3 p-3 border border-white/10 bg-black/40 rounded-xl hover:bg-white/5 text-left transition cursor-pointer"
          @click="emit('select', m)"
        >
          <div class="w-16 h-16 bg-black/60 rounded overflow-hidden flex items-center justify-center border border-white/10">
            <template v-if="(m.extension || '').toLowerCase() === '.mp4'">
              <span class="text-xl">🎥</span>
            </template>
            <template v-else>
              <img :src="m.url" alt="media" class="w-full h-full object-cover" />
            </template>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-100 truncate">
              {{ displayLabel(m) }}
            </p>

            <p class="text-xs text-gray-400">
              {{ m.captured_at ?? m.update_time ?? m.time ?? '' }}
            </p>

            <p v-if="m && m.id != null" class="text-[11px] text-gray-500">
              ID: {{ m.id }}
            </p>

           

            <p
              v-if="m && m.latitude != null && m.longitude != null"
              class="text-xs text-gray-500"
            >
              {{ m.latitude }}, {{ m.longitude }}
            </p>

            <div class="mt-2 flex justify-end">
              <button
                type="button"
                class="px-2 py-1 text-[11px] rounded-full border border-white/15 bg-white/5 hover:bg-white/10 text-gray-100"
                @click.stop="emit('locate', m)"
              >
                Ubicar en mapa
              </button>
            </div>
          </div>
        </div>
      </div>

    <RequestMediaModal v-model="showRequestModal" :imei="imei" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import RequestMediaModal from './RequestMediaModal.vue'

const props = defineProps({
  media: { type: Array, default: () => [] },
  imei: { type: String, default: '' }
})

const emit = defineEmits(['select', 'updated', 'locate'])

const showRequestModal = ref(false)
const mediaLocal = ref(props.media || [])
const currentPage = ref(1)
const lastPage = ref(1)
const isLoading = ref(false)

watch(
  () => props.media,
  (val) => {
    mediaLocal.value = val || []
  },
  { immediate: true }
)

async function loadUpdatedMedia(page = 1) {
  if (isLoading.value) return
  // aseguramos mínimo página 1
  const targetPage = page < 1 ? 1 : page
  isLoading.value = true
  try {
    const token = window.localStorage.getItem('auth_token')
    if (!token) {
      console.error('No se encontró auth_token en localStorage')
      return
    }

    const res = await axios.get('https://app.dygne.com/api/media', {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json'
      },
      params: {
        imei: props.imei,
        page: targetPage
      }
    })

    const data = res.data || {}
    const items = Array.isArray(data.data)
      ? data.data
      : (Array.isArray(data) ? data : [])

    mediaLocal.value = items

    // Laravel paginator clásico: current_page, last_page en la raíz
    currentPage.value = data.current_page || targetPage
    lastPage.value = data.last_page || currentPage.value

    emit('updated', mediaLocal.value)
  } catch (e) {
    console.error('Error solicitando media actualizada', e)
  } finally {
    isLoading.value = false
  }
}

function displayLabel(m) {
  const ext = (m?.extension || '').toLowerCase()
  const t = (m?.type || '').toUpperCase()
  const isVideo = ext === '.mp4'
  const isPhoto = ext === '.jpeg'
  const isFront = t.includes('FRONT')
  const isRear = t.includes('REAR')

  const base = isVideo ? 'Video' : (isPhoto ? 'Foto' : 'Media')
  const side = isFront ? 'Frontal' : (isRear ? 'Interior' : '')
  return side ? `${base} ${side}` : base
}
</script>
