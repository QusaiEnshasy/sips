<template>
  <div class="students-page">
    <div class="mb-4">
      <h2 class="fw-bold mb-1">{{ t('students') }}</h2>
      <p class="text-muted mb-0">{{ t('manage_pending_approved_students') }}</p>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4" v-for="item in statsCards" :key="item.label">
        <div class="card border-0 shadow-sm p-3">
          <div class="text-muted small">{{ item.label }}</div>
          <div class="fs-4 fw-bold">{{ item.value }}</div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm p-3 mb-4">
      <h5 class="fw-bold mb-3">{{ t('pending_students') }}</h5>
      <div v-if="pendingStudents.length === 0" class="text-muted">{{ t('no_pending_students') }}</div>
      <div v-else class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>{{ t('name') }}</th>
              <th>{{ t('email') }}</th>
              <th>{{ t('actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="student in pendingStudents" :key="student.id">
              <td class="fw-bold">{{ student.name }}</td>
              <td>{{ student.email }}</td>
              <td class="d-flex gap-2">
                <button class="btn btn-sm btn-success" @click="approvePending(student.id)">{{ t('approved') }}</button>
                <button class="btn btn-sm btn-danger" @click="rejectPending(student.id)">{{ t('rejected') }}</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card border-0 shadow-sm p-3 mb-4">
      <h5 class="fw-bold mb-3">{{ t('pending') }} {{ t('applications') }}</h5>
      <div v-if="pendingTrainingApplications.length === 0" class="text-muted">{{ t('no_pending_applications') }}</div>
      <div v-else class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>{{ t('student') }}</th>
              <th>{{ t('program') }}</th>
              <th>{{ t('actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="app in pendingTrainingApplications" :key="app.id">
              <td>
                <div class="fw-bold">{{ app.student_name }}</div>
                <small class="text-muted">{{ app.student_email }}</small>
              </td>
              <td>{{ app.program_title || '-' }}</td>
              <td class="d-flex gap-2">
                <button class="btn btn-sm btn-success" @click="approveApplication(app.id)">{{ t('approved') }}</button>
                <button class="btn btn-sm btn-danger" @click="rejectApplication(app.id)">{{ t('rejected') }}</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card border-0 shadow-sm p-3">
      <h5 class="fw-bold mb-3">{{ t('approved_students') }}</h5>
      <div v-if="approvedStudents.length === 0" class="text-muted">{{ t('no_approved_students_yet') }}</div>
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
            <tr v-for="student in approvedStudents" :key="student.application_id || student.id">
              <td>
                <div class="fw-bold">{{ student.name }}</div>
                <small class="text-muted">{{ student.email }}</small>
              </td>
              <td>{{ student.program || '-' }}</td>
              <td style="min-width:220px;">
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

    <div class="card border-0 shadow-sm p-3 mt-4">
      <h5 class="fw-bold mb-3">{{ t('approved_rejected_students') }}</h5>
      <div v-if="studentsStatusTable.length === 0" class="text-muted">{{ t('no_students_found') }}</div>
      <div v-else class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>{{ t('student') }}</th>
              <th>{{ t('email') }}</th>
              <th>{{ t('program') }}</th>
              <th>{{ t('status') }}</th>
              <th>{{ t('actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="student in studentsStatusTable" :key="`status-${student.id}`">
              <td class="fw-bold">{{ student.name }}</td>
              <td>{{ student.email || '-' }}</td>
              <td>{{ student.program || '-' }}</td>
              <td>
                <span class="badge" :class="student.status === 'rejected' ? 'bg-danger' : 'bg-success'">
                  {{ student.status === 'rejected' ? t('rejected') : t('approved') }}
                </span>
              </td>
              <td class="d-flex gap-2">
                <button
                  v-if="student.board_url"
                  class="btn btn-sm btn-primary"
                  @click="openBoard(student.board_url)"
                  :title="t('board')"
                >
                  <i class="bi bi-kanban"></i>
                </button>
                <button
                  class="btn btn-sm btn-outline-danger"
                  @click="deleteStudent(student.id)"
                  :title="t('delete')"
                >
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="rejectModal.open" class="reject-modal-overlay" @click.self="closeRejectModal">
        <div class="reject-modal-card">
          <div class="reject-modal-header">
            <div>
              <h5 class="fw-bold mb-1">{{ t('rejection_reason') }}</h5>
              <p class="text-muted small mb-0">{{ rejectModal.label }}</p>
            </div>
            <button class="btn-close" type="button" @click="closeRejectModal"></button>
          </div>

          <form @submit.prevent="submitRejectReason">
            <label class="form-label fw-bold small mt-3">{{ t('enter_rejection_reason') }}</label>
            <textarea
              v-model="rejectModal.reason"
              class="form-control reject-textarea"
              rows="5"
              :placeholder="t('enter_rejection_reason')"
              required
            ></textarea>

            <div class="reject-actions">
              <button type="button" class="btn btn-light" @click="closeRejectModal">{{ t('cancel') }}</button>
              <button type="submit" class="btn btn-danger" :disabled="rejectModal.submitting || !rejectModal.reason.trim()">
                <span v-if="rejectModal.submitting">{{ t('saving') }}</span>
                <span v-else>{{ t('confirm_reject') }}</span>
              </button>
            </div>
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

const stats = ref({})
const pendingStudents = ref([])
const pendingTrainingApplications = ref([])
const approvedStudents = ref([])
const rejectedStudents = ref([])
const rejectModal = ref({
  open: false,
  type: null,
  targetId: null,
  label: '',
  reason: '',
  submitting: false
})

const statsCards = computed(() => [
  { label: t('total_students'), value: stats.value.total_students ?? 0 },
  { label: t('pending_students'), value: stats.value.total_pending ?? 0 },
  { label: t('approved_students'), value: stats.value.total_approved ?? 0 },
  { label: t('rejected'), value: stats.value.total_rejected ?? 0 }
])

const studentsStatusTable = computed(() => {
  const approved = approvedStudents.value.map((s) => ({ ...s, status: 'approved' }))
  const rejected = rejectedStudents.value.map((s) => ({ ...s, status: 'rejected' }))
  return [...approved, ...rejected]
})

const loadStudents = async () => {
  const res = await supervisorAPI.getStudents()
  const data = res.data?.data || {}
  stats.value = data.stats || {}
  pendingStudents.value = data.pending_students || []
  pendingTrainingApplications.value = data.pending_training_applications || []
  approvedStudents.value = data.approved_students || []
  rejectedStudents.value = data.rejected_students || []
}

const approvePending = async (id) => {
  await supervisorAPI.approveStudentActivation(id)
  await loadStudents()
}

const rejectPending = async (id) => {
  openRejectModal('student', id, t('pending_students'))
}

const approveApplication = async (id) => {
  await supervisorAPI.approveApplication(id)
  await loadStudents()
}

const rejectApplication = async (id) => {
  openRejectModal('application', id, t('applications'))
}

const deleteStudent = async (id) => {
  if (!confirm(t('confirm_delete'))) return
  await supervisorAPI.deleteStudent(id)
  await loadStudents()
}

const goStudent = (id) => router.push(`/supervisor/student/${id}`)
const openBoard = (url) => { if (url) window.location.href = url }

const openRejectModal = (type, id, label) => {
  rejectModal.value = {
    open: true,
    type,
    targetId: id,
    label,
    reason: '',
    submitting: false
  }
}

const closeRejectModal = () => {
  rejectModal.value = {
    open: false,
    type: null,
    targetId: null,
    label: '',
    reason: '',
    submitting: false
  }
}

const submitRejectReason = async () => {
  const reason = rejectModal.value.reason.trim()
  if (!reason) return

  rejectModal.value.submitting = true
  try {
    if (rejectModal.value.type === 'student') {
      await supervisorAPI.rejectStudentActivation(rejectModal.value.targetId, { reason })
    } else {
      await supervisorAPI.rejectApplication(rejectModal.value.targetId, { reason })
    }

    closeRejectModal()
    await loadStudents()
  } finally {
    rejectModal.value.submitting = false
  }
}

onMounted(loadStudents)
</script>

<style scoped>
.students-page { padding: 20px 0; }

.reject-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1200;
  padding: 20px;
}

.reject-modal-card {
  width: min(560px, 100%);
  background: #fff;
  border-radius: 22px;
  padding: 22px;
  box-shadow: 0 25px 70px rgba(15, 23, 42, 0.22);
}

.reject-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.reject-textarea {
  min-height: 140px;
  border-radius: 16px;
  resize: vertical;
}

.reject-textarea:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.12);
}

.reject-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}
</style>
