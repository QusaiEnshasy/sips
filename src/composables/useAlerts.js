import { ref } from 'vue'
import { useI18n } from '@/composables/useI18n'

const alertState = ref({
  show: false,
  title: '',
  message: '',
  type: 'info',
  confirmText: '',
  cancelText: '',
  showCancel: false,
  resolve: null
})

export function useAlerts() {
  const { t } = useI18n()

  const showAlert = (options) => {
    return new Promise((resolve) => {
      alertState.value = {
        show: true,
        title: options.title || t('alert_info_title'),
        message: options.message || '',
        type: options.type || 'info',
        confirmText: options.confirmText || t('alert_ok_button'),
        cancelText: options.cancelText || t('alert_cancel_button'),
        showCancel: options.showCancel || false,
        resolve
      }
    })
  }

  const showSuccess = (message, title) => {
    return showAlert({
      title: title || t('alert_success_title'),
      message,
      type: 'success',
      confirmText: t('alert_ok_button')
    })
  }

  const showError = (message, title) => {
    return showAlert({
      title: title || t('alert_error_title'),
      message,
      type: 'error',
      confirmText: t('alert_ok_button')
    })
  }

  const showWarning = (message, title) => {
    return showAlert({
      title: title || t('alert_warning_title'),
      message,
      type: 'warning',
      confirmText: t('alert_ok_button'),
      showCancel: true
    })
  }

  const showInfo = (message, title) => {
    return showAlert({
      title: title || t('alert_info_title'),
      message,
      type: 'info',
      confirmText: t('alert_ok_button')
    })
  }

  const showConfirm = (message, title) => {
    return showAlert({
      title: title || t('alert_confirm_title'),
      message,
      type: 'warning',
      confirmText: t('alert_confirm_button'),
      cancelText: t('alert_cancel_button'),
      showCancel: true
    })
  }

  const closeAlert = (confirmed = false) => {
    alertState.value.show = false
    if (alertState.value.resolve) {
      alertState.value.resolve({ isConfirmed: confirmed })
    }
  }

  return {
    alertState,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    showConfirm,
    closeAlert
  }
}