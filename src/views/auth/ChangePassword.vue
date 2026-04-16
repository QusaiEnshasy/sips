<template>
  <div class="change-password-page">
    <div class="container">
      <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-md-6">
          <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white border-0 pt-4 pb-0">
              <div class="text-center mb-4">
                <div class="logo-box mx-auto"><i class="bi bi-shield-lock"></i></div>
                <h4 class="fw-bold mt-3">{{ t('change_password') }}</h4>
                <p class="text-muted small">{{ t('first_time_login_message') }}</p>
              </div>
            </div>
            <div class="card-body p-4">
              <form @submit.prevent="changePassword">
                <div class="mb-3">
                  <label class="form-label fw-bold">{{ t('current_password') }}</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                    <input :type="showCurrent ? 'text' : 'password'" class="form-control border-start-0 ps-0" v-model="form.current_password" required placeholder="••••••••" />
                    <button class="input-group-text bg-light" type="button" @click="showCurrent = !showCurrent">
                      <i :class="showCurrent ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">{{ t('new_password') }}</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-key"></i></span>
                    <input :type="showNew ? 'text' : 'password'" class="form-control border-start-0 ps-0" v-model="form.new_password" required placeholder="••••••••" />
                    <button class="input-group-text bg-light" type="button" @click="showNew = !showNew">
                      <i :class="showNew ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                  </div>
                  <div class="form-text small text-muted mt-1">{{ t('password_requirements') }}</div>
                </div>
                <div class="mb-4">
                  <label class="form-label fw-bold">{{ t('confirm_new_password') }}</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-check-circle"></i></span>
                    <input :type="showConfirm ? 'text' : 'password'" class="form-control border-start-0 ps-0" v-model="form.new_password_confirmation" required placeholder="••••••••" />
                    <button class="input-group-text bg-light" type="button" @click="showConfirm = !showConfirm">
                      <i :class="showConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                  </div>
                </div>
                <button type="submit" class="btn-accent-gradient w-100 py-2" :disabled="isLoading">
                  <span v-if="!isLoading"><i class="bi bi-check-lg me-2"></i>{{ t('change_password') }}</span>
                  <span v-else><span class="spinner-border spinner-border-sm me-2"></span>{{ t('saving') }}</span>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { useToastStore } from '@/stores/toast'
import { authAPI } from '@/services/api/auth'

const { t } = useI18n()
const router = useRouter()
const toastStore = useToastStore()

const isLoading = ref(false)
const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)

const form = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const changePassword = async () => {
  if (form.value.new_password !== form.value.new_password_confirmation) {
    toastStore.addToast({ type: 'error', title: t('error'), message: t('passwords_do_not_match') })
    return
  }

  if (form.value.new_password.length < 8) {
    toastStore.addToast({ type: 'error', title: t('error'), message: t('password_too_short') })
    return
  }

  isLoading.value = true
  try {
    await authAPI.changePassword(form.value)
    toastStore.addToast({ type: 'success', title: t('success'), message: t('password_updated') })
    router.push('/dashboard')
  } catch (error) {
    toastStore.addToast({ type: 'error', title: t('error'), message: error.response?.data?.message || t('error_occurred') })
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.change-password-page { background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%); min-height: 100vh; }
[data-theme="dark"] .change-password-page { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
.card { background: var(--card-bg); border: 1px solid var(--border-color); }
.logo-box { width: 70px; height: 70px; background: linear-gradient(135deg, #7c3aed, #6d28d9); border-radius: 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; }
.btn-accent-gradient { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.3s ease; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.btn-accent-gradient:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(124,58,237,0.3); }
.form-label { font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; }
.input-group-text { background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-muted); }
.form-control { background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-dark); }
.form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
</style>
