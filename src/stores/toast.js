import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])
  let timers = new Map()

  const addToast = ({ type = 'info', title = '', message, duration = 5000 }) => {
    const id = Date.now() + Math.random()
    const toast = {
      id,
      type,
      title,
      message,
      duration,
      progress: 100,
      startTime: Date.now()
    }
    toasts.value.push(toast)
    startTimer(id, duration)
    return id
  }

  const startTimer = (id, duration) => {
    const toast = toasts.value.find(t => t.id === id)
    if (!toast) return

    const interval = 100 // تحديث كل 100ms
    const steps = duration / interval
    let currentStep = 0

    const timer = setInterval(() => {
      currentStep++
      const currentToast = toasts.value.find(t => t.id === id)
      if (currentToast) {
        currentToast.progress = 100 - (currentStep / steps * 100)
      }
      if (currentStep >= steps) {
        removeToast(id)
      }
    }, interval)

    timers.set(id, timer)
  }

  const pauseTimer = (id) => {
    const timer = timers.get(id)
    if (timer) {
      clearInterval(timer)
      timers.delete(id)
    }
  }

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

  const removeToast = (id) => {
    const timer = timers.get(id)
    if (timer) clearInterval(timer)
    timers.delete(id)
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  const clearAllToasts = () => {
    timers.forEach(timer => clearInterval(timer))
    timers.clear()
    toasts.value = []
  }

  return { toasts, addToast, removeToast, clearAllToasts, pauseTimer, resumeTimer }
})
