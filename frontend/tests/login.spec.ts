import { test, expect } from '@playwright/test'
import {login, resetDB} from './helpers/auth'

test('login works', async ({ page }) => {
  await login(page)
})

test('login fails', async ({ page }) => {

  await page.goto('http://localhost:3000/login', { timeout: 60000 })

  await page.getByPlaceholder('examplo@examplo.pt').fill('admin@example.com')

  await page.getByPlaceholder('••••••').fill('wrongpassword')

  await page.getByRole('button', { name: 'Entrar' }).click()

  await expect(page.getByText('Credenciais inválidas', { exact: true }).first()).toBeVisible()
})
