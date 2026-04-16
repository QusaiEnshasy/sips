<template>
  <div class="supervisor-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-2">{{ t('supervisor_dashboard') }}</h2>
        <p class="text-muted mb-0">{{ t('monitor_students') }}</p>
      </div>
      <button class="btn btn-primary" @click="showCreateModal = true">
        <i class="bi bi-plus-circle me-2"></i>{{ t('create_task') }}
      </button>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-lg-3" v-for="stat in statCards" :key="stat.label">
        <div class="card p-3 h-100 border-0 shadow-sm">
          <div class="text-muted small">{{ stat.label }}</div>
          <div class="fs-4 fw-bold">{{ stat.value }}</div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm p-3 mb-4">
      <h5 class="fw-bold mb-3">{{ t('students_progress') }}</h5>
      <div v-if="students.length === 0" class="text-muted">{{ t('no_approved_students_yet') }}</div>
      <div v-else class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>{{ t('student') }}</th>
              <th>{{ t('program') }}</th>
              <th>{{ t('training_progress') }}</th>
              <th>{{ t('tasks_completed') }}</th>
              <th>{{ t('actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="student in students" :key="student.application_id">
              <td>
                <div class="fw-bold">{{ student.name }}</div>
                <small class="text-muted">{{ student.email }}</small>
              </td>
              <td>{{ student.program }}</td>
              <td style="min-width: 200px;">
                <div class="progress" style="height: 10px;">
                  <div class="progress-bar" :class="student.status === 'at-risk' ? 'bg-warning' : 'bg-success'" :style="{ width: `${student.hoursCompleted}%` }"></div>
                </div>
                <small class="text-muted">{{ student.hoursCompleted }}%</small>
              </td>
              <td>{{ student.tasksCompleted }}/{{ student.tasksTotal }}</td>
              <td class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" @click="goStudent(student.id)">
                  <i class="bi bi-person"></i>
                </button>
                <button class="btn btn-sm btn-primary" @click="openBoard(student.board_url)">
                  <i class="bi bi-kanban"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showCreateModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <h5 class="fw-bold mb-3">{{ t('create_task') }}</h5>
          <form @submit.prevent="createTask">
            <input v-model="newTask.title" class="form-control mb-2" :placeholder="t('task_title')" required>
            <textarea v-model="newTask.details" class="form-control mb-2" rows="3" :placeholder="t('details')"></textarea>
            <input v-model="newTask.due_date" type="date" class="form-control mb-2">
            <select v-model="newTask.label" class="form-select mb-3">
              <option value="">{{ t('label') }}</option>
              <option value="red">{{ t('urgent') }}</option>
              <option value="green">{{ t('normal') }}</option>
              <option value="blue">{{ t('feature') }}</option>
            </select>
            <button type="submit" class="btn btn-primary w-100" :disabled="isCreating">
              {{ isCreating ? t('saving') : t('create_task') }}
            </button>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { supervisorAPI } from '@/services/api/supervisor'

const { t } = useI18n()
const router = useRouter()

const dashboard = ref(null)
const students = ref([])
const showCreateModal = ref(false)
const isCreating = ref(false)
const newTask = ref({ title: '', details: '', due_date: '', label: '' })

const statCards = computed(() => {
  const s = dashboard.value?.quick_stats || {}
  return [
    { label: t('total_students'), value: s.total_students ?? 0 },
    { label: t('pending_students'), value: s.pending_students ?? 0 },
    { label: t('active_students'), value: s.active_students ?? 0 },
    { label: t('done_tasks'), value: s.done_tasks ?? 0 }
  ]
})

const loadDashboard = async () => {
  const res = await supervisorAPI.getDashboard()
  dashboard.value = res.data?.data || {}
  students.value = dashboard.value.students || []
}

const createTask = async () => {
  isCreating.value = true
  try {
    await supervisorAPI.broadcastTask(newTask.value)
    closeModal()
    await loadDashboard()
  } catch (error) {
    alert(error.response?.data?.message || t('error_creating_task'))
  } finally {
    isCreating.value = false
  }
}

const closeModal = () => {
  showCreateModal.value = false
  newTask.value = { title: '', details: '', due_date: '', label: '' }
}

const goStudent = (id) => router.push(`/supervisor/student/${id}`)
const openBoard = (url) => { if (url) window.location.href = url }

onMounted(loadDashboard)
</script>

<style scoped>
.supervisor-dashboard { padding: 20px 0; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center; z-index: 1200; }
.modal-content { background: #fff; width: min(520px, 92vw); border-radius: 16px; padding: 20px; }
</style>
