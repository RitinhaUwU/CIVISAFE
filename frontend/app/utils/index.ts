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
