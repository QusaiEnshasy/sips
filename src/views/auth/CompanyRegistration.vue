<template>
  <div class="company-registration-page">
    <div class="registration-container">
      <div class="text-center mb-4">
        <div class="logo-box"><i class="bi bi-building"></i></div>
        <h3 class="fw-bold mb-2">{{ t('company_registration') }}</h3>
        <p class="text-muted mb-0">{{ t('register_offer_training') }}</p>
      </div>

      <div class="registration-card" data-aos="fade-up">
        <div class="d-flex justify-content-between mb-4 flex-wrap gap-2">
          <span class="badge-info"><i class="bi bi-building me-1"></i>{{ t('company_registration') }}</span>
          <span class="badge-admin"><i class="bi bi-clock me-1"></i>{{ t('requires_admin') }}</span>
        </div>

        <form @submit.prevent="handleSubmit">
          <div class="mb-4">
            <label class="form-label">{{ t('company_name') }} <span class="text-danger">*</span></label>
            <div class="input-group"><span class="input-group-text"><i class="bi bi-building"></i></span>
              <input type="text" class="form-control" :placeholder="t('company_name_placeholder')" v-model="form.companyName" required />
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">{{ t('industry') }}</label>
              <select class="form-select" v-model="form.industry">
                <option value="" disabled selected>{{ t('select_industry') }}</option>
                <option v-for="ind in industries" :key="ind" :value="ind">{{ t(ind) }}</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ t('company_size') }}</label>
              <select class="form-select" v-model="form.size">
                <option value="" disabled selected>{{ t('select_size') }}</option>
                <option v-for="size in sizes" :key="size" :value="size">{{ t(size) }}</option>
              </select>
            </div>
          </div>

          <h6 class="section-title">{{ t('contact_information') }}</h6>

          <div class="mb-4">
            <label class="form-label">{{ t('contact_email') }} <span class="text-danger">*</span></label>
            <div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" class="form-control" :placeholder="t('contact_email_placeholder')" v-model="form.email" required />
            </div>
            <small class="text-muted mt-1 d-block">{{ t('email_used_login') }}</small>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">{{ t('phone_number') }}</label>
              <div class="input-group"><span class="input-group-text"><i class="bi bi-telephone"></i></span>
                <input type="tel" class="form-control" :placeholder="t('phone_placeholder')" v-model="form.phone" />
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ t('website') }}</label>
              <div class="input-group"><span class="input-group-text"><i class="bi bi-globe"></i></span>
                <input type="url" class="form-control" :placeholder="t('website_placeholder')" v-model="form.website" />
              </div>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">{{ t('location') }}</label>
            <div class="input-group"><span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
              <input type="text" class="form-control" :placeholder="t('location_placeholder')" v-model="form.location" />
            </div>
          </div>

          <h6 class="section-title">{{ t('account_security') }}</h6>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">{{ t('password') }} <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input :type="showPassword ? 'text' : 'password'" class="form-control" :placeholder="t('password_placeholder')" v-model="form.password" required />
                <button class="input-group-text password-toggle" type="button" @click="showPassword = !showPassword">
                  <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ t('confirm_password') }} <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input :type="showConfirmPassword ? 'text' : 'password'" class="form-control" :placeholder="t('confirm_password_placeholder')" v-model="form.confirmPassword" required />
                <button class="input-group-text password-toggle" type="button" @click="showConfirmPassword = !showConfirmPassword">
                  <i :class="showConfirmPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="alert-approval">
            <div class="d-flex align-items-start gap-3">
              <i class="bi bi-info-circle text-warning fs-5 flex-shrink-0"></i>
              <div><h6 class="mb-1 fw-bold">{{ t('admin_approval_required') }}</h6>
              <p class="mb-0 text-muted small">{{ t('registration_review') }}</p></div>
            </div>
          </div>

          <button type="submit" class="btn-submit w-100 mt-4" :disabled="isSubmitting">
            <span v-if="!isSubmitting"><i class="bi bi-send me-2"></i>{{ t('submit_approval') }}</span>
            <span v-else><span class="spinner-border spinner-border-sm me-2"></span>{{ t('submitting') }}</span>
          </button>

          <div class="text-center mt-4">
            <small class="text-muted">{{ t('already_approved') }}
              <router-link to="/company/login" class="text-decoration-none fw-medium text-primary">{{ t('sign_in') }}</router-link>
            </small>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { useToastStore } from '@/stores/toast'
import { authAPI } from '@/services/api/auth'

const { t } = useI18n()
const router = useRouter()
const toastStore = useToastStore()

const form = reactive({
  companyName: '', industry: '', size: '', email: '', phone: '', website: '', location: '', password: '', confirmPassword: ''
})

const industries = ['technology', 'finance', 'healthcare', 'education', 'consulting']
const sizes = ['1-10', '11-50', '51-200', '200+']

const showPassword = ref(false)
const showConfirmPassword = ref(false)
const isSubmitting = ref(false)

const handleSubmit = async () => {
  if (form.password !== form.confirmPassword) {
    toastStore.addToast({ type: 'error', title: t('error'), message: t('passwords_do_not_match') })
    return
  }

  if (form.password.length < 8) {
    toastStore.addToast({ type: 'error', title: t('error'), message: t('password_too_short') })
    return
  }

  isSubmitting.value = true
  try {
    const payload = {
      company_name: form.companyName,
      industry: form.industry,
      size: form.size,
      email: form.email,
      phone: form.phone,
      website: form.website,
      location: form.location,
      password: form.password
    }

    const response = await authAPI.registerCompany(payload)

    if (response.data.status === 'success') {
      toastStore.addToast({ type: 'success', title: t('success'), message: t('registration_success') })
      router.push('/company/login')
    }
  } catch (error) {
    toastStore.addToast({ type: 'error', title: t('error'), message: error.response?.data?.message || t('error_occurred') })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.company-registration-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

[data-theme="dark"] .company-registration-page { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }

.registration-container { width: 100%; max-width: 700px; }
.logo-box { width: 70px; height: 70px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: white; font-size: 30px; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3); }
.registration-card { background: var(--card-bg); border-radius: 24px; padding: 40px; border: 1px solid var(--border-color); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05); }

.badge-info { background: #f5f3ff; color: #7c3aed; font-size: 0.75rem; padding: 6px 16px; border-radius: 20px; font-weight: 600; }
.badge-admin { background: #eff6ff; color: #2563eb; font-size: 0.75rem; padding: 6px 16px; border-radius: 20px; }

.form-label { font-size: 0.85rem; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; display: block; }
.input-group { border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color); transition: all 0.3s ease; }
.input-group:focus-within { border-color: #10b981; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }
.input-group-text { background: transparent; border: none; color: #94a3b8; padding: 0 15px; }
.form-control, .form-select { border: none; padding: 12px 15px 12px 0; font-size: 14px; background: transparent; color: var(--text-dark); }
.form-control:focus, .form-select:focus { outline: none; box-shadow: none; }
.password-toggle { cursor: pointer; transition: all 0.2s ease; }
.password-toggle:hover { color: #10b981; }

.section-title { font-size: 0.9rem; font-weight: 700; color: var(--text-dark); margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 2px solid var(--border-color); }
.alert-approval { background: #fffbeb; border: 1px solid #fde68a; border-radius: 16px; padding: 20px; margin: 30px 0 20px; }
[data-theme="dark"] .alert-approval { background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.2); }

.btn-submit { background: linear-gradient(135deg, #10b981, #059669); border: none; padding: 14px; border-radius: 12px; font-weight: 600; color: white; transition: all 0.3s ease; cursor: pointer; }
.btn-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3); }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }

@media (max-width: 768px) { .registration-card { padding: 30px 20px; } .logo-box { width: 60px; height: 60px; font-size: 24px; } }
@media (max-width: 576px) { .registration-card { padding: 25px 15px; } .d-flex.justify-content-between { flex-direction: column; align-items: flex-start !important; } .badge-info, .badge-admin { width: 100%; text-align: center; } }
</style>
