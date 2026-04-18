<template>
  <div class="jisr-reviews-page">
    <div class="page-header">
      <div>
        <h2 class="fw-bold mb-2">تقييم حلول برنامج الجسر</h2>
        <p class="text-muted mb-0">راجع إجابات الطلاب في برنامج الجسر، ثم اعتمد الحل أو اطلب إعادة المحاولة مع ملاحظاتك.</p>
      </div>
    </div>

    <div v-if="isLoading" class="loading-state">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted mt-3 mb-0">جاري تحميل الحلول...</p>
    </div>

    <template v-else>
      <div class="stats-grid">
        <div class="stat-card">
          <span class="stat-label">إجمالي التسليمات</span>
          <strong>{{ stats.total_submissions }}</strong>
        </div>
        <div class="stat-card warning">
          <span class="stat-label">بانتظار التقييم</span>
          <strong>{{ stats.pending_reviews }}</strong>
        </div>
        <div class="stat-card success">
          <span class="stat-label">تم قبولها</span>
          <strong>{{ stats.accepted_submissions }}</strong>
        </div>
        <div class="stat-card danger">
          <span class="stat-label">تحتاج إعادة</span>
          <strong>{{ stats.rejected_submissions }}</strong>
        </div>
      </div>

      <div v-if="submissions.length === 0" class="empty-state">
        <i class="bi bi-inbox"></i>
        <h5 class="fw-bold mt-3">لا توجد حلول مرسلة حاليًا</h5>
        <p class="text-muted mb-0">بمجرد أن يرسل الطالب حله في برنامج الجسر سيظهر هنا للمراجعة.</p>
      </div>

      <div v-else class="submissions-grid">
        <article v-for="submission in submissions" :key="submission.id" class="submission-card" :class="statusClass(submission.status)">
          <div class="submission-top">
            <div>
              <div class="student-name">{{ submission.student.name }}</div>
              <div class="student-meta">
                <span>{{ submission.student.email }}</span>
                <span v-if="submission.student.university_id">| {{ submission.student.university_id }}</span>
              </div>
            </div>
            <span class="status-pill" :class="badgeClass(submission.status)">
              {{ statusText(submission.status) }}
            </span>
          </div>

          <div class="task-box">
            <div class="task-title">المهمة #{{ submission.task.order_number }}: {{ submission.task.title }}</div>
            <p class="task-description mb-2">{{ submission.task.description }}</p>
            <small class="text-muted">الدرجة القصوى: {{ submission.task.max_score }}</small>
          </div>

          <div class="answer-box">
            <div class="section-title">حل الطالب</div>
            <p class="answer-text">{{ submission.content || 'لا يوجد نص مرفق.' }}</p>
          </div>

          <div v-if="submission.attachments?.length" class="attachments-box">
            <div class="attachments-header">
              <div class="section-title mb-0">المرفقات</div>
              <span class="attachments-count">{{ submission.attachments.length }} ملف</span>
            </div>
            <div class="attachments-list">
              <div
                v-for="(attachment, index) in submission.attachments"
                :key="index"
                class="attachment-item"
              >
                <div class="attachment-item__info">
                  <div class="attachment-item__icon">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                  </div>
                  <div>
                    <div class="attachment-item__name">{{ attachment.name }}</div>
                    <div class="attachment-item__meta">تم رفعه مع حل الطالب</div>
                  </div>
                </div>

                <div class="attachment-item__actions">
                  <a
                    v-if="attachment.view_url"
                    :href="attachment.view_url"
                    class="attachment-btn attachment-btn--view"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    <i class="bi bi-eye"></i>
                    <span>فتح</span>
                  </a>
                  <a
                    v-if="attachment.download_url"
                    :href="attachment.download_url"
                    class="attachment-btn attachment-btn--download"
                  >
                    <i class="bi bi-download"></i>
                    <span>تنزيل</span>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="submission-footer">
            <div class="review-meta">
              <span>أُرسل: {{ formatDate(submission.submitted_at) }}</span>
              <span v-if="submission.feedback">| الملاحظة الحالية: {{ submission.feedback }}</span>
            </div>

            <div class="actions-row">
              <button class="btn-review" @click="openReview(submission, 'accepted')">
                <i class="bi bi-check-circle"></i>
                اعتماد الحل
              </button>
              <button class="btn-review danger" @click="openReview(submission, 'rejected')">
                <i class="bi bi-arrow-counterclockwise"></i>
                طلب إعادة
              </button>
            </div>
          </div>
        </article>
      </div>
    </template>

    <Teleport to="body">
      <div v-if="showReviewModal" class="modal-overlay" @click.self="closeModal">
        <div class="review-modal">
          <div class="modal-header">
            <div>
              <h5 class="fw-bold mb-1">{{ selectedStatus === 'accepted' ? 'اعتماد الحل' : 'إرجاع الحل للطالب' }}</h5>
              <p class="text-muted small mb-0">{{ selectedSubmission?.student?.name }} - {{ selectedSubmission?.task?.title }}</p>
            </div>
            <button class="btn-icon" @click="closeModal">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form class="modal-body" @submit.prevent="submitReview">
            <div v-if="selectedStatus === 'accepted'" class="mb-3">
              <label class="form-label fw-bold">الدرجة</label>
              <input
                v-model.number="reviewForm.score"
                type="number"
                min="0"
                :max="selectedSubmission?.task?.max_score || 100"
                class="form-control"
                required
              >
              <small class="text-muted">الحد الأعلى {{ selectedSubmission?.task?.max_score || 100 }}</small>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">ملاحظات المشرف</label>
              <textarea
                v-model="reviewForm.feedback"
                class="form-control"
                rows="5"
                :placeholder="selectedStatus === 'accepted' ? 'اكتب ملاحظتك للطالب بعد الاعتماد...' : 'اشرح للطالب ما الذي يحتاج تعديله...'"
                required
              ></textarea>
            </div>

            <button class="btn-submit-review" type="submit" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
              {{ isSubmitting ? 'جاري الحفظ...' : 'حفظ التقييم' }}
            </button>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { supervisorAPI } from '@/services/api/supervisor'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()

const isLoading = ref(false)
const isSubmitting = ref(false)
const submissions = ref([])
const stats = ref({
  total_submissions: 0,
  pending_reviews: 0,
  accepted_submissions: 0,
  rejected_submissions: 0
})

const showReviewModal = ref(false)
const selectedSubmission = ref(null)
const selectedStatus = ref('accepted')
const reviewForm = ref({
  score: null,
  feedback: ''
})

const loadReviews = async () => {
  isLoading.value = true
  try {
    const response = await supervisorAPI.getJisrReviews()
    stats.value = response.data?.data?.stats || stats.value
    const list = response.data?.data?.submissions || []
    submissions.value = [
      ...list.filter((item) => item.status === 'pending_review'),
      ...list.filter((item) => item.status !== 'pending_review')
    ]
  } catch (error) {
    console.error('Failed to load Jisr reviews:', error)
    toastStore.addToast({ type: 'error', message: 'تعذر تحميل حلول برنامج الجسر.' })
  } finally {
    isLoading.value = false
  }
}

const openReview = (submission, status) => {
  selectedSubmission.value = submission
  selectedStatus.value = status
  reviewForm.value = {
    score: status === 'accepted'
      ? (submission.score ?? submission.task?.max_score ?? 100)
      : (submission.score ?? null),
    feedback: status === 'accepted'
      ? (submission.feedback || 'أحسنت، تم اعتماد الحل.')
      : (submission.feedback || '')
  }
  showReviewModal.value = true
}

const closeModal = () => {
  showReviewModal.value = false
  selectedSubmission.value = null
  selectedStatus.value = 'accepted'
  reviewForm.value = { score: null, feedback: '' }
}

const submitReview = async () => {
  if (!selectedSubmission.value) return

  isSubmitting.value = true
  try {
    await supervisorAPI.reviewJisrSubmission(selectedSubmission.value.id, {
      status: selectedStatus.value,
      score: selectedStatus.value === 'accepted' ? reviewForm.value.score : reviewForm.value.score,
      feedback: reviewForm.value.feedback
    })

    toastStore.addToast({
      type: 'success',
      message: selectedStatus.value === 'accepted' ? 'تم اعتماد الحل بنجاح.' : 'تم إرجاع الحل للطالب مع الملاحظات.'
    })

    closeModal()
    await loadReviews()
  } catch (error) {
    console.error('Failed to review submission:', error)
    toastStore.addToast({
      type: 'error',
      message: error.response?.data?.message || 'تعذر حفظ التقييم.'
    })
  } finally {
    isSubmitting.value = false
  }
}

const formatDate = (value) => {
  if (!value) return 'غير محدد'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'غير محدد' : date.toLocaleString('ar-EG')
}

const statusText = (status) => ({
  pending_review: 'بانتظار التقييم',
  accepted: 'تم الاعتماد',
  rejected: 'مرفوض ويحتاج تعديل'
}[status] || 'جديد')

const statusClass = (status) => ({
  pending_review: 'pending',
  accepted: 'accepted',
  rejected: 'rejected'
}[status] || 'pending')

const badgeClass = (status) => ({
  pending_review: 'warning',
  accepted: 'success',
  rejected: 'danger'
}[status] || 'warning')

onMounted(() => {
  loadReviews()
})
</script>

<style scoped>
.jisr-reviews-page {
  padding: 20px 0;
}

.page-header {
  margin-bottom: 24px;
}

.loading-state,
.empty-state {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 24px;
  padding: 48px 24px;
  text-align: center;
}

.empty-state i {
  font-size: 42px;
  color: var(--accent);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 18px;
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.stat-card strong {
  font-size: 30px;
}

.stat-card.warning strong {
  color: #d97706;
}

.stat-card.success strong {
  color: #16a34a;
}

.stat-card.danger strong {
  color: #dc2626;
}

.stat-label {
  color: var(--text-muted);
  font-size: 14px;
}

.submissions-grid {
  display: grid;
  gap: 18px;
}

.submission-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 22px;
  padding: 22px;
}

.submission-card.pending {
  border-right: 4px solid #f59e0b;
}

.submission-card.accepted {
  border-right: 4px solid #22c55e;
}

.submission-card.rejected {
  border-right: 4px solid #ef4444;
}

.submission-top,
.actions-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.student-name,
.task-title {
  font-weight: 700;
  color: var(--text-dark);
}

.student-meta,
.review-meta,
.task-description,
.answer-text {
  color: var(--text-muted);
}

.task-box,
.answer-box,
.attachments-box {
  margin-top: 16px;
  padding: 16px;
  border-radius: 16px;
  background: var(--main-bg);
  border: 1px solid var(--border-color);
}

.section-title {
  font-weight: 700;
  margin-bottom: 10px;
}

.attachments-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.attachments-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: #f8fafc;
  color: #475569;
  border: 1px solid var(--border-color);
  font-size: 12px;
  font-weight: 700;
}

.status-pill {
  padding: 8px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
}

.status-pill.warning {
  background: #fff7ed;
  color: #c2410c;
}

.status-pill.success {
  background: #f0fdf4;
  color: #15803d;
}

.status-pill.danger {
  background: #fef2f2;
  color: #b91c1c;
}

.attachments-list {
  display: grid;
  gap: 12px;
}

.attachment-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  border-radius: 16px;
  border: 1px solid var(--border-color);
  background: #fff;
  padding: 14px 16px;
}

.attachment-item__info {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  flex: 1;
}

.attachment-item__icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(124, 58, 237, 0.08);
  color: var(--accent);
  font-size: 18px;
  flex-shrink: 0;
}

.attachment-item__name {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-color);
  word-break: break-word;
}

.attachment-item__meta {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

.attachment-item__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.attachment-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  border-radius: 999px;
  padding: 8px 14px;
  font-size: 13px;
  font-weight: 700;
}

.attachment-btn--view {
  color: var(--accent);
  background: rgba(124, 58, 237, 0.08);
}

.attachment-btn--download {
  color: #166534;
  background: #dcfce7;
}

.submission-footer {
  margin-top: 18px;
}

.btn-review,
.btn-submit-review,
.btn-icon {
  border: none;
  cursor: pointer;
}

.btn-review {
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  color: #fff;
  border-radius: 12px;
  padding: 10px 16px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-review.danger {
  background: #fee2e2;
  color: #b91c1c;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1200;
  padding: 20px;
}

.review-modal {
  width: 100%;
  max-width: 640px;
  background: var(--card-bg);
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 30px 60px rgba(15, 23, 42, 0.2);
}

.modal-header,
.modal-body {
  padding: 22px;
}

.modal-header {
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: var(--main-bg);
  border: 1px solid var(--border-color);
}

.btn-submit-review {
  width: 100%;
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  color: #fff;
  border-radius: 12px;
  padding: 12px 16px;
  font-weight: 700;
}

@media (max-width: 992px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .attachment-item {
    flex-direction: column;
    align-items: stretch;
  }

  .attachment-item__actions {
    justify-content: stretch;
  }

  .attachment-btn {
    justify-content: center;
    width: 100%;
  }
}
</style>
