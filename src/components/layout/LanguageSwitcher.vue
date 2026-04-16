<template>
  <select
    class="lang-select"
    v-model="selectedLang"
    :aria-label="t('select_language')"
  >
    <option value="en">🇬🇧 EN</option>
    <option value="ar">🇸🇦 AR</option>
  </select>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from '@/composables/useI18n'

const { currentLang, changeLanguage, t } = useI18n()

const selectedLang = computed({
  get: () => currentLang.value,
  set: (value) => changeLanguage(value)
})
</script>

<style scoped>
.lang-select {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 8px 12px;
  height: 40px;
  color: var(--text-dark);
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.lang-select:hover {
  border-color: var(--accent);
}
</style>
