<template>
  <div class="student-dashboard">
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div v-else-if="error" class="alert alert-warning rounded-4">
      {{ error }}
      <button class="btn btn-sm btn-outline-primary ms-2" @click="loadDashboard">Retry</button>
    </div>

    <div v-else>
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
          <h3 class="fw-bold mb-1">{{ t('student_dashboard') }}</h3>
          <p class="text-muted mb-0">
            {{ student.name }} <span v-if="student.student_id">| {{ student.student_id }}</span>
          </p>
        </div>
        <router-link class="btn btn-primary rounded-pill px-4" to="/student/browse-programs">
          {{ t('browse_programs') }}
        </router-link>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-sm-6 col-lg-3" v-for="card in cards" :key="card.key">
          <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="small text-muted">{{ card.label }}</div>
                <div class="fw-bold fs-3">{{ card.value }}</div>
              </div>
              <i :class="card.icon" class="fs-4 text-primary"></i>
            </div>
          </div>
        </div>
      </div>

      <div v-if="activeTraining" class="content-card mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h5 class="fw-bold mb-1">{{ t('active_training') }}</h5>
            <div class="text-muted">{{ activeTraining.program_title }} - {{ activeTraining.company_name }}</div>
          </div>
          <button class="btn btn-outline-primary rounded-pill" @click="openBoard(activeTraining.board_url)">
            {{ t('open_tasks_board') }}
          </button>
        </div>
        <div class="progress" style="height: 12px">
          <div class="progress-bar bg-success" :style="{ width: `${activeTraining.progress || 0}%` }"></div>
        </div>
        <div class="small text-muted mt-2">{{ t('progress') }}: {{ activeTraining.progress || 0 }}%</div>
      </div>

      <div class="content-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0">{{ t('application_status') }}</h5>
          <router-link to="/student/application-status" class="btn btn-sm btn-outline-primary rounded-pill">
            {{ t('view_all') }}
          </router-link>
        </div>
        <div v-if="applications.length === 0" class="text-muted">{{ t('no_applications_yet') }}</div>
        <div v-else class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Program</th>
                <th>Company</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="app in applications.slice(0, 5)" :key="app.id">
                <td>{{ app.program_title || '-' }}</td>
                <td>{{ app.company_name || '-' }}</td>
                <td>
                  <span class="badge" :class="statusClass(app.final_status)">{{ app.final_status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="content-card">
        <h5 class="fw-bold mb-3">{{ t('my_tasks_snapshot') }}</h5>
        <div v-if="weeklyTasks.length === 0" class="text-muted">{{ t('no_tasks_available') }}</div>
        <div v-else class="row g-3">
          <div class="col-12" v-for="task in weeklyTasks" :key="task.id">
            <div class="task-row d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold">{{ task.title }}</div>
                <div class="small text-muted">{{ task.description || 'No details' }}</div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge" :class="statusClass(task.status)">{{ task.status }}</span>
                <button class="btn btn-sm btn-outline-primary" @click="openBoard(task.board_url)">Board</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { studentAPI } from '@/services/api/student'
import { useI18n } from '@/composables/useI18n'

const isLoading = ref(false)
const { t } = useI18n()
const error = ref('')
const student = ref({})
const stats = ref({})
const applications = ref([])
const weeklyTasks = ref([])
const activeTraining = ref(null)

const cards = computed(() => [
  { key: 'total', label: t('total_applications'), value: stats.value.total_applications ?? 0, icon: 'bi bi-files' },
  { key: 'pending', label: t('pending_applications'), value: stats.value.pending_applications ?? 0, icon: 'bi bi-hourglass-split' },
  { key: 'approved', label: t('approved_applications'), value: stats.value.approved_applications ?? 0, icon: 'bi bi-check2-circle' },
  { key: 'rejected', label: t('rejected_applications'), value: stats.value.rejected_applications ?? 0, icon: 'bi bi-x-circle' }
])

const statusClass = (status) => {
  if (status === 'approved' || status === 'done') return 'bg-success'
  if (status === 'rejected') return 'bg-danger'
  if (status === 'progress') return 'bg-info'
  return 'bg-warning text-dark'
}

const openBoard = (url) => {
  if (!url) return
  window.location.href = url
}

const loadDashboard = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const res = await studentAPI.getDashboard()
    const payload = res.data?.data || {}
    student.value = payload.student || {}
    stats.value = payload.stats || {}
    applications.value = payload.applications || []
    weeklyTasks.value = payload.weekly_tasks || []
    activeTraining.value = payload.active_training || null
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load dashboard'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadDashboard)
</script>

<style scoped>
.stat-card,
.content-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 18px;
  padding: 20px;
}

.task-row {
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 12px;
}
</style>
