import { execSync } from 'child_process';

export function resetDatabase(): void {
  execSync(
    'docker compose exec -T app php artisan migrate:fresh --seed --seeder=LocalTestDataSeeder',
    { stdio: 'inherit' }
  );
}
