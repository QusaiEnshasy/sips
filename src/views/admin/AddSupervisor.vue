<template>
  <div class="add-supervisor-page">
    <div class="page-header mb-4">
      <h2 class="fw-bold mb-2">{{ t('add_supervisor') }}</h2>
      <p class="text-muted mb-0">{{ t('add_supervisor_desc') }}</p>
    </div>

    <div class="form-card mb-4">
      <form @submit.prevent="handleSubmit">
        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label fw-bold">{{ t('full_name') }} *</label>
            <input type="text" class="form-control" v-model.trim="form.name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">{{ t('email') }}</label>
            <input type="email" class="form-control" v-model.trim="form.email">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">{{ t('university_id') }} *</label>
            <input type="number" class="form-control" v-model="form.university_id" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">{{ t('phone_number') }} *</label>
            <input type="text" class="form-control" v-model.trim="form.phone_number" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">{{ t('password') }} *</label>
            <input type="password" class="form-control" v-model="form.password" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">{{ t('confirm_password') }} *</label>
            <input type="password" class="form-control" v-model="form.password_confirmation" required>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary w-100" :disabled="isSubmitting">
              <span v-if="!isSubmitting"><i class="bi bi-person-plus me-2"></i>{{ t('create_supervisor') }}</span>
              <span v-else><span class="spinner-border spinner-border-sm me-2"></span>{{ t('creating') }}</span>
            </button>
          </div>
        </div>
      </form>
    </div>

    <div v-if="created" class="result-card">
      <div class="d-flex align-items-center gap-2 mb-2 text-success fw-bold">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ t('supervisor_created_successfully') }}</span>
      </div>
      <div class="row g-3 small">
        <div class="col-md-6"><strong>{{ t('full_name') }}:</strong> {{ created.name }}</div>
        <div class="col-md-6"><strong>{{ t('email') }}:</strong> {{ created.email || '-' }}</div>
        <div class="col-md-6"><strong>{{ t('university_id') }}:</strong> {{ created.university_id }}</div>
        <div class="col-md-6"><strong>{{ t('phone_number') }}:</strong> {{ created.phone_number }}</div>
        <div class="col-12">
          <div class="code-box">
            <span class="code-label">{{ t('supervisor_code') }}</span>
            <span class="code-value">{{ created.supervisor_code }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { adminAPI } from '@/services/api/admin'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()

const isSubmitting = ref(false)
const created = ref(null)

const form = reactive({
  name: '',
  email: '',
  university_id: '',
  phone_number: '',
  password: '',
  password_confirmation: ''
})

const resetForm = () => {
  form.name = ''
  form.email = ''
  form.university_id = ''
  form.phone_number = ''
  form.password = ''
  form.password_confirmation = ''
}

const handleSubmit = async () => {
  if (form.password !== form.password_confirmation) {
    alert(t('password_confirmation_not_match'))
    return
  }

  isSubmitting.value = true
  try {
    const { data } = await adminAPI.createSupervisor({
      name: form.name,
      email: form.email || null,
      university_id: form.university_id,
      phone_number: form.phone_number,
      password: form.password
    })

    created.value = data.data
    resetForm()
  } catch (error) {
    const msg = error?.response?.data?.message || t('create_supervisor_failed')
    alert(msg)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.add-supervisor-page { padding: 20px 0; max-width: 900px; margin: 0 auto; }
.form-card, .result-card { background: var(--card-bg); border-radius: 20px; padding: 24px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow); }
.form-control { background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 14px; font-size: 14px; color: var(--text-dark); width: 100%; }
.form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(124,58,237,.1); outline: none; }
.btn-primary { background: linear-gradient(135deg, #7c3aed, #6d28d9); border: none; border-radius: 10px; padding: 12px; font-weight: 600; color: white; }
.code-box { background: #f5f3ff; border: 1px dashed #8b5cf6; border-radius: 12px; padding: 12px; display: flex; align-items: center; justify-content: space-between; }
.code-label { color: #6b7280; font-weight: 600; }
.code-value { font-weight: 800; color: #5b21b6; letter-spacing: .5px; }
</style>
