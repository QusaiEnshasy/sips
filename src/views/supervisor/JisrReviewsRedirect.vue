<template>
  <div class="jisr-redirect-page">
    <div class="redirect-card">
      <div class="spinner-border text-primary" role="status"></div>
      <h3 class="fw-bold mt-3 mb-2">جاري فتح تقييم برنامج الجسر</h3>
      <p class="text-muted mb-3">يتم الآن تحويلك إلى صفحة التقييم المباشرة.</p>
      <a class="btn btn-primary" :href="targetUrl">فتح الصفحة مباشرة</a>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue'

const targetUrl = computed(() => '/supervisor/jisr-reviews?server=1')

onMounted(() => {
  window.location.assign(targetUrl.value)
})
</script>

<style scoped>
.jisr-redirect-page {
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.redirect-card {
  width: min(520px, 100%);
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 24px;
  padding: 32px 24px;
  text-align: center;
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
}
</style>
