import { reactive } from 'vue'
import { profileService } from '../services/apiService'


export const auth = reactive({
  user: null,
  loading: true,
})

export async function loadUser() {
  try {
    const res = await profileService()
    if (res.status) {
        auth.user = res.user 
    }
    
  } catch {
    auth.user = null
  } finally {
    auth.loading = false
  }
}

export function clearUser() {
  auth.user = null
}
