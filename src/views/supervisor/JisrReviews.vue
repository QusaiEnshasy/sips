<template>
  <div class="jisr-reviews-page" :dir="isRTL ? 'rtl' : 'ltr'">
    <div class="page-header">
      <div>
        <h2 class="fw-bold mb-2">{{ t('supervisor_jisr_reviews_title') }}</h2>
        <p class="text-muted mb-0">{{ t('supervisor_jisr_reviews_subtitle') }}</p>
      </div>
    </div>

    <div v-if="isLoading" class="loading-state">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted mt-3 mb-0">{{ t('loading_jisr_reviews') }}</p>
    </div>

    <template v-else>
      <div class="stats-grid">
        <div class="stat-card">
          <span class="stat-label">{{ t('total_submissions') }}</span>
          <strong>{{ stats.total_submissions }}</strong>
        </div>
        <div class="stat-card warning">
          <span class="stat-label">{{ t('pending_reviews') }}</span>
          <strong>{{ stats.pending_reviews }}</strong>
        </div>
        <div class="stat-card success">
          <span class="stat-label">{{ t('accepted_submissions') }}</span>
          <strong>{{ stats.accepted_submissions }}</strong>
        </div>
        <div class="stat-card danger">
          <span class="stat-label">{{ t('rejected_submissions') }}</span>
          <strong>{{ stats.rejected_submissions }}</strong>
        </div>
      </div>

      <div v-if="studentCards.length === 0" class="empty-state">
        <i class="bi bi-inbox"></i>
        <h5 class="fw-bold mt-3">{{ t('no_jisr_submissions') }}</h5>
        <p class="text-muted mb-0">{{ t('no_jisr_submissions_desc') }}</p>
      </div>

      <div v-else class="student-cards">
        <article
          v-for="card in studentCards"
          :key="card.student.id"
          class="student-card"
          :class="statusClass(card.status)"
        >
          <div class="student-card__top">
            <div>
              <div class="student-name">{{ card.student.name }}</div>
              <div class="student-meta">
                <span>{{ card.student.email }}</span>
                <span v-if="card.student.university_id">| {{ card.student.university_id }}</span>
              </div>
            </div>
            <span class="status-pill" :class="badgeClass(card.status)">
              {{ statusText(card.status) }}
            </span>
          </div>

          <div class="student-summary">
            <span>{{ t('submitted_tasks') }}: {{ card.submissions.length }}</span>
            <span>{{ t('uploaded_files') }}: {{ card.attachmentsCount }}</span>
            <span>{{ t('approved_count') }}: {{ card.acceptedCount }}/{{ card.totalTasks }}</span>
          </div>

          <div class="task-list">
            <section
              v-for="submission in card.submissions"
              :key="submission.id"
              class="task-row"
              :class="statusClass(submission.status)"
            >
              <div class="task-row__top">
                <div>
                  <div class="task-title">
                    {{ t('task') }} #{{ submission.task?.order_number }}: {{ submission.task?.title }}
                  </div>
                  <p class="task-description mb-2">{{ submission.task?.description }}</p>
                  <small class="text-muted">{{ t('max_score') }}: {{ submission.task?.max_score || 100 }}</small>
                </div>
                <span class="status-pill" :class="badgeClass(submission.status)">
                  {{ statusText(submission.status) }}
                </span>
              </div>

              <div class="answer-box">
                <div class="section-title">{{ t('solution') }}</div>
                <p class="answer-text">{{ submission.content || t('no_text_solution') }}</p>
              </div>

              <div v-if="submission.attachments?.length" class="attachments-box">
                <div class="attachments-header">
                  <div class="section-title mb-0">{{ t('solution_files') }}</div>
                  <span class="attachments-count">{{ submission.attachments.length }} {{ t('file') }}</span>
                </div>

                <div class="attachments-list">
                  <div
                    v-for="(attachment, index) in submission.attachments"
                    :key="`${submission.id}-${index}`"
                    class="attachment-item"
                  >
                    <div class="attachment-item__info">
                      <div class="attachment-item__icon">
                        <i class="bi bi-file-earmark-arrow-up"></i>
                      </div>
                      <div>
                        <div class="attachment-item__name">{{ attachment.name }}</div>
                        <div class="attachment-item__meta">
                          {{ t('uploaded_with_task') }} #{{ submission.task?.order_number }}
                        </div>
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
                        <span>{{ t('open') }}</span>
                      </a>
                      <a
                        v-if="attachment.download_url"
                        :href="attachment.download_url"
                        class="attachment-btn attachment-btn--download"
                      >
                        <i class="bi bi-download"></i>
                        <span>{{ t('download') }}</span>
                      </a>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="attachments-box muted">
                {{ t('no_files_for_task') }}
              </div>

              <div class="submission-footer">
                <div class="review-meta">
                  <span>{{ t('submitted_at') }}: {{ formatDate(submission.submitted_at) }}</span>
                  <span v-if="submission.feedback">| {{ t('current_feedback') }}: {{ submission.feedback }}</span>
                </div>

                <div class="actions-row">
                  <button v-if="submission.status !== 'accepted'" class="btn-review" @click="openReview(submission, 'accepted')">
                    <i class="bi bi-check-circle"></i>
                    {{ t('approve_task') }}
                  </button>
                  <button class="btn-review danger" @click="openReview(submission, 'rejected')">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    {{ t('return_task') }}
                  </button>
                </div>
              </div>
            </section>
          </div>
        </article>
      </div>
    </template>

    <Teleport to="body">
      <div v-if="showReviewModal" class="modal-overlay" @click.self="closeModal">
        <div class="review-modal" :dir="isRTL ? 'rtl' : 'ltr'">
          <div class="modal-header">
            <div>
              <h5 class="fw-bold mb-1">
                {{ selectedStatus === 'accepted' ? t('approve_solution') : t('return_solution_to_student') }}
              </h5>
              <p class="text-muted small mb-0">
                {{ selectedSubmission?.student?.name }} - {{ selectedSubmission?.task?.title }}
              </p>
            </div>
            <button class="btn-icon" @click="closeModal">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form class="modal-body" @submit.prevent="submitReview">
            <div v-if="selectedStatus === 'accepted'" class="mb-3">
              <label class="form-label fw-bold">{{ t('score') }}</label>
              <input
                v-model.number="reviewForm.score"
                type="number"
                min="0"
                :max="selectedSubmission?.task?.max_score || 100"
                class="form-control"
                required
              >
              <small class="text-muted">{{ t('maximum') }} {{ selectedSubmission?.task?.max_score || 100 }}</small>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">{{ t('supervisor_feedback') }}</label>
              <textarea
                v-model="reviewForm.feedback"
                class="form-control"
                rows="5"
                :placeholder="selectedStatus === 'accepted' ? t('accepted_feedback_placeholder') : t('rejected_feedback_placeholder')"
                required
              ></textarea>
            </div>

            <button class="btn-submit-review" type="submit" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
              {{ isSubmitting ? t('saving_review') : t('save_review') }}
            </button>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { supervisorAPI } from '@/services/api/supervisor'
import { useToastStore } from '@/stores/toast'
import { useI18n } from '@/composables/useI18n'

const toastStore = useToastStore()
const { t, currentLang, isRTL } = useI18n()

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

const studentCards = computed(() => {
  const grouped = new Map()

  for (const submission of submissions.value) {
    const studentId = submission.student?.id || `unknown-${submission.id}`
    if (!grouped.has(studentId)) {
      grouped.set(studentId, {
        student: submission.student || { id: studentId, name: t('unknown_student'), email: '' },
        submissions: [],
        attachmentsCount: 0,
        acceptedCount: submission.student?.accepted_tasks || 0,
        totalTasks: submission.student?.total_tasks || 0,
        status: 'accepted'
      })
    }

    const card = grouped.get(studentId)
    card.submissions.push(submission)
    card.attachmentsCount += submission.attachments?.length || 0
  }

  return Array.from(grouped.values()).map((card) => {
    card.submissions.sort((a, b) => {
      const aOrder = a.task?.order_number || 999
      const bOrder = b.task?.order_number || 999
      return aOrder - bOrder
    })

    if (card.submissions.some((item) => item.status === 'pending_review')) {
      card.status = 'pending_review'
    } else if (card.submissions.some((item) => item.status === 'rejected')) {
      card.status = 'rejected'
    } else {
      card.status = 'accepted'
    }

    return card
  }).sort((a, b) => statusWeight(a.status) - statusWeight(b.status))
})

const loadReviews = async () => {
  isLoading.value = true
  try {
    const response = await supervisorAPI.getJisrReviews()
    stats.value = response.data?.data?.stats || stats.value
    const list = response.data?.data?.submissions || []
    submissions.value = [
      ...list.filter((item) => item.status === 'pending_review'),
      ...list.filter((item) => item.status === 'rejected'),
      ...list.filter((item) => !['pending_review', 'rejected'].includes(item.status))
    ]
  } catch (error) {
    console.error('Failed to load Jisr reviews:', error)
    toastStore.addToast({ type: 'error', message: t('load_jisr_reviews_failed') })
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
      ? (submission.feedback || t('default_accept_feedback'))
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
      message: selectedStatus.value === 'accepted' ? t('solution_accepted_success') : t('solution_returned_success')
    })

    closeModal()
    await loadReviews()
  } catch (error) {
    console.error('Failed to review submission:', error)
    toastStore.addToast({
      type: 'error',
      message: error.response?.data?.message || t('save_review_failed')
    })
  } finally {
    isSubmitting.value = false
  }
}

const formatDate = (value) => {
  if (!value) return t('not_specified')
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? t('not_specified')
    : date.toLocaleString(currentLang.value === 'ar' ? 'ar-EG' : 'en-US')
}

const statusText = (status) => ({
  pending_review: t('pending_review'),
  accepted: t('accepted'),
  rejected: t('rejected_needs_revision')
}[status] || t('new'))

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

const statusWeight = (status) => ({
  pending_review: 0,
  rejected: 1,
  accepted: 2
}[status] ?? 3)

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
  color: var(--accent);
  font-size: 42px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card,
.student-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
}

.stat-card {
  border-radius: 18px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 18px;
}

.stat-card strong {
  font-size: 30px;
}

.stat-card.warning strong {
  color: #b7791f;
}

.stat-card.success strong {
  color: #238654;
}

.stat-card.danger strong {
  color: #b84a4a;
}

.stat-label,
.student-meta,
.review-meta,
.task-description,
.answer-text {
  color: var(--text-muted);
}

.student-cards {
  display: grid;
  gap: 20px;
}

.student-card {
  border-radius: 24px;
  padding: 22px;
  box-shadow: 0 16px 42px rgba(15, 23, 42, .07);
}

.student-card.pending {
  border-inline-start: 5px solid #d9a52f;
}

.student-card.accepted {
  border-inline-start: 5px solid #4baf72;
}

.student-card.rejected {
  border-inline-start: 5px solid #d66a6a;
}

.student-card__top,
.task-row__top,
.actions-row,
.attachments-header {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: space-between;
}

.student-name,
.task-title,
.section-title {
  color: var(--text-dark);
  font-weight: 700;
}

.student-summary {
  background: var(--main-bg);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
  padding: 12px;
}

.student-summary span,
.attachments-count,
.status-pill {
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
}

.student-summary span {
  background: #fff;
  border: 1px solid var(--border-color);
  padding: 7px 12px;
}

.task-list {
  display: grid;
  gap: 0;
  margin-top: 18px;
}

.task-row {
  border-top: 1px solid var(--border-color);
  padding: 18px 0;
}

.task-row:first-child {
  border-top: none;
  padding-top: 0;
}

.task-row:last-child {
  padding-bottom: 0;
}

.task-row.pending .task-title {
  color: #9a6a0a;
}

.task-row.accepted .task-title {
  color: #217345;
}

.task-row.rejected .task-title {
  color: #9f2f2f;
}

.answer-box,
.attachments-box {
  background: var(--main-bg);
  border-radius: 14px;
  margin-top: 14px;
  padding: 14px;
}

.attachments-box.muted {
  color: var(--text-muted);
}

.status-pill {
  padding: 8px 14px;
}

.status-pill.warning {
  background: #fff7d6;
  color: #8a5a04;
}

.status-pill.success {
  background: #e8f8ef;
  color: #1f6f42;
}

.status-pill.danger {
  background: #fdecec;
  color: #9f2f2f;
}

.attachments-count {
  background: #f8fafc;
  border: 1px solid var(--border-color);
  color: #475569;
  padding: 6px 12px;
}

.attachments-list {
  display: grid;
  gap: 12px;
  margin-top: 12px;
}

.attachment-item {
  align-items: center;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  display: flex;
  gap: 14px;
  justify-content: space-between;
  padding: 14px 16px;
}

.attachment-item__info {
  align-items: center;
  display: flex;
  flex: 1;
  gap: 12px;
  min-width: 0;
}

.attachment-item__icon {
  align-items: center;
  background: rgba(124, 58, 237, 0.08);
  border-radius: 12px;
  color: var(--accent);
  display: inline-flex;
  flex-shrink: 0;
  font-size: 18px;
  height: 42px;
  justify-content: center;
  width: 42px;
}

.attachment-item__name {
  color: var(--text-color);
  font-size: 14px;
  font-weight: 700;
  word-break: break-word;
}

.attachment-item__meta {
  color: #64748b;
  font-size: 12px;
  margin-top: 2px;
}

.attachment-item__actions {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.attachment-btn {
  align-items: center;
  border-radius: 999px;
  display: inline-flex;
  font-size: 13px;
  font-weight: 700;
  gap: 8px;
  padding: 8px 14px;
  text-decoration: none;
}

.attachment-btn--view {
  background: rgba(124, 58, 237, 0.08);
  color: var(--accent);
}

.attachment-btn--download {
  background: #eaf8f0;
  color: #1f6f42;
}

.submission-footer {
  margin-top: 16px;
}

.actions-row {
  margin-top: 12px;
}

.btn-review,
.btn-submit-review,
.btn-icon {
  border: none;
  cursor: pointer;
}

.btn-review {
  align-items: center;
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  border-radius: 12px;
  color: #fff;
  display: inline-flex;
  gap: 8px;
  padding: 10px 16px;
}

.btn-review.danger {
  background: #fdecec;
  color: #9f2f2f;
}

.modal-overlay {
  align-items: center;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  inset: 0;
  justify-content: center;
  padding: 20px;
  position: fixed;
  z-index: 1200;
}

.review-modal {
  background: var(--card-bg);
  border-radius: 24px;
  box-shadow: 0 30px 60px rgba(15, 23, 42, 0.2);
  max-width: 640px;
  overflow: hidden;
  width: 100%;
}

.modal-header,
.modal-body {
  padding: 22px;
}

.modal-header {
  border-bottom: 1px solid var(--border-color);
  display: flex;
  gap: 12px;
  justify-content: space-between;
}

.btn-icon {
  background: var(--main-bg);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  height: 36px;
  width: 36px;
}

.btn-submit-review {
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  border-radius: 12px;
  color: #fff;
  font-weight: 700;
  padding: 12px 16px;
  width: 100%;
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
    align-items: stretch;
    flex-direction: column;
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
