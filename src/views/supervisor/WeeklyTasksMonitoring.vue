<template>
  <div class="weekly-tasks-monitoring">
    <div class="mb-4">
      <h2 class="fw-bold mb-1">{{ t('weekly_tasks_monitoring') }}</h2>
      <p class="text-muted mb-0">{{ t('track_tasks_for_approved_students') }}</p>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3" v-for="card in statCards" :key="card.label">
        <div class="card border-0 shadow-sm p-3">
          <div class="text-muted small">{{ card.label }}</div>
          <div class="fs-4 fw-bold">{{ card.value }}</div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm p-3">
      <div v-if="tasks.length === 0" class="text-muted">{{ t('no_tasks_found') }}</div>
      <div v-else class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>{{ t('task_title') }}</th>
              <th>{{ t('student') }}</th>
              <th>{{ t('status') }}</th>
              <th>{{ t('due_date') }}</th>
              <th>{{ t('board') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="task in tasks" :key="task.id">
              <td class="fw-bold">{{ task.title }}</td>
              <td>{{ task.studentName }}</td>
              <td>
                <span class="badge" :class="statusClass(task.status)">{{ task.status }}</span>
              </td>
              <td>{{ task.dueDate || '-' }}</td>
              <td>
                <button class="btn btn-sm btn-primary" @click="openBoard(task.board_url)">
                  <i class="bi bi-kanban me-1"></i>{{ t('board') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from '@/composables/useI18n'
import { supervisorAPI } from '@/services/api/supervisor'

const { t } = useI18n()
const stats = ref({})
const tasks = ref([])

const statCards = computed(() => [
  { label: t('total_tasks'), value: stats.value.total_tasks ?? 0 },
  { label: t('todo'), value: stats.value.todo_tasks ?? 0 },
  { label: t('in_progress'), value: stats.value.progress_tasks ?? 0 },
  { label: t('completed'), value: stats.value.done_tasks ?? 0 }
])

const loadTasks = async () => {
  const res = await supervisorAPI.getWeeklyTasks()
  stats.value = res.data?.data?.stats || {}
  tasks.value = res.data?.data?.tasks || []
}

const statusClass = (status) => {
  if (status === 'done') return 'bg-success'
  if (status === 'progress') return 'bg-warning text-dark'
  return 'bg-secondary'
}

const openBoard = (url) => { if (url) window.location.href = url }

onMounted(loadTasks)
</script>

<style scoped>
.weekly-tasks-monitoring { padding: 20px 0; }
</style>
