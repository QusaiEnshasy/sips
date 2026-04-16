<template>
  <div class="student-card" @click="$emit('click')">
    <!-- رأس البطاقة مع الصورة والحالة -->
    <div class="card-header">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar-box" :style="{ background: avatarColor }">
          {{ initials }}
        </div>
        <div>
          <h6 class="fw-bold mb-0">{{ name }}</h6>
          <small class="text-muted">{{ email }}</small>
        </div>
      </div>
      <span class="status-badge" :class="statusClass">
        <i :class="statusIcon"></i>
        {{ t(statusText) }}
      </span>
    </div>

    <!-- معلومات الطالب -->
    <div class="student-info">
      <div class="info-item">
        <i class="bi bi-building me-2"></i>
        <span>{{ company || t('no_company') }}</span>
      </div>
      <div class="info-item">
        <i class="bi bi-mortarboard me-2"></i>
        <span>{{ program || t('no_program') }}</span>
      </div>
    </div>

    <!-- أشرطة التقدم -->
    <div class="progress-section">
      <div class="progress-item">
        <div class="progress-label">
          <span>{{ t('training_hours') }}</span>
          <span class="fw-bold">{{ hoursCompleted }}/{{ hoursTotal }}h</span>
        </div>
        <div class="progress">
          <div 
            class="progress-bar" 
            :class="hoursProgressClass"
            :style="{ width: hoursPercentage + '%' }"
          ></div>
        </div>
      </div>

      <div class="progress-item">
        <div class="progress-label">
          <span>{{ t('tasks_completed') }}</span>
          <span class="fw-bold">{{ tasksCompleted }}/{{ tasksTotal }}</span>
        </div>
        <div class="progress">
          <div 
            class="progress-bar" 
            :class="tasksProgressClass"
            :style="{ width: tasksPercentage + '%' }"
          ></div>
        </div>
      </div>
    </div>

    <!-- أزرار الإجراءات -->
    <div class="card-actions" @click.stop>
      <button 
        class="btn-outline" 
        @click="$emit('view')"
      >
        <i class="bi bi-eye me-2"></i>
        {{ t('view') }}
      </button>
      <button 
        class="btn-outline" 
        @click="$emit('note')"
      >
        <i class="bi bi-chat-left-text me-2"></i>
        {{ t('note') }}
      </button>
    </div>

    <!-- محتوى إضافي -->
    <slot></slot>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()

const props = defineProps({
  // البيانات الأساسية
  name: { type: String, required: true },
  email: { type: String, required: true },
  initials: { type: String, required: true },
  
  // المعلومات الإضافية
  company: { type: String, default: '' },
  program: { type: String, default: '' },
  
  // حالة الطالب
  status: {
    type: String,
    default: 'on-track',
    validator: (value) => ['on-track', 'at-risk', 'completed', 'pending'].includes(value)
  },
  
  // تقدم التدريب
  hoursCompleted: { type: Number, default: 0 },
  hoursTotal: { type: Number, default: 240 },
  tasksCompleted: { type: Number, default: 0 },
  tasksTotal: { type: Number, default: 15 }
})

defineEmits(['click', 'view', 'note'])

// لون الصورة بناءً على الاسم
const avatarColor = computed(() => {
  const colors = ['#7c3aed', '#3b82f6', '#10b981', '#f59e0b', '#ef4444']
  const index = props.name.length % colors.length
  return colors[index]
})

// كلاس الحالة
const statusClass = computed(() => `status-${props.status}`)

// أيقونة الحالة
const statusIcon = computed(() => {
  const icons = {
    'on-track': 'bi bi-check-circle-fill',
    'at-risk': 'bi bi-exclamation-triangle-fill',
    'completed': 'bi bi-award-fill',
    'pending': 'bi bi-clock'
  }
  return icons[props.status] || 'bi bi-circle'
})

// نص الحالة
const statusText = computed(() => {
  const texts = {
    'on-track': 'on_track',
    'at-risk': 'at_risk',
    'completed': 'completed',
    'pending': 'pending'
  }
  return texts[props.status] || 'pending'
})

// حساب النسب المئوية
const hoursPercentage = computed(() => {
  if (props.hoursTotal === 0) return 0
  return Math.min(100, Math.round((props.hoursCompleted / props.hoursTotal) * 100))
})

const tasksPercentage = computed(() => {
  if (props.tasksTotal === 0) return 0
  return Math.min(100, Math.round((props.tasksCompleted / props.tasksTotal) * 100))
})

// كلاسات أشرطة التقدم
const hoursProgressClass = computed(() => {
  if (hoursPercentage.value >= 80) return 'bg-success'
  if (hoursPercentage.value >= 50) return 'bg-primary'
  if (hoursPercentage.value >= 20) return 'bg-warning'
  return 'bg-danger'
})

const tasksProgressClass = computed(() => {
  if (tasksPercentage.value >= 80) return 'bg-success'
  if (tasksPercentage.value >= 50) return 'bg-primary'
  if (tasksPercentage.value >= 20) return 'bg-warning'
  return 'bg-danger'
})
</script>

<style scoped>
.student-card {
  background: var(--card-bg);
  border-radius: 24px;
  padding: 24px;
  border: 1px solid var(--border-color);
  box-shadow: var(--card-shadow);
  transition: all 0.3s ease;
  height: 100%;
  cursor: pointer;
}

.student-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.avatar-box {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 18px;
  color: white;
  flex-shrink: 0;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.status-on-track {
  background: #f0fdf4;
  color: #22c55e;
}

.status-at-risk {
  background: #fffbeb;
  color: #f59e0b;
}

.status-completed {
  background: #e0f2fe;
  color: #0284c7;
}

.status-pending {
  background: #f1f5f9;
  color: #64748b;
}

.student-info {
  margin-bottom: 20px;
  padding: 15px 0;
  border-top: 1px solid var(--border-color);
  border-bottom: 1px solid var(--border-color);
}

.info-item {
  display: flex;
  align-items: center;
  color: var(--text-muted);
  font-size: 13px;
  margin-bottom: 8px;
}

.info-item:last-child {
  margin-bottom: 0;
}

.progress-section {
  margin-bottom: 20px;
}

.progress-item {
  margin-bottom: 15px;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: var(--text-muted);
  margin-bottom: 5px;
}

.progress {
  height: 8px;
  border-radius: 10px;
  background: var(--border-color);
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  transition: width 1s ease;
}

.card-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.btn-outline {
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 10px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-dark);
  background: var(--card-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  cursor: pointer;
}

.btn-outline:hover {
  background: var(--accent-soft);
  border-color: var(--accent);
  color: var(--accent);
}

/* Responsive */
@media (max-width: 768px) {
  .student-card {
    padding: 20px;
  }
  
  .avatar-box {
    width: 48px;
    height: 48px;
    font-size: 16px;
  }
  
  .card-actions {
    gap: 8px;
  }
  
  .btn-outline {
    padding: 8px;
    font-size: 12px;
  }
}

@media (max-width: 576px) {
  .card-header {
    flex-direction: column;
    gap: 10px;
  }
  
  .status-badge {
    align-self: flex-start;
  }
}
</style>