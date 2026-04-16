<template>
  <div class="program-details">
    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted mt-3">{{ t('loading') }}</p>
    </div>

    <div v-else-if="!program" class="text-center py-5">
      <i class="bi bi-exclamation-triangle fs-1 text-muted"></i>
      <p class="text-muted mt-3">{{ t('program_not_found') }}</p>
      <router-link to="/company/programs" class="btn btn-primary">
        {{ t('back_to_programs') }}
      </router-link>
    </div>

    <div v-else>
      <!-- Header with back button -->
      <div class="d-flex align-items-center mb-4">
        <button class="btn btn-link me-3" @click="goBack">
          <i class="bi bi-arrow-left fs-4"></i>
        </button>
        <div>
          <h2 class="fw-bold mb-2">{{ program.title }}</h2>
          <p class="text-muted mb-0">{{ t('program_details') }}</p>
        </div>
        <div class="ms-auto d-flex gap-2">
          <button class="btn-accent-outline" @click="editProgram">
            <i class="bi bi-pencil me-2"></i>{{ t('edit') }}
          </button>
          <button class="btn-accent-gradient" @click="viewApplicants">
            <i class="bi bi-people me-2"></i>{{ t('view_applicants') }}
          </button>
        </div>
      </div>

      <!-- Program Info Cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-3">
          <div class="info-card">
            <div class="d-flex align-items-center gap-3">
              <div class="info-icon bg-primary text-white">
                <i class="bi bi-people"></i>
              </div>
              <div>
                <p class="text-muted small mb-1">{{ t('students_enrolled') }}</p>
                <h4 class="fw-bold mb-0">{{ program.students_count || 0 }}</h4>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-card">
            <div class="d-flex align-items-center gap-3">
              <div class="info-icon bg-success text-white">
                <i class="bi bi-clock"></i>
              </div>
              <div>
                <p class="text-muted small mb-1">{{ t('duration') }}</p>
                <h4 class="fw-bold mb-0">{{ program.duration_weeks || program.duration }} {{ t('weeks') }}</h4>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-card">
            <div class="d-flex align-items-center gap-3">
              <div class="info-icon bg-info text-white">
                <i class="bi bi-calendar"></i>
              </div>
              <div>
                <p class="text-muted small mb-1">{{ t('start_date') }}</p>
                <h4 class="fw-bold mb-0">{{ formatDate(program.start_date) }}</h4>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-card">
            <div class="d-flex align-items-center gap-3">
              <div class="info-icon bg-warning text-white">
                <i class="bi bi-file-text"></i>
              </div>
              <div>
                <p class="text-muted small mb-1">{{ t('applicants') }}</p>
                <h4 class="fw-bold mb-0">{{ program.applicants_count || 0 }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
          <!-- Description -->
          <div class="details-card mb-4">
            <h5 class="fw-bold mb-3">{{ t('description') }}</h5>
            <p class="text-muted">{{ program.description }}</p>
          </div>

          <!-- Requirements -->
          <div class="details-card mb-4">
            <h5 class="fw-bold mb-3">{{ t('requirements') }}</h5>
            <ul class="list-unstyled">
              <li v-for="(req, index) in program.requirements || []" :key="index" class="mb-2">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                {{ req }}
              </li>
            </ul>
          </div>

          <!-- Skills -->
          <div class="details-card mb-4">
            <h5 class="fw-bold mb-3">{{ t('skills_required') }}</h5>
            <div class="d-flex flex-wrap gap-2">
              <span v-for="skill in program.skills_required || []" :key="skill" class="skill-tag">
                {{ skill }}
              </span>
            </div>
          </div>

          <!-- Timeline -->
          <div class="details-card">
            <h5 class="fw-bold mb-3">{{ t('program_timeline') }}</h5>
            <div class="timeline">
              <div v-for="(item, index) in program.timeline || []" :key="index" class="timeline-item">
                <div class="timeline-marker" :class="item.completed ? 'bg-success' : 'bg-secondary'"></div>
                <div class="timeline-content">
                  <h6 class="fw-bold mb-1">{{ item.title }}</h6>
                  <p class="small text-muted mb-0">{{ formatDate(item.date) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
          <!-- Status Card -->
          <div class="status-card mb-4">
            <h6 class="fw-bold mb-3">{{ t('program_status') }}</h6>
            <div class="mb-3">
              <span class="badge mb-2" :class="getStatusClass(program.status)">
                {{ t(program.status) }}
              </span>
            </div>
            <div class="progress mb-3" style="height: 10px;">
              <div class="progress-bar" :class="getProgressClass(program.progress)" :style="{ width: program.progress + '%' }"></div>
            </div>
            <p class="small text-muted">{{ program.progress }}% {{ t('complete') }}</p>

            <div class="mt-3 pt-3 border-top">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">{{ t('positions_available') }}</span>
                <span class="fw-bold">{{ program.positions_available || 0 }}</span>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted small">{{ t('applications_received') }}</span>
                <span class="fw-bold">{{ program.applicants_count || 0 }}</span>
              </div>
            </div>
          </div>

          <!-- Recent Applicants -->
          <div class="applicants-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold mb-0">{{ t('recent_applicants') }}</h6>
              <router-link to="/company/applicants" class="small text-primary">
                {{ t('view_all') }}
              </router-link>
            </div>
            <div v-for="applicant in recentApplicants" :key="applicant.id" class="applicant-item">
              <div class="d-flex align-items-center gap-2">
                <div class="avatar" :style="{ background: applicant.avatarColor }">
                  {{ applicant.initials }}
                </div>
                <div class="flex-grow-1">
                  <p class="fw-bold mb-0 small">{{ applicant.name }}</p>
                  <small class="text-muted">{{ formatDate(applicant.applied_at) }}</small>
                </div>
                <span class="badge" :class="getStatusClass(applicant.status)">
                  {{ t(applicant.status) }}
                </span>
              </div>
            </div>
            <div v-if="recentApplicants.length === 0" class="text-center py-3 text-muted small">
              {{ t('no_applicants_yet') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Program Modal -->
    <Teleport to="body">
      <div v-if="showEditModal" class="modal-overlay" @click.self="closeEditModal">
        <div class="modal-content" style="max-width: 700px;">
          <div class="modal-header">
            <h5 class="fw-bold mb-0">{{ t('edit_program') }}</h5>
            <button class="btn-close" @click="closeEditModal">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="updateProgram">
              <div class="mb-3">
                <label class="form-label fw-bold">{{ t('program_title') }}</label>
                <input type="text" class="form-control" v-model="editForm.title" required>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">{{ t('description') }}</label>
                <textarea class="form-control" rows="4" v-model="editForm.description" required></textarea>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold">{{ t('duration_weeks') }}</label>
                  <input type="number" class="form-control" v-model="editForm.duration_weeks" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">{{ t('positions_available') }}</label>
                  <input type="number" class="form-control" v-model="editForm.positions_available" required>
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold">{{ t('start_date') }}</label>
                  <input type="date" class="form-control" v-model="editForm.start_date" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">{{ t('end_date') }}</label>
                  <input type="date" class="form-control" v-model="editForm.end_date" required>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">{{ t('status') }}</label>
                <select class="form-select" v-model="editForm.status">
                  <option value="active">{{ t('active') }}</option>
                  <option value="draft">{{ t('draft') }}</option>
                  <option value="completed">{{ t('completed') }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">{{ t('requirements') }}</label>
                <div v-for="(req, idx) in editForm.requirements" :key="idx" class="input-group mb-2">
                  <input type="text" class="form-control" v-model="editForm.requirements[idx]" :placeholder="t('requirement_placeholder')">
                  <button class="btn btn-outline-danger" type="button" @click="removeRequirement(idx)">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
                <button type="button" class="btn btn-sm btn-link ps-0" @click="addRequirement">
                  <i class="bi bi-plus-circle me-1"></i> {{ t('add_requirement') }}
                </button>
              </div>
              <button type="submit" class="btn-accent-gradient w-100" :disabled="isUpdating">
                <span v-if="!isUpdating">{{ t('update_program') }}</span>
                <span v-else><span class="spinner-border spinner-border-sm me-2"></span>{{ t('saving') }}</span>
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
const isUpdating = ref(false)
const program = ref(null)
const recentApplicants = ref([])
const showEditModal = ref(false)
const editForm = ref({
  title: '',
  description: '',
  duration_weeks: '',
  positions_available: '',
  start_date: '',
  end_date: '',
  status: 'active',
  requirements: []
})

// ✅ جلب تفاصيل البرنامج من API
const loadProgramDetails = async () => {
  isLoading.value = true
  try {
    const programId = route.params.id
    const response = await companyAPI.getProgram(programId)
    const data = response.data.data
    
    program.value = {
      id: data.id,
      title: data.title,
      description: data.description,
      duration_weeks: data.duration_weeks,
      start_date: data.start_date,
      end_date: data.end_date,
      status: data.status,
      progress: data.progress || 0,
      students_count: data.students_count || 0,
      applicants_count: data.applicants_count || 0,
      positions_available: data.positions_available,
      requirements: data.requirements || [],
      skills_required: data.skills_required || [],
      timeline: data.timeline || []
    }
    
    recentApplicants.value = data.recent_applicants || []
    
  } catch (error) {
    console.error('Failed to load program details:', error)
  } finally {
    isLoading.value = false
  }
}

const editProgram = () => {
  editForm.value = {
    title: program.value.title,
    description: program.value.description,
    duration_weeks: program.value.duration_weeks,
    positions_available: program.value.positions_available,
    start_date: program.value.start_date,
    end_date: program.value.end_date,
    status: program.value.status,
    requirements: [...(program.value.requirements || [])]
  }
  showEditModal.value = true
  document.body.style.overflow = 'hidden'
}

const closeEditModal = () => {
  showEditModal.value = false
  document.body.style.overflow = ''
}

const updateProgram = async () => {
  isUpdating.value = true
  try {
    await companyAPI.updateProgram(program.value.id, editForm.value)
    closeEditModal()
    await loadProgramDetails()
    alert(t('program_updated'))
  } catch (error) {
    alert(error.response?.data?.message || t('error_occurred'))
  } finally {
    isUpdating.value = false
  }
}

const addRequirement = () => {
  editForm.value.requirements.push('')
}

const removeRequirement = (index) => {
  editForm.value.requirements.splice(index, 1)
}

const goBack = () => {
  router.push('/company/programs')
}

const viewApplicants = () => {
  router.push('/company/applicants')
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-success',
    draft: 'bg-warning',
    completed: 'bg-info',
    pending: 'bg-warning',
    accepted: 'bg-success',
    rejected: 'bg-danger'
  }
  return classes[status] || 'bg-secondary'
}

const getProgressClass = (progress) => {
  if (progress >= 70) return 'bg-success'
  if (progress >= 30) return 'bg-primary'
  return 'bg-warning'
}

onMounted(() => {
  AOS.init({ duration: 800, once: true })
  loadProgramDetails()
})
</script>

<style scoped>
.program-details { padding: 30px; }
.info-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #f0f0f0; height: 100%; }
.info-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.details-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid #f0f0f0; }
.skill-tag { background: #f3f0ff; color: #7c3aed; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 500; }
.status-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid #f0f0f0; }
.applicants-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid #f0f0f0; }
.timeline { position: relative; padding-left: 30px; }
.timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e5e7eb; }
.timeline-item { position: relative; margin-bottom: 25px; }
.timeline-marker { position: absolute; left: -30px; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px #e5e7eb; }
.avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; font-weight: 600; }
.applicant-item { padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
.applicant-item:last-child { border-bottom: none; }
.badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
.progress { background: #f0f0f0; border-radius: 10px; }
.btn-accent-outline { background: transparent; border: 2px solid #7c3aed; color: #7c3aed; border-radius: 10px; padding: 8px 20px; font-weight: 600; transition: all 0.3s ease; cursor: pointer; }
.btn-accent-outline:hover { background: #7c3aed; color: white; transform: translateY(-2px); }
.btn-accent-gradient { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.3s ease; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 1100; padding: 20px; }
.modal-content { background: var(--card-bg); border-radius: 24px; width: 100%; max-width: 700px; max-height: 90vh; overflow-y: auto; }
.modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 24px; }
.btn-close { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; }
[data-theme="dark"] .info-card,
[data-theme="dark"] .details-card,
[data-theme="dark"] .status-card,
[data-theme="dark"] .applicants-card { background: #1f2937; border-color: #374151; }
[data-theme="dark"] .timeline::before { background: #374151; }
@media (max-width: 768px) { .program-details { padding: 20px; } .info-card { padding: 16px; } .info-icon { width: 40px; height: 40px; font-size: 18px; } }
</style>