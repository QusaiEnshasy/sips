<template>
  <div class="evaluations-page">
    <!-- Header -->
    <div class="page-header mb-4" data-aos="fade-down">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h2 class="fw-bold mb-2">{{ t('evaluations') }}</h2>
          <p class="text-muted mb-0">{{ t('manage_student_evaluations') }}</p>
        </div>
        <button class="btn-accent-gradient" @click="openCreateModal">
          <i class="bi bi-plus-circle me-2"></i>
          {{ t('new_evaluation') }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted mt-3">{{ t('loading') }}</p>
    </div>

    <div v-else>
      <!-- Stats Cards -->
      <div class="row g-4 mb-5">
        <div class="col-md-3" v-for="stat in stats" :key="stat.key">
          <div class="stat-card">
            <div class="stat-icon" :class="stat.iconClass">
              <i :class="stat.icon"></i>
            </div>
            <div class="stat-content">
              <p class="text-muted mb-1">{{ t(stat.label) }}</p>
              <h3 class="fw-bold mb-0">{{ stat.value }}</h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-card mb-4">
        <div class="row g-3">
          <div class="col-md-5">
            <div class="search-wrapper">
              <i class="bi bi-search"></i>
              <input type="text" class="form-control" :placeholder="t('search_students')" v-model="searchQuery" @input="loadEvaluations" />
            </div>
          </div>
          <div class="col-md-3">
            <select class="form-select" v-model="statusFilter" @change="loadEvaluations">
              <option value="all">{{ t('all_status') }}</option>
              <option value="completed">{{ t('completed') }}</option>
              <option value="pending">{{ t('pending') }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select" v-model="sortBy" @change="loadEvaluations">
              <option value="newest">{{ t('newest_first') }}</option>
              <option value="oldest">{{ t('oldest_first') }}</option>
              <option value="highest">{{ t('highest_score') }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn-refresh w-100" @click="loadEvaluations">
              <i class="bi bi-arrow-repeat me-2"></i>
              {{ t('refresh') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Evaluations List -->
      <div class="evaluations-list">
        <div v-for="evalItem in filteredEvaluations" :key="evalItem.id" class="evaluation-card" data-aos="fade-up">
          <div class="row align-items-center g-3">
            <div class="col-lg-3">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-box" :style="{ background: evalItem.avatarColor }">
                  {{ evalItem.initials }}
                </div>
                <div>
                  <h6 class="fw-bold mb-0">{{ evalItem.studentName }}</h6>
                  <small class="text-muted">{{ evalItem.studentId }}</small>
                </div>
              </div>
            </div>
            <div class="col-lg-3">
              <div>
                <small class="text-muted d-block">{{ t('program') }}</small>
                <span class="fw-bold small">{{ evalItem.program }}</span>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="score-badge" :class="getScoreClass(evalItem.score)">
                <i :class="getScoreIcon(evalItem.score)"></i>
                {{ evalItem.score }}%
              </div>
            </div>
            <div class="col-lg-2">
              <div>
                <small class="text-muted d-block">{{ t('evaluation_date') }}</small>
                <span class="small">{{ formatDate(evalItem.evaluationDate) }}</span>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="d-flex gap-2">
                <button class="btn-icon-accent" @click="viewEvaluation(evalItem)" :title="t('view_details')">
                  <i class="bi bi-eye"></i>
                </button>
                <button class="btn-icon-accent" @click="editEvaluation(evalItem)" :title="t('edit')">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn-icon-accent text-danger" @click="deleteEvaluation(evalItem)" :title="t('delete')">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4" v-if="pagination.total > pagination.per_page">
          <nav>
            <ul class="pagination">
              <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                <button class="page-link" @click="changePage(pagination.current_page - 1)">&laquo;</button>
              </li>
              <li class="page-item" v-for="page in paginationPages" :key="page" :class="{ active: page === pagination.current_page }">
                <button class="page-link" @click="changePage(page)">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                <button class="page-link" @click="changePage(pagination.current_page + 1)">&raquo;</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Create/Edit Evaluation Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content animate__animated animate__fadeInUp" style="max-width: 700px;">
          <div class="modal-header">
            <h5 class="fw-bold mb-0">{{ editingEvaluation ? t('edit_evaluation') : t('new_evaluation') }}</h5>
            <button class="btn-close" @click="closeModal"><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveEvaluation">
              <!-- Student Selection -->
              <div class="mb-4">
                <label class="form-label fw-bold">{{ t('student') }}</label>
                <select class="form-select" v-model="form.studentId" required :disabled="editingEvaluation">
                  <option value="">{{ t('select_student') }}</option>
                  <option v-for="student in students" :key="student.id" :value="student.id">
                    {{ student.name }} ({{ student.studentId }})
                  </option>
                </select>
              </div>

              <!-- Internship Selection -->
              <div class="mb-4">
                <label class="form-label fw-bold">{{ t('internship') }}</label>
                <select class="form-select" v-model="form.internshipId" required>
                  <option value="">{{ t('select_internship') }}</option>
                  <option v-for="internship in internships" :key="internship.id" :value="internship.id">
                    {{ internship.title }} - {{ internship.company }}
                  </option>
                </select>
              </div>

              <!-- Criteria Scores -->
              <div class="mb-4">
                <label class="form-label fw-bold">{{ t('evaluation_criteria') }}</label>
                <div v-for="(criterion, index) in criteria" :key="index" class="criterion-item mb-3">
                  <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold small">{{ t(criterion.name) }}</span>
                    <span class="text-muted small">{{ criterion.maxScore }} {{ t('points') }}</span>
                  </div>
                  <input type="range" class="form-range" :min="0" :max="criterion.maxScore"
                         v-model.number="form.criteriaScores[criterion.id]" @input="updateTotalScore">
                  <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted">{{ t('poor') }}</small>
                    <small class="text-muted">{{ t('excellent') }}</small>
                  </div>
                </div>
              </div>

              <!-- Total Score Display -->
              <div class="score-preview mb-4">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="fw-bold">{{ t('total_score') }}</span>
                  <span class="score-value" :class="getScoreClass(totalScore)">
                    {{ totalScore }} / 100
                  </span>
                </div>
                <div class="progress mt-2">
                  <div class="progress-bar" :class="getScoreClass(totalScore)" :style="{ width: totalScore + '%' }"></div>
                </div>
              </div>

              <!-- Comments -->
              <div class="mb-4">
                <label class="form-label fw-bold">{{ t('comments') }}</label>
                <textarea class="form-control" rows="4" v-model="form.comments"
                          :placeholder="t('feedback_placeholder')"></textarea>
              </div>

              <!-- Strengths & Weaknesses -->
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label fw-bold">{{ t('strengths') }}</label>
                  <div v-for="(strength, idx) in form.strengths" :key="idx" class="input-group mb-2">
                    <input type="text" class="form-control" v-model="form.strengths[idx]" :placeholder="t('enter_strength')">
                    <button class="btn btn-outline-danger" type="button" @click="removeStrength(idx)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                  <button type="button" class="btn btn-sm btn-link ps-0" @click="addStrength">
                    <i class="bi bi-plus-circle me-1"></i> {{ t('add_strength') }}
                  </button>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">{{ t('weaknesses') }}</label>
                  <div v-for="(weakness, idx) in form.weaknesses" :key="idx" class="input-group mb-2">
                    <input type="text" class="form-control" v-model="form.weaknesses[idx]" :placeholder="t('enter_weakness')">
                    <button class="btn btn-outline-danger" type="button" @click="removeWeakness(idx)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                  <button type="button" class="btn btn-sm btn-link ps-0" @click="addWeakness">
                    <i class="bi bi-plus-circle me-1"></i> {{ t('add_weakness') }}
                  </button>
                </div>
              </div>

              <!-- Submit Button -->
              <button type="submit" class="btn-accent-gradient w-100" :disabled="isSaving">
                <span v-if="!isSaving">
                  <i class="bi bi-check-lg me-2"></i>
                  {{ editingEvaluation ? t('update_evaluation') : t('create_evaluation') }}
                </span>
                <span v-else>
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  {{ t('saving') }}
                </span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- View Evaluation Modal -->
    <Teleport to="body">
      <div v-if="showViewModal" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal-content animate__animated animate__fadeInUp" style="max-width: 600px;">
          <div class="modal-header">
            <h5 class="fw-bold mb-0">{{ t('evaluation_details') }}</h5>
            <button class="btn-close" @click="closeViewModal"><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body" v-if="selectedEvaluation">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="avatar-box-lg" :style="{ background: selectedEvaluation.avatarColor }">
                {{ selectedEvaluation.initials }}
              </div>
              <div>
                <h6 class="fw-bold mb-0">{{ selectedEvaluation.studentName }}</h6>
                <small class="text-muted">{{ selectedEvaluation.studentId }}</small>
              </div>
            </div>

            <div class="score-display mb-4">
              <div class="score-circle" :class="getScoreClass(selectedEvaluation.score)">
                {{ selectedEvaluation.score }}%
              </div>
              <div class="ms-3">
                <span class="fw-bold">{{ t('overall_grade') }}</span>
                <p class="text-muted small mb-0">{{ getScoreText(selectedEvaluation.score) }}</p>
              </div>
            </div>

            <div class="mb-4">
              <h6 class="fw-bold mb-3">{{ t('criteria_scores') }}</h6>
              <div v-for="(score, key) in selectedEvaluation.criteriaScores" :key="key" class="criteria-row mb-2">
                <div class="d-flex justify-content-between mb-1">
                  <span>{{ getCriteriaName(key) }}</span>
                  <span class="fw-bold">{{ score }}/{{ getCriteriaMaxScore(key) }}</span>
                </div>
                <div class="progress">
                  <div class="progress-bar" :style="{ width: (score / getCriteriaMaxScore(key) * 100) + '%' }"></div>
                </div>
              </div>
            </div>

            <div class="mb-4" v-if="selectedEvaluation.comments">
              <h6 class="fw-bold mb-2">{{ t('comments') }}</h6>
              <p class="text-muted small">{{ selectedEvaluation.comments }}</p>
            </div>

            <div class="mb-3" v-if="selectedEvaluation.strengths?.length">
              <h6 class="fw-bold mb-2 text-success">{{ t('strengths') }}</h6>
              <ul class="list-unstyled">
                <li v-for="(s, idx) in selectedEvaluation.strengths" :key="idx" class="mb-1">
                  <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                  {{ s }}
                </li>
              </ul>
            </div>

            <div class="mb-3" v-if="selectedEvaluation.weaknesses?.length">
              <h6 class="fw-bold mb-2 text-danger">{{ t('weaknesses') }}</h6>
              <ul class="list-unstyled">
                <li v-for="(w, idx) in selectedEvaluation.weaknesses" :key="idx" class="mb-1">
                  <i class="bi bi-x-circle-fill text-danger me-2 small"></i>
                  {{ w }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { supervisorAPI } from '@/services/api/supervisor'
import { useToastStore } from '@/stores/toast'
import AOS from 'aos'

const { t, formatDate } = useI18n()
const router = useRouter()
const toastStore = useToastStore()

// State
const isLoading = ref(false)
const isSaving = ref(false)
const evaluations = ref([])
const students = ref([])
const internships = ref([])
const searchQuery = ref('')
const statusFilter = ref('all')
const sortBy = ref('newest')
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
})
const showModal = ref(false)
const showViewModal = ref(false)
const editingEvaluation = ref(null)
const selectedEvaluation = ref(null)

// Criteria
const criteria = ref([
  { id: 'technical_skills', name: 'technical_skills', maxScore: 25 },
  { id: 'quality', name: 'quality', maxScore: 20 },
  { id: 'commitment', name: 'commitment', maxScore: 20 },
  { id: 'initiative', name: 'initiative', maxScore: 15 },
  { id: 'teamwork', name: 'teamwork', maxScore: 10 },
  { id: 'communication', name: 'communication', maxScore: 10 }
])

// Form
const form = ref({
  studentId: '',
  internshipId: '',
  criteriaScores: {
    technical_skills: 0,
    quality: 0,
    commitment: 0,
    initiative: 0,
    teamwork: 0,
    communication: 0
  },
  comments: '',
  strengths: [],
  weaknesses: []
})

// Stats
const stats = ref([
  { key: 'total', icon: 'bi bi-star-fill', iconClass: 'bg-primary', label: 'total_evaluations', value: '0' },
  { key: 'average', icon: 'bi bi-graph-up', iconClass: 'bg-success', label: 'average_score', value: '0%' },
  { key: 'completed', icon: 'bi bi-check-circle', iconClass: 'bg-info', label: 'completed', value: '0' },
  { key: 'pending', icon: 'bi bi-clock', iconClass: 'bg-warning', label: 'pending', value: '0' }
])

// Computed
const totalScore = computed(() => {
  const s = form.value.criteriaScores
  return (s.technical_skills || 0) + (s.quality || 0) + (s.commitment || 0) +
         (s.initiative || 0) + (s.teamwork || 0) + (s.communication || 0)
})

const filteredEvaluations = computed(() => {
  let filtered = [...evaluations.value]
  if (searchQuery.value) {
    filtered = filtered.filter(e =>
      e.studentName?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      e.studentId?.includes(searchQuery.value)
    )
  }
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(e => e.status === statusFilter.value)
  }
  switch (sortBy.value) {
    case 'newest': return filtered.sort((a, b) => new Date(b.evaluationDate) - new Date(a.evaluationDate))
    case 'oldest': return filtered.sort((a, b) => new Date(a.evaluationDate) - new Date(b.evaluationDate))
    case 'highest': return filtered.sort((a, b) => b.score - a.score)
    default: return filtered
  }
})

const paginationPages = computed(() => {
  const pages = []
  const total = pagination.value.last_page
  const current = pagination.value.current_page
  const maxVisible = 5
  let start = Math.max(1, current - Math.floor(maxVisible / 2))
  let end = Math.min(total, start + maxVisible - 1)
  if (end - start + 1 < maxVisible) start = Math.max(1, end - maxVisible + 1)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

// Load evaluations
const loadEvaluations = async () => {
  isLoading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      search: searchQuery.value,
      status: statusFilter.value !== 'all' ? statusFilter.value : null,
      sort: sortBy.value
    }
    const response = await supervisorAPI.getEvaluations(params)
    const data = response.data.data

    evaluations.value = (data.evaluations || []).map(e => ({
      id: e.id,
      studentName: e.student?.user?.name || t('unknown'),
      studentId: e.student?.student_id || '',
      initials: getInitials(e.student?.user?.name || ''),
      avatarColor: getAvatarColor(e.student?.user?.name || ''),
      program: e.internship?.title || '',
      score: e.score,
      status: e.score >= 70 ? 'completed' : 'pending',
      evaluationDate: e.evaluation_date,
      criteriaScores: e.criteria_scores || {},
      comments: e.comments,
      strengths: e.strengths || [],
      weaknesses: e.weaknesses || []
    }))

    pagination.value = data.pagination || { current_page: 1, last_page: 1, per_page: 10, total: 0 }

    stats.value[0].value = evaluations.value.length
    const avgScore = evaluations.value.reduce((sum, e) => sum + e.score, 0) / evaluations.value.length || 0
    stats.value[1].value = Math.round(avgScore) + '%'
    stats.value[2].value = evaluations.value.filter(e => e.status === 'completed').length
    stats.value[3].value = evaluations.value.filter(e => e.status === 'pending').length

  } catch (error) {
    console.error('Failed to load evaluations:', error)
    toastStore.addToast({ type: 'error', title: t('error'), message: error.response?.data?.message || t('error_loading_evaluations') })
  } finally {
    isLoading.value = false
  }
}

const loadStudents = async () => {
  try {
    const response = await supervisorAPI.getStudents()
    students.value = (response.data.data?.students || []).map(s => ({
      id: s.id,
      name: s.name,
      studentId: s.student_id
    }))
  } catch (error) {
    console.error('Failed to load students:', error)
  }
}

const loadInternships = async () => {
  try {
    const response = await supervisorAPI.getInternships()
    internships.value = (response.data.data?.internships || []).map(i => ({
      id: i.id,
      title: i.title,
      company: i.company?.company_name || ''
    }))
  } catch (error) {
    console.error('Failed to load internships:', error)
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    pagination.value.current_page = page
    loadEvaluations()
  }
}

const openCreateModal = () => {
  editingEvaluation.value = null
  form.value = {
    studentId: '',
    internshipId: '',
    criteriaScores: { technical_skills: 0, quality: 0, commitment: 0, initiative: 0, teamwork: 0, communication: 0 },
    comments: '',
    strengths: [],
    weaknesses: []
  }
  showModal.value = true
  document.body.style.overflow = 'hidden'
}

const editEvaluation = (evalData) => {
  editingEvaluation.value = evalData
  form.value = {
    studentId: evalData.studentId,
    internshipId: evalData.internshipId,
    criteriaScores: evalData.criteriaScores || {},
    comments: evalData.comments || '',
    strengths: evalData.strengths ? [...evalData.strengths] : [],
    weaknesses: evalData.weaknesses ? [...evalData.weaknesses] : []
  }
  showModal.value = true
  document.body.style.overflow = 'hidden'
}

const viewEvaluation = (evalData) => {
  selectedEvaluation.value = evalData
  showViewModal.value = true
  document.body.style.overflow = 'hidden'
}

const closeModal = () => {
  showModal.value = false
  editingEvaluation.value = null
  document.body.style.overflow = ''
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedEvaluation.value = null
  document.body.style.overflow = ''
}

const saveEvaluation = async () => {
  if (totalScore.value === 0) {
    toastStore.addToast({ type: 'error', title: t('error'), message: t('select_score_alert') })
    return
  }

  isSaving.value = true
  try {
    const payload = {
      student_id: form.value.studentId,
      internship_id: form.value.internshipId,
      score: totalScore.value,
      criteria_scores: form.value.criteriaScores,
      comments: form.value.comments,
      strengths: form.value.strengths.filter(s => s.trim()),
      weaknesses: form.value.weaknesses.filter(w => w.trim())
    }

    if (editingEvaluation.value) {
      await supervisorAPI.updateEvaluation(editingEvaluation.value.id, payload)
      toastStore.addToast({ type: 'success', title: t('success'), message: t('evaluation_updated') })
    } else {
      await supervisorAPI.createEvaluation(payload)
      toastStore.addToast({ type: 'success', title: t('success'), message: t('evaluation_created') })
    }

    await loadEvaluations()
    closeModal()
  } catch (error) {
    toastStore.addToast({ type: 'error', title: t('error'), message: error.response?.data?.message || t('error_occurred') })
  } finally {
    isSaving.value = false
  }
}

const deleteEvaluation = async (evalData) => {
  if (confirm(t('confirm_delete_evaluation'))) {
    try {
      await supervisorAPI.deleteEvaluation(evalData.id)
      await loadEvaluations()
      toastStore.addToast({ type: 'success', title: t('success'), message: t('evaluation_deleted') })
    } catch (error) {
      toastStore.addToast({ type: 'error', title: t('error'), message: error.response?.data?.message || t('error_occurred') })
    }
  }
}

const updateTotalScore = () => {}

const addStrength = () => { form.value.strengths.push('') }
const removeStrength = (idx) => { form.value.strengths.splice(idx, 1) }
const addWeakness = () => { form.value.weaknesses.push('') }
const removeWeakness = (idx) => { form.value.weaknesses.splice(idx, 1) }

// Helper functions
const getInitials = (name) => name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || '??'
const getAvatarColor = (name) => {
  const colors = ['#7c3aed', '#3b82f6', '#10b981', '#f59e0b', '#ef4444']
  return colors[(name?.length || 0) % colors.length]
}
const getScoreClass = (score) => score >= 85 ? 'bg-success' : score >= 70 ? 'bg-primary' : score >= 60 ? 'bg-warning' : 'bg-danger'
const getScoreIcon = (score) => score >= 85 ? 'bi bi-star-fill' : score >= 70 ? 'bi bi-star-half' : 'bi bi-star'
const getScoreText = (score) => score >= 85 ? t('excellent') : score >= 70 ? t('very_good') : score >= 60 ? t('good') : t('needs_improvement')
const getCriteriaName = (key) => ({
  technical_skills: t('technical_skills'),
  quality: t('quality'),
  commitment: t('commitment'),
  initiative: t('initiative'),
  teamwork: t('teamwork'),
  communication: t('communication')
})[key] || key
const getCriteriaMaxScore = (key) => ({ technical_skills: 25, quality: 20, commitment: 20, initiative: 15, teamwork: 10, communication: 10 })[key] || 10

onMounted(() => {
  AOS.init({ duration: 800, once: true })
  loadEvaluations()
  loadStudents()
  loadInternships()
})
</script>

<style scoped>
/* الأنماط الأصلية من الملف المرفوع */
.evaluations-page { padding: 20px 0; }
.stat-card { background: var(--card-bg); border-radius: 16px; padding: 20px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px; }
.stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.filters-card { background: var(--card-bg); border-radius: 16px; padding: 20px; border: 1px solid var(--border-color); }
.search-wrapper { position: relative; }
.search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.search-wrapper input { padding-left: 45px; }
.btn-refresh { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px; font-weight: 500; cursor: pointer; }
.evaluation-card { background: var(--card-bg); border-radius: 16px; padding: 20px; border: 1px solid var(--border-color); margin-bottom: 15px; transition: all 0.3s ease; }
.avatar-box { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px; }
.score-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 14px; width: fit-content; }
.btn-icon-accent { width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.btn-accent-gradient { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.3s ease; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 1100; padding: 20px; }
.modal-content { background: var(--card-bg); border-radius: 24px; width: 100%; max-width: 700px; max-height: 90vh; overflow-y: auto; }
.modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 24px; }
.btn-close { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.criterion-item { background: var(--main-bg); padding: 12px; border-radius: 12px; }
.score-preview { background: var(--main-bg); padding: 15px; border-radius: 12px; }
.score-value { font-size: 24px; font-weight: 700; }
.score-display { display: flex; align-items: center; gap: 20px; background: var(--main-bg); padding: 20px; border-radius: 16px; }
.score-circle { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; color: white; }
.pagination .page-link { background: var(--card-bg); border-color: var(--border-color); color: var(--text-dark); cursor: pointer; }
.pagination .active .page-link { background: var(--accent); border-color: var(--accent); color: white; }
@media (max-width: 768px) { .evaluation-card .row { gap: 15px; } .score-badge { width: 100%; justify-content: center; } }
</style>