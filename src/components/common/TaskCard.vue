<template>
  <div 
    class="task-card" 
    :class="[taskClass, { 'featured': featured }]"
    @click="$emit('click')"
  >
    <!-- رأس البطاقة مع النوع والحالة -->
    <div class="d-flex justify-content-between align-items-start mb-3">
      <span class="badge-task" :class="typeClass">
        <i :class="typeIcon"></i>
        {{ t(typeLabel) }}
      </span>
      <span class="badge-status" :class="statusClass">
        <i :class="statusIcon"></i>
        {{ t(statusText) }}
      </span>
    </div>

    <!-- عنوان المهمة والوصف -->
    <h6 class="fw-bold mb-2">{{ title }}</h6>
    <p class="text-muted small mb-3">{{ description }}</p>

    <!-- تاريخ الاستحقاق مع علامة -->
    <div class="due-date mb-3" :class="{ 'text-danger': isOverdue }">
      <i class="bi bi-calendar-event me-2"></i>
      <span>{{ t('due_date') + ': ' + formatDate(dueDate) }}</span>
      <span v-if="isOverdue" class="badge bg-danger ms-2" v-text="t('overdue')"></span>
      <span v-else-if="isDueSoon" class="badge bg-warning text-dark ms-2" v-text="t('due_soon')"></span>
    </div>

    <!-- إحصائيات المهمة (عدد الطلاب/التقديمات) -->
    <div class="task-stats mb-3">
      <div class="stat-item">
        <i class="bi bi-people me-1"></i>
        <span>{{ totalStudents }} {{ t('students') }}</span>
      </div>
      <div class="stat-item">
        <i class="bi bi-check2-circle me-1"></i>
        <span>{{ submissions }}/{{ totalStudents }} {{ t('submitted') }}</span>
      </div>
      <div class="stat-item">
        <i class="bi bi-award me-1"></i>
        <span>{{ points }} {{ t('points') }}</span>
      </div>
    </div>

    <!-- شريط التقدم -->
    <div class="progress-container mb-3">
      <div class="progress-label">
        <span>{{ t('completion_rate') }}</span>
        <span class="fw-bold">{{ completionRate }}%</span>
      </div>
      <div class="progress">
        <div 
          class="progress-bar" 
          :class="progressClass"
          :style="{ width: completionRate + '%' }"
        ></div>
      </div>
    </div>

    <!-- أزرار الإجراءات (سلوط مخصصة) -->
    <div class="task-actions" @click.stop>
      <slot name="actions"></slot>
    </div>

    <!-- محتوى إضافي -->
    <slot></slot>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from '@/composables/useI18n'

const { t, formatDate } = useI18n()

const props = defineProps({
  // البيانات الأساسية
  title: { type: String, required: true },
  description: { type: String, default: '' },
  dueDate: { type: [String, Date], required: true },
  
  // نوع المهمة
  type: {
    type: String,
    default: 'assignment',
    validator: (value) => ['assignment', 'quiz', 'project', 'task'].includes(value)
  },
  
  // الحالة
  status: {
    type: String,
    default: 'pending',
    validator: (value) => ['completed', 'pending', 'in-progress', 'late', 'graded'].includes(value)
  },
  
  // إحصائيات
  totalStudents: { type: Number, default: 0 },
  submissions: { type: Number, default: 0 },
  points: { type: Number, default: 0 },
  
  // خصائص إضافية
  featured: { type: Boolean, default: false },
  variant: { type: String, default: 'default' }
})

defineEmits(['click'])

// تحديد كلاس المهمة حسب النوع
const taskClass = computed(() => {
  const classes = {
    assignment: 'task-assignment',
    quiz: 'task-quiz',
    project: 'task-project',
    task: 'task-default'
  }
  return classes[props.type] || 'task-default'
})

// أيقونة النوع
const typeIcon = computed(() => {
  const icons = {
    assignment: 'bi bi-pencil-square',
    quiz: 'bi bi-question-circle',
    project: 'bi bi-diagram-3',
    task: 'bi bi-check2-circle'
  }
  return icons[props.type] || 'bi bi-file-text'
})

// نص النوع
const typeLabel = computed(() => {
  const labels = {
    assignment: 'assignment',
    quiz: 'quiz',
    project: 'project',
    task: 'task'
  }
  return labels[props.type] || 'task'
})

// كلاس الحالة
const statusClass = computed(() => {
  const classes = {
    completed: 'status-completed',
    pending: 'status-pending',
    'in-progress': 'status-progress',
    late: 'status-late',
    graded: 'status-graded'
  }
  return classes[props.status] || 'status-default'
})

// أيقونة الحالة
const statusIcon = computed(() => {
  const icons = {
    completed: 'bi bi-check-circle-fill',
    pending: 'bi bi-clock',
    'in-progress': 'bi bi-arrow-repeat',
    late: 'bi bi-exclamation-triangle-fill',
    graded: 'bi bi-star-fill'
  }
  return icons[props.status] || 'bi bi-circle'
})

// نص الحالة
const statusText = computed(() => {
  const texts = {
    completed: 'completed',
    pending: 'pending',
    'in-progress': 'in_progress',
    late: 'late',
    graded: 'graded'
  }
  return texts[props.status] || 'pending'
})

// حساب نسبة الإنجاز
const completionRate = computed(() => {
  if (props.totalStudents === 0) return 0
  return Math.round((props.submissions / props.totalStudents) * 100)
})

// كلاس شريط التقدم
const progressClass = computed(() => {
  if (completionRate.value >= 80) return 'bg-success'
  if (completionRate.value >= 50) return 'bg-primary'
  if (completionRate.value >= 20) return 'bg-warning'
  return 'bg-danger'
})

// التحقق من تأخر المهمة
const isOverdue = computed(() => {
  const today = new Date()
  const due = new Date(props.dueDate)
  return due < today && props.status !== 'completed'
})

// التحقق من اقتراب الموعد
const isDueSoon = computed(() => {
  if (isOverdue.value) return false
  const today = new Date()
  const due = new Date(props.dueDate)
  const diffDays = Math.ceil((due - today) / (1000 * 60 * 60 * 24))
  return diffDays <= 3 && diffDays > 0
})
</script>

<style scoped>
.task-card {
  background: var(--card-bg);
  border-radius: 20px;
  padding: 24px;
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.task-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
}

.task-card.featured {
  border: 2px solid var(--accent);
  position: relative;
  overflow: hidden;
}

.task-card.featured::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 60px;
  height: 60px;
  background: var(--accent);
  clip-path: polygon(100% 0, 0 0, 100% 100%);
  opacity: 0.1;
}

/* أنماط حسب نوع المهمة */
.task-assignment {
  border-left: 4px solid #3b82f6;
}

.task-quiz {
  border-left: 4px solid #f59e0b;
}

.task-project {
  border-left: 4px solid #10b981;
}

.task-default {
  border-left: 4px solid #6b7280;
}

.badge-task {
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.badge-status {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.status-completed {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.status-pending {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.status-progress {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.status-late {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.status-graded {
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
}

.due-date {
  font-size: 13px;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 5px;
}

.task-stats {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  color: var(--text-muted);
  padding: 10px 0;
  border-top: 1px dashed var(--border-color);
  border-bottom: 1px dashed var(--border-color);
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 3px;
}

.progress-container {
  margin: 10px 0;
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

.task-actions {
  margin-top: auto;
  padding-top: 15px;
  display: flex;
  gap: 10px;
}

/* Responsive */
@media (max-width: 768px) {
  .task-card {
    padding: 20px;
  }
  
  .task-stats {
    flex-wrap: wrap;
    gap: 10px;
  }
}

@media (max-width: 576px) {
  .task-card {
    padding: 18px;
  }
  
  .badge-task {
    font-size: 10px;
    padding: 4px 10px;
  }
  
  .badge-status {
    font-size: 10px;
    padding: 3px 8px;
  }
}
</style>