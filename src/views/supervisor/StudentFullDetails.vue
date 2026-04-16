<template>
  <div class="student-details-page">
    <div class="mb-3">
      <router-link to="/supervisor/students" class="text-decoration-none text-muted">
        <i class="bi bi-arrow-left me-2"></i>{{ t('back_to_students') }}
      </router-link>
    </div>

    <div class="card border-0 shadow-sm p-4 mb-4" v-if="student">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h3 class="fw-bold mb-1">{{ student.name }}</h3>
          <div class="text-muted">{{ student.email }}</div>
          <div class="text-muted">{{ student.program || '-' }} - {{ student.company || '-' }}</div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary" @click="openBoard(student.board_url)" :disabled="!student.board_url">
            <i class="bi bi-kanban me-2"></i>{{ t('board') }}
          </button>
        </div>
      </div>
    </div>

    <div class="row g-4" v-if="student">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3 h-100">
          <h6 class="fw-bold mb-3">{{ t('training_progress') }}</h6>
          <div class="mb-2 d-flex justify-content-between small">
            <span>{{ t('time_progress') }}</span>
            <span>{{ student.hoursCompleted }}%</span>
          </div>
          <div class="progress mb-3" style="height: 10px;">
            <div class="progress-bar bg-success" :style="{ width: `${student.hoursCompleted}%` }"></div>
          </div>
          <div class="mb-2 d-flex justify-content-between small">
            <span>{{ t('tasks_progress') }}</span>
            <span>{{ student.tasksCompleted }}/{{ student.tasksTotal }}</span>
          </div>
          <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-primary" :style="{ width: `${taskPercent}%` }"></div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3 h-100">
          <h6 class="fw-bold mb-3">{{ t('student_info') }}</h6>
          <div class="small text-muted mb-2">{{ t('phone') }}: {{ student.phone || '-' }}</div>
          <div class="small text-muted mb-2">{{ t('location') }}: {{ student.location || '-' }}</div>
          <div class="small text-muted mb-2">{{ t('enrolled') }}: {{ student.enrolledDate || '-' }}</div>
          <div class="small text-muted">{{ t('expected_end') }}: {{ student.expectedEndDate || '-' }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { supervisorAPI } from '@/services/api/supervisor'

const { t } = useI18n()
const route = useRoute()
const student = ref(null)

const taskPercent = computed(() => {
  if (!student.value?.tasksTotal) return 0
  return Math.round((student.value.tasksCompleted / student.value.tasksTotal) * 100)
})

const loadStudent = async () => {
  const res = await supervisorAPI.getStudent(route.params.id)
  student.value = res.data?.data || null
}

const openBoard = (url) => { if (url) window.location.href = url }

onMounted(loadStudent)
</script>

<style scoped>
.student-details-page { padding: 20px 0; }
</style>
