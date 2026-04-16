<template>
  <div class="application-status-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <h3 class="fw-bold mb-1">{{ t('application_status') }}</h3>
        <p class="text-muted mb-0">{{ t('details') }}</p>
      </div>
      <router-link class="btn btn-outline-primary rounded-pill px-4" to="/student/browse-programs">
        {{ t('browse_more_programs') }}
      </router-link>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div v-else-if="error" class="alert alert-warning rounded-4">{{ error }}</div>
    <div v-else-if="applications.length === 0" class="alert alert-light border rounded-4">
      You have not submitted any applications yet.
    </div>
    <div v-else class="content-card">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Program</th>
              <th>Company</th>
              <th>Company Status</th>
              <th>Supervisor Status</th>
              <th>Final Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="app in applications" :key="app.id">
              <td>{{ app.program_title || '-' }}</td>
              <td>{{ app.company_name || '-' }}</td>
              <td><span class="badge" :class="statusClass(app.company_status)">{{ app.company_status }}</span></td>
              <td><span class="badge" :class="statusClass(app.supervisor_status)">{{ app.supervisor_status }}</span></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge" :class="statusClass(app.final_status)">{{ app.final_status }}</span>
                  <button
                    v-if="app.final_status === 'approved' && activeApplication?.board_url"
                    class="btn btn-sm btn-outline-primary"
                    @click="openBoard(activeApplication.board_url)"
                  >
                    Open Board
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
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
const applications = ref([])
const activeApplication = ref(null)

const statusClass = (status) => {
  if (status === 'approved' || status === 'done') return 'bg-success'
  if (status === 'rejected') return 'bg-danger'
  if (status === 'progress') return 'bg-info'
  return 'bg-warning text-dark'
}

const openBoard = (url) => {
  if (!url) return
  window.location.href = url
}

const loadApplications = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const res = await studentAPI.getApplications()
    const payload = res.data?.data || {}
    applications.value = payload.applications || []
    activeApplication.value = payload.active_application || null
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load applications'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadApplications)
</script>

<style scoped>
.content-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 18px;
  padding: 20px;
}
</style>
