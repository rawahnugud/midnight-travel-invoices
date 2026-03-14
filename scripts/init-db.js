const fs = require('fs');
const path = require('path');
const Database = require('better-sqlite3');

const dbPath = process.env.DATABASE_PATH || path.join(__dirname, '..', 'data', 'invoices.db');
const dir = path.dirname(dbPath);
if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });

const schemaPath = path.join(__dirname, '..', 'db', 'schema.sql');
const schema = fs.readFileSync(schemaPath, 'utf8');

const db = new Database(dbPath);
db.exec(schema);
db.close();
console.log('Database initialized at', dbPath);
