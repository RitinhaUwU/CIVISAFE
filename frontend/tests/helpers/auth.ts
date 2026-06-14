import { expect, Page } from '@playwright/test'

export async function login(page: Page) {

  await page.goto('http://localhost:3000/test/login', { timeout: 60000 })

  await page.getByPlaceholder('examplo@examplo.pt').fill('admin@example.com')

  await page.getByPlaceholder('••••••').fill('password')

  await page.getByRole('button', { name: 'Entrar' }).click()

  await expect(page).toHaveURL(/inicio/, { timeout: 30000 })

  await page.waitForFunction(() => {
    const token = localStorage.getItem('token')
    return !!token
  })

  await page.waitForTimeout(500)
}
