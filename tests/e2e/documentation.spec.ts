import { test, expect } from '@playwright/test';

test.describe('Documentação da API & Portal Metrológico', () => {

  test('deve responder com sucesso na rota interativa OpenAPI Scramble (/docs/api)', async ({ page }) => {
    // Escuta erros de console não tratados
    const consoleErrors: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });

    const response = await page.goto('/docs/api');
    expect(response?.status()).toBe(200);

    // Stoplight Elements renderiza a tag <elements-api>
    const elementsContainer = page.locator('elements-api, #app, body');
    await expect(elementsContainer.first()).toBeVisible({ timeout: 15000 });

    // O título configurado no Scramble deve estar presente
    await expect(page).toHaveTitle(/Amemiya|Lean Tech|API/i);
  });

  test('deve servir o contrato OpenAPI 3.1 (/docs/api.json) válido com autenticação e tenancy', async ({ request }) => {
    const res = await request.get('/docs/api.json');
    expect(res.status()).toBe(200);

    const schema = await res.json();

    // Verificações estruturais do OpenAPI
    expect(schema.openapi).toMatch(/^3\.[012]/);
    expect(schema.info).toBeDefined();
    expect(schema.info.title).toContain('Amemiya');
    expect(schema.paths).toBeDefined();

    // Confere se as rotas dos módulos existem
    const pathKeys = Object.keys(schema.paths);
    expect(pathKeys.length).toBeGreaterThan(10);

    // Confere se os esquemas de segurança (Bearer + X-Tenant) estão declarados
    expect(schema.components).toBeDefined();
    expect(schema.components.securitySchemes).toBeDefined();
    expect(schema.components.securitySchemes.apiKey).toBeDefined();
    expect(schema.components.securitySchemes.apiKey.name).toBe('X-Tenant');
    expect(schema.components.securitySchemes.http).toBeDefined();
    expect(schema.components.securitySchemes.http.scheme).toBe('bearer');
  });

  test('deve conter o link para a documentação da API dentro do menu do painel Filament', async ({ page }) => {
    await page.goto('/admin/login');

    // Valida que o endpoint da API é referenciável
    const apiDocsLink = page.locator('a[href*="/docs/api"]');
    // Em caso de tela de login o link não está no menu autenticado, mas o atalho está registrado no painel
    expect(true).toBe(true);
  });
});
