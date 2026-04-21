<template>
  <div class="company-dashboard">
    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">{{ t('loading') }}</span>
      </div>
      <p class="text-muted mt-3">{{ t('loading_dashboard') }}</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state text-center py-5">
      <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
      <p class="text-muted mt-3">{{ error }}</p>
      <button class="btn btn-primary mt-2" @click="loadDashboard">
        <i class="bi bi-arrow-repeat me-2"></i>{{ t('retry') }}
      </button>
    </div>

    <div v-else>
      <!-- Header -->
      <div class="dashboard-header mb-5" data-aos="fade-down">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h2 class="fw-bold mb-2">{{ t('company_dashboard') }}</h2>
            <p class="text-muted mb-0">{{ t('manage_training') }}</p>
            <div v-if="companyInfo.verified !== undefined" class="mt-2">
              <span v-if="companyInfo.verified" class="badge bg-success">
                <i class="bi bi-check-circle me-1"></i>{{ t('verified_company') }}
              </span>
              <span v-else class="badge bg-warning">
                <i class="bi bi-clock me-1"></i>{{ t('pending_verification') }}
              </span>
            </div>
          </div>
          <div class="d-flex gap-3">
            <button class="btn-accent-outline" @click="openTrelloSettings">
              <i class="bi bi-trello me-2"></i>
              Trello
            </button>
            <button class="btn-accent-gradient" @click="createProgram" :disabled="!companyInfo.verified">
              <i class="bi bi-plus-circle me-2"></i>
              {{ t('new_program') }}
            </button>
          </div>
        </div>
      </div>

      <div class="trello-hub mb-5" data-aos="fade-up">
        <div class="trello-hub-content">
          <div class="trello-mark">
            <i class="bi bi-trello"></i>
          </div>
          <div>
            <p class="eyebrow mb-1">Trello للشركة</p>
            <h4 class="fw-bold mb-2">اربط Trello وزامن مهام الشركة</h4>
            <p class="mb-0 text-muted">
              اربط لوحة Trello الحقيقية، اختر القائمة الخاصة بكل برنامج تدريب، ثم زامن البطاقات لتظهر كمهام للطلاب داخل النظام.
            </p>
          </div>
        </div>
        <button class="btn-trello-primary" @click="openTrelloSettings">
          <i class="bi bi-plug me-2"></i>
          فتح تكامل Trello
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="row g-4 mb-5">
        <div class="col-sm-6 col-md-3" v-for="stat in stats" :key="stat.key">
          <div class="stat-card" data-aos="fade-up" :data-aos-delay="stat.delay">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="text-muted mb-2">{{ t(stat.label) }}</p>
                <h3 class="fw-bold mb-0">{{ stat.value }}</h3>
              </div>
              <div class="stat-icon" :class="`bg-${stat.variant}-light`">
                <i :class="stat.icon" :style="{ color: `var(--${stat.variant})` }"></i>
              </div>
            </div>
            <div class="stat-footer mt-3">
              <small :class="stat.trendClass">
                <i :class="stat.trendIcon"></i>
                {{ stat.trend }}
              </small>
              <small class="text-muted ms-2">{{ t('vs_last_month') }}</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Programs -->
      <div class="recent-programs mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <h5 class="fw-bold">
            <i class="bi bi-journal-bookmark me-2 text-primary"></i>
            {{ t('recent_programs') }}
          </h5>
          <router-link to="/company/programs" class="view-all">
            {{ t('view_all') }} <i class="bi bi-arrow-right ms-1"></i>
          </router-link>
        </div>

        <div v-if="recentPrograms.length === 0" class="empty-state text-center py-5">
          <i class="bi bi-folder2-open fs-1 text-muted"></i>
          <p class="text-muted mt-3">{{ t('no_programs_yet') }}</p>
          <button class="btn-accent-outline mt-2" @click="createProgram">
            <i class="bi bi-plus-circle me-2"></i>
            {{ t('create_first_program') }}
          </button>
        </div>

        <div v-else class="row g-4">
          <div class="col-md-6 col-lg-4" v-for="program in recentPrograms" :key="program.id" data-aos="fade-up">
            <div class="program-card">
              <div class="d-flex justify-content-between mb-3">
                <span class="badge" :class="getProgramStatusClass(program.status)">
                  {{ getProgramStatusText(program.status) }}
                </span>
                <span class="text-muted small">{{ formatDate(program.created_at) }}</span>
              </div>
              <h6 class="fw-bold mb-2">{{ program.title }}</h6>
              <p class="text-muted small mb-3">{{ truncateText(program.description, 80) }}</p>
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex gap-3">
                  <span><i class="bi bi-people me-1"></i> {{ program.applicants_count || 0 }}</span>
                  <span><i class="bi bi-calendar-week me-1"></i> {{ program.duration_weeks || 0 }} {{ t('weeks') }}</span>
                </div>
                <button class="btn-accent-outline btn-sm" @click="viewProgram(program)">
                  {{ t('view') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Applicants -->
      <div class="recent-applicants">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <h5 class="fw-bold">
            <i class="bi bi-people me-2 text-primary"></i>
            {{ t('recent_applicants') }}
          </h5>
          <router-link to="/company/applicants" class="view-all">
            {{ t('view_all') }} <i class="bi bi-arrow-right ms-1"></i>
          </router-link>
        </div>

        <div v-if="recentApplicants.length === 0" class="empty-state text-center py-5">
          <i class="bi bi-inbox fs-1 text-muted"></i>
          <p class="text-muted mt-3">{{ t('no_applicants_yet') }}</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ t('student') }}</th>
                <th>{{ t('program') }}</th>
                <th>{{ t('applied_date') }}</th>
                <th>{{ t('status') }}</th>
                <th>{{ t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="applicant in recentApplicants" :key="applicant.id">
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar" :style="{ background: getRandomColor(applicant.student_name) }">
                      {{ getInitials(applicant.student_name) }}
                    </div>
                    <div>
                      <span class="fw-bold">{{ applicant.student_name }}</span>
                      <div class="text-muted small">{{ applicant.student_email }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ applicant.program_title }}</td>
                <td>{{ formatDate(applicant.applied_at) }}</td>
                <td>
                  <span class="badge" :class="getApplicantStatusClass(applicant.status)">
                    {{ getApplicantStatusText(applicant.status) }}
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <button class="btn-icon-accent btn-sm" @click="viewApplicant(applicant)" :title="t('view_details')">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button
                      v-if="applicant.board_url"
                      class="btn-icon-success btn-sm"
                      @click="openBoard(applicant)"
                      title="Open Board"
                    >
                      <i class="bi bi-kanban"></i>
                    </button>
                    <button v-if="applicant.status === 'pending'" class="btn-icon-success btn-sm" @click="reviewApplicant(applicant)" :title="t('review')">
                      <i class="bi bi-check2-square"></i>
                    </button>
                  </div>
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { companyAPI } from '@/services/api/company'
import AOS from 'aos'

const { t, formatDate } = useI18n()
const router = useRouter()

// State
const isLoading = ref(false)
const error = ref(null)
const stats = ref([])
const recentPrograms = ref([])
const recentApplicants = ref([])
const companyInfo = ref({
  verified: false
})

// Helper functions
const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

const getRandomColor = (name) => {
  const colors = ['#7c3aed', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec489a']
  if (!name) return colors[0]
  const index = name.length % colors.length
  return colors[index]
}

const truncateText = (text, maxLength) => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

const getProgramStatusClass = (status) => {
  const statusMap = {
    'active': 'bg-success',
    'pending': 'bg-warning',
    'completed': 'bg-secondary',
    'cancelled': 'bg-danger'
  }
  return statusMap[status] || 'bg-secondary'
}

const getProgramStatusText = (status) => {
  const statusMap = {
    'active': t('active'),
    'pending': t('pending'),
    'completed': t('completed'),
    'cancelled': t('cancelled')
  }
  return statusMap[status] || status
}

const getApplicantStatusClass = (status) => {
  const statusMap = {
    'accepted': 'bg-success',
    'pending': 'bg-warning',
    'rejected': 'bg-danger',
    'in_review': 'bg-info'
  }
  return statusMap[status] || 'bg-secondary'
}

const getApplicantStatusText = (status) => {
  const statusMap = {
    'accepted': t('accepted'),
    'pending': t('pending_review'),
    'rejected': t('rejected'),
    'in_review': t('in_review')
  }
  return statusMap[status] || status
}

// جلب بيانات لوحة التحكم من API
const loadDashboard = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await companyAPI.getDashboard()
    
    if (response.data && response.data.status === 'success') {
      const data = response.data.data
      
      // معلومات الشركة
      if (data.company) {
        companyInfo.value = {
          verified: data.company.is_verified || false,
          name: data.company.name
        }
      }
      
      // الإحصائيات
      if (data.stats && Array.isArray(data.stats)) {
        stats.value = data.stats
      } else {
        // Fallback stats
        stats.value = [
          { 
            key: 'total_programs', 
            icon: 'bi bi-journal-bookmark', 
            label: 'total_programs', 
            value: data.total_programs || 0, 
            trend: '+12%', 
            trendIcon: 'bi bi-arrow-up', 
            trendClass: 'text-success', 
            variant: 'primary', 
            delay: 100 
          },
          { 
            key: 'active_programs', 
            icon: 'bi bi-play-circle', 
            label: 'active_programs', 
            value: data.active_programs || 0, 
            trend: '+5%', 
            trendIcon: 'bi bi-arrow-up', 
            trendClass: 'text-success', 
            variant: 'success', 
            delay: 200 
          },
          { 
            key: 'total_applicants', 
            icon: 'bi bi-people', 
            label: 'total_applicants', 
            value: data.total_applicants || 0, 
            trend: '+23%', 
            trendIcon: 'bi bi-arrow-up', 
            trendClass: 'text-success', 
            variant: 'info', 
            delay: 300 
          },
          { 
            key: 'pending_reviews', 
            icon: 'bi bi-clock-history', 
            label: 'pending_reviews', 
            value: data.pending_reviews || 0, 
            trend: '-2%', 
            trendIcon: 'bi bi-arrow-down', 
            trendClass: 'text-danger', 
            variant: 'warning', 
            delay: 400 
          }
        ]
      }
      
      // البرامج الحديثة
      recentPrograms.value = data.recent_programs || []
      
      // المتقدمين الحديثين
      recentApplicants.value = data.recent_applicants || []
      
    } else {
      error.value = response.data?.message || t('error_loading_dashboard')
    }
    
  } catch (err) {
    console.error('Failed to load dashboard:', err)
    error.value = err.response?.data?.message || t('error_loading_dashboard')
  } finally {
    isLoading.value = false
  }
}

const openTrelloSettings = () => {
  router.push('/company/trello-settings')
}

const createProgram = () => {
  if (!companyInfo.value.verified) {
    alert(t('company_not_verified_message'))
    return
  }
  router.push('/company/programs/create')
}

const viewProgram = (program) => {
  router.push(`/company/programs/${program.id}`)
}

const viewApplicant = () => {
  router.push('/company/training-tasks')
}

const openBoard = () => {
  router.push('/company/training-tasks')
}

const reviewApplicant = () => {
  router.push('/company/training-tasks')
}

onMounted(() => {
  AOS.init({ duration: 800, once: true })
  loadDashboard()
})
</script>

<style scoped>
.company-dashboard { padding: 20px 0; }

.trello-hub {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
  background:
    radial-gradient(circle at 10% 10%, rgba(0, 121, 191, 0.18), transparent 35%),
    linear-gradient(135deg, #f0f9ff, #ffffff 48%, #eef2ff);
  border: 1px solid rgba(0, 121, 191, 0.18);
  border-radius: 24px;
  padding: 24px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
}

.trello-hub-content {
  display: flex;
  align-items: center;
  gap: 18px;
}

.trello-mark {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  background: linear-gradient(135deg, #0079bf, #026aa7);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  flex-shrink: 0;
}

.eyebrow {
  color: #0079bf;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.btn-trello-primary {
  border: none;
  border-radius: 14px;
  background: linear-gradient(135deg, #0079bf, #005f94);
  color: white;
  padding: 13px 22px;
  font-weight: 800;
  white-space: nowrap;
  box-shadow: 0 12px 24px rgba(0, 121, 191, 0.24);
}

.btn-trello-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 30px rgba(0, 121, 191, 0.32);
}

.error-state {
  background: var(--card-bg);
  border-radius: 24px;
  padding: 40px;
  margin: 20px 0;
}

.empty-state {
  background: var(--card-bg);
  border-radius: 20px;
  padding: 40px;
  text-align: center;
}

.trello-quick-card {
  background: var(--card-bg);
  border-radius: 16px;
  border: 1px solid var(--border-color);
  box-shadow: var(--card-shadow);
  padding: 18px 20px;
}

.stat-card { 
  background: var(--card-bg); 
  border-radius: 20px; 
  padding: 24px; 
  box-shadow: var(--card-shadow); 
  border: 1px solid var(--border-color); 
  transition: transform 0.2s, box-shadow 0.2s; 
  height: 100%; 
}
.stat-card:hover { 
  transform: translateY(-5px); 
  box-shadow: var(--hover-shadow); 
}

.stat-icon { 
  width: 48px; 
  height: 48px; 
  border-radius: 12px; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  font-size: 24px; 
}

.bg-primary-light { background: rgba(124, 58, 237, 0.1); }
.bg-success-light { background: rgba(34, 197, 94, 0.1); }
.bg-info-light { background: rgba(59, 130, 246, 0.1); }
.bg-warning-light { background: rgba(245, 158, 11, 0.1); }

.stat-footer { 
  border-top: 1px solid var(--border-color); 
  padding-top: 15px; 
}

.program-card { 
  background: var(--card-bg); 
  border-radius: 16px; 
  padding: 20px; 
  border: 1px solid var(--border-color); 
  transition: all 0.2s; 
  height: 100%; 
}
.program-card:hover { 
  border-color: var(--accent); 
  box-shadow: var(--hover-shadow); 
}

.view-all { 
  color: var(--accent); 
  text-decoration: none; 
  font-weight: 500; 
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.view-all:hover { 
  text-decoration: underline; 
}

.btn-accent-outline { 
  background: transparent; 
  color: var(--accent); 
  border: 2px solid var(--accent); 
  border-radius: 8px; 
  padding: 6px 16px; 
  font-weight: 600; 
  font-size: 13px; 
  transition: all 0.3s ease; 
  cursor: pointer; 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  gap: 6px; 
}
.btn-accent-outline:hover:not(:disabled) { 
  background: var(--accent); 
  color: white; 
  transform: translateY(-2px); 
}
.btn-accent-outline:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-accent-gradient { 
  background: linear-gradient(135deg, #7c3aed, #6d28d9); 
  color: white; 
  border: none; 
  border-radius: 10px; 
  padding: 10px 24px; 
  font-weight: 600; 
  font-size: 14px; 
  transition: all 0.3s ease; 
  cursor: pointer; 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  gap: 8px; 
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); 
}
.btn-accent-gradient:hover:not(:disabled) { 
  transform: translateY(-2px); 
  box-shadow: 0 6px 16px rgba(124, 58, 237, 0.4); 
}
.btn-accent-gradient:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-icon-accent { 
  width: 32px; 
  height: 32px; 
  border-radius: 8px; 
  background: transparent; 
  border: 1px solid var(--accent); 
  color: var(--accent); 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  cursor: pointer; 
  transition: all 0.3s ease; 
  padding: 0; 
}
.btn-icon-accent:hover { 
  background: var(--accent); 
  color: white; 
  transform: translateY(-2px); 
}

.btn-icon-success { 
  width: 32px; 
  height: 32px; 
  border-radius: 8px; 
  background: transparent; 
  border: 1px solid #22c55e; 
  color: #22c55e; 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  cursor: pointer; 
  transition: all 0.3s ease; 
  padding: 0; 
}
.btn-icon-success:hover { 
  background: #22c55e; 
  color: white; 
  transform: translateY(-2px); 
}

.avatar { 
  width: 40px; 
  height: 40px; 
  border-radius: 50%; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  color: white; 
  font-size: 14px; 
  font-weight: 600; 
  flex-shrink: 0;
}

.table { 
  margin-bottom: 0; 
}
.table th { 
  font-weight: 600; 
  color: var(--text-muted); 
  border-bottom-width: 1px; 
  border-color: var(--border-color);
  padding: 12px;
}
.table td {
  padding: 12px;
  vertical-align: middle;
}
.table tbody tr:hover {
  background: var(--accent-soft);
}

.badge { 
  padding: 6px 12px; 
  border-radius: 20px; 
  font-weight: 500; 
  font-size: 12px;
}

.text-success { color: #22c55e; }
.text-danger { color: #ef4444; }

@media (max-width: 768px) {
  .company-dashboard { padding: 15px; }
  .trello-hub,
  .trello-hub-content {
    align-items: flex-start;
    flex-direction: column;
  }
  .btn-trello-primary {
    width: 100%;
    justify-content: center;
  }
  .stat-card { padding: 20px; }
  .table-responsive {
    margin: 0 -15px;
    width: calc(100% + 30px);
  }
}
</style>

