import { test, expect } from '@playwright/test'
import {login, resetDB} from './helpers/auth'

test.describe.configure({ mode: 'serial' })

const timestamp = Date.now()
const testName = `Test Good Type ${timestamp}`
const updatedName = `Updated Good Type ${timestamp}`
const noUnitAttemptName = `Sem Unidade Bem ${timestamp}`
const countableName = `Bem Contavel ${timestamp}`

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('donation good types list loads', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')
  await page.waitForSelector('table')

  await expect(page.getByRole('columnheader', { name: 'Nome' })).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('columnheader', { name: 'Unidade de Medida' })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Quantidade Crítica' })).toBeVisible()

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10000 })
})

test('create donation good type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')

  await page.getByRole('button', { name: 'Novo Bem Doável' }).click()

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').pressSequentially(testName, { delay: 50 })

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Tipo de Bem criado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('create donation good type fails', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')

  await page.getByRole('button', { name: 'Novo Bem Doável' }).click()
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create donation good type fails when countable without unit', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')

  await page.getByRole('button', { name: 'Novo Bem Doável' }).click()

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').pressSequentially(noUnitAttemptName, { delay: 50 })

  await page.getByText('Tem Unidade Associada?').click()

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('O campo Unidade é obrigatório quando o tipo tem uma Unidade associada.', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create countable donation good type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')

  await page.getByRole('button', { name: 'Novo Bem Doável' }).click()

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').pressSequentially(countableName, { delay: 50 })

  await page.getByText('Tem Unidade Associada?').click()

  await page.getByRole('combobox', { name: 'Unidade', exact: true }).click()
  await page.getByRole('option', { name: 'Quilos' }).click()

  await page.getByLabel('Número mínimo').click()
  await page.getByLabel('Número mínimo').fill('10')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Tipo de Bem criado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit donation good type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar Tipos de Bens...')
  await input.click()
  await input.pressSequentially(testName, { delay: 50 })
  await page.waitForTimeout(1500)

  const goodTypeRow = page.locator('tbody tr').filter({ hasText: testName }).first()
  await expect(goodTypeRow).toBeVisible({ timeout: 20000 })

  await goodTypeRow.getByTestId('edit-priority').click()
  await page.waitForURL('**/donationGoodsTypes/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 10000 })

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill(updatedName)
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Tipo de Bem atualizado.', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit donation good type fails', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar Tipos de Bens...')
  await input.click()
  await input.pressSequentially(updatedName, { delay: 50 })
  await page.waitForTimeout(1500)

  const goodTypeRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(goodTypeRow).toBeVisible({ timeout: 20000 })

  await goodTypeRow.getByTestId('edit-priority').click()
  await page.waitForURL('**/donationGoodsTypes/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill('')
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Erro de validação', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('search filters donation good types', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar Tipos de Bens...')
  await input.click()
  await input.clear()
  await input.pressSequentially(updatedName, { delay: 100 })
  await page.waitForTimeout(1500)

  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible({ timeout: 20000 })
})

test('search shows no results for unknown donation good type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')
  await page.waitForSelector('table')

  const input = page.getByPlaceholder('Filtrar Tipos de Bens...')
  await input.click()
  await input.fill('xxxxxxxxxxxxxxxxxxx')
  await input.dispatchEvent('input')
  await page.waitForTimeout(1000)

  await expect(page.locator('tbody tr').filter({ hasText: 'xxxxxxxxxxxxxxxxxxx' })).toHaveCount(0, { timeout: 20000 })
})

test('cancel delete donation good type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar Tipos de Bens...')
  await input.click()
  await input.pressSequentially(updatedName, { delay: 50 })
  await page.waitForTimeout(1500)

  const goodTypeRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(goodTypeRow).toBeVisible({ timeout: 20000 })

  await goodTypeRow.getByTestId('delete-priority').click()

  await expect(page.getByText(/Eliminar Tipo de Bem Doável/)).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText(/Eliminar Tipo de Bem Doável/)).not.toBeVisible({ timeout: 5000 })
  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible()
})

test('delete donation good type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/donationGoodsTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar Tipos de Bens...')
  await input.click()
  await input.pressSequentially(updatedName, { delay: 50 })
  await page.waitForTimeout(1500)

  const goodTypeRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(goodTypeRow).toBeVisible({ timeout: 20000 })

  await goodTypeRow.getByTestId('delete-priority').click()

  await page.getByRole('button', { name: 'Eliminar' }).click()

  await expect(page.getByText('Eliminado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})
