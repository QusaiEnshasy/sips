<template>
  <div class="admin-general-tasks">
    <div class="header mb-4">
      <h2 class="fw-bold mb-1">{{ t('general_task_broadcast') }}</h2>
      <p class="text-muted mb-0">{{ t('general_task_broadcast_desc') }}</p>
    </div>

    <div class="card-box mb-4">
      <form @submit.prevent="submitTask">
        <div class="mb-3">
          <label class="form-label fw-semibold">{{ t('title') }}</label>
          <input v-model.trim="form.title" type="text" class="form-control" required maxlength="255">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">{{ t('details') }}</label>
          <textarea v-model.trim="form.details" class="form-control" rows="4" maxlength="5000"></textarea>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">{{ t('due_date') }}</label>
            <input v-model="form.due_date" type="date" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">{{ t('label') }}</label>
            <select v-model="form.label" class="form-select">
              <option value="">{{ t('none') }}</option>
              <option value="red">{{ t('urgent') }}</option>
              <option value="green">{{ t('low') }}</option>
              <option value="blue">{{ t('feature') }}</option>
            </select>
          </div>
        </div>

        <button class="btn btn-primary" :disabled="submitting">
          <span v-if="!submitting"><i class="bi bi-send me-2"></i>{{ t('broadcast_task') }}</span>
          <span v-else><span class="spinner-border spinner-border-sm me-2"></span>{{ t('submitting') }}</span>
        </button>
      </form>
    </div>

    <div class="card-box">
      <h5 class="fw-bold mb-3">{{ t('approved_applications') }}</h5>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>{{ t('student') }}</th>
              <th>{{ t('program') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in applications"
              :key="item.id"
              class="clickable-row"
              @click="openBoard(item.id)"
            >
              <td class="fw-semibold">{{ item.student?.name || '-' }}</td>
              <td>{{ item.opportunity?.title || '-' }}</td>
            </tr>
            <tr v-if="applications.length === 0">
              <td colspan="2" class="text-muted">{{ t('no_approved_applications') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()

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

const submitting = ref(false)
const applications = ref([])
const form = ref({
  title: '',
  details: '',
  due_date: '',
  label: ''
})

const loadData = async () => {
  const { data } = await webApi.get('/admin/tasks/workspace')
  applications.value = data.approved_applications || []
}

const submitTask = async () => {
  submitting.value = true
  try {
    await webApi.post('/admin/tasks/broadcast', form.value)
    form.value = { title: '', details: '', due_date: '', label: '' }
    await loadData()
    alert(t('task_broadcast_success'))
  } catch (error) {
    const msg = error?.response?.data?.message || t('task_broadcast_failed')
    alert(msg)
  } finally {
    submitting.value = false
  }
}

const openBoard = (applicationId) => {
  window.location.href = `/applications/${applicationId}/tasks`
}

onMounted(loadData)
</script>

<style scoped>
.admin-general-tasks { padding: 20px 0; }
.card-box { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; }
.clickable-row { cursor: pointer; }
.clickable-row:hover { background: #f8fafc; }
</style>
