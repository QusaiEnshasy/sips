<template>
  <div class="notifications-page">
    <div class="notifications-header mb-4" data-aos="fade-down">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-bell-main"><i class="bi bi-bell-fill"></i></div>
          <div>
            <h4 class="fw-bold mb-0">{{ t('notifications') }}</h4>
            <p class="text-muted small mb-0">{{ unreadCount }} {{ t('unread') }}</p>
          </div>
        </div>
        <button class="btn-mark-all" @click="markAllAsRead" :disabled="isLoading || notifications.length === 0">
          <i class="bi bi-check2-all me-1"></i>{{ t('mark_all_as_read') }}
        </button>
      </div>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div v-else-if="notifications.length === 0" class="text-center text-muted py-5">
      {{ t('no_notifications') }}
    </div>

    <div v-else class="notifications-list">
      <div v-for="notif in notifications" :key="notif.id" class="mb-3">
        <NotificationCard
          :title="notif.title"
          :description="notif.description"
          :reason="notif.reason"
          :details="notif.details"
          :time="notif.time"
          :unread="notif.unread"
          :type="notif.type"
          :category="notif.category"
          @click="viewNotification(notif)"
          @mark-read="markAsRead(notif)"
          @delete="deleteNotification(notif)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { useToastStore } from '@/stores/toast'
import { useAuthStore } from '@/stores/auth'
import NotificationCard from '@/components/common/NotificationCard.vue'
import AOS from 'aos'

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
const webApi = axios.create({
  baseURL: '',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrf,
    Accept: 'application/json'
  },
  withCredentials: true
})

const { t } = useI18n()
const router = useRouter()
const toastStore = useToastStore()
const authStore = useAuthStore()

const notifications = ref([])
const isLoading = ref(false)

const unreadCount = computed(() => notifications.value.filter(n => n.unread).length)

const loadNotifications = async () => {
  isLoading.value = true
  try {
    const res = await webApi.get('/notifications')
    notifications.value = res.data?.data?.notifications || []
  } catch {
    toastStore.addToast({ type: 'error', message: t('failed_load_notifications') })
  } finally {
    isLoading.value = false
  }
}

const markAsRead = async (notif) => {
  try {
    await webApi.post(`/notifications/${notif.id}/mark-read`)
    notif.unread = false
  } catch {
    toastStore.addToast({ type: 'error', message: t('failed_mark_notification_read') })
  }
}

const markAllAsRead = async () => {
  try {
    await webApi.post('/notifications/mark-all-read')
    notifications.value.forEach(n => { n.unread = false })
  } catch {
    toastStore.addToast({ type: 'error', message: t('failed_mark_all_notifications_read') })
  }
}

const deleteNotification = async (notif) => {
  try {
    await webApi.delete(`/notifications/${notif.id}`)
    notifications.value = notifications.value.filter(n => n.id !== notif.id)
  } catch {
    toastStore.addToast({ type: 'error', message: t('failed_delete_notification') })
  }
}

const viewNotification = (notif) => {
  if (notif.unread) {
    markAsRead(notif)
  }
  if (notif.category === 'auth') return
  const role = authStore.userType || authStore.user?.role || 'student'
  router.push(`/${role}/dashboard`)
}

onMounted(() => {
  AOS.init({ duration: 800, once: true, easing: 'ease-in-out-sine' })
  loadNotifications()
})
</script>

<style scoped>
.notifications-page { padding: 20px 0; max-width: 900px; margin: 0 auto; }
.icon-bell-main { width: 45px; height: 45px; background: var(--accent); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.btn-mark-all { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 500; color: var(--accent); transition: all 0.3s ease; cursor: pointer; display: inline-flex; align-items: center; min-height: 42px; }
.btn-mark-all:hover { background: var(--accent-soft); border-color: var(--accent); }
</style>
