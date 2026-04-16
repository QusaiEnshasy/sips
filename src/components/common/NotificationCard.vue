<template>
  <div
    class="notif-card"
    :class="[typeClass, { 'unread': unread }]"
    @click="$emit('click')"
  >
    <!-- أيقونة الإشعار حسب النوع -->
    <div class="notif-icon" :class="iconClass">
      <i :class="typeIcon"></i>
    </div>

    <!-- محتوى الإشعار -->
    <div class="notif-content">
      <div class="notif-header">
        <h6 class="notif-title fw-bold">
          {{ title }}
          <span v-if="unread" class="unread-dot"></span>
        </h6>
        <small class="notif-time">{{ timeAgo(time) }}</small>
      </div>

      <p class="notif-desc" v-if="description">{{ description }}</p>

      <div v-if="reason" class="notif-reason">
        <strong>{{ reasonLabel }}:</strong>
        <span>{{ reason }}</span>
      </div>

      <div v-if="details" class="notif-details">
        {{ details }}
      </div>

      <div class="notif-meta">
        <span v-if="category">
          <i :class="categoryIcon"></i>
          {{ category }}
        </span>
        <span>•</span>
        <span>{{ timeAgo(time) }}</span>
      </div>
    </div>

    <!-- أزرار الإجراءات -->
    <div class="notif-actions" @click.stop>
      <button
        v-if="unread"
        class="btn-action"
        @click="$emit('mark-read')"
        :title="t('mark_as_read')"
      >
        <i class="bi bi-eye"></i>
      </button>
      <button
        class="btn-action btn-delete"
        @click="$emit('delete')"
        :title="t('delete')"
      >
        <i class="bi bi-trash3"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from '@/composables/useI18n'

const { t, timeAgo } = useI18n()

const props = defineProps({
  // البيانات الأساسية
  title: { type: String, required: true },
  description: { type: String, default: '' },
  reason: { type: String, default: '' },
  details: { type: String, default: '' },
  time: { type: [String, Date], required: true },

  // حالة الإشعار
  unread: { type: Boolean, default: false },

  // نوع الإشعار
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'info', 'warning', 'danger', 'course'].includes(value)
  },

  // التصنيف
  category: { type: String, default: '' }
})

defineEmits(['click', 'mark-read', 'delete'])

// كلاس حسب النوع
const typeClass = computed(() => `notif-${props.type}`)

// أيقونة حسب النوع
const typeIcon = computed(() => {
  const icons = {
    success: 'bi bi-check-circle-fill',
    info: 'bi bi-info-circle-fill',
    warning: 'bi bi-exclamation-circle-fill',
    danger: 'bi bi-x-circle-fill',
    course: 'bi bi-mortarboard-fill'
  }
  return icons[props.type] || 'bi bi-bell-fill'
})

// كلاس الأيقونة
const iconClass = computed(() => `bg-${props.type}-soft`)

// أيقونة التصنيف
const categoryIcon = computed(() => {
  const icons = {
    course: 'bi bi-mortarboard',
    system: 'bi bi-gear',
    achievement: 'bi bi-award',
    assignment: 'bi bi-pencil-square'
  }
  return icons[props.category] || 'bi bi-tag'
})

const reasonLabel = computed(() => (
  props.type === 'danger' || props.type === 'warning'
    ? 'سبب الرفض'
    : 'سبب الرسالة'
))
</script>

<style scoped>
.notif-card {
  background: var(--card-bg);
  border-radius: 16px;
  border: 1px solid var(--border-color);
  padding: 20px;
  margin-bottom: 12px;
  display: flex;
  gap: 16px;
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
  cursor: pointer;
  position: relative;
}

.notif-card:hover {
  transform: translateX(5px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
}

[dir="rtl"] .notif-card:hover {
  transform: translateX(-5px);
}

.notif-card.unread {
  background: var(--accent-soft);
}

/* ألوان الحدود حسب النوع */
.notif-success {
  border-left-color: #22c55e;
}

.notif-info {
  border-left-color: #3b82f6;
}

.notif-warning {
  border-left-color: #f59e0b;
}

.notif-danger {
  border-left-color: #ef4444;
}

.notif-course {
  border-left-color: #8b5cf6;
}

.notif-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.bg-success-soft {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.bg-info-soft {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.bg-warning-soft {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.bg-danger-soft {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.bg-course-soft {
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
}

.notif-content {
  flex: 1;
}

.notif-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.notif-title {
  font-size: 14px;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.unread-dot {
  width: 8px;
  height: 8px;
  background: var(--accent);
  border-radius: 50%;
}

.notif-time {
  font-size: 11px;
  color: var(--text-muted);
}

.notif-desc {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 8px;
  line-height: 1.5;
}

.notif-reason {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  font-size: 12px;
  line-height: 1.6;
  margin-bottom: 8px;
  padding: 10px 12px;
  border-radius: 10px;
  background: rgba(239, 68, 68, 0.08);
  color: #991b1b;
}

.notif-details {
  font-size: 12px;
  line-height: 1.6;
  color: var(--text-muted);
  margin-bottom: 8px;
  padding: 10px 12px;
  border-radius: 10px;
  background: var(--accent-soft);
}

.notif-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 11px;
  color: var(--text-muted);
}

.notif-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.btn-action {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: var(--card-bg);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-action:hover {
  background: var(--accent-soft);
  color: var(--accent);
  border-color: var(--accent);
}

.btn-delete:hover {
  background: #fee2e2;
  color: #ef4444;
  border-color: #fecaca;
}

/* Responsive */
@media (max-width: 576px) {
  .notif-card {
    flex-direction: column;
    padding: 16px;
  }

  .notif-actions {
    position: absolute;
    top: 16px;
    right: 16px;
  }

  [dir="rtl"] .notif-actions {
    right: auto;
    left: 16px;
  }
}
</style>
