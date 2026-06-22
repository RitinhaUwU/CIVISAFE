import { test, expect, type Page } from '@playwright/test'
import { login } from './helpers/auth'
import * as path from 'path'

test.describe.configure({ mode: 'serial' })

const timestamp = Date.now()
const testName = `Test Entity ${timestamp}`
const updatedName = `Updated Entity ${timestamp}`

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

test('entities list loads', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')
  await page.waitForSelector('table')

  await expect(page.getByRole('columnheader', { name: 'Tipo' })).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('columnheader', { name: 'Nome' })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Telefone' })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Última Atualização' })).toBeVisible()

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10000 })
})

test('create entity', async ({ page }) => {
  await page.goto('http://localhost:3000/administration/entities')

  await page.getByRole('button', { name: 'Nova Entidade' }).click()
  await expect(page.getByText('Adicione uma Nova Entidade')).toBeVisible({ timeout: 10000 })

  await page.getByTestId('entity-type-select').click()
  await page.waitForTimeout(1000)
  const optionVisible = await page.locator('[role="option"]').first().isVisible().catch(() => false)
  if (!optionVisible) {
    await page.getByTestId('entity-type-select').click()
    await page.waitForTimeout(1000)
  }
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]').first().click()
  await page.waitForTimeout(500)

  await page.getByLabel('Nome:').fill(testName)
  await page.getByLabel('Email:').fill('teste@exemplo.com')
  await page.getByLabel('Contacto:').fill('912345678')
  await page.getByLabel('Morada:').fill('Rua de Teste, 1')
  await page.getByLabel('Nome do Responsável:').fill('João Silva')
  await page.getByLabel('Email do Responsável:').fill('joao@exemplo.com')
  await page.getByLabel('Contacto do Responsável:').fill('912345679')
  await page.getByLabel('Descrição:').fill('Descrição de teste')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await page.waitForTimeout(3000)

  await expect(page.getByText('Entidade criada com sucesso', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create entity fails without name', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')

  await page.getByRole('button', { name: 'Nova Entidade' }).click()

  await expect(page.getByText('Adicione uma Nova Entidade')).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create entity fails with invalid email', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')

  await page.getByRole('button', { name: 'Nova Entidade' }).click()

  await page.getByLabel('Nome:').fill(testName)
  await page.getByLabel('Email:').fill('email-invalido')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Email inválido', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create entity fails with invalid phone', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')

  await page.getByRole('button', { name: 'Nova Entidade' }).click()

  await page.getByLabel('Nome:').fill(testName)
  await page.getByLabel('Contacto:').fill('123')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Número inválido', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('create entity with image', async ({ page }) => {
  await page.goto('http://localhost:3000/administration/entities')

  await page.getByRole('button', { name: 'Nova Entidade' }).click()
  await expect(page.getByText('Adicione uma Nova Entidade')).toBeVisible({ timeout: 10000 })

  await page.getByTestId('entity-type-select').click()
  await page.waitForTimeout(1000)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]').first().click()
  await page.waitForTimeout(500)

  await page.getByLabel('Nome:').fill(`${testName} Com Imagem`)
  await page.getByLabel('Email:').fill('teste@exemplo.com')
  await page.getByLabel('Contacto:').fill('912345678')
  await page.getByLabel('Morada:').fill('Rua de Teste, 1')
  await page.getByLabel('Nome do Responsável:').fill('João Silva')
  await page.getByLabel('Email do Responsável:').fill('joao@exemplo.com')
  await page.getByLabel('Contacto do Responsável:').fill('912345679')
  await page.getByLabel('Descrição:').fill('Descrição de teste')

  const fileInput = page.locator('input[type="file"]').first()
  await fileInput.setInputFiles(path.resolve('tests/e2e/fixtures/test-image.jpg'))

  await expect(page.locator('img[alt="Preview"]')).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Guardar' }).click()
  await page.waitForTimeout(3000)

  await expect(page.getByText('Entidade criada com sucesso', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('edit entity', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, testName)

  const entityRow = page.locator('tbody tr').filter({ hasText: testName }).first()
  await expect(entityRow).toBeVisible({ timeout: 20000 })

  await entityRow.getByTestId('edit-entity').click()
  await page.waitForURL('**/entities/**')

  await expect(page.getByLabel('Nome da Entidade')).not.toHaveValue('', { timeout: 10000 })

  await page.getByLabel('Nome da Entidade').click()
  await page.getByLabel('Nome da Entidade').fill(updatedName)
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Entidade atualizada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit entity fails without name', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const entityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(entityRow).toBeVisible({ timeout: 20000 })

  await entityRow.getByTestId('edit-entity').click()
  await page.waitForURL('**/entities/**')

  await expect(page.getByLabel('Nome da Entidade')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Nome da Entidade').click()
  await page.getByLabel('Nome da Entidade').fill('')
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Nome é obrigatório', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('update entity image', async ({ page }) => {
  await page.goto('http://localhost:3000/administration/entities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const entityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await entityRow.getByTestId('edit-entity').click()
  await page.waitForURL('**/entities/**')
  await expect(page.getByLabel('Nome da Entidade')).not.toHaveValue('', { timeout: 10000 })

  const fileInput = page.locator('input[type="file"]').first()
  await fileInput.setInputFiles(path.resolve('tests/e2e/fixtures/test-image3.jpg'))
  await expect(page.locator('img[alt="Logotipo"]')).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Guardar' }).click()
  await expect(page.getByText('Entidade atualizada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('update entity image then restore previous', async ({ page }) => {
  await page.goto('http://localhost:3000/administration/entities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const entityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await entityRow.getByTestId('edit-entity').click()
  await page.waitForURL('**/entities/**')
  await expect(page.getByLabel('Nome da Entidade')).not.toHaveValue('', { timeout: 10000 })

  await expect(page.locator('img[alt="Logotipo"]')).toBeVisible({ timeout: 10000 })

  const fileInput = page.locator('input[type="file"]').first()
  await fileInput.setInputFiles(path.resolve('tests/e2e/fixtures/test-image2.jpg'))
  await expect(page.locator('img[alt="Logotipo"]')).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Restaurar' }).click({ force: true })

  await expect(page.locator('img[alt="Logotipo"]')).toBeVisible({ timeout: 5000 })
})

test('search filters entities', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  const input = page.getByPlaceholder('Filtrar entidades...')
  await input.click()
  await input.clear()
  await input.pressSequentially(updatedName, { delay: 100 })
  await page.waitForTimeout(1500)

  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible({ timeout: 20000 })
})

test('search shows no results for unknown entity', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')
  await page.waitForSelector('table')

  const input = page.getByPlaceholder('Filtrar entidades...')
  await input.click()
  await input.fill('xxxxxxxxxxxxxxxxxxx')
  await input.dispatchEvent('input')
  await page.waitForTimeout(1000)

  await expect(page.locator('tbody tr').filter({ hasText: 'xxxxxxxxxxxxxxxxxxx' })).toHaveCount(0, { timeout: 20000 })
})

test('filter entities by type', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await page.locator('[placeholder="Tipos"], button:has-text("Tipos")').first().click()
  await page.waitForTimeout(500)

  const firstOption = page.locator('[role="option"]').first()
  await expect(firstOption).toBeVisible({ timeout: 10000 })
  await firstOption.click()

  await page.waitForTimeout(1000)

  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })
})

test('cancel delete entity', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const entityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(entityRow).toBeVisible({ timeout: 20000 })

  await entityRow.getByTestId('delete-entity').click()

  await expect(page.getByText(/Eliminar Entidade:/)).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText(/Eliminar Entidade:/)).not.toBeVisible({ timeout: 5000 })
  await expect(page.locator('tbody tr').filter({ hasText: updatedName }).first()).toBeVisible()
})

test('delete entity', async ({ page }) => {

  await page.goto('http://localhost:3000/administration/entities')
  await page.waitForSelector('table')
  await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20000 })

  await scrollUntilVisible(page, updatedName)

  const entityRow = page.locator('tbody tr').filter({ hasText: updatedName }).first()
  await expect(entityRow).toBeVisible({ timeout: 20000 })

  await entityRow.getByTestId('delete-entity').click()

  await page.getByRole('button', { name: 'Eliminar' }).click()

  await expect(page.getByText('Eliminado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})

