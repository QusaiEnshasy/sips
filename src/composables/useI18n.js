import { ref, computed } from 'vue'
import { formatDistanceToNow, format } from 'date-fns'
import { ar, enUS } from 'date-fns/locale'

// استيراد الترجمات مباشرة من ملفات JSON
import enCommon from '@/locales/en/common.json'
import enAuth from '@/locales/en/auth.json'
import enAdmin from '@/locales/en/admin.json'
import enSupervisor from '@/locales/en/supervisor.json'
import enStudent from '@/locales/en/student.json'
import enShared from '@/locales/en/shared.json'
import enCompany from '@/locales/en/company.json'
import enErrors from '@/locales/en/errors.json'

import arCommon from '@/locales/ar/common.json'
import arAuth from '@/locales/ar/auth.json'
import arAdmin from '@/locales/ar/admin.json'
import arSupervisor from '@/locales/ar/supervisor.json'
import arStudent from '@/locales/ar/student.json'
import arShared from '@/locales/ar/shared.json'
import arCompany from '@/locales/ar/company.json'
import arErrors from '@/locales/ar/errors.json'

// دمج كل الترجمات الإنجليزية
const enTranslations = {
  ...enCommon,
  ...enAuth,
  ...enAdmin,
  ...enSupervisor,
  ...enStudent,
  ...enShared,
  ...enCompany,
  ...enErrors
}

// دمج كل الترجمات العربية
const arTranslations = {
  ...arCommon,
  ...arAuth,
  ...arAdmin,
  ...arSupervisor,
  ...arStudent,
  ...arShared,
  ...arCompany,
  ...arErrors
}

// اللغة الحالية
const currentLang = ref(localStorage.getItem('lang') || 'ar')

// دالة تنسيق التاريخ
const formatDate = (date, formatStr = 'PPP') => {
  if (!date) return ''
  const dateObj = new Date(date)
  if (isNaN(dateObj.getTime())) return ''

  const locale = currentLang.value === 'ar' ? ar : enUS
  return format(dateObj, formatStr, { locale })
}

// دالة الوقت النسبي (منذ متى)
const timeAgo = (date) => {
  if (!date) return ''
  const dateObj = new Date(date)
  if (isNaN(dateObj.getTime())) return ''

  const locale = currentLang.value === 'ar' ? ar : enUS
  return formatDistanceToNow(dateObj, { addSuffix: true, locale })
}

export function useI18n() {
  // دالة الترجمة
  const t = (key, params = {}) => {
    // اختيار الترجمات حسب اللغة
    const translations = currentLang.value === 'ar' ? arTranslations : enTranslations

    // البحث عن الترجمة
    let text = translations[key]

    // إذا مش موجودة، رجّع المفتاح مع تحذير
    if (!text) {
      console.warn(`⚠️ Missing translation: ${key}`)
      return key
    }

    // استبدال المتغيرات
    if (params && Object.keys(params).length > 0) {
      Object.keys(params).forEach(param => {
        text = text.replace(new RegExp(`{${param}}`, 'g'), params[param])
      })
    }

    return text
  }

  // تغيير اللغة (بدون إعادة تحميل الصفحة)
  const changeLanguage = (lang) => {
    if (lang === 'ar' || lang === 'en') {
      currentLang.value = lang
      localStorage.setItem('lang', lang)

      document.documentElement.lang = lang
      document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr'

      if (lang === 'ar') {
        document.body.classList.add('rtl')
        document.body.classList.remove('ltr')
      } else {
        document.body.classList.add('ltr')
        document.body.classList.remove('rtl')
      }
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
