import { test, expect } from '@playwright/test'
import {login, resetDB} from './helpers/auth'

test.describe.configure({ mode: 'serial' })

const timestamp = Date.now()
const testName = `Test Entrega ${timestamp}`
const testContact = '912345678'
const updatedContact = '913456789'

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('donation distribution page loads', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/distribution')

  await expect(page.getByText('Nova Entrega')).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('button', { name: 'Registar Entrega' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Lista de Entregas' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Regulamento' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Stocks' })).toBeVisible()
})

test('add and remove goods rows', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/distribution')

  const categoryFields = page.getByText('Selecione uma Categoria...')
  await expect(categoryFields).toHaveCount(1)

  await page.getByRole('button', { name: 'Adicionar Linha' }).click()
  await expect(categoryFields).toHaveCount(2)

  await page.getByRole('button', { name: 'Adicionar Linha' }).click()
  await expect(categoryFields).toHaveCount(3)

  await page.getByTestId('remove-good-row').first().click()
  await expect(categoryFields).toHaveCount(2)
})

test('create distribution fails', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/distribution')

  await page.getByRole('button', { name: 'Registar Entrega' }).click()

  await expect(page.getByText('O Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create distribution', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/audit')
  await page.getByRole('button', { name: 'Registar Atualização de Stock' }).click()
  await page.getByRole('button', { name: 'Adicionar' }).click()

  await page.getByText('Selecione uma Categoria...').click()
  await page.getByRole('option').first().click()
  await expect(page.getByText('Selecione uma Categoria...')).toBeHidden({ timeout: 5000 })

  const stockQuantityField = page.getByRole('spinbutton', { name: /^Quantidade/ })
  await stockQuantityField.click()
  await stockQuantityField.press('Control+A')
  await stockQuantityField.pressSequentially('50', { delay: 50 })
  await stockQuantityField.press('Tab')

  await page.getByRole('combobox', { name: 'Motivo' }).click()
  await page.getByRole('option', { name: 'Correção de Diferença' }).click()

  await page.getByRole('button', { name: 'Guardar' }).click()
  await expect(page.getByRole('heading', { name: 'Registar Atualização de Stock' })).not.toBeVisible({ timeout: 20000 })

  await page.goto('http://localhost:3000/donations/distribution')

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').pressSequentially(testName, { delay: 50 })

  await page.getByLabel('Contacto', { exact: true }).click()
  await page.getByLabel('Contacto', { exact: true }).pressSequentially(testContact, { delay: 50 })

  await page.getByText('Selecione uma Categoria...').first().click()
  await page.getByRole('option').first().click()
  await expect(page.getByText('Selecione uma Categoria...').first()).toBeHidden({ timeout: 5000 })

  const quantityField = page.getByRole('spinbutton', { name: /^Quantidade/ })
  await quantityField.click()
  await quantityField.press('Control+A')
  await quantityField.pressSequentially('2', { delay: 50 })
  await quantityField.press('Tab')

  await page.getByRole('button', { name: 'Registar Entrega' }).click()

  await expect(page.getByText(`A entrega de bens a ${testName} foi registada com sucesso!`, { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit distribution via list modal', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/distribution')

  await page.getByRole('button', { name: 'Lista de Entregas' }).click()
  await page.waitForSelector('table')

  const distributionRow = page.locator('tbody tr').filter({ hasText: testName }).first()
  await expect(distributionRow).toBeVisible({ timeout: 20000 })

  await distributionRow.locator('button').first().click()

  await expect(page.getByText(`Entrega a ${testName}`)).toBeVisible({ timeout: 10000 })

  await page.getByLabel('Contacto', { exact: true }).click()
  await page.getByLabel('Contacto', { exact: true }).fill(updatedContact)

  await page.getByRole('button', { name: 'Guardar Alterações' }).click()

  await expect(page.getByText(`A entrega de bens a ${testName} foi registada com sucesso!`, { exact: true })).toBeVisible({ timeout: 20000 })
})

test('view distribution history', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/distribution')

  await page.getByRole('button', { name: 'Lista de Entregas' }).click()
  await page.waitForSelector('table')

  const distributionRow = page.locator('tbody tr').filter({ hasText: testName }).first()
  await expect(distributionRow).toBeVisible({ timeout: 20000 })

  await distributionRow.locator('button').last().click()

  await expect(page.getByText(new RegExp(`Histórico da entrega a ${testName}`))).toBeVisible({ timeout: 10000 })
})

test('open rules modal', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/distribution')

  await page.getByRole('button', { name: 'Regulamento' }).click()

  await expect(page.getByRole('heading', { name: /Regulamento/ })).toBeVisible({ timeout: 10000 })
})

test('open stocks modal', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/distribution')

  await page.getByRole('button', { name: 'Stocks' }).click()

  await expect(page.getByRole('heading', { name: 'Stocks' })).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('columnheader', { name: 'Tipo de Bem' })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Última Atualização' })).toBeVisible()
})
