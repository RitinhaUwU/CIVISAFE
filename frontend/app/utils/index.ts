import type {DonationGoodType} from "~/types";

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

export const createBlobURL = (file: any) => {
  return URL.createObjectURL(file)
}

export function convertedMeasurementUnit(techString: string) {
  switch (techString) {
    case 'units':
      return "Unidades";

    case 'liters':
      return "Litros";

    case 'kilos':
      return "Quilos";

    case 'linear_meters':
      return "Metros";

    case 'squared_meters':
      return "Metros Quadrados";

    default:
      return techString;
  }
}

export const suffixForQuantityBox = (categories: DonationGoodType[], categoryId: number, simple: boolean = false) => {
  const category = categories.find((x: DonationGoodType) => x.id == categoryId);

  if(!category || !category.unit)
    return '';

  if(simple)
  {
    return convertedMeasurementUnit(category.unit);
  }
  return `(em ${convertedMeasurementUnit(category.unit)})`;
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
