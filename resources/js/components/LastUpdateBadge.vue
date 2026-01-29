<template>
  <div class="flex items-center gap-2 text-xs">
    <span class="inline-flex h-6 w-6 rounded-full bg-blue-500/10 items-center justify-center text-blue-300">
      <Clock3 class="w-3.5 h-3.5" />
    </span>
    <span class="text-gray-400">{{ label }} </span>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Clock3 } from 'lucide-vue-next'

const props = defineProps({
  value: {
    type: String,
    required: true,
  },
})

const now = ref(Date.now())
let timerId = null

function parseDate(value) {
  if (!value) return null
  const d = new Date(value.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return null
  return d
}

const label = computed(() => {
  const parsed = parseDate(props.value)
  if (!parsed) return ''
  const diffMs = now.value - parsed.getTime()
  if (diffMs < 0) return '0 segs'
  const diffSec = Math.floor(diffMs / 1000)
  if (diffSec < 60) return `${diffSec} segs`
  const diffMin = Math.floor(diffSec / 60)
  if (diffMin === 1) return 'Hace 1 min'
  if (diffMin < 60) return `Hace ${diffMin} min`
  const diffHours = Math.floor(diffMin / 60)
  if (diffHours === 1) return 'Hace 1 hora'
  if (diffHours < 24) return `Hace ${diffHours} horas`
  const diffDays = Math.floor(diffHours / 24)
  if (diffDays === 1) return 'Hace 1 día'
  return `Hace ${diffDays} días`
})

onMounted(() => {
  timerId = window.setInterval(() => {
    now.value = Date.now()
  }, 1000)
})

onUnmounted(() => {
  if (timerId) {
    clearInterval(timerId)
    timerId = null
  }
})

watch(
  () => props.value,
  () => {
    // Reinicia el reloj al recibir un nuevo valor
    now.value = Date.now()
  }
)
</script>
