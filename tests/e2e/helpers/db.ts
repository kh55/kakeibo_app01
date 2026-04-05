import { execSync } from 'child_process';
import * as fs from 'fs';
import * as path from 'path';

export function resetDatabase(): void {
  // SQLite ファイルが存在しない場合は作成
  const dbPath = path.join(process.cwd(), 'database', 'testing_e2e.sqlite');
  if (!fs.existsSync(dbPath)) {
    fs.writeFileSync(dbPath, '');
  }

  execSync('APP_ENV=testing php artisan migrate:fresh --seed --force', {
    cwd: process.cwd(),
    stdio: 'pipe',
  });
}
