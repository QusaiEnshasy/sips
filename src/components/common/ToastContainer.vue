<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast" tag="div">
        <div 
          v-for="toast in toasts" 
          :key="toast.id"
          class="toast-item"
          :class="`toast-${toast.type}`"
          @mouseenter="pauseTimer(toast.id)"
          @mouseleave="resumeTimer(toast.id)"
        >
          <!-- أيقونة حسب النوع -->
          <div class="toast-icon">
            <i :class="getToastIcon(toast.type)"></i>
          </div>

          <!-- المحتوى -->
          <div class="toast-content">
            <div v-if="toast.title" class="toast-title">{{ toast.title }}</div>
            <div class="toast-message">{{ toast.message }}</div>
          </div>

          <!-- شريط التقدم -->
          <div class="toast-progress" :style="{ width: toast.progress + '%' }"></div>

          <!-- زر الإغلاق -->
          <button class="toast-close" @click="removeToast(toast.id)">
            <i class="bi bi-x"></i>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()

// حالة التوست
const toasts = ref([])
let timers = new Map()

// دالة إضافة توست جديدة (تُستدعى من أماكن أخرى)
const addToast = ({ type = 'info', title = '', message, duration = 5000 }) => {
  const id = Date.now() + Math.random()
  const toast = {
    id,
    type,
    title: title || t(`toast_${type}`),
    message,
    duration,
    progress: 100,
    startTime: Date.now(),
    timer: null
  }
  
  toasts.value.push(toast)
  startTimer(id, duration)
  
  return id
}

// بدء المؤقت
const startTimer = (id, duration) => {
  const toast = toasts.value.find(t => t.id === id)
  if (!toast) return
  
  const interval = 100 // تحديث كل 100ms
  const steps = duration / interval
  let currentStep = 0
  
  const timer = setInterval(() => {
    currentStep++
    toast.progress = 100 - (currentStep / steps * 100)
    
    if (currentStep >= steps) {
      removeToast(id)
    }
  }, interval)
  
  timers.set(id, timer)
}

// إيقاف المؤقت مؤقتاً
const pauseTimer = (id) => {
  const timer = timers.get(id)
  if (timer) {
    clearInterval(timer)
    timers.delete(id)
  }
}

// استئناف المؤقت
const resumeTimer = (id) => {
  const toast = toasts.value.find(t => t.id === id)
  if (toast) {
    const elapsed = Date.now() - toast.startTime
    const remaining = toast.duration - elapsed
    if (remaining > 0) {
      startTimer(id, remaining)
    }
  }
}

// إزالة توست
const removeToast = (id) => {
  const timer = timers.get(id)
  if (timer) {
    clearInterval(timer)
    timers.delete(id)
  }
  toasts.value = toasts.value.filter(t => t.id !== id)
}

// إزالة كل التوست
const clearAllToasts = () => {
  timers.forEach(timer => clearInterval(timer))
  timers.clear()
  toasts.value = []
}

// الحصول على أيقونة حسب النوع
const getToastIcon = (type) => {
  const icons = {
    success: 'bi bi-check-circle-fill',
    error: 'bi bi-x-circle-fill',
    warning: 'bi bi-exclamation-triangle-fill',
    info: 'bi bi-info-circle-fill'
  }
  return icons[type] || icons.info
}

// تعريف الدوال للاستخدام الخارجي
defineExpose({
  addToast,
  clearAllToasts
})

// تنظيف المؤقتات عند إزالة المكون
onUnmounted(() => {
  timers.forEach(timer => clearInterval(timer))
  timers.clear()
})
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 400px;
  width: calc(100% - 40px);
  pointer-events: none;
}

[dir="rtl"] .toast-container {
  right: auto;
  left: 20px;
}

.toast-item {
  background: var(--card-bg);
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 15px;
  position: relative;
  overflow: hidden;
  pointer-events: auto;
  border: 1px solid var(--border-color);
  animation: toast-in 0.3s ease;
  width: 100%;
}

.toast-item:hover {
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
}

/* ألوان حسب النوع */
.toast-success {
  border-left: 4px solid #22c55e;
}

.toast-success .toast-icon {
  color: #22c55e;
}

.toast-error {
  border-left: 4px solid #ef4444;
}

.toast-error .toast-icon {
  color: #ef4444;
}

.toast-warning {
  border-left: 4px solid #f59e0b;
}

.toast-warning .toast-icon {
  color: #f59e0b;
}

.toast-info {
  border-left: 4px solid #3b82f6;
}

.toast-info .toast-icon {
  color: #3b82f6;
}

.toast-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-weight: 700;
  font-size: 14px;
  margin-bottom: 4px;
  color: var(--text-dark);
}

.toast-message {
  font-size: 13px;
  color: var(--text-muted);
  word-break: break-word;
}

.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: var(--accent);
  opacity: 0.3;
  transition: width 0.1s linear;
}

[dir="rtl"] .toast-progress {
  left: auto;
  right: 0;
}

.toast-close {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  border: none;
  background: transparent;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
  padding: 0;
}

.toast-close:hover {
  background: var(--accent-soft);
  color: var(--accent);
}

/* أنيميشن الدخول والخروج */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

[dir="rtl"] .toast-enter-from {
  transform: translateX(-100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

[dir="rtl"] .toast-leave-to {
  transform: translateX(-100%);
}

/* Responsive */
@media (max-width: 576px) {
  .toast-container {
    top: 10px;
    right: 10px;
    left: 10px;
    width: calc(100% - 20px);
    max-width: none;
  }
  
  .toast-item {
    padding: 12px;
  }
  
  .toast-icon {
    font-size: 20px;
  }
  
  .toast-title {
    font-size: 13px;
  }
  
  .toast-message {
    font-size: 12px;
  }
}
</style>