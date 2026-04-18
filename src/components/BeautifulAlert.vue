<template>
  <Teleport to="body">
    <div v-if="show" class="alert-overlay" tabindex="-1" @click.self="close">
      <div class="alert-dialog">
        <div class="alert-card">
          <button type="button" class="close-btn" @click="close" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>

          <div class="alert-icon-wrap" :style="{ background: modalConfig.iconBg }">
            <i :class="modalConfig.iconClass" class="alert-icon" :style="{ color: modalConfig.iconColor }"></i>
          </div>

          <div class="alert-content">
            <h5 class="alert-title">{{ title }}</h5>
            <p class="alert-message">{{ message }}</p>
          </div>

          <div class="alert-actions">
            <button type="button" class="btn action-btn" :class="modalConfig.buttonClass" @click="confirm">
              {{ confirmText }}
            </button>
            <button v-if="showCancel" type="button" class="btn btn-light action-btn border" @click="close">
              {{ cancelText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, defineEmits, defineProps } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Are you sure you want to logout?' },
  message: { type: String, default: '' },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  confirmText: { type: String, default: 'Logout' },
  cancelText: { type: String, default: 'Cancel' },
  showCancel: { type: Boolean, default: false }
})

const emit = defineEmits(['confirm', 'close'])

const typeConfig = {
  success: {
    iconClass: 'bi bi-check-circle-fill',
    iconColor: '#1f9254',
    iconBg: 'rgba(31, 146, 84, 0.12)',
    buttonClass: 'btn-success'
  },
  error: {
    iconClass: 'bi bi-x-circle-fill',
    iconColor: '#c73636',
    iconBg: 'rgba(199, 54, 54, 0.12)',
    buttonClass: 'btn-danger'
  },
  warning: {
    iconClass: 'bi bi-exclamation-triangle-fill',
    iconColor: '#cf7f00',
    iconBg: 'rgba(207, 127, 0, 0.14)',
    buttonClass: 'btn-warning'
  },
  info: {
    iconClass: 'bi bi-info-circle-fill',
    iconColor: '#1976d2',
    iconBg: 'rgba(25, 118, 210, 0.12)',
    buttonClass: 'btn-primary'
  }
}

const modalConfig = computed(() => typeConfig[props.type] || typeConfig.info)

const confirm = () => emit('confirm')
const close = () => emit('close')
</script>

<style scoped>
.alert-overlay {
  position: fixed;
  inset: 0;
  background: rgba(10, 22, 39, 0.48);
  backdrop-filter: blur(5px);
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.alert-dialog {
  width: 100%;
  max-width: 450px;
}

.alert-card {
  position: relative;
  background: #ffffff;
  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(15, 23, 42, 0.08);
  padding: 28px 24px 22px;
  text-align: center;
}

.close-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 10px;
  background: #f1f5f9;
  color: #475569;
}

.alert-icon-wrap {
  width: 78px;
  height: 78px;
  border-radius: 50%;
  margin: 0 auto 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.alert-icon {
  font-size: 2.25rem;
}

.alert-title {
  font-weight: 700;
  margin-bottom: 8px;
  color: #0f172a;
}

.alert-message {
  margin-bottom: 0;
  color: #475569;
  font-size: 1.03rem;
  line-height: 1.7;
}

.alert-actions {
  margin-top: 22px;
  display: flex;
  gap: 10px;
  justify-content: center;
}

.action-btn {
  min-width: 120px;
  border-radius: 10px;
  font-weight: 600;
  padding: 10px 16px;
}

@media (max-width: 576px) {
  .alert-actions {
    flex-direction: column;
  }

  .action-btn {
    width: 100%;
  }
}
</style>
