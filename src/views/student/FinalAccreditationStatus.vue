<template>
  <div class="accreditation-page">
    <!-- رأس الصفحة -->
    <div class="page-header">
      <div class="container">
        <div class="d-flex align-items-center gap-3">
          <div class="header-icon">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-0">{{ t('accreditation_status') }}</h6>
            <small class="text-muted">{{ t('program_completion') }}</small>
          </div>
        </div>
      </div>
    </div>

    <div class="container mt-4">
      <!-- بانر التهاني -->
      <div class="approval-banner" data-aos="fade-in">
        <div class="check-circle animate__animated animate__zoomIn">
          <i class="bi bi-patch-check-fill"></i>
        </div>
        
        <div class="approved-tag mb-3">
          <i class="bi bi-shield-lock-fill me-2"></i>
          {{ t('approved') }}
        </div>

        <h2 class="fw-bold">{{ t('congratulations') }}</h2>
        <p class="text-muted">{{ t('approval_msg') }}</p>

        <div class="d-flex justify-content-center gap-4 mt-4 text-muted small flex-wrap">
          <span><i class="bi bi-person me-1"></i> Sarah Johnson</span>
          <span><i class="bi bi-hash me-1"></i> STU-2024-1589</span>
          <span><i class="bi bi-calendar-check me-1"></i> {{ t('reviewed') }}: Dec 28, 2024</span>
        </div>
      </div>

      <!-- بطاقات الإحصائيات -->
      <div class="row g-4 mb-5" data-aos="fade-up">
        <div class="col-md-3" v-for="stat in stats" :key="stat.key">
          <div class="stat-small-card">
            <div class="icon-box" :class="stat.iconClass">
              <i :class="stat.icon"></i>
            </div>
            <div>
              <small class="text-muted d-block">{{ t(stat.label) }}</small>
              <span class="fw-bold fs-5">{{ stat.value }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- التفاصيل والتعليقات -->
      <div class="row">
        <!-- العمود الأيسر - التفاصيل -->
        <div class="col-lg-8" data-aos="fade-left">
          <div class="detail-card">
            <h6 class="fw-bold mb-4">
              <i class="bi bi-journal-text text-primary me-2"></i>
              {{ t('program_details') }}
            </h6>

            <div class="detail-item">
              <span class="text-muted small">
                <i class="bi bi-briefcase me-2"></i> {{ t('program_name') }}
              </span>
              <span class="fw-bold small">{{ t('program_name_value') }}</span>
            </div>

            <div class="detail-item">
              <span class="text-muted small">
                <i class="bi bi-calendar2-check me-2"></i> {{ t('completion_date') }}
              </span>
              <span class="fw-bold small">{{ t('completion_date_value') }}</span>
            </div>

            <div class="detail-item">
              <span class="text-muted small">
                <i class="bi bi-clock-history me-2"></i> {{ t('submission_date') }}
              </span>
              <span class="fw-bold small">{{ t('submission_date_value') }}</span>
            </div>

            <div class="detail-item border-0">
              <span class="text-muted small">
                <i class="bi bi-patch-check me-2"></i> {{ t('certificate_number') }}
              </span>
              <span class="fw-bold small text-primary">CERT-2024-AWDCA-1089</span>
            </div>
          </div>

          <!-- تعليقات المراجعة -->
          <div class="review-box">
            <h6 class="fw-bold mb-3">
              <i class="bi bi-shield-check text-primary me-2"></i>
              {{ t('university_review') }}
            </h6>
            <p class="small text-muted lh-lg mb-4">{{ t('review_text') }}</p>

            <div class="d-flex align-items-center gap-3">
              <div class="reviewer-avatar">👨‍🏫</div>
              <div>
                <div class="fw-bold small">{{ t('reviewer_name') }}</div>
                <div class="text-muted small">{{ t('reviewer_role') }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- العمود الأيمن - الخطوات التالية -->
        <div class="col-lg-4" data-aos="fade-right">
          <div class="detail-card">
            <h6 class="fw-bold mb-4">
              <i class="bi bi-stars text-primary me-2"></i>
              {{ t('next_steps') }}
            </h6>

            <div class="next-step-item">
              <i class="bi bi-check-circle-fill text-success"></i>
              <span>{{ t('step_1') }}</span>
            </div>

            <div class="next-step-item">
              <i class="bi bi-check-circle-fill text-success"></i>
              <span>{{ t('step_2') }}</span>
            </div>

            <div class="next-step-item">
              <i class="bi bi-check-circle-fill text-success"></i>
              <span>{{ t('step_3') }}</span>
            </div>

            <div class="next-step-item">
              <i class="bi bi-check-circle-fill text-success"></i>
              <span>{{ t('step_4') }}</span>
            </div>
          </div>

          <!-- بطاقة المساعدة -->
          <div class="help-card">
            <h6 class="fw-bold mb-2">{{ t('need_assistance') }}</h6>
            <p class="small opacity-75 mb-4">{{ t('assistance_text') }}</p>
            <button class="btn-contact" @click="contactSupport" :disabled="isContacting">
              <span v-if="!isContacting">
                <i class="bi bi-headset me-2"></i>
                {{ t('contact_support') }}
              </span>
              <span v-else>
                <span class="spinner-border spinner-border-sm me-2"></span>
                {{ t('connecting') }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from '@/composables/useI18n'
import AOS from 'aos'

const { t } = useI18n()

// ========== بيانات الإحصائيات ==========
const stats = ref([
  {
    key: 'grade',
    icon: 'bi bi-graph-up-arrow',
    iconClass: 'bg-success-subtle text-success',
    label: 'overall_grade',
    value: '92%'
  },
  {
    key: 'tasks',
    icon: 'bi bi-check2-square',
    iconClass: 'bg-primary-subtle text-primary',
    label: 'tasks_completed',
    value: '58/60'
  },
  {
    key: 'attendance',
    icon: 'bi bi-people',
    iconClass: 'bg-info-subtle text-info',
    label: 'attendance_rate',
    value: '98%'
  },
  {
    key: 'duration',
    icon: 'bi bi-calendar3',
    iconClass: 'bg-warning-subtle text-warning',
    label: 'duration',
    value: '12 Weeks'
  }
])

// ========== حالة التواصل ==========
const isContacting = ref(false)

// ========== دوال التفاعل ==========
const contactSupport = async () => {
  isContacting.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 1500))
    alert(t('support_alert'))
  } finally {
    isContacting.value = false
  }
}

// تهيئة AOS
onMounted(() => {
  AOS.init({
    duration: 800,
    once: true,
    easing: 'ease-in-out-sine'
  })
})
</script>

<style scoped>
.accreditation-page {
  min-height: 100vh;
  background: #f9fafb;
  padding-bottom: 50px;
}

[data-theme="dark"] .accreditation-page {
  background: var(--main-bg);
}

.page-header {
  background: var(--card-bg);
  border-bottom: 1px solid var(--border-color);
  padding: 15px 0;
  margin-bottom: 30px;
}

.header-icon {
  width: 45px;
  height: 45px;
  background: var(--accent);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
}

.approval-banner {
  background: #f0fdf4;
  border: 1px solid #dcfce7;
  border-radius: 30px;
  padding: 50px;
  text-align: center;
  margin-bottom: 30px;
  position: relative;
}

[data-theme="dark"] .approval-banner {
  background: rgba(34, 197, 94, 0.1);
  border-color: rgba(34, 197, 94, 0.2);
}

.check-circle {
  width: 80px;
  height: 80px;
  background: #22c55e;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
  margin: 0 auto 20px;
  box-shadow: 0 10px 15px -3px rgba(34, 197, 94, 0.3);
}

.approved-tag {
  background: #22c55e;
  color: white;
  padding: 8px 24px;
  border-radius: 50px;
  font-weight: 600;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
}

.stat-small-card {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 20px;
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 15px;
  height: 100%;
}

.stat-small-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
}

.icon-box {
  width: 45px;
  height: 45px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.detail-card {
  background: var(--card-bg);
  border-radius: 20px;
  border: 1px solid var(--border-color);
  padding: 24px;
  height: 100%;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  padding: 15px 0;
  border-bottom: 1px solid #f8fafc;
}

[data-theme="dark"] .detail-item {
  border-bottom-color: #334155;
}

.detail-item:last-child {
  border-bottom: none;
}

.review-box {
  background: #f5f3ff;
  border: 1px solid #e9e7ff;
  border-radius: 20px;
  padding: 25px;
  margin-top: 25px;
}

[data-theme="dark"] .review-box {
  background: rgba(124, 58, 237, 0.1);
  border-color: rgba(124, 58, 237, 0.2);
}

.reviewer-avatar {
  width: 45px;
  height: 45px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  border: 2px solid var(--accent);
}

.next-step-item {
  display: flex;
  gap: 12px;
  margin-bottom: 15px;
  font-size: 14px;
  color: var(--text-dark);
  align-items: flex-start;
}

.next-step-item i {
  color: #22c55e;
  font-size: 18px;
  flex-shrink: 0;
  margin-top: 2px;
}

.help-card {
  background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
  border-radius: 20px;
  padding: 25px;
  color: white;
  margin-top: 20px;
}

.btn-contact {
  background: white;
  color: var(--accent);
  border: none;
  border-radius: 12px;
  font-weight: 700;
  width: 100%;
  padding: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-contact:hover:not(:disabled) {
  transform: scale(1.02);
  box-shadow: 0 5px 15px rgba(255, 255, 255, 0.2);
}

.btn-contact:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
  .approval-banner {
    padding: 30px 20px;
  }
  
  .check-circle {
    width: 60px;
    height: 60px;
    font-size: 30px;
  }
  
  .approved-tag {
    padding: 6px 16px;
    font-size: 13px;
  }
  
  .stat-small-card {
    padding: 15px;
  }
  
  .icon-box {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }
}

@media (max-width: 576px) {
  .approval-banner h2 {
    font-size: 24px;
  }
  
  .d-flex.gap-4 {
    gap: 15px !important;
    flex-direction: column;
    align-items: center;
  }
  
  .detail-item {
    flex-direction: column;
    gap: 5px;
  }
}
</style>