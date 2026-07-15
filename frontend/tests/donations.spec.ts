import { test, expect } from '@playwright/test'
import {login, resetDB} from './helpers/auth'

test.describe.configure({ mode: 'serial' })

const timestamp = Date.now()
const testName = `Test Doador ${timestamp}`
const updatedName = `Updated Doador ${timestamp}`
const testContact = '912345678'

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('donations dashboard loads', async ({ page }) => {
  await page.goto('http://localhost:3000/donations')

  await expect(page.getByText('Quantidade de Itens em Stock')).toBeVisible({ timeout: 10000 })
  await expect(page.getByText('Alertas de Abastecimento').first()).toBeVisible()
  await expect(page.getByText('Doações recentes')).toBeVisible()

  await expect(page.getByRole('button', { name: 'Registar Doação' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Ver Todas as Doações' })).toBeVisible()
})

test('create donation', async ({ page }) => {
  await page.goto('http://localhost:3000/donations')

  await page.getByRole('button', { name: 'Registar Doação' }).click()

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').pressSequentially(testName, { delay: 50 })

  await page.getByLabel('Contacto Telefónico').click()
  await page.getByLabel('Contacto Telefónico').pressSequentially(testContact, { delay: 50 })

  await page.getByRole('combobox').filter({ hasText: 'Selecione o Tipo de Doador...' }).click()
  await page.getByRole('option', { name: 'Pessoa Singular' }).click()

  await page.getByText('Selecione uma Categoria...').click()
  await page.getByRole('option').first().click()
  await expect(page.getByText('Selecione uma Categoria...')).toBeHidden({ timeout: 5000 })

  const quantityField = page.getByRole('spinbutton', { name: /^Quantidade/ })
  await quantityField.click()
  await quantityField.press('Control+A')
  await quantityField.pressSequentially('5', { delay: 50 })
  await quantityField.press('Tab')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Doação registada com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})
test('create donation fails', async ({ page }) => {
  await page.goto('http://localhost:3000/donations')

  await page.getByRole('button', { name: 'Registar Doação' }).click()
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('O Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create donation fails with invalid contact', async ({ page }) => {
  await page.goto('http://localhost:3000/donations')

  await page.getByRole('button', { name: 'Registar Doação' }).click()

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').pressSequentially(`${testName} contacto invalido`, { delay: 50 })

  await page.getByLabel('Contacto Telefónico').click()
  await page.getByLabel('Contacto Telefónico').pressSequentially('abc', { delay: 50 })

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Número inválido', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('edit donation', async ({ page }) => {
  await page.goto('http://localhost:3000/donations')
  await page.waitForSelector('table')

  const donationRow = page.locator('tbody tr').filter({ hasText: testName }).first()
  await expect(donationRow).toBeVisible({ timeout: 20000 })

  await donationRow.getByRole('button').click()
  await page.waitForURL('**/donations/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 10000 })

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill(updatedName)
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Doação atualizada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit donation fails', async ({ page }) => {
  await page.goto('http://localhost:3000/donations')
  await page.waitForSelector('table')

  const donationRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(donationRow).toBeVisible({ timeout: 20000 })

  await donationRow.getByRole('button').click()
  await page.waitForURL('**/donations/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill('')
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('O Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('search filters donations in modal', async ({ page }) => {
  await page.goto('http://localhost:3000/donations')

  await page.getByRole('button', { name: 'Ver Todas as Doações' }).click()

  const input = page.getByPlaceholder('Pesquisar...')
  await input.click()
  await input.pressSequentially(updatedName, { delay: 100 })
  await page.waitForTimeout(1500)

  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible({ timeout: 20000 })
})

test('search shows no results for unknown donation', async ({ page }) => {
  await page.goto('http://localhost:3000/donations')

  await page.getByRole('button', { name: 'Ver Todas as Doações' }).click()

  const input = page.getByPlaceholder('Pesquisar...')
  await input.click()
  await input.fill('xxxxxxxxxxxxxxxxxxx')
  await input.dispatchEvent('input')
  await page.waitForTimeout(1000)

  await expect(page.locator('tbody tr').filter({ hasText: 'xxxxxxxxxxxxxxxxxxx' })).toHaveCount(0, { timeout: 20000 })
})
