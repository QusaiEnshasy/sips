<template>
  <div class="trainer-reports-page">
    <!-- رأس الصفحة -->
    <div class="page-header mb-4" data-aos="fade-down">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h2 class="fw-bold mb-2">{{ t('reports_analytics') }}</h2>
          <p class="text-muted mb-0">{{ t('generate_download_reports') }}</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn-refresh" @click="refreshData">
            <i class="bi bi-arrow-repeat me-2"></i>
            {{ t('refresh_data') }}
          </button>
          <button class="btn-generate" @click="openGenerateModal">
            <i class="bi bi-plus-lg me-2"></i>
            {{ t('generate_report') }}
          </button>
        </div>
      </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="row g-4 mb-5">
      <div class="col-md-3" v-for="stat in reportStats" :key="stat.key" data-aos="fade-up">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div class="stat-icon" :class="stat.iconClass">
              <i :class="stat.icon"></i>
            </div>
            <span class="trend" :class="stat.trendClass">
              <i :class="stat.trendIcon"></i> {{ stat.trend }}
            </span>
          </div>
          <div class="stat-content">
            <p class="text-muted mb-1">{{ t(stat.label) }}</p>
            <h3 class="fw-bold mb-0">{{ stat.value }}</h3>
          </div>
        </div>
      </div>
    </div>

    <!-- شريط البحث والتصفية -->
    <div class="row g-3 mb-4" data-aos="fade-up">
      <div class="col-md-6">
        <div class="search-wrapper">
          <i class="bi bi-search"></i>
          <input 
            type="text" 
            class="form-control" 
            :placeholder="t('search_reports')"
            v-model="searchQuery"
          />
        </div>
      </div>
      <div class="col-md-3">
        <select class="form-select" v-model="dateFilter">
          <option value="month">{{ t('this_month') }}</option>
          <option value="last-month">{{ t('last_month') }}</option>
          <option value="quarter">{{ t('this_quarter') }}</option>
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select" v-model="programFilter">
          <option value="all">{{ t('all_programs') }}</option>
          <option value="web">{{ t('web_development') }}</option>
          <option value="data">{{ t('data_science') }}</option>
        </select>
      </div>
    </div>

    <!-- قائمة التقارير -->
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="reports-list" data-aos="fade-right">
          <h5 class="fw-bold mb-4">
            <i class="bi bi-file-text text-primary me-2"></i>
            {{ t('available_reports') }} ({{ filteredReports.length }})
          </h5>

          <div v-for="report in filteredReports" :key="report.id" class="report-item">
            <div class="d-flex gap-3 flex-wrap">
              <!-- أيقونة التقرير -->
              <div class="report-icon" :class="report.iconClass">
                <i :class="report.icon"></i>
              </div>

              <!-- محتوى التقرير -->
              <div class="report-content flex-grow-1">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                  <div>
                    <h6 class="fw-bold mb-1">
                      {{ t(report.title) }}
                      <span class="badge ms-2" :class="report.badgeClass">
                        {{ t(report.category) }}
                      </span>
                    </h6>
                    <p class="small text-muted mb-2">{{ t(report.description) }}</p>
                  </div>
                </div>

                <!-- معلومات الملف -->
                <div class="report-meta">
                  <span><i class="far fa-calendar-alt me-1"></i> {{ t('last_generated') }}: {{ report.lastGenerated }}</span>
                  <span><i :class="report.formatIcon"></i> {{ report.format }}</span>
                  <span><i class="fas fa-hdd me-1"></i> {{ report.size }}</span>
                </div>

                <!-- أزرار الإجراءات -->
                <div class="report-actions">
                  <button class="btn-action" @click="downloadReport(report)">
                    <i class="bi bi-download me-1"></i> {{ t('download') }}
                  </button>
                  <button class="btn-action" @click="previewReport(report)">
                    <i class="bi bi-eye me-1"></i> {{ t('preview') }}
                  </button>
                  <button class="btn-action" @click="shareReport(report)">
                    <i class="bi bi-share me-1"></i> {{ t('share') }}
                  </button>
                  <button class="btn-action" @click="printReport(report)">
                    <i class="bi bi-printer me-1"></i> {{ t('print') }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- العمود الجانبي -->
      <div class="col-lg-4">
        <!-- توزيع الأداء -->
        <div class="sidebar-card mb-4" data-aos="fade-left">
          <h6 class="fw-bold mb-4">
            <i class="far fa-clock text-primary me-2"></i>
            {{ t('performance_distribution') }}
          </h6>

          <div v-for="(item, index) in performanceData" :key="index" class="mb-3">
            <div class="d-flex justify-content-between small mb-1">
              <span>{{ t(item.label) }}</span>
              <span>{{ item.count }} {{ t('students') }}</span>
            </div>
            <div class="progress">
              <div 
                class="progress-bar" 
                :class="item.barClass"
                :style="{ width: item.percentage + '%' }"
              ></div>
            </div>
          </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="sidebar-card mb-4" data-aos="fade-left" data-aos-delay="100">
          <h6 class="fw-bold mb-3">
            <i class="fas fa-bolt text-primary me-2"></i>
            {{ t('quick_stats') }}
          </h6>
          <ul class="list-unstyled">
            <li v-for="stat in quickStats" :key="stat.key" class="stat-item">
              <span class="text-muted">
<i :class="[stat.icon, stat.iconClass, 'me-2']"></i>
                {{ t(stat.label) }}
              </span>
              <span class="fw-bold">{{ stat.value }}</span>
            </li>
          </ul>
        </div>

        <!-- خيارات التصدير -->
        <div class="sidebar-card" data-aos="fade-left" data-aos-delay="200">
          <h6 class="fw-bold mb-3">
            <i class="fas fa-file-export text-success me-2"></i>
            {{ t('export_options') }}
          </h6>
          <div class="d-grid gap-2">
            <button class="btn-export" @click="exportReport('pdf')">
              <i class="far fa-file-pdf text-danger me-2"></i>
              {{ t('export_pdf') }}
            </button>
            <button class="btn-export" @click="exportReport('excel')">
              <i class="far fa-file-excel text-success me-2"></i>
              {{ t('export_excel') }}
            </button>
            <button class="btn-export" @click="exportReport('csv')">
              <i class="fas fa-file-csv text-info me-2"></i>
              {{ t('export_csv') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- مودال إنشاء تقرير -->
    <Teleport to="body">
      <div v-if="showGenerateModal" class="modal-overlay" @click.self="closeGenerateModal">
        <div class="modal-content animate__animated animate__fadeInUp">
          <div class="modal-header">
            <h5 class="fw-bold mb-0">{{ t('generate_new_report') }}</h5>
            <button class="btn-close" @click="closeGenerateModal">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="generateReport">
              <div class="mb-3">
                <label class="form-label fw-bold">{{ t('report_type') }}</label>
                <select class="form-select" v-model="newReport.type">
                  <option value="performance">{{ t('performance_report') }}</option>
                  <option value="progress">{{ t('progress_report') }}</option>
                  <option value="completion">{{ t('completion_report') }}</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">{{ t('date_range') }}</label>
                <select class="form-select" v-model="newReport.range">
                  <option value="week">{{ t('this_week') }}</option>
                  <option value="month">{{ t('this_month') }}</option>
                  <option value="quarter">{{ t('this_quarter') }}</option>
                  <option value="year">{{ t('this_year') }}</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">{{ t('format') }}</label>
                <div class="d-flex gap-3">
                  <label class="format-option">
                    <input type="radio" value="pdf" v-model="newReport.format" />
                    <i class="far fa-file-pdf text-danger"></i>
                    PDF
                  </label>
                  <label class="format-option">
                    <input type="radio" value="excel" v-model="newReport.format" />
                    <i class="far fa-file-excel text-success"></i>
                    Excel
                  </label>
                  <label class="format-option">
                    <input type="radio" value="csv" v-model="newReport.format" />
                    <i class="fas fa-file-csv text-info"></i>
                    CSV
                  </label>
                </div>
              </div>

              <button type="submit" class="btn-generate-modal w-100" :disabled="isGenerating">
                <span v-if="!isGenerating">
                  <i class="bi bi-file-earmark-plus me-2"></i>
                  {{ t('generate_report') }}
                </span>
                <span v-else>
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  {{ t('generating') }}
                </span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from '@/composables/useI18n'
import { supervisorAPI } from '@/services/api/supervisor'
import AOS from 'aos'

const { t } = useI18n()

// ========== إحصائيات التقارير ==========
const reportStats = ref([])

// ========== بيانات التقارير ==========
const reports = ref([])

// ========== بيانات الأداء ==========
const performanceData = ref([])

// ========== إحصائيات سريعة ==========
const quickStats = ref([])

// ========== التصفية ==========
const searchQuery = ref('')
const dateFilter = ref('month')
const programFilter = ref('all')

const filteredReports = computed(() => {
  return reports.value.filter(report => 
    t(report.title).toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    t(report.description).toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const loadReports = async () => {
  const response = await supervisorAPI.getReports()
  const data = response.data?.data || {}
  reportStats.value = data.stats || []
  reports.value = data.reports || []
  performanceData.value = data.performanceData || []
  quickStats.value = data.quickStats || []
}

// ========== مودال إنشاء تقرير ==========
const showGenerateModal = ref(false)
const isGenerating = ref(false)

const newReport = ref({
  type: 'performance',
  range: 'month',
  format: 'pdf'
})

const openGenerateModal = () => {
  showGenerateModal.value = true
  document.body.style.overflow = 'hidden'
}

const closeGenerateModal = () => {
  showGenerateModal.value = false
  document.body.style.overflow = ''
}

const generateReport = async () => {
  isGenerating.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 600))

    const nextId = Date.now()
    const format = String(newReport.value.format || 'pdf').toUpperCase()
    const generatedAt = new Date().toLocaleDateString()

    reports.value.unshift({
      id: nextId,
      title: reportTitleByType(newReport.value.type),
      description: reportDescriptionByType(newReport.value.type),
      category: reportCategoryByType(newReport.value.type),
      badgeClass: reportBadgeByType(newReport.value.type),
      icon: reportIconByType(newReport.value.type),
      iconClass: 'bg-light text-primary',
      lastGenerated: generatedAt,
      format,
      formatIcon: reportFormatIcon(format),
      size: estimateReportSize(format)
    })

    closeGenerateModal()
    downloadReport(reports.value[0])
  } finally {
    isGenerating.value = false
  }
}

// ========== دوال التفاعل ==========
const refreshData = () => {
  loadReports()
}

const downloadReport = (report) => {
  const content = buildReportContent(report)
  const blob = new Blob([content], { type: 'text/plain;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  const safeTitle = String(report.title || 'report').replace(/[^a-z0-9-_]+/gi, '-').toLowerCase()
  link.href = url
  link.download = `${safeTitle}.${normalizeFormat(report.format)}`
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

const previewReport = (report) => {
  const content = buildReportContent(report).replace(/\n/g, '<br>')
  const previewWindow = window.open('', '_blank')
  if (!previewWindow) return
  previewWindow.document.write(`<html><head><title>${t(report.title)}</title></head><body style="font-family:Arial;padding:24px;">${content}</body></html>`)
  previewWindow.document.close()
}

const shareReport = (report) => {
  if (navigator.share) {
    navigator.share({
      title: t(report.title),
      text: buildReportContent(report)
    }).catch(() => {})
    return
  }

  navigator.clipboard?.writeText(buildReportContent(report))
}

const printReport = (report) => {
  const printWindow = window.open('', '_blank')
  if (!printWindow) return
  printWindow.document.write(`<html><head><title>${t(report.title)}</title></head><body style="font-family:Arial;padding:24px;white-space:pre-wrap;">${buildReportContent(report)}</body></html>`)
  printWindow.document.close()
  printWindow.print()
}

const exportReport = (format) => {
  if (!reports.value.length) return
  downloadReport({ ...reports.value[0], format: format.toUpperCase() })
}

const reportTitleByType = (type) => ({
  performance: 'student_performance_report',
  progress: 'program_progress_summary',
  completion: 'student_performance_report'
}[type] || 'student_performance_report')

const reportDescriptionByType = (type) => ({
  performance: 'performance_report_desc',
  progress: 'progress_summary_desc',
  completion: 'progress_summary_desc'
}[type] || 'performance_report_desc')

const reportCategoryByType = (type) => ({
  performance: 'student',
  progress: 'program',
  completion: 'student'
}[type] || 'student')

const reportBadgeByType = (type) => ({
  performance: 'bg-primary-subtle text-primary',
  progress: 'bg-warning-subtle text-warning',
  completion: 'bg-success-subtle text-success'
}[type] || 'bg-primary-subtle text-primary')

const reportIconByType = (type) => ({
  performance: 'fas fa-user-graduate',
  progress: 'fas fa-book',
  completion: 'fas fa-check-circle'
}[type] || 'fas fa-file-lines')

const reportFormatIcon = (format) => ({
  PDF: 'far fa-file-pdf text-danger',
  EXCEL: 'far fa-file-excel text-success',
  CSV: 'fas fa-file-csv text-info'
}[format] || 'far fa-file')

const estimateReportSize = (format) => ({
  PDF: '1.2 MB',
  EXCEL: '980 KB',
  CSV: '320 KB'
}[format] || '600 KB')

const normalizeFormat = (format) => String(format || 'txt').toLowerCase() === 'excel'
  ? 'xlsx'
  : String(format || 'txt').toLowerCase()

const buildReportContent = (report) => {
  return [
    `Report: ${t(report.title)}`,
    `Description: ${t(report.description)}`,
    `Generated: ${report.lastGenerated}`,
    `Format: ${report.format}`,
    '',
    'Summary',
    `Total Students: ${reportStats.value[0]?.value || 0}`,
    `Average Grade: ${reportStats.value[1]?.value || 0}`,
    `Completion Rate: ${reportStats.value[2]?.value || 0}`,
    `Active Tasks: ${reportStats.value[3]?.value || 0}`,
  ].join('\n')
}

// تهيئة AOS
onMounted(() => {
  AOS.init({
    duration: 800,
    once: true
  })
  loadReports()
})
</script>

<style scoped>
.trainer-reports-page {
  padding: 20px 0;
}

.btn-refresh,
.btn-generate {
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.btn-refresh {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  color: var(--text-dark);
}

.btn-refresh:hover {
  background: var(--accent-soft);
  border-color: var(--accent);
  color: var(--accent);
}

.btn-generate {
  background: var(--accent);
  color: white;
}

.btn-generate:hover {
  background: #6d28d9;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(124, 58, 237, 0.3);
}

.stat-card {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 20px;
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
}

.stat-icon {
  width: 45px;
  height: 45px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.trend {
  font-size: 12px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 6px;
}

.trend-up {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.trend-down {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.stat-content {
  margin-top: 15px;
}

.search-wrapper {
  position: relative;
}

.search-wrapper i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.search-wrapper input {
  padding-left: 45px;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  background: var(--input-bg);
  color: var(--text-dark);
}

.form-select {
  border-radius: 12px;
  border: 1px solid var(--border-color);
  background: var(--input-bg);
  color: var(--text-dark);
  padding: 10px;
}

.report-item {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 20px;
  border: 1px solid var(--border-color);
  margin-bottom: 15px;
  transition: all 0.3s ease;
}

.report-item:hover {
  box-shadow: var(--hover-shadow);
}

.report-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.report-meta {
  display: flex;
  gap: 20px;
  font-size: 12px;
  color: var(--text-muted);
  margin: 10px 0;
  flex-wrap: wrap;
}

.report-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-action {
  background: transparent;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
}

.btn-action:hover {
  background: var(--accent-soft);
  border-color: var(--accent);
  color: var(--accent);
}

.sidebar-card {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 20px;
  border: 1px solid var(--border-color);
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--border-color);
}

.stat-item:last-child {
  border-bottom: none;
}

.btn-export {
  background: transparent;
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 10px;
  text-align: left;
  color: var(--text-dark);
  transition: all 0.2s ease;
  cursor: pointer;
}

.btn-export:hover {
  background: var(--accent-soft);
  border-color: var(--accent);
}

/* مودال */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1100;
  padding: 20px;
}

.modal-content {
  background: var(--card-bg);
  border-radius: 24px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 24px;
}

.btn-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.format-option {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 8px 12px;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  cursor: pointer;
}

.format-option input {
  margin-right: 5px;
}

.btn-generate-modal {
  background: var(--accent);
  color: white;
  border: none;
  border-radius: 10px;
  padding: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-generate-modal:hover:not(:disabled) {
  background: #6d28d9;
  transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
  .report-actions {
    flex-wrap: wrap;
  }
  
  .btn-action {
    flex: 1;
  }
  
  .report-meta {
    gap: 10px;
  }
}

@media (max-width: 576px) {
  .d-flex.justify-content-between {
    flex-direction: column;
    align-items: flex-start !important;
  }
  
  .btn-refresh,
  .btn-generate {
    width: 100%;
  }
}
</style>
