<template>
  <div class="bg-[#050814] border border-white/5 shadow-sm rounded-3xl p-4 sm:p-5 text-gray-100 space-y-3">
    <div class="flex justify-between items-center gap-3">
      <p class="text-sm font-medium text-gray-100">Media del dispositivo</p>
      <button
        type="button"
        class="px-3 py-1.5 text-xs rounded-lg border border-white/15 bg-white/5 hover:bg-white/10 text-gray-100"
        @click="showRequestModal = true"
      >
        Solicitar media
      </button>
    </div>

    <div v-if="!media || media.length === 0" class="text-sm text-gray-400">Sin media para este dispositivo.</div>
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <button
        v-for="(m, idx) in media"
        :key="idx"
        type="button"
        class="flex items-center gap-3 p-3 border border-white/10 bg-black/40 rounded-xl hover:bg-white/5 text-left transition"
        @click="$emit('select', m)"
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

  <p v-if="m?.upload_time" class="text-xs text-gray-400">
    Tiempo de descarga: {{ m.upload_time }}
  </p>

  <p v-if="m?.latitude != null && m?.longitude != null"
     class="text-xs text-gray-500">
    {{ m.latitude }}, {{ m.longitude }}
  </p>
</div>

      </button>
    </div>

    <RequestMediaModal v-model="showRequestModal" :imei="imei" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import RequestMediaModal from './RequestMediaModal.vue'

const props = defineProps({
  media: { type: Array, default: () => [] },
  imei: { type: String, default: '' }
})

const showRequestModal = ref(false)

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
