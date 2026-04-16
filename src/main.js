import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// Bootstrap CSS (JS يستورد بعد المكونات)
import 'bootstrap/dist/css/bootstrap.min.css'
 
// Icons
import 'bootstrap-icons/font/bootstrap-icons.css'
import '@fortawesome/fontawesome-free/css/all.min.css'

// Animations
import 'aos/dist/aos.css'
import 'animate.css/animate.min.css'

// Custom CSS
import './assets/styles/main.css'

const initialLang = localStorage.getItem('lang') || 'en'
document.documentElement.lang = initialLang
document.documentElement.dir = initialLang === 'ar' ? 'rtl' : 'ltr'
document.body.classList.toggle('rtl', initialLang === 'ar')
document.body.classList.toggle('ltr', initialLang !== 'ar')

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// استيراد Bootstrap JS بعد إنشاء التطبيق
import('bootstrap/dist/js/bootstrap.bundle.min.js')

app.mount('#app')
