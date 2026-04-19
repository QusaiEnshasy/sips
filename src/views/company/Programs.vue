<template>
  <div class="company-programs">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-2">{{ t('training_programs') }}</h2>
        <p class="text-muted mb-0">{{ t('manage_programs') }}</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn-trello" @click="openTrelloSettings">
          <i class="bi bi-trello me-2"></i>
          تكامل Trello
        </button>
        <button class="btn-accent-gradient" @click="createProgram">
          <i class="bi bi-plus-circle me-2"></i>
          {{ t('new_program') }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted mt-3">{{ t('loading') }}</p>
    </div>

    <div v-else>
      <div class="trello-program-banner mb-4">
        <div>
          <div class="small-title">مصدر مهام Trello</div>
          <h5 class="fw-bold mb-1">مهام الشركة يتم إنشاؤها من Trello</h5>
          <p class="text-muted mb-0">
            اربط لوحة وقائمة Trello بكل برنامج تدريب، ثم زامن البطاقات حتى تظهر للطلاب كمهام داخل النظام.
          </p>
        </div>
        <button class="btn-trello" @click="openTrelloSettings">
          <i class="bi bi-arrow-repeat me-2"></i>
          ربط / مزامنة
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="row g-4 mb-5">
        <div class="col-md-3" v-for="stat in programStats" :key="stat.key">
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
      <div class="filter-section mb-4">
        <div class="row g-3">
          <div class="col-md-5">
            <div class="search-wrapper">
              <i class="bi bi-search"></i>
              <input type="text" class="form-control" :placeholder="t('search_programs')" v-model="searchQuery" @input="loadPrograms" />
            </div>
          </div>
          <div class="col-md-3">
            <select class="form-select" v-model="statusFilter" @change="loadPrograms">
              <option value="all">{{ t('all_status') }}</option>
              <option value="active">{{ t('active') }}</option>
              <option value="draft">{{ t('draft') }}</option>
              <option value="completed">{{ t('completed') }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select" v-model="sortBy" @change="loadPrograms">
              <option value="newest">{{ t('newest_first') }}</option>
              <option value="oldest">{{ t('oldest_first') }}</option>
              <option value="students">{{ t('most_students') }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn-refresh w-100" @click="loadPrograms">
              <i class="bi bi-arrow-repeat me-2"></i>
              {{ t('refresh') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Programs Grid -->
      <div class="row g-4">
        <div v-for="program in programs" :key="program.id" class="col-md-6 col-lg-4">
          <div class="program-card">
            <div class="card-header d-flex justify-content-between align-items-start mb-3">
              <div>
                <span class="badge" :class="getStatusClass(program.status)">
                  {{ t(program.status) }}
                </span>
              </div>
              <div class="dropdown">
                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                  <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="#" @click.prevent="editProgram(program)">
                    <i class="bi bi-pencil me-2"></i>{{ t('edit') }}
                  </a></li>
                  <li><a class="dropdown-item" href="#" @click.prevent="duplicateProgram(program)">
                    <i class="bi bi-files me-2"></i>{{ t('duplicate') }}
                  </a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteProgram(program)">
                    <i class="bi bi-trash me-2"></i>{{ t('delete') }}
                  </a></li>
                </ul>
              </div>
            </div>

            <h5 class="fw-bold mb-2">{{ program.title }}</h5>
            <p class="text-muted small mb-3">{{ truncateText(program.description, 100) }}</p>

            <div class="program-stats mb-3">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">{{ t('students_enrolled') }}</span>
                <span class="fw-bold">{{ program.students_count || 0 }}</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">{{ t('duration') }}</span>
                <span class="fw-bold">{{ program.duration_weeks || program.duration }} {{ t('weeks') }}</span>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted small">{{ t('start_date') }}</span>
                <span class="fw-bold">{{ formatDate(program.start_date) }}</span>
              </div>
            </div>

            <div class="progress mb-3" v-if="program.progress">
              <div class="progress-bar" :class="getProgressClass(program.progress)" :style="{ width: program.progress + '%' }"></div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
              <span class="small text-muted">
                <i class="bi bi-people me-1"></i> {{ program.applicants_count || 0 }} {{ t('applicants') }}
              </span>
              <button class="btn-accent-outline btn-sm" @click="viewProgram(program)">
                {{ t('view_details') }}
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
const programs = ref([])
const searchQuery = ref('')
const statusFilter = ref('all')
const sortBy = ref('newest')
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0
})

// Stats
const programStats = ref([
  { key: 'total', icon: 'bi bi-journal-bookmark', iconClass: 'bg-primary', label: 'total_programs', value: '0' },
  { key: 'active', icon: 'bi bi-play-circle', iconClass: 'bg-success', label: 'active', value: '0' },
  { key: 'draft', icon: 'bi bi-file-earmark', iconClass: 'bg-warning', label: 'draft', value: '0' },
  { key: 'students', icon: 'bi bi-people', iconClass: 'bg-info', label: 'total_students', value: '0' }
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

// ✅ جلب البرامج من API
const loadPrograms = async () => {
  isLoading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      search: searchQuery.value,
      status: statusFilter.value !== 'all' ? statusFilter.value : null,
      sort: sortBy.value
    }
    
    const response = await companyAPI.getPrograms(params)
    const data = response.data.data
    
    programs.value = data.programs || []
    pagination.value = data.pagination || { current_page: 1, last_page: 1, per_page: 12, total: 0 }
    
    if (data.stats) {
      programStats.value = data.stats
    }
    
  } catch (error) {
    console.error('Failed to load programs:', error)
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    pagination.value.current_page = page
    loadPrograms()
  }
}

const createProgram = () => {
  router.push('/company/programs/create')
}

const openTrelloSettings = () => {
  router.push('/company/trello-settings')
}

const viewProgram = (program) => {
  router.push(`/company/programs/${program.id}`)
}

const editProgram = (program) => {
  router.push(`/company/programs/${program.id}/edit`)
}

const duplicateProgram = async (program) => {
  try {
    await companyAPI.duplicateProgram(program.id)
    await loadPrograms()
    alert(t('duplicate_program', { name: program.title }))
  } catch (error) {
    alert(t('error_occurred'))
  }
}

const deleteProgram = async (program) => {
  if (confirm(t('confirm_delete_program'))) {
    try {
      await companyAPI.deleteProgram(program.id)
      await loadPrograms()
      alert(t('program_deleted'))
    } catch (error) {
      alert(error.response?.data?.message || t('error_occurred'))
    }
  }
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-success',
    draft: 'bg-warning',
    completed: 'bg-info'
  }
  return classes[status] || 'bg-secondary'
}

const getProgressClass = (progress) => {
  if (progress >= 70) return 'bg-success'
  if (progress >= 30) return 'bg-primary'
  return 'bg-warning'
}

const truncateText = (text, maxLength) => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

onMounted(() => {
  AOS.init({ duration: 800, once: true })
  loadPrograms()
})
</script>

<style scoped>
.company-programs { padding: 30px; }
.trello-program-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
  background: linear-gradient(135deg, #eff6ff, #ffffff);
  border: 1px solid rgba(0, 121, 191, 0.18);
  border-radius: 18px;
  padding: 20px;
}
.small-title {
  color: #0079bf;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-bottom: 4px;
}
.stat-card { background: white; border-radius: 20px; padding: 24px; display: flex; align-items: center; gap: 15px; border: 1px solid #f0f0f0; }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; }
.stat-content { flex: 1; }
.stat-content p { font-size: 13px; margin-bottom: 5px; }
.stat-content h3 { font-size: 28px; }
.filter-section { background: white; border-radius: 16px; padding: 20px; border: 1px solid #f0f0f0; }
.search-wrapper { position: relative; }
.search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.search-wrapper input { padding-left: 45px; }
.btn-refresh { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; font-weight: 500; cursor: pointer; }
.btn-refresh:hover { background: #f5f3ff; border-color: #7c3aed; color: #7c3aed; }
.program-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid #f0f0f0; transition: all 0.3s ease; height: 100%; }
.program-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-color: #7c3aed; }
.badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
.progress { height: 8px; border-radius: 10px; background: #f0f0f0; }
.btn-accent-outline { background: transparent; color: #7c3aed; border: 2px solid #7c3aed; border-radius: 8px; padding: 6px 16px; font-weight: 600; font-size: 13px; transition: all 0.3s ease; cursor: pointer; }
.btn-accent-outline:hover { background: #7c3aed; color: white; transform: translateY(-2px); }
.btn-accent-gradient { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.3s ease; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-trello { background: linear-gradient(135deg, #0079bf, #026aa7); color: white; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 700; transition: all 0.3s ease; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-trello:hover { color: white; transform: translateY(-2px); box-shadow: 0 10px 22px rgba(0, 121, 191, .22); }
.pagination .page-link { background: white; border-color: #e2e8f0; color: #1e293b; cursor: pointer; }
.pagination .active .page-link { background: #7c3aed; border-color: #7c3aed; color: white; }
[data-theme="dark"] .stat-card,
[data-theme="dark"] .filter-section,
[data-theme="dark"] .program-card { background: #1f2937; border-color: #374151; }
[data-theme="dark"] .trello-program-banner { background: linear-gradient(135deg, #0f172a, #1f2937); border-color: #334155; }
[data-theme="dark"] .btn-refresh { background: #1f2937; border-color: #374151; color: #f3f4f6; }
[data-theme="dark"] .pagination .page-link { background: #1f2937; border-color: #374151; color: #f3f4f6; }

@media (max-width: 768px) {
  .trello-program-banner {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
