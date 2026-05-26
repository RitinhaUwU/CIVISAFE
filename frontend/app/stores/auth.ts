import {defineStore} from 'pinia'
import {computed, ref} from 'vue'
import {useApiStore} from './api'
import {useRouter} from 'vue-router'
import type {User} from "~/types";
import {createDB, retrieveData, storeData} from "~/composables/useIndexedDB";
import {checkServerAccess} from "~/utils";

export const useAuthStore = defineStore('auth', () => {
    const apiStore = useApiStore()
    const toast = useToast()
    const router = useRouter()

    const currentUser = ref<User | undefined>(undefined)
    const token = ref<string | null>(localStorage.getItem('token'))

    function reset() {
        token.value = null
        currentUser.value = undefined
        localStorage.removeItem('token')
        localStorage.removeItem('tokenUserID')
        apiStore.removeBearerToken?.()
        useNotificationStore().disconnect();
    }

    const isLoggedIn = computed(() => currentUser.value !== undefined)

    const currentUserID = computed(() => {
        return currentUser.value?.id
    })

    const currentUserPermissions = computed(() => currentUser.value?.permissions)

    const roles = computed(() => currentUser.value?.roles)

    const isAuthenticated = async () => {
        await createDB();

        const tokenUserID = localStorage.getItem('tokenUserID');

        if (!token.value || !tokenUserID) return false

        try {
            apiStore.setBearerToken(token.value)

            if (!await checkServerAccess()) {
                console.log("Performing OFFLINE ACCESS")

                currentUser.value = await retrieveData('users', parseInt(tokenUserID));
            } else {
                console.log("Performing ONLINE User Authentication")
                await getUser();

                //Tentamos ligar na mesma porque ele vai fazendo tentativas
                await useNotificationStore().connect()
            }

            return true
        } catch (err) {
            reset()
            return false
        }
    }

    const login = async (credentials: { email: string, password: string }) => {
        if (!await checkServerAccess()) {
            toast.add({
                title: 'Sem Ligação à internet',
                description: 'Lige-se à internet para iniciar sessão',
                color: 'error'
            });

            reset();
            return;
        }

        try {
            const res = await apiStore.postLogin(credentials)

            token.value = res.data.token // res.data
            localStorage.setItem('token', <string>token.value)
            apiStore.setBearerToken(<string>token.value)

            await getUser()
            await useNotificationStore().connect()

            toast.add({
                title: 'Login efetuado com sucesso',
                color: 'success'
            })

            return currentUser.value
        } catch (err) {
            reset()

            toast.add({
                title: 'Credenciais inválidas',
                color: 'error'
            })
        }
    }

    const logout = async () => {
        reset()
        toast.add({
            title: 'Sessão Encerrada',
            color: 'success'
        })

        if (router) {
            await router.push('/')
        }
    }

    const getUser = async () => {
        const res = await apiStore.getAuthUser()
        currentUser.value = res.data.data

        localStorage.setItem('tokenUserID', JSON.stringify(res.data.data.id))

        await storeData('users', res.data.data)
        return currentUser.value
    }

    const hasPermission = (permission: string) => {
        if (!currentUserPermissions.value) return false;

        return currentUserPermissions.value.includes(permission)
    }

    const hasRole = (role: string) => {
        if (!roles.value) return false;

        return roles.value.includes(role)
    }

    const isAdmin = computed(() => hasRole('admin'))
    const isManager = computed(() => hasRole('manager'))
    const isUser = computed(() => hasRole('user'))

    return {
        currentUser,
        currentUserID,
        currentUserPermissions,
        roles,
        isLoggedIn,
        isAuthenticated,
        login,
        logout,
        getUser,
        hasPermission,
        hasRole,
        isAdmin,
        isManager,
        isUser
    }
})
