import { resetDatabase } from './helpers/db';

async function globalSetup() {
  resetDatabase();
}

export default globalSetup;
