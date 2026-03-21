<script setup lang="ts">
definePageMeta({
  layout: 'login'
})

const email = ref('')
const password = ref('')

// Função de Login
const login = () => {
  console.log('Email:', email.value)
  console.log('Password:', password.value)
}
</script>

<template>
  <div class="flex items-center justify-center flex-1 px-4">
    <UCard class="w-full max-w-md bg-gray-500/5 backdrop-blur">
      <h1 class="text-xl sm:text-2xl font-bold mb-4 text-center">
        Login
      </h1>
      <div class="mb-3 flex flex-col">
        <label class="mb-1">Email</label>
        <UInput v-model="email" type="email" placeholder="exemplo@exemplo.pt" />
      </div>
      <div class="mb-3 flex flex-col">
        <label class="mb-1">Palavra-Passe</label>
        <UInput v-model="password" type="password" placeholder="••••••" />
      </div>
      <NuxtLink to="" class="text-sm cursor-pointer text-blue-500 hover:underline">
        Esqueci-me da palavra-passe
      </NuxtLink>
      <UButton block class="mt-4 cursor-pointer" @click="login">
        Entrar
      </UButton>
    </UCard>
  </div>
</template>
