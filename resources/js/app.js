import './bootstrap';
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// CSS
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'
import '@fortawesome/fontawesome-free/css/all.min.css'
import 'aos/dist/aos.css'
import 'animate.css/animate.min.css'
import './assets/styles/main.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)

import('bootstrap/dist/js/bootstrap.bundle.min.js')

app.mount('#app')