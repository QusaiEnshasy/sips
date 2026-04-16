import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref([])
  const unreadCount = computed(() => notifications.value.filter(n => n.unread === true).length)
  const recentNotifications = computed(() => notifications.value.slice(0, 5))
  const lastNotificationId = ref(null)
  const hasLoadedOnce = ref(false)
  const pollTimer = ref(null)

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

  function playNotificationSound() {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext
      if (!AudioCtx) return

      const ctx = new AudioCtx()
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()

      osc.type = 'sine'
      osc.frequency.setValueAtTime(880, ctx.currentTime)
      gain.gain.setValueAtTime(0.0001, ctx.currentTime)
      gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.01)
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.18)

      osc.connect(gain)
      gain.connect(ctx.destination)
      osc.start()
      osc.stop(ctx.currentTime + 0.2)
    } catch {
      // Sound is optional; never break UI if blocked by browser policy.
    }
  }

  async function fetchNotifications({ withSound = true } = {}) {
    try {
      const res = await webApi.get('/notifications')
      const incoming = res.data?.data?.notifications || []

      const topId = incoming.length > 0 ? incoming[0].id : null
      const isNew = hasLoadedOnce.value && topId && topId !== lastNotificationId.value

      notifications.value = incoming
      lastNotificationId.value = topId

      if (isNew && withSound && document.visibilityState === 'visible') {
        playNotificationSound()
      }

      hasLoadedOnce.value = true
    } catch {
      // Silent fail; notifications should not break app.
    }
  }

  function startPolling(intervalMs = 15000) {
    if (pollTimer.value) return

    fetchNotifications({ withSound: false })
    pollTimer.value = window.setInterval(() => {
      fetchNotifications({ withSound: true })
    }, intervalMs)
  }

  function stopPolling() {
    if (!pollTimer.value) return
    window.clearInterval(pollTimer.value)
    pollTimer.value = null
  }

  function addNotification(notification) {
    notifications.value.unshift({
      id: Date.now(),
      unread: true,
      time: new Date(),
      ...notification
    })
  }

  function markAsRead(id) {
    const notif = notifications.value.find(n => n.id === id)
    if (notif) {
      notif.read = true
      notif.unread = false
    }
  }

  function markAllAsRead() {
    notifications.value.forEach(n => {
      n.read = true
      n.unread = false
    })
  }

  function deleteNotification(id) {
    notifications.value = notifications.value.filter(n => n.id !== id)
  }

  return {
    notifications,
    unreadCount,
    recentNotifications,
    fetchNotifications,
    startPolling,
    stopPolling,
    addNotification,
    markAsRead,
    markAllAsRead,
    deleteNotification
  }
})
