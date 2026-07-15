import { test, expect } from '@playwright/test'
import {login, resetDB} from './helpers/auth'

test.describe.configure({ mode: 'serial' })

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('donation audit page loads', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/audit')

  await expect(page.getByText('Doações - Gestão de Quebras')).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('button', { name: 'Registar Atualização de Stock' })).toBeVisible()
})

test('stock update modal opens with expected fields', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/audit')

  await page.getByRole('button', { name: 'Registar Atualização de Stock' }).click()

  await expect(page.getByRole('heading', { name: 'Registar Atualização de Stock' })).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('button', { name: 'Adicionar' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Remover' })).toBeVisible()
  await expect(page.getByText('Selecione uma Categoria...')).toBeVisible()
})

test('create stock update fails validation', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/audit')

  await page.getByRole('button', { name: 'Registar Atualização de Stock' }).click()
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Selecione a operação', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('register stock removal', async ({ page }) => {

  await page.goto('http://localhost:3000/donations/audit')

  await page.getByRole('button', { name: 'Registar Atualização de Stock' }).click()

  await page.getByRole('button', { name: 'Remover' }).click()

  await page.getByText('Selecione uma Categoria...').click()
  await page.getByRole('option').first().click()
  await expect(page.getByText('Selecione uma Categoria...')).toBeHidden({ timeout: 5000 })

  const removeQuantityField = page.getByRole('spinbutton', { name: /^Quantidade/ })
  await removeQuantityField.click()
  await removeQuantityField.press('Control+A')
  await removeQuantityField.pressSequentially('1', { delay: 50 })
  await removeQuantityField.press('Tab')

  await page.getByRole('combobox', { name: 'Motivo' }).click()
  await page.getByRole('option', { name: 'Item Danificado' }).click()

  await page.getByLabel('Observações').click()
  await page.getByLabel('Observações').pressSequentially('Registo de teste automatizado', { delay: 50 })

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByRole('heading', { name: 'Registar Atualização de Stock' })).not.toBeVisible({ timeout: 20000 })
})

test('audit timeline shows entries after stock updates', async ({ page }) => {
  await page.goto('http://localhost:3000/donations/audit')

  await expect(page.getByText('Sem Registos de Auditoria a Mostrar')).not.toBeVisible({ timeout: 20000 })
})
