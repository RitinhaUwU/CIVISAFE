import { test, expect, type Page } from '@playwright/test'
import { login } from './helpers/auth'
import * as path from 'path'

test.describe.configure({ mode: 'serial' })

const timestamp = Date.now()
const testName = `Test Facility ${timestamp}`
const updatedName = `Updated Facility ${timestamp}`

async function scrollUntilVisible(page: Page, text: string, maxAttempts = 15) {
  const scrollContainer = page.locator('.overflow-x-auto').first()

  for (let i = 0; i < maxAttempts; i++) {
    const row = page.locator('tbody tr').filter({ hasText: text }).first()
    const visible = await row.isVisible().catch(() => false)
    if (visible) return

    await scrollContainer.evaluate(el => el.scrollTop = el.scrollHeight)
    await page.waitForTimeout(800)
  }
}

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('facilities list loads', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')

  await expect(page.getByRole('columnheader', { name: 'Nome' })).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('columnheader', { name: 'Telefone' })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Sede' })).toBeVisible()

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10000 })
})

test('create facility', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')

  await page.getByRole('button', { name: 'Nova Instalação' }).click()
  await expect(page.getByText('Adicione uma Nova Instalação')).toBeVisible({ timeout: 10000 })

  await page.getByLabel('Nome').fill(testName)
  await page.getByLabel('Email').fill('facility@exemplo.com')
  await page.getByLabel('Telefone').fill('912345678')
  await page.getByLabel('Sede').fill('Rua da Instalação, 1')
  await page.getByLabel('Observações').fill('Descrição de teste')

  await page.getByRole('button', { name: 'Guardar' }).click()
  await page.waitForTimeout(3000)

  await expect(page.getByText('Instalação criada com sucesso', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create facility with image', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')

  await page.getByRole('button', { name: 'Nova Instalação' }).click()
  await expect(page.getByText('Adicione uma Nova Instalação')).toBeVisible({ timeout: 10000 })

  await page.getByLabel('Nome').fill(`${testName} Com Imagem`)
  await page.getByLabel('Email').fill('facility@exemplo.com')
  await page.getByLabel('Telefone').fill('912345678')
  await page.getByLabel('Sede').fill('Rua da Instalação, 1')

  const fileInput = page.locator('input[type="file"]').first()
  await fileInput.setInputFiles(path.resolve('tests/e2e/fixtures/test-image.jpg'))
  await expect(page.locator('img[alt="Preview"]')).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Guardar' }).click()
  await page.waitForTimeout(3000)

  await expect(page.getByText('Instalação criada com sucesso', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create facility fails without name', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')

  await page.getByRole('button', { name: 'Nova Instalação' }).click()
  await expect(page.getByText('Adicione uma Nova Instalação')).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create facility fails with invalid email', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')

  await page.getByRole('button', { name: 'Nova Instalação' }).click()
  await expect(page.getByText('Adicione uma Nova Instalação')).toBeVisible({ timeout: 10000 })

  await page.getByLabel('Nome').fill(testName)
  await page.getByLabel('Email').fill('email-invalido')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Email inválido', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create facility fails with invalid phone', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')

  await page.getByRole('button', { name: 'Nova Instalação' }).click()
  await expect(page.getByText('Adicione uma Nova Instalação')).toBeVisible({ timeout: 10000 })

  await page.getByLabel('Nome').fill(testName)
  await page.getByLabel('Telefone').fill('123')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Número inválido', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('edit facility', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, testName)

  const facilityRow = page.locator('tbody tr').filter({ hasText: testName }).first()
  await expect(facilityRow).toBeVisible({ timeout: 20000 })

  await facilityRow.getByRole('button').first().click()
  await page.waitForURL('**/facilities/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 10000 })

  await page.getByLabel('Nome').fill(updatedName)

  await page.locator('button.fixed.bottom-6.right-6').click()

  await expect(page.getByText('Entidade atualizada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit facility fails without name', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const facilityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await facilityRow.getByRole('button').first().click()
  await page.waitForURL('**/facilities/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Nome').fill('')
  await page.locator('button.fixed.bottom-6.right-6').click()

  await expect(page.getByText('Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('update facility image', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const facilityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await facilityRow.getByRole('button').first().click()
  await page.waitForURL('**/facilities/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 10000 })

  const fileInput = page.locator('input[type="file"]').first()
  await fileInput.setInputFiles(path.resolve('tests/e2e/fixtures/test-image.jpg'))
  await expect(page.locator('img[alt="Logotipo"]')).toBeVisible({ timeout: 10000 })

  await page.locator('button.fixed.bottom-6.right-6').click()

  await expect(page.getByText('Entidade atualizada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('update facility image then restore previous', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const facilityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await facilityRow.getByRole('button').first().click()
  await page.waitForURL('**/facilities/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 10000 })

  await expect(page.locator('img[alt="Logotipo"]')).toBeVisible({ timeout: 10000 })

  const fileInput = page.locator('input[type="file"]').first()
  await fileInput.setInputFiles(path.resolve('tests/e2e/fixtures/test-image2.jpg'))
  await expect(page.locator('img[alt="Logotipo"]')).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Restaurar' }).click({ force: true })
  await expect(page.locator('img[alt="Logotipo"]')).toBeVisible({ timeout: 5000 })
})

test('search filters facilities', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar instalações...')
  await input.click()
  await input.clear()
  await input.pressSequentially(updatedName, { delay: 100 })
  await page.waitForTimeout(1500)

  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible({ timeout: 20000 })
})

test('search shows no results for unknown facility', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')

  const input = page.getByPlaceholder('Filtrar instalações...')
  await input.fill('xxxxxxxxxxxxxxxxxxx')
  await input.dispatchEvent('input')
  await page.waitForTimeout(1000)

  await expect(page.locator('tbody tr').filter({ hasText: 'xxxxxxxxxxxxxxxxxxx' })).toHaveCount(0, { timeout: 20000 })
})

test('upload document to facility', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const facilityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await facilityRow.getByRole('button').first().click()
  await page.waitForURL('**/facilities/**')

  await page.getByRole('tab', { name: 'Ficheiros' }).click()
  await page.waitForTimeout(500)

  await page.getByRole('button', { name: 'Carregar Ficheiros' }).click()
  await expect(page.getByText('Adicione documentos à instalação')).toBeVisible({ timeout: 10000 })

  const fileInput = page.locator('input[type="file"]').last()
  await fileInput.setInputFiles(path.resolve('tests/e2e/fixtures/document.pdf'))

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Ficheiros carregados com sucesso.', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('upload document fails without file', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const facilityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await facilityRow.getByRole('button').first().click()
  await page.waitForURL('**/facilities/**')

  await page.getByRole('tab', { name: 'Ficheiros' }).click()
  await page.waitForTimeout(500)

  await page.getByRole('button', { name: 'Carregar Ficheiros' }).click()
  await expect(page.getByText('Adicione documentos à instalação')).toBeVisible({ timeout: 10000 })

  await expect(page.getByRole('button', { name: 'Guardar' })).toBeDisabled({ timeout: 5000 })
})

test('delete document from facility', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const facilityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await facilityRow.getByRole('button').first().click()
  await page.waitForURL('**/facilities/**')

  await page.getByRole('tab', { name: 'Ficheiros' }).click()
  await page.waitForTimeout(500)

  await page.getByRole('button', { name: 'Carregar Ficheiros' }).click()
  await expect(page.getByText('Adicione documentos à instalação')).toBeVisible({ timeout: 10000 })
  const fileInput = page.locator('input[type="file"]').last()
  await fileInput.setInputFiles(path.resolve('tests/e2e/fixtures/document.pdf'))
  await page.getByRole('button', { name: 'Guardar' }).click()
  await expect(page.getByText('Ficheiros carregados com sucesso.', { exact: true })).toBeVisible({ timeout: 10000 })

  await page.waitForTimeout(1000)

  await page.getByTestId('delete-document').first().click()

  await expect(page.getByText('Ficheiro eliminado.', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('download document from facility', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const facilityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await facilityRow.getByRole('button').first().click()
  await page.waitForURL('**/facilities/**')

  await page.getByRole('tab', { name: 'Ficheiros' }).click()
  await page.waitForTimeout(500)

  await page.getByRole('button', { name: 'Carregar Ficheiros' }).click()
  await expect(page.getByText('Adicione documentos à instalação')).toBeVisible({ timeout: 10000 })
  const fileInput = page.locator('input[type="file"]').last()
  await fileInput.setInputFiles(path.resolve('tests/e2e/fixtures/document.pdf'))
  await page.getByRole('button', { name: 'Guardar' }).click()
  await expect(page.getByText('Ficheiros carregados com sucesso.', { exact: true })).toBeVisible({ timeout: 10000 })

  await page.waitForTimeout(1000)

  await page.getByTestId('download-document').first().click()

  await expect(page.getByText('Download iniciado.', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('cancel delete facility', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const facilityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(facilityRow).toBeVisible({ timeout: 20000 })

  await facilityRow.getByRole('button').nth(1).click()

  await expect(page.getByText(/Eliminar Instalação:/)).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText(/Eliminar Instalação:/)).not.toBeVisible({ timeout: 5000 })
  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible()
})

test('delete facility', async ({ page }) => {
  await page.goto('http://localhost:3000/facilities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const facilityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(facilityRow).toBeVisible({ timeout: 20000 })

  await facilityRow.getByRole('button').nth(1).click()

  await expect(page.getByText(/Eliminar Instalação:/)).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Eliminar' }).click()

  await expect(page.getByText('Eliminado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})
