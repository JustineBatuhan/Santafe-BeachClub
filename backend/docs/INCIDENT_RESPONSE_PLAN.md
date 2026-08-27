# 🚨 Incident Response Plan
## Santa Fe Beach Club Booking System

**Version:** 1.0  
**Last Updated:** 2026-08-25  
**Owner:** System Administrator

---

## 1. Purpose
This plan defines the steps to detect, contain, eradicate, and recover from a security incident affecting the Santa Fe Beach Club Booking System, its database, or its guest/staff data.

---

## 2. Incident Severity Classification

| Level | Name | Examples |
|:---:|:---|:---|
| 🔴 **P1 — Critical** | Full Compromise | Database dumped, admin account hijacked, ransomware |
| 🟠 **P2 — High** | Partial Breach | Unauthorized admin login, CSRF exploit successful |
| 🟡 **P3 — Medium** | Suspicious Activity | Repeated failed logins, rate-limit triggers, odd traffic |
| 🟢 **P4 — Low** | Policy Violation | Staff accessing unauthorized pages, weak password detected |

---

## 3. Response Procedures

### PHASE 1 — Detect & Identify (0–15 min)
- [ ] Check **Security Logs** (`admin_logs.php` → Security Audit tab)
- [ ] Look for: `FAILED_LOGIN`, `MFA_LOCKOUT`, `CSRF_MISMATCH`, `UNAUTHORIZED_API_ACCESS`
- [ ] Check PHP error log: `backend/logs/app_errors.log`
- [ ] Identify affected IP address, username, and time window

### PHASE 2 — Contain (15–30 min)
- [ ] **P1/P2:** Immediately block the attacker IP in `.htaccess`:
  ```apache
  Deny from 203.0.113.45
  ```
- [ ] **P1:** Take the site offline (rename `.htaccess` to force 403 on all routes)
- [ ] **P1:** Revoke all active admin sessions by rotating `session.name` in PHP config or deleting session files
- [ ] Reset the compromised admin password immediately via `admin_staff.php`
- [ ] If DB is compromised: change DB credentials in `backend/config/db.php`

### PHASE 3 — Eradicate (30–120 min)
- [ ] Identify the root cause from logs
- [ ] Patch the specific vulnerability found
- [ ] If malware/shell uploaded: scan `frontend/uploads/` and `frontend/assets/` for `.php` files
  ```
  find frontend/ -name "*.php" -newer backend/config/db.php
  ```
- [ ] Remove any unauthorized files
- [ ] Run a full Wapiti re-scan: `php backend/scripts/wapiti_scan.sh`

### PHASE 4 — Recover (2–4 hrs)
- [ ] Restore from last known-good backup:
  ```
  php backend/scripts/db_restore_test.php
  ```
- [ ] Bring site back online gradually (maintenance mode first)
- [ ] Force all admins to reset their passwords on next login
- [ ] Monitor logs closely for 24 hours post-incident

### PHASE 5 — Post-Incident Review (within 48 hrs)
- [ ] Document a timeline of events
- [ ] Identify how the attacker got in
- [ ] Update this plan and/or the codebase to prevent recurrence
- [ ] Notify affected guests if personal data was accessed (legal obligation)

---

## 4. Key Contacts

| Role | Name | Contact |
|:---|:---|:---|
| System Owner | Justine Batuhan | Justinebatuhan017@gmail.com |
| Hosting Support | XAMPP / Hosting Provider | Support portal |

---

## 5. Quick Reference — Useful Commands

```bash
# Take a fresh backup right now
php backend/scripts/db_backup.php

# Test that the latest backup is restorable
php backend/scripts/db_restore_test.php

# Check for rogue PHP files in uploads folder
dir frontend\uploads\*.php /s /b

# Block an IP in .htaccess
echo "Deny from [ATTACKER_IP]" >> .htaccess
```
