<template>
  <Teleport to="body">
    <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header" :class="headerClass">
            <h5 class="modal-title">
              <i :class="iconClass" class="me-2"></i>
              {{ title }}
            </h5>
            <button type="button" class="btn-close" @click="close" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center py-4">
            <div class="mb-3">
              <i :class="iconClass" :style="{ fontSize: '3rem', color: iconColor }"></i>
            </div>
            <p class="mb-0 fs-5">{{ message }}</p>
          </div>
          <div class="modal-footer justify-content-center border-0 pb-4">
            <button type="button" class="btn" :class="buttonClass" @click="confirm">
              {{ confirmText }}
            </button>
            <button v-if="showCancel" type="button" class="btn btn-secondary ms-2" @click="close">
              {{ cancelText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'تنبيه'
  },
  message: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'info', // success, error, warning, info
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  confirmText: {
    type: String,
    default: 'موافق'
  },
  cancelText: {
    type: String,
    default: 'إلغاء'
  },
  showCancel: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['confirm', 'close'])

const typeConfig = {
  success: {
    headerClass: 'bg-success text-white',
    iconClass: 'bi bi-check-circle-fill',
    iconColor: '#198754',
    buttonClass: 'btn-success'
  },
  error: {
    headerClass: 'bg-danger text-white',
    iconClass: 'bi bi-x-circle-fill',
    iconColor: '#dc3545',
    buttonClass: 'btn-danger'
  },
  warning: {
    headerClass: 'bg-warning text-dark',
    iconClass: 'bi bi-exclamation-triangle-fill',
    iconColor: '#ffc107',
    buttonClass: 'btn-warning'
  },
  info: {
    headerClass: 'bg-info text-white',
    iconClass: 'bi bi-info-circle-fill',
    iconColor: '#0dcaf0',
    buttonClass: 'btn-info'
  }
}

const config = computed(() => typeConfig[props.type] || typeConfig.info)

const { headerClass, iconClass, iconColor, buttonClass } = config.value

const confirm = () => {
  emit('confirm')
}

const close = () => {
  emit('close')
}
</script>

<style scoped>
.modal-content {
  border-radius: 1rem;
}

.modal-header {
  border-radius: 1rem 1rem 0 0;
  border: none;
}

.modal-body {
  padding: 2rem;
}

.btn {
  border-radius: 0.5rem;
  padding: 0.5rem 2rem;
  font-weight: 500;
}
</style>