import { test, expect } from '@playwright/test'
import {login, resetDB} from './helpers/auth'

test.describe.configure({ mode: 'serial' })

function uniqueEmail() {
  return `volunteer-${Date.now()}-${Math.floor(Math.random() * 1000)}@test.com`
}

const now = new Date()
const pad = (n: number) => String(n).padStart(2, '0')
const startDatetime = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`
const endDatetime = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate() + 1)}T${pad(now.getHours())}:${pad(now.getMinutes())}`

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('create volunteer', async ({ page }) => {

  await page.goto('http://localhost:3000/volunteers')

  await page.getByRole('button', { name: 'Novo Voluntário' }).click()

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill('Test Volunteer')
  await page.getByLabel('Contacto').click()
  await page.getByLabel('Contacto').fill('912345678')
  await page.getByLabel('Email').click()
  await page.getByLabel('Email').fill(uniqueEmail())
  await page.getByLabel('Identificação').click()
  await page.getByLabel('Identificação').fill('Equipa Teste')

  await page.getByLabel('Entrada').fill(startDatetime)
  await page.getByLabel('Saída').fill(endDatetime)

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Voluntário criado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('create volunteer fails', async ({ page }) => {

  await page.goto('http://localhost:3000/volunteers')

  await page.getByRole('button', { name: 'Novo Voluntário' }).click()
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit volunteer', async ({ page }) => {

  await page.goto('http://localhost:3000/volunteers')
  await page.waitForSelector('table')

  const volunteerRow = page.getByRole('row').filter({ hasText: 'Equipa Teste' }).first()
  await expect(volunteerRow).toBeVisible({ timeout: 20000 })

  await volunteerRow.getByTestId('edit-volunteer').click()

  await page.waitForURL('**/volunteers/**')
  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill('Updated Volunteer')
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Voluntário atualizado', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit volunteer fails', async ({ page }) => {

  await page.goto('http://localhost:3000/volunteers')
  await page.waitForSelector('table')

  const volunteerRow = page.getByRole('row').filter({ hasText: 'Updated Volunteer' }).first()
  await expect(volunteerRow).toBeVisible({ timeout: 20000 })

  await volunteerRow.getByTestId('edit-volunteer').click()

  await page.waitForURL('**/volunteers/**')
  await expect(page.getByLabel('Email')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Email').click()
  await page.getByLabel('Email').fill('invalid-email')
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Email inválido', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit volunteer classification', async ({ page }) => {

  await page.goto('http://localhost:3000/volunteers')
  await page.waitForSelector('table')

  const volunteerRow = page.getByRole('row').filter({ hasText: 'Updated Volunteer' }).first()
  await expect(volunteerRow).toBeVisible({ timeout: 20000 })

  await volunteerRow.getByTestId('edit-volunteer').click()

  await page.waitForURL('**/volunteers/**')
  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Classificação').click()
  await page.getByRole('option', { name: 'Organização' }).click()

  await expect(page.getByLabel('Nº Elementos')).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Voluntário atualizado', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('search filters volunteers', async ({ page }) => {

  await page.goto('http://localhost:3000/volunteers')
  await page.waitForSelector('table')

  await expect(page.getByRole('row').filter({ hasText: 'Updated Volunteer' }).first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar voluntários...')
  await input.click()
  await input.pressSequentially('Updated Volunteer', { delay: 100 })
  await page.waitForTimeout(1500)

  await expect(page.getByRole('row').filter({ hasText: 'Updated Volunteer' }).first()).toBeVisible({ timeout: 20000 })
})

test('search shows no results for unknown volunteer', async ({ page }) => {

  await page.goto('http://localhost:3000/volunteers')
  await page.waitForSelector('table')

  const input = page.getByPlaceholder('Filtrar voluntários...')
  await input.click()
  await input.fill('xxxxxxxxxxxxxxxxxxx')
  await input.dispatchEvent('input')
  await page.waitForTimeout(1000)

  await expect(page.getByRole('row').filter({ hasText: 'xxxxxxxxxxxxxxxxxxx' })).toHaveCount(0, { timeout: 20000 })
})

test('classification filter select works', async ({ page }) => {

  await page.goto('http://localhost:3000/volunteers')
  await page.waitForSelector('table')

  const select = page.getByRole('combobox').filter({ hasText: 'Tipo de Equipa' })
  await expect(select).toBeVisible({ timeout: 10000 })

  await select.click()

  await expect(page.getByRole('option', { name: 'Individual' })).toBeVisible({ timeout: 5000 })
  await expect(page.getByRole('option', { name: 'Organização' })).toBeVisible()
  await expect(page.getByRole('option', { name: 'Outro' })).toBeVisible()

  await page.getByRole('option', { name: 'Individual' }).click()

  await expect(page.getByRole('combobox').filter({ hasText: 'Individual' })).toBeVisible({ timeout: 10000 })
})

test('cancel delete volunteer', async ({ page }) => {

  await page.goto('http://localhost:3000/volunteers')
  await page.waitForSelector('table')

  const volunteerRow = page.getByRole('row').filter({ hasText: 'Updated Volunteer' }).first()
  await expect(volunteerRow).toBeVisible({ timeout: 20000 })

  await volunteerRow.getByTestId('delete-volunteer').click()

  await expect(page.getByText(/Eliminar Voluntário:/)).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText(/Eliminar Voluntário:/)).not.toBeVisible({ timeout: 5000 })
  await expect(page.getByRole('row').filter({ hasText: 'Updated Volunteer' }).first()).toBeVisible()
})

test('delete volunteer', async ({ page }) => {

  await page.goto('http://localhost:3000/volunteers')
  await page.waitForSelector('table')

  const volunteerRow = page.getByRole('row').filter({ hasText: 'Updated Volunteer' }).first()
  await expect(volunteerRow).toBeVisible({ timeout: 20000 })

  await volunteerRow.getByTestId('delete-volunteer').click()

  await page.getByRole('button', { name: 'Eliminar' }).click()

  await expect(page.locator('[data-slot="description"]').filter({ hasText: 'foi removido com sucesso' })).toBeVisible({ timeout: 20000 })
})
