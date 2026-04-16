<template>
  <div class="program-details-page">
    <div class="mb-4">
      <router-link to="/student/browse-programs" class="btn btn-outline-primary rounded-pill px-4">
        {{ t('back_to_programs') }}
      </router-link>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div v-else-if="error" class="alert alert-warning rounded-4">{{ error }}</div>
    <div v-else class="row g-4">
      <div class="col-lg-8">
        <div class="content-card h-100">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <div class="small text-muted">{{ program.type || '-' }}</div>
              <h3 class="fw-bold mb-1">{{ program.title }}</h3>
              <div class="text-muted">{{ program.company_name || '-' }}</div>
            </div>
            <span class="badge bg-success-subtle text-success">{{ program.status }}</span>
          </div>

          <div class="mb-3">
            <h6 class="fw-bold">Description</h6>
            <p class="text-muted mb-0">{{ program.description || '-' }}</p>
          </div>

          <div class="mb-3">
            <h6 class="fw-bold">Requirements</h6>
            <p class="text-muted mb-0">{{ program.requirements || '-' }}</p>
          </div>

          <div class="row g-2 small">
            <div class="col-md-4"><span class="text-muted">Field:</span> {{ program.field || '-' }}</div>
            <div class="col-md-4"><span class="text-muted">City:</span> {{ program.city || '-' }}</div>
            <div class="col-md-4"><span class="text-muted">Work Type:</span> {{ program.work_type || '-' }}</div>
            <div class="col-md-4"><span class="text-muted">Duration:</span> {{ program.duration || 0 }} month(s)</div>
            <div class="col-md-4"><span class="text-muted">Education:</span> {{ program.education_level || '-' }}</div>
            <div class="col-md-4"><span class="text-muted">Deadline:</span> {{ program.deadline || '-' }}</div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="content-card">
          <h5 class="fw-bold mb-3">{{ t('apply_to_program') }}</h5>

          <div v-if="existingApplication" class="alert alert-info rounded-4">
            You already applied to this program. Final status:
            <strong>{{ existingApplication.final_status }}</strong>
          </div>

          <form v-else @submit.prevent="submitApplication">
            <div class="mb-3">
              <label class="form-label fw-semibold">Skills</label>
              <textarea v-model="form.skills" class="form-control rounded-4" rows="3"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Motivation</label>
              <textarea v-model="form.motivation" class="form-control rounded-4" rows="4"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ t('upload_cv_pdf') }}</label>
              <input class="form-control rounded-4" type="file" accept=".pdf" @change="onFileSelect">
            </div>
            <div v-if="submitError" class="alert alert-warning rounded-4 py-2">{{ submitError }}</div>
            <button class="btn btn-primary rounded-pill w-100" :disabled="submitting">
              {{ submitting ? `${t('loading')}` : t('apply_now') }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { studentAPI } from '@/services/api/student'
import { useI18n } from '@/composables/useI18n'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const isLoading = ref(false)
const error = ref('')
const program = ref({})
const existingApplication = ref(null)

const submitting = ref(false)
const submitError = ref('')
const form = ref({
  skills: '',
  motivation: '',
  cv: null
})

const onFileSelect = (event) => {
  form.value.cv = event.target.files?.[0] || null
}

const loadProgram = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const res = await studentAPI.getProgram(route.params.id)
    const payload = res.data?.data || {}
    program.value = payload.program || {}
    existingApplication.value = payload.existing_application || null
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load program details'
  } finally {
    isLoading.value = false
  }
}

const submitApplication = async () => {
  submitError.value = ''
  if (!form.value.cv) {
    submitError.value = 'CV file is required.'
    return
  }

  submitting.value = true
  try {
    await studentAPI.applyToProgram({
      opportunity_id: program.value.id,
      skills: form.value.skills,
      motivation: form.value.motivation,
      cv: form.value.cv
    })
    router.push('/student/application-status')
  } catch (e) {
    submitError.value = e?.response?.data?.message || 'Failed to submit application'
  } finally {
    submitting.value = false
  }
}

onMounted(loadProgram)
</script>

<style scoped>
.content-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 18px;
  padding: 20px;
}
</style>
