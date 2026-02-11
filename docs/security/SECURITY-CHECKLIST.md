---
title: Security Implementation Checklist - Media Storage Optimization
type: security-checklist
date: 2026-01-26
status: action-required
---

# Security Implementation Checklist

## Pre-Implementation (BLOCKING)

### Credential Management
- [ ] **Install phpdotenv for environment variables**
  ```bash
  cd /Applications/MAMP/htdocs/acrylicon
  composer require vlucas/phpdotenv
  ```

- [ ] **Create .env file for credentials**
  ```bash
  # Create .env file (outside web root)
  cat > /Applications/MAMP/htdocs/acrylicon/.env << 'EOF'
  # Database Credentials
  DB_NAME=acrylicon
  DB_USER=acrylicon_user
  DB_PASSWORD=GENERATE_NEW_PASSWORD_HERE

  # WordPress Salts (regenerate from https://api.wordpress.org/secret-key/1.1/salt/)
  AUTH_KEY=GENERATE_NEW_KEY
  SECURE_AUTH_KEY=GENERATE_NEW_KEY
  LOGGED_IN_KEY=GENERATE_NEW_KEY
  NONCE_KEY=GENERATE_NEW_KEY
  AUTH_SALT=GENERATE_NEW_SALT
  SECURE_AUTH_SALT=GENERATE_NEW_SALT
  LOGGED_IN_SALT=GENERATE_NEW_SALT
  NONCE_SALT=GENERATE_NEW_SALT

  # Cloudflare R2 Credentials (when ready)
  R2_ACCESS_KEY_ID=
  R2_SECRET_ACCESS_KEY=
  R2_BUCKET=acrylicon-media
  R2_REGION=auto
  R2_CUSTOM_DOMAIN=https://media.acrylicon.no

  # ShortPixel API (when ready)
  SHORTPIXEL_API_KEY=
  EOF
  ```

- [ ] **Add .env to .gitignore (CRITICAL)**
  ```bash
  echo ".env" >> /Applications/MAMP/htdocs/acrylicon/.gitignore
  echo ".env.backup" >> /Applications/MAMP/htdocs/acrylicon/.gitignore
  echo ".env.*" >> /Applications/MAMP/htdocs/acrylicon/.gitignore
  ```

- [ ] **Add wp-config.php to .gitignore (CRITICAL)**
  ```bash
  echo "wp-config.php" >> /Applications/MAMP/htdocs/acrylicon/.gitignore
  ```

- [ ] **Remove wp-config.php from Git history**
  ```bash
  git rm --cached /Applications/MAMP/htdocs/acrylicon/wp-config.php
  git commit -m "Remove wp-config.php from version control"
  ```

- [ ] **Update wp-config.php to load environment variables**
  ```php
  // Add to top of wp-config.php (after <?php)
  require_once __DIR__ . '/vendor/autoload.php';
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();

  // Replace hardcoded credentials with:
  define( 'DB_NAME', $_ENV['DB_NAME'] );
  define( 'DB_USER', $_ENV['DB_USER'] );
  define( 'DB_PASSWORD', $_ENV['DB_PASSWORD'] );
  // ... etc for all credentials
  ```

- [ ] **Rotate all existing credentials**
  - [ ] Generate new database password
  - [ ] Generate new WordPress salts
  - [ ] Update production servers
  - [ ] Verify no hardcoded credentials remain

- [ ] **Verify Git repository clean**
  ```bash
  git status
  # Ensure wp-config.php is not staged
  # Ensure .env is not staged
  ```

### File Upload Security

- [ ] **Add file validation function to theme functions.php**
  - [ ] Extension whitelist: jpg, jpeg, png, gif, webp, pdf only
  - [ ] MIME type validation using finfo
  - [ ] File size limit: 10 MB maximum
  - [ ] SVG script detection and blocking
  - [ ] Path traversal prevention

- [ ] **Update media handler with validation**
  - [ ] Add validation call before file copy
  - [ ] Reject files failing validation
  - [ ] Log rejected upload attempts
  - [ ] Return secure error messages (no path disclosure)

- [ ] **Test file upload security**
  - [ ] Try uploading .php file (should reject)
  - [ ] Try uploading SVG with script (should reject)
  - [ ] Try uploading oversized file (should reject)
  - [ ] Verify only whitelisted types accepted

## Cloudflare R2 Configuration

### Bucket Setup

- [ ] **Create R2 bucket with security settings**
  - [ ] Bucket name: `acrylicon-media`
  - [ ] Access: Private (not public)
  - [ ] Region: Auto (nearest to Norway)
  - [ ] Versioning: Enabled (for rollback)

- [ ] **Configure bucket policy (least privilege)**
  ```json
  {
    "Version": "2012-10-17",
    "Statement": [
      {
        "Effect": "Allow",
        "Principal": {
          "AWS": "arn:aws:iam::ACCOUNT:user/wordpress"
        },
        "Action": [
          "s3:GetObject",
          "s3:PutObject",
          "s3:ListBucket"
        ],
        "Resource": [
          "arn:aws:s3:::acrylicon-media",
          "arn:aws:s3:::acrylicon-media/*"
        ]
      },
      {
        "Effect": "Deny",
        "Principal": "*",
        "Action": [
          "s3:DeleteBucket",
          "s3:PutBucketPolicy",
          "s3:PutBucketAcl"
        ],
        "Resource": "*"
      }
    ]
  }
  ```

- [ ] **Enable access logging**
  - [ ] Create separate logging bucket
  - [ ] Configure log delivery
  - [ ] Set log retention policy (90 days)

### API Token

- [ ] **Create limited-privilege API token**
  - [ ] Permissions: Read, Write (NOT Admin)
  - [ ] Scope: acrylicon-media bucket only
  - [ ] TTL: Set expiration (1 year maximum)
  - [ ] Store in .env file (not wp-config.php)

- [ ] **Document token creation date**
  - [ ] Add reminder to rotate in 6 months
  - [ ] Document rotation procedure

### CORS Configuration

- [ ] **Configure restrictive CORS policy**
  ```json
  {
    "AllowedOrigins": [
      "https://acrylicon.no",
      "https://en.acrylicon.no"
    ],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }
  ```

- [ ] **DO NOT use wildcards in AllowedOrigins**
- [ ] **Only allow GET/HEAD methods for media retrieval**
- [ ] **Add localhost for development if needed**

### Custom Domain

- [ ] **Configure custom domain with TLS**
  - [ ] Domain: media.acrylicon.no
  - [ ] TLS: Version 1.3 minimum
  - [ ] HTTPS: Enforce (no HTTP)
  - [ ] HSTS: Enable with max-age=31536000

- [ ] **Update wp-config.php**
  ```php
  define( 'MCS_PROVIDER', 'r2' );
  define( 'MCS_BUCKET', $_ENV['R2_BUCKET'] );
  define( 'MCS_REGION', $_ENV['R2_REGION'] );
  define( 'MCS_ACCESS_KEY_ID', $_ENV['R2_ACCESS_KEY_ID'] );
  define( 'MCS_SECRET_ACCESS_KEY', $_ENV['R2_SECRET_ACCESS_KEY'] );
  define( 'MCS_CUSTOM_DOMAIN', $_ENV['R2_CUSTOM_DOMAIN'] );
  define( 'MCS_FORCE_HTTPS', true );
  define( 'MCS_DELETE_LOCAL', false ); // Start false, enable after testing
  ```

## Security Monitoring

### Logging

- [ ] **Create security log file**
  ```bash
  touch /Applications/MAMP/htdocs/acrylicon/wp-content/security.log
  chmod 600 /Applications/MAMP/htdocs/acrylicon/wp-content/security.log
  ```

- [ ] **Add security.log to .gitignore**
  ```bash
  echo "security.log" >> /Applications/MAMP/htdocs/acrylicon/wp-content/.gitignore
  ```

- [ ] **Implement security event logging function**
  - [ ] Log all sync operations
  - [ ] Log failed upload attempts
  - [ ] Log authentication failures
  - [ ] Log suspicious file types

- [ ] **Set up log rotation**
  ```bash
  # Add to logrotate.d
  /Applications/MAMP/htdocs/acrylicon/wp-content/security.log {
      weekly
      rotate 4
      compress
      missingok
      notifempty
  }
  ```

### Alerting

- [ ] **Configure email alerts for critical events**
  - [ ] Failed authentication (5+ attempts)
  - [ ] Suspicious file uploads
  - [ ] Unusual API usage patterns
  - [ ] R2 cost threshold exceeded

- [ ] **Set up Cloudflare alerts**
  - [ ] API usage > 10,000 requests/day
  - [ ] Storage cost > 50 NOK/month
  - [ ] Unusual geographic access patterns

### Monitoring

- [ ] **Monitor API key usage**
  - [ ] Daily API call count
  - [ ] Failed API requests
  - [ ] Geographic access patterns

- [ ] **Monitor file uploads**
  - [ ] Upload frequency
  - [ ] Upload sizes
  - [ ] Rejected uploads

- [ ] **Monitor R2 costs**
  - [ ] Storage size
  - [ ] Bandwidth usage
  - [ ] API operation counts

## Input Validation (Media Sync Plugin)

### Admin UI Updates

- [ ] **Add rate limiting to AJAX handler**
  - [ ] Limit: 5 syncs per 5 minutes per user
  - [ ] Store attempts in transients
  - [ ] Return rate limit error message

- [ ] **Add comprehensive input validation**
  - [ ] Validate post_id exists and user can edit
  - [ ] Validate target_blog_id exists
  - [ ] Prevent self-sync (source = target)
  - [ ] Validate post type is syncable

- [ ] **Add IP validation for admin requests**
  - [ ] Store session IP on login
  - [ ] Compare with request IP
  - [ ] Reject mismatched IPs

### Media Handler Updates

- [ ] **Add path traversal protection**
  - [ ] Validate file path within upload directory
  - [ ] Use realpath() for canonicalization
  - [ ] Reject paths outside upload base

- [ ] **Add file validation before copy**
  - [ ] Call validation function
  - [ ] Log rejected files
  - [ ] Return descriptive error

- [ ] **Fix race condition in file check**
  - [ ] Use atomic operations
  - [ ] Create temp file first
  - [ ] Rename to final location

- [ ] **Add metadata sanitization**
  - [ ] Sanitize file names
  - [ ] Validate dimensions are integers
  - [ ] Sanitize MIME types

## Backup Security

### Backup Configuration

- [ ] **Configure encrypted backups**
  - [ ] Use GPG encryption for backup files
  - [ ] Store encryption keys separately
  - [ ] Document key recovery process

- [ ] **Set backup retention policy**
  - [ ] Daily: Keep 7 days
  - [ ] Weekly: Keep 4 weeks
  - [ ] Monthly: Keep 12 months
  - [ ] Auto-delete after retention period

- [ ] **Store backups in secure location**
  - [ ] Separate S3 bucket or R2 bucket
  - [ ] Enable versioning
  - [ ] Restrict access (IAM policies)

### Backup Testing

- [ ] **Test backup restoration**
  - [ ] Monthly restore drill
  - [ ] Verify all files restored correctly
  - [ ] Test on staging environment
  - [ ] Document restore time

- [ ] **Verify backup integrity**
  - [ ] Generate SHA-256 checksums
  - [ ] Verify checksums after backup
  - [ ] Store checksums separately

## Compliance

### GDPR Compliance

- [ ] **Sign Data Processing Agreement with Cloudflare**
  - [ ] Review terms
  - [ ] Confirm data location (EU/EEA)
  - [ ] Document agreement

- [ ] **Update privacy policy**
  - [ ] Disclose third-party processing (Cloudflare R2)
  - [ ] Explain data retention
  - [ ] Provide contact for data requests

- [ ] **Implement data subject request procedures**
  - [ ] Document how to request data deletion
  - [ ] Create process to delete files from R2
  - [ ] Test deletion procedure

- [ ] **Conduct Data Protection Impact Assessment (DPIA)**
  - [ ] Identify data processing activities
  - [ ] Assess risks to data subjects
  - [ ] Document mitigation measures

### Documentation

- [ ] **Document data processing activities**
  - [ ] What data is stored in R2
  - [ ] Why it's processed
  - [ ] How long it's retained
  - [ ] Who has access

- [ ] **Create incident response plan**
  - [ ] Define security incident
  - [ ] Notification procedures
  - [ ] Escalation path
  - [ ] Contact information

## Testing

### Security Testing

- [ ] **Penetration testing of upload functionality**
  - [ ] Test file upload bypasses
  - [ ] Test path traversal
  - [ ] Test SQL injection in metadata
  - [ ] Test XSS in file names

- [ ] **AJAX security testing**
  - [ ] Test CSRF protection
  - [ ] Test nonce validation
  - [ ] Test rate limiting
  - [ ] Test permission checks

- [ ] **Authentication testing**
  - [ ] Test with different user roles
  - [ ] Test network admin vs site admin
  - [ ] Test contributor/editor roles
  - [ ] Verify proper authorization

### Functional Testing

- [ ] **Test file sync with validation**
  - [ ] Upload valid file types
  - [ ] Verify rejected file types blocked
  - [ ] Check error messages (no information leakage)
  - [ ] Verify logs created

- [ ] **Test R2 integration**
  - [ ] Upload file to R2
  - [ ] Verify file accessible via URL
  - [ ] Test custom domain
  - [ ] Verify HTTPS enforced

- [ ] **Test rollback procedure**
  - [ ] Disable R2 sync
  - [ ] Restore from backup
  - [ ] Verify site functionality
  - [ ] Document rollback time

## Production Deployment

### Pre-Deployment

- [ ] **Complete all P0 checklist items**
- [ ] **Complete all P1 checklist items**
- [ ] **Review security audit report**
- [ ] **Obtain security team approval**
- [ ] **Create deployment runbook**

### Deployment

- [ ] **Deploy to staging first**
  - [ ] Test all functionality
  - [ ] Run security scans
  - [ ] Verify monitoring works
  - [ ] Test for 48 hours minimum

- [ ] **Deploy to production**
  - [ ] Take full backup before deployment
  - [ ] Deploy during maintenance window
  - [ ] Monitor logs closely
  - [ ] Verify functionality immediately

### Post-Deployment

- [ ] **Monitor for 7 days**
  - [ ] Check security logs daily
  - [ ] Monitor R2 usage
  - [ ] Check error logs
  - [ ] Verify no credential exposure

- [ ] **Conduct post-deployment security review**
  - [ ] Verify all controls working
  - [ ] Check for new vulnerabilities
  - [ ] Update documentation
  - [ ] Schedule next review

## Ongoing Maintenance

### Monthly

- [ ] **Review security logs**
- [ ] **Check for WordPress/plugin updates**
- [ ] **Monitor R2 costs**
- [ ] **Test backup restoration**

### Quarterly

- [ ] **Rotate API credentials**
- [ ] **Conduct security scan**
- [ ] **Review access permissions**
- [ ] **Update documentation**

### Annually

- [ ] **Full security audit**
- [ ] **Penetration testing**
- [ ] **DPIA review**
- [ ] **Incident response drill**

---

## Contact Information

**Security Team:** security@acrylicon.no
**Incident Response:** incident-response@acrylicon.no
**Emergency Contact:** [Define emergency contact]

---

**Last Updated:** 2026-01-26
**Next Review:** 2026-02-26
**Owner:** Technical Lead
