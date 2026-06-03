import { test, expect } from '@playwright/test'
import { login } from './helpers/auth'

test.describe.configure({ mode: 'serial' })

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('notifications slideover opens', async ({ page }) => {
  await page.getByText('Notificações').click()

  await expect(page.getByRole('heading', { name: 'Notificações' })).toBeVisible({ timeout: 10000 })

  await expect(page.getByText('Marcar todos como lidas')).toBeVisible()
})

test('notifications slideover closes', async ({ page }) => {
  await page.getByText('Notificações').click()

  await expect(page.getByRole('heading', { name: 'Notificações' })).toBeVisible()

  await page.keyboard.press('Escape')

  await expect(page.getByRole('heading', { name: 'Notificações' })).not.toBeVisible()
})

test('notifications list loads', async ({ page }) => {
  await page.getByText('Notificações').click()

  await expect(page.locator('a').filter({ hasText: /.+/ }).first()).toBeVisible({ timeout: 10000 })
})
