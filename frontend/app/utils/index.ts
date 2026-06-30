export function randomInt(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

export function randomFrom<T>(array: T[]): T {
  return array[Math.floor(Math.random() * array.length)]!
}

export async function checkServerAccess() {
  try {

    const response = await fetch(useRuntimeConfig().public.apiBase + '/up', {
      method: 'HEAD',
      cache: 'no-store',
      signal: AbortSignal.timeout(3000)
    })

    return response.ok
  } catch {
    return false
  }
}

/**
 * Formatação de bytes em unidade SI human readable
 * Fonte: Documentação NuxtUI: https://ui.nuxt.com/docs/components/file-upload#examples (18/5/2026)
 **/
export const formatBytes = (bytes: number, decimals = 2) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
}

export const createBlobURL = (file) => {
  return URL.createObjectURL(file)
}

/**
 * Extrair cursor através do URL
 **/
export const extractCursor = (url: string | null): string | null => {
  if (!url) return null
  try {
    return new URL(url).searchParams.get('cursor')
  } catch {
    return null
  }
}

//https://stackoverflow.com/questions/30166338/setting-value-of-datetime-local-from-date
// Converte o ISO que vem da API para um objeto Date.
export const toDatetimeLocal = (value?: string | null) => {
  if (!value) return ''

  const date = new Date(value)

  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')

  return `${year}-${month}-${day}T${hours}:${minutes}`
}
