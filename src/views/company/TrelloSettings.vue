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
            <h2 class="fw-bold mb-2">تكامل Trello</h2>
            <p class="text-muted mb-0">اربط حساب Trello الخاص بالشركة وزامن البطاقات كمهام للطلاب.</p>
          </div>
        </div>
        <router-link to="/company/dashboard" class="btn-back">
          <i class="bi bi-arrow-left me-2"></i>
          العودة للوحة التحكم
        </router-link>
        <button type="button" class="btn-trello-direct" @click="openTrelloHome">
          <i class="bi bi-box-arrow-up-right me-2"></i>
          فتح Trello الرسمي
        </button>
      </div>
    </div>

    <div class="row g-4">
      <!-- Official Trello Authorization Card -->
      <div class="col-lg-6">
        <div class="settings-card" data-aos="fade-up">
          <div class="card-header-custom">
            <i class="bi bi-shield-lock"></i>
            <h5 class="fw-bold mb-0">الربط الرسمي مع Trello</h5>
          </div>

          <div v-if="hasTrello" class="connected-oauth-box mb-4">
            <div>
              <strong>حساب Trello مربوط</strong>
              <span>التوكن محفوظ تلقائيا بالخلفية ومشفر داخل النظام.</span>
            </div>
            <i class="bi bi-check2-circle"></i>
          </div>

          <div v-else class="alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>
            اضغط الزر وسيفتح Trello بشكل طبيعي. بعد الموافقة سيرجعك للنظام ويحفظ Token تلقائيا بدون نسخه أو كتابته.
          </div>

          <div class="d-flex gap-3 flex-wrap">
            <button class="btn-accent-gradient" @click="connectWithTrello" :disabled="isAuthorizing">
              <span v-if="isAuthorizing" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-box-arrow-up-right me-2"></i>
              {{ hasTrello ? 'إعادة ربط Trello' : 'الدخول إلى Trello وربط الحساب' }}
            </button>

            <button v-if="hasTrello" class="btn-outline" @click="testConnection" :disabled="isTesting">
              <span v-if="isTesting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-plug me-2"></i>
              اختبار الاتصال
            </button>

            <button v-if="hasTrello" class="btn-outline text-danger" @click="disconnectTrello">
              <i class="bi bi-unlink me-2"></i>
              فصل Trello
            </button>
          </div>

          <div v-if="connectionStatus" class="connection-status mt-3" :class="connectionStatusClass">
            <i :class="connectionStatusIcon"></i>
            {{ connectionStatus }}
          </div>
        </div>
      </div>

      <!-- API Settings Card -->
      <div v-if="false" class="col-lg-6">
        <div class="settings-card" data-aos="fade-up">
          <div class="card-header-custom">
            <i class="bi bi-key"></i>
            <h5 class="fw-bold mb-0">إعدادات الربط</h5>
          </div>
          
          <div class="alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>
            تحتاج الشركة إلى Token من حساب Trello الخاص بها. يمكن ترك API Key فارغًا لاستخدام مفتاح النظام.
            <a href="https://trello.com/power-ups/admin" target="_blank" class="ms-2">
              فتح Trello Power-Ups <i class="bi bi-box-arrow-up-right"></i>
            </a>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-bold">API Key <span class="text-muted small">(اختياري)</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-key"></i></span>
              <input type="text" class="form-control" v-model="apiKey" placeholder="اتركه فارغًا لاستخدام مفتاح النظام">
            </div>
            <div class="form-text">لا تضع الإيميل هنا. هذه الخانة للمفتاح الطويل فقط، ويمكن تركها فارغة.</div>
          </div>
          
          <div class="mb-4">
            <label class="form-label fw-bold">Token الخاص بحساب الشركة</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input :type="showToken ? 'text' : 'password'" class="form-control" v-model="apiToken" placeholder="الصق Token الذي أخذته من Trello">
              <button class="input-group-text" @click="showToken = !showToken">
                <i :class="showToken ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
              </button>
            </div>
          </div>
          
          <div class="d-flex gap-3">
            <button class="btn-accent-gradient" @click="saveSettings" :disabled="isSaving">
              <i class="bi bi-save me-2"></i>
              حفظ الإعدادات
            </button>
            <button class="btn-outline" @click="testConnection" :disabled="isTesting">
              <i class="bi bi-plug me-2"></i>
              اختبار الاتصال
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
            <h5 class="fw-bold mb-0">اللوحات المتاحة</h5>
          </div>
          
          <div v-if="isLoadingBoards" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-3">جاري تحميل اللوحات...</p>
          </div>
          
          <div v-else-if="boards.length === 0" class="empty-state text-center py-5">
            <i class="bi bi-kanban fs-1 text-muted"></i>
            <p class="text-muted mt-3">لا توجد لوحات ظاهرة</p>
            <p class="small text-muted">احفظ Token صحيح أولًا، ثم اختبر الاتصال.</p>
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
              ربط
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="trello-rules-card mt-4" data-aos="fade-up" data-aos-delay="150">
      <div class="automation-content">
        <div class="automation-icon">
          <i class="bi bi-list-check"></i>
        </div>
        <div>
          <h5 class="fw-bold mb-1">شروط إنشاء المهمة داخل Trello</h5>
          <p class="text-muted mb-1">أنشئ الكرت داخل Trello الحقيقي في القائمة المرتبطة بالبرنامج، ثم اضغط مزامنة من هنا.</p>
          <small class="text-muted d-block">إذا أردت المهمة لكل طلاب التدريب، أنشئ الكرت بشكل طبيعي داخل Trello وسيظهر تلقائيا.</small>
          <small class="text-muted d-block">إذا أردتها لطالب محدد فقط، اكتب داخل العنوان أو الوصف مثل: <strong>student: student@email.com</strong> أو <strong>student_id: 123456</strong>.</small>
        </div>
      </div>
    </div>

    <!-- Connected Internships -->
    <div class="integrations-card mt-4" data-aos="fade-up" data-aos-delay="200">
      <div class="card-header-custom">
        <i class="bi bi-link-45deg"></i>
        <h5 class="fw-bold mb-0">البرامج المرتبطة</h5>
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
                <span class="mx-2">|</span>
                <span>
                  جديد: {{ int.latest_log?.created_count ?? 0 }} |
                  محدث: {{ int.latest_log?.updated_count ?? 0 }} |
                  متخطى: {{ int.latest_log?.skipped_count ?? 0 }}
                </span>
                <span v-if="int.sync_status === 'syncing'" class="text-warning ms-2">
                  <i class="bi bi-hourglass-split"></i> {{ t('syncing') }}
                </span>
                <span v-else-if="int.sync_status === 'success'" class="text-success ms-2">
                  <i class="bi bi-check-circle"></i> {{ t('synced') }}
                </span>
                <span v-else-if="int.sync_status === 'idle'" class="text-muted ms-2">
                  <i class="bi bi-pause-circle"></i> {{ t('not_synced_yet') || 'Not synced yet' }}
                </span>
                <span v-else class="text-danger ms-2">
                  <i class="bi bi-exclamation-circle"></i> {{ t('sync_failed') }}
                </span>
              </div>
            </div>
          </div>
          <div class="integration-actions">
            <button class="btn-icon-accent" @click="openTrelloBoard(int)" title="فتح Trello">
              <i class="bi bi-box-arrow-up-right"></i>
            </button>
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

    <div class="integrations-card mt-4" data-aos="fade-up" data-aos-delay="250">
      <div class="card-header-custom">
        <i class="bi bi-clock-history"></i>
        <h5 class="fw-bold mb-0">سجل المزامنة</h5>
      </div>

      <div v-if="syncLogs.length === 0" class="empty-state-small text-center py-4">
        <i class="bi bi-inbox fs-3 text-muted"></i>
        <p class="text-muted mt-2 mb-0">لا توجد عمليات مزامنة بعد.</p>
      </div>

      <div v-else class="sync-log-list">
        <div v-for="log in syncLogs" :key="log.id" class="sync-log-item">
          <div>
            <div class="fw-bold">{{ log.program || 'برنامج غير محدد' }}</div>
            <small class="text-muted">
              {{ log.trigger === 'manual' ? 'يدوي' : 'تلقائي سابق' }} |
              {{ formatDate(log.finished_at || log.started_at) }}
            </small>
          </div>
          <div class="sync-log-counts">
            <span class="badge bg-success">جديد {{ log.created }}</span>
            <span class="badge bg-primary">محدث {{ log.updated }}</span>
            <span class="badge bg-secondary">متخطى {{ log.skipped }}</span>
            <span class="badge" :class="log.status === 'success' ? 'bg-success' : (log.status === 'failed' ? 'bg-danger' : 'bg-warning')">
              {{ log.status }}
            </span>
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

              <div class="mb-4">
                <label class="form-label fw-bold">نمط توزيع المهام على الطلاب</label>
                <select class="form-select" v-model="assignmentMode">
                  <option value="all">كل الطلاب المقبولين بهذا التدريب</option>
                  <option value="marker_required">حسب تحديد الطالب داخل الكرت فقط</option>
                  <option value="selected">طلاب محددين فقط</option>
                </select>
                <div class="small text-muted mt-1">
                  هذا الخيار يحدد من سيرى الكروت الجديدة إذا لم تضف `student:` داخل الكرت.
                </div>
              </div>

              <div v-if="assignmentMode === 'selected'" class="mb-4">
                <label class="form-label fw-bold">اختر الطلاب المستهدفين</label>
                <div v-if="isLoadingInternshipStudents" class="small text-muted mb-2">
                  <i class="bi bi-hourglass-split"></i> جاري تحميل الطلاب...
                </div>
                <div v-else-if="internshipStudents.length === 0" class="small text-danger mb-2">
                  لا يوجد طلاب مقبولين حاليًا ضمن هذا التدريب.
                </div>
                <div v-else class="students-picker">
                  <label v-for="student in internshipStudents" :key="student.id" class="student-chip">
                    <input
                      type="checkbox"
                      :value="student.id"
                      v-model="selectedTargetStudentIds"
                    />
                    <span>{{ student.name }} - {{ student.email }}</span>
                  </label>
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
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from '@/composables/useI18n'
import { companyAPI } from '@/services/api/company'
import AOS from 'aos'

const { t, formatDate } = useI18n()

// State
const hasTrello = ref(false)
const isAuthorizing = ref(false)
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
const syncLogs = ref([])
const internships = ref([])
const lists = ref([])
const showConnectModal = ref(false)
const selectedBoard = ref(null)
const selectedBoardId = ref('')
const selectedInternshipId = ref('')
const selectedListId = ref('')
const assignmentMode = ref('all')
const internshipStudents = ref([])
const selectedTargetStudentIds = ref([])
const isLoadingInternshipStudents = ref(false)

// Computed
const connectionStatusIcon = computed(() => {
  if (connectionStatusClass.value === 'text-success') return 'bi bi-check-circle-fill'
  if (connectionStatusClass.value === 'text-danger') return 'bi bi-x-circle-fill'
  return 'bi bi-info-circle'
})

const trelloOAuthMessages = {
  email_mismatch: 'حساب Trello يجب أن يكون بنفس إيميل حساب الشركة داخل النظام.',
  missing_oauth_data: 'Trello لم يرجع بيانات الربط المطلوبة. حاول الربط مرة أخرى.',
  session_expired: 'انتهت جلسة الربط. اضغط ربط Trello مرة أخرى من نفس المتصفح.',
  profile_failed: 'تمت الموافقة لكن تعذر قراءة بيانات حساب Trello.',
  oauth_failed: 'تمت الموافقة لكن فشل حفظ الربط. سنعرض السبب في سجل Laravel.',
  access_denied: 'تم إلغاء الموافقة من Trello.',
}

const handleTrelloOAuthResult = () => {
  const params = new URLSearchParams(window.location.search)
  const connected = params.get('trello_connected')
  const error = params.get('trello_error')

  if (connected) {
    connectionStatus.value = 'تم ربط Trello بنجاح وحفظ التوكن بالخلفية.'
    connectionStatusClass.value = 'text-success'
  } else if (error) {
    connectionStatus.value = trelloOAuthMessages[error] || `تعذر ربط Trello: ${error}`
    connectionStatusClass.value = 'text-danger'
  } else {
    return
  }

  window.history.replaceState({}, document.title, window.location.pathname)
}

// Methods
const loadSettings = async () => {
  try {
    const response = await companyAPI.getTrelloSettings()
    const settings = response.data.data || {}
    hasTrello.value = !!settings.has_trello
    apiKey.value = settings.has_trello ? '' : apiKey.value
    apiToken.value = ''
    if (settings.has_trello) {
      await loadBoards()
    } else {
      boards.value = []
    }
  } catch (error) {
    console.error('Failed to load Trello settings:', error)
  }
}

const connectWithTrello = async () => {
  isAuthorizing.value = true
  connectionStatus.value = ''
  window.location.href = '/company/trello/connect'
  return

  const verifier = window.prompt('بعد ما تضغط Allow في Trello، انسخ كود الموافقة الذي يظهر والصقه هنا. هذا ليس Token، فقط كود مؤقت لتوليد التوكن بالخلفية.')

  if (!verifier) {
    isAuthorizing.value = false
    connectionStatus.value = 'تم إلغاء الربط. اضغط الزر مرة أخرى إذا أردت المحاولة.'
    connectionStatusClass.value = 'text-info'
    return
  }

  try {
    await companyAPI.completeTrelloPinAuthorization({ oauth_verifier: verifier.trim() })
    connectionStatus.value = 'تم ربط Trello بنجاح وحفظ التوكن بالخلفية.'
    connectionStatusClass.value = 'text-success'
    await loadSettings()
    await loadIntegrations()
  } catch (error) {
    connectionStatus.value = error?.response?.data?.message || 'تعذر إكمال ربط Trello. تأكد من الكود وحاول مرة أخرى.'
    connectionStatusClass.value = 'text-danger'
  } finally {
    isAuthorizing.value = false
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
    const response = await companyAPI.getTrelloIntegrations()
    integrations.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load integrations:', error)
  } finally {
    isLoadingIntegrations.value = false
  }
}

const loadSyncLogs = async () => {
  try {
    const response = await companyAPI.getTrelloSyncLogs()
    syncLogs.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load Trello sync logs:', error)
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
  if (!apiToken.value) {
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
    connectionStatus.value = error?.response?.data?.message || t('error_saving_settings')
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
    connectionStatus.value = error?.response?.data?.message || t('connection_failed')
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
  assignmentMode.value = 'all'
  internshipStudents.value = []
  selectedTargetStudentIds.value = []
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

  if (assignmentMode.value === 'selected' && selectedTargetStudentIds.value.length === 0) {
    alert('اختر طالبًا واحدًا على الأقل عند استخدام نمط طلاب محددين.')
    return
  }
  
  isConnecting.value = true
  try {
    await companyAPI.connectTrelloBoard(selectedInternshipId.value, {
      board_id: selectedBoardId.value,
      list_id: selectedListId.value,
      list_name: lists.value.find((l) => l.id === selectedListId.value)?.name || null,
      board_name: selectedBoard.value?.name,
      assignment_mode: assignmentMode.value,
      target_student_ids: assignmentMode.value === 'selected' ? selectedTargetStudentIds.value : []
    })
    alert(t('connected_successfully'))
    closeConnectModal()
    await loadIntegrations()
    await loadSettings()
  } catch (error) {
    alert(error?.response?.data?.message || t('connection_failed'))
  } finally {
    isConnecting.value = false
  }
}

const syncInternship = async (integration) => {
  try {
    const response = await companyAPI.syncTrello(integration.internship_id)
    const result = response.data?.data || {}
    alert(`تمت المزامنة: جديد ${result.created || 0}، محدث ${result.updated || 0}، متخطى ${result.skipped || 0}`)
    await loadIntegrations()
    await loadSyncLogs()
  } catch (error) {
    alert(t('sync_failed'))
  }
}

const openTrelloBoard = (integration) => {
  const url = integration?.board_url || (integration?.board_id ? `https://trello.com/b/${integration.board_id}` : '')
  if (url) {
    window.open(url, '_blank', 'noopener')
  }
}

const openTrelloHome = () => {
  window.open('https://trello.com/', '_blank', 'noopener')
}

const disconnectTrello = async () => {
  if (!confirm('سيتم فصل حساب Trello من النظام. هل أنت متأكد؟')) return

  try {
    await companyAPI.disconnectTrello()
    hasTrello.value = false
    boards.value = []
    integrations.value = []
    syncLogs.value = []
    connectionStatus.value = 'تم فصل Trello بنجاح.'
    connectionStatusClass.value = 'text-success'
  } catch (error) {
    alert(error?.response?.data?.message || 'تعذر فصل Trello.')
  }
}

const disconnectInternship = async (integration) => {
  if (confirm(t('confirm_disconnect'))) {
    try {
      await companyAPI.unlinkTrelloInternship(integration.internship_id)
      await loadIntegrations()
      await loadSyncLogs()
      alert(t('disconnected'))
    } catch (error) {
      alert(error?.response?.data?.message || t('disconnect_failed'))
    }
  }
}

const closeConnectModal = () => {
  showConnectModal.value = false
  selectedBoard.value = null
  selectedBoardId.value = ''
  selectedInternshipId.value = ''
  selectedListId.value = ''
  assignmentMode.value = 'all'
  internshipStudents.value = []
  selectedTargetStudentIds.value = []
  lists.value = []
}

const loadInternshipStudents = async (internshipId) => {
  if (!internshipId) {
    internshipStudents.value = []
    selectedTargetStudentIds.value = []
    return
  }

  isLoadingInternshipStudents.value = true
  try {
    const response = await companyAPI.getInternshipStudents(internshipId)
    internshipStudents.value = response.data.data || []
    selectedTargetStudentIds.value = selectedTargetStudentIds.value
      .map((id) => Number(id))
      .filter((id) => internshipStudents.value.some((student) => Number(student.id) === id))
  } catch (error) {
    internshipStudents.value = []
    selectedTargetStudentIds.value = []
    console.error('Failed to load internship students:', error)
  } finally {
    isLoadingInternshipStudents.value = false
  }
}

onMounted(() => {
  AOS.init({ duration: 800, once: true })
  handleTrelloOAuthResult()
  loadSettings()
  loadIntegrations()
  loadSyncLogs()
  loadInternships()
})

watch(selectedInternshipId, (internshipId) => {
  loadInternshipStudents(internshipId)
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

.btn-trello-direct {
  background: linear-gradient(135deg, #0079bf, #026aa7);
  border: none;
  border-radius: 30px;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  padding: 9px 20px;
  transition: all .25s ease;
}

.btn-trello-direct:hover {
  box-shadow: 0 10px 22px rgba(0, 121, 191, .25);
  transform: translateY(-1px);
}

.settings-card, .boards-card, .integrations-card, .automation-card, .trello-rules-card {
  background: var(--card-bg);
  border-radius: 24px;
  padding: 24px;
  border: 1px solid var(--border-color);
}

.automation-card, .trello-rules-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  background:
    radial-gradient(circle at 10% 0%, rgba(0, 121, 191, .16), transparent 35%),
    var(--card-bg);
}

.automation-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.automation-icon {
  width: 54px;
  height: 54px;
  border-radius: 16px;
  background: linear-gradient(135deg, #0079bf, #026aa7);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
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

.connected-oauth-box {
  align-items: center;
  background: #e8f8ef;
  border: 1px solid #b7ebca;
  border-radius: 16px;
  display: flex;
  gap: 14px;
  justify-content: space-between;
  padding: 16px;
}

.connected-oauth-box strong {
  color: #166534;
  display: block;
  font-weight: 800;
}

.connected-oauth-box span {
  color: #3f7c56;
  display: block;
  font-size: 13px;
  margin-top: 4px;
}

.connected-oauth-box i {
  color: #16a34a;
  font-size: 30px;
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

.sync-log-list {
  display: grid;
  gap: 10px;
}

.sync-log-item {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  padding: 12px;
  border: 1px solid var(--border-color);
  border-radius: 14px;
}

.sync-log-counts {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  justify-content: flex-end;
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

.students-picker {
  border: 1px solid var(--border-color);
  border-radius: 10px;
  max-height: 190px;
  overflow-y: auto;
  padding: 10px;
  display: grid;
  gap: 8px;
}

.student-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--input-bg);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 8px 10px;
  font-size: 13px;
}

@media (max-width: 768px) {
  .board-item, .integration-item, .automation-card, .trello-rules-card, .automation-content, .sync-log-item {
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
