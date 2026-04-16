<template>
  <div class="profile-page">
    <div class="settings-card mb-4" data-aos="fade-up" data-aos-delay="200">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h4 class="fw-bold mb-1">{{ t('profile_settings') }}</h4>
          <p class="text-muted mb-0">{{ t('manage_account') }}</p>
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div v-else-if="error" class="alert alert-warning rounded-4">{{ error }}</div>
    <div v-else-if="actionMessage" class="alert rounded-4" :class="actionType === 'success' ? 'alert-success' : 'alert-danger'">
      {{ actionMessage }}
    </div>

    <div v-else class="settings-card">
      <form @submit.prevent="saveProfile">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">{{ t('role') }}</label>
            <input class="form-control" :value="user.role" disabled>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">{{ t('status') }}</label>
            <input class="form-control" :value="user.status || '-'" disabled>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">{{ t('name') }}</label>
            <input class="form-control" v-model="form.name" :disabled="!isEditable('name')">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">{{ t('email') }}</label>
            <input class="form-control" :value="user.email" disabled>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">{{ t('phone_number') }}</label>
            <input class="form-control" v-model="form.phone_number" :disabled="!isEditable('phone_number')">
          </div>

          <div v-if="user.role === 'supervisor'" class="col-md-6">
            <label class="form-label fw-semibold">{{ t('supervisor_code') }}</label>
            <input class="form-control" :value="user.supervisor_code || '-'" disabled>
          </div>

          <template v-if="user.role === 'company'">
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ t('company_name') }}</label>
              <input class="form-control" v-model="form.company_name" :disabled="!isEditable('company_name')">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ t('website') }}</label>
              <input class="form-control" v-model="form.company_website" :disabled="!isEditable('company_website')">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">{{ t('location') }}</label>
              <input class="form-control" v-model="form.company_address" :disabled="!isEditable('company_address')">
            </div>
          </template>

          <div v-if="user.role === 'student'" class="col-md-6">
            <label class="form-label fw-semibold">{{ t('student_id') }}</label>
            <input class="form-control" :value="user.student_id || user.university_id || '-'" disabled>
          </div>
        </div>

        <div v-if="canEditAnyField" class="d-flex justify-content-end mt-4">
          <button type="submit" class="btn-save" :disabled="isSaving">
            <span v-if="!isSaving">{{ t('save_changes') }}</span>
            <span v-else>{{ t('saving') }}</span>
          </button>
        </div>
      </form>
    </div>

    <div
      v-if="canChangePassword"
      class="settings-card mb-4 mt-4"
      data-aos="fade-up"
      data-aos-delay="200"
    >
      <h6 class="card-title"><i class="bi bi-lock text-muted me-2"></i>{{ t('change_password') }}</h6>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">{{ t('current_password') }}</label>
          <input type="password" class="form-control editable-input" v-model="password.current" placeholder="••••••••" />
        </div>
        <div class="col-12">
          <label class="form-label">{{ t('new_password') }}</label>
          <input type="password" class="form-control editable-input" v-model="password.new" placeholder="••••••••" />
          <div class="text-muted small mt-2">{{ t('password_requirements') }}</div>
        </div>
        <div class="col-12">
          <label class="form-label">{{ t('confirm_new_password') }}</label>
          <input type="password" class="form-control editable-input" v-model="password.confirm" placeholder="••••••••" />
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn-cancel" @click="resetPassword">{{ t('cancel') }}</button>
        <button class="btn-save password-save-btn" @click="updatePassword">
          <i class="bi bi-key me-2"></i>
          {{ t('update_password') }}
        </button>
      </div>
    </div>

    <div
      v-if="canDeleteAccount"
      class="settings-card danger-zone"
      data-aos="fade-up"
      data-aos-delay="300"
    >
      <h6 class="danger-title mb-2">{{ t('danger_zone') }}</h6>
      <p class="text-muted small mb-4">{{ t('delete_warning') }}</p>
      <button class="btn-delete" @click="confirmDelete">{{ t('delete_account') }}</button>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import AOS from 'aos'

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
const webApi = axios.create({
  baseURL: '',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrf,
    Accept: 'application/json'
  },
  withCredentials: true
})

const authStore = useAuthStore()
const toastStore = useToastStore()
const router = useRouter()
const { t } = useI18n()

const isLoading = ref(false)
const isSaving = ref(false)
const error = ref('')
const actionMessage = ref('')
const actionType = ref('success')
const editableFields = ref([])
const user = reactive({
  role: '',
  status: '',
  name: '',
  email: '',
  phone_number: '',
  company_name: '',
  company_address: '',
  company_website: '',
  supervisor_code: '',
  university_id: '',
  student_id: ''
})

const form = reactive({
  name: '',
  phone_number: '',
  company_name: '',
  company_address: '',
  company_website: ''
})
const password = reactive({ current: '', new: '', confirm: '' })

const isEditable = (field) => editableFields.value.includes(field)
const canEditAnyField = computed(() => editableFields.value.length > 0)
const canChangePassword = computed(() => ['student', 'supervisor', 'company'].includes(String(user.role || '')))
const canDeleteAccount = computed(() => ['student', 'supervisor', 'company'].includes(String(user.role || '')))

const mapPayload = (payload) => {
  Object.assign(user, payload.user || {})
  editableFields.value = payload.editable_fields || []
  form.name = user.name || ''
  form.phone_number = user.phone_number || ''
  form.company_name = user.company_name || ''
  form.company_address = user.company_address || ''
  form.company_website = user.company_website || ''
}

const loadProfile = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const res = await webApi.get('/profile')
    mapPayload(res.data?.data || {})
  } catch (e) {
    error.value = e?.response?.data?.message || t('failed_load_profile')
  } finally {
    isLoading.value = false
  }
}

const saveProfile = async () => {
  if (!canEditAnyField.value) return
  isSaving.value = true
  error.value = ''
  try {
    const payload = {}
    if (isEditable('name')) payload.name = form.name
    if (isEditable('phone_number')) payload.phone_number = form.phone_number
    if (isEditable('company_name')) payload.company_name = form.company_name
    if (isEditable('company_address')) payload.company_address = form.company_address
    if (isEditable('company_website')) payload.company_website = form.company_website

    const res = await webApi.put('/profile', payload)
    mapPayload(res.data?.data || {})
    authStore.setUser({
      ...(authStore.user || {}),
      ...user,
      role: user.role
    })
    toastStore.addToast({
      type: 'success',
      message: t('profile_updated')
    })
  } catch (e) {
    const msg = e?.response?.data?.message || t('failed_update_profile')
    error.value = msg
    toastStore.addToast({ type: 'error', message: msg })
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  loadProfile()
  AOS.init({
    duration: 800,
    once: true,
    easing: 'ease-in-out-sine'
  })
})

const resetPassword = () => {
  password.current = ''
  password.new = ''
  password.confirm = ''
}

const extractApiError = (error, fallback) => {
  const data = error?.response?.data
  if (typeof data?.message === 'string' && data.message.trim() !== '') {
    return data.message
  }
  if (data?.errors && typeof data.errors === 'object') {
    const firstKey = Object.keys(data.errors)[0]
    if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey].length > 0) {
      return String(data.errors[firstKey][0])
    }
  }
  return fallback
}

const updatePassword = async () => {
  actionMessage.value = ''
  if (!password.current || !password.new || !password.confirm) {
    const msg = t('fill_all_password_fields')
    actionType.value = 'error'
    actionMessage.value = msg
    toastStore.addToast({ type: 'error', message: msg })
    alert(msg)
    return
  }
  if (password.new !== password.confirm) {
    const msg = t('passwords_do_not_match')
    actionType.value = 'error'
    actionMessage.value = msg
    toastStore.addToast({ type: 'error', message: msg })
    alert(msg)
    return
  }
  if (password.new.length < 8) {
    const msg = t('password_too_short')
    actionType.value = 'error'
    actionMessage.value = msg
    toastStore.addToast({ type: 'error', message: msg })
    alert(msg)
    return
  }

  try {
    await webApi.post('/change-password', {
      current_password: password.current,
      new_password: password.new,
      new_password_confirmation: password.confirm,
      confirm_password: password.confirm
    })
    const msg = t('password_updated')
    actionType.value = 'success'
    actionMessage.value = msg
    toastStore.addToast({ type: 'success', message: msg })
    alert(msg)
    resetPassword()
  } catch (e) {
    const msg = extractApiError(e, t('failed_update_password'))
    actionType.value = 'error'
    actionMessage.value = msg
    toastStore.addToast({
      type: 'error',
      message: msg
    })
    alert(msg)
  }
}

const confirmDelete = async () => {
  if (!confirm(t('confirm_delete_account'))) return

  try {
    await webApi.delete('/profile')
    const msg = t('account_deleted')
    actionType.value = 'success'
    actionMessage.value = msg
    toastStore.addToast({ type: 'success', message: msg })
    alert(msg)
    await authStore.logout()
    router.push('/login')
  } catch (e) {
    const msg = extractApiError(e, t('failed_delete_account'))
    actionType.value = 'error'
    actionMessage.value = msg
    toastStore.addToast({
      type: 'error',
      message: msg
    })
    alert(msg)
  }
}
</script>

<style scoped>
.profile-page { max-width: 900px; margin: 0 auto; padding: 20px 0; }
.settings-card {
  background: var(--card-bg);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  padding: 24px;
  box-shadow: var(--card-shadow);
}
.form-control {
  background-color: var(--input-bg);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 10px 14px;
  color: var(--text-dark);
}
.editable-input,
.form-control:not(:disabled) {
  color: #000 !important;
}
.form-control:disabled {
  opacity: 0.85;
  cursor: not-allowed;
}
.btn-save {
  background: var(--accent);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s ease;
  cursor: pointer;
  min-width: 120px;
}
.btn-save:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-1px);
}
.btn-cancel {
  background-color: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 600;
  color: var(--text-dark);
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 120px;
}
.btn-cancel:hover {
  background: var(--accent-soft);
  border-color: var(--accent);
  color: var(--accent);
}
.card-title { font-size: 16px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; color: var(--text-dark); }
.password-save-btn { background-color: #6366f1; }
.danger-zone { border: 1px solid #fee2e2; background-color: #fef2f2; }
[data-theme="dark"] .danger-zone { background-color: #450a0a; border-color: #7f1d1d; }
.danger-title { color: #b91c1c; font-weight: 700; }
[data-theme="dark"] .danger-title { color: #fca5a5; }
.btn-delete {
  background: var(--card-bg);
  border: 1px solid #fca5a5;
  color: #b91c1c;
  width: 100%;
  padding: 12px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}
.btn-delete:hover { background: #fee2e2; }
</style>
