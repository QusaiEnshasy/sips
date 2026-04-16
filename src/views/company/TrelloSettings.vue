<template>
  <div class="trello-settings-page">
    <!-- Header -->
    <div class="page-header mb-4" data-aos="fade-down">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="header-icon">
            <i class="bi bi-trello"></i>
          </div>
          <div>
            <h2 class="fw-bold mb-2">{{ t('trello_integration') }}</h2>
            <p class="text-muted mb-0">{{ t('connect_trello_manage_tasks') }}</p>
          </div>
        </div>
        <router-link to="/company/dashboard" class="btn-back">
          <i class="bi bi-arrow-left me-2"></i>
          {{ t('back_dashboard') }}
        </router-link>
      </div>
    </div>

    <div class="row g-4">
      <!-- API Settings Card -->
      <div class="col-lg-6">
        <div class="settings-card" data-aos="fade-up">
          <div class="card-header-custom">
            <i class="bi bi-key"></i>
            <h5 class="fw-bold mb-0">{{ t('api_settings') }}</h5>
          </div>
          
          <div class="alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>
            {{ t('trello_api_help') }}
            <a href="https://trello.com/power-ups/admin" target="_blank" class="ms-2">
              {{ t('get_api_key') }} <i class="bi bi-box-arrow-up-right"></i>
            </a>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-bold">{{ t('api_key') }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-key"></i></span>
              <input type="text" class="form-control" v-model="apiKey" :placeholder="t('enter_api_key')">
            </div>
          </div>
          
          <div class="mb-4">
            <label class="form-label fw-bold">{{ t('api_token') }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input :type="showToken ? 'text' : 'password'" class="form-control" v-model="apiToken" :placeholder="t('enter_api_token')">
              <button class="input-group-text" @click="showToken = !showToken">
                <i :class="showToken ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
              </button>
            </div>
          </div>
          
          <div class="d-flex gap-3">
            <button class="btn-accent-gradient" @click="saveSettings" :disabled="isSaving">
              <i class="bi bi-save me-2"></i>
              {{ t('save_settings') }}
            </button>
            <button class="btn-outline" @click="testConnection" :disabled="isTesting">
              <i class="bi bi-plug me-2"></i>
              {{ t('test_connection') }}
            </button>
          </div>
          
          <div v-if="connectionStatus" class="connection-status mt-3" :class="connectionStatusClass">
            <i :class="connectionStatusIcon"></i>
            {{ connectionStatus }}
          </div>
        </div>
      </div>

      <!-- Available Boards Card -->
      <div class="col-lg-6">
        <div class="boards-card" data-aos="fade-up" data-aos-delay="100">
          <div class="card-header-custom">
            <i class="bi bi-kanban"></i>
            <h5 class="fw-bold mb-0">{{ t('available_boards') }}</h5>
          </div>
          
          <div v-if="isLoadingBoards" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-3">{{ t('loading_boards') }}</p>
          </div>
          
          <div v-else-if="boards.length === 0" class="empty-state text-center py-5">
            <i class="bi bi-kanban fs-1 text-muted"></i>
            <p class="text-muted mt-3">{{ t('no_boards_found') }}</p>
            <p class="small text-muted">{{ t('save_api_key_first') }}</p>
          </div>
          
          <div v-else class="boards-list">
            <div v-for="board in boards" :key="board.id" class="board-item">
              <div class="board-info">
                <div class="board-icon" :style="{ background: board.prefs?.backgroundColor || '#7c3aed' }">
                  <i class="bi bi-kanban"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0">{{ board.name }}</h6>
                  <small class="text-muted">{{ board.desc?.substring(0, 60) || t('no_description') }}</small>
                </div>
              </div>
              <button class="btn-accent-outline" @click="openConnectModal(board)">
                <i class="bi bi-link me-1"></i>
                {{ t('connect') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Connected Internships -->
    <div class="integrations-card mt-4" data-aos="fade-up" data-aos-delay="200">
      <div class="card-header-custom">
        <i class="bi bi-link-45deg"></i>
        <h5 class="fw-bold mb-0">{{ t('connected_internships') }}</h5>
        <span class="badge ms-2" :class="integrations.length ? 'bg-primary' : 'bg-secondary'">
          {{ integrations.length }}
        </span>
      </div>
      
      <div v-if="isLoadingIntegrations" class="text-center py-4">
        <div class="spinner-border spinner-border-sm text-primary"></div>
      </div>
      
      <div v-else-if="integrations.length === 0" class="empty-state-small text-center py-4">
        <i class="bi bi-link fs-3 text-muted"></i>
        <p class="text-muted mt-2 mb-0">{{ t('no_integrations') }}</p>
      </div>
      
      <div v-else>
        <div v-for="int in integrations" :key="int.id" class="integration-item">
          <div class="integration-info">
            <div class="integration-icon">
              <i class="bi bi-journal-bookmark"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-0">{{ int.internship_title }}</h6>
              <div class="integration-meta">
                <span><i class="bi bi-kanban me-1"></i> {{ int.board_name }}</span>
                <span class="mx-2">•</span>
                <span><i class="bi bi-arrow-repeat me-1"></i> {{ t('last_sync') }}: {{ formatDate(int.last_sync) }}</span>
                <span v-if="int.sync_status === 'قيد_المزامنة'" class="text-warning ms-2">
                  <i class="bi bi-hourglass-split"></i> {{ t('syncing') }}
                </span>
                <span v-else-if="int.sync_status === 'ناجح'" class="text-success ms-2">
                  <i class="bi bi-check-circle"></i> {{ t('synced') }}
                </span>
                <span v-else class="text-danger ms-2">
                  <i class="bi bi-exclamation-circle"></i> {{ t('sync_failed') }}
                </span>
              </div>
            </div>
          </div>
          <div class="integration-actions">
            <button class="btn-icon-accent" @click="syncInternship(int)" :title="t('sync_now')">
              <i class="bi bi-arrow-repeat"></i>
            </button>
            <button class="btn-icon-accent text-danger" @click="disconnectInternship(int)" :title="t('disconnect')">
              <i class="bi bi-unlink"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Connect Modal -->
    <Teleport to="body">
      <div v-if="showConnectModal" class="modal-overlay" @click.self="closeConnectModal">
        <div class="modal-content animate__animated animate__fadeInUp">
          <div class="modal-header">
            <h5 class="fw-bold mb-0">{{ t('connect_internship') }} - {{ selectedBoard?.name }}</h5>
            <button class="btn-close" @click="closeConnectModal">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="connectInternship">
              <div class="mb-4">
                <label class="form-label fw-bold">{{ t('select_internship') }}</label>
                <select class="form-select" v-model="selectedInternshipId" required>
                  <option value="">{{ t('select') }}</option>
                  <option v-for="internship in internships" :key="internship.id" :value="internship.id">
                    {{ internship.title }}
                  </option>
                </select>
              </div>
              
              <div class="mb-4">
                <label class="form-label fw-bold">{{ t('select_list') }}</label>
                <select class="form-select" v-model="selectedListId" required :disabled="isLoadingLists">
                  <option value="">{{ t('select_list_first') }}</option>
                  <option v-for="list in lists" :key="list.id" :value="list.id">
                    {{ list.name }}
                  </option>
                </select>
                <div v-if="isLoadingLists" class="small text-muted mt-1">
                  <i class="bi bi-hourglass-split"></i> {{ t('loading_lists') }}
                </div>
              </div>
              
              <div class="info-box mb-4">
                <i class="bi bi-info-circle"></i>
                <span>{{ t('trello_connect_info') }}</span>
              </div>
              
              <button type="submit" class="btn-accent-gradient w-100" :disabled="isConnecting">
                <span v-if="!isConnecting">
                  <i class="bi bi-link me-2"></i>
                  {{ t('connect') }}
                </span>
                <span v-else>
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  {{ t('connecting') }}
                </span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from '@/composables/useI18n'
import { companyAPI } from '@/services/api/company'
import AOS from 'aos'

const { t, formatDate } = useI18n()

// State
const apiKey = ref('')
const apiToken = ref('')
const showToken = ref(false)
const isSaving = ref(false)
const isTesting = ref(false)
const isLoadingBoards = ref(false)
const isLoadingIntegrations = ref(false)
const isLoadingLists = ref(false)
const isConnecting = ref(false)
const connectionStatus = ref('')
const connectionStatusClass = ref('')
const boards = ref([])
const integrations = ref([])
const internships = ref([])
const lists = ref([])
const showConnectModal = ref(false)
const selectedBoard = ref(null)
const selectedBoardId = ref('')
const selectedInternshipId = ref('')
const selectedListId = ref('')

// Computed
const connectionStatusIcon = computed(() => {
  if (connectionStatusClass.value === 'text-success') return 'bi bi-check-circle-fill'
  if (connectionStatusClass.value === 'text-danger') return 'bi bi-x-circle-fill'
  return 'bi bi-info-circle'
})

// Methods
const loadSettings = async () => {
  try {
    const response = await companyAPI.getTrelloSettings()
    if (response.data.data?.has_trello) {
      apiKey.value = '••••••••••••••••'
      apiToken.value = '••••••••••••••••'
    }
  } catch (error) {
    console.error('Failed to load Trello settings:', error)
  }
}

const loadBoards = async () => {
  isLoadingBoards.value = true
  try {
    const response = await companyAPI.getTrelloBoards()
    boards.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load boards:', error)
  } finally {
    isLoadingBoards.value = false
  }
}

const loadIntegrations = async () => {
  isLoadingIntegrations.value = true
  try {
    const response = await companyAPI.getTrelloIntegrations?.() || { data: { data: [] } }
    integrations.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load integrations:', error)
  } finally {
    isLoadingIntegrations.value = false
  }
}

const loadInternships = async () => {
  try {
    const response = await companyAPI.getPrograms()
    internships.value = response.data.data?.programs || []
  } catch (error) {
    console.error('Failed to load internships:', error)
  }
}

const saveSettings = async () => {
  if (!apiKey.value || !apiToken.value) {
    connectionStatus.value = t('fill_api_credentials')
    connectionStatusClass.value = 'text-danger'
    return
  }
  
  isSaving.value = true
  try {
    await companyAPI.saveTrelloSettings({
      trello_api_key: apiKey.value,
      trello_token: apiToken.value
    })
    connectionStatus.value = t('settings_saved')
    connectionStatusClass.value = 'text-success'
    setTimeout(() => { connectionStatus.value = '' }, 3000)
    await loadBoards()
  } catch (error) {
    connectionStatus.value = t('error_saving_settings')
    connectionStatusClass.value = 'text-danger'
  } finally {
    isSaving.value = false
  }
}

const testConnection = async () => {
  isTesting.value = true
  connectionStatus.value = t('testing_connection')
  connectionStatusClass.value = 'text-info'
  try {
    await companyAPI.testTrelloConnection()
    connectionStatus.value = t('connection_successful')
    connectionStatusClass.value = 'text-success'
    await loadBoards()
  } catch (error) {
    connectionStatus.value = t('connection_failed')
    connectionStatusClass.value = 'text-danger'
  } finally {
    isTesting.value = false
    setTimeout(() => { connectionStatus.value = '' }, 3000)
  }
}

const openConnectModal = async (board) => {
  selectedBoard.value = board
  selectedBoardId.value = board.id
  selectedInternshipId.value = ''
  selectedListId.value = ''
  showConnectModal.value = true
  await loadLists(board.id)
}

const loadLists = async (boardId) => {
  isLoadingLists.value = true
  try {
    const response = await companyAPI.getTrelloLists(boardId)
    lists.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load lists:', error)
  } finally {
    isLoadingLists.value = false
  }
}

const connectInternship = async () => {
  if (!selectedInternshipId.value || !selectedListId.value) {
    alert(t('fill_all_fields'))
    return
  }
  
  isConnecting.value = true
  try {
    await companyAPI.connectTrelloBoard(selectedInternshipId.value, {
      board_id: selectedBoardId.value,
      list_id: selectedListId.value,
      board_name: selectedBoard.value?.name
    })
    alert(t('connected_successfully'))
    closeConnectModal()
    await loadIntegrations()
  } catch (error) {
    alert(t('connection_failed'))
  } finally {
    isConnecting.value = false
  }
}

const syncInternship = async (integration) => {
  try {
    await companyAPI.syncTrello(integration.internship_id)
    alert(t('sync_completed'))
    await loadIntegrations()
  } catch (error) {
    alert(t('sync_failed'))
  }
}

const disconnectInternship = async (integration) => {
  if (confirm(t('confirm_disconnect'))) {
    try {
      await companyAPI.disconnectTrello()
      await loadIntegrations()
      alert(t('disconnected'))
    } catch (error) {
      alert(t('disconnect_failed'))
    }
  }
}

const closeConnectModal = () => {
  showConnectModal.value = false
  selectedBoard.value = null
  selectedBoardId.value = ''
  selectedInternshipId.value = ''
  selectedListId.value = ''
  lists.value = []
}

onMounted(() => {
  AOS.init({ duration: 800, once: true })
  loadSettings()
  loadBoards()
  loadIntegrations()
  loadInternships()
})
</script>

<style scoped>
.trello-settings-page {
  padding: 20px 0;
}

.header-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #0079bf, #026aa7);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
}

.btn-back {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 30px;
  padding: 8px 20px;
  color: var(--text-muted);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-back:hover {
  background: var(--accent-soft);
  color: var(--accent);
  border-color: var(--accent);
}

.settings-card, .boards-card, .integrations-card {
  background: var(--card-bg);
  border-radius: 24px;
  padding: 24px;
  border: 1px solid var(--border-color);
}

.card-header-custom {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 2px solid var(--border-color);
}

.card-header-custom i {
  font-size: 24px;
  color: var(--accent);
}

.alert-info {
  background: #e0f2fe;
  color: #0369a1;
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 13px;
}

[data-theme="dark"] .alert-info {
  background: #0c4a6e;
  color: #bae6fd;
}

.alert-info a {
  color: #0284c7;
  text-decoration: none;
}

.btn-accent-gradient {
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  color: white;
  border: none;
  border-radius: 10px;
  padding: 10px 24px;
  font-weight: 600;
  transition: all 0.3s ease;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-accent-gradient:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(124, 58, 237, 0.3);
}

.btn-outline {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 10px 24px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-outline:hover {
  background: var(--accent-soft);
  border-color: var(--accent);
  color: var(--accent);
}

.btn-accent-outline {
  background: transparent;
  border: 1px solid var(--accent);
  color: var(--accent);
  border-radius: 8px;
  padding: 6px 16px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-accent-outline:hover {
  background: var(--accent);
  color: white;
}

.btn-icon-accent {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: var(--card-bg);
  color: var(--text-muted);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-icon-accent:hover {
  background: var(--accent-soft);
  color: var(--accent);
  border-color: var(--accent);
}

.boards-list {
  max-height: 400px;
  overflow-y: auto;
}

.board-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid var(--border-color);
}

.board-item:last-child {
  border-bottom: none;
}

.board-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.board-icon {
  width: 45px;
  height: 45px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
}

.integration-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid var(--border-color);
}

.integration-item:last-child {
  border-bottom: none;
}

.integration-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.integration-icon {
  width: 45px;
  height: 45px;
  background: var(--accent-soft);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent);
  font-size: 20px;
}

.integration-meta {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}

.integration-actions {
  display: flex;
  gap: 8px;
}

.empty-state {
  text-align: center;
  padding: 40px;
}

.empty-state-small {
  text-align: center;
  padding: 20px;
}

.connection-status {
  padding: 10px;
  border-radius: 8px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.text-success { color: #22c55e; }
.text-danger { color: #ef4444; }
.text-info { color: #3b82f6; }

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1100;
  padding: 20px;
}

.modal-content {
  background: var(--card-bg);
  border-radius: 24px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 24px;
}

.btn-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.info-box {
  background: var(--accent-soft);
  padding: 12px;
  border-radius: 10px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--accent);
}

.form-select, .form-control {
  background: var(--input-bg);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 10px 12px;
  color: var(--text-dark);
}

@media (max-width: 768px) {
  .board-item, .integration-item {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
  
  .integration-actions {
    width: 100%;
    justify-content: flex-end;
  }
}

@media (max-width: 576px) {
  .d-flex.gap-3 {
    flex-direction: column;
  }
  
  .btn-accent-gradient, .btn-outline {
    width: 100%;
    justify-content: center;
  }
}
</style>