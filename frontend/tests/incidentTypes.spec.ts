import { test, expect } from '@playwright/test'
import {login, resetDB} from './helpers/auth'

test.describe.configure({ mode: 'serial' })

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('incident types list loads', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentTypes')
  await page.waitForSelector('table')

  await expect(page.getByRole('columnheader', { name: 'Código' })).toBeVisible({ timeout: 20000 })
  await expect(page.getByRole('columnheader', { name: 'Espécie' })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Tipo' })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Última Atualização' })).toBeVisible()

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })
})

test('incident types search filters results', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar tipos...')
  await input.click()
  await input.fill('xxxxxxxxxxx')
  await input.dispatchEvent('input')
  await page.waitForTimeout(1000)

  await expect(page.getByRole('row').filter({ hasText: 'xxxxxxxxxxx' })).toHaveCount(0, { timeout: 20000 })
})

test('upload modal opens', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentTypes')

  await page.getByRole('button', { name: 'Carregar Estados' }).click()

  await expect(page.getByText('Carregar Ficheiro...')).toBeVisible({ timeout: 20000 })
  await expect(page.getByText('Esta lista irá substituir todos os Tipos de Ocorrência atuais')).toBeVisible()
  await expect(page.getByRole('button', { name: 'Carregar' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Cancelar' })).toBeVisible()
})

test('upload modal closes on cancel', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentTypes')

  await page.getByRole('button', { name: 'Carregar Estados' }).click()
  await expect(page.getByText('Carregar Ficheiro...')).toBeVisible({ timeout: 20000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText('Carregar Ficheiro...')).not.toBeVisible({ timeout: 5000 })
})

test('upload modal fails without file', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentTypes')

  await page.getByRole('button', { name: 'Carregar Estados' }).click()
  await expect(page.getByText('Carregar Ficheiro...')).toBeVisible({ timeout: 20000 })

  await page.getByRole('button', { name: 'Carregar' }).click()

  await expect(page.getByText('Selecione um Ficheiro xlsx (Excel)', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('view incident type detail', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentTypes')
  await page.waitForSelector('table')

  const firstRow = page.locator('tbody tr').first()
  await expect(firstRow).toBeVisible({ timeout: 20000 })

  await firstRow.getByTestId('edit-volunteer').click()

  await page.waitForURL('**/incidentTypes/**')

  await expect(page.getByLabel('Código')).toBeVisible({ timeout: 10000 })
  await expect(page.getByLabel('Código')).toBeDisabled()
  await expect(page.getByLabel('Espécie')).toBeVisible()
  await expect(page.getByLabel('Espécie')).toBeDisabled()
  await expect(page.getByLabel('Tipo')).toBeVisible()
  await expect(page.getByLabel('Tipo')).toBeDisabled()

  await expect(page.getByLabel('Código')).not.toHaveValue('', { timeout: 10000 })
  await expect(page.getByLabel('Tipo')).not.toHaveValue('')
})

test('view incident type detail and go back', async ({ page }) => {
  await page.goto('http://localhost:3000/administration/incidentTypes')

  await page.waitForSelector('table')

  const firstRow = page.locator('tbody tr').first()

  await expect(firstRow).toBeVisible({ timeout: 20000 })

  await firstRow.getByTestId('edit-volunteer').click()

  await page.waitForURL('**/incidentTypes/**')

  await expect(page.getByLabel('Código')).not.toHaveValue('', { timeout: 20000 })

  await page.getByRole('link', {name: 'Tipos de Ocorrência'}).click()

  await page.waitForURL('**/incidentTypes')

  await expect(page.getByRole('columnheader', { name: 'Código' })).toBeVisible({ timeout: 20000 })
})
