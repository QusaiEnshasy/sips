<template>
  <div class="create-program">
    <div class="d-flex align-items-center mb-4">
      <button class="btn btn-link me-3" @click="goBack"><i class="bi bi-arrow-left fs-4"></i></button>
      <div>
        <h2 class="fw-bold mb-2">{{ isEdit ? t('edit_program') : t('create_new_program') }}</h2>
        <p class="text-muted mb-0">{{ t('fill_program_details') }}</p>
      </div>
    </div>

    <div class="form-card">
      <form @submit.prevent="saveProgram">
        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label fw-bold">Program Title *</label>
            <input v-model="form.title" type="text" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Type *</label>
            <select v-model="form.type" class="form-select" required>
              <option value="training">Training</option>
              <option value="job">Job</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Field</label>
            <input v-model="form.field" type="text" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">City</label>
            <input v-model="form.city" type="text" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Work Type</label>
            <select v-model="form.work_type" class="form-select">
              <option value="">Select work type</option>
              <option value="onsite">Onsite</option>
              <option value="remote">Remote</option>
              <option value="hybrid">Hybrid</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Education Level</label>
            <input v-model="form.education_level" type="text" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Duration (months)</label>
            <input v-model.number="form.duration" type="number" min="1" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Deadline</label>
            <input v-model="form.deadline" type="date" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Status</label>
            <select v-model="form.status" class="form-select">
              <option value="open">Open</option>
              <option value="closed">Closed</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label fw-bold">Description *</label>
            <textarea v-model="form.description" class="form-control" rows="5" required></textarea>
          </div>

          <div class="col-12">
            <label class="form-label fw-bold">Requirements</label>
            <textarea v-model="form.requirements" class="form-control" rows="4"></textarea>
          </div>

          <div class="col-12 d-flex justify-content-end gap-3">
            <button type="button" class="btn btn-light" @click="goBack">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="isSaving">
              {{ isSaving ? t('saving') : (isEdit ? t('update_program') : t('save_program')) }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { companyAPI } from '@/services/api/company'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const isSaving = ref(false)
const isEdit = computed(() => !!route.params.id)

const form = reactive({
  title: '',
  type: 'training',
  field: '',
  city: '',
  work_type: '',
  education_level: '',
  duration: null,
  deadline: '',
  status: 'open',
  description: '',
  requirements: ''
})

const mapProgramToForm = (data) => {
  form.title = data.title || ''
  form.type = data.type || 'training'
  form.field = data.field || ''
  form.city = data.city || ''
  form.work_type = data.work_type || ''
  form.education_level = data.education_level || ''
  form.duration = data.duration || data.duration_weeks || null
  form.deadline = data.deadline || data.end_date || ''
  form.status = data.status === 'active' ? 'open' : (data.status === 'completed' ? 'closed' : (data.status || 'open'))
  form.description = data.description || ''
  form.requirements = Array.isArray(data.requirements) ? data.requirements.join('\n') : (data.requirements || '')
}

const loadForEdit = async () => {
  if (!isEdit.value) return
  const res = await companyAPI.getProgram(route.params.id)
  mapProgramToForm(res.data?.data || {})
}

const saveProgram = async () => {
  isSaving.value = true
  try {
    const payload = { ...form }
    if (!payload.duration) payload.duration = null
    if (!payload.deadline) payload.deadline = null
    if (!payload.work_type) payload.work_type = null
    if (!payload.field) payload.field = null
    if (!payload.city) payload.city = null
    if (!payload.education_level) payload.education_level = null
    if (!payload.requirements) payload.requirements = null

    if (isEdit.value) {
      await companyAPI.updateProgram(route.params.id, payload)
    } else {
      await companyAPI.createProgram(payload)
    }

    router.push('/company/programs')
  } catch (error) {
    alert(error.response?.data?.message || t('error_occurred'))
  } finally {
    isSaving.value = false
  }
}

const goBack = () => router.push('/company/programs')

onMounted(loadForEdit)
</script>

<style scoped>
.create-program { padding: 30px; }
.form-card { background: white; border-radius: 20px; padding: 30px; border: 1px solid #f0f0f0; max-width: 980px; }
[data-theme="dark"] .form-card { background: #1f2937; border-color: #374151; }
</style>
