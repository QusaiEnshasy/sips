<template>
  <div class="register-page">
    <div class="register-container">
      <div class="register-card animate__animated animate__fadeIn">
        <div class="text-center mb-4">
          <div class="logo-box"><i class="bi bi-person-plus-fill fs-3"></i></div>
          <h3 class="fw-bold mb-1">{{ t('create_account') }}</h3>
          <p class="text-muted small mb-4">{{ t('join_platform') }}</p>
        </div>

        <div class="tabs">
          <button type="button" class="tab-btn student-btn" :class="{ active: role === 'student' }" @click="setRole('student')">{{ t('student_portal') }}</button>
          <button type="button" class="tab-btn supervisor-btn" :class="{ active: role === 'supervisor' }" @click="setRole('supervisor')">{{ t('supervisor_portal') }}</button>
          <button type="button" class="tab-btn company-btn" :class="{ active: role === 'company' }" @click="setRole('company')">{{ t('company_portal') }}</button>
        </div>

        <form novalidate @submit.prevent="submitRegister">
          <div v-if="generalError" class="alert alert-danger rounded-4">
            {{ generalError }}
          </div>

          <div v-if="role === 'student'">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('full_name') }}</label>
                <input type="text" class="form-control" :class="fieldClass('name')" data-register-field="name" v-model="student.name" @input="clearFieldError('name')" required />
                <div v-if="fieldError('name')" class="invalid-feedback d-block">{{ fieldError('name') }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('university_id') }}</label>
                <input type="text" class="form-control" :class="fieldClass('university_id')" data-register-field="university_id" v-model="student.university_id" @input="clearFieldError('university_id')" required />
                <div v-if="fieldError('university_id')" class="invalid-feedback d-block">{{ fieldError('university_id') }}</div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('email_label') }}</label>
              <input type="email" class="form-control" :class="fieldClass('email')" data-register-field="email" v-model="student.email" @input="clearFieldError('email')" />
              <div v-if="fieldError('email')" class="invalid-feedback d-block">{{ fieldError('email') }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('phone_number') }}</label>
              <input type="text" class="form-control" :class="fieldClass('phone_number')" data-register-field="phone_number" v-model="student.phone_number" @input="clearFieldError('phone_number')" required />
              <div v-if="fieldError('phone_number')" class="invalid-feedback d-block">{{ fieldError('phone_number') }}</div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('password_label') }}</label>
                <input type="password" class="form-control" :class="fieldClass('password')" data-register-field="password" v-model="student.password" @input="clearFieldError('password')" required />
                <div v-if="fieldError('password')" class="invalid-feedback d-block">{{ fieldError('password') }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('confirm_password') }}</label>
                <input type="password" class="form-control" :class="fieldClass('password_confirmation')" data-register-field="password_confirmation" v-model="student.password_confirmation" @input="clearFieldError('password_confirmation'); clearFieldError('password')" required />
                <div v-if="fieldError('password_confirmation')" class="invalid-feedback d-block">{{ fieldError('password_confirmation') }}</div>
              </div>
            </div>
            <div class="supervisor-box">
              <strong>{{ t('supervisor_id') }}</strong>
              <p class="small text-muted mb-2">{{ t('supervisor_first_time') }}</p>
              <input type="text" class="form-control" :class="fieldClass('supervisor_code')" data-register-field="supervisor_code" v-model="student.supervisor_code" @input="clearFieldError('supervisor_code')" required />
              <div v-if="fieldError('supervisor_code')" class="invalid-feedback d-block">{{ fieldError('supervisor_code') }}</div>
            </div>
          </div>

          <div v-else-if="role === 'supervisor'">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('full_name') }}</label>
                <input type="text" class="form-control" :class="fieldClass('name')" data-register-field="name" v-model="supervisor.name" @input="clearFieldError('name')" required />
                <div v-if="fieldError('name')" class="invalid-feedback d-block">{{ fieldError('name') }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('university_id') }}</label>
                <input type="text" class="form-control" :class="fieldClass('university_id')" data-register-field="university_id" v-model="supervisor.university_id" @input="clearFieldError('university_id')" required />
                <div v-if="fieldError('university_id')" class="invalid-feedback d-block">{{ fieldError('university_id') }}</div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('email_label') }}</label>
              <input type="email" class="form-control" :class="fieldClass('email')" data-register-field="email" v-model="supervisor.email" @input="clearFieldError('email')" />
              <div v-if="fieldError('email')" class="invalid-feedback d-block">{{ fieldError('email') }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('phone_number') }}</label>
              <input type="text" class="form-control" :class="fieldClass('phone_number')" data-register-field="phone_number" v-model="supervisor.phone_number" @input="clearFieldError('phone_number')" required />
              <div v-if="fieldError('phone_number')" class="invalid-feedback d-block">{{ fieldError('phone_number') }}</div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('password_label') }}</label>
                <input type="password" class="form-control" :class="fieldClass('password')" data-register-field="password" v-model="supervisor.password" @input="clearFieldError('password')" required />
                <div v-if="fieldError('password')" class="invalid-feedback d-block">{{ fieldError('password') }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('confirm_password') }}</label>
                <input type="password" class="form-control" :class="fieldClass('password_confirmation')" data-register-field="password_confirmation" v-model="supervisor.password_confirmation" @input="clearFieldError('password_confirmation'); clearFieldError('password')" required />
                <div v-if="fieldError('password_confirmation')" class="invalid-feedback d-block">{{ fieldError('password_confirmation') }}</div>
              </div>
            </div>
          </div>

          <div v-else>
            <div class="mb-3">
              <label class="form-label">{{ t('full_name') }}</label>
              <input type="text" class="form-control" :class="fieldClass('name')" data-register-field="name" v-model="company.name" @input="clearFieldError('name')" required />
              <div v-if="fieldError('name')" class="invalid-feedback d-block">{{ fieldError('name') }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('company_name') }}</label>
              <input type="text" class="form-control" :class="fieldClass('company_name')" data-register-field="company_name" v-model="company.company_name" @input="clearFieldError('company_name')" required />
              <div v-if="fieldError('company_name')" class="invalid-feedback d-block">{{ fieldError('company_name') }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('email_label') }}</label>
              <input type="email" class="form-control" :class="fieldClass('email')" data-register-field="email" v-model="company.email" @input="clearFieldError('email')" required />
              <div v-if="fieldError('email')" class="invalid-feedback d-block">{{ fieldError('email') }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('phone_number') }}</label>
              <input type="text" class="form-control" :class="fieldClass('phone_number')" data-register-field="phone_number" v-model="company.phone_number" @input="clearFieldError('phone_number')" required />
              <div v-if="fieldError('phone_number')" class="invalid-feedback d-block">{{ fieldError('phone_number') }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('location') }}</label>
              <input type="text" class="form-control" :class="fieldClass('company_address')" data-register-field="company_address" v-model="company.company_address" @input="clearFieldError('company_address')" required />
              <div v-if="fieldError('company_address')" class="invalid-feedback d-block">{{ fieldError('company_address') }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('website') }}</label>
              <input type="text" class="form-control" :class="fieldClass('company_website')" data-register-field="company_website" v-model="company.company_website" @input="clearFieldError('company_website')" />
              <div v-if="fieldError('company_website')" class="invalid-feedback d-block">{{ fieldError('company_website') }}</div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('password_label') }}</label>
                <input type="password" class="form-control" :class="fieldClass('password')" data-register-field="password" v-model="company.password" @input="clearFieldError('password')" required />
                <div v-if="fieldError('password')" class="invalid-feedback d-block">{{ fieldError('password') }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('confirm_password') }}</label>
                <input type="password" class="form-control" :class="fieldClass('password_confirmation')" data-register-field="password_confirmation" v-model="company.password_confirmation" @input="clearFieldError('password_confirmation'); clearFieldError('password')" required />
                <div v-if="fieldError('password_confirmation')" class="invalid-feedback d-block">{{ fieldError('password_confirmation') }}</div>
              </div>
            </div>
          </div>

          <button type="submit" class="main-btn" :disabled="isLoading">
            <span v-if="!isLoading"><i class="fa-solid fa-arrow-right-to-bracket me-2"></i>{{ t('create_account') }}</span>
            <span v-else><span class="spinner-border spinner-border-sm me-2"></span>{{ t('signing_up') }}</span>
          </button>

          <div class="footer">
            {{ t('already_member') }}
            <a href="#" @click.prevent="goLogin">{{ t('sign_in') }}</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { nextTick, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()
const route = useRoute()
const authStore = useAuthStore()

const role = ref('student')
const isLoading = ref(false)
const errors = ref({})
const generalError = ref('')

const student = reactive({
  name: '', university_id: '', email: '', phone_number: '', password: '', password_confirmation: '', supervisor_code: ''
})

const supervisor = reactive({
  name: '', university_id: '', email: '', phone_number: '', password: '', password_confirmation: ''
})

const company = reactive({
  name: '', company_name: '', email: '', phone_number: '', company_address: '', company_website: '', password: '', password_confirmation: ''
})

const setRole = (value) => {
  role.value = value
  errors.value = {}
  generalError.value = ''
}

const syncRoleFromRoute = () => {
  const requestedRole = typeof route.query.role === 'string' ? route.query.role : ''
  if (['student', 'supervisor', 'company'].includes(requestedRole)) {
    role.value = requestedRole
  } else {
    role.value = 'student'
  }
}

const roleFields = {
  student: ['name', 'university_id', 'email', 'phone_number', 'password', 'password_confirmation', 'supervisor_code'],
  supervisor: ['name', 'university_id', 'email', 'phone_number', 'password', 'password_confirmation'],
  company: ['name', 'company_name', 'email', 'phone_number', 'company_address', 'company_website', 'password', 'password_confirmation']
}

const fieldError = (field) => {
  const value = errors.value[field]
  if (Array.isArray(value)) return value[0] || ''
  return value || ''
}

const fieldClass = (field) => ({
  'is-invalid': Boolean(fieldError(field))
})

const clearFieldError = (field) => {
  if (!errors.value[field]) return
  const nextErrors = { ...errors.value }
  delete nextErrors[field]
  errors.value = nextErrors
}

const firstErrorField = () => {
  const fields = roleFields[role.value] || []
  return fields.find((field) => fieldError(field)) || Object.keys(errors.value)[0] || ''
}

const focusFirstError = async () => {
  await nextTick()
  const field = firstErrorField()
  if (!field) return

  const input = document.querySelector(`[data-register-field="${field}"]`)
  input?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  input?.focus()
}

const applyOldInput = () => {
  const oldInput = window.__REGISTER_OLD__ || {}
  if (!oldInput || Object.keys(oldInput).length === 0) return

  if (['student', 'supervisor', 'company'].includes(oldInput.role)) {
    role.value = oldInput.role
  }

  const target = role.value === 'student'
    ? student
    : (role.value === 'supervisor' ? supervisor : company)

  Object.keys(target).forEach((key) => {
    if (key.includes('password')) return
    if (oldInput[key] !== undefined && oldInput[key] !== null) {
      target[key] = oldInput[key]
    }
  })
}

const applyInitialErrors = async () => {
  const initialErrors = window.__REGISTER_ERRORS__ || {}
  if (!initialErrors || Object.keys(initialErrors).length === 0) return

  errors.value = initialErrors
  generalError.value = Object.values(initialErrors).flat()[0] || ''
  await focusFirstError()
}

const submitRegister = async () => {
  isLoading.value = true
  errors.value = {}
  generalError.value = ''

  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (!token) {
    isLoading.value = false
    generalError.value = 'Missing CSRF token.'
    return
  }

  const payload = { _token: token, role: role.value }

  if (role.value === 'student') {
    Object.assign(payload, student)
  } else if (role.value === 'supervisor') {
    Object.assign(payload, supervisor)
  } else {
    Object.assign(payload, company)
  }

  try {
    const response = await axios.post('/register', payload, {
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json'
      }
    })

    if (response.data?.message) {
      sessionStorage.setItem('register_success_message', response.data.message)
    }

    window.location.href = response.data?.redirect || '/login'
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data?.errors || {}
      generalError.value = error.response.data?.message || Object.values(errors.value).flat()[0] || ''
      await focusFirstError()
      return
    }

    generalError.value = error.response?.data?.message || 'تعذر إنشاء الحساب. تأكد من البيانات وحاول مرة أخرى.'
  } finally {
    isLoading.value = false
  }
}

const goLogin = () => {
  window.location.href = '/login'
}

onMounted(async () => {
  if (authStore.isAuthenticated) {
    await authStore.logout()
  }

  syncRoleFromRoute()
  applyOldInput()
  await applyInitialErrors()
})
watch(() => route.query.role, syncRoleFromRoute)
</script>

<style scoped>
.register-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #eef2ff, #f8fafc);
  padding: 30px 10px;
}

.register-container {
  width: 100%;
  max-width: 720px;
}

.register-card {
  padding: 35px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.9);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.logo-box {
  width: 64px;
  height: 64px;
  margin: 0 auto 12px;
  border-radius: 14px;
  background: #7c3aed;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tabs {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.tab-btn {
  border: none;
  padding: 10px 25px;
  border-radius: 30px;
  font-weight: 600;
}

.student-btn {
  background: #f3e8ff;
  color: #7747ca;
}

.student-btn.active {
  background: #7747ca;
  color: #fff;
}

.supervisor-btn {
  background: #fff1e6;
  color: #db733a;
}

.supervisor-btn.active {
  background: #db733a;
  color: #fff;
}

.company-btn {
  background: #e6f9f0;
  color: #16a34a;
}

.company-btn.active {
  background: #16a34a;
  color: #fff;
}

.supervisor-box {
  border: 2px solid #e9d5ff;
  border-radius: 15px;
  padding: 20px;
  background: #faf5ff;
  margin-top: 8px;
  margin-bottom: 16px;
}

.form-control.is-invalid {
  border-color: #dc3545;
  box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12);
}

.invalid-feedback {
  font-size: 13px;
}

.main-btn {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 30px;
  color: white;
  font-weight: 600;
  background: linear-gradient(90deg, #9333ea, #2563eb);
}

.footer {
  text-align: center;
  margin-top: 15px;
  font-size: 14px;
}
</style>
