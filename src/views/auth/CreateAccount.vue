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

        <form @submit.prevent="submitRegister">
          <div v-if="role === 'student'">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('full_name') }}</label>
                <input type="text" class="form-control" v-model="student.name" required />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('university_id') }}</label>
                <input type="text" class="form-control" v-model="student.university_id" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('email_label') }}</label>
              <input type="email" class="form-control" v-model="student.email" />
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('phone_number') }}</label>
              <input type="text" class="form-control" v-model="student.phone_number" required />
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('password_label') }}</label>
                <input type="password" class="form-control" v-model="student.password" required />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('confirm_password') }}</label>
                <input type="password" class="form-control" v-model="student.password_confirmation" required />
              </div>
            </div>
            <div class="supervisor-box">
              <strong>{{ t('supervisor_id') }}</strong>
              <p class="small text-muted mb-2">{{ t('supervisor_first_time') }}</p>
              <input type="text" class="form-control" v-model="student.supervisor_code" required />
            </div>
          </div>

          <div v-else-if="role === 'supervisor'">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('full_name') }}</label>
                <input type="text" class="form-control" v-model="supervisor.name" required />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('university_id') }}</label>
                <input type="text" class="form-control" v-model="supervisor.university_id" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('email_label') }}</label>
              <input type="email" class="form-control" v-model="supervisor.email" />
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('phone_number') }}</label>
              <input type="text" class="form-control" v-model="supervisor.phone_number" required />
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('password_label') }}</label>
                <input type="password" class="form-control" v-model="supervisor.password" required />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('confirm_password') }}</label>
                <input type="password" class="form-control" v-model="supervisor.password_confirmation" required />
              </div>
            </div>
          </div>

          <div v-else>
            <div class="mb-3">
              <label class="form-label">{{ t('full_name') }}</label>
              <input type="text" class="form-control" v-model="company.name" required />
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('company_name') }}</label>
              <input type="text" class="form-control" v-model="company.company_name" required />
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('email_label') }}</label>
              <input type="email" class="form-control" v-model="company.email" required />
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('phone_number') }}</label>
              <input type="text" class="form-control" v-model="company.phone_number" required />
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('location') }}</label>
              <input type="text" class="form-control" v-model="company.company_address" required />
            </div>
            <div class="mb-3">
              <label class="form-label">{{ t('website') }}</label>
              <input type="text" class="form-control" v-model="company.company_website" />
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('password_label') }}</label>
                <input type="password" class="form-control" v-model="company.password" required />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('confirm_password') }}</label>
                <input type="password" class="form-control" v-model="company.password_confirmation" required />
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
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()
const route = useRoute()
const authStore = useAuthStore()

const role = ref('student')
const isLoading = ref(false)

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
}

const syncRoleFromRoute = () => {
  const requestedRole = typeof route.query.role === 'string' ? route.query.role : ''
  if (['student', 'supervisor', 'company'].includes(requestedRole)) {
    role.value = requestedRole
  } else {
    role.value = 'student'
  }
}

const submitRegister = () => {
  isLoading.value = true

  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (!token) {
    isLoading.value = false
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

  const htmlForm = document.createElement('form')
  htmlForm.method = 'POST'
  htmlForm.action = '/register'

  Object.entries(payload).forEach(([name, value]) => {
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = name
    input.value = value ?? ''
    htmlForm.appendChild(input)
  })

  document.body.appendChild(htmlForm)
  htmlForm.submit()
}

const goLogin = () => {
  window.location.href = '/login'
}

onMounted(async () => {
  if (authStore.isAuthenticated) {
    await authStore.logout()
  }

  syncRoleFromRoute()
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
