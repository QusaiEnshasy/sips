<template>
  <div class="not-found-page">
    <div class="container text-center py-5">
      <div class="error-code display-1 fw-bold text-primary">404</div>
      <h1 class="mb-4">{{ t('page_not_found') }}</h1>
      <p class="text-muted mb-4">{{ t('page_not_found_message') }}</p>
      <router-link to="/" class="btn btn-primary">
        <i class="bi bi-house-door me-2"></i>
        {{ t('back_to_home') }}
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()
</script>

<style scoped>
.not-found-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--main-bg);
}

.error-code {
  font-size: 8rem;
  font-weight: 800;
  line-height: 1;
  background: linear-gradient(135deg, var(--accent), var(--primary-purple));
  -webkit-text-fill-color: transparent;
  margin-bottom: 1rem;
}

@media (max-width: 768px) {
  .error-code {
    font-size: 6rem;
  }
}
</style>