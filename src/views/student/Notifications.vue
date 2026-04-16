<template>
  <div class="student-notifications-page">
    <!-- Header -->
    <div class="page-header mb-4">
      <h2 class="fw-bold">{{ t('notifications') }}</h2>
      <p class="text-muted">{{ t('your_notifications') }}</p>
    </div>

    <!-- Notifications List -->
    <div class="notifications-container">
      <div v-if="notifications.length === 0" class="empty-state text-center py-5">
        <i class="bi bi-bell-slash fs-1 text-muted"></i>
        <p class="text-muted mt-3">{{ t('no_notifications') }}</p>
      </div>

      <div v-for="notif in notifications" :key="notif.id" class="notification-card mb-3">
        <div class="d-flex gap-3">
          <div class="notification-icon" :class="`bg-${notif.type}-soft`">
            <i :class="getNotificationIcon(notif.type)"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start">
              <h6 class="fw-bold mb-1">{{ t(notif.title) }}</h6>
              <small class="text-muted">{{ formatTime(notif.time) }}</small>
            </div>
            <p class="text-muted small mb-2">{{ t(notif.description) }}</p>
            <div class="d-flex gap-3">
              <button v-if="!notif.read" class="btn btn-sm btn-link p-0" @click="markAsRead(notif.id)">
                {{ t('mark_as_read') }}
              </button>
              <button class="btn btn-sm btn-link text-danger p-0" @click="deleteNotification(notif.id)">
                {{ t('delete') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Load More Button -->
    <div v-if="notifications.length > 0" class="text-center mt-4">
      <button class="btn btn-outline-primary" @click="loadMore">
        {{ t('load_more') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from '@/composables/useI18n'
import { useNotificationsStore } from '@/stores/notifications'

const { t } = useI18n()
const notificationsStore = useNotificationsStore()

// Sample notifications data
const notifications = ref([
  {
    id: 1,
    title: 'course_completed',
    description: 'course_completed_desc',
    time: new Date(Date.now() - 5 * 60000),
    read: false,
    type: 'success'
  },
  {
    id: 2,
    title: 'new_course_available',
    description: 'new_course_desc',
    time: new Date(Date.now() - 2 * 3600000),
    read: false,
    type: 'info'
  },
  {
    id: 3,
    title: 'assignment_reviewed',
    description: 'assignment_reviewed_desc',
    time: new Date(Date.now() - 24 * 3600000),
    read: true,
    type: 'warning'
  }
])

const getNotificationIcon = (type) => {
  const icons = {
    success: 'bi bi-check-circle-fill',
    info: 'bi bi-info-circle-fill',
    warning: 'bi bi-exclamation-triangle-fill',
    danger: 'bi bi-x-circle-fill'
  }
  return icons[type] || 'bi bi-bell-fill'
}

const formatTime = (date) => {
  const now = new Date()
  const diff = now - new Date(date)
  const minutes = Math.floor(diff / 60000)
  
  if (minutes < 1) return t('just_now')
  if (minutes < 60) return t('minutes_ago', { count: minutes })
  if (minutes < 1440) return t('hours_ago', { count: Math.floor(minutes / 60) })
  return t('days_ago', { count: Math.floor(minutes / 1440) })
}

const markAsRead = (id) => {
  const notif = notifications.value.find(n => n.id === id)
  if (notif) notif.read = true
}

const deleteNotification = (id) => {
  notifications.value = notifications.value.filter(n => n.id !== id)
}

const loadMore = () => {
  alert(t('no_more_notifications'))
}

onMounted(() => {
  // يمكن إضافة إشعارات من store إذا وجدت
})
</script>

<style scoped>
.student-notifications-page {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 30px;
}

.notification-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #f0f0f0;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}

.notification-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border-color: #7c3aed;
}

.notification-icon {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.bg-success-soft {
  background: #f0fdf4;
  color: #22c55e;
}

.bg-info-soft {
  background: #eff6ff;
  color: #3b82f6;
}

.bg-warning-soft {
  background: #fffbeb;
  color: #f59e0b;
}

.btn-link {
  text-decoration: none;
  font-size: 13px;
}

.btn-link:hover {
  text-decoration: underline;
}

.empty-state {
  color: #9ca3af;
}

/* Dark mode */
[data-theme="dark"] .notification-card {
  background: #1f2937;
  border-color: #374151;
}

[data-theme="dark"] .notification-card:hover {
  border-color: #7c3aed;
}
</style>