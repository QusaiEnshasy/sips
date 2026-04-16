<template>
  <div class="admin-dashboard">
    <div class="dashboard-header mb-4">
      <h2 class="fw-bold mb-1">{{ t('admin_dashboard') }}</h2>
      <p class="text-muted mb-0">{{ t('manage_text') }}</p>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-12 col-sm-6 col-md-3" v-for="card in cards" :key="card.key">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small">{{ card.label }}</div>
              <div class="h4 fw-bold mb-0">{{ card.value }}</div>
            </div>
            <i :class="card.icon"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="panel-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">{{ t('pending_companies') }}</h5>
            <router-link to="/admin/companies" class="btn btn-sm btn-outline-primary">{{ t('open') }}</router-link>
          </div>

          <div v-if="pendingCompanies.length === 0" class="text-muted">{{ t('no_pending_companies') }}</div>
          <div v-for="company in pendingCompanies" :key="company.id" class="item-row">
            <div>
              <div class="fw-semibold">{{ company.company_name || company.name }}</div>
              <small class="text-muted">{{ company.email }}</small>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-success btn-sm" @click="updateCompany(company.id, 'active')"><i class="bi bi-check-lg"></i></button>
              <button class="btn btn-danger btn-sm" @click="updateCompany(company.id, 'reject')"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="panel-card">
          <h5 class="fw-bold mb-3">{{ t('recent_activity') }}</h5>
          <div v-if="recentActivities.length === 0" class="text-muted">{{ t('no_recent_activity') }}</div>
          <div v-for="item in recentActivities" :key="item.id" class="item-row">
            <div>
              <div class="fw-semibold">{{ item.name }}</div>
              <small class="text-muted">{{ item.role }} - {{ item.email }}</small>
            </div>
            <span class="badge text-bg-light">{{ item.status || 'pending' }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { adminAPI } from '@/services/api/admin'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()

const loading = ref(false)
const stats = ref({
  total_users: 0,
  total_companies: 0,
  total_all: 0,
  pending_companies: 0
})
const pendingCompanies = ref([])
const recentActivities = ref([])

const cards = computed(() => [
  { key: 'u', label: t('total_users'), value: stats.value.total_users, icon: 'bi bi-people-fill h4 mb-0 text-primary' },
  { key: 'c', label: t('total_companies'), value: stats.value.total_companies, icon: 'bi bi-building h4 mb-0 text-success' },
  { key: 'a', label: t('all_accounts'), value: stats.value.total_all, icon: 'bi bi-person-badge h4 mb-0 text-warning' },
  { key: 'p', label: t('pending_companies'), value: stats.value.pending_companies, icon: 'bi bi-clock-history h4 mb-0 text-danger' }
])

const loadDashboard = async () => {
  loading.value = true
  try {
    const { data } = await adminAPI.getDashboard()
    stats.value = data.stats || stats.value
    pendingCompanies.value = data.pending_companies || []
    recentActivities.value = data.recent_activities || []
  } finally {
    loading.value = false
  }
}

const updateCompany = async (id, action) => {
  if (action === 'active') {
    await adminAPI.approveCompany(id)
  } else {
    await adminAPI.rejectCompany(id)
  }
  await loadDashboard()
}

onMounted(loadDashboard)
</script>

<style scoped>
.admin-dashboard { padding: 20px 0; }
.stat-card, .panel-card { background: var(--card-bg); border-radius: 16px; padding: 20px; border: 1px solid var(--border-color); }
.item-row { display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid var(--border-color); padding:12px 0; }
.item-row:last-child { border-bottom:none; }
</style>
