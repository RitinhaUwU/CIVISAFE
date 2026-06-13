import { test, expect } from '@playwright/test'
import {login, resetDB} from './helpers/auth'

test.describe.configure({ mode: 'serial' })

const timestamp = Date.now()
const testName = `Test Entity Type ${timestamp}`
const updatedName = `Updated Entity Type ${timestamp}`

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('entity types list loads', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entityTypes')
  await page.waitForSelector('table')

  await expect(page.getByRole('columnheader', { name: 'Nome' })).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('columnheader', { name: 'Descrição' })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Última Atualização' })).toBeVisible()

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10000 })
})

test('create entity type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entityTypes')

  await page.getByRole('button', { name: 'Novo Tipo de Entidade' }).click()

  await page.getByLabel('Nome:').fill(testName)
  await page.getByLabel('Observações:').fill('Descrição de teste')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await page.waitForTimeout(3000)

  await expect(page.getByText('Tipo de Entidade criado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('create entity type fails', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entityTypes')

  await page.getByRole('button', { name: 'Novo Tipo de Entidade' }).click()

  await expect(page.getByText('Adicione um Novo Tipo de Entidade')).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('edit entity type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entityTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const scrollContainer = page.locator('.overflow-x-auto').first()
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)

  const entityRow = page.locator('tbody tr').filter({ hasText: testName }).first()
  await expect(entityRow).toBeVisible({ timeout: 20000 })

  await entityRow.getByTestId('edit-entity-type').click()
  await page.waitForURL('**/entityTypes/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 10000 })

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill(updatedName)
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Tipo de Entidade atualizada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit entity type fails', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entityTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const scrollContainer = page.locator('.overflow-x-auto').first()
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)

  const entityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(entityRow).toBeVisible({ timeout: 20000 })

  await entityRow.getByTestId('edit-entity-type').click()
  await page.waitForURL('**/entityTypes/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill('')
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('search filters entity types', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entityTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar tipos...')
  await input.click()
  await input.clear()
  await input.pressSequentially(updatedName, { delay: 100 })
  await page.waitForTimeout(1500)

  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible({ timeout: 20000 })
})

test('search shows no results for unknown entity type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entityTypes')
  await page.waitForSelector('table')

  const input = page.getByPlaceholder('Filtrar tipos...')
  await input.click()
  await input.fill('xxxxxxxxxxxxxxxxxxx')
  await input.dispatchEvent('input')
  await page.waitForTimeout(1000)

  await expect(page.locator('tbody tr').filter({ hasText: 'xxxxxxxxxxxxxxxxxxx' })).toHaveCount(0, { timeout: 20000 })
})

test('cancel delete entity type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entityTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const scrollContainer = page.locator('.overflow-x-auto').first()
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)

  const entityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(entityRow).toBeVisible({ timeout: 20000 })

  await entityRow.getByTestId('delete-entity-type').click()

  await expect(page.getByText(/Eliminar Tipo de Entidade:/)).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText(/Eliminar Tipo de Entidade:/)).not.toBeVisible({ timeout: 5000 })
  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible()
})

test('delete entity type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entityTypes')
  await page.waitForSelector('table')

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const scrollContainer = page.locator('.overflow-x-auto').first()
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)
  await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
  await page.waitForTimeout(1000)

  const entityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(entityRow).toBeVisible({ timeout: 20000 })

  await entityRow.getByTestId('delete-entity-type').click()

  await page.getByRole('button', { name: 'Eliminar' }).click()

  await expect(page.getByText('Eliminado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})
