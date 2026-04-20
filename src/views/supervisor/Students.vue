<template>
  <div class="students-page">
    <div class="mb-4">
      <h2 class="fw-bold mb-1">{{ t('students') }}</h2>
      <p class="text-muted mb-0">{{ t('manage_pending_approved_students') }}</p>
    </div>

    <div class="jisr-entry-card mb-4 jisr-entry-card-strong">
      <div>
        <h5 class="fw-bold mb-1">تقييم برنامج الجسر</h5>
        <p class="text-muted mb-0">من هنا تراجع حلول الطلاب في برنامج الجسر وتقيّمها.</p>
      </div>
      <button class="btn btn-primary" @click="goJisrReviews">
        <i class="bi bi-clipboard-check me-2"></i>
        فتح تقييم برنامج الجسر
      </button>
    </div>

    <div class="card border-0 shadow-sm p-3 mb-4 jisr-review-card-strong">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
          <h5 class="fw-bold mb-1">تقييم حلول برنامج الجسر</h5>
          <p class="text-muted mb-0">يمكنك من هنا مراجعة حلول الطلاب في الجسر واعتمادها أو إرجاعها مباشرة.</p>
        </div>
        <span class="badge bg-warning text-dark fs-6">بانتظار التقييم: {{ pendingJisrCount }}</span>
      </div>

      <div v-if="jisrReviews.length === 0" class="text-muted">لا توجد حلول مرسلة في برنامج الجسر حاليًا.</div>
      <div v-else class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>الطالب</th>
              <th>المهمة</th>
              <th>الحالة</th>
              <th>الحل</th>
              <th>التقييم</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="submission in jisrReviews" :key="submission.id">
              <td>
                <div class="fw-bold">{{ submission.student?.name || '-' }}</div>
                <small class="text-muted">{{ submission.student?.email || '-' }}</small>
              </td>
              <td>
                <div class="fw-bold">{{ submission.task?.title || '-' }}</div>
                <small class="text-muted">#{{ submission.task?.order_number || '-' }}</small>
              </td>
              <td>
                <span class="badge" :class="jisrStatusBadge(submission.status)">
                  {{ jisrStatusText(submission.status) }}
                </span>
              </td>
              <td style="max-width:320px;">
                <div class="small text-truncate">{{ submission.content || 'لا يوجد نص مرفق.' }}</div>
                <div v-if="submission.attachments?.length" class="mt-1">
                  <a
                    v-for="(attachment, index) in submission.attachments"
                    :key="index"
                    :href="attachment.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="attachment-chip"
                  >
                    {{ attachment.name }}
                  </a>
                </div>
              </td>
              <td class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-success" @click="openJisrReviewModal(submission, 'accepted')">اعتماد</button>
                <button class="btn btn-sm btn-danger" @click="openJisrReviewModal(submission, 'rejected')">إعادة للطالب</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
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
              <td>
                <div class="fw-bold d-flex align-items-center gap-2 flex-wrap">
                  <span>{{ student.name }}</span>
                  <span v-if="student.is_in_jisr" class="badge jisr-badge">برنامج الجسر</span>
                </div>
              </td>
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
              <th>حالة الشركة</th>
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
              <td>
                <span class="badge" :class="companyStatusBadge(app.company_status)">
                  {{ companyStatusText(app.company_status) }}
                </span>
              </td>
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
                <div class="fw-bold d-flex align-items-center gap-2 flex-wrap">
                  <span>{{ student.name }}</span>
                  <span v-if="student.is_in_jisr" class="badge jisr-badge">برنامج الجسر</span>
                </div>
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
              <td>
                <div class="fw-bold d-flex align-items-center gap-2 flex-wrap">
                  <span>{{ student.name }}</span>
                  <span v-if="student.is_in_jisr" class="badge jisr-badge">برنامج الجسر</span>
                </div>
              </td>
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

    <Teleport to="body">
      <div v-if="jisrReviewModal.open" class="reject-modal-overlay" @click.self="closeJisrReviewModal">
        <div class="reject-modal-card">
          <div class="reject-modal-header">
            <div>
              <h5 class="fw-bold mb-1">{{ jisrReviewModal.action === 'accepted' ? 'اعتماد حل الجسر' : 'إرجاع حل الجسر' }}</h5>
              <p class="text-muted small mb-0">{{ jisrReviewModal.studentName }} - {{ jisrReviewModal.taskTitle }}</p>
            </div>
            <button class="btn-close" type="button" @click="closeJisrReviewModal"></button>
          </div>

          <form @submit.prevent="submitJisrReview">
            <div v-if="jisrReviewModal.action === 'accepted'" class="mt-3">
              <label class="form-label fw-bold small">الدرجة</label>
              <input
                v-model.number="jisrReviewModal.score"
                type="number"
                min="0"
                :max="jisrReviewModal.maxScore"
                class="form-control"
                required
              >
            </div>

            <label class="form-label fw-bold small mt-3">ملاحظات المشرف</label>
            <textarea
              v-model="jisrReviewModal.feedback"
              class="form-control reject-textarea"
              rows="5"
              placeholder="اكتب ملاحظتك للطالب هنا"
              required
            ></textarea>

            <div class="reject-actions">
              <button type="button" class="btn btn-light" @click="closeJisrReviewModal">إلغاء</button>
              <button type="submit" class="btn btn-primary" :disabled="jisrReviewModal.submitting || !jisrReviewModal.feedback.trim()">
                <span v-if="jisrReviewModal.submitting">جاري الحفظ...</span>
                <span v-else>حفظ التقييم</span>
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
const jisrReviews = ref([])
const rejectModal = ref({
  open: false,
  type: null,
  targetId: null,
  label: '',
  reason: '',
  submitting: false
})
const jisrReviewModal = ref({
  open: false,
  submissionId: null,
  action: 'accepted',
  studentName: '',
  taskTitle: '',
  maxScore: 100,
  score: 100,
  feedback: '',
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
const pendingJisrCount = computed(() => jisrReviews.value.filter((item) => item.status === 'pending_review').length)

const loadStudents = async () => {
  const res = await supervisorAPI.getStudents()
  const data = res.data?.data || {}
  stats.value = data.stats || {}
  pendingStudents.value = data.pending_students || []
  pendingTrainingApplications.value = data.pending_training_applications || []
  approvedStudents.value = data.approved_students || []
  rejectedStudents.value = data.rejected_students || []
}

const loadJisrReviews = async () => {
  const res = await supervisorAPI.getJisrReviews()
  jisrReviews.value = res.data?.data?.submissions || []
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
const goJisrReviews = () => router.push('/supervisor/jisr-reviews')
const openBoard = (url) => { if (url) window.location.href = url }

const jisrStatusText = (status) => ({
  pending_review: 'بانتظار التقييم',
  accepted: 'تم الاعتماد',
  rejected: 'مطلوب تعديل'
}[status] || status)

const jisrStatusBadge = (status) => ({
  pending_review: 'bg-warning text-dark',
  accepted: 'bg-success',
  rejected: 'bg-danger'
}[status] || 'bg-secondary')

const companyStatusText = (status) => ({
  approved: 'مقبول',
  rejected: 'مرفوض',
  pending: 'بانتظار الشركة'
}[status] || 'بانتظار الشركة')

const companyStatusBadge = (status) => ({
  approved: 'bg-success',
  rejected: 'bg-danger',
  pending: 'bg-warning text-dark'
}[status] || 'bg-warning text-dark')

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

const openJisrReviewModal = (submission, action) => {
  jisrReviewModal.value = {
    open: true,
    submissionId: submission.id,
    action,
    studentName: submission.student?.name || '',
    taskTitle: submission.task?.title || '',
    maxScore: submission.task?.max_score || 100,
    score: submission.score ?? submission.task?.max_score ?? 100,
    feedback: action === 'accepted'
      ? (submission.feedback || 'أحسنت، تم اعتماد الحل.')
      : (submission.feedback || ''),
    submitting: false
  }
}

const closeJisrReviewModal = () => {
  jisrReviewModal.value = {
    open: false,
    submissionId: null,
    action: 'accepted',
    studentName: '',
    taskTitle: '',
    maxScore: 100,
    score: 100,
    feedback: '',
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

const submitJisrReview = async () => {
  if (!jisrReviewModal.value.submissionId) return

  jisrReviewModal.value.submitting = true
  try {
    await supervisorAPI.reviewJisrSubmission(jisrReviewModal.value.submissionId, {
      status: jisrReviewModal.value.action,
      score: jisrReviewModal.value.score,
      feedback: jisrReviewModal.value.feedback
    })
    closeJisrReviewModal()
    await loadJisrReviews()
  } finally {
    jisrReviewModal.value.submitting = false
  }
}

onMounted(async () => {
  await loadStudents()
  await loadJisrReviews()
})
</script>

<style scoped>
.students-page { padding: 20px 0; }

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

.jisr-entry-card-strong {
  background: linear-gradient(135deg, #fff7cc, #fff1a8);
  border: 2px solid #f59e0b;
  box-shadow: 0 12px 30px rgba(245, 158, 11, 0.15);
}

.jisr-entry-card-strong h5,
.jisr-entry-card-strong p {
  color: #7c2d12 !important;
}

.jisr-review-card-strong {
  border: 2px solid #facc15 !important;
  box-shadow: 0 12px 30px rgba(250, 204, 21, 0.12);
}

.jisr-badge {
  background: #ede9fe;
  color: #6d28d9;
  border: 1px solid #c4b5fd;
  font-size: 12px;
  font-weight: 700;
}

.attachment-chip {
  display: inline-block;
  margin-inline-end: 6px;
  margin-top: 4px;
  padding: 4px 10px;
  border-radius: 999px;
  background: #ede9fe;
  color: #6d28d9;
  text-decoration: none;
  font-size: 12px;
}

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
