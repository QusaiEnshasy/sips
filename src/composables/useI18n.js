import { ref, computed } from 'vue'
import { formatDistanceToNow, format } from 'date-fns'
import { ar, enUS } from 'date-fns/locale'

import enCommon from '@/locales/en/common.json'
import enAuth from '@/locales/en/auth.json'
import enAdmin from '@/locales/en/admin.json'
import enSupervisor from '@/locales/en/supervisor.json'
import enStudent from '@/locales/en/student.json'
import enShared from '@/locales/en/shared.json'
import enCompany from '@/locales/en/company.json'
import enErrors from '@/locales/en/errors.json'
import enOverrides from '@/locales/en/overrides.json'

import arCommon from '@/locales/ar/common.json'
import arAuth from '@/locales/ar/auth.json'
import arAdmin from '@/locales/ar/admin.json'
import arSupervisor from '@/locales/ar/supervisor.json'
import arStudent from '@/locales/ar/student.json'
import arShared from '@/locales/ar/shared.json'
import arCompany from '@/locales/ar/company.json'
import arErrors from '@/locales/ar/errors.json'
import arOverrides from '@/locales/ar/overrides.json'

const enTranslations = {
  ...enCommon,
  ...enAuth,
  ...enAdmin,
  ...enSupervisor,
  ...enStudent,
  ...enShared,
  ...enCompany,
  ...enErrors,
  ...enOverrides
}

const arTranslations = {
  ...arCommon,
  ...arAuth,
  ...arAdmin,
  ...arSupervisor,
  ...arStudent,
  ...arShared,
  ...arCompany,
  ...arErrors,
  ...arOverrides
}

const currentLang = ref(localStorage.getItem('lang') || 'ar')

const applyLanguageToDocument = (lang) => {
  document.documentElement.lang = lang
  document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr'
  document.body?.classList.toggle('rtl', lang === 'ar')
  document.body?.classList.toggle('ltr', lang === 'en')
}

if (typeof document !== 'undefined') {
  applyLanguageToDocument(currentLang.value)
}

const formatDate = (date, formatStr = 'PPP') => {
  if (!date) return ''

  const dateObj = new Date(date)
  if (isNaN(dateObj.getTime())) return ''

  const locale = currentLang.value === 'ar' ? ar : enUS
  return format(dateObj, formatStr, { locale })
}

const timeAgo = (date) => {
  if (!date) return ''

  const dateObj = new Date(date)
  if (isNaN(dateObj.getTime())) return ''

  const locale = currentLang.value === 'ar' ? ar : enUS
  return formatDistanceToNow(dateObj, { addSuffix: true, locale })
}

export function useI18n() {
  const t = (key, params = {}) => {
    const forcedTranslations = {
      ar: {
        jisr_program: 'برنامج الجسر',
        training_tasks: 'مهام التدريب',
        start_jisr_program: 'ابدأ برنامج الجسر',
        complete_tasks_to_advance: 'أكمل مهام برنامج الجسر للانتقال إلى المرحلة التالية',
        jisr_description: 'هذا المسار التأهيلي يساعدك على تطوير مهاراتك قبل العودة إلى المسار الأساسي',
        program_progress: 'تقدم البرنامج'
      },
      en: {
        jisr_program: 'Jisr Program',
        training_tasks: 'Training Tasks',
        start_jisr_program: 'Start Jisr Program',
        complete_tasks_to_advance: 'Complete Jisr tasks to move to the next stage',
        jisr_description: 'This preparation path helps you improve your skills before returning to the main track',
        program_progress: 'Program Progress'
      }
    }

    if (forcedTranslations[currentLang.value]?.[key]) {
      return forcedTranslations[currentLang.value][key]
    }

    const translations = currentLang.value === 'ar' ? arTranslations : enTranslations
    let text = translations[key]

    if (!text) {
      console.warn(`Missing translation: ${key}`)
      return key
    }

    if (params && Object.keys(params).length > 0) {
      Object.keys(params).forEach((param) => {
        text = text.replace(new RegExp(`{${param}}`, 'g'), params[param])
      })
    }

    return text
  }

  const changeLanguage = (lang) => {
    if (lang === 'ar' || lang === 'en') {
      currentLang.value = lang
      localStorage.setItem('lang', lang)
      applyLanguageToDocument(lang)
      window.dispatchEvent(new CustomEvent('app-language-changed', { detail: { lang } }))
    }
  }

  const isRTL = computed(() => currentLang.value === 'ar')

  return {
    currentLang: computed(() => currentLang.value),
    t,
    changeLanguage,
    isRTL,
    formatDate,
    timeAgo
  }
}
