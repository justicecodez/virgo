import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from "./router/index"
import { ensureCsrf } from './utils/boot/app'
import { loadUser } from './utils/store/auth'


async function bootstrap() {
    await ensureCsrf()
    await loadUser();
    createApp(App).use(router).mount('#app')
}

bootstrap();