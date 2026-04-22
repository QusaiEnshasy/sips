<template>
  <Teleport to="body">
    <!-- زر القائمة للموبايل -->
    <button
      class="mobile-menu-toggle"
      :class="{ 'active': isOpen }"
      @click="toggleMenu"
      :aria-label="t('toggle_menu')"
    >
      <i :class="isOpen ? 'bi bi-x-lg' : 'bi bi-list'"></i>
    </button>

    <!-- Sidebar للموبايل -->
    <div
      class="mobile-sidebar"
      :class="{ 'open': isOpen }"
      :style="{ [direction === 'rtl' ? 'right' : 'left']: 0 }"
    >
      <div class="mobile-sidebar-header">
        <div class="logo-area">
          <div class="brand-icon">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <span class="fw-bold fs-5">{{ t('brand') }}</span>
        </div>
        <button class="close-btn" @click="closeMenu">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="mobile-sidebar-content">
        <!-- Menu Items -->
        <div v-for="section in menuSections" :key="section.title" class="menu-section">
          <div class="section-title">{{ t(section.title) }}</div>
          <router-link
            v-for="item in section.items"
            :key="item.path"
            :to="item.path"
            class="menu-item"
            :class="{ active: isActive(item.path) }"
            @click="closeMenu"
          >
            <i :class="item.icon"></i>
            <span>{{ t(item.label) }}</span>
          </router-link>
        </div>

        <!-- Logout -->
        <div class="menu-section">
          <button class="menu-item text-danger" @click="handleLogout">
            <i class="bi bi-box-arrow-left"></i>
            <span>{{ t('logout') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Overlay -->
    <div
      v-if="isOpen"
      class="mobile-overlay"
      @click="closeMenu"
    ></div>

  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useAlerts } from '@/composables/useAlerts'

const { t, currentLang } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()
const { showConfirm } = useAlerts()

const isOpen = ref(false)
const direction = computed(() => currentLang.value === 'ar' ? 'rtl' : 'ltr')

// Menu sections based on user role
const userType = computed(() => authStore.userType || 'student')

const menuSections = computed(() => {
  const menus = {
    student: [
      {
        title: 'menu',
        items: [
          { path: '/student/dashboard', icon: 'bi bi-grid', label: 'dashboard' },
          { path: '/student/workspace', icon: 'bi bi-laptop', label: 'workspace' },
          { path: '/student/application-status', icon: 'bi bi-file-earmark-text', label: 'application_status' },
          { path: '/notifications', icon: 'bi bi-bell', label: 'notifications' }
        ]
      },
      {
        title: 'support',
        items: [
          { path: '/profile', icon: 'bi bi-person-circle', label: 'profile' },
          { path: '/settings', icon: 'bi bi-gear', label: 'settings' }
        ]
      }
    ],
    supervisor: [
      {
        title: 'menu',
        items: [
          { path: '/supervisor/dashboard', icon: 'bi bi-grid', label: 'dashboard' },
          { path: '/supervisor/students', icon: 'bi bi-people', label: 'students' },
          { path: '/supervisor/applications', icon: 'bi bi-file-earmark-check', label: 'applications' },
          { path: '/supervisor/jisr-reviews', icon: 'bi bi-clipboard-check', label: 'jisr_reviews' },
          { path: '/supervisor/weekly-tasks', icon: 'bi bi-bar-chart-steps', label: 'weekly_tasks' },
          { path: '/notifications', icon: 'bi bi-bell', label: 'notifications' }
        ]
      },
      {
        title: 'support',
        items: [
          { path: '/profile', icon: 'bi bi-person-circle', label: 'profile' },
          { path: '/settings', icon: 'bi bi-gear', label: 'settings' }
        ]
      }
    ],
    admin: [
      {
        title: 'menu',
        items: [
          { path: '/admin/dashboard', icon: 'bi bi-speedometer2', label: 'dashboard' },
          { path: '/admin/users', icon: 'bi bi-people', label: 'users' },
          { path: '/admin/companies', icon: 'bi bi-building', label: 'companies' },
          { path: '/admin/reports', icon: 'bi bi-file-earmark-bar-graph', label: 'reports' }
        ]
      },
      {
        title: 'management',
        items: [
          { path: '/admin/add-supervisor', icon: 'bi bi-plus-circle', label: 'add_supervisor' }
        ]
      }
    ],
    company: [
      {
        title: 'menu',
        items: [
          { path: '/company/dashboard', icon: 'bi bi-speedometer2', label: 'dashboard' },
          { path: '/company/programs', icon: 'bi bi-journal-bookmark', label: 'programs' },
          { path: '/company/applicants', icon: 'bi bi-people', label: 'applicants' },
          { path: '/company/training-tasks', icon: 'bi bi-clipboard2-check', label: 'training_tasks' },
          { path: '/company/trello-settings', icon: 'bi bi-trello', label: 'trello_integration' },
          { path: '/company/reports', icon: 'bi bi-file-earmark-bar-graph', label: 'reports' },
          { path: '/notifications', icon: 'bi bi-bell', label: 'notifications' }
        ]
      },
      {
        title: 'support',
        items: [
          { path: '/profile', icon: 'bi bi-person-circle', label: 'profile' },
          { path: '/settings', icon: 'bi bi-gear', label: 'settings' }
        ]
      }
    ]
  }
  return menus[userType.value] || menus.student
})

// Check if route is active
const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/')
}

// Toggle menu
const toggleMenu = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
}

const closeMenu = () => {
  isOpen.value = false
  document.body.style.overflow = ''
}

// Handle logout
const handleLogout = async () => {
  const result = await showConfirm(t('confirm_logout') || 'هل أنت متأكد من تسجيل الخروج؟', t('logout') || 'تسجيل الخروج')
  if (!result.isConfirmed) {
    return
  }

  try {
    await authStore.logout()
    toastStore.addToast({
      type: 'success',
      message: t('logged_out_successfully')
    })
    closeMenu()
  } catch (error) {
    toastStore.addToast({
      type: 'error',
      message: t('logout_failed')
    })
  }
}

// Close menu on route change
watch(() => route.path, () => {
  if (window.innerWidth <= 768) {
    closeMenu()
  }
})

// Handle window resize
const handleResize = () => {
  if (window.innerWidth > 768 && isOpen.value) {
    closeMenu()
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.mobile-menu-toggle {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, var(--accent), var(--primary-purple));
  border: none;
  border-radius: 50%;
  color: white;
  font-size: 24px;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 1100;
  box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
  cursor: pointer;
  transition: all 0.3s ease;
}

.mobile-menu-toggle.active {
  transform: rotate(90deg);
}

.mobile-menu-toggle:hover {
  transform: scale(1.1);
}

.mobile-sidebar {
  position: fixed;
  top: 0;
  width: 280px;
  height: 100vh;
  background: var(--sidebar-bg);
  z-index: 1200;
  transform: translateX(-100%);
  transition: transform 0.3s ease;
  display: flex;
  flex-direction: column;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

[dir="rtl"] .mobile-sidebar {
  transform: translateX(100%);
}

.mobile-sidebar.open {
  transform: translateX(0);
}

.mobile-sidebar-header {
  padding: 20px;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo-area {
  display: flex;
  align-items: center;
  gap: 12px;
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

.close-btn {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: transparent;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: var(--accent-soft);
  color: var(--accent);
  border-color: var(--accent);
}

.mobile-sidebar-content {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.menu-section {
  margin-bottom: 30px;
}

.section-title {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--text-muted);
  margin-bottom: 15px;
  font-weight: 700;
  opacity: 0.6;
  padding-left: 10px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  color: var(--text-muted);
  text-decoration: none;
  border-radius: 12px;
  margin-bottom: 4px;
  font-weight: 500;
  transition: all 0.2s ease;
  width: 100%;
  background: transparent;
  border: none;
  cursor: pointer;
  text-align: left;
}

[dir="rtl"] .menu-item {
  text-align: right;
}

.menu-item:hover {
  background: var(--accent-soft);
  color: var(--accent);
}

.menu-item.active {
  background: var(--accent);
  color: white !important;
}

.menu-item i {
  font-size: 1.2rem;
}

.mobile-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1150;
  backdrop-filter: blur(2px);
}

/* Show only on mobile */
@media (max-width: 768px) {
  .mobile-menu-toggle {
    display: flex;
  }
}

@media (max-width: 576px) {
  .mobile-menu-toggle {
    width: 48px;
    height: 48px;
    font-size: 20px;
    bottom: 15px;
    right: 15px;
  }

  .mobile-sidebar {
    width: 100%;
    max-width: 280px;
  }
}
</style>

