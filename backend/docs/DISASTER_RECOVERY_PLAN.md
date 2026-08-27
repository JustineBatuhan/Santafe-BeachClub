# 🔄 Disaster Recovery Plan
## Santa Fe Beach Club Booking System

**Version:** 1.0  
**Last Updated:** 2026-08-25  
**RTO (Recovery Time Objective):** 2 hours  
**RPO (Recovery Point Objective):** 24 hours (daily backups)

---

## 1. Disaster Scenarios Covered

| Scenario | Likelihood | Impact |
|:---|:---:|:---:|
| Server hard drive failure | Low | Critical |
| XAMPP / PHP corruption | Medium | High |
| MySQL database corruption | Medium | Critical |
| Accidental data deletion | Medium | High |
| Ransomware / full compromise | Low | Critical |
| Hosting provider outage | Low | High |

---

## 2. What Is Backed Up

| Asset | Location | Backup Method | Frequency |
|:---|:---|:---|:---|
| **Full Database** | `backend/logs/backups/` | `db_backup.php` (mysqldump) | Manual / Scheduled |
| **Uploaded Receipts** | `frontend/uploads/receipts/` | Manual copy to external drive | Weekly |
| **Gallery Images** | `frontend/assets/gallery/` | Manual copy to external drive | Weekly |
| **Source Code** | Git repository (recommended) | `git push` | On every change |

---

## 3. Recovery Procedures by Scenario

---

### 🔴 Scenario A: Database Corruption or Accidental Deletion

**Estimated Recovery Time: 15–30 minutes**

1. **Identify the most recent clean backup:**
   ```
   dir backend\logs\backups\ /O-D
   ```

2. **Run the restoration test to verify the backup:**
   ```
   php backend/scripts/db_restore_test.php
   ```

3. **Drop the corrupted database and restore:**
   ```
   C:\xampp\mysql\bin\mysql.exe -u root -P 3307 -e "DROP DATABASE santafe_beach_club;"
   C:\xampp\mysql\bin\mysql.exe -u root -P 3307 -e "CREATE DATABASE santafe_beach_club CHARACTER SET utf8mb4;"
   C:\xampp\mysql\bin\mysql.exe -u root -P 3307 santafe_beach_club < backend\logs\backups\[FILENAME].sql
   ```

4. **Verify the site loads correctly** by visiting `http://localhost/SantaBeachClub-BookingSystem/`

---

### 🔴 Scenario B: Full Server / PC Failure (New Machine Setup)

**Estimated Recovery Time: 1–2 hours**

1. **Install XAMPP** on the new machine (same PHP/MySQL versions recommended: PHP 8.2, MariaDB 10.4).

2. **Restore source code** from backup drive or Git repository:
   ```
   git clone [your-repo-url] C:\xampp\htdocs\SantaBeachClub-BookingSystem
   ```

3. **Start Apache and MySQL** in XAMPP Control Panel.

4. **Create the database and restore:**
   ```
   C:\xampp\mysql\bin\mysql.exe -u root -P 3306 -e "CREATE DATABASE santafe_beach_club CHARACTER SET utf8mb4;"
   C:\xampp\mysql\bin\mysql.exe -u root -P 3306 santafe_beach_club < [backup_file].sql
   ```
   > **Note:** Default XAMPP MySQL port is 3306. Update `backend/config/db.php` if your port differs.

5. **Restore uploaded files** (receipts and gallery) from external drive to:
   - `frontend/uploads/receipts/`
   - `frontend/assets/gallery/`

6. **Run the restoration test** to confirm everything is working:
   ```
   php backend/scripts/db_restore_test.php
   ```

---

### 🟠 Scenario C: Hosting Provider Outage (Online Deployment)

**Estimated Recovery Time: 30–60 minutes**

1. **Contact hosting support** and get an ETA.
2. If outage exceeds 2 hours, **activate a maintenance page** on an alternate domain or sub-domain.
3. Once service resumes, verify database integrity by running `db_restore_test.php` remotely via SSH.
4. Monitor `backend/logs/app_errors.log` for any anomalies post-restoration.

---

## 4. Backup Schedule (Recommended)

| Backup Type | Frequency | Retention | Where to Store |
|:---|:---|:---|:---|
| **Daily DB Backup** | Daily (manual or scheduled) | 30 days | `backend/logs/backups/` |
| **Weekly Offsite Copy** | Every Sunday | 3 months | USB Drive or Google Drive |
| **Pre-Deployment Backup** | Before any major code change | Indefinite | Labelled archive |

**To schedule a daily automated backup on Windows:**
1. Open **Task Scheduler** → Create Basic Task
2. Name: `SantaFe Daily DB Backup`
3. Trigger: Daily at `02:00 AM`
4. Action: Start a Program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\SantaBeachClub-BookingSystem\backend\scripts\db_backup.php`

---

## 5. Recovery Verification Checklist

After any recovery, confirm all of the following before reopening to guests:

- [ ] Site loads at expected URL without PHP errors
- [ ] Admin login works (with MFA)
- [ ] Guest booking lookup (`my_booking.php`) works
- [ ] Rooms and pricing display correctly
- [ ] Existing bookings are present and accurate
- [ ] Payment records are intact
- [ ] `db_restore_test.php` returns **PASS** for all tables
- [ ] `backend/logs/app_errors.log` is clean (no new errors)
- [ ] Security logs (`admin_logs.php`) show no suspicious post-recovery activity
