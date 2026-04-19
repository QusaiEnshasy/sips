<template>
  <div class="jisr-program-page">
    <!-- Header -->
    <div class="page-header mb-4" data-aos="fade-down">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="header-icon">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <h2 class="fw-bold mb-2">برنامج الجسر</h2>
            <p class="text-muted mb-0">أكمل مهام برنامج الجسر للانتقال إلى المرحلة التالية</p>
          </div>
        </div>
        <router-link to="/student/dashboard" class="btn-back">
          <i class="bi bi-arrow-left me-2"></i>
          {{ t('back_dashboard') }}
        </router-link>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="text-muted mt-3">{{ t('loading') }}</p>
    </div>

    <div v-else>
      <!-- Program Progress -->
      <div class="progress-card mb-5" data-aos="fade-up">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="fw-bold mb-1">تقدم البرنامج</h5>
            <p class="text-muted small mb-0">هذا المسار التأهيلي يساعدك على تطوير مهاراتك قبل العودة للمسار الأساسي</p>
          </div>
          <div class="progress-percentage fw-bold">{{ completionRate }}%</div>
        </div>
        <div class="progress-container">
          <div class="progress">
            <div class="progress-bar bg-primary" :style="{ width: completionRate + '%' }"></div>
          </div>
        </div>
        <div class="d-flex justify-content-between mt-3">
          <small class="text-muted">{{ t('completed_tasks') }}: {{ completedCount }}/{{ totalTasks }}</small>
          <small class="text-muted">{{ t('points_earned') }}: {{ totalPoints }}/{{ maxPoints }}</small>
        </div>
      </div>

      <!-- Tasks Grid -->
      <div class="row g-4">
        <div v-for="task in tasks" :key="task.id" class="col-md-6 col-lg-4" data-aos="fade-up" :data-aos-delay="task.order_number * 50">
          <div class="task-card" :class="getTaskStatusClass(task.status)">
            <div class="task-header">
              <div class="task-number">#{{ task.order_number }}</div>
              <div class="task-badge" :class="getTypeClass(task.type)">
                <i :class="getTypeIcon(task.type)"></i>
                {{ getTypeText(task.type) }}
              </div>
            </div>

            <h5 class="fw-bold mb-2">{{ task.title }}</h5>
            <p class="text-muted small mb-3">{{ task.description }}</p>

            <div class="points-box mb-3">
              <i class="bi bi-award"></i>
              <span>{{ task.max_score }} نقطة</span>
            </div>

            <div class="status-badge" :class="getStatusBadgeClass(task.status)">
              <i :class="getStatusIcon(task.status)"></i>
              {{ getStatusText(task.status) }}
            </div>

            <div v-if="task.status === 'accepted' || task.status === 'rejected'" class="feedback-box mt-3">
              <div class="d-flex justify-content-between mb-2">
                <span class="fw-bold small">الدرجة</span>
                <span :class="task.score >= 70 ? 'text-success' : 'text-danger'">{{ task.score }}%</span>
              </div>
              <div v-if="task.feedback" class="feedback-text">
                <i class="bi bi-chat-left-quote me-1"></i>
                <span class="small">{{ task.feedback }}</span>
              </div>
            </div>

            <div v-if="task.attachments?.length" class="task-files-box mt-3">
              <div class="task-files-header">
                <span class="fw-bold small">الملفات المرفوعة</span>
                <span class="task-files-count">{{ task.attachments.length }} ملف</span>
              </div>
              <div class="task-files-list">
                <a
                  v-for="(attachment, attachmentIndex) in task.attachments"
                  :key="`${task.id}-${attachmentIndex}`"
                  :href="attachment.url"
                  class="task-file-link"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <i class="bi bi-paperclip"></i>
                  <span>{{ attachment.name }}</span>
                </a>
              </div>
            </div>

            <button class="task-action-btn mt-3" :class="getButtonClass(task.status)" 
                    @click="handleTaskAction(task)" :disabled="isSubmitting === task.id || task.status === 'pending_review'">
              <span v-if="isSubmitting === task.id">
                <span class="spinner-border spinner-border-sm me-2"></span>
                {{ t('submitting') }}
              </span>
              <span v-else>
                <i :class="getButtonIcon(task.status)"></i>
                {{ getButtonText(task.status) }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Task Modal -->
    <Teleport to="body">
      <div v-if="showSubmitModal" class="modal-overlay" @click.self="closeSubmitModal">
        <div class="modal-content animate__animated animate__fadeInUp" style="max-width: 700px;">
          <div class="modal-header">
            <h5 class="fw-bold mb-0">{{ t('submit_task') }}: {{ selectedTask?.title }}</h5>
            <button class="btn-close" @click="closeSubmitModal">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitTask">
              <div class="instruction-box mb-4">
                <h6 class="fw-bold mb-2">{{ t('task_instructions') }}</h6>
                <p class="text-muted small">{{ selectedTask?.instructions || t('default_instructions') }}</p>
              </div>

              <div class="mb-4">
                <label class="form-label fw-bold">{{ t('your_answer') }} <span class="text-danger">*</span></label>
                <textarea class="form-control" rows="6" v-model="submissionContent" 
                          :placeholder="t('write_your_answer')" required></textarea>
              </div>

              <div class="mb-4">
                <label class="form-label fw-bold">{{ t('attachments') }}</label>
                <div class="upload-zone" @click="triggerFileInput" @dragover.prevent @drop.prevent="handleDrop">
                  <i class="bi bi-cloud-arrow-up display-6 text-muted mb-2"></i>
                  <p class="small text-muted mb-0">{{ t('drag_files_or_click') }}</p>
                  <p class="text-muted small">{{ t('accepted_formats') }}</p>
                </div>
                <input type="file" ref="fileInput" multiple hidden @change="handleFileSelect" accept=".pdf,.doc,.docx,.zip,.jpg,.png">
                
                <div v-if="attachments.length > 0" class="attachments-list mt-3">
                  <div class="selected-files-summary">{{ attachments.length }} ملف جاهز للرفع</div>
                  <div v-for="(file, idx) in attachments" :key="idx" class="attachment-item">
                    <i :class="getFileIcon(file.name)"></i>
                    <span class="small">{{ file.name }}</span>
                    <button type="button" class="btn-remove-attachment" @click="removeAttachment(idx)">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>
              </div>

              <button type="submit" class="btn-accent-gradient w-100" :disabled="isSubmittingTask">
                <span v-if="!isSubmittingTask">
                  <i class="bi bi-send me-2"></i>
                  {{ t('submit_for_review') }}
                </span>
                <span v-else>
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  {{ t('submitting') }}
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
import { useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { studentAPI } from '@/services/api/student'
import { useAuthStore } from '@/stores/auth'
import AOS from 'aos'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()

// State
const isLoading = ref(false)
const tasks = ref([])
const selectedTask = ref(null)
const showSubmitModal = ref(false)
const submissionContent = ref('')
const attachments = ref([])
const fileInput = ref(null)
const isSubmitting = ref(null)
const isSubmittingTask = ref(false)

// Computed
const totalTasks = computed(() => tasks.value.length)
const completedCount = computed(() => tasks.value.filter(t => t.status === 'accepted').length)
const completionRate = computed(() => {
  if (totalTasks.value === 0) return 0
  return Math.round((completedCount.value / totalTasks.value) * 100)
})
const totalPoints = computed(() => {
  return tasks.value.filter(t => t.status === 'accepted').reduce((sum, t) => sum + (t.score || t.max_score), 0)
})
const maxPoints = computed(() => {
  return tasks.value.reduce((sum, t) => sum + t.max_score, 0)
})

// ✅ جلب مهام جسر من API
const loadTasks = async () => {
  isLoading.value = true
  try {
    const response = await studentAPI.getJisrTasks()
    const data = response.data.data
    
    tasks.value = (data.tasks || []).map(t => ({
      id: t.id,
      order_number: t.order_number,
      title: t.title,
      description: t.description,
      type: t.type,
      max_score: t.max_score,
      status: t.submission?.status || 'pending',
      score: t.submission?.score,
      feedback: t.submission?.feedback,
      instructions: t.instructions,
      content: t.submission?.content || '',
      attachments: t.submission?.attachments || []
    }))
    
  } catch (error) {
    console.error('Failed to load Jisr tasks:', error)
  } finally {
    isLoading.value = false
  }
}

const handleTaskAction = (task) => {
  if (task.status === 'pending' || task.status === 'rejected') {
    selectedTask.value = task
    submissionContent.value = task.status === 'rejected' ? (task.content || '') : ''
    attachments.value = []
    showSubmitModal.value = true
  } else if (task.status === 'accepted') {
    alert(task.feedback || t('task_submitted_success'))
  } else if (task.status === 'pending_review') {
    alert(t('waiting_review'))
  }
}

const submitTask = async () => {
  if (!submissionContent.value.trim()) {
    alert(t('please_enter_answer'))
    return
  }

  isSubmittingTask.value = true
  try {
    const formData = new FormData()
    formData.append('submission', submissionContent.value)
    attachments.value.forEach((file, index) => {
      formData.append(`attachments[${index}]`, file, file.name)
    })

    const response = await studentAPI.submitJisrTask(selectedTask.value.id, formData)

    if (response.data?.data?.user_state) {
      authStore.setUser({
        ...authStore.user,
        ...response.data.data.user_state
      })
    }
    
    // تحديث حالة المهمة
    const taskIndex = tasks.value.findIndex(t => t.id === selectedTask.value.id)
    if (taskIndex !== -1) {
      tasks.value[taskIndex].status = response.data?.data?.submission?.status || 'pending_review'
      tasks.value[taskIndex].score = response.data?.data?.submission?.score
      tasks.value[taskIndex].feedback = response.data?.data?.submission?.feedback || ''
      tasks.value[taskIndex].content = submissionContent.value
      tasks.value[taskIndex].attachments = response.data?.data?.submission?.attachments || attachments.value.map(file => ({
        name: file.name,
        url: null
      }))
    }
    
    closeSubmitModal()
    if (response.data?.data?.program_completed && response.data?.data?.next_path) {
      alert(t('task_submitted_success'))
      router.push(response.data.data.next_path)
      return
    }

    alert(response.data?.message || 'تم إرسال الحل وبانتظار تقييم المشرف.')
  } catch (error) {
    console.error('Failed to submit task:', error)
    alert(t('error_occurred'))
  } finally {
    isSubmittingTask.value = false
  }
}

const triggerFileInput = () => fileInput.value.click()
const handleFileSelect = (event) => addFiles(Array.from(event.target.files))
const handleDrop = (event) => addFiles(Array.from(event.dataTransfer.files))
const addFiles = (files) => {
  for (const file of files) {
    if (file.size > 10 * 1024 * 1024) {
      alert(t('file_too_large', { name: file.name }))
      continue
    }
    const duplicate = attachments.value.some(existing => (
      existing.name === file.name &&
      existing.size === file.size &&
      existing.lastModified === file.lastModified
    ))
    if (duplicate) continue
    attachments.value.push(file)
  }
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}
const removeAttachment = (index) => attachments.value.splice(index, 1)
const closeSubmitModal = () => {
  showSubmitModal.value = false
  selectedTask.value = null
  submissionContent.value = ''
  attachments.value = []
}

// Helper functions
const getTaskStatusClass = (status) => {
  if (status === 'accepted') return 'task-completed'
  if (status === 'pending_review') return 'task-pending-review'
  if (status === 'rejected') return 'task-rejected'
  return 'task-pending'
}
const getTypeClass = (type) => ({ practical: 'badge-practical', research: 'badge-research', theoretical: 'badge-theoretical' }[type] || 'badge-practical')
const getTypeIcon = (type) => ({ practical: 'bi bi-code-slash', research: 'bi bi-file-text', theoretical: 'bi bi-book' }[type] || 'bi bi-file')
const getTypeText = (type) => ({ practical: 'عملي', research: 'بحثي', theoretical: 'نظري' }[type] || 'مهمة')
const getStatusBadgeClass = (status) => ({ accepted: 'status-accepted', pending_review: 'status-review', rejected: 'status-rejected' }[status] || 'status-pending')
const getStatusIcon = (status) => ({ accepted: 'bi bi-check-circle-fill', pending_review: 'bi bi-clock-history', rejected: 'bi bi-x-circle-fill' }[status] || 'bi bi-hourglass-split')
const getStatusText = (status) => ({ accepted: 'تم الاعتماد', pending_review: 'بانتظار التقييم', rejected: 'مرفوض' }[status] || 'لم يتم التسليم')
const getButtonClass = (status) => ({ accepted: 'btn-view', pending_review: 'btn-pending', rejected: 'btn-retry' }[status] || 'btn-submit')
const getButtonIcon = (status) => ({ accepted: 'bi bi-eye', pending_review: 'bi bi-hourglass', rejected: 'bi bi-arrow-repeat' }[status] || 'bi bi-send')
const getButtonText = (status) => ({ accepted: 'عرض الحل', pending_review: 'بانتظار التقييم', rejected: 'إعادة التسليم' }[status] || 'تسليم الحل')
const getFileIcon = (filename) => {
  const ext = filename.split('.').pop().toLowerCase()
  const icons = { pdf: 'bi bi-file-earmark-pdf text-danger', doc: 'bi bi-file-earmark-word text-primary', docx: 'bi bi-file-earmark-word text-primary', zip: 'bi bi-file-earmark-zip text-warning', jpg: 'bi bi-file-earmark-image text-success', png: 'bi bi-file-earmark-image text-success' }
  return icons[ext] || 'bi bi-file-earmark'
}

onMounted(() => {
  AOS.init({ duration: 800, once: true })
  loadTasks()
})
</script>

<style scoped>
.jisr-program-page { padding: 20px 0; }
.header-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #7c3aed, #a855f7); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; }
.btn-back { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 30px; padding: 8px 20px; color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.3s ease; }
.btn-back:hover { background: var(--accent-soft); color: var(--accent); border-color: var(--accent); }
.progress-card { background: var(--card-bg); border-radius: 24px; padding: 24px; border: 1px solid var(--border-color); }
.progress-percentage { font-size: 32px; font-weight: 700; color: var(--accent); }
.progress { height: 12px; border-radius: 10px; background: var(--border-color); }
.task-card { background: var(--card-bg); border-radius: 20px; padding: 24px; border: 1px solid var(--border-color); transition: all 0.3s ease; height: 100%; display: flex; flex-direction: column; }
.task-card:hover { transform: translateY(-5px); box-shadow: var(--hover-shadow); }
.task-completed { border-left: 4px solid #22c55e; }
.task-pending-review { border-left: 4px solid #f59e0b; }
.task-rejected { border-left: 4px solid #ef4444; }
.task-pending { border-left: 4px solid var(--accent); }
.task-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
.task-number { background: var(--accent-soft); color: var(--accent); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.task-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
.badge-practical { background: #e0f2fe; color: #0284c7; }
.badge-research { background: #fef3c7; color: #d97706; }
.badge-theoretical { background: #e0e7ff; color: #4f46e5; }
.points-box { background: #fef9e3; padding: 8px 12px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #f59e0b; width: fit-content; }
.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; width: fit-content; }
.status-accepted { background: #f0fdf4; color: #22c55e; }
.status-review { background: #fffbeb; color: #f59e0b; }
.status-rejected { background: #fef2f2; color: #ef4444; }
.status-pending { background: #f1f5f9; color: #64748b; }
.feedback-box { background: var(--main-bg); padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); margin-top: auto; }
.task-files-box { background: var(--main-bg); padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); }
.task-files-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
.task-files-count { display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; border-radius: 999px; background: #f8fafc; border: 1px solid var(--border-color); font-size: 12px; font-weight: 700; color: var(--text-muted); }
.task-files-list { display: grid; gap: 8px; }
.task-file-link { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: var(--accent); background: rgba(124, 58, 237, 0.08); border-radius: 12px; padding: 8px 12px; font-size: 13px; font-weight: 600; }
.selected-files-summary { font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 10px; }
.task-action-btn { margin-top: auto; padding: 10px; border-radius: 10px; font-weight: 600; font-size: 14px; transition: all 0.3s ease; cursor: pointer; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-submit { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); }
.btn-view { background: var(--main-bg); border: 1px solid var(--border-color); color: var(--text-dark); }
.btn-pending { background: #fef3c7; color: #b45309; cursor: not-allowed; }
.btn-retry { background: #fee2e2; color: #dc2626; }
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 1100; padding: 20px; }
.modal-content { background: var(--card-bg); border-radius: 24px; width: 100%; max-width: 700px; max-height: 90vh; overflow-y: auto; }
.modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 24px; }
.btn-close { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.instruction-box { background: var(--accent-soft); padding: 16px; border-radius: 12px; }
.upload-zone { border: 2px dashed var(--border-color); border-radius: 12px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s ease; }
.upload-zone:hover { border-color: var(--accent); background: var(--accent-soft); }
.attachments-list { max-height: 150px; overflow-y: auto; }
.attachment-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: var(--main-bg); border-radius: 8px; margin-bottom: 8px; }
.btn-remove-attachment { margin-left: auto; width: 24px; height: 24px; border-radius: 50%; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.btn-remove-attachment:hover { background: #fee2e2; color: #ef4444; }
.btn-accent-gradient { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; border: none; border-radius: 10px; padding: 12px; font-weight: 600; transition: all 0.3s ease; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; }
@media (max-width: 768px) { .progress-percentage { font-size: 24px; } .task-header { flex-direction: column; align-items: flex-start; } }
</style>
