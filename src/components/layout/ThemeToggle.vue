<template>
  <button 
    class="theme-toggle-btn" 
    @click="toggleTheme"
    :title="t('toggle_theme')"
    :aria-label="t('toggle_theme')"
  >
    <i :class="isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill'"></i>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()
const { isDark, toggleTheme } = useTheme()
</script>

<style scoped>
.theme-toggle-btn {
  width: 40px;
  height: 40px;
  background: var(--accent-soft);
  border: none;
  border-radius: 10px;
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  cursor: pointer;
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.theme-toggle-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(124, 58, 237, 0.2);
}
</style>