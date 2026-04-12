import { test, expect } from '@playwright/test';
import { LoginPage } from '../pages/LoginPage';
import { resetDatabase } from '../helpers/db';

test.describe('取引明細 - 分類フィルタ', () => {
  test.beforeAll(() => {
    resetDatabase();
  });

  test.beforeEach(async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login('test@example.com', 'password');
    await expect(page).toHaveURL(/dashboard/);
    await page.goto('/transactions');
    await expect(page).toHaveURL(/transactions/);
  });

  test('分類フィルタのドロップダウンが表示される', async ({ page }) => {
    const select = page.locator('select[name="category_id"]');
    await expect(select).toBeVisible();
    await expect(select.locator('option[value=""]')).toHaveText('分類：すべて');
    await expect(select.locator('option[value="null"]')).toHaveText('未分類');
    // option要素はDOMに存在することを確認（toBeVisibleはoption要素に使えないためcountで確認）
    await expect(select.locator('option', { hasText: '食費' })).toHaveCount(1);
  });

  test('カテゴリを選択してフィルタすると対応する取引のみ表示される', async ({ page }) => {
    await page.selectOption('select[name="category_id"]', { label: '食費' });
    await page.locator('form[method="GET"] button[type="submit"]').click();
    await expect(page).toHaveURL(/category_id=/);

    const categoryColumns = page.locator('table tbody tr td:nth-child(4)');
    const count = await categoryColumns.count();
    expect(count).toBeGreaterThan(0);
    for (let i = 0; i < count; i++) {
      await expect(categoryColumns.nth(i)).toHaveText('食費');
    }
  });

  test('未分類を選択してフィルタすると空状態メッセージが表示される', async ({ page }) => {
    await page.selectOption('select[name="category_id"]', { value: 'null' });
    await page.locator('form[method="GET"] button[type="submit"]').click();
    await expect(page).toHaveURL(/category_id=null/);
    await expect(page.locator('table tbody')).toContainText('表示する取引がありません');
  });
});
