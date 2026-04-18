<template>
  <div class="skill-test-page">
    <div v-if="testStarted && !testCompleted" class="test-container">
      <div class="test-header">
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <router-link to="/student/dashboard" class="test-back-link">
                <i class="bi bi-arrow-left"></i>
              </router-link>
              <div>
                <h5 class="fw-bold mb-0">{{ pageTitle }}</h5>
                <small class="text-muted">{{ selectedSpecializationName }}</small>
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
              <p class="text-muted mt-3">{{ labels.loadingQuestions }}</p>
            </div>

            <div v-else class="questions-container">
              <div
                v-for="(question, idx) in questions"
                :key="question.id || idx"
                class="question-card"
                :class="{ answered: answers[idx] !== undefined }"
              >
                <div class="question-number">{{ labels.question }} {{ idx + 1 }}</div>
                <h6 class="fw-bold mb-3">{{ question.question }}</h6>
                <div class="options-list">
                  <div
                    v-for="(option, optIdx) in question.options"
                    :key="optIdx"
                    class="option-item"
                    :class="{ selected: answers[idx] === optIdx }"
                    @click="selectAnswer(idx, optIdx)"
                  >
                    <div class="option-radio">
                      <div v-if="answers[idx] === optIdx" class="radio-dot"></div>
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
                  {{ labels.submitTest }}
                </span>
                <span v-else>
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  {{ labels.submitting }}
                </span>
              </button>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="progress-sidebar">
              <h6 class="fw-bold mb-3">{{ labels.testProgress }}</h6>
              <div class="progress-grid">
                <button
                  v-for="(question, idx) in questions"
                  :key="question.id || idx"
                  class="question-marker"
                  :class="{ answered: answers[idx] !== undefined }"
                  @click="scrollToQuestion(idx)"
                >
                  {{ idx + 1 }}
                </button>
              </div>
              <div class="progress-stats mt-4">
                <div class="d-flex justify-content-between mb-2">
                  <span>{{ labels.answered }}</span>
                  <span class="fw-bold">{{ answeredCount }}/{{ questions.length }}</span>
                </div>
                <div class="progress">
                  <div class="progress-bar bg-success" :style="{ width: progressWidth }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="testCompleted && testResult" class="result-container">
      <div class="result-card" data-aos="fade-up">
        <div class="result-icon" :class="resultClass">
          <i :class="resultIcon"></i>
        </div>
        <h2 class="fw-bold mb-2">{{ labels.testCompleted }}</h2>
        <p class="text-muted mb-2">{{ selectedSpecializationName }}</p>
        <p class="text-muted mb-4">{{ labels.yourResult }}</p>

        <div class="score-circle-large" :class="resultClass">
          {{ testResult.score }}%
        </div>

        <div class="result-info mt-4">
          <div class="info-row">
            <span>{{ labels.selectedSpecialization }}</span>
            <span class="fw-bold">{{ selectedSpecializationName }}</span>
          </div>
          <div class="info-row">
            <span>{{ labels.passingScore }}</span>
            <span class="fw-bold">{{ test?.passing_score }}%</span>
          </div>
          <div class="info-row">
            <span>{{ labels.yourScore }}</span>
            <span class="fw-bold" :class="testResult.score >= passingScore ? 'text-success' : 'text-danger'">
              {{ testResult.score }}%
            </span>
          </div>
          <div class="info-row">
            <span>{{ labels.result }}</span>
            <span class="badge" :class="testResult.score >= passingScore ? 'bg-success' : 'bg-danger'">
              {{ testResult.score >= passingScore ? labels.passed : labels.failed }}
            </span>
          </div>
          <div class="info-row">
            <span>{{ labels.recommendedPath }}</span>
            <span class="fw-bold">
              {{ testResult.score >= passingScore ? labels.readyForInternship : labels.needJisr }}
            </span>
          </div>
        </div>

        <div class="result-actions mt-4">
          <router-link to="/student/dashboard" class="btn-back-to-dashboard">
            <i class="bi bi-house-door me-2"></i>
            {{ labels.backToDashboard }}
          </router-link>
          <button v-if="testResult.score < passingScore" class="btn-start-jisr" @click="goToJisr">
            <i class="bi bi-mortarboard me-2"></i>
            {{ labels.startJisr }}
          </button>
        </div>
      </div>
    </div>

    <div v-else class="test-selection-page">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-xl-9 col-lg-10">
            <div class="test-intro-card" data-aos="fade-up">
              <div class="test-icon">
                <i class="bi bi-file-text-fill"></i>
              </div>
              <h2 class="fw-bold mb-3">{{ pageTitle }}</h2>
              <p class="text-muted mb-4">{{ labels.testDescription }}</p>

              <div class="specialization-section">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                  <h5 class="fw-bold mb-0">{{ labels.chooseSpecialization }}</h5>
                  <span class="selection-hint">{{ labels.chooseSpecializationHint }}</span>
                </div>

                <div v-if="isLoading" class="text-center py-4">
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  {{ labels.loading }}
                </div>

                <div v-else class="specialization-grid">
                  <button
                    v-for="specialization in specializations"
                    :key="specialization.code"
                    type="button"
                    class="specialization-card"
                    :class="{ selected: selectedSpecialization === specialization.code }"
                    @click="chooseSpecialization(specialization.code)"
                  >
                    <div class="specialization-top">
                      <h6 class="fw-bold mb-1">{{ specialization.name }}</h6>
                      <span class="badge rounded-pill text-bg-light">{{ specialization.questions_count }} {{ labels.questions }}</span>
                    </div>
                    <p class="small text-muted mb-3">{{ specialization.description || labels.specializationDescriptionFallback }}</p>
                    <div class="specialization-meta">
                      <span><i class="bi bi-clock me-1"></i>{{ specialization.duration_minutes }} {{ labels.minutes }}</span>
                      <span><i class="bi bi-award me-1"></i>{{ labels.passingScoreShort }} {{ specialization.passing_score }}%</span>
                    </div>
                  </button>
                </div>
              </div>

              <div v-if="selectedSpecializationMeta" class="selected-summary">
                <div class="test-info">
                  <div class="info-item">
                    <i class="bi bi-bookmark-check"></i>
                    <span>{{ selectedSpecializationMeta.name }}</span>
                  </div>
                  <div class="info-item">
                    <i class="bi bi-question-circle"></i>
                    <span>{{ selectedSpecializationMeta.questions_count }} {{ labels.questions }}</span>
                  </div>
                  <div class="info-item">
                    <i class="bi bi-clock"></i>
                    <span>{{ selectedSpecializationMeta.duration_minutes }} {{ labels.minutes }}</span>
                  </div>
                  <div class="info-item">
                    <i class="bi bi-award"></i>
                    <span>{{ labels.passingScore }}: {{ selectedSpecializationMeta.passing_score }}%</span>
                  </div>
                </div>

                <div class="test-instructions mt-4">
                  <h6 class="fw-bold mb-2">{{ labels.instructions }}</h6>
                  <ul class="small text-muted mb-0">
                    <li>{{ labels.instructionChooseSpecialization }}</li>
                    <li>{{ labels.readQuestionsCarefully }}</li>
                    <li>{{ labels.timeWarning(selectedSpecializationMeta.duration_minutes) }}</li>
                    <li>{{ labels.scoreToPass(selectedSpecializationMeta.passing_score) }}</li>
                  </ul>
                </div>
              </div>

              <button
                class="btn-start-test"
                @click="startTest"
                :disabled="isLoadingQuestions || !selectedSpecialization || questions.length === 0"
              >
                <span v-if="!isLoadingQuestions">
                  <i class="bi bi-play-fill me-2"></i>
                  {{ labels.startTest }}
                </span>
                <span v-else>
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  {{ labels.loadingQuestions }}
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

const { currentLang } = useI18n()
const router = useRouter()
const authStore = useAuthStore()

const isLoading = ref(false)
const isSubmitting = ref(false)
const isLoadingQuestions = ref(false)
const testStarted = ref(false)
const testCompleted = ref(false)
const specializations = ref([])
const selectedSpecialization = ref('')
const test = ref(null)
const questions = ref([])
const answers = ref({})
const testResult = ref(null)
const timeLeft = ref(0)
let timerInterval = null

const text = {
  ar: {
    pageTitle: 'اختبار تحديد المستوى',
    testDescription: 'قبل بدء الامتحان اختر تخصصك، وبعدها سيتم تحميل أسئلة مخصصة لهذا التخصص فقط.',
    chooseSpecialization: 'اختر التخصص',
    chooseSpecializationHint: 'لا يمكن بدء الامتحان قبل تحديد التخصص',
    selectedSpecialization: 'التخصص المختار',
    specializationDescriptionFallback: 'سيتم عرض مجموعة الأسئلة المناسبة لهذا المسار.',
    loading: 'جاري التحميل',
    loadingQuestions: 'جاري تحميل الأسئلة',
    question: 'السؤال',
    questions: 'أسئلة',
    minutes: 'دقيقة',
    passingScore: 'علامة النجاح',
    passingScoreShort: 'النجاح',
    instructions: 'تعليمات',
    instructionChooseSpecialization: 'اختر التخصص الصحيح لأن الأسئلة ستُبنى عليه.',
    readQuestionsCarefully: 'اقرأ كل سؤال جيدًا قبل اختيار الإجابة.',
    timeWarning: (minutes) => `الوقت المتاح ${minutes} دقيقة فقط.`,
    scoreToPass: (score) => `تحتاج إلى ${score}% على الأقل للنجاح.`,
    startTest: 'ابدأ الامتحان',
    submitTest: 'تسليم الامتحان',
    submitting: 'جاري التسليم',
    testProgress: 'تقدم الامتحان',
    answered: 'تمت الإجابة',
    testCompleted: 'تم إنهاء الامتحان',
    yourResult: 'هذه نتيجتك النهائية',
    yourScore: 'علامتك',
    result: 'النتيجة',
    passed: 'ناجح',
    failed: 'راسب',
    recommendedPath: 'المسار المقترح',
    readyForInternship: 'جاهز للتدريب',
    needJisr: 'يحتاج برنامج الجسر',
    backToDashboard: 'العودة للوحة الطالب',
    startJisr: 'ابدأ برنامج الجسر',
    partialSubmitConfirm: 'لم تُجب عن كل الأسئلة بعد. هل تريد تسليم الامتحان؟',
    chooseSpecializationFirst: 'اختر التخصص أولًا قبل بدء الامتحان.'
  },
  en: {
    pageTitle: 'Skill Assessment Test',
    testDescription: 'Before starting the exam, choose your specialization to load the matching question set.',
    chooseSpecialization: 'Choose Specialization',
    chooseSpecializationHint: 'You must choose a specialization before starting',
    selectedSpecialization: 'Selected Specialization',
    specializationDescriptionFallback: 'A matching question set will be loaded for this track.',
    loading: 'Loading',
    loadingQuestions: 'Loading questions',
    question: 'Question',
    questions: 'Questions',
    minutes: 'minutes',
    passingScore: 'Passing score',
    passingScoreShort: 'Pass',
    instructions: 'Instructions',
    instructionChooseSpecialization: 'Choose the correct specialization because the exam depends on it.',
    readQuestionsCarefully: 'Read each question carefully before answering.',
    timeWarning: (minutes) => `You have ${minutes} minutes to complete the test.`,
    scoreToPass: (score) => `You need at least ${score}% to pass.`,
    startTest: 'Start Test',
    submitTest: 'Submit Test',
    submitting: 'Submitting',
    testProgress: 'Test Progress',
    answered: 'Answered',
    testCompleted: 'Test Completed',
    yourResult: 'Here is your final result',
    yourScore: 'Your score',
    result: 'Result',
    passed: 'Passed',
    failed: 'Failed',
    recommendedPath: 'Recommended path',
    readyForInternship: 'Ready for internship',
    needJisr: 'Needs Jisr program',
    backToDashboard: 'Back to dashboard',
    startJisr: 'Start Jisr Program',
    partialSubmitConfirm: 'You have unanswered questions. Submit anyway?',
    chooseSpecializationFirst: 'Choose a specialization before starting the test.'
  }
}

const labels = computed(() => text[currentLang.value] || text.en)
const pageTitle = computed(() => labels.value.pageTitle)
const answeredCount = computed(() => Object.keys(answers.value).length)
const formattedTime = computed(() => {
  const minutes = Math.floor(timeLeft.value / 60)
  const seconds = timeLeft.value % 60
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
})
const timerClass = computed(() => (timeLeft.value <= 300 ? 'timer-warning' : ''))
const passingScore = computed(() => test.value?.passing_score || 70)
const resultClass = computed(() => (testResult.value?.score >= passingScore.value ? 'result-success' : 'result-failed'))
const resultIcon = computed(() => (testResult.value?.score >= passingScore.value ? 'bi bi-trophy-fill' : 'bi bi-emoji-frown-fill'))
const progressWidth = computed(() => `${questions.value.length ? (answeredCount.value / questions.value.length) * 100 : 0}%`)
const selectedSpecializationMeta = computed(() => specializations.value.find((item) => item.code === selectedSpecialization.value) || null)
const selectedSpecializationName = computed(() => selectedSpecializationMeta.value?.name || test.value?.specialization_name || '-')

const applyTestPayload = (data) => {
  specializations.value = data.specializations || []
  selectedSpecialization.value = data.selected_specialization || selectedSpecialization.value

  test.value = data.test
    ? {
      id: data.test.id,
      title: data.test.title,
      description: data.test.description,
      specialization_code: data.test.specialization_code,
      specialization_name: data.test.specialization_name,
      duration_minutes: data.test.duration_minutes,
      passing_score: data.test.passing_score,
      questions_count: data.questions?.length || 0
    }
    : null

  questions.value = (data.questions || []).map((question) => ({
    id: question.id,
    question: question.question,
    options: question.options,
    correct_answer: question.correct_answer
  }))
}

const fallbackSpecializations = () => ([
  {
    code: 'web_development',
    name: currentLang.value === 'ar' ? 'تطوير الويب' : 'Web Development',
    description: currentLang.value === 'ar' ? 'أسئلة في الواجهات، Laravel، وقواعد البيانات.' : 'Questions about frontend, Laravel, and databases.',
    questions_count: 5,
    duration_minutes: 30,
    passing_score: 70,
    test_id: 1
  },
  {
    code: 'networking',
    name: currentLang.value === 'ar' ? 'الشبكات' : 'Networking',
    description: currentLang.value === 'ar' ? 'أسئلة في أساسيات الشبكات والبروتوكولات والأجهزة.' : 'Questions about networking basics, protocols, and devices.',
    questions_count: 5,
    duration_minutes: 30,
    passing_score: 70,
    test_id: 2
  },
  {
    code: 'cybersecurity',
    name: currentLang.value === 'ar' ? 'الأمن السيبراني' : 'Cybersecurity',
    description: currentLang.value === 'ar' ? 'أسئلة في الحماية والمصادقة والثغرات الشائعة.' : 'Questions about protection, authentication, and common vulnerabilities.',
    questions_count: 5,
    duration_minutes: 30,
    passing_score: 70,
    test_id: 3
  }
])

const fallbackQuestions = (specialization) => {
  if (specialization === 'networking') {
    return [
      { id: 1, question: currentLang.value === 'ar' ? 'ماذا يعني IP في الشبكات؟' : 'What does IP stand for in networking?', options: ['Internet Protocol', 'Internal Program', 'Input Port', 'Interface Process'], correct_answer: 0 },
      { id: 2, question: currentLang.value === 'ar' ? 'أي جهاز يربط بين الشبكات المختلفة؟' : 'Which device connects different networks?', options: ['Switch', 'Router', 'Access Point', 'Patch Panel'], correct_answer: 1 },
      { id: 3, question: currentLang.value === 'ar' ? 'أي بروتوكول يوزع عناوين IP تلقائيًا؟' : 'Which protocol assigns IP addresses automatically?', options: ['DNS', 'DHCP', 'FTP', 'SMTP'], correct_answer: 1 },
      { id: 4, question: currentLang.value === 'ar' ? 'في أي طبقة يوجد التوجيه في OSI؟' : 'Which OSI layer handles routing?', options: ['Physical', 'Data Link', 'Network', 'Presentation'], correct_answer: 2 },
      { id: 5, question: currentLang.value === 'ar' ? 'ما وظيفة subnet mask؟' : 'What is the role of a subnet mask?', options: ['Encryption', 'Define network and host parts', 'Increase Wi-Fi speed', 'Store DNS records'], correct_answer: 1 }
    ]
  }

  if (specialization === 'cybersecurity') {
    return [
      { id: 1, question: currentLang.value === 'ar' ? 'ما الهدف الأساسي من التصيد الاحتيالي؟' : 'What is phishing mainly used for?', options: ['Speeding up networks', 'Stealing sensitive data', 'Compressing files', 'Backing up servers'], correct_answer: 1 },
      { id: 2, question: currentLang.value === 'ar' ? 'ما مبدأ أقل الصلاحيات؟' : 'What is the least privilege principle?', options: ['Give only needed access', 'Give all permissions', 'Share all passwords', 'Disable audits'], correct_answer: 0 },
      { id: 3, question: currentLang.value === 'ar' ? 'ما الأداة التي تراقب وتفلتر حركة الشبكة؟' : 'Which tool filters network traffic?', options: ['Compiler', 'Firewall', 'Browser', 'Spreadsheet'], correct_answer: 1 },
      { id: 4, question: currentLang.value === 'ar' ? 'ماذا يعني MFA؟' : 'What does MFA stand for?', options: ['Managed File Access', 'Multi-Factor Authentication', 'Main Function Audit', 'Manual Firewall Approval'], correct_answer: 1 },
      { id: 5, question: currentLang.value === 'ar' ? 'لماذا تحديثات النظام مهمة أمنيًا؟' : 'Why are software updates important for security?', options: ['Only visual changes', 'Patch known vulnerabilities', 'Remove passwords', 'Disable backups'], correct_answer: 1 }
    ]
  }

  if (specialization === 'web_development') {
    return [
      { id: 1, question: currentLang.value === 'ar' ? 'ما اللغة المستخدمة غالبًا مع Laravel؟' : 'Which language is commonly used with Laravel?', options: ['Python', 'PHP', 'Java', 'Ruby'], correct_answer: 1 },
      { id: 2, question: currentLang.value === 'ar' ? 'أي مما يلي إطار واجهات أمامية؟' : 'Which of the following is a frontend framework?', options: ['Laravel', 'Django', 'Vue.js', 'Flask'], correct_answer: 2 },
      { id: 3, question: currentLang.value === 'ar' ? 'ما الأمر الشائع لتشغيل migrations في Laravel؟' : 'What command is commonly used to run Laravel migrations?', options: ['php artisan migrate', 'npm run build', 'composer dump', 'git migrate'], correct_answer: 0 },
      { id: 4, question: currentLang.value === 'ar' ? 'أي مما يلي قاعدة بيانات علائقية؟' : 'Which of the following is a relational database?', options: ['MySQL', 'HTML', 'CSS', 'Figma'], correct_answer: 0 },
      { id: 5, question: currentLang.value === 'ar' ? 'ماذا يعني MVC؟' : 'What does MVC stand for?', options: ['Main View Controller', 'Model View Controller', 'Module Value Class', 'Model Version Core'], correct_answer: 1 }
    ]
  }

  return []
}

const loadTest = async (specialization = null) => {
  if (specialization) {
    isLoadingQuestions.value = true
  } else {
    isLoading.value = true
  }

  try {
    const response = await studentAPI.getSkillTest(specialization)
    applyTestPayload(response.data.data)
  } catch (error) {
    console.error('Failed to load test:', error)

    const selected = fallbackSpecializations().find((item) => item.code === specialization) || null
    applyTestPayload({
      specializations: fallbackSpecializations(),
      selected_specialization: specialization,
      test: selected ? {
        id: selected.test_id,
        title: pageTitle.value,
        description: labels.value.testDescription,
        specialization_code: selected.code,
        specialization_name: selected.name,
        duration_minutes: selected.duration_minutes,
        passing_score: selected.passing_score
      } : null,
      questions: fallbackQuestions(specialization)
    })
  } finally {
    isLoading.value = false
    isLoadingQuestions.value = false
  }
}

const chooseSpecialization = async (code) => {
  selectedSpecialization.value = code
  testCompleted.value = false
  testResult.value = null
  await loadTest(code)
}

const startTest = () => {
  if (!selectedSpecialization.value) {
    window.alert(labels.value.chooseSpecializationFirst)
    return
  }

  answers.value = {}
  testStarted.value = true
  timeLeft.value = (test.value?.duration_minutes || 30) * 60
  startTimer()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const startTimer = () => {
  timerInterval = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value -= 1
      return
    }

    clearInterval(timerInterval)
    submitTest()
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
  if (Object.keys(answers.value).length < questions.value.length && !window.confirm(labels.value.partialSubmitConfirm)) {
    return
  }

  isSubmitting.value = true
  if (timerInterval) clearInterval(timerInterval)

  try {
    let correctCount = 0
    for (let i = 0; i < questions.value.length; i += 1) {
      if (answers.value[i] === questions.value[i].correct_answer) {
        correctCount += 1
      }
    }

    const percentage = Math.round((correctCount / Math.max(questions.value.length, 1)) * 100)
    const payload = {
      test_id: test.value.id,
      specialization_code: selectedSpecialization.value,
      score: percentage,
      answers: answers.value,
      completed_at: new Date().toISOString()
    }

    const response = await studentAPI.submitTestResult(test.value.id, payload)
    if (response.data?.data?.user_state) {
      authStore.setUser({
        ...authStore.user,
        ...response.data.data.user_state
      })
    }

    testResult.value = {
      score: response.data?.data?.score ?? percentage,
      specialization_code: response.data?.data?.specialization_code ?? selectedSpecialization.value,
      specialization_name: response.data?.data?.specialization_name ?? selectedSpecializationName.value
    }
    testCompleted.value = true
    testStarted.value = false
  } catch (error) {
    console.error('Failed to submit test:', error)

    let correctCount = 0
    for (let i = 0; i < questions.value.length; i += 1) {
      if (answers.value[i] === questions.value[i].correct_answer) {
        correctCount += 1
      }
    }

    testResult.value = {
      score: Math.round((correctCount / Math.max(questions.value.length, 1)) * 100),
      specialization_code: selectedSpecialization.value,
      specialization_name: selectedSpecializationName.value
    }
    testCompleted.value = true
    testStarted.value = false
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
.test-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #0f766e, #14b8a6); border-radius: 30px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; color: white; font-size: 32px; }
.specialization-section { margin-top: 32px; text-align: start; }
.selection-hint { color: var(--text-muted); font-size: 14px; }
.specialization-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
.specialization-card { background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 22px; padding: 20px; text-align: start; transition: all 0.25s ease; cursor: pointer; }
.specialization-card:hover { border-color: #0f766e; transform: translateY(-2px); }
.specialization-card.selected { background: linear-gradient(135deg, rgba(15, 118, 110, 0.08), rgba(20, 184, 166, 0.12)); border-color: #0f766e; box-shadow: 0 18px 40px rgba(15, 118, 110, 0.12); }
.specialization-top { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; margin-bottom: 8px; }
.specialization-meta { display: flex; flex-direction: column; gap: 8px; color: var(--text-muted); font-size: 13px; }
.selected-summary { margin-top: 24px; }
.test-info { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin: 30px 0; }
.info-item { display: flex; align-items: center; gap: 8px; background: var(--main-bg); padding: 8px 16px; border-radius: 40px; font-size: 14px; }
.test-instructions { background: var(--main-bg); padding: 20px; border-radius: 16px; text-align: left; }
.btn-start-test { background: linear-gradient(135deg, #0f766e, #0d9488); color: white; border: none; border-radius: 40px; padding: 14px 40px; font-weight: 700; font-size: 18px; margin-top: 30px; cursor: pointer; transition: all 0.3s ease; }
.btn-start-test:disabled { opacity: 0.6; cursor: not-allowed; }
.test-header { background: var(--card-bg); border-bottom: 1px solid var(--border-color); padding: 15px 0; position: sticky; top: 0; z-index: 100; }
.test-back-link { width: 40px; height: 40px; border-radius: 10px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-muted); transition: all 0.3s ease; }
.timer-box { background: var(--accent-soft); padding: 10px 20px; border-radius: 40px; font-size: 18px; font-weight: 600; color: var(--accent); }
.timer-warning { background: #fee2e2; color: #dc2626; animation: pulse 1s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
.question-card { background: var(--card-bg); border-radius: 20px; padding: 24px; border: 1px solid var(--border-color); margin-bottom: 20px; }
.question-card.answered { border-inline-start: 4px solid #22c55e; }
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
.result-card { background: var(--card-bg); border-radius: 40px; padding: 50px; text-align: center; max-width: 560px; width: 100%; border: 1px solid var(--border-color); }
.result-icon { width: 80px; height: 80px; border-radius: 40px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 40px; }
.result-success { background: #f0fdf4; color: #22c55e; }
.result-failed { background: #fef2f2; color: #ef4444; }
.score-circle-large { width: 150px; height: 150px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 30px auto; font-size: 40px; font-weight: 700; border: 4px solid; }
.result-info { background: var(--main-bg); border-radius: 20px; padding: 20px; }
.info-row { display: flex; justify-content: space-between; gap: 16px; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
.info-row:last-child { border-bottom: none; }
.result-actions { display: flex; gap: 15px; justify-content: center; }
.btn-back-to-dashboard { background: var(--main-bg); border: 1px solid var(--border-color); border-radius: 40px; padding: 12px 24px; color: var(--text-dark); text-decoration: none; font-weight: 600; }
.btn-start-jisr { background: linear-gradient(135deg, #0f766e, #0d9488); color: white; border: none; border-radius: 40px; padding: 12px 24px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
@media (max-width: 992px) { .specialization-grid { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .test-intro-card { padding: 30px 20px; } .progress-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 576px) { .test-info, .result-actions { flex-direction: column; } .info-row { flex-direction: column; align-items: flex-start; } }
</style>
