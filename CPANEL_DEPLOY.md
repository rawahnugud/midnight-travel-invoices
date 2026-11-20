# Update the existing cPanel invoice application

This compatibility release uses the PHP 8.1 and Laravel 10 runtime already installed on the invoice site. The account PHP selector, domain isolation, document root, application key, staff accounts and database location stay as configured.

## Existing installation

1. Record the checked-out commit and take a fresh private backup of the SQLite database, settings and uploads. Preserve the installed dependencies and any server-generated Composer lock file as part of the backup.
2. During the release window pause invoice entry. Update the existing checkout to the reviewed release. In cPanel, **Deploy HEAD Commit** runs the update script. From Terminal the equivalent command in the application directory is `sh scripts/update-cpanel.sh`.
3. The script checks the installed runtime, refreshes the autoloader, runs Laravel package discovery directly, applies pending migrations, and rebuilds the configuration cache. It reuses the existing vendor directory. A failed step stops the script.
4. Verify login, the latest invoice, the invoice count, paid totals by currency, and a printed invoice before resuming entry.

## Recovery

The original cPanel checkout is saved at `baseline/cpanel-checkout-2692709`. Preserve newer invoices during a code rollback. Older code can authenticate deactivated users, so secure those accounts and invalidate their sessions before reopening an older release.

## Runtime maintenance

PHP 8.1 and Laravel 10 are outside upstream security support. Composer can report advisories for the installed runtime. This update addresses invoice application behavior. Review advisories separately from functional test results. The Laravel 12 release remains available for hosting with a supported PHP version.
