const csrf = require('csurf');

const csrfProtection = csrf({ cookie: false });

module.exports = { csrfProtection };
