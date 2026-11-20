# Invoice accounts and sample data

For a fresh production database, run migrations and then `php artisan invoices:create-admin admin` in Terminal. Enter a new password at both hidden prompts. The command creates one administrator. Add staff and viewer accounts under Users.

For an empty local demo database, `php artisan db:seed` creates sample users and invoices. Use the local demo logins in README.md. Production seeding skips demo records.

Existing installations retain their accounts and invoices. Rotate any demo passwords still in use and review historical sample records with your accountant before changing them.
