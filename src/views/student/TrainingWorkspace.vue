<template>
  <div class="training-workspace">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <h3 class="fw-bold mb-1">{{ t('training_workspace') }}</h3>
        <p class="text-muted mb-0">{{ t('training_board_after_approval') }}</p>
      </div>
      <router-link class="btn btn-outline-primary rounded-pill px-4" to="/student/dashboard">
        {{ t('dashboard') }}
      </router-link>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div v-else-if="error" class="alert alert-warning rounded-4">{{ error }}</div>

    <div v-else-if="!activeApplication" class="content-card text-center">
      <h5 class="fw-bold mb-2">{{ t('no_approved_training_yet') }}</h5>
      <p class="text-muted mb-0">{{ t('training_board_after_approval') }}</p>
    </div>

    <div v-else class="content-card">
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <div class="small text-muted">{{ t('program') }}</div>
          <div class="fw-bold">{{ activeApplication.program_title || '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="small text-muted">{{ t('company') }}</div>
          <div class="fw-bold">{{ activeApplication.company_name || '-' }}</div>
        </div>
        <div class="col-md-4">
          <div class="small text-muted">{{ t('company_status') }}</div>
          <span class="badge bg-success">{{ activeApplication.company_status }}</span>
        </div>
        <div class="col-md-4">
          <div class="small text-muted">{{ t('supervisor_status') }}</div>
          <span class="badge bg-success">{{ activeApplication.supervisor_status }}</span>
        </div>
        <div class="col-md-4">
          <div class="small text-muted">{{ t('training_end_date') }}</div>
          <div class="fw-semibold">{{ activeApplication.training_end_date || '-' }}</div>
        </div>
      </div>

      <div class="progress mb-2" style="height: 12px">
        <div class="progress-bar bg-primary" :style="{ width: `${activeApplication.progress || 0}%` }"></div>
      </div>
      <div class="small text-muted mb-4">{{ t('progress') }}: {{ activeApplication.progress || 0 }}%</div>

      <div v-if="activeApplication.training_completed_at" class="alert alert-success rounded-4">
        {{ t('training_completed_successfully') }}
      </div>
      <div v-else-if="activeApplication.training_ended" class="alert alert-warning rounded-4">
        {{ t('training_period_ended_waiting_eval') }}
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button
          v-if="!activeApplication.training_completed_at && !activeApplication.training_ended"
          class="btn btn-primary rounded-pill px-4"
          @click="openBoard(activeApplication.board_url)"
        >
          {{ t('open_tasks_board') }}
        </button>
        <button
          v-if="activeApplication.complete_url"
          class="btn btn-success rounded-pill px-4"
          @click="goTo(activeApplication.complete_url)"
        >
          {{ t('open_congratulations_screen') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { studentAPI } from '@/services/api/student'
import { useI18n } from '@/composables/useI18n'

const isLoading = ref(false)
const { t } = useI18n()
const error = ref('')
const activeApplication = ref(null)

const goTo = (url) => {
  if (!url) return
  window.location.href = url
}

const openBoard = (url) => {
  if (!url) return
  window.location.href = url
}

const loadWorkspace = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const res = await studentAPI.getWorkspace()
    activeApplication.value = res.data?.data?.active_application || null
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load workspace'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadWorkspace)
</script>

<style scoped>
.content-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 18px;
  padding: 20px;
}
</style>
