require('dotenv').config({ path: require('path').join(__dirname, '.env') });
const express = require('express');
const session = require('express-session');
const methodOverride = require('method-override');
const path = require('path');
const { csrfProtection } = require('./middleware/csrf');
const routes = require('./routes');
const db = require('./config/database');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3000;

// Ensure schema exists
const schemaPath = path.join(__dirname, 'db', 'schema.sql');
if (fs.existsSync(schemaPath)) {
  try {
    db.exec(fs.readFileSync(schemaPath, 'utf8'));
  } catch (e) {
    if (!e.message.includes('already exists')) throw e;
  }
}

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(require('express-ejs-layouts'));
app.set('layout', 'layout');

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(methodOverride('_method'));
app.use(express.static(path.join(__dirname, 'public')));

app.use(session({
  secret: process.env.SESSION_SECRET || 'midnight-travel-invoice-secret-change-in-production',
  resave: false,
  saveUninitialized: false,
  cookie: { secure: process.env.NODE_ENV === 'production', httpOnly: true, maxAge: 24 * 60 * 60 * 1000 },
}));

app.use(csrfProtection);
app.use((req, res, next) => {
  res.locals.csrfToken = req.csrfToken ? req.csrfToken() : '';
  next();
});

app.use(routes);

app.use((req, res) => {
  res.status(404).render('404', { title: 'Not Found', layout: false });
});

app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).send('Server error');
});

app.listen(PORT, () => {
  console.log(`Midnight Travel Invoices running at http://localhost:${PORT}`);
});
