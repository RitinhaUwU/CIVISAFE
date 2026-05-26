import { request } from '@playwright/test'

async function globalSetup() {
  const api = await request.newContext()

  await api.post('http://127.0.0.1:8000/api/test/reset', {
    headers: {
      'TEST-TOKEN': 'civisafe-test'
    }
  })
}

export default globalSetup
