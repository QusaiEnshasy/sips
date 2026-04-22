<template>
  <div class="supervisor-applications-page">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
      <div>
        <h2 class="fw-bold mb-1">{{ t('applications') }}</h2>
        <p class="text-muted mb-0">{{ t('pending_applications') }}</p>
      </div>
      <router-link class="btn btn-outline-primary rounded-pill px-4" to="/supervisor/students">
        <i class="bi bi-people me-2"></i>
        {{ t('students') }}
      </router-link>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <div class="text-muted mt-3">{{ t('loading') }}</div>
    </div>

    <div v-else-if="error" class="alert alert-warning rounded-4">
      {{ error }}
      <button class="btn btn-sm btn-outline-primary ms-2" @click="loadApplications">
        {{ t('retry') }}
      </button>
    </div>

    <template v-else>
      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3" v-for="item in statsCards" :key="item.key">
          <div class="stat-card h-100">
            <div class="small text-muted">{{ item.label }}</div>
            <div class="fs-3 fw-bold">{{ item.value }}</div>
          </div>
        </div>
      </div>

      <div class="content-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <h5 class="fw-bold mb-0">{{ t('applications') }}</h5>
          <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
            {{ applications.length }}
          </span>
        </div>

        <div v-if="applications.length === 0" class="text-muted py-4">
          {{ t('no_applications_yet') }}
        </div>

        <div v-else class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>{{ t('student') }}</th>
                <th>{{ t('program') }}</th>
                <th>{{ t('company_status') }}</th>
                <th>{{ t('supervisor_status') }}</th>
                <th>{{ t('final_status') }}</th>
                <th>{{ t('created_at') }}</th>
                <th>{{ t('actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="application in applications" :key="application.id">
                <td>
                  <div class="fw-bold">{{ application.student_name || '-' }}</div>
                  <small class="text-muted">{{ application.student_email || '-' }}</small>
                </td>
                <td>{{ application.program_title || '-' }}</td>
                <td>
                  <span class="badge" :class="statusClass(application.company_status)">
                    {{ statusText(application.company_status) }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="statusClass(application.supervisor_status)">
                    {{ statusText(application.supervisor_status) }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="statusClass(application.final_status)">
                    {{ statusText(application.final_status) }}
                  </span>
                </td>
                <td>{{ formatDate(application.created_at) || '-' }}</td>
                <td>
                  <div v-if="application.supervisor_status === 'pending'" class="d-flex gap-2 flex-wrap">
                    <button
                      class="btn btn-sm btn-success"
                      :disabled="busyId === application.id"
                      @click="approveApplication(application.id)"
                    >
                      {{ t('approved') }}
                    </button>
                    <button
                      class="btn btn-sm btn-danger"
                      :disabled="busyId === application.id"
                      @click="openRejectModal(application)"
                    >
                      {{ t('rejected') }}
                    </button>
                  </div>
                  <span v-else class="text-muted small">{{ statusText(application.supervisor_status) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <Teleport to="body">
      <div v-if="rejectModal.open" class="reject-modal-overlay" @click.self="closeRejectModal">
        <div class="reject-modal-card">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <h5 class="fw-bold mb-1">{{ t('rejection_reason') }}</h5>
              <p class="text-muted small mb-0">{{ rejectModal.studentName }}</p>
            </div>
            <button class="btn-close" type="button" @click="closeRejectModal"></button>
          </div>

          <form class="mt-3" @submit.prevent="submitReject">
            <label class="form-label fw-bold small">{{ t('enter_rejection_reason') }}</label>
            <textarea
              v-model.trim="rejectModal.reason"
              class="form-control"
              rows="5"
              required
            ></textarea>

            <div class="d-flex justify-content-end gap-2 mt-4">
              <button type="button" class="btn btn-light" @click="closeRejectModal">
                {{ t('cancel') }}
              </button>
              <button
                type="submit"
                class="btn btn-danger"
                :disabled="rejectModal.submitting || !rejectModal.reason"
              >
                {{ t('confirm_reject') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { supervisorAPI } from '@/services/api/supervisor'
import { useI18n } from '@/composables/useI18n'

const { t, formatDate } = useI18n()

const isLoading = ref(false)
const error = ref('')
const applications = ref([])
const stats = ref({})
const busyId = ref(null)
const rejectModal = ref({
  open: false,
  applicationId: null,
  studentName: '',
  reason: '',
  submitting: false
})

const statsCards = computed(() => [
  { key: 'total', label: t('total_applications'), value: stats.value.total_applications ?? 0 },
  { key: 'pending', label: t('pending_applications'), value: stats.value.pending_applications ?? 0 },
  { key: 'approved', label: t('approved_applications'), value: stats.value.approved_applications ?? 0 },
  { key: 'rejected', label: t('rejected_applications'), value: stats.value.rejected_applications ?? 0 }
])

const statusText = (status) => ({
  approved: t('approved'),
  rejected: t('rejected'),
  pending: t('pending')
}[status] || t('pending'))

const statusClass = (status) => ({
  approved: 'bg-success',
  rejected: 'bg-danger',
  pending: 'bg-warning text-dark'
}[status] || 'bg-warning text-dark')

const loadApplications = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const response = await supervisorAPI.getApplications()
    const data = response.data?.data || {}
    applications.value = data.applications || []
    stats.value = data.stats || {}
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load applications.'
  } finally {
    isLoading.value = false
  }
}

const approveApplication = async (id) => {
  busyId.value = id
  try {
    await supervisorAPI.approveApplication(id)
    await loadApplications()
  } finally {
    busyId.value = null
  }
}

const openRejectModal = (application) => {
  rejectModal.value = {
    open: true,
    applicationId: application.id,
    studentName: application.student_name || '',
    reason: '',
    submitting: false
  }
}

const closeRejectModal = () => {
  rejectModal.value = {
    open: false,
    applicationId: null,
    studentName: '',
    reason: '',
    submitting: false
  }
}

const submitReject = async () => {
  if (!rejectModal.value.applicationId || !rejectModal.value.reason) return

  rejectModal.value.submitting = true
  try {
    await supervisorAPI.rejectApplication(rejectModal.value.applicationId, {
      reason: rejectModal.value.reason
    })
    closeRejectModal()
    await loadApplications()
  } finally {
    rejectModal.value.submitting = false
  }
}

onMounted(loadApplications)
</script>

<style scoped>
.supervisor-applications-page {
  padding: 20px 0;
}

.stat-card,
.content-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 18px;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
}

.stat-card {
  padding: 18px;
}

.content-card {
  padding: 20px;
}

.reject-modal-overlay {
  align-items: center;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  inset: 0;
  justify-content: center;
  padding: 24px;
  position: fixed;
  z-index: 2000;
}

.reject-modal-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 18px;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
  max-width: 520px;
  padding: 24px;
  width: 100%;
}
</style>
