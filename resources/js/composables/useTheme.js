import { ref } from 'vue'

const STORAGE_KEY = 'ui_theme'
const theme = ref('dark')

function applyTheme(value) {
  const normalized = value === 'light' ? 'light' : 'dark'
  theme.value = normalized

  if (typeof document !== 'undefined') {
    const root = document.documentElement
    root.classList.remove('theme-dark', 'theme-light')
    root.classList.add(`theme-${normalized}`)
    root.style.colorScheme = normalized
  }

  if (typeof localStorage !== 'undefined') {
    localStorage.setItem(STORAGE_KEY, normalized)
  }
}

function initTheme() {
  let stored = null
  if (typeof localStorage !== 'undefined') {
    stored = localStorage.getItem(STORAGE_KEY)
  }
  applyTheme(stored || 'dark')
}

function toggleTheme() {
  applyTheme(theme.value === 'dark' ? 'light' : 'dark')
}

export function useTheme() {
  return {
    theme,
    initTheme,
    toggleTheme,
    applyTheme,
  }
}
