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
