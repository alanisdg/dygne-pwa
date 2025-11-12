<template>
  <button
    type="button"
    class="px-3 py-1.5 bg-emerald-600 text-white rounded text-xs hover:bg-emerald-700 disabled:opacity-60"
    :disabled="busy || !media || !media.url"
    @click="onShare"
  >
    {{ busy ? 'Compartiendo…' : 'Compartir' }}
  </button>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  media: { type: Object, required: true },
})

const busy = ref(false)
const shareUrl = computed(() => props.media?.url || '')
const shareTitle = computed(() => (props.media?.type || 'Media'))
const shareText = computed(() => {
  const lat = props.media?.latitude
  const lng = props.media?.longitude
  const when = props.media?.captured_at || ''
  const coords = (lat != null && lng != null) ? `\nUbicación: ${lat}, ${lng}` : ''
  return `${shareTitle.value}\nCapturada: ${when}${coords}`.trim()
})

async function onShare() {
  if (!shareUrl.value) return
  try {
    busy.value = true
    if (navigator.share) {
      await navigator.share({ title: shareTitle.value, text: shareText.value, url: shareUrl.value })
      busy.value = false
      return
    }
    const text = encodeURIComponent(`${shareText.value}\n${shareUrl.value}`)
    const wa = `https://wa.me/?text=${text}`
    window.open(wa, '_blank')
  } catch (e) {
    console.error('Error al compartir', e)
  } finally {
    busy.value = false
  }
}
</script>
