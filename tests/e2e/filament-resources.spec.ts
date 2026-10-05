import { test, expect } from '@playwright/test';

test.describe('Filament Admin - Proteção de Rotas e Recursos Metrológicos', () => {
  const protectedRoutes = [
    { name: 'Dashboard Principal', path: '/admin' },
    { name: 'Cluster Metrologia', path: '/admin/metrology' },
    { name: 'Recurso de Instrumentos', path: '/admin/metrology/instruments' },
    { name: 'Recurso de Calibrações', path: '/admin/metrology/calibrations' },
    { name: 'Checagens Intermediárias', path: '/admin/metrology/intermediate-checks' },
    { name: 'Não Conformidades (RNC)', path: '/admin/metrology/non-conformities' },
    { name: 'Padrões de Referência', path: '/admin/metrology/reference-standards' },
  ];

  for (const route of protectedRoutes) {
    test(`deve bloquear acesso direto não autenticado a ${route.name} e redirecionar para login`, async ({ page }) => {
      await page.goto(route.path);

      // Deve redirecionar para a tela de autenticação do Filament com o parâmetro de retorno
      await expect(page).toHaveURL(/.*\/admin\/login/);

      // Garante que o formulário de login está visível
      const emailInput = page.locator('input[type="email"], input[id*="email"]');
      await expect(emailInput).toBeVisible();
    });
  }
});
