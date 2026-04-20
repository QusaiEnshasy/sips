<template>
  <aside :class="['sidebar', { 'show': isOpen }]" :style="sidebarStyle">
    <!-- زر الإغلاق للموبايل -->
    <button class="sidebar-close d-md-none" @click="closeSidebar">
      <i class="bi bi-x-lg"></i>
    </button>

    <!-- Logo Section -->
    <div class="logo-area" aria-label="Application icon">
      <div class="brand-icon">
        <i class="bi bi-mortarboard-fill"></i>
      </div>
      <span class="fw-bold fs-5">{{ t('brand') }}</span>
    </div>

    <!-- القائمة حسب نوع المستخدم -->
    <div v-for="(section, index) in menuSections" :key="index">
      <div class="nav-section-title" v-text="t(section.title)"></div>
      <nav>
        <router-link
          v-for="item in section.items"
          :key="item.path"
          :to="item.path"
          class="nav-link"
          :class="{ active: isActive(item.path) }"
          @click="closeSidebarOnMobile"
        >
          <i :class="item.icon"></i>
          <span v-text="t(item.key)"></span>
        </router-link>
      </nav>
    </div>

    <!-- Logout Section -->
    <div class="logout-section">
      <hr class="opacity-25" />
      <button class="nav-link text-danger w-100" @click="confirmLogout">
        <i class="bi bi-box-arrow-left"></i>
        <span>{{ t('logout') }}</span>
      </button>
    </div>
  </aside>

  <!-- Overlay للموبايل -->
  <div
    v-if="isOpen"
    class="sidebar-overlay d-md-none"
    @click="closeSidebar"
  ></div>

</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from '@/composables/useI18n'
import { useToastStore } from '@/stores/toast'
import { useAlerts } from '@/composables/useAlerts'

const { t, currentLang } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()
const { showConfirm } = useAlerts()

// حالة القائمة (للموبايل)
const isOpen = ref(false)

// تحديد نوع المستخدم من الـ store
const userType = computed(() => authStore.userType || 'student')

// تعريف قوائم التنقل حسب نوع المستخدم
const menuSections = computed(() => {
  const userState = authStore.user || {}
  const menus = {
    // ===== قائمة المدير (Admin) =====
    admin: [
      {
        title: 'menu',
        items: [
          { path: '/admin/dashboard', icon: 'bi bi-speedometer2', key: 'dashboard' },
          { path: '/admin/users', icon: 'bi bi-people', key: 'users' },
          { path: '/admin/companies', icon: 'bi bi-building', key: 'companies' },
          { path: '/admin/reports', icon: 'bi bi-file-earmark-bar-graph', key: 'reports' },
          { path: '/admin/tasks/workspace', icon: 'bi bi-list-task', key: 'general_tasks' }
        ]
      },
      {
        title: 'management',
        items: [
          { path: '/admin/add-supervisor', icon: 'bi bi-plus-circle', key: 'add_supervisor' }
        ]
      }
    ],

    // ===== قائمة المشرف (Supervisor) =====
    supervisor: [
      {
        title: 'menu',
        items: [
          { path: '/supervisor/dashboard', icon: 'bi bi-grid', key: 'dashboard' },
          { path: '/supervisor/students', icon: 'bi bi-people', key: 'students' },
          { path: '/supervisor/applications', icon: 'bi bi-file-earmark-check', key: 'applications' },
          { path: '/supervisor/jisr-reviews', icon: 'bi bi-clipboard-check', key: 'jisr_reviews' },
          { path: '/supervisor/weekly-tasks', icon: 'bi bi-bar-chart-steps', key: 'weekly_tasks' },
          { path: '/notifications', icon: 'bi bi-bell', key: 'notifications' }
        ]
      }
    ],

    // ===== قائمة الطالب (Student) =====
    student: userState.is_in_jisr
      ? [
          {
            title: 'menu',
            items: [
              { path: '/student/jisr', icon: 'bi bi-mortarboard', key: 'jisr_program' },
              { path: '/notifications', icon: 'bi bi-bell', key: 'notifications' }
            ]
          }
        ]
      : (userState.skill_test_required && !userState.skill_test_passed
          ? [
              {
                title: 'menu',
                items: [
                  { path: '/student/skill-test', icon: 'bi bi-patch-question', key: 'skill_assessment_test' },
                  { path: '/notifications', icon: 'bi bi-bell', key: 'notifications' }
                ]
              }
            ]
          : [
              {
                title: 'menu',
                items: [
                  { path: '/student/dashboard', icon: 'bi bi-grid', key: 'dashboard' },
                  { path: '/student/browse-programs', icon: 'bi bi-journal-bookmark', key: 'browse_programs' },
                  { path: '/student/workspace', icon: 'bi bi-laptop', key: 'workspace' },
                  { path: '/student/application-status', icon: 'bi bi-file-earmark-text', key: 'application_status' },
                  { path: '/notifications', icon: 'bi bi-bell', key: 'notifications' }
                ]
              }
            ]),

    // ===== قائمة الشركة (Company) =====
    company: [
      {
        title: 'menu',
        items: [
          { path: '/company/dashboard', icon: 'bi bi-speedometer2', key: 'dashboard' },
          { path: '/company/programs', icon: 'bi bi-journal-bookmark', key: 'programs' },
          { path: '/company/applicants', icon: 'bi bi-people', key: 'applicants' },
          { path: '/company/training-tasks', icon: 'bi bi-clipboard2-check', key: 'training_tasks' },
          { path: '/company/trello-settings', icon: 'bi bi-trello', key: 'trello_integration' },
          { path: '/company/reports', icon: 'bi bi-file-earmark-bar-graph', key: 'reports' },
          { path: '/notifications', icon: 'bi bi-bell', key: 'notifications' }
        ]
      }
    ]
  }

  return menus[userType.value] || menus.student
})

// التحقق إذا كان الرابط نشط
const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/')
}

// فتح/إغلاق القائمة
const openSidebar = () => {
  isOpen.value = true
  document.body.style.overflow = 'hidden'
}

const closeSidebar = () => {
  isOpen.value = false
  document.body.style.overflow = ''
}

const closeSidebarOnMobile = () => {
  if (window.innerWidth < 768) {
    closeSidebar()
  }
}

// ✅ دالة تسجيل الخروج مع تأكيد
const confirmLogout = async () => {
  const result = await showConfirm(t('confirm_logout') || 'هل أنت متأكد من تسجيل الخروج؟', t('logout') || 'تسجيل الخروج')
  if (result.isConfirmed) {
    await handleLogout()
  }
}

const handleLogout = async () => {
  try {
    await authStore.logout()
    toastStore.addToast({
      type: 'success',
      message: t('logged_out_successfully')
    })
  } catch (error) {
    console.error('Logout failed:', error)
    toastStore.addToast({
      type: 'error',
      message: t('logout_failed')
    })
  }
}

// تحديد اتجاه الـ sidebar حسب اللغة
const sidebarStyle = computed(() => {
  const lang = currentLang.value || 'en'
  return {
    left: lang === 'ar' ? 'auto' : 0,
    right: lang === 'ar' ? 0 : 'auto',
    transform: isOpen.value ? 'translateX(0)' : ''
  }
})

// الاستماع لتغيير حجم الشاشة
const handleResize = () => {
  if (window.innerWidth >= 768) {
    closeSidebar()
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// تعريض الدوال للمكونات الأخرى
defineExpose({ openSidebar, closeSidebar })
</script>

<style scoped>
.sidebar {
  width: 280px;
  height: 100vh;
  background: var(--sidebar-bg);
  border-right: 1px solid var(--border-color);
  position: fixed;
  top: 0;
  padding: 30px 24px;
  display: flex;
  flex-direction: column;
  z-index: 1050;
  transition: all 0.3s ease;
}

[dir="rtl"] .sidebar {
  border-right: none;
  border-left: 1px solid var(--border-color);
}

.logo-area {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 40px;
  text-decoration: none;
  color: var(--text-dark);
}

.brand-icon {
  width: 40px;
  height: 40px;
  background: var(--accent);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
}

.nav-section-title {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--text-muted);
  margin: 20px 0 10px 10px;
  font-weight: 700;
  opacity: 0.6;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  color: var(--text-muted);
  text-decoration: none;
  border-radius: 14px;
  margin-bottom: 8px;
  font-weight: 500;
  transition: all 0.3s ease;
  white-space: nowrap;
  width: 100%;
  background: transparent;
  border: none;
  cursor: pointer;
  text-align: left;
}

[dir="rtl"] .nav-link {
  text-align: right;
}

.nav-link:hover:not(.active) {
  background: var(--accent-soft);
  color: var(--accent);
  transform: translateX(5px);
}

[dir="rtl"] .nav-link:hover:not(.active) {
  transform: translateX(-5px);
}

.nav-link.active {
  background: var(--accent);
  color: white !important;
  box-shadow: 0 10px 15px -3px rgba(124, 58, 237, 0.3);
}

.nav-link.text-danger:hover {
  background: #fee2e2;
  color: #ef4444 !important;
}

.logout-section {
  margin-top: auto;
  padding-top: 20px;
}

hr {
  margin: 0 0 15px 0;
  border-color: var(--border-color);
}

.sidebar-close {
  position: absolute;
  top: 20px;
  right: 15px;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--accent-soft);
  color: var(--accent);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1040;
  backdrop-filter: blur(2px);
}

@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }

  [dir="rtl"] .sidebar {
    transform: translateX(100%);
  }

  .sidebar.show {
    transform: translateX(0) !important;
  }
}
</style>

