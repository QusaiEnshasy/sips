<template>
  <div class="skill-test-page">
    <!-- Test Taking View -->
    <div v-if="testStarted && !testCompleted" class="test-container">
      <div class="test-header">
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <router-link to="/student/dashboard" class="test-back-link">
                <i class="bi bi-arrow-left"></i>
              </router-link>
              <div>
                <h5 class="fw-bold mb-0">{{ t('skill_assessment_test') }}</h5>
                <small class="text-muted">{{ t('answer_all_questions') }}</small>
              </div>
            </div>
            <div class="timer-box" :class="timerClass">
              <i class="bi bi-hourglass-split me-2"></i>
              <span class="fw-bold">{{ formattedTime }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="container mt-4">
        <div class="row g-4">
          <div class="col-lg-8">
            <div v-if="isLoadingQuestions" class="text-center py-5">
              <div class="spinner-border text-primary" role="status"></div>
              <p class="text-muted mt-3">{{ t('loading_questions') }}</p>
            </div>

            <div v-else class="questions-container">
              <div v-for="(question, idx) in questions" :key="idx" 
                   class="question-card" :class="{ 'answered': answers[idx] !== undefined }">
                <div class="question-number">{{ t('question') }} {{ idx + 1 }}</div>
                <h6 class="fw-bold mb-3">{{ question.question }}</h6>
                <div class="options-list">
                  <div v-for="(option, optIdx) in question.options" :key="optIdx" 
                       class="option-item" :class="{ 'selected': answers[idx] === optIdx }" 
                       @click="selectAnswer(idx, optIdx)">
                    <div class="option-radio">
                      <div class="radio-dot" v-if="answers[idx] === optIdx"></div>
                    </div>
                    <span>{{ option }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="test-actions">
              <button class="btn-submit-test" @click="submitTest" :disabled="isSubmitting">
                <span v-if="!isSubmitting">
                  <i class="bi bi-check-lg me-2"></i>
                  {{ t('submit_test') }}
                </span>
                <span v-else>
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  {{ t('submitting') }}
                </span>
              </button>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="progress-sidebar">
              <h6 class="fw-bold mb-3">{{ t('test_progress') }}</h6>
              <div class="progress-grid">
                <button v-for="(q, idx) in questions" :key="idx" 
                        class="question-marker" 
                        :class="{ 'answered': answers[idx] !== undefined }"
                        @click="scrollToQuestion(idx)">
                  {{ idx + 1 }}
                </button>
              </div>
              <div class="progress-stats mt-4">
                <div class="d-flex justify-content-between mb-2">
                  <span>{{ t('answered') }}</span>
                  <span class="fw-bold">{{ answeredCount }}/{{ questions.length }}</span>
                </div>
                <div class="progress">
                  <div class="progress-bar bg-success" 
                       :style="{ width: (answeredCount / questions.length * 100) + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Test Result View -->
    <div v-else-if="testCompleted && testResult" class="result-container">
      <div class="result-card" data-aos="fade-up">
        <div class="result-icon" :class="resultClass">
          <i :class="resultIcon"></i>
        </div>
        <h2 class="fw-bold mb-2">{{ t('test_completed') }}</h2>
        <p class="text-muted mb-4">{{ t('your_result') }}</p>

        <div class="score-circle-large" :class="resultClass">
          {{ testResult.score }}%
        </div>

        <div class="result-info mt-4">
          <div class="info-row">
            <span>{{ t('passing_score') }}</span>
            <span class="fw-bold">{{ test?.passing_score }}%</span>
          </div>
          <div class="info-row">
            <span>{{ t('your_score') }}</span>
            <span class="fw-bold" :class="testResult.score >= (test?.passing_score || 70) ? 'text-success' : 'text-danger'">
              {{ testResult.score }}%
            </span>
          </div>
          <div class="info-row">
            <span>{{ t('result') }}</span>
            <span class="badge" :class="testResult.score >= (test?.passing_score || 70) ? 'bg-success' : 'bg-danger'">
              {{ testResult.score >= (test?.passing_score || 70) ? t('passed') : t('failed') }}
            </span>
          </div>
          <div class="info-row">
            <span>{{ t('recommended_path') }}</span>
            <span class="fw-bold">{{ testResult.score >= (test?.passing_score || 70) ? t('ready_for_internship') : t('need_jisr') }}</span>
          </div>
        </div>

        <div class="result-actions mt-4">
          <router-link to="/student/dashboard" class="btn-back-to-dashboard">
            <i class="bi bi-house-door me-2"></i>
            {{ t('back_to_dashboard') }}
          </router-link>
          <button v-if="testResult.score < (test?.passing_score || 70)" 
                  class="btn-start-jisr" @click="goToJisr">
            <i class="bi bi-mortarboard me-2"></i>
            {{ t('start_jisr_program') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Test Selection View -->
    <div v-else class="test-selection-page">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="test-intro-card" data-aos="fade-up">
              <div class="test-icon">
                <i class="bi bi-file-text-fill"></i>
              </div>
              <h2 class="fw-bold mb-3">{{ t('skill_assessment_test') }}</h2>
              <p class="text-muted mb-4">{{ t('test_description') }}</p>

              <div class="test-info">
                <div class="info-item">
                  <i class="bi bi-question-circle"></i>
                  <span>{{ test?.questions_count || questions.length }} {{ t('questions') }}</span>
                </div>
                <div class="info-item">
                  <i class="bi bi-clock"></i>
                  <span>{{ test?.duration_minutes || 30 }} {{ t('minutes') }}</span>
                </div>
                <div class="info-item">
                  <i class="bi bi-award"></i>
                  <span>{{ t('passing_score') }}: {{ test?.passing_score || 70 }}%</span>
                </div>
              </div>

              <div class="test-instructions mt-4">
                <h6 class="fw-bold mb-2">{{ t('instructions') }}</h6>
                <ul class="small text-muted">
                  <li>{{ t('read_questions_carefully') }}</li>
                  <li>{{ t('no_time_limit_warning') }} {{ test?.duration_minutes || 30 }} {{ t('minutes') }}</li>
                  <li>{{ t('cannot_change_answers') }}</li>
                  <li>{{ t('score_70_to_pass') }}</li>
                </ul>
              </div>

              <button class="btn-start-test" @click="startTest" :disabled="isLoading">
                <span v-if="!isLoading">
                  <i class="bi bi-play-fill me-2"></i>
                  {{ t('start_test') }}
                </span>
                <span v-else>
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  {{ t('loading') }}
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
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
const isSubmitting = ref(false)
const isLoadingQuestions = ref(false)
const testStarted = ref(false)
const testCompleted = ref(false)
const test = ref(null)
const questions = ref([])
const answers = ref({})
const testResult = ref(null)
const timeLeft = ref(0)
let timerInterval = null

// Computed
const answeredCount = computed(() => Object.keys(answers.value).length)
const formattedTime = computed(() => {
  const minutes = Math.floor(timeLeft.value / 60)
  const seconds = timeLeft.value % 60
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
})
const timerClass = computed(() => timeLeft.value <= 300 ? 'timer-warning' : '')
const resultClass = computed(() => 
  testResult.value && testResult.value.score >= (test.value?.passing_score || 70) 
    ? 'result-success' 
    : 'result-failed'
)
const resultIcon = computed(() => 
  testResult.value && testResult.value.score >= (test.value?.passing_score || 70) 
    ? 'bi bi-trophy-fill' 
    : 'bi bi-emoji-frown-fill'
)

// جلب بيانات الاختبار من API
const loadTest = async () => {
  isLoading.value = true
  try {
    const response = await studentAPI.getSkillTest()
    const data = response.data.data
    
    test.value = {
      id: data.test.id,
      title: data.test.title,
      description: data.test.description,
      duration_minutes: data.test.duration_minutes,
      passing_score: data.test.passing_score,
      questions_count: data.questions?.length || 0
    }
    
    questions.value = data.questions.map(q => ({
      id: q.id,
      question: q.question,
      options: q.options,
      correct_answer: q.correct_answer
    }))
    
  } catch (error) {
    console.error('Failed to load test:', error)
    // بيانات افتراضية في حالة فشل API
    test.value = {
      id: 1,
      title: t('skill_assessment_test'),
      description: t('test_description'),
      duration_minutes: 30,
      passing_score: 70,
      questions_count: 5
    }
    questions.value = [
      { id: 1, question: 'ما هي لغة البرمجة المستخدمة في Laravel؟', options: ['Python', 'Java', 'PHP', 'Ruby'], correct_answer: 2 },
      { id: 2, question: 'أي من التالي هو إطار عمل للواجهات الأمامية؟', options: ['Laravel', 'Django', 'Vue.js', 'Flask'], correct_answer: 2 },
      { id: 3, question: 'ما هي وظيفة Artisan في Laravel؟', options: ['إدارة قاعدة البيانات', 'تنفيذ أوامر', 'تصميم واجهات', 'اختبار الكود'], correct_answer: 1 },
      { id: 4, question: 'أي من التالي هو نظام إدارة قواعد بيانات؟', options: ['MySQL', 'Vue.js', 'React', 'Angular'], correct_answer: 0 },
      { id: 5, question: 'ما معنى MVC؟', options: ['Model View Controller', 'Main View Code', 'Model Version Code', 'Main Version Control'], correct_answer: 0 }
    ]
  } finally {
    isLoading.value = false
  }
}

const startTest = () => {
  testStarted.value = true
  timeLeft.value = (test.value?.duration_minutes || 30) * 60
  startTimer()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const startTimer = () => {
  timerInterval = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value--
    } else {
      clearInterval(timerInterval)
      submitTest()
    }
  }, 1000)
}

const selectAnswer = (questionIndex, answerIndex) => {
  answers.value[questionIndex] = answerIndex
}

const scrollToQuestion = (index) => {
  const elements = document.querySelectorAll('.question-card')
  if (elements[index]) {
    elements[index].scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

const submitTest = async () => {
  if (Object.keys(answers.value).length < questions.value.length) {
    if (!confirm(t('submit_partial_answers'))) return
  }

  isSubmitting.value = true
  if (timerInterval) clearInterval(timerInterval)

  try {
    // حساب النتيجة
    let correctCount = 0
    for (let i = 0; i < questions.value.length; i++) {
      if (answers.value[i] === questions.value[i].correct_answer) {
        correctCount++
      }
    }
    const percentage = Math.round((correctCount / questions.value.length) * 100)
    
    const result = {
      test_id: test.value.id,
      score: percentage,
      answers: answers.value,
      completed_at: new Date().toISOString()
    }
    
    const response = await studentAPI.submitTestResult(test.value.id, result)
    if (response.data?.data?.user_state) {
      authStore.setUser({
        ...authStore.user,
        ...response.data.data.user_state
      })
    }
    
    testResult.value = {
      score: percentage,
      result: percentage >= (test.value.passing_score || 70) ? 'ناجح' : 'راسب',
      recommended_path: percentage >= (test.value.passing_score || 70) ? 'جاهز' : 'يحتاج جسر'
    }
    
    testCompleted.value = true
    
  } catch (error) {
    console.error('Failed to submit test:', error)
    const correctCount = Object.keys(answers.value).length
    const percentage = Math.round((correctCount / questions.value.length) * 100)
    testResult.value = {
      score: percentage,
      result: percentage >= 70 ? 'ناجح' : 'راسب',
      recommended_path: percentage >= 70 ? 'جاهز' : 'يحتاج جسر'
    }
    testCompleted.value = true
  } finally {
    isSubmitting.value = false
  }
}

const goToJisr = () => {
  router.push('/student/jisr')
}

onMounted(() => {
  AOS.init({ duration: 800, once: true })
  loadTest()
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<style scoped>
.skill-test-page { min-height: 100vh; background: var(--main-bg); }
.test-selection-page { padding: 60px 0; }
.test-intro-card { background: var(--card-bg); border-radius: 32px; padding: 48px; text-align: center; border: 1px solid var(--border-color); }
.test-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #7c3aed, #a855f7); border-radius: 30px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; color: white; font-size: 32px; }
.test-info { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; margin: 30px 0; }
.info-item { display: flex; align-items: center; gap: 8px; background: var(--main-bg); padding: 8px 16px; border-radius: 40px; font-size: 14px; }
.test-instructions { background: var(--main-bg); padding: 20px; border-radius: 16px; text-align: left; }
.test-instructions ul { padding-left: 20px; margin-bottom: 0; }
.btn-start-test { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; border: none; border-radius: 40px; padding: 14px 40px; font-weight: 700; font-size: 18px; margin-top: 30px; cursor: pointer; transition: all 0.3s ease; }
.test-header { background: var(--card-bg); border-bottom: 1px solid var(--border-color); padding: 15px 0; position: sticky; top: 0; z-index: 100; }
.test-back-link { width: 40px; height: 40px; border-radius: 10px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-muted); transition: all 0.3s ease; }
.timer-box { background: var(--accent-soft); padding: 10px 20px; border-radius: 40px; font-size: 18px; font-weight: 600; color: var(--accent); }
.timer-warning { background: #fee2e2; color: #dc2626; animation: pulse 1s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
.question-card { background: var(--card-bg); border-radius: 20px; padding: 24px; border: 1px solid var(--border-color); margin-bottom: 20px; }
.question-card.answered { border-left: 4px solid #22c55e; }
.question-number { background: var(--accent-soft); color: var(--accent); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; margin-bottom: 15px; }
.options-list { display: flex; flex-direction: column; gap: 12px; }
.option-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s ease; }
.option-item:hover { background: var(--accent-soft); border-color: var(--accent); }
.option-item.selected { background: var(--accent-soft); border-color: var(--accent); }
.option-radio { width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; }
.option-item.selected .option-radio { border-color: var(--accent); }
.radio-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--accent); }
.test-actions { display: flex; justify-content: flex-end; margin: 30px 0 50px; }
.btn-submit-test { background: #22c55e; color: white; border: none; border-radius: 12px; padding: 14px 32px; font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.3s ease; }
.progress-sidebar { background: var(--card-bg); border-radius: 20px; padding: 24px; border: 1px solid var(--border-color); position: sticky; top: 100px; }
.progress-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
.question-marker { width: 40px; height: 40px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-muted); font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.question-marker.answered { background: #22c55e; color: white; border-color: #22c55e; }
.result-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
.result-card { background: var(--card-bg); border-radius: 40px; padding: 50px; text-align: center; max-width: 500px; width: 100%; border: 1px solid var(--border-color); }
.result-icon { width: 80px; height: 80px; border-radius: 40px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 40px; }
.result-success { background: #f0fdf4; color: #22c55e; }
.result-failed { background: #fef2f2; color: #ef4444; }
.score-circle-large { width: 150px; height: 150px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 30px auto; font-size: 40px; font-weight: 700; border: 4px solid; }
.result-info { background: var(--main-bg); border-radius: 20px; padding: 20px; }
.info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
.info-row:last-child { border-bottom: none; }
.result-actions { display: flex; gap: 15px; justify-content: center; }
.btn-back-to-dashboard { background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 40px; padding: 12px 24px; color: var(--text-dark); text-decoration: none; font-weight: 600; }
.btn-start-jisr { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; border: none; border-radius: 40px; padding: 12px 24px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
@media (max-width: 768px) { .test-intro-card { padding: 30px 20px; } .progress-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 576px) { .test-info { flex-direction: column; gap: 10px; } .result-actions { flex-direction: column; } }
</style>
