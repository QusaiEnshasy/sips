<template>
  <div class="submit-task-page">
    <!-- رأس الصفحة -->
    <div class="page-header">
      <div class="container">
        <router-link to="/student/workspace" class="back-link">
          <i class="bi bi-arrow-left me-2"></i>
          {{ t('back_to_workspace') }}
        </router-link>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h3 class="fw-bold mb-1">{{ t('submit_assignment') }}</h3>
            <p class="text-muted mb-0">{{ task?.title }}</p>
          </div>
          <div class="due-badge">
            <i class="bi bi-clock me-2"></i>
            {{ t('due') }} {{ formatDate(task?.dueDate) }}
          </div>
        </div>
      </div>
    </div>

    <div class="container mt-4">
      <div class="row g-4">
        <!-- العمود الرئيسي -->
        <div class="col-lg-8">
          <!-- وصف المهمة -->
          <div class="submit-card">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="info-icon">
                <i class="bi bi-file-earmark-code"></i>
              </div>
              <div>
                <h6 class="fw-bold mb-0">{{ t('task_description') }}</h6>
                <small class="text-muted">{{ task?.points }} {{ t('points') }} • {{ t('assignment') }}</small>
              </div>
            </div>
            <p class="small text-muted mb-0 lh-lg">{{ t(task?.description) }}</p>
          </div>

          <!-- رفع الملفات -->
          <div class="submit-card">
            <h6 class="fw-bold mb-3">{{ t('upload_work') }}</h6>
            
            <!-- منطقة السحب والإفلات -->
            <div 
              class="upload-zone" 
              @click="triggerFileInput"
              @dragover.prevent
              @drop.prevent="handleDrop"
              :class="{ 'drag-over': isDragging }"
            >
              <i class="bi bi-cloud-arrow-up display-6 text-muted mb-3"></i>
              <h6 class="fw-bold mb-1">{{ t('drop_files') }}</h6>
              <p class="text-muted small mb-0">{{ t('accepted_formats') }}</p>
              <p class="text-muted small mt-1">{{ t('max_files') }}</p>
            </div>

            <!-- قائمة الملفات المرفوعة -->
            <div v-if="uploadedFiles.length > 0" class="files-list mt-4">
              <div 
                v-for="(file, index) in uploadedFiles" 
                :key="index"
                class="file-item"
              >
                <div class="d-flex align-items-center gap-3">
                  <i :class="getFileIcon(file.name)" class="file-icon"></i>
                  <div>
                    <p class="mb-0 fw-bold small">{{ file.name }}</p>
                    <small class="text-muted">{{ formatFileSize(file.size) }}</small>
                  </div>
                </div>
                <button class="btn-remove" @click="removeFile(index)">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>

            <input 
              type="file" 
              ref="fileInput" 
              multiple 
              hidden 
              @change="handleFileSelect"
              accept=".zip,.pdf,.json,.txt,.js,.jsx,.ts,.tsx,.css,.html"
            />
          </div>

          <!-- كتابة التقرير -->
          <div class="submit-card">
            <h6 class="fw-bold mb-3">{{ t('write_report') }}</h6>
            <textarea 
              class="form-control custom-textarea" 
              rows="6"
              v-model="report"
              :placeholder="t('report_placeholder')"
            ></textarea>
            <div class="text-muted small mt-2">{{ report.length }} {{ t('characters') }}</div>
          </div>

          <!-- جاهز للتسليم -->
          <div class="ready-box">
            <div class="d-flex gap-3">
              <div class="text-primary fs-4">
                <i class="bi bi-info-circle"></i>
              </div>
              <div>
                <h6 class="fw-bold mb-1">{{ t('ready_submit') }}</h6>
                <p class="text-muted small mb-0 lh-base">
                  {{ t('review_before_submit') }}
                </p>
              </div>
            </div>
            <button class="btn-submit-main" @click="submitTask" :disabled="isSubmitting">
              <span v-if="!isSubmitting">
                <i class="bi bi-send me-2"></i>
                {{ t('submit_button') }}
              </span>
              <span v-else>
                <span class="spinner-border spinner-border-sm me-2"></span>
                {{ t('submitting') }}
              </span>
            </button>
          </div>
        </div>

        <!-- العمود الجانبي -->
        <div class="col-lg-4">
          <!-- معلومات المهمة -->
          <div class="info-card">
            <h6 class="fw-bold mb-4">{{ t('task_info') }}</h6>
            
            <div class="info-item">
              <div class="info-icon"><i class="bi bi-calendar-event"></i></div>
              <div>
                <small class="text-muted d-block">{{ t('due_date') }}</small>
                <span class="fw-bold small">{{ formatDate(task?.dueDate) }}</span>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon"><i class="bi bi-award"></i></div>
              <div>
                <small class="text-muted d-block">{{ t('points') }}</small>
                <span class="fw-bold small">{{ task?.points }} {{ t('points') }}</span>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon"><i class="bi bi-file-earmark-text"></i></div>
              <div>
                <small class="text-muted d-block">{{ t('type') }}</small>
                <span class="fw-bold small">{{ t('assignment') }}</span>
              </div>
            </div>

            <div class="info-item mb-0">
              <div class="info-icon"><i class="bi bi-paperclip"></i></div>
              <div>
                <small class="text-muted d-block">{{ t('files_uploaded') }}</small>
                <span class="fw-bold small">{{ uploadedFiles.length }} / 5</span>
              </div>
            </div>
          </div>

          <!-- إرشادات التسليم -->
          <div class="guideline-box mb-4">
            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-clock-history"></i>
              {{ t('submission_guidelines') }}
            </h6>
            <div class="guideline-item">
              <i class="bi bi-check2-circle"></i>
              {{ t('ensure_files') }}
            </div>
            <div class="guideline-item">
              <i class="bi bi-check2-circle"></i>
              {{ t('include_readme') }}
            </div>
            <div class="guideline-item">
              <i class="bi bi-check2-circle"></i>
              {{ t('double_check_formats') }}
            </div>
            <div class="guideline-item">
              <i class="bi bi-check2-circle"></i>
              {{ t('review_instructions') }}
            </div>
          </div>

          <!-- مساعدة -->
          <div class="help-box">
            <h6 class="fw-bold mb-2">{{ t('need_help') }}</h6>
            <p class="small opacity-75 mb-4 lh-base">{{ t('help_text') }}</p>
            <button class="btn-contact" @click="contactTrainer">
              {{ t('contact_trainer') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

// ========== بيانات المهمة ==========
const task = ref({
  id: route.params.taskId,
  title: 'RESTful API Design Assignment',
  description: 'task_details',
  points: 150,
  dueDate: '2025-01-08'
})

// ========== حالة التقديم ==========
const isSubmitting = ref(false)
const report = ref('')

// ========== رفع الملفات ==========
const uploadedFiles = ref([])
const fileInput = ref(null)
const isDragging = ref(false)

const triggerFileInput = () => {
  fileInput.value.click()
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  addFiles(files)
}

const handleDrop = (event) => {
  isDragging.value = false
  const files = Array.from(event.dataTransfer.files)
  addFiles(files)
}

const addFiles = (files) => {
  if (uploadedFiles.value.length + files.length > 5) {
    alert(t('max_files_limit'))
    return
  }
  
  files.forEach(file => {
    if (file.size > 50 * 1024 * 1024) {
      alert(t('file_too_large', { name: file.name }))
      return
    }
    uploadedFiles.value.push(file)
  })
}

const removeFile = (index) => {
  uploadedFiles.value.splice(index, 1)
}

const getFileIcon = (filename) => {
  const ext = filename.split('.').pop().toLowerCase()
  const icons = {
    pdf: 'bi bi-file-earmark-pdf text-danger',
    zip: 'bi bi-file-earmark-zip text-warning',
    js: 'bi bi-file-earmark-code text-primary',
    jsx: 'bi bi-file-earmark-code text-primary',
    ts: 'bi bi-file-earmark-code text-primary',
    tsx: 'bi bi-file-earmark-code text-primary',
    json: 'bi bi-file-earmark-code text-success',
    txt: 'bi bi-file-earmark-text text-muted',
    html: 'bi bi-file-earmark-code text-orange',
    css: 'bi bi-file-earmark-code text-info'
  }
  return icons[ext] || 'bi bi-file-earmark text-muted'
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// ========== دوال مساعدة ==========
const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// ========== دوال التفاعل ==========
const submitTask = async () => {
  if (uploadedFiles.value.length === 0) {
    alert(t('please_upload_files'))
    return
  }

  isSubmitting.value = true
  try {
    // محاكاة رفع الملفات
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    alert(t('submission_success'))
    router.push('/student/workspace')
  } finally {
    isSubmitting.value = false
  }
}

const contactTrainer = () => {
  alert(t('contact_trainer_message'))
}

// منع السلوك الافتراضي للسحب والإفلات
onMounted(() => {
  document.addEventListener('dragover', (e) => e.preventDefault())
  document.addEventListener('drop', (e) => e.preventDefault())
})
</script>

<style scoped>
.submit-task-page {
  min-height: 100vh;
  background: #f8fafc;
  padding-bottom: 50px;
}

[data-theme="dark"] .submit-task-page {
  background: var(--main-bg);
}

.page-header {
  padding: 30px 0;
  background: var(--card-bg);
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 40px;
}

.back-link {
  text-decoration: none;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  margin-bottom: 15px;
  transition: all 0.2s ease;
}

.back-link:hover {
  color: var(--accent);
}

.due-badge {
  background: #fffbeb;
  color: #f59e0b;
  border: 1px solid #fef3c7;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
}

[data-theme="dark"] .due-badge {
  background: rgba(245, 158, 11, 0.1);
  border-color: rgba(245, 158, 11, 0.2);
}

.submit-card {
  background: var(--card-bg);
  border-radius: 16px;
  border: 1px solid var(--border-color);
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
}

.info-icon {
  width: 36px;
  height: 36px;
  background: #f5f3ff;
  color: var(--accent);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.upload-zone {
  border: 2px dashed #e2e8f0;
  border-radius: 16px;
  padding: 40px;
  text-align: center;
  background: #fbfcfe;
  cursor: pointer;
  transition: all 0.3s ease;
}

[data-theme="dark"] .upload-zone {
  background: #1e293b;
  border-color: #475569;
}

.upload-zone:hover,
.upload-zone.drag-over {
  border-color: var(--accent);
  background: #f5f3ff;
}

[data-theme="dark"] .upload-zone:hover,
[data-theme="dark"] .upload-zone.drag-over {
  background: rgba(124, 58, 237, 0.1);
}

.files-list {
  margin-top: 20px;
}

.file-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border: 1px solid var(--border-color);
  border-radius: 10px;
  margin-bottom: 10px;
  background: var(--card-bg);
}

.file-icon {
  font-size: 20px;
}

.btn-remove {
  width: 30px;
  height: 30px;
  border-radius: 6px;
  border: 1px solid var(--border-color);
  background: var(--card-bg);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-remove:hover {
  background: #fee2e2;
  color: #ef4444;
  border-color: #fecaca;
}

.custom-textarea {
  background: var(--input-bg);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 15px;
  font-size: 14px;
  resize: vertical;
  color: var(--text-dark);
}

.custom-textarea:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
}

.ready-box {
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
  border-radius: 16px;
  padding: 24px;
}

[data-theme="dark"] .ready-box {
  background: rgba(124, 58, 237, 0.1);
  border-color: rgba(124, 58, 237, 0.2);
}

.btn-submit-main {
  background: var(--accent);
  color: white;
  border: none;
  width: 100%;
  padding: 14px;
  border-radius: 10px;
  font-weight: 600;
  margin-top: 20px;
  transition: all 0.3s ease;
  cursor: pointer;
}

.btn-submit-main:hover:not(:disabled) {
  background: #6d28d9;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(124, 58, 237, 0.3);
}

.btn-submit-main:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.info-card {
  background: var(--card-bg);
  border-radius: 16px;
  border: 1px solid var(--border-color);
  padding: 24px;
  margin-bottom: 20px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}

.info-item:last-child {
  margin-bottom: 0;
}

.guideline-box {
  background: #eff6ff;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #dbeafe;
}

[data-theme="dark"] .guideline-box {
  background: rgba(59, 130, 246, 0.1);
  border-color: rgba(59, 130, 246, 0.2);
}

.guideline-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #1e40af;
  margin-bottom: 12px;
}

[data-theme="dark"] .guideline-item {
  color: #93c5fd;
}

.help-box {
  background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
  border-radius: 16px;
  padding: 24px;
  color: white;
}

.btn-contact {
  background: white;
  color: var(--accent);
  border: none;
  width: 100%;
  padding: 12px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-contact:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(255, 255, 255, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
  .page-header {
    padding: 20px 0;
  }
  
  .upload-zone {
    padding: 30px 20px;
  }
  
  .submit-card,
  .info-card,
  .guideline-box,
  .help-box {
    padding: 20px;
  }
}

@media (max-width: 576px) {
  .d-flex.justify-content-between {
    flex-direction: column;
    align-items: flex-start !important;
  }
  
  .due-badge {
    align-self: flex-start;
  }
  
  .btn-submit-main {
    padding: 12px;
  }
}
</style>