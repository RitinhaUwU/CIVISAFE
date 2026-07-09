import { test, expect } from '@playwright/test'
import { login } from './helpers/auth'

test.describe.configure({ mode: 'serial' })

test.beforeEach(async ({ page }) => {
  await login(page)
})

async function goToFirstIncidentTimeline(page: any) {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  const editButton = page.locator('tbody tr').first().getByRole('button').nth(1)
  await editButton.click()
  await page.waitForURL('**/incidents/**')

  await page.waitForTimeout(10500)
  await page.getByRole('tab', { name: 'Fita de Tempo' }).click()
  await page.waitForTimeout(1000)
}

test('timeline tab loads correctly', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  await expect(page.getByText('Nova entrada manual')).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('heading', { name: 'Fita de Tempo' })).toBeVisible()
  await expect(page.getByLabel('Descrição')).toBeVisible()
  await expect(page.getByRole('button', { name: 'Guardar' })).toBeVisible()
})

test('create comment successfully', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  const commentBody = `Entrada de teste - ${Date.now()}`
  await page.getByLabel('Descrição').fill(commentBody)
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Entrada registada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('create comment with custom datetime', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  await page.getByLabel('Data da ocorrência').fill('2024-06-01T09:30')
  await page.getByLabel('Descrição').fill(`Entrada com data manual - ${Date.now()}`)
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Entrada registada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('guardar button is disabled when comment is empty', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  await page.getByLabel('Descrição').fill('')

  await expect(page.getByRole('button', { name: 'Guardar' })).toBeDisabled()
})

test('guardar button enables after typing comment', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  await page.getByLabel('Descrição').fill('')
  await expect(page.getByRole('button', { name: 'Guardar' })).toBeDisabled()

  await page.getByLabel('Descrição').fill('Texto de teste')
  await expect(page.getByRole('button', { name: 'Guardar' })).toBeEnabled()
})

test('textarea clears after submitting comment', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  await page.getByLabel('Descrição').fill('Comentário para limpar após submit')
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Entrada registada', { exact: true })).toBeVisible({ timeout: 20000 })
  await expect(page.getByLabel('Descrição')).toHaveValue('')
})

test('edit comment shows edit form inline', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  const commentBody = `Comentário para editar - ${Date.now()}`
  await page.getByLabel('Descrição').fill(commentBody)
  await page.getByRole('button', { name: 'Guardar' }).click()
  await expect(page.getByText('Entrada registada', { exact: true })).toBeVisible({ timeout: 20000 })
  await page.waitForTimeout(1000)

  await expect(page.getByText(commentBody)).toBeVisible({ timeout: 10000 })
  await page.locator('[data-testid="edit-comment"]').first().click()

  await expect(page.getByText(commentBody)).not.toBeVisible({ timeout: 5000 })
  await expect(page.locator('textarea').nth(1)).toBeVisible({ timeout: 10000 })
  await expect(page.getByRole('button', { name: 'Cancelar' })).toBeVisible()
})

test('edit comment successfully', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  const originalBody = `Original - ${Date.now()}`
  await page.getByLabel('Descrição').fill(originalBody)
  await page.getByRole('button', { name: 'Guardar' }).click()
  await expect(page.getByText('Entrada registada', { exact: true })).toBeVisible({ timeout: 20000 })
  await page.waitForTimeout(1000)

  await expect(page.getByText(originalBody)).toBeVisible({ timeout: 10000 })
  await page.locator('[data-testid="edit-comment"]').first().click()

  const editTextarea = page.locator('textarea').nth(1)
  await expect(editTextarea).toBeVisible({ timeout: 10000 })
  await editTextarea.clear()

  const editedBody = `Editado - ${Date.now()}`
  await editTextarea.fill(editedBody)

  await page.getByRole('button', { name: 'Guardar' }).last().click()

  await expect(page.getByText('Entrada atualizada', { exact: true })).toBeVisible({ timeout: 20000 })
  await expect(page.getByText(editedBody)).toBeVisible({ timeout: 10000 })
})

test('cancel edit comment closes inline form', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  const cancelBody = `Cancelar edição - ${Date.now()}`
  await page.getByLabel('Descrição').fill(cancelBody)
  await page.getByRole('button', { name: 'Guardar' }).click()
  await expect(page.getByText('Entrada registada', { exact: true })).toBeVisible({ timeout: 20000 })
  await page.waitForTimeout(1000)

  await expect(page.getByText(cancelBody)).toBeVisible({ timeout: 10000 })
  await page.locator('[data-testid="edit-comment"]').first().click()

  const editTextarea = page.locator('textarea').nth(1)
  await expect(editTextarea).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(editTextarea).not.toBeVisible({ timeout: 5000 })
  await expect(page.getByText(cancelBody)).toBeVisible({ timeout: 5000 })
})

test('edit comment with custom datetime updates entry', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  const datetimeBody = `Data custom - ${Date.now()}`
  await page.getByLabel('Descrição').fill(datetimeBody)
  await page.getByRole('button', { name: 'Guardar' }).click()
  await expect(page.getByText('Entrada registada', { exact: true })).toBeVisible({ timeout: 20000 })
  await page.waitForTimeout(1000)

  await expect(page.getByText(datetimeBody)).toBeVisible({ timeout: 10000 })
  await page.locator('[data-testid="edit-comment"]').first().click()

  const editDateInput = page.locator('input[type="datetime-local"]').nth(1)
  await expect(editDateInput).toBeVisible({ timeout: 10000 })
  await editDateInput.fill('2024-01-15T14:00')

  await page.getByRole('button', { name: 'Guardar' }).last().click()

  await expect(page.getByText('Entrada atualizada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('saving incident general data creates timeline log entry', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  const editButton = page.locator('tbody tr').first().getByRole('button').nth(1)
  await editButton.click()
  await page.waitForURL('**/incidents/**')
  await expect(page.getByLabel('Identificador')).not.toHaveValue('', { timeout: 20000 })

  await page.getByRole('tab', { name: 'Geral' }).click()
  await page.waitForTimeout(500)

  await page.locator('#obs').fill(`Obs atualizada - ${Date.now()}`)
  await page.getByLabel('Tlf. Contacto:').fill('912345678')
  await page.locator('button.fixed.bottom-6.right-6').click()
  await expect(page.getByText('Ocorrência atualizada', { exact: true })).toBeVisible({ timeout: 20000 })

  await page.getByRole('tab', { name: 'Fita de Tempo' }).click()
  await page.waitForTimeout(1000)

  await expect(page.getByText('Ver alterações').first()).toBeVisible({ timeout: 10000 })
})

test('log entry accordion expands to show field changes', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  const accordion = page.getByText('Ver alterações').first()
  const hasLog = await accordion.isVisible().catch(() => false)

  if (!hasLog) {
    test.skip()
    return
  }

  await accordion.click()

  await expect(page.locator('.space-y-1.text-xs').first()).toBeVisible({ timeout: 5000 })
})

test('comment entries show pencil edit button', async ({ page }) => {
  await goToFirstIncidentTimeline(page)

  await page.getByLabel('Descrição').fill(`Comentário ícone - ${Date.now()}`)
  await page.getByRole('button', { name: 'Guardar' }).click()
  await expect(page.getByText('Entrada registada', { exact: true })).toBeVisible({ timeout: 20000 })
  await page.waitForTimeout(500)

  await expect(page.locator('[data-testid="edit-comment"]').first()).toBeVisible({ timeout: 10000 })
})
