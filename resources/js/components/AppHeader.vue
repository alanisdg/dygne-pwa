<template>
  <div class="flex items-center justify-between gap-3 px-4 pt-4 sm:px-0 sm:pt-0">
    <div class="flex items-center gap-3">
      <a
        v-if="backHref"
        :href="backHref"
        class="inline-flex items-center text-sm text-gray-400 hover:text-gray-100"
      >
        ← Volver
      </a>
      <h1 class="text-xl font-semibold tracking-tight">
        {{ title }}
      </h1>
    </div>

    <div class="flex items-center gap-3 relative">
      <a
        href="/notifications"
        class="inline-flex items-center justify-center rounded-full bg-white/5 hover:bg-white/10 border border-white/10 p-2 text-gray-200 hover:text-white transition"
        title="Notificaciones"
      >
        <Bell class="w-4 h-4" />
      </a>

      <div class="flex flex-col items-end gap-1">
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-full bg-white/5 hover:bg-white/10 border border-white/10 px-4 py-1.5 text-[11px] sm:text-xs font-mono transition disabled:opacity-60 max-w-[220px]"
          :disabled="loading"
          @click="toggleMenu"
        >
          <span class="flex flex-col items-end text-right leading-tight max-w-full">
            <span v-if="customerName" class="font-semibold text-[10px] text-gray-200 truncate">
              {{ customerName }}
            </span>
            <span class="text-[11px] sm:text-xs text-gray-300 truncate">
              {{ email || '(sin email)' }}
            </span>
          </span>
        </button>
        <div v-if="message" class="text-xs" :class="messageClass">
          {{ message }}
        </div>
      </div>

      <div
        v-if="menuOpen"
        class="absolute right-0 top-full mt-2 w-40 rounded-xl bg-[#050814] border border-white/10 shadow-lg py-1 text-sm z-20"
      >
        <button
          type="button"
          class="w-full text-left px-3 py-2 hover:bg-white/5 text-gray-200 flex items-center justify-between"
          :disabled="loading"
          @click="onClickLogout"
        >
          <span>Cerrar sesión</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Bell } from 'lucide-vue-next'

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  backHref: {
    type: String,
    default: null,
  },
  email: {
    type: String,
    default: '',
  },
})

const loading = ref(false)
const message = ref('')
const messageClass = ref('text-gray-600')
const menuOpen = ref(false)
const customerName = ref('')

// Cargar nombre de cliente desde localStorage si existe
try {
  const storedCustomerName = localStorage.getItem('auth_customer_name')
  customerName.value = storedCustomerName || ''
} catch (e) {
  console.warn('[Header] No se pudo leer auth_customer_name', e)
}

function toggleMenu() {
  if (loading.value) return
  menuOpen.value = !menuOpen.value
}

async function onClickLogout() {
  await logout()
  menuOpen.value = false
}

async function logout() {
  loading.value = true
  message.value = ''
  try {
    const token = localStorage.getItem('auth_token')
    if (token) {
      await fetch('https://app.dygne.com/api/logout', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        mode: 'cors',
      }).catch(() => {})
    }
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_email')
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_customer')
    localStorage.removeItem('auth_customer_name')
    customerName.value = ''
    message.value = 'Sesión cerrada'
    messageClass.value = 'text-green-600'
    setTimeout(() => { window.location.href = '/' }, 300)
    loading.value = false
  }
}
</script>
