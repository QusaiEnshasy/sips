<template>
  <div class="admin-users-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-1">{{ t('manage_supervisors') }}</h2>
        <p class="text-muted mb-0">{{ t('manage_supervisors_desc') }}</p>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-3" v-for="stat in statsCards" :key="stat.key">
        <div class="stat-card">
          <div class="text-muted small">{{ stat.label }}</div>
          <div class="h4 fw-bold mb-0">{{ stat.value }}</div>
        </div>
      </div>
    </div>

    <div class="table-container">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>{{ t('full_name') }}</th>
              <th>{{ t('email') }}</th>
              <th>{{ t('supervisor_code') }}</th>
              <th>{{ t('status') }}</th>
              <th>{{ t('joined') }}</th>
              <th>{{ t('actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td class="fw-semibold">{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td><code>{{ user.supervisor_code || '-' }}</code></td>
              <td><span class="badge text-bg-light">{{ user.status || 'pending' }}</span></td>
              <td>{{ formatDate(user.created_at) }}</td>
              <td>
                <div class="d-flex gap-2">
                  <template v-if="(user.status || 'pending') === 'pending'">
                    <button class="btn btn-sm btn-success" @click="setStatus(user.id, 'active')"><i class="bi bi-check-lg"></i></button>
                    <button class="btn btn-sm btn-warning" @click="setStatus(user.id, 'reject')"><i class="bi bi-x-lg"></i></button>
                  </template>
                  <template v-else>
                    <button class="btn btn-sm btn-danger" @click="setStatus(user.id, 'delete')"><i class="bi bi-trash"></i></button>
                  </template>
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
import { computed, onMounted, ref } from 'vue'
import { adminAPI } from '@/services/api/admin'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()

const users = ref([])
const stats = ref({
  total_supervisors: 0,
  approved_supervisors: 0,
  pending_supervisors: 0,
  rejected_supervisors: 0
})

const statsCards = computed(() => [
  { key: 't', label: t('total_supervisors'), value: stats.value.total_supervisors },
  { key: 'a', label: t('approved'), value: stats.value.approved_supervisors },
  { key: 'p', label: t('pending'), value: stats.value.pending_supervisors },
  { key: 'r', label: t('rejected'), value: stats.value.rejected_supervisors }
])

const loadUsers = async () => {
  const { data } = await adminAPI.getUsers()
  users.value = data.users || []
  stats.value = data.stats || stats.value
}

const setStatus = async (id, action) => {
  try {
    if (action === 'delete' && !confirm(t('confirm_delete_supervisor'))) return
    if (action === 'active') await adminAPI.approveSupervisor(id)
    if (action === 'reject') await adminAPI.rejectSupervisor(id)
    if (action === 'delete') await adminAPI.deleteSupervisor(id)
    await loadUsers()
  } catch (error) {
    alert(error?.response?.data?.message || t('failed_update_supervisor'))
  }
}

const formatDate = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString()
}

onMounted(loadUsers)
</script>

<style scoped>
.admin-users-page { padding: 20px 0; }
.stat-card, .table-container { background: var(--card-bg); border-radius: 16px; padding: 20px; border: 1px solid var(--border-color); }
.table { min-width: 760px; }
:deep([dir="rtl"]) .admin-users-page .table th,
:deep([dir="rtl"]) .admin-users-page .table td { text-align: right; }
</style>
