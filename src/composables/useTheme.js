import { ref, watch, onMounted } from 'vue'

export function useTheme() {
  const isDark = ref(false)

  // تطبيق الثيم على عنصر html
  const applyTheme = (dark) => {
    if (dark) {
      document.documentElement.setAttribute('data-theme', 'dark')
      document.documentElement.setAttribute('data-bs-theme', 'dark')
    } else {
      document.documentElement.removeAttribute('data-theme')
      document.documentElement.setAttribute('data-bs-theme', 'light')
    }
    localStorage.setItem('theme', dark ? 'dark' : 'light')
  }

  // تبديل الثيم
  const toggleTheme = () => {
    isDark.value = !isDark.value
    applyTheme(isDark.value)
  }

  // تحميل الثيم المحفوظ
  const loadSavedTheme = () => {
    const saved = localStorage.getItem('theme')
    isDark.value = saved === 'dark'
    applyTheme(isDark.value)
  }

  onMounted(() => {
    loadSavedTheme()
  })

  // مراقبة تغيير الثيم
  watch(isDark, (newVal) => {
    applyTheme(newVal)
  })

  return {
    isDark,
    toggleTheme,
    loadSavedTheme
  }
}