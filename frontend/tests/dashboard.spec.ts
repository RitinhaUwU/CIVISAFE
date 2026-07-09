import { test, expect } from '@playwright/test'
import { login } from './helpers/auth'

test.describe.configure({ mode: 'serial' })

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('home page loads map', async ({ page }) => {
  await page.goto('http://localhost:3000/inicio')

  await expect(page.locator('.leaflet-container')).toBeVisible({ timeout: 20000 })
})

test('clicking map opens create incident modal', async ({ page }) => {
  await page.goto('http://localhost:3000/inicio')

  const map = page.locator('.leaflet-container')
  await expect(map).toBeVisible({ timeout: 20000 })

  await map.click({ position: { x: 400, y: 300 } })

  await expect(page.getByText('Registo Ocorrência')).toBeVisible({ timeout: 10000 })
})

test('create incident from map successfully', async ({ page }) => {
  await page.goto('http://localhost:3000/inicio')

  const map = page.locator('.leaflet-container')
  await expect(map).toBeVisible({ timeout: 20000 })

  await map.click({ position: { x: 400, y: 300 } })

  await expect(page.getByText('Registo Ocorrência')).toBeVisible({ timeout: 10000 })

  const identifier = `OC-MAP-${Date.now()}`
  await page.getByLabel('Data Alerta').fill('2024-06-01T10:00')

  await page.locator('[placeholder="Selecionar estado"], button:has-text("Selecionar estado")').first().click()
  await page.waitForTimeout(500)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]').first().click()
  await page.waitForTimeout(300)

  await page.locator('[placeholder="Selecionar prioridade"], button:has-text("Selecionar prioridade")').first().click()
  await page.waitForTimeout(500)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]').first().click()
  await page.waitForTimeout(300)

  await page.locator('[placeholder="Selecionar tipo de ocorrência"], button:has-text("Selecionar tipo de ocorrência")').first().click()
  await page.waitForTimeout(500)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]').first().click()
  await page.waitForTimeout(300)

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Ocorrência criada com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('cancel incident creation from map closes modal', async ({ page }) => {
  await page.goto('http://localhost:3000/inicio')

  const map = page.locator('.leaflet-container')
  await expect(map).toBeVisible({ timeout: 20000 })

  await map.click({ position: { x: 400, y: 300 } })

  await expect(page.getByText('Registo Ocorrência')).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText('Registo Ocorrência')).not.toBeVisible({ timeout: 5000 })
})
