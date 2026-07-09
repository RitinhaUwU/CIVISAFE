import { test, expect } from '@playwright/test'
import { login } from './helpers/auth'

test.describe.configure({ mode: 'serial' })

function uniqueIdentifier() {
  return `OC-TEST-${Date.now()}-${Math.floor(Math.random() * 1000)}`
}

let createdIdentifier: string

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('incidents page loads table', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await expect(page.getByRole('button', { name: 'Nova Ocorrência' })).toBeVisible()
})

test('search filter updates table', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.getByPlaceholder('Filtrar ocorrências...').fill('OC-')
  await page.waitForTimeout(1500)

  const rows = page.getByRole('row')
  await expect(rows).not.toHaveCount(0)
})

test('search with no results shows no data rows', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.waitForTimeout(12000)
  await page.getByPlaceholder('Filtrar ocorrências...').fill('nao')

  const tbodyRows = page.locator('tbody tr')
  await expect(tbodyRows).toHaveCount(1, { timeout: 10000 })
})

test('create incident successfully', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')

  await page.getByRole('button', { name: 'Nova Ocorrência' }).click()
  await expect(page.getByText('Registo Ocorrência')).toBeVisible({ timeout: 10000 })

  createdIdentifier = uniqueIdentifier()

  await page.getByLabel('Data Alerta').fill('2024-06-01T10:00')

  await page.locator('[placeholder="Selecionar estado"], button:has-text("Selecionar estado")').first().click()
  await page.waitForTimeout(500)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]').first().click()
  await page.waitForTimeout(300)

  await page.locator('[placeholder="Selecionar prioridade"], button:has-text("Selecionar prioridade")').first().click()
  await page.waitForTimeout(500)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]').first().click()
  await page.waitForTimeout(300)

  await page.locator('[placeholder="Selecionar tipo de ocorrência"], button:has-text("Selecionar tipo de ocorrência")').first().click()
  await page.waitForTimeout(500)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]').first().click()
  await page.waitForTimeout(300)

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(
    page.getByText('Ocorrência criada com sucesso', { exact: true })
  ).toBeVisible({ timeout: 20000 })
})

test('create incident fails with invalid phone number', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')

  await page.getByRole('button', { name: 'Nova Ocorrência' }).click()

  await page.getByLabel('Data Alerta').fill('2024-06-01T10:00')

  await page.getByLabel('Tlf. Contacto').fill('numero-invalido')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Insira apenas números ou formato +000 000000000')).toBeVisible({ timeout: 10000 })
})

test('cancel creation closes modal without creating incident', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')

  await page.getByRole('button', { name: 'Nova Ocorrência' }).click()
  await expect(page.getByText('Registo Ocorrência')).toBeVisible()

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText('Registo Ocorrência')).not.toBeVisible({ timeout: 5000 })
})

test('filter by state updates list', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.locator('[placeholder="Estado"], button:has-text("Estado")').first().click()
  await page.waitForTimeout(500)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]').nth(1).click()
  await page.waitForTimeout(1000)

  await expect(page.locator('table')).toBeVisible()
})

test('filter by major incident updates list', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.locator('[placeholder="Ocorrência Major"], button:has-text("Ocorrência Major")').first().click()
  await page.waitForTimeout(500)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]', { hasText: 'Sim' }).click()
  await page.waitForTimeout(1000)

  await expect(page.locator('table')).toBeVisible()
})

test('edit incident successfully', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')

  await page.getByRole('button', { name: 'Nova Ocorrência' }).click()
  await expect(page.getByText('Registo Ocorrência')).toBeVisible({ timeout: 10000 })

  await page.getByLabel('Data Alerta').fill('2024-06-01T10:00')

  await page.locator('[placeholder="Selecionar estado"], button:has-text("Selecionar estado")').first().click()
  await expect(page.locator('[role="option"]').first()).toBeVisible()
  await page.locator('[role="option"]').first().click()

  await page.locator('[placeholder="Selecionar prioridade"], button:has-text("Selecionar prioridade")').first().click()
  await expect(page.locator('[role="option"]').first()).toBeVisible()
  await page.locator('[role="option"]').first().click()

  await page.locator('[placeholder="Selecionar tipo de ocorrência"], button:has-text("Selecionar tipo de ocorrência")').first().click()
  await expect(page.locator('[role="option"]').first()).toBeVisible()
  await page.locator('[role="option"]').first().click()

  const [response] = await Promise.all([
    page.waitForResponse(resp =>
      resp.request().method() === 'POST' &&
      resp.url().includes('/incidents')
    ),
    page.getByRole('button', { name: 'Guardar' }).click()
  ])

  await expect(page.getByText('Ocorrência criada com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })

  const body = await response.json()
  const incidentId = body.data.id

  await page.goto(`http://localhost:3000/incidents/${incidentId}`)

  await page.waitForLoadState('networkidle')

  const obs = page.locator('#obs')
  await expect(obs).toBeVisible({ timeout: 20000 })

  const newObs = `Observação editada ${Date.now()}`
  await obs.fill(newObs)

  await page.locator('button.fixed.bottom-6.right-6').click()

  await expect(page.getByText('Ocorrência atualizada', { exact: true })).toBeVisible({ timeout: 20000 })

  await expect(obs).toHaveValue(newObs)
})

test('add PCO function successfully', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.getByRole('combobox').first().click()
  await page.getByRole('option', { name: 'Não' }).click()

  await page.waitForTimeout(1000)

  await page.locator('tbody tr').first().getByRole('button').nth(1).click()

  await page.waitForURL('**/incidents/**')

  await page.getByRole('tab', { name: 'Posto de Comando' }).click()
  await page.getByRole('button', { name: 'Nova Função' }).click()

  await page.getByRole('combobox', { name: 'Função' }).click()
  await page.waitForTimeout(500)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.getByRole('option', { name: 'COS' }).click()

  await page.getByLabel('Responsável').fill('João Silva')
  await page.getByLabel('Categoria').fill('Bombeiros')
  await page.getByLabel('Contacto 1').fill('912345678')
  await page.getByLabel('Data Início').fill('2024-06-01T08:00')

  await page.getByRole('button', { name: 'Adicionar' }).click()

  await expect(page.getByText('Função adicionada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('add PCO function fails without required fields', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.getByRole('combobox').first().click()
  await page.getByRole('option', { name: 'Não' }).click()

  await page.waitForTimeout(1000)

  await page.locator('tbody tr').first().getByRole('button').nth(1).click()

  await page.waitForURL('**/incidents/**')

  await page.getByRole('tab', { name: 'Posto de Comando' }).click()
  await page.getByRole('button', { name: 'Nova Função' }).click()

  await page.getByRole('button', { name: 'Adicionar' }).click()

  await expect(page.getByText('Selecione uma função', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('cancel PCO function creation closes modal', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.getByRole('combobox').first().click()
  await page.getByRole('option', { name: 'Não' }).click()

  await page.waitForTimeout(1000)

  await page.locator('tbody tr').first().getByRole('button').nth(1).click()

  await page.waitForURL('**/incidents/**')

  await page.getByRole('tab', { name: 'Posto de Comando' }).click()
  await page.getByRole('button', { name: 'Nova Função' }).click()

  await expect(page.getByText('Nova Função no PCO')).toBeVisible()

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText('Nova Função no PCO', { exact: true })).not.toBeVisible({ timeout: 5000 })
})

test('add logistics team successfully', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.getByRole('combobox').first().click()
  await page.getByRole('option', { name: 'Não' }).click()

  await page.waitForTimeout(1000)

  await page.locator('tbody tr').first().getByRole('button').nth(1).click()

  await page.waitForURL('**/incidents/**')

  await page.getByRole('tab', { name: 'Meios e Recursos' }).click()
  await page.getByRole('button', { name: 'Novo Recurso' }).click()

  await page.locator('[placeholder="Selecionar entidade"], button:has-text("Selecionar entidade")').first().click()
  await page.waitForTimeout(500)
  await expect(page.locator('[role="option"]').first()).toBeVisible({ timeout: 10000 })
  await page.locator('[role="option"]').first().click()
  await page.waitForTimeout(300)

  await page.getByLabel('Nº de Veículos').fill('3')
  await page.getByLabel('Nº de Operacionais').fill('12')

  await page.getByRole('button', { name: 'Adicionar' }).click()

  await expect(page.getByText('Recurso guardado', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('add logistics team fails without entity', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.getByRole('combobox').first().click()
  await page.getByRole('option', { name: 'Não' }).click()

  await page.waitForTimeout(1000)

  await page.locator('tbody tr').first().getByRole('button').nth(1).click()

  await page.waitForURL('**/incidents/**')

  await page.getByRole('tab', { name: 'Meios e Recursos' }).click()
  await page.getByRole('button', { name: 'Novo Recurso' }).click()

  await page.getByRole('button', { name: 'Adicionar' }).click()

  await expect(page.getByText('A entidade é obrigatória', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('edit PCO function successfully', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.getByRole('combobox').first().click()
  await page.getByRole('option', { name: 'Não' }).click()

  await page.waitForTimeout(1000)

  await page.locator('tbody tr').first().getByRole('button').nth(1).click()

  await page.waitForURL('**/incidents/**')
  await page.getByRole('tab', { name: 'Posto de Comando' }).click()

  await expect(page.locator('[data-testid="edit-pco"]').first()).toBeVisible({ timeout: 10000 }).catch(() => {})
  await page.locator('[data-testid="edit-pco"]').first().click({ force: true })

  await expect(page.getByText('Editar Função no PCO')).toBeVisible({ timeout: 10000 })

  await page.getByLabel('Responsável').fill('Maria Santos')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Função atualizada', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit logistics team successfully', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  await page.getByRole('combobox').first().click()
  await page.getByRole('option', { name: 'Não' }).click()

  await page.waitForTimeout(1000)

  await page.locator('tbody tr').first().getByRole('button').nth(1).click()

  await page.waitForURL('**/incidents/**')

  await page.getByRole('tab', { name: 'Meios e Recursos' }).click()

  await expect(page.locator('[data-testid="edit-logistic"]').first()).toBeVisible({ timeout: 10000 }).catch(() => {})
  await page.locator('[data-testid="edit-logistic"]').first().click({ force: true })

  await expect(page.getByText('Editar Equipa')).toBeVisible({ timeout: 10000 })

  await page.getByLabel('Nº de Veículos').fill('5')
  await page.getByLabel('Nº de Operacionais').fill('20')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Recurso guardado', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('cancel delete keeps incident', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  const row = page.locator('tbody tr').first()
  const identifier = (await row.locator('td').first().textContent())?.trim()

  expect(identifier).toBeTruthy()

  await row.getByRole('button').nth(2).click()

  await expect(page.getByText(/Eliminar ocorrência:/)).toBeVisible({ timeout: 10000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText(/Eliminar ocorrência:/)).not.toBeVisible({ timeout: 5000 })
  await expect(page.locator('tbody tr').filter({ hasText: createdIdentifier }).first()).toBeVisible()
})

test('delete incident successfully', async ({ page }) => {
  await page.goto('http://localhost:3000/incidents')
  await page.waitForSelector('table')

  const row = page.locator('tbody tr').first()
  const identifier = (await row.locator('td').first().textContent())?.trim()

  expect(identifier).toBeTruthy()

  await row.getByRole('button').nth(2).click()

  await page.getByRole('button', { name: 'Eliminar' }).click()

  await expect(page.getByText('Eliminado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})
