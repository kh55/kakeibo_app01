import { test, expect } from '@playwright/test';
import { LoginPage } from '../pages/LoginPage';

test.describe('認証', () => {
  test('正しい認証情報でログインできる', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login('test@example.com', 'password');
    await expect(page).toHaveURL(/dashboard/);
  });

  test('誤ったパスワードでログインできない', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login('test@example.com', 'wrongpassword');
    await loginPage.expectErrorVisible();
    await expect(page).toHaveURL(/login/);
  });

  test('誤ったメールアドレスでログインできない', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login('notexist@example.com', 'password');
    await loginPage.expectErrorVisible();
    await expect(page).toHaveURL(/login/);
  });

  test('未認証ユーザーは /dashboard にアクセスできない', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/login/);
  });

  test('ログアウトできる', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login('test@example.com', 'password');
    await expect(page).toHaveURL(/dashboard/);
    await loginPage.logout();
    // After logout, the app redirects to '/' (welcome page)
    await expect(page).toHaveURL('http://localhost:8080/');
  });
});
