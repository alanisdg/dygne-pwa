<template>
  <div class="py-6">
    <div class="rounded-xl bg-white shadow-sm p-5">
      <h1 class="text-2xl font-semibold mb-2">Dashboard</h1>
      <p class="text-gray-600">Has iniciado sesión como:</p>
      <p class="mt-1 font-mono font-medium">{{ email }}</p>

      <div class="mt-6 flex gap-3">
        <button
          class="rounded-lg bg-black text-white px-4 py-2 font-semibold"
          @click="goHome"
        >Ir a inicio</button>
        <button
          class="rounded-lg border border-gray-300 px-4 py-2"
          @click="logout"
        >Cerrar sesión</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')

onMounted(() => {
  email.value = localStorage.getItem('auth_email') || '(sin email)'
})

function logout() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_email')
  window.location.href = '/'
}

function goHome() {
  router.push({ name: 'dashboard' })
}
</script>
