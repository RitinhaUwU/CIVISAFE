import { useAuthStore } from "../stores/auth";

export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore()

  if (!auth.currentUser) {
    await auth.isAuthenticated()
  }

  const isPublic = to.path === '/'
  const isLoggedIn = !!auth.currentUser

  if (isLoggedIn && isPublic) {
    return navigateTo('/inicio')
  }

  if (!isLoggedIn && !isPublic) {
    return navigateTo('/')
  }
})
