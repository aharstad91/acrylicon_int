---
status: pending
priority: p1
issue_id: "002"
tags: [code-review, security, credentials, phase3]
dependencies: []
---

# API Credential Exposure in wp-config.php

## Problem Statement

The plan recommends storing Cloudflare R2 API keys as **plain text constants in wp-config.php**, which creates multiple critical security vulnerabilities:

1. **Version control exposure:** wp-config.php is located OUTSIDE wp-content directory and may not be in .gitignore
2. **File system access:** Anyone with FTP/SSH/file access can read credentials
3. **No encryption:** Credentials stored in plain text
4. **Permanent exposure:** If committed to Git, credentials remain in Git history forever

**Why it matters:** Exposed R2 credentials = full access to media bucket, ability to delete all files, unlimited API usage costs.

## Findings

**Source:** Security-sentinel agent

**Current state:**
- Plan (lines 369-371) suggests: `define( 'MCS_ACCESS_KEY_ID', 'your-access-key-id' );`
- Database password already exposed in plain text in wp-config.php (line 29)
- No evidence that wp-config.php is in .gitignore
- wp-config.php location: `/Applications/MAMP/htdocs/acrylicon/wp-config.php` (outside wp-content)

**Attack scenarios:**
1. Developer commits wp-config.php → credentials in Git history
2. FTP account compromised → attacker reads wp-config.php
3. Server backup leaked → credentials exposed
4. WordPress vulnerability exposes file system → wp-config.php readable

**Real-world severity:** This is a **CRITICAL** vulnerability that has led to major data breaches in production environments.

## Proposed Solutions

### Option 1: Environment Variables with php-dotenv (Recommended)

Use Composer package to load credentials from .env file:

```bash
# Install phpdotenv
cd /Applications/MAMP/htdocs/acrylicon
composer require vlucas/phpdotenv
```

```php
// In wp-config.php (top of file)
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Use environment variables
define( 'MCS_ACCESS_KEY_ID', $_ENV['R2_ACCESS_KEY_ID'] );
define( 'MCS_SECRET_ACCESS_KEY', $_ENV['R2_SECRET_ACCESS_KEY'] );
define( 'DB_PASSWORD', $_ENV['DB_PASSWORD'] );
```

```bash
# .env file (add to .gitignore!)
R2_ACCESS_KEY_ID=your-actual-key
R2_SECRET_ACCESS_KEY=your-actual-secret
DB_PASSWORD=your-db-password
```

```bash
# .gitignore
.env
.env.local
.env.*.local
```

- **Pros:** Industry standard, secure, easy to rotate credentials, works locally and production
- **Cons:** Adds Composer dependency
- **Effort:** Small (2-3 hours)
- **Risk:** Low (well-tested package, 185M+ downloads)

### Option 2: Server Environment Variables

Set credentials at web server level (Apache/Nginx):

```apache
# In .htaccess or Apache config
SetEnv R2_ACCESS_KEY_ID "your-key"
SetEnv R2_SECRET_ACCESS_KEY "your-secret"
```

```php
// In wp-config.php
define( 'MCS_ACCESS_KEY_ID', getenv('R2_ACCESS_KEY_ID') );
define( 'MCS_SECRET_ACCESS_KEY', getenv('R2_SECRET_ACCESS_KEY') );
```

- **Pros:** No additional dependencies, credentials never touch filesystem
- **Cons:** Requires server access, harder to manage locally
- **Effort:** Small (if you have server access)
- **Risk:** Low

### Option 3: WordPress Constants with Encrypted Storage

Use WordPress encryption functions:

```php
// In wp-config.php
define( 'ENCRYPTION_KEY', 'generate-random-64-char-key' );  // Store this separately

// Helper functions in mu-plugin
function get_encrypted_credential($key) {
    $encrypted = get_option('encrypted_' . $key);
    return decrypt_value($encrypted, ENCRYPTION_KEY);
}

define( 'MCS_ACCESS_KEY_ID', get_encrypted_credential('r2_access_key') );
```

- **Pros:** No external dependencies
- **Cons:** Complex, still requires securing encryption key
- **Effort:** Medium (4-6 hours)
- **Risk:** Medium (custom crypto implementation)

## Recommended Action

**Immediate (BLOCKING):**
1. ✅ Implement Option 1 (phpdotenv) - industry standard, proven secure
2. ✅ Add .env to .gitignore
3. ✅ Verify wp-config.php is NOT in version control
4. ✅ If wp-config.php was committed, clean Git history: `git filter-branch --force --index-filter 'git rm --cached --ignore-unmatch wp-config.php' --prune-empty --tag-name-filter cat -- --all`
5. ✅ Rotate ALL existing credentials (R2 keys, database password, WordPress salts)

## Technical Details

**Affected Files:**
- `/Applications/MAMP/htdocs/acrylicon/wp-config.php` (modify to use environment variables)
- `/Applications/MAMP/htdocs/acrylicon/.env` (create, add to .gitignore)
- `/Applications/MAMP/htdocs/acrylicon/.gitignore` (add .env)
- `/Applications/MAMP/htdocs/acrylicon/composer.json` (add phpdotenv dependency)

**Components:**
- Cloudflare R2 API authentication
- Database connection
- WordPress security salts (consider rotating)

**Credential Rotation Required:**
1. Generate new R2 API key in Cloudflare dashboard
2. Delete old R2 API key
3. Generate new database password
4. Generate new WordPress auth/secure/logged_in/nonce salts using [https://api.wordpress.org/secret-key/1.1/salt/](https://api.wordpress.org/secret-key/1.1/salt/)

## Acceptance Criteria

- [ ] .env file created with all sensitive credentials
- [ ] .env added to .gitignore
- [ ] wp-config.php modified to use environment variables via phpdotenv
- [ ] Composer dependency vlucas/phpdotenv installed
- [ ] All existing credentials rotated (R2, database, salts)
- [ ] Verified .env is NOT in Git (`git ls-files | grep .env` returns nothing)
- [ ] Site loads correctly with new credential system
- [ ] R2 uploads work with new credentials
- [ ] Database connections work with new password
- [ ] Clean Git history if wp-config.php was previously committed
- [ ] Document credential rotation procedure in README

## Work Log

### 2026-01-26
- Identified critical security vulnerability during security review
- Confirmed database password already exposed in plain text
- Recommended phpdotenv as industry-standard solution

## Resources

- Plan section: Lines 369-371 (insecure credential storage)
- [vlucas/phpdotenv documentation](https://github.com/vlucas/phpdotenv)
- [WordPress Environment Variables Guide](https://roots.io/twelve-factor-wordpress/)
- [OWASP: Storing Credentials Securely](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)
- [Git History Cleaning](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository)
- [WordPress Salts Generator](https://api.wordpress.org/secret-key/1.1/salt/)
