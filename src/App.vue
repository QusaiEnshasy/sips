<template>
  <div id="app" :class="{ 'dark-theme': isDark }">
    <template v-if="isAuthPage">
      <router-view />
      <ToastContainer />
    </template>

    <template v-else>
      <AppSidebar
        v-if="authStore.isAuthenticated"
        ref="sidebarRef"
      />

      <button
        v-if="authStore.isAuthenticated && windowWidth < 768"
        class="mobile-menu-toggle"
        @click="toggleSidebar"
      >
        <i class="bi bi-list"></i>
      </button>

      <div
        class="main-wrapper"
        :class="{ 'with-sidebar': authStore.isAuthenticated }"
      >
        <AppHeader
          v-if="authStore.isAuthenticated"
          :showSearch="showSearch"
          @toggle-sidebar="toggleSidebar"
          @search="handleSearch"
        />

        <main class="main-content">
          <router-view v-slot="{ Component }">
            <transition
              name="fade"
              mode="out-in"
              @before-enter="beforeEnter"
              @enter="enter"
              @leave="leave"
            >
              <component :is="Component" />
            </transition>
          </router-view>
        </main>
      </div>

      <ToastContainer />
      <BeautifulAlert
        :show="alertState.show"
        :title="alertState.title"
        :message="alertState.message"
        :type="alertState.type"
        :confirm-text="alertState.confirmText"
        :cancel-text="alertState.cancelText"
        :show-cancel="alertState.showCancel"
        @confirm="handleAlertConfirm"
        @close="handleAlertClose"
      />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import { useToastStore } from '@/stores/toast'
import { useAlerts } from '@/composables/useAlerts'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import ToastContainer from '@/components/common/ToastContainer.vue'
import BeautifulAlert from '@/components/BeautifulAlert.vue'

const { isDark } = useTheme()
const authStore = useAuthStore()
const router = useRouter()
const toastStore = useToastStore()
const { alertState, closeAlert } = useAlerts()

const sidebarRef = ref(null)
const windowWidth = ref(window.innerWidth)
const showSearch = ref(false)

const isAuthPage = computed(() => ['/login', '/register'].includes(router.currentRoute.value.path))

const handleSearch = (term) => {
  const event = new CustomEvent('global-search', { detail: term })
  window.dispatchEvent(event)
}

const toggleSidebar = () => {
  if (sidebarRef.value) {
    sidebarRef.value.openSidebar()
  }
}

const handleAlertConfirm = () => {
  closeAlert(true)
}

const handleAlertClose = () => {
  closeAlert(false)
}

const updateWindowWidth = () => {
  windowWidth.value = window.innerWidth
}

const beforeEnter = (el) => {
  el.style.opacity = 0
  el.style.transform = 'translateY(20px)'
}

const enter = (el, done) => {
  setTimeout(() => {
    el.style.transition = 'all 0.3s ease'
    el.style.opacity = 1
    el.style.transform = 'translateY(0)'
    done()
  }, 50)
}

const leave = (el, done) => {
  el.style.transition = 'all 0.2s ease'
  el.style.opacity = 0
  el.style.transform = 'translateY(20px)'
  setTimeout(done, 200)
}

onMounted(() => {
  const flash = window.__FLASH__ || {}
  if (!isAuthPage.value) {
    if (flash.error) {
      toastStore.addToast({ type: 'error', title: 'تنبيه', message: flash.error })
    } else if (flash.warning) {
      toastStore.addToast({ type: 'warning', title: 'تنبيه', message: flash.warning })
    } else if (flash.success || flash.status) {
      toastStore.addToast({ type: 'success', title: 'نجاح', message: flash.success || flash.status })
    }
  }

  window.addEventListener('resize', updateWindowWidth)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateWindowWidth)
})
</script>

<style>
@import '@/assets/styles/variables.css';
@import '@/assets/styles/dark-mode.css';
@import '@/assets/styles/main.css';

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html, body, #app {
  min-height: 100%;
}

body {
  font-family: 'Inter', 'Cairo', sans-serif;
  overflow-x: hidden;
  min-height: 100vh;
}

#app {
  min-height: 100vh;
  background-color: var(--main-bg);
  color: var(--text-dark);
  transition: all 0.3s ease;
}

.main-wrapper {
  min-height: 100vh;
  transition: margin-left 0.3s ease;
}

.main-wrapper.with-sidebar {
  margin-left: 280px;
}

[dir="rtl"] .main-wrapper.with-sidebar {
  margin-left: 0;
  margin-right: 280px;
}

.main-content {
  padding: 30px;
  min-height: calc(100vh - 80px);
}

.mobile-menu-toggle {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 56px;
  height: 56px;
  background: var(--accent);
  border: none;
  border-radius: 50%;
  color: white;
  font-size: 24px;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 1060;
  box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
  cursor: pointer;
}

.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

@media (max-width: 768px) {
  .main-wrapper.with-sidebar {
    margin-left: 0 !important;
    margin-right: 0 !important;
  }

  .main-content {
    padding: 20px;
  }

  .mobile-menu-toggle {
    display: flex;
  }
}

@media (max-width: 576px) {
  .main-content {
    padding: 15px;
  }
}
</style>
