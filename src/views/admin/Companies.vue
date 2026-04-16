<template>
  <div class="admin-companies-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-1">{{ t('manage_companies') }}</h2>
        <p class="text-muted mb-0">{{ t('manage_companies_desc') }}</p>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-4" v-for="stat in statsCards" :key="stat.key">
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
              <th>{{ t('company') }}</th>
              <th>{{ t('email') }}</th>
              <th>{{ t('status') }}</th>
              <th>{{ t('joined') }}</th>
              <th>{{ t('actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="company in companies" :key="company.id">
              <td class="fw-semibold">{{ company.company_name || company.name }}</td>
              <td>{{ company.email }}</td>
              <td><span class="badge text-bg-light">{{ displayStatus(company.status) }}</span></td>
              <td>{{ formatDate(company.created_at) }}</td>
              <td>
                <div class="d-flex gap-2">
                  <template v-if="(company.status || 'pending') === 'pending'">
                    <button class="btn btn-sm btn-success" @click="setStatus(company.id, 'active')"><i class="bi bi-check-lg"></i></button>
                    <button class="btn btn-sm btn-warning" @click="setStatus(company.id, 'reject')"><i class="bi bi-x-lg"></i></button>
                  </template>
                  <template v-else>
                    <button class="btn btn-sm btn-danger" @click="setStatus(company.id, 'delete')"><i class="bi bi-trash"></i></button>
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

const companies = ref([])
const stats = ref({
  total_companies: 0,
  approved_companies: 0,
  pending_companies: 0
})

const statsCards = computed(() => [
  { key: 't', label: t('total_companies'), value: stats.value.total_companies },
  { key: 'a', label: t('approved'), value: stats.value.approved_companies },
  { key: 'p', label: t('pending'), value: stats.value.pending_companies }
])

const loadCompanies = async () => {
  const { data } = await adminAPI.getCompanies()
  companies.value = data.companies || []
  stats.value = data.stats || stats.value
}

const setStatus = async (id, action) => {
  try {
    if (action === 'delete' && !confirm(t('confirm_delete_company'))) return
    if (action === 'active') await adminAPI.approveCompany(id)
    if (action === 'reject') await adminAPI.rejectCompany(id)
    if (action === 'delete') await adminAPI.deleteCompany(id)
    await loadCompanies()
  } catch (error) {
    alert(error?.response?.data?.message || t('failed_update_company'))
  }
}

const formatDate = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString()
}

const displayStatus = (status) => {
  if (!status) return t('pending')
  if (status === 'active') return t('approved')
  if (status === 'rejected') return t('rejected')
  return status
}

onMounted(loadCompanies)
</script>

<style scoped>
.admin-companies-page { padding: 20px 0; }
.stat-card, .table-container { background: var(--card-bg); border-radius: 16px; padding: 20px; border: 1px solid var(--border-color); }
.table { min-width: 760px; }
:deep([dir="rtl"]) .admin-companies-page .table th,
:deep([dir="rtl"]) .admin-companies-page .table td { text-align: right; }
</style>
