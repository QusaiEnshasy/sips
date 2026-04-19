<template>
  <div class="trello-callback-page" dir="rtl">
    <div class="callback-card">
      <div class="callback-icon" :class="{ success: status === 'success', error: status === 'error' }">
        <i v-if="status === 'success'" class="bi bi-check2-circle"></i>
        <i v-else-if="status === 'error'" class="bi bi-x-circle"></i>
        <div v-else class="spinner-border spinner-border-sm" role="status"></div>
      </div>

      <h2>{{ title }}</h2>
      <p>{{ message }}</p>

      <router-link v-if="status !== 'processing'" to="/company/trello-settings" class="btn-primary-soft">
        العودة إلى تكامل Trello
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { companyAPI } from '@/services/api/company'

const router = useRouter()
const status = ref('processing')
const message = ref('جاري حفظ ربط Trello بحساب الشركة...')

const title = computed(() => {
  if (status.value === 'success') return 'تم ربط Trello بنجاح'
  if (status.value === 'error') return 'تعذر ربط Trello'
  return 'جاري إكمال الربط'
})

const extractToken = () => {
  const hashParams = new URLSearchParams(window.location.hash.replace(/^#/, ''))
  const queryParams = new URLSearchParams(window.location.search)
  return hashParams.get('token') || queryParams.get('token') || ''
}

onMounted(async () => {
  const token = extractToken()

  if (!token) {
    status.value = 'error'
    message.value = 'لم يصل Token من Trello. حاول الربط مرة ثانية من صفحة الإعدادات.'
    return
  }

  try {
    await companyAPI.completeTrelloAuthorization({ trello_token: token })
    status.value = 'success'
    message.value = 'تم حفظ الربط بالخلفية. الآن اختر اللوحة التي تريد مزامنة مهامها.'
    window.history.replaceState({}, document.title, '/company/trello/callback')
    setTimeout(() => router.replace('/company/trello-settings'), 1200)
  } catch (error) {
    status.value = 'error'
    message.value = error?.response?.data?.message || 'تعذر حفظ ربط Trello. تأكد أنك وافقت من نفس حساب الشركة.'
  }
})
</script>

<style scoped>
.trello-callback-page {
  min-height: 70vh;
  display: grid;
  place-items: center;
  padding: 24px;
}

.callback-card {
  width: min(520px, 100%);
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 28px;
  padding: 34px 28px;
  text-align: center;
  box-shadow: 0 24px 70px rgba(15, 23, 42, .12);
}

.callback-icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 18px;
  border-radius: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #e0f2fe;
  color: #0369a1;
  font-size: 34px;
}

.callback-icon.success {
  background: #dcfce7;
  color: #15803d;
}

.callback-icon.error {
  background: #fee2e2;
  color: #b91c1c;
}

h2 {
  color: var(--text-dark);
  font-weight: 800;
  margin-bottom: 10px;
}

p {
  color: var(--text-muted);
  line-height: 1.8;
}

.btn-primary-soft {
  display: inline-flex;
  justify-content: center;
  margin-top: 12px;
  background: #7c3aed;
  color: #fff;
  text-decoration: none;
  border-radius: 999px;
  padding: 11px 22px;
  font-weight: 800;
}
</style>
