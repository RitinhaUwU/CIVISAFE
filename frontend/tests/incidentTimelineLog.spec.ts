import { test, expect } from '@playwright/test'
import { login } from './helpers/auth'

test.describe.configure({ mode: 'serial' })

test.beforeEach(async ({ page }) => {
  await login(page)
})

