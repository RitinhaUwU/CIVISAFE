import { test, expect } from '@playwright/test'
import {login, resetDB} from './helpers/auth'

test.describe.configure({ mode: 'serial' })

function uniqueEmail() {
  return `test-${Date.now()}-${Math.floor(Math.random() * 1000)}@test.com`
}

test.beforeEach(async ({ page }) => {
  await login(page)
})

test('create user', async ({ page }) => {
  await page.goto('http://localhost:3000/users')

  await page.getByRole('button', { name: 'Novo Utilizador' }).click()

  const email = uniqueEmail()

  await page.getByLabel('Nome').click()
  await page.getByLabel('Nome').fill('Test User')
  await page.getByLabel('Email').click()
  await page.getByLabel('Email').fill(email)
  await page.getByLabel('Telemóvel').click()
  await page.getByLabel('Telemóvel').fill('912345678')

  await page.getByLabel('Função').click()
  await page.getByRole('option', { name: 'Utilizador' }).click()

  const passwordField = page.getByTestId('password')
  await passwordField.click()
  await passwordField.clear()
  await passwordField.pressSequentially('password123', { delay: 50 })

  const confirmField = page.getByTestId('password-confirmation')
  await confirmField.click()
  await confirmField.clear()
  await confirmField.pressSequentially('password123', { delay: 50 })

  await expect(passwordField).toHaveValue('password123')
  await expect(confirmField).toHaveValue('password123')

  await page.waitForTimeout(500)
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Utilizador criado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('create user fails', async ({ page }) => {

  await page.goto('http://localhost:3000/users')

  await page.getByRole('button', { name: 'Novo Utilizador' }).click()
  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Nome demasiado curto')).toBeVisible({ timeout: 20000 })
})

test('edit user', async ({ page }) => {
  await page.goto('http://localhost:3000/users')

  await page.waitForSelector('table')

  await page.getByPlaceholder('Filtrar utilizadores...').fill('Utilizador User')

  await expect(page.locator('tr').filter({ hasText: 'Utilizador User' }).first()).toBeVisible({ timeout: 20000 })

  const userRow = page.locator('tr').filter({ hasText: 'Utilizador User' }).first()
  await userRow.getByTestId('edit-user').click()

  await page.waitForURL('**/users/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Nome').fill('Updated User')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Utilizador atualizado', { exact: true })).toBeVisible({ timeout: 10000 })
})

test('edit user fails', async ({ page }) => {

  await page.goto('http://localhost:3000/users')
  await page.waitForSelector('table')

  const userRow = page.getByRole('row').filter({ hasText: 'Updated User' }).first()
  await expect(userRow).toBeVisible({ timeout: 20000 })

  await userRow.getByTestId('edit-user').click()
  await page.waitForURL('**/users/**')

  await page.getByLabel('Email').fill('invalid-email')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Email inválido', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit user role', async ({ page }) => {

  await page.goto('http://localhost:3000/users')
  await page.waitForSelector('table')

  const userRow = page.getByRole('row').filter({ hasText: 'Updated User' }).first()
  await expect(userRow).toBeVisible({ timeout: 20000 })

  await userRow.getByTestId('edit-user').click()
  await page.waitForURL('**/users/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Função').click()
  await page.getByRole('option', { name: 'Gestor' }).click()

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Utilizador atualizado', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('edit user password mismatch fails', async ({ page }) => {

  await page.goto('http://localhost:3000/users')
  await page.waitForSelector('table')

  const userRow = page.getByRole('row').filter({ hasText: 'Updated User' }).first()
  await expect(userRow).toBeVisible({ timeout: 20000 })

  await userRow.getByTestId('edit-user').click()
  await page.waitForURL('**/users/**')

  await expect(page.getByLabel('Nome')).not.toHaveValue('', { timeout: 20000 })

  await page.getByLabel('Nova Palavra-Passe').fill('newpassword123')
  await page.getByLabel('Confirmar Palavra-Passe').fill('differentpassword')

  await page.getByRole('button', { name: 'Guardar' }).click()

  await expect(page.getByText('Passwords não coincidem', { exact: true })).toBeVisible({ timeout: 20000 })
})

test('search filters users', async ({ page }) => {

  await page.goto('http://localhost:3000/users')
  await page.waitForSelector('table')

  await page.getByPlaceholder('Filtrar utilizadores...').fill('Utilizador Administrador')
  await page.waitForTimeout(2000)

  await expect(page.getByRole('row').filter({ hasText: 'Utilizador Administrador' }).first()).toBeVisible({ timeout: 20000 })
})

test('search shows no results for unknown user', async ({ page }) => {

  await page.goto('http://localhost:3000/users')
  await page.waitForSelector('table')

  await page.getByPlaceholder('Filtrar utilizadores...').fill('xxxxxxxxxxxxxxxxxxx')
  await page.waitForTimeout(2000)

  await expect(page.getByRole('row').filter({ hasText: 'xxxxxxxxxxxxxxxxxxx' })).toHaveCount(0, { timeout: 20000 })
})

test('toggle user lock', async ({ page }) => {

  await page.goto('http://localhost:3000/users')
  await page.waitForSelector('table')

  const userRow = page.getByRole('row').filter({ hasText: 'Updated User' }).first()
  await expect(userRow).toBeVisible({ timeout: 20000 })

  await userRow.getByRole('button').first().click()

  await expect(
    page.locator('[data-slot="description"]').filter({ hasText: /foi ativado|foi bloqueado/ })
  ).toBeVisible({ timeout: 20000 })
})

test('cancel delete user', async ({ page }) => {

  await page.goto('http://localhost:3000/users')
  await page.waitForSelector('table')

  const userRow = page.getByRole('row').filter({ hasText: 'Updated User' }).first()
  await expect(userRow).toBeVisible({ timeout: 20000 })

  await userRow.getByTestId('delete-user').click()

  await expect(page.getByText(/Eliminar Utilizador:/)).toBeVisible({ timeout: 20000 })

  await page.getByRole('button', { name: 'Cancelar' }).click()

  await expect(page.getByText(/Eliminar Utilizador:/)).not.toBeVisible({ timeout: 5000 })
  await expect(page.getByRole('row').filter({ hasText: 'Updated User' }).first()).toBeVisible()
})

test('delete user', async ({ page }) => {

  await page.goto('http://localhost:3000/users')
  await page.waitForSelector('table')

  const userRow = page.getByRole('row').filter({ hasText: 'Updated User' }).first()
  await expect(userRow).toBeVisible({ timeout: 20000 })

  await userRow.getByTestId('delete-user').click()

  await page.getByRole('button', { name: 'Eliminar' }).click()

  await expect(page.getByText('Eliminado com sucesso', { exact: true })).toBeVisible({ timeout: 20000 })
})
