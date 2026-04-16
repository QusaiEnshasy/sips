<template>
  <div class="company-applicants">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-2">{{ t('applicants') }}</h2>
        <p class="text-muted mb-0">{{ t('manage_applicants') }}</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted mt-3">{{ t('loading') }}</p>
    </div>

    <div v-else>
      <!-- Stats -->
      <div class="row g-4 mb-4">
        <div class="col-md-3" v-for="stat in stats" :key="stat.key">
          <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
              <div class="stat-icon" :class="stat.iconClass">
                <i :class="stat.icon"></i>
              </div>
              <div>
                <p class="text-muted small mb-1">{{ t(stat.label) }}</p>
                <h4 class="fw-bold mb-0">{{ stat.value }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-section mb-4">
        <div class="row g-3">
          <div class="col-md-5">
            <div class="search-wrapper">
              <i class="bi bi-search"></i>
              <input type="text" class="form-control" :placeholder="t('search_applicants')" v-model="searchQuery" @input="loadApplicants" />
            </div>
          </div>
          <div class="col-md-3">
            <select class="form-select" v-model="programFilter" @change="loadApplicants">
              <option value="all">{{ t('all_programs') }}</option>
              <option v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.title }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select" v-model="statusFilter" @change="loadApplicants">
              <option value="all">{{ t('all_status') }}</option>
              <option value="pending">{{ t('pending') }}</option>
              <option value="accepted">{{ t('accepted') }}</option>
              <option value="rejected">{{ t('rejected') }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn-refresh w-100" @click="loadApplicants">
              <i class="bi bi-arrow-repeat me-2"></i>
              {{ t('refresh') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Applicants Table -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>{{ t('applicant') }}</th>
                <th>{{ t('program') }}</th>
                <th>{{ t('applied_date') }}</th>
                <th>{{ t('match') }}</th>
                <th>{{ t('status') }}</th>
                <th>{{ t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="applicant in applicants" :key="applicant.id">
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar" :style="{ background: applicant.avatarColor }">
                      {{ applicant.initials }}
                    </div>
                    <div>
                      <div class="fw-bold">{{ applicant.name }}</div>
                      <small class="text-muted">{{ applicant.email }}</small>
                    </div>
                  </div>
                </td>
                <td>{{ applicant.program_title }}</td>
                <td>{{ formatDate(applicant.applied_at) }}</td>
                <td>
                  <span class="match-badge" :class="getMatchClass(applicant.match_percentage)">
                    {{ applicant.match_percentage }}%
                  </span>
                </td>
                <td>
                  <span class="badge" :class="getStatusClass(applicant.status)">
                    {{ t(applicant.status) }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-icon-accent btn-sm" @click="viewApplicant(applicant)">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button
                      v-if="applicant.can_open_board"
                      class="btn btn-sm btn-outline-primary"
                      @click="openBoard(applicant)"
                      title="Open Task Board"
                    >
                      <i class="bi bi-kanban"></i>
                    </button>
                    <button v-if="applicant.status === 'pending'" class="btn btn-sm btn-outline-success" @click="acceptApplicant(applicant)">
                      <i class="bi bi-check-lg"></i>
                    </button>
                    <button v-if="applicant.status === 'pending'" class="btn btn-sm btn-outline-danger" @click="rejectApplicant(applicant)">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
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

      <div class="table-card mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0">{{ t('approved_students') }} - {{ t('training_progress') }}</h5>
          <span class="text-muted small">{{ approvedTrainees.length }}</span>
        </div>
        <div v-if="approvedTrainees.length === 0" class="text-muted small py-3">
          {{ t('no_approved_students_yet') }}
        </div>
        <div v-else class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>{{ t('student') }}</th>
                <th>{{ t('program') }}</th>
                <th>{{ t('training_progress') }}</th>
                <th>{{ t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="trainee in approvedTrainees" :key="trainee.application_id">
                <td>
                  <div class="fw-bold">{{ trainee.student_name }}</div>
                  <small class="text-muted">{{ trainee.student_email }}</small>
                </td>
                <td>{{ trainee.program_title }}</td>
                <td style="min-width: 220px;">
                  <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-success" :style="{ width: `${trainee.progress_percent}%` }"></div>
                  </div>
                  <small class="text-muted">{{ trainee.progress_percent }}%</small>
                </td>
                <td>
                  <button v-if="trainee.board_url" class="btn btn-sm btn-primary" @click="openBoard(trainee)">
                    <i class="bi bi-kanban me-1"></i>{{ t('board') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { companyAPI } from '@/services/api/company'
import AOS from 'aos'

const { t, formatDate } = useI18n()
const router = useRouter()

// State
const isLoading = ref(false)
const applicants = ref([])
const programs = ref([])
const approvedTrainees = ref([])
const searchQuery = ref('')
const programFilter = ref('all')
const statusFilter = ref('all')
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

// Stats
const stats = ref([
  { key: 'total', icon: 'bi bi-people', iconClass: 'bg-primary', label: 'total_applicants', value: '0' },
  { key: 'pending', icon: 'bi bi-clock', iconClass: 'bg-warning', label: 'pending', value: '0' },
  { key: 'accepted', icon: 'bi bi-check-circle', iconClass: 'bg-success', label: 'accepted', value: '0' },
  { key: 'rejected', icon: 'bi bi-x-circle', iconClass: 'bg-danger', label: 'rejected', value: '0' }
])

// Pagination pages
const paginationPages = computed(() => {
  const pages = []
  const total = pagination.value.last_page
  const current = pagination.value.current_page
  const maxVisible = 5
  
  let start = Math.max(1, current - Math.floor(maxVisible / 2))
  let end = Math.min(total, start + maxVisible - 1)
  
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// ✅ جلب المتقدمين من API
const loadApplicants = async () => {
  isLoading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      search: searchQuery.value,
      program_id: programFilter.value !== 'all' ? programFilter.value : null,
      status: statusFilter.value !== 'all' ? statusFilter.value : null
    }
    
    const response = await companyAPI.getApplicants(params)
    const data = response.data.data
    
    applicants.value = data.applicants || []
    approvedTrainees.value = data.approved_trainees || []
    pagination.value = data.pagination || { current_page: 1, last_page: 1, per_page: 15, total: 0 }
    
    if (data.stats) {
      stats.value = data.stats
    }
    
  } catch (error) {
    console.error('Failed to load applicants:', error)
  } finally {
    isLoading.value = false
  }
}

// جلب البرامج للفلتر
const loadPrograms = async () => {
  try {
    const response = await companyAPI.getPrograms()
    programs.value = response.data.data.programs || []
  } catch (error) {
    console.error('Failed to load programs:', error)
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    pagination.value.current_page = page
    loadApplicants()
  }
}

const viewApplicant = (applicant) => {
  router.push(`/company/applicants/${applicant.id}`)
}

const openBoard = (item) => {
  const url = item.board_url
  if (url) {
    window.location.href = url
  }
}

const acceptApplicant = async (applicant) => {
  if (confirm(t('confirm_accept_applicant'))) {
    try {
      await companyAPI.acceptApplicant(applicant.id)
      await loadApplicants()
      alert(t('applicant_accepted'))
    } catch (error) {
      alert(error.response?.data?.message || t('error_occurred'))
    }
  }
}

const rejectApplicant = async (applicant) => {
  const reason = prompt(t('enter_rejection_reason'))
  if (reason !== null) {
    try {
      await companyAPI.rejectApplicant(applicant.id, { reason })
      await loadApplicants()
      alert(t('applicant_rejected'))
    } catch (error) {
      alert(error.response?.data?.message || t('error_occurred'))
    }
  }
}

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-warning',
    accepted: 'bg-success',
    rejected: 'bg-danger'
  }
  return classes[status] || 'bg-secondary'
}

const getMatchClass = (match) => {
  if (match >= 80) return 'match-high'
  if (match >= 60) return 'match-medium'
  return 'match-low'
}

onMounted(() => {
  AOS.init({ duration: 800, once: true })
  loadPrograms()
  loadApplicants()
})
</script>

<style scoped>
.company-applicants { padding: 30px; }
.stat-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #f0f0f0; }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; }
.filter-section { background: white; border-radius: 16px; padding: 20px; border: 1px solid #f0f0f0; }
.search-wrapper { position: relative; }
.search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.search-wrapper input { padding-left: 45px; }
.btn-refresh { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; font-weight: 500; cursor: pointer; }
.btn-refresh:hover { background: #f5f3ff; border-color: #7c3aed; color: #7c3aed; }
.table-card { background: white; border-radius: 20px; padding: 20px; border: 1px solid #f0f0f0; }
.avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; font-weight: 600; }
.match-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.match-high { background: #f0fdf4; color: #22c55e; }
.match-medium { background: #fffbeb; color: #f59e0b; }
.match-low { background: #fef2f2; color: #ef4444; }
.badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
.action-buttons { display: flex; gap: 5px; }
.btn-icon-accent { width: 32px; height: 32px; border-radius: 8px; background: transparent; border: 2px solid #7c3aed; color: #7c3aed; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; }
.btn-icon-accent:hover { background: #7c3aed; color: white; transform: translateY(-2px); }
.pagination .page-link { background: white; border-color: #e2e8f0; color: #1e293b; cursor: pointer; }
.pagination .active .page-link { background: #7c3aed; border-color: #7c3aed; color: white; }
[data-theme="dark"] .stat-card,
[data-theme="dark"] .filter-section,
[data-theme="dark"] .table-card { background: #1f2937; border-color: #374151; }
[data-theme="dark"] .btn-refresh { background: #1f2937; border-color: #374151; color: #f3f4f6; }
[data-theme="dark"] .pagination .page-link { background: #1f2937; border-color: #374151; color: #f3f4f6; }
</style>
