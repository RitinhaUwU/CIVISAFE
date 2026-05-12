import { test, expect } from '@playwright/test'
import {login, resetDB} from './helpers/auth'

test.describe.configure({ mode: 'serial' })

const timestamp = Date.now()
const testName = `Test Priority ${timestamp}`
const updatedName = `Updated Priority ${timestamp}`

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('incident priorities list loads', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')
  await page.waitForSelector('table')

  await expect(page.getByRole('columnheader', { name: 'Nome' })).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('columnheader', { name: 'Descrição' })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Estado' })).toBeVisible()

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10000 })
})

test('create incident priority', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')

  await page.getByRole('button', { name: 'Nova Prioridade' }).click()

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').pressSequentially(testName, { delay: 50 })

  await page.getByLabel('Observações').click()
  await page.getByLabel('Observações').pressSequentially('Descrição de teste', { delay: 50 })

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Entidade criada com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('create incident priority fails', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')

  await page.getByRole('button', { name: 'Nova Prioridade' }).click()
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create incident priority fails with duplicate name', async ({ page }) => {
  await page.goto('http://localhost:3000/administration/incidentPriorities')

  await page.getByRole('button', { name: 'Nova Prioridade' }).click()

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').pressSequentially(testName, { delay: 50 })

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Já existe uma Prioridade com esse nome', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('edit incident priority', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const scrollContainer = page.locator('.overflow-x-auto').first()
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)

  const priorityRow = page.locator('tbody tr').filter({ hasText: testName }).first()
  await expect(priorityRow).toBeVisible({ timeout: 20000 })

  await priorityRow.getByTestId('edit-priority').click()
  await page.waitForURL('**/incidentPriorities/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 10000 })

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill(updatedName)
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Tipo de Prioridade atualizada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit incident priority fails', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const scrollContainer = page.locator('.overflow-x-auto').first()
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)

  const priorityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(priorityRow).toBeVisible({ timeout: 20000 })

  await priorityRow.getByTestId('edit-priority').click()
  await page.waitForURL('**/incidentPriorities/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill('')
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('search filters incident priorities', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar prioridades...')
  await input.click()
  await input.clear()
  await input.pressSequentially(updatedName, { delay: 100 })
  await page.waitForTimeout(1500)

  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible({ timeout: 20000 })
})

test('search shows no results for unknown priority', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')
  await page.waitForSelector('table')

  const input = page.getByPlaceholder('Filtrar prioridades...')
  await input.click()
  await input.fill('xxxxxxxxxxxxxxxxxxx')
  await input.dispatchEvent('input')
  await page.waitForTimeout(1000)

  await expect(page.locator('tbody tr').filter({ hasText: 'xxxxxxxxxxxxxxxxxxx' })).toHaveCount(0, { timeout: 20000 })
})

test('filter by status active', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const select = page.getByRole('combobox').filter({ hasText: 'Estado' })
  await select.click()
  await page.getByRole('option', { name: 'Ativo' }).click()

  await expect(page.getByRole('combobox').filter({ hasText: 'Ativo' })).toBeVisible({ timeout: 10000 })
  await page.waitForTimeout(1000)
  await expect(page.locator('table')).toBeVisible({ timeout: 10000 })
})

test('filter by status inactive', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const select = page.getByRole('combobox').filter({ hasText: 'Estado' })
  await select.click()
  await page.getByRole('option', { name: 'Desativado' }).click()

  await expect(page.getByRole('combobox').filter({ hasText: 'Desativado' })).toBeVisible({ timeout: 10000 })
  await page.waitForTimeout(1000)
  await expect(page.locator('table')).toBeVisible({ timeout: 10000 })
})

test('cancel delete incident priority', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const scrollContainer = page.locator('.overflow-x-auto').first()
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)

  const priorityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(priorityRow).toBeVisible({ timeout: 20000 })

  await priorityRow.getByTestId('delete-priority').click()

  await expect(page.getByText(/Eliminar tipo de Estado:/)).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText(/Eliminar tipo de Estado:/)).not.toBeVisible({ timeout: 5000 })
  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible()
})

test('delete incident priority', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/incidentPriorities')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const scrollContainer = page.locator('.overflow-x-auto').first()
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)

  const priorityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(priorityRow).toBeVisible({ timeout: 20000 })

  await priorityRow.getByTestId('delete-priority').click()

  await page.getByRole('button', { name: 'Eliminar' }).click()

  await expect(page.getByText('Eliminado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})
