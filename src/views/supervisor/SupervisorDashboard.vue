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

    <div v-if="supervisorCode" class="supervisor-code-card mb-4">
      <div class="code-copy-icon">
        <i class="bi bi-key"></i>
      </div>
      <div class="flex-grow-1">
        <div class="text-muted small mb-1">كود المشرف للطلاب</div>
        <div class="code-value">{{ supervisorCode }}</div>
        <small class="text-muted">أعطِ هذا الكود للطلاب حتى يرتبطوا بحسابك عند التسجيل.</small>
      </div>
      <button class="btn btn-outline-primary" @click="copySupervisorCode">
        <i class="bi bi-copy me-1"></i>
        نسخ الكود
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

    <div class="jisr-entry-card mb-4">
      <div>
        <h5 class="fw-bold mb-1">تقييم برنامج الجسر</h5>
        <p class="text-muted mb-0">افتح صفحة تقييم حلول طلاب الجسر واعتمادها أو إرجاعها.</p>
      </div>
      <button class="btn btn-primary" @click="goJisrReviews">
        <i class="bi bi-clipboard-check me-2"></i>
        فتح تقييم برنامج الجسر
      </button>
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
                <div class="fw-bold d-flex align-items-center gap-2 flex-wrap">
                  <span>{{ student.name }}</span>
                  <span v-if="student.is_in_jisr" class="badge jisr-badge">برنامج الجسر</span>
                </div>
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
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()

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

const supervisorCode = computed(() => (
  dashboard.value?.supervisor_code ||
  authStore.user?.supervisor_code ||
  ''
))

const loadDashboard = async () => {
  const res = await supervisorAPI.getDashboard()
  dashboard.value = res.data?.data || {}
  students.value = dashboard.value.students || []
}

const copySupervisorCode = async () => {
  if (!supervisorCode.value) return

  try {
    await navigator.clipboard.writeText(supervisorCode.value)
    alert('تم نسخ كود المشرف')
  } catch (error) {
    alert(supervisorCode.value)
  }
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
const goJisrReviews = () => router.push('/supervisor/jisr-reviews')
const openBoard = (url) => { if (url) window.location.href = url }

onMounted(loadDashboard)
</script>

<style scoped>
.supervisor-dashboard { padding: 20px 0; }
.supervisor-code-card {
  align-items: center;
  background: linear-gradient(135deg, #eef6ff, #ffffff);
  border: 1px solid #cfe4ff;
  border-radius: 20px;
  box-shadow: 0 14px 35px rgba(15, 23, 42, .06);
  display: flex;
  gap: 16px;
  padding: 18px 20px;
}
.code-copy-icon {
  align-items: center;
  background: #2563eb;
  border-radius: 16px;
  color: #fff;
  display: inline-flex;
  flex-shrink: 0;
  font-size: 24px;
  height: 54px;
  justify-content: center;
  width: 54px;
}
.code-value {
  color: #111827;
  font-size: 26px;
  font-weight: 900;
  letter-spacing: .08em;
}
.jisr-entry-card {
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.08), rgba(124, 58, 237, 0.02));
  border: 1px solid #d8ccff;
  border-radius: 20px;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.jisr-badge {
  background: #ede9fe;
  color: #6d28d9;
  border: 1px solid #c4b5fd;
  font-size: 12px;
  font-weight: 700;
}
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center; z-index: 1200; }
.modal-content { background: #fff; width: min(520px, 92vw); border-radius: 16px; padding: 20px; }
@media (max-width: 768px) {
  .supervisor-code-card {
    align-items: flex-start;
    flex-direction: column;
  }
  .supervisor-code-card .btn {
    width: 100%;
  }
}
</style>
