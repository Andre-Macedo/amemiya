import { test, expect } from '@playwright/test';

test.describe('Filament Admin - Autenticação e Segurança', () => {
  test('deve renderizar a tela de login do painel Filament com a marca Lean Tech', async ({ page }) => {
    await page.goto('/admin/login');

    // Verifica se a tela de login carregou
    await expect(page).toHaveURL(/.*\/admin\/login/);

    // O Filament renderiza o nome da marca configurado no AdminPanelProvider ('Lean Tech')
    const brandOrTitle = page.locator('text=Lean Tech');
    await expect(brandOrTitle.first()).toBeVisible();

    // Inputs de autenticação do Filament (Livewire form)
    const emailInput = page.locator('input[type="email"], input[id*="email"]');
    const passwordInput = page.locator('input[type="password"], input[id*="password"]');
    const submitButton = page.locator('button[type="submit"]');

    await expect(emailInput).toBeVisible();
    await expect(passwordInput).toBeVisible();
    await expect(submitButton).toBeVisible();
  });

  test('deve exibir mensagem de erro ao submeter credenciais inválidas no painel Filament', async ({ page }) => {
    await page.goto('/admin/login');

    const emailInput = page.locator('input[type="email"], input[id*="email"]');
    const passwordInput = page.locator('input[type="password"], input[id*="password"]');
    const submitButton = page.locator('button[type="submit"]');

    await emailInput.fill('operador.invalido@leantech.com.br');
    await passwordInput.fill('senha_errada_456');
    await submitButton.click();

    // Aguarda a resposta do Livewire e a exibição da mensagem de validação
    const errorMessage = page.locator('.fi-fo-field-wrp-error-message, .text-danger-600, [role="alert"]');
    await expect(errorMessage.first()).toBeVisible({ timeout: 10000 });
  });

  test('deve redirecionar para /admin/login ao tentar acessar o dashboard sem sessão', async ({ page }) => {
    await page.goto('/admin');
    await expect(page).toHaveURL(/.*\/admin\/login/);
  });
});
