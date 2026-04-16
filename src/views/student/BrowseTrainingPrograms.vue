<template>
  <div class="browse-programs">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <h3 class="fw-bold mb-1">{{ t('browse_programs') }}</h3>
        <p class="text-muted mb-0">{{ t('no_open_programs_found') }}</p>
      </div>
      <router-link class="btn btn-outline-primary rounded-pill px-4" to="/student/dashboard">
        {{ t('back_dashboard') }}
      </router-link>
    </div>

    <div class="content-card mb-4">
      <div class="row g-3">
        <div class="col-md-6">
          <input
            v-model.trim="search"
            type="text"
            class="form-control rounded-pill"
            placeholder="Search by title, field, city..."
            @keyup.enter="loadPrograms"
          >
        </div>
        <div class="col-md-6 text-md-end">
          <button class="btn btn-primary rounded-pill px-4" @click="loadPrograms">{{ t('search') }}</button>
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div v-else-if="error" class="alert alert-warning rounded-4">{{ error }}</div>
    <div v-else-if="programs.length === 0" class="alert alert-light border rounded-4">{{ t('no_open_programs_found') }}</div>

    <div v-else class="row g-4">
      <div v-for="program in programs" :key="program.id" class="col-md-6">
        <div class="program-card h-100">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <div class="small text-muted mb-1">{{ program.type || '-' }}</div>
              <h5 class="fw-bold mb-1">{{ program.title }}</h5>
              <div class="small text-muted">{{ program.company_name || '-' }}</div>
            </div>
            <span class="badge bg-success-subtle text-success">{{ program.status }}</span>
          </div>

          <p class="text-muted small">{{ short(program.description) }}</p>

          <div class="row g-2 small mb-3">
            <div class="col-6"><span class="text-muted">Field:</span> {{ program.field || '-' }}</div>
            <div class="col-6"><span class="text-muted">City:</span> {{ program.city || '-' }}</div>
            <div class="col-6"><span class="text-muted">Work:</span> {{ program.work_type || '-' }}</div>
            <div class="col-6"><span class="text-muted">Duration:</span> {{ program.duration || 0 }} month(s)</div>
          </div>

          <router-link :to="`/student/program/${program.id}`" class="btn btn-primary rounded-pill">
            View Details
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { studentAPI } from '@/services/api/student'
import { useI18n } from '@/composables/useI18n'

const isLoading = ref(false)
const error = ref('')
const programs = ref([])
const search = ref('')
const { t } = useI18n()

const short = (text) => {
  if (!text) return '-'
  return text.length > 160 ? `${text.slice(0, 160)}...` : text
}

const loadPrograms = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const res = await studentAPI.getPrograms({ search: search.value || undefined })
    programs.value = res.data?.data?.programs || []
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load programs'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadPrograms)
</script>

<style scoped>
.content-card,
.program-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 18px;
  padding: 18px;
}
</style>
