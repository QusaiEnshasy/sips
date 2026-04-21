<template>
  <header class="page-header">
    <div class="container-fluid px-3 px-md-4 px-lg-5">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
          <button class="menu-toggle d-md-none" @click="$emit('toggle-sidebar')" :aria-label="t('toggle_menu')">
            <i class="bi bi-list"></i>
          </button>

          <div>
            <h5 class="fw-bold mb-0" v-text="pageTitle"></h5>
            <small class="text-muted" v-if="pageSubtitle" v-text="pageSubtitle"></small>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2 gap-sm-3">
          <div v-if="isSupervisor && supervisorCode" class="supervisor-code-badge">
            <i class="bi bi-key text-primary"></i>
            <span class="small fw-semibold">{{ t('supervisor_code') }}:</span>
            <span class="fw-bold">{{ supervisorCode }}</span>
            <button class="copy-code-btn" @click="copySupervisorCode" :title="t('copy_code')">
              <i class="bi bi-copy"></i>
            </button>
          </div>

          <div v-if="showSearch" class="search-wrapper d-none d-lg-block">
            <i class="bi bi-search"></i>
            <input type="text" class="search-input" :placeholder="t('search')" @input="$emit('search', $event.target.value)" />
          </div>

          <button class="btn-notification" @click="openNotifications" :aria-label="t('notifications')">
            <i class="bi bi-bell"></i>
            <span v-if="unreadCount > 0" class="notification-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
          </button>

          <LanguageSwitcher />
          <ThemeToggle />

          <router-link to="/profile" class="user-avatar">
            <img :src="userAvatar || 'https://ui-avatars.com/api/?name=User&background=7c3aed&color=fff'" alt="Profile" class="rounded-circle border" width="40" height="40" />
          </router-link>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import { useI18n } from '@/composables/useI18n'
import LanguageSwitcher from './LanguageSwitcher.vue'
import ThemeToggle from './ThemeToggle.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()

defineEmits(['toggle-sidebar', 'search'])
defineProps({ showSearch: { type: Boolean, default: false } })

const pageTitle = computed(() => {
  const titles = {
    '/admin/dashboard': t('admin_dashboard'),
    '/supervisor/dashboard': t('supervisor_dashboard'),
    '/student/dashboard': t('student_dashboard'),
    '/company/dashboard': t('company_dashboard')
  }
  return titles[route.path] || t('dashboard')
})

const pageSubtitle = computed(() => {
  const subtitles = {
    '/admin/dashboard': t('manage_text'),
    '/supervisor/dashboard': t('monitor_students'),
    '/student/dashboard': t('manage_training'),
    '/company/dashboard': t('manage_training')
  }
  return subtitles[route.path] || ''
})

const isSupervisor = computed(() => {
  const role = authStore.user?.role || authStore.user?.type || authStore.userType
  return role === 'supervisor'
})

const supervisorCode = computed(() => authStore.user?.supervisor_code || '')
const userAvatar = computed(() => authStore.user?.avatar || authStore.user?.profile_photo_url || '')
const unreadCount = computed(() => notificationsStore.unreadCount || 0)

const copySupervisorCode = async () => {
  if (!supervisorCode.value) return
  try {
    await navigator.clipboard.writeText(supervisorCode.value)
    alert(t('copied'))
  } catch (error) {
    console.error('Failed to copy supervisor code', error)
  }
}

const openNotifications = async () => {
  await notificationsStore.fetchNotifications({ withSound: false })
  router.push('/notifications')
}

onMounted(() => notificationsStore.startPolling())
onUnmounted(() => notificationsStore.stopPolling())
</script>

<style scoped>
.page-header {
  background: var(--header-bg);
  border-bottom: 1px solid var(--border-color);
  padding: 15px 0;
  position: sticky;
  top: 0;
  z-index: 1000;
  backdrop-filter: blur(10px);
  background: rgba(var(--header-bg-rgb, 255, 255, 255), 0.95);
}

.menu-toggle {
  width: 40px;
  height: 40px;
  background: var(--accent-soft);
  border: none;
  border-radius: 10px;
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  cursor: pointer;
}

.search-wrapper { position: relative; width: 300px; }
.search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.search-input { width: 100%; padding: 10px 15px 10px 45px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--input-bg); color: var(--text-dark); }
[dir="rtl"] .search-wrapper i { left: auto; right: 15px; }
[dir="rtl"] .search-input { padding: 10px 45px 10px 15px; }

.btn-notification {
  width: 40px;
  height: 40px;
  border: 1px solid var(--border-color);
  border-radius: 10px;
  background: var(--card-bg);
  color: var(--text-muted);
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #ef4444;
  color: white;
  font-size: 10px;
  font-weight: 700;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.supervisor-code-badge { display: flex; align-items: center; gap: 8px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 999px; padding: 6px 10px; }
.copy-code-btn { width: 28px; height: 28px; border: 1px solid var(--border-color); border-radius: 50%; background: transparent; color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.copy-code-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-soft); }
.user-avatar { display: block; line-height: 0; }

@media (max-width: 768px) {
  .page-header { padding: 10px 0; }
  .search-wrapper { display: none; }
  .supervisor-code-badge { max-width: 180px; overflow: hidden; white-space: nowrap; }
}
</style>
