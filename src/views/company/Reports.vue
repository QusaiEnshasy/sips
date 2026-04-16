<template>
  <div class="company-reports">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-2">{{ t('reports') }}</h2>
        <p class="text-muted mb-0">{{ t('view_reports') }}</p>
      </div>
      <button class="btn-accent-gradient" @click="generateReport">
        <i class="bi bi-plus-circle me-2"></i>
        {{ t('generate_report') }}
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
      <div class="col-md-4" v-for="stat in reportStats" :key="stat.key">
        <div class="stat-card">
          <div class="d-flex justify-content-between">
            <div>
              <p class="text-muted mb-2">{{ t(stat.label) }}</p>
              <h3 class="fw-bold mb-0">{{ stat.value }}</h3>
            </div>
            <div class="stat-icon" :class="stat.iconClass">
              <i :class="stat.icon"></i>
            </div>
          </div>
          <div class="stat-footer mt-3">
            <small :class="stat.trendClass">
              <i :class="stat.trendIcon"></i> {{ stat.trend }}
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-5">
      <div class="col-md-6">
        <div class="chart-card">
          <h6 class="fw-bold mb-4">{{ t('applications_over_time') }}</h6>
          <div class="chart-placeholder">
            <div class="text-center text-muted py-5">
              <i class="bi bi-bar-chart-line fs-1"></i>
              <p class="mt-2">{{ t('chart_placeholder') }}</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="chart-card">
          <h6 class="fw-bold mb-4">{{ t('program_performance') }}</h6>
          <div class="chart-placeholder">
            <div class="text-center text-muted py-5">
              <i class="bi bi-pie-chart fs-1"></i>
              <p class="mt-2">{{ t('chart_placeholder') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reports List -->
    <div class="reports-list">
      <h5 class="fw-bold mb-4">{{ t('recent_reports') }}</h5>
      
      <div v-for="report in reports" :key="report.id" class="report-item">
        <div class="d-flex align-items-center gap-3">
          <div class="report-icon" :class="report.iconClass">
            <i :class="report.icon"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="fw-bold mb-1">{{ t(report.title) }}</h6>
                <p class="small text-muted mb-1">{{ report.description }}</p>
                <div class="d-flex gap-3 small text-muted">
                  <span><i class="bi bi-calendar me-1"></i>{{ report.date }}</span>
                  <span><i class="bi bi-file-earmark me-1"></i>{{ report.format }}</span>
                  <span><i class="bi bi-hdd me-1"></i>{{ report.size }}</span>
                </div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn-icon-accent btn-sm" @click="downloadReport(report)">
                  <i class="bi bi-download"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" @click="shareReport(report)">
                  <i class="bi bi-share"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from '@/composables/useI18n'
import { companyAPI } from '@/services/api/company'

const { t } = useI18n()

const reportStats = ref([])
const reports = ref([])

const loadReports = async () => {
  const response = await companyAPI.getReports()
  const data = response.data?.data || {}
  reportStats.value = data.stats || []
  reports.value = data.reports || []
}

const generateReport = () => {
  const now = new Date().toLocaleDateString()
  reports.value.unshift({
    id: Date.now(),
    title: 'monthly_applications_report',
    description: `Generated on ${now}`,
    date: now,
    format: 'PDF',
    size: '1.1 MB',
    icon: 'bi bi-file-earmark-pdf',
    iconClass: 'pdf'
  })

  downloadReport(reports.value[0])
}

const downloadReport = (report) => {
  const content = [
    `Report: ${t(report.title)}`,
    `Description: ${report.description}`,
    `Generated: ${report.date}`,
    `Format: ${report.format}`,
    '',
    `Total Reports: ${reportStats.value[0]?.value || 0}`,
    `Total Applications: ${reportStats.value[1]?.value || 0}`,
    `Active Programs: ${reportStats.value[2]?.value || 0}`
  ].join('\n')

  const blob = new Blob([content], { type: 'text/plain;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${String(report.title || 'report').replace(/[^a-z0-9-_]+/gi, '-').toLowerCase()}.txt`
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

const shareReport = (report) => {
  const text = `${t(report.title)} - ${report.description}`
  if (navigator.share) {
    navigator.share({ title: t(report.title), text }).catch(() => {})
    return
  }

  navigator.clipboard?.writeText(text)
}

onMounted(() => {
  loadReports()
})
</script>

<style scoped>
.company-reports {
  padding: 30px;
}

.stat-card {
  background: white;
  border-radius: 20px;
  padding: 24px;
  border: 1px solid #f0f0f0;
  height: 100%;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
}

.stat-footer {
  border-top: 1px solid #f0f0f0;
  padding-top: 15px;
}

.chart-card {
  background: white;
  border-radius: 20px;
  padding: 24px;
  border: 1px solid #f0f0f0;
  height: 100%;
}

.chart-placeholder {
  background: #f9fafb;
  border-radius: 12px;
}
/* زر متدرج بنفسجي مثل لون الموقع */
.btn-accent-gradient {
  background: linear-gradient(135deg, #7c3aed, #6d28d9) !important;
  color: white !important;
  border: none !important;
  border-radius: 10px !important;
  padding: 10px 24px !important;
  font-weight: 600 !important;
  font-size: 14px !important;
  transition: all 0.3s ease !important;
  cursor: pointer !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 8px !important;
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3) !important;
}
/* زر أيقونة بنفسجي */
.btn-icon-accent {
  width: 32px !important;
  height: 32px !important;
  border-radius: 8px !important;
  background: transparent !important;
  border: 2px solid #7c3aed !important;
  color: #7c3aed !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  cursor: pointer !important;
  transition: all 0.3s ease !important;
  padding: 0 !important;
}

.btn-icon-accent i {
  font-size: 16px !important;
}

.btn-icon-accent:hover {
  background: #7c3aed !important;
  color: white !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3) !important;
}

.btn-icon-accent:active {
  transform: translateY(0) !important;
}

/* حجم صغير (btn-sm) */
.btn-icon-accent.btn-sm {
  width: 28px !important;
  height: 28px !important;
}

.btn-icon-accent.btn-sm i {
  font-size: 14px !important;
}

/* للوضع الليلي */
[data-theme="dark"] .btn-icon-accent {
  border-color: #a78bfa !important;
  color: #a78bfa !important;
}

[data-theme="dark"] .btn-icon-accent:hover {
  background: #7c3aed !important;
  color: white !important;
  border-color: #7c3aed !important;
}
.btn-accent-gradient:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 6px 16px rgba(124, 58, 237, 0.4) !important;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed) !important;
}

.btn-accent-gradient:active {
  transform: translateY(0) !important;
}

.btn-accent-gradient i {
  font-size: 16px;
}

/* للوضع الليلي */
[data-theme="dark"] .btn-accent-gradient {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed) !important;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3) !important;
}
.report-item {
  background: white;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #f0f0f0;
  margin-bottom: 15px;
  transition: all 0.3s ease;
}

.report-item:hover {
  border-color: #7c3aed;
  box-shadow: 0 4px 12px rgba(124,58,237,0.1);
}

.report-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  background: #f8f9fa;
}

.report-icon.pdf {
  color: #dc2626;
}

.report-icon.excel {
  color: #16a34a;
}

[data-theme="dark"] .stat-card,
[data-theme="dark"] .chart-card,
[data-theme="dark"] .report-item {
  background: #1f2937;
  border-color: #374151;
}

[data-theme="dark"] .chart-placeholder {
  background: #111827;
}
</style>
