<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { reactive } from 'vue'

definePageMeta({
  layout: 'login'
})

const auth = useAuthStore()
const router = useRouter()

const credentials = reactive({
  email: '',
  password: ''
})

async function handleLogin() {
  await auth.login(credentials)
  await router.push('/inicio')
}
</script>

<template>
  <div class="flex items-center justify-center flex-1 px-4">
    <UCard class="w-full max-w-md bg-gray-500/5 backdrop-blur">
      <h1 class="text-xl sm:text-2xl font-bold mb-4 text-center">
        Login
      </h1>
      <form @submit.prevent="handleLogin">
        <div class="mb-3 flex flex-col">
          <label class="mb-1">Email</label>
          <UInput v-model="credentials.email" type="email" placeholder="examplo@examplo.pt" required />
        </div>
        <div class="mb-3 flex flex-col">
          <label class="mb-1">Palavra-Passe</label>
          <UInput v-model="credentials.password" type="password" placeholder="••••••" required />
        </div>
        <NuxtLink to="#" class="text-sm cursor-pointer text-blue-500 hover:underline">
          Esqueci-me da palavra-passe
        </NuxtLink>
        <UButton block class="mt-4 cursor-pointer" type="submit">
          Entrar
        </UButton>
      </form>
    </UCard>
  </div>
</template>
