<template>
  <div class="applicant-details">
    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted mt-3">{{ t('loading') }}</p>
    </div>

    <div v-else-if="!applicant" class="text-center py-5">
      <i class="bi bi-exclamation-triangle fs-1 text-muted"></i>
      <p class="text-muted mt-3">{{ t('applicant_not_found') }}</p>
      <router-link to="/company/applicants" class="btn btn-primary">
        {{ t('back_to_applicants') }}
      </router-link>
    </div>

    <div v-else>
      <!-- Header with back button -->
      <div class="d-flex align-items-center mb-4">
        <button class="btn btn-link me-3" @click="goBack">
          <i class="bi bi-arrow-left fs-4"></i>
        </button>
        <div>
          <h2 class="fw-bold mb-2">{{ applicant.name }}</h2>
          <p class="text-muted mb-0">{{ t('applicant_details') }}</p>
        </div>
        <div class="ms-auto">
          <button v-if="applicant.can_open_board" class="btn btn-primary me-2" @click="openBoard">
            <i class="bi bi-kanban me-2"></i>Board
          </button>
          <button class="btn btn-outline-primary me-2" @click="sendMessage">
            <i class="bi bi-envelope me-2"></i>{{ t('send_message') }}
          </button>
          <button v-if="applicant.status === 'pending'" class="btn btn-success me-2" @click="updateStatus('accepted')">
            <i class="bi bi-check-lg me-2"></i>{{ t('accept') }}
          </button>
          <button v-if="applicant.status === 'pending'" class="btn btn-danger" @click="openRejectModal">
            <i class="bi bi-x-lg me-2"></i>{{ t('reject') }}
          </button>
        </div>
      </div>

      <div class="row g-4">
        <!-- Left Column - Personal Info -->
        <div class="col-lg-4">
          <!-- Profile Card -->
          <div class="profile-card mb-4">
            <div class="text-center mb-4">
              <div class="avatar-large mx-auto" :style="{ background: applicant.avatarColor }">
                {{ applicant.initials }}
              </div>
              <h4 class="fw-bold mt-3 mb-1">{{ applicant.name }}</h4>
              <p class="text-muted">{{ applicant.email }}</p>
              <span class="badge mb-2" :class="getStatusClass(applicant.status)">
                {{ t(applicant.status) }}
              </span>
            </div>

            <div class="info-list">
              <div class="info-item">
                <i class="bi bi-telephone text-primary"></i>
                <div>
                  <small class="text-muted d-block">{{ t('phone') }}</small>
                  <span>{{ applicant.phone || t('not_provided') }}</span>
                </div>
              </div>
              <div class="info-item">
                <i class="bi bi-geo-alt text-primary"></i>
                <div>
                  <small class="text-muted d-block">{{ t('location') }}</small>
                  <span>{{ applicant.location || t('not_provided') }}</span>
                </div>
              </div>
              <div class="info-item">
                <i class="bi bi-mortarboard text-primary"></i>
                <div>
                  <small class="text-muted d-block">{{ t('education') }}</small>
                  <span>{{ applicant.education || t('not_provided') }}</span>
                </div>
              </div>
              <div class="info-item">
                <i class="bi bi-briefcase text-primary"></i>
                <div>
                  <small class="text-muted d-block">{{ t('experience') }}</small>
                  <span>{{ applicant.experience || t('not_provided') }}</span>
                </div>
              </div>
              <div class="info-item">
                <i class="bi bi-percent text-primary"></i>
                <div>
                  <small class="text-muted d-block">{{ t('match_percentage') }}</small>
                  <span class="fw-bold">{{ applicant.match_percentage }}%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Application Status Card -->
          <div class="status-card mb-4">
            <h6 class="fw-bold mb-3">{{ t('application_status') }}</h6>
            <div class="mb-3">
              <span class="badge mb-2" :class="getStatusClass(applicant.status)">
                {{ t(applicant.status) }}
              </span>
            </div>
            <div class="mb-2">
              <small class="text-muted d-block">{{ t('applied_for') }}</small>
              <span class="fw-bold">{{ applicant.program_title }}</span>
            </div>
            <div class="mb-2">
              <small class="text-muted d-block">{{ t('applied_date') }}</small>
              <span>{{ formatDate(applicant.applied_at) }}</span>
            </div>
            <div>
              <small class="text-muted d-block">{{ t('last_update') }}</small>
              <span>{{ formatDate(applicant.updated_at) }}</span>
            </div>
          </div>

          <div v-if="applicant.final_status === 'approved'" class="evaluation-card mb-4">
            <h6 class="fw-bold mb-3">
              <i class="bi bi-star-half text-primary me-2"></i>
              تقييم مهمات الشركة
            </h6>

            <div v-if="applicant.training_completed_at" class="alert alert-success rounded-4 mb-3">
              <div class="fw-bold">تم اعتماد النتيجة النهائية</div>
              <div class="small mt-1">العلامة النهائية: {{ applicant.final_score ?? 0 }}/100</div>
            </div>

            <div v-else-if="applicant.training_ended && applicant.company_final_score === null" class="alert alert-warning rounded-4 mb-3">
              <div class="fw-bold">قيّم مهمات الطالب من لوحة المهمات.</div>
              <div class="small mt-1">علامة الشركة النهائية تُحسب تلقائيًا من متوسط تقييمات المهمات.</div>
              <button
                v-if="applicant.can_open_board"
                type="button"
                class="btn btn-primary btn-sm rounded-pill mt-3"
                @click="openBoard"
              >
                فتح لوحة المهمات
              </button>
            </div>

            <div v-else-if="applicant.training_ended" class="alert alert-info rounded-4 mb-3">
              <div class="fw-bold">علامة مهمات الشركة: {{ applicant.company_final_score }}/100</div>
              <div class="small mt-1" v-if="applicant.supervisor_final_score === null">
                بانتظار تقييم المشرف حتى تظهر واجهة المباركة.
              </div>
            </div>

            <div v-else class="alert alert-light border rounded-4 mb-3">
              <div class="fw-bold">علامة الشركة تُحسب من تقييمات المهمات.</div>
              <div class="small mt-1">تاريخ النهاية: {{ applicant.training_end_date || '-' }}</div>
            </div>

            <button
              v-if="applicant.complete_url"
              type="button"
              class="btn btn-success w-100 rounded-pill"
              @click="goTo(applicant.complete_url)"
            >
              واجهة المباركة والنتيجة
            </button>
          </div>
        </div>

        <!-- Right Column - Documents & Notes -->
        <div class="col-lg-8">
          <!-- Skills -->
          <div class="skills-card mb-4">
            <h6 class="fw-bold mb-3">{{ t('skills') }}</h6>
            <div class="d-flex flex-wrap gap-2">
              <span v-for="skill in applicant.skills || []" :key="skill" class="skill-tag">
                {{ skill }}
              </span>
              <span v-if="!applicant.skills?.length" class="text-muted small">{{ t('no_skills_listed') }}</span>
            </div>
          </div>

          <!-- Documents -->
          <div class="documents-card mb-4">
            <h6 class="fw-bold mb-3">{{ t('submitted_documents') }}</h6>
            <div v-for="doc in applicant.documents || []" :key="doc.name" class="document-item">
              <div class="d-flex align-items-center gap-3">
                <i :class="getDocumentIcon(doc.type)" class="fs-4" :style="{ color: doc.color }"></i>
                <div class="flex-grow-1">
                  <p class="mb-0 fw-bold small">{{ doc.name }}</p>
                  <small class="text-muted">{{ doc.size }} • {{ t('uploaded') }} {{ formatDate(doc.uploaded_at) }}</small>
                </div>
                <button class="btn btn-sm btn-outline-primary" @click="downloadDocument(doc)">
                  <i class="bi bi-download"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" @click="viewDocument(doc)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <div v-if="!applicant.documents?.length" class="text-center py-3 text-muted small">
              {{ t('no_documents_uploaded') }}
            </div>
          </div>

          <!-- Notes -->
          <div class="notes-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold mb-0">{{ t('notes') }}</h6>
              <button class="btn btn-sm btn-primary" @click="addNote">
                <i class="bi bi-plus-circle me-2"></i>{{ t('add_note') }}
              </button>
            </div>

            <div v-for="note in applicant.notes || []" :key="note.id" class="note-item">
              <div class="d-flex justify-content-between mb-2">
                <span class="fw-bold small">{{ note.author }}</span>
                <small class="text-muted">{{ formatDate(note.created_at) }}</small>
              </div>
              <p class="small mb-0">{{ note.content }}</p>
              <div class="mt-2">
                <button class="btn btn-sm btn-link p-0 me-3" @click="editNote(note)">
                  <i class="bi bi-pencil"></i> {{ t('edit') }}
                </button>
                <button class="btn btn-sm btn-link text-danger p-0" @click="deleteNote(note)">
                  <i class="bi bi-trash"></i> {{ t('delete') }}
                </button>
              </div>
            </div>
            <div v-if="!applicant.notes?.length" class="text-center py-3 text-muted small">
              {{ t('no_notes') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <Teleport to="body">
      <div v-if="showRejectModal" class="modal-overlay" @click.self="closeRejectModal">
        <div class="modal-content" style="max-width: 500px;">
          <div class="modal-header">
            <h5 class="fw-bold mb-0">{{ t('reject_applicant') }}</h5>
            <button class="btn-close" @click="closeRejectModal">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="rejectApplicant">
              <div class="mb-3">
                <label class="form-label fw-bold">{{ t('rejection_reason') }}</label>
                <textarea class="form-control" rows="4" v-model="rejectionReason" :placeholder="t('enter_rejection_reason')" required></textarea>
              </div>
              <button type="submit" class="btn-danger w-100 py-2 rounded-3" :disabled="isRejecting">
                <span v-if="!isRejecting">{{ t('confirm_reject') }}</span>
                <span v-else><span class="spinner-border spinner-border-sm me-2"></span>{{ t('processing') }}</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { companyAPI } from '@/services/api/company'
import AOS from 'aos'

const { t, formatDate } = useI18n()
const route = useRoute()
const router = useRouter()

// State
const isLoading = ref(false)
const isRejecting = ref(false)
const applicant = ref(null)
const showRejectModal = ref(false)
const rejectionReason = ref('')
// ✅ جلب تفاصيل المتقدم من API
const loadApplicantDetails = async () => {
  isLoading.value = true
  try {
    const applicantId = route.params.id
    const response = await companyAPI.getApplicant(applicantId)
    const data = response.data.data
    
    applicant.value = {
      id: data.id,
      name: data.name,
      email: data.email,
      initials: data.initials,
      avatarColor: data.avatarColor,
      phone: data.phone,
      location: data.location,
      education: data.education,
      experience: data.experience,
      skills: data.skills || [],
      match_percentage: data.match_percentage,
      status: data.status,
      final_status: data.final_status,
      can_open_board: !!data.can_open_board,
      board_url: data.board_url || null,
      training_end_date: data.training_end_date || null,
      training_ended: !!data.training_ended,
      training_completed_at: data.training_completed_at || null,
      complete_url: data.complete_url || null,
      company_final_score: data.company_final_score,
      supervisor_final_score: data.supervisor_final_score,
      supervisor_final_note: data.supervisor_final_note,
      final_score: data.final_score,
      program_title: data.program_title,
      applied_at: data.applied_at,
      updated_at: data.updated_at,
      documents: data.documents || [],
      notes: data.notes || []
    }
    
  } catch (error) {
    console.error('Failed to load applicant details:', error)
  } finally {
    isLoading.value = false
  }
}

const updateStatus = async (status) => {
  try {
    if (status === 'accepted') {
      await companyAPI.acceptApplicant(applicant.value.id)
      applicant.value.status = 'accepted'
      alert(t('applicant_accepted'))
    }
  } catch (error) {
    alert(error.response?.data?.message || t('error_occurred'))
  }
}

const openRejectModal = () => {
  rejectionReason.value = ''
  showRejectModal.value = true
  document.body.style.overflow = 'hidden'
}

const closeRejectModal = () => {
  showRejectModal.value = false
  document.body.style.overflow = ''
}

const rejectApplicant = async () => {
  if (!rejectionReason.value.trim()) {
    alert(t('enter_rejection_reason'))
    return
  }
  
  isRejecting.value = true
  try {
    await companyAPI.rejectApplicant(applicant.value.id, { reason: rejectionReason.value })
    applicant.value.status = 'rejected'
    closeRejectModal()
    alert(t('applicant_rejected'))
  } catch (error) {
    alert(error.response?.data?.message || t('error_occurred'))
  } finally {
    isRejecting.value = false
  }
}

const sendMessage = () => {
  alert(t('send_message_to', { name: applicant.value.name }))
}

const downloadDocument = (doc) => {
  if (!doc?.download_url && !doc?.url) {
    alert(t('error_occurred'))
    return
  }

  window.open(doc.download_url || doc.url, '_blank')
}

const viewDocument = (doc) => {
  if (!doc?.url) {
    alert(t('error_occurred'))
    return
  }

  window.open(doc.url, '_blank')
}

const addNote = () => {
  const note = prompt(t('enter_note'))
  if (note) {
    const newNote = {
      id: Date.now(),
      author: 'You',
      created_at: new Date().toISOString(),
      content: note
    }
    if (!applicant.value.notes) applicant.value.notes = []
    applicant.value.notes.unshift(newNote)
    // هنا يمكن إضافة API لحفظ الملاحظة
  }
}

const editNote = (note) => {
  const newContent = prompt(t('edit_note'), note.content)
  if (newContent) {
    note.content = newContent
    // هنا يمكن إضافة API لتحديث الملاحظة
  }
}

const deleteNote = (note) => {
  if (confirm(t('confirm_delete_note'))) {
    applicant.value.notes = applicant.value.notes.filter(n => n.id !== note.id)
    // هنا يمكن إضافة API لحذف الملاحظة
  }
}

const goBack = () => {
  router.push('/company/applicants')
}

const goTo = (url) => {
  if (!url) return
  window.location.href = url
}

const openBoard = () => {
  if (applicant.value?.board_url) {
    window.location.href = applicant.value.board_url
    return
  }

  router.push('/company/training-tasks')
}

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-warning',
    accepted: 'bg-success',
    rejected: 'bg-danger'
  }
  return classes[status] || 'bg-secondary'
}

const getDocumentIcon = (type) => {
  const icons = {
    pdf: 'bi bi-file-earmark-pdf',
    doc: 'bi bi-file-earmark-word',
    docx: 'bi bi-file-earmark-word',
    zip: 'bi bi-file-earmark-zip',
    image: 'bi bi-file-earmark-image'
  }
  return icons[type] || 'bi bi-file-earmark'
}

onMounted(() => {
  AOS.init({ duration: 800, once: true })
  loadApplicantDetails()
})
</script>

<style scoped>
.applicant-details { padding: 30px; }
.profile-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid #f0f0f0; }
.avatar-large { width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 36px; font-weight: 600; margin: 0 auto; }
.info-list { border-top: 1px solid #f0f0f0; padding-top: 20px; }
.info-item { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
.info-item i { width: 24px; font-size: 18px; }
.status-card, .skills-card, .documents-card, .notes-card, .evaluation-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid #f0f0f0; margin-bottom: 24px; }
.skill-tag { background: #f3f0ff; color: #7c3aed; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 500; }
.document-item { border: 1px solid #f0f0f0; border-radius: 12px; padding: 15px; margin-bottom: 10px; transition: all 0.2s; }
.document-item:hover { border-color: #7c3aed; box-shadow: 0 2px 8px rgba(124,58,237,0.1); }
.note-item { background: #f9fafb; border-radius: 12px; padding: 15px; margin-bottom: 15px; }
.badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
.btn-danger { background: #ef4444; color: white; border: none; border-radius: 10px; padding: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
.btn-danger:hover:not(:disabled) { background: #dc2626; transform: translateY(-2px); }
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 1100; padding: 20px; }
.modal-content { background: var(--card-bg); border-radius: 24px; width: 100%; max-height: 90vh; overflow-y: auto; }
.modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 24px; }
.btn-close { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; }
[data-theme="dark"] .profile-card,
[data-theme="dark"] .status-card,
[data-theme="dark"] .skills-card,
[data-theme="dark"] .documents-card,
[data-theme="dark"] .notes-card,
[data-theme="dark"] .evaluation-card { background: #1f2937; border-color: #374151; }
[data-theme="dark"] .note-item { background: #111827; }
[data-theme="dark"] .info-list { border-color: #374151; }
[data-theme="dark"] .document-item { border-color: #374151; }
@media (max-width: 768px) { .applicant-details { padding: 20px; } }
</style>
