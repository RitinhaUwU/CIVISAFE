import {useAuthStore} from "@/stores/auth";

export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore()

  if (!auth.currentUser) {
    await auth.isAuthenticated()
  }

  const isLoggedIn = !!auth.currentUser

  if (isLoggedIn && to.path === '/') {
    return navigateTo('/inicio')
  }

  if (!isLoggedIn && !(to.path === '/')) {
    return navigateTo('/')
  }
})
