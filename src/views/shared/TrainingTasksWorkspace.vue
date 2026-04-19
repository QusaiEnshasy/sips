<template>
  <div class="training-tasks-page" dir="rtl">
    <section class="hero-panel">
      <div>
        <span class="eyebrow">مساحة التدريب</span>
        <h1>مهام التدريب والتقييم</h1>
        <p>
          الشركة تنشئ المهام، والطالب يرى مهامه فقط، وكل تسليم وتقييم محفوظ بشكل منفصل لكل طالب.
        </p>
      </div>
      <button class="refresh-btn" :disabled="loading" @click="loadWorkspace">
        <i class="bi bi-arrow-clockwise"></i>
        تحديث
      </button>
    </section>

    <div class="stats-grid">
      <article v-for="stat in statCards" :key="stat.label" class="stat-card">
        <span>{{ stat.label }}</span>
        <strong>{{ stat.value }}</strong>
      </article>
    </div>

    <div v-if="error" class="alert-box">{{ error }}</div>
    <div v-if="success" class="success-box">{{ success }}</div>

    <section v-if="role === 'company'" class="create-panel">
      <div class="section-heading">
        <div>
          <h2>إنشاء المهام يتم من Trello الحقيقي</h2>
          <p>الشركة لا تنشئ مهام الطلاب من الموقع. افتح Trello، أنشئ الكروت هناك، ثم ارجع واضغط مزامنة من صفحة تكامل Trello.</p>
        </div>
        <router-link class="primary-action link-action" to="/company/trello-settings">
          فتح تكامل Trello
        </router-link>
      </div>

      <div class="students-box">
        <div class="students-toolbar">
          <strong>شروط ظهور كرت Trello للطالب</strong>
        </div>
        <div class="rule-row">الكرت لازم يكون داخل القائمة المرتبطة بالبرنامج في صفحة تكامل Trello.</div>
        <div class="rule-row">اكتب داخل عنوان أو وصف الكرت: <b>student: email</b> أو <b>student_id: الرقم الجامعي</b>.</div>
        <div class="rule-row">الكرت بدون طالب محدد لن تتم مزامنته، حتى لا يظهر لكل الطلاب بالغلط.</div>
        <div class="rule-row">بعد تسليم الطالب من الموقع، سيضاف تعليق تلقائي على كرت Trello الحقيقي مع الحل وروابط الملفات.</div>
      </div>
    </section>

    <section class="tasks-panel">
      <div class="section-heading">
        <div>
          <h2>{{ role === 'student' ? 'مهامي التدريبية' : 'متابعة مهام الطلاب' }}</h2>
          <p>{{ role === 'student' ? 'هذه المهام موجهة لك فقط.' : 'كل كارد يمثل مهمة طالب واحدة بتسليمها وتقييمها.' }}</p>
        </div>
      </div>

      <div v-if="loading" class="empty-state">جاري تحميل المهام...</div>
      <div v-else-if="tasks.length === 0" class="empty-state">لا توجد مهام تدريبية حالياً.</div>

      <div v-else class="tasks-grid">
        <article v-for="task in tasks" :key="task.id" class="task-card" :class="`status-${task.status}`">
          <div class="task-topline">
            <span class="task-chip">{{ statusText(task.status) }}</span>
            <span v-if="task.due_date" class="due-date">تسليم: {{ task.due_date }}</span>
          </div>

          <h3>{{ task.title }}</h3>
          <p class="task-details">{{ task.details || 'لا يوجد وصف إضافي.' }}</p>

          <div class="meta-grid">
            <span><b>الطالب</b>{{ task.student?.name || '-' }}</span>
            <span><b>البرنامج</b>{{ task.program || '-' }}</span>
            <span><b>الشركة</b>{{ task.company || '-' }}</span>
            <span><b>الحالة</b>{{ task.submitted ? 'تم التسليم' : 'بانتظار التسليم' }}</span>
            <span><b>مصدر المهمة</b>{{ task.source === 'trello' ? 'Trello' : 'النظام' }}</span>
            <span><b>أنشأها</b>{{ task.source === 'trello' ? 'الشركة من Trello' : creatorLabel(task.creator) }}</span>
          </div>

          <div v-if="task.student_solution" class="solution-box">
            <strong>حل الطالب</strong>
            <p>{{ task.student_solution }}</p>
          </div>

          <div v-if="task.attachments?.length" class="files-box">
            <strong>الملفات المرفوعة</strong>
            <a v-for="file in task.attachments" :key="file.id" :href="file.url" target="_blank" rel="noopener">
              <i class="bi bi-paperclip"></i>
              {{ file.filename }}
            </a>
          </div>

          <form v-if="role === 'student' && !task.submitted" class="submit-box" @submit.prevent="submitTask(task)">
            <label>
              حل المهمة
              <textarea v-model.trim="studentSubmissions[task.id]" required rows="3" placeholder="اكتب ملخص الحل أو رابط العمل"></textarea>
            </label>
            <label class="file-input">
              رفع ملفات
              <input type="file" multiple @change="setTaskFiles(task.id, $event)" />
            </label>
            <button class="primary-action compact" :disabled="submittingTaskId === task.id">
              تسليم المهمة
            </button>
          </form>

          <div v-if="role !== 'student'" class="grading-box">
            <div class="scores">
              <span>تقييم الشركة: {{ task.company_score ?? '-' }}/50</span>
              <span>تقييم المشرف: {{ task.supervisor_score ?? '-' }}/50</span>
            </div>
            <form @submit.prevent="gradeTask(task)">
              <input v-model.number="grades[task.id]" type="number" min="0" max="50" placeholder="0 - 50" required />
              <button class="secondary-action" :disabled="gradingTaskId === task.id">حفظ التقييم</button>
            </form>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { trainingTasksAPI } from '@/services/api/trainingTasks'

const loading = ref(false)
const savingTask = ref(false)
const submittingTaskId = ref(null)
const gradingTaskId = ref(null)
const error = ref('')
const success = ref('')
const role = ref('')
const applications = ref([])
const tasks = ref([])
const selectedApplicationIds = ref([])
const studentSubmissions = ref({})
const taskFiles = ref({})
const grades = ref({})

const taskForm = ref({
  title: '',
  details: '',
  due_date: '',
  label: ''
})

const statCards = computed(() => [
  { label: 'إجمالي المهام', value: tasks.value.length },
  { label: 'بانتظار التسليم', value: tasks.value.filter((task) => !task.submitted).length },
  { label: 'تم التسليم', value: tasks.value.filter((task) => task.submitted).length },
  { label: 'تم التقييم', value: tasks.value.filter((task) => task.company_score !== null || task.supervisor_score !== null).length }
])

const allStudentsSelected = computed(() => (
  applications.value.length > 0 && selectedApplicationIds.value.length === applications.value.length
))

const loadWorkspace = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await trainingTasksAPI.getWorkspace()
    role.value = data.data?.role || ''
    applications.value = data.data?.applications || []
    tasks.value = data.data?.tasks || []
    grades.value = Object.fromEntries(tasks.value.map((task) => [
      task.id,
      role.value === 'supervisor' ? task.supervisor_score : task.company_score
    ]))
  } catch (e) {
    error.value = e?.response?.data?.message || 'تعذر تحميل شاشة التدريب.'
  } finally {
    loading.value = false
  }
}

const toggleAllStudents = () => {
  selectedApplicationIds.value = allStudentsSelected.value
    ? []
    : applications.value.map((application) => application.id)
}

const createTask = async () => {
  savingTask.value = true
  error.value = ''
  success.value = ''
  try {
    await trainingTasksAPI.createTask({
      ...taskForm.value,
      application_ids: selectedApplicationIds.value
    })
    success.value = 'تم إنشاء المهمة بنجاح، وكل طالب أخذ نسخة خاصة للتسليم والتقييم.'
    taskForm.value = { title: '', details: '', due_date: '', label: '' }
    selectedApplicationIds.value = []
    await loadWorkspace()
  } catch (e) {
    error.value = e?.response?.data?.message || 'تعذر إنشاء المهمة.'
  } finally {
    savingTask.value = false
  }
}

const setTaskFiles = (taskId, event) => {
  taskFiles.value[taskId] = Array.from(event.target.files || [])
}

const submitTask = async (task) => {
  submittingTaskId.value = task.id
  error.value = ''
  success.value = ''
  try {
    const formData = new FormData()
    formData.append('student_solution', studentSubmissions.value[task.id] || '')
    ;(taskFiles.value[task.id] || []).forEach((file) => formData.append('attachments[]', file))
    await trainingTasksAPI.submitTask(task.id, formData)
    success.value = 'تم تسليم المهمة وإرسالها للشركة والمشرف.'
    await loadWorkspace()
  } catch (e) {
    error.value = e?.response?.data?.message || 'تعذر تسليم المهمة.'
  } finally {
    submittingTaskId.value = null
  }
}

const gradeTask = async (task) => {
  gradingTaskId.value = task.id
  error.value = ''
  success.value = ''
  try {
    await trainingTasksAPI.gradeTask(task.id, { score: grades.value[task.id] })
    success.value = 'تم حفظ التقييم بنجاح.'
    await loadWorkspace()
  } catch (e) {
    error.value = e?.response?.data?.message || 'تعذر حفظ التقييم.'
  } finally {
    gradingTaskId.value = null
  }
}

const statusText = (status) => {
  if (status === 'done') return 'منجزة'
  if (status === 'progress') return 'قيد التنفيذ'
  return 'جديدة'
}

const creatorLabel = (creator) => {
  if (!creator) return '-'
  if (creator.role === 'supervisor') return `المشرف: ${creator.name}`
  if (creator.role === 'company') return `الشركة: ${creator.name}`
  if (creator.role === 'admin') return `الإدارة: ${creator.name}`
  return creator.name || '-'
}

onMounted(loadWorkspace)
</script>

<style scoped>
.training-tasks-page {
  display: grid;
  gap: 22px;
  color: #1f2937;
}

.hero-panel,
.create-panel,
.tasks-panel,
.task-card,
.stat-card {
  border: 1px solid #e5e7eb;
  background: #ffffff;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}

.hero-panel {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: center;
  padding: 28px;
  border-radius: 28px;
  background:
    radial-gradient(circle at top left, rgba(124, 58, 237, 0.15), transparent 32%),
    linear-gradient(135deg, #ffffff, #f8fafc);
}

.eyebrow {
  color: #6d28d9;
  font-weight: 800;
  letter-spacing: 0.04em;
}

.hero-panel h1 {
  margin: 8px 0;
  font-weight: 900;
}

.hero-panel p {
  margin: 0;
  color: #64748b;
}

.refresh-btn,
.primary-action,
.secondary-action,
.students-toolbar button {
  border: 0;
  border-radius: 16px;
  font-weight: 800;
  transition: 0.2s ease;
}

.refresh-btn {
  padding: 12px 18px;
  background: #ede9fe;
  color: #6d28d9;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

.stat-card {
  border-radius: 22px;
  padding: 18px;
}

.stat-card span {
  display: block;
  color: #64748b;
  font-size: 13px;
}

.stat-card strong {
  display: block;
  margin-top: 8px;
  font-size: 28px;
}

.create-panel,
.tasks-panel {
  border-radius: 28px;
  padding: 24px;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: start;
  margin-bottom: 18px;
}

.section-heading h2 {
  margin: 0 0 6px;
  font-weight: 900;
}

.section-heading p {
  margin: 0;
  color: #64748b;
}

.section-heading span {
  background: #ecfdf5;
  color: #047857;
  border-radius: 999px;
  padding: 8px 14px;
  font-weight: 800;
}

.task-form {
  display: grid;
  gap: 16px;
}

.form-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 14px;
}

label {
  display: grid;
  gap: 8px;
  font-weight: 800;
}

input,
select,
textarea {
  width: 100%;
  border: 1px solid #dbe3ef;
  border-radius: 16px;
  padding: 12px 14px;
  background: #f8fafc;
  color: #1f2937;
}

textarea {
  resize: vertical;
}

.students-box {
  border: 1px dashed #c4b5fd;
  border-radius: 22px;
  padding: 16px;
  background: #faf7ff;
}

.students-toolbar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  margin-bottom: 12px;
}

.students-toolbar button {
  padding: 9px 12px;
  background: #ffffff;
  color: #6d28d9;
  border: 1px solid #c4b5fd;
}

.student-row {
  grid-template-columns: auto 1fr;
  align-items: center;
  margin: 8px 0;
  padding: 12px;
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #ede9fe;
}

.student-row input {
  width: auto;
}

.student-row small {
  display: block;
  color: #64748b;
  font-weight: 500;
}

.rule-row {
  background: #ffffff;
  border: 1px solid #ede9fe;
  border-radius: 16px;
  color: #334155;
  margin-top: 10px;
  padding: 12px 14px;
}

.primary-action {
  padding: 13px 18px;
  background: #6d28d9;
  color: #ffffff;
}

.link-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
}

.primary-action.compact {
  padding: 10px 14px;
}

.secondary-action {
  padding: 10px 14px;
  background: #111827;
  color: #ffffff;
}

.tasks-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.task-card {
  display: grid;
  gap: 14px;
  border-radius: 24px;
  padding: 20px;
}

.task-topline,
.scores {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
}

.task-chip {
  background: #eef2ff;
  color: #4338ca;
  border-radius: 999px;
  padding: 6px 12px;
  font-weight: 800;
}

.due-date {
  color: #64748b;
}

.task-card h3 {
  margin: 0;
  font-weight: 900;
}

.task-details {
  margin: 0;
  color: #475569;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.meta-grid span {
  background: #f8fafc;
  border-radius: 16px;
  padding: 10px;
  color: #475569;
}

.meta-grid b {
  display: block;
  color: #111827;
}

.solution-box,
.files-box,
.submit-box,
.grading-box {
  border-radius: 18px;
  padding: 14px;
  background: #f8fafc;
  border: 1px solid #e5e7eb;
}

.solution-box p {
  margin: 8px 0 0;
  white-space: pre-wrap;
}

.files-box {
  display: grid;
  gap: 8px;
}

.files-box a {
  color: #5b21b6;
  font-weight: 800;
  text-decoration: none;
}

.submit-box,
.grading-box form {
  display: grid;
  gap: 12px;
}

.grading-box form {
  grid-template-columns: 1fr auto;
  margin-top: 12px;
}

.alert-box,
.success-box,
.empty-state {
  padding: 16px;
  border-radius: 18px;
  font-weight: 800;
}

.alert-box {
  color: #b91c1c;
  background: #fee2e2;
}

.success-box {
  color: #047857;
  background: #d1fae5;
}

.empty-state {
  text-align: center;
  color: #64748b;
  background: #f8fafc;
}

.empty-state.small {
  text-align: start;
}

@media (max-width: 992px) {
  .stats-grid,
  .tasks-grid,
  .form-grid {
    grid-template-columns: 1fr;
  }

  .hero-panel,
  .section-heading {
    align-items: stretch;
    flex-direction: column;
  }

  .meta-grid,
  .grading-box form {
    grid-template-columns: 1fr;
  }
}
</style>
