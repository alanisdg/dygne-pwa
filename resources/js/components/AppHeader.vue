<template>
  <div class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <a
        v-if="backHref"
        :href="backHref"
        class="inline-flex items-center text-sm text-gray-400 hover:text-gray-100"
      >
        ← Volver
      </a>
      <img src="/images/icons/logo.png" alt="Logo" class="h-8" />
    </div>

    <div class="flex items-center gap-3 relative">
      <button
        type="button"
        class="inline-flex items-center justify-center rounded-full border p-2 transition"
        :class="theme === 'dark'
          ? 'bg-white/5 hover:bg-white/10 border-white/10 text-gray-200 hover:text-white'
          : 'bg-black/5 hover:bg-black/10 border-black/10 text-gray-700 hover:text-gray-900'"
        :title="theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        @click="toggleTheme"
      >
        <Sun v-if="theme === 'dark'" class="w-4 h-4" />
        <Moon v-else class="w-4 h-4" />
      </button>

      <a
        href="/notifications"
        class="inline-flex items-center justify-center rounded-full border p-2 transition"
        :class="theme === 'dark'
          ? 'bg-white/5 hover:bg-white/10 border-white/10 text-gray-200 hover:text-white'
          : 'bg-black/5 hover:bg-black/10 border-black/10 text-gray-700 hover:text-gray-900'"
        title="Notificaciones"
      >
        <Bell class="w-4 h-4" />
      </a>
      <CustomerSwitcher v-if="isAdmin" />

      <div class="flex flex-col items-end gap-1">
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-full bg-white/5/80 hover:bg-white/10 border border-white/10 px-4 py-1.5 text-xs font-mono transition disabled:opacity-60 max-w-[240px]"
          :disabled="loading"
          @click="toggleMenu"
        >
          <span class="flex flex-col items-start text-left leading-tight max-w-full">
            <span
              v-if="customerName"
              class="text-[10px] uppercase tracking-wide font-semibold text-gray-200/90 truncate"
            >
              {{ customerName }}
            </span>
            <span class="text-[11px] text-gray-300 truncate">
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
        <a
          href="/shares"
          class="block w-full text-left px-3 py-2 hover:bg-white/5 text-gray-200 flex items-center justify-between gap-2"
          @click="menuOpen = false"
        >
          <span class="flex items-center gap-2">
            <Share2 class="w-3.5 h-3.5" />
            <span>Compartidos</span>
          </span>
        </a>
        
        <a
          href="/configuracion"
          class="block w-full text-left px-3 py-2 hover:bg-white/5 text-gray-200 flex items-center justify-between gap-2"
          @click="menuOpen = false"
        >
          <span class="flex items-center gap-2">
            <Settings class="w-3.5 h-3.5" />
            <span>Configuración</span>
          </span>
        </a>
        <a
          href="/instalar-equipo"
          class="block w-full text-left px-3 py-2 hover:bg-white/5 text-gray-200 flex items-center justify-between gap-2"
          @click="menuOpen = false"
        >
          <span class="flex items-center gap-2">
            <Wrench class="w-3.5 h-3.5" />
            <span>Instalar Equipo</span>
          </span>
        </a>
        <button
          type="button"
          class="w-full text-left px-3 py-2 hover:bg-white/5 text-gray-200 flex items-center justify-between gap-2"
          :disabled="loading"
          @click="onClickLogout"
        >
          <span class="flex items-center gap-2">
            <LogOut class="w-3.5 h-3.5" />
            <span>Cerrar sesión</span>
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Bell, Settings, Share2, LogOut, Sun, Moon, Wrench } from 'lucide-vue-next'
import CustomerSwitcher from '@/components/CustomerSwitcher.vue'
import { useTheme } from '@/composables/useTheme'

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

const { theme, toggleTheme } = useTheme()

const loading = ref(false)
const message = ref('')
const messageClass = ref('text-gray-600')
const menuOpen = ref(false)
const customerName = ref('')
const isAdmin = ref(false)

// Cargar nombre de cliente y rol desde localStorage si existe
try {
  const storedCustomerName = localStorage.getItem('auth_customer_name')
  customerName.value = storedCustomerName || ''

  const rawUser = localStorage.getItem('auth_user')
  if (rawUser) {
    const parsed = JSON.parse(rawUser)
    const roleId = parsed?.role_id ?? parsed?.roleId
    isAdmin.value = Number(roleId) === 1
  }
} catch (e) {
  console.warn('[Header] No se pudo leer auth_customer_name o auth_user', e)
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
