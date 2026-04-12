import { Page, expect } from '@playwright/test';

export class LoginPage {
  constructor(private page: Page) {}

  async goto() {
    await this.page.goto('/login');
  }

  async login(email: string, password: string) {
    await this.page.fill('#email', email);
    await this.page.fill('#password', password);
    await this.page.click('button[type="submit"]');
  }

  async logout() {
    // Bootstrap dropdown: click user name toggle first, then logout button
    await this.page.locator('a.nav-link.dropdown-toggle').last().click();
    // Wait for dropdown menu to be fully open (Bootstrap adds 'show' class)
    await this.page.locator('.navbar-nav .dropdown-menu.show').waitFor({ state: 'visible' });
    await this.page.locator('form[action*="logout"] button[type="submit"]').click();
    // After logout, the app redirects to '/' (welcome page)
    await this.page.waitForURL('/');
  }

  async expectErrorVisible() {
    await expect(this.page.locator('.text-sm.text-red-600').first()).toBeVisible();
  }
}
