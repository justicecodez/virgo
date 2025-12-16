<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { loginService, profileService } from '../utils/services/apiService'


const router = useRouter()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref(null)

const submit = async () => {
  error.value = null
  loading.value = true

  try {
    await loginService({
      email: email.value,
      password: password.value,
    })
    const user = await profileService();
    if (user.status) {
      console.log('logged')
      router.replace('/')
    }

  } catch (e) {
    error.value = e.response?.data?.message || 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-sm">
      <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>

      <form @submit.prevent="submit" class="space-y-4">
        <p class="text-center text-red-500" v-if="error">{{ error }}</p>
        <!-- Email -->
        <div>
          <label for="email" class="block text-gray-700 font-medium mb-1">Email</label>
          <input type="email" id="email" v-model="email" required
            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="you@example.com" />
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block text-gray-700 font-medium mb-1">Password</label>
          <input type="password" id="password" v-model="password" required
            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="********" />
        </div>

        <!-- Submit Button -->
        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>
