# e-Barangay ni Kap - Troubleshooting Guide

## Login Issues

### Problem: "Invalid username or password" Error

**Root Cause:** The password hashes in the database don't match the actual passwords.

**Solution:**

#### Option 1: Fresh Database Installation
1. Drop the existing database:
   ```sql
   DROP DATABASE IF EXISTS ebarangay_ni_kap;
   ```

2. Run the corrected schema:
   ```bash
   mysql -u root -p < database/ebarangay_complete_schema.sql
   ```

3. Test login with:
   - **admin@ebarangay.com** / **admin123**
   - **staff@ebarangay.com** / **staff123**
   - **purok@ebarangay.com** / **purok123**
   - **resident@ebarangay.com** / **resident123**

#### Option 2: Update Existing Passwords
If you already have data in the database:

1. Run the password reset script:
   ```bash
   php database/reset_passwords.php
   ```

2. This will update all user passwords with correct hashes.

#### Option 3: Manual Password Update
```sql
UPDATE users SET password = '$2y$12$6ufNFwNLsacC/kLdvcNgI.EVFpyr.2tT/8oB2B5xdYsWSMbbkxRte' WHERE email = 'admin@ebarangay.com';
UPDATE users SET password = '$2y$12$YgNRdZrDFPMATQ1szEn.7eKUyRxgMksk5fsV.K1O7nbt6VUOzcU5.' WHERE email = 'staff@ebarangay.com';
UPDATE users SET password = '$2y$12$tI33tvDRLNIbVj6bVhZgvOlVUypy65dzvmp9EjZ2wdlBo9wjc1rYW' WHERE email = 'purok@ebarangay.com';
UPDATE users SET password = '$2y$12$HyRp.S7QeB/hUODyVgv0N.iS1jIhD60FLcwAuAjkb28KXKglNumt2' WHERE email = 'resident@ebarangay.com';
```

### Problem: Database Connection Error

**Symptoms:**
- "Database connection failed" error
- "Table doesn't exist" errors

**Solutions:**

1. **Check Database Configuration:**
   ```php
   // Verify in includes/config/database.php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'ebarangay_ni_kap');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

2. **Verify Database Exists:**
   ```sql
   SHOW DATABASES;
   USE ebarangay_ni_kap;
   SHOW TABLES;
   ```

3. **Check MySQL Service:**
   - Ensure MySQL is running in XAMPP
   - Check port 3306 is not blocked

### Problem: Session Issues

**Symptoms:**
- Users get logged out frequently
- Session timeout errors

**Solutions:**

1. **Check Session Configuration:**
   ```php
   // In includes/config/constants.php
   define('SESSION_TIMEOUT', 3600); // 1 hour
   ```

2. **Verify Session Directory:**
   - Ensure PHP has write permissions to session directory
   - Check session.save_path in php.ini

3. **Clear Browser Cookies:**
   - Clear all cookies for the domain
   - Try incognito/private browsing mode

### Problem: CSRF Token Errors

**Symptoms:**
- "Invalid request" errors on form submission

**Solutions:**

1. **Check CSRF Token Generation:**
   ```php
   // Verify token is being generated
   $csrfToken = $auth->generateCSRFToken();
   ```

2. **Ensure Token is in Form:**
   ```html
   <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
   ```

## Database Verification

### Check Database Integrity

1. **Verify All Tables Exist:**
   ```sql
   USE ebarangay_ni_kap;
   SHOW TABLES;
   ```

   Expected tables:
   - roles, users, puroks
   - family_records, residents, census_data
   - certificate_requests, certificate_templates, generated_certificates, request_documents
   - blotter_reports, health_records, immunization_records
   - announcements, events, feedback
   - notifications, activity_logs

2. **Check User Accounts:**
   ```sql
   SELECT username, email, role_id, is_active FROM users;
   ```

3. **Verify Password Hashes:**
   ```sql
   SELECT username, email, LEFT(password, 7) as hash_start FROM users;
   ```
   All hashes should start with `$2y$12$`

### Test Login Functionality

1. **Test Password Verification:**
   ```php
   <?php
   require_once 'includes/config/database.php';
   require_once 'includes/classes/Auth.php';
   
   $auth = new Auth();
   $password = 'admin123';
   $hash = '$2y$12$6ufNFwNLsacC/kLdvcNgI.EVFpyr.2tT/8oB2B5xdYsWSMbbkxRte';
   
   echo "Password verification: " . ($auth->verifyPassword($password, $hash) ? 'SUCCESS' : 'FAILED');
   ?>
   ```

2. **Test Database Query:**
   ```php
   <?php
   require_once 'includes/config/database.php';
   
   $db = getDB();
   $user = $db->fetchOne(
       "SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email = ? AND u.is_active = 1",
       ['admin@ebarangay.com']
   );
   
   var_dump($user);
   ?>
   ```

## Common Error Messages and Solutions

### "Account is temporarily locked"
- **Cause:** Too many failed login attempts
- **Solution:** Wait 15 minutes or clear activity_logs table

### "Session expired"
- **Cause:** Session timeout reached
- **Solution:** Login again, session timeout is 1 hour

### "Access denied"
- **Cause:** User doesn't have required permissions
- **Solution:** Check user role and permissions in database

### "Database query failed"
- **Cause:** SQL syntax error or connection issue
- **Solution:** Check database connection and table structure

## Performance Issues

### Slow Login
1. **Check Database Indexes:**
   ```sql
   SHOW INDEX FROM users;
   ```

2. **Verify Query Performance:**
   ```sql
   EXPLAIN SELECT u.*, r.name as role_name 
   FROM users u 
   JOIN roles r ON u.role_id = r.id 
   WHERE u.email = 'admin@ebarangay.com' AND u.is_active = 1;
   ```

### Memory Issues
1. **Check PHP Memory Limit:**
   ```php
   echo ini_get('memory_limit');
   ```

2. **Optimize Session Storage:**
   - Use database sessions for better performance
   - Implement session garbage collection

## Security Issues

### Password Security
1. **Verify Password Hashing:**
   - All passwords should use bcrypt with cost 12
   - Never store plain text passwords

2. **Check for SQL Injection:**
   - All queries should use prepared statements
   - Input should be properly sanitized

### Session Security
1. **Verify Session Configuration:**
   ```php
   ini_set('session.cookie_httponly', 1);
   ini_set('session.use_strict_mode', 1);
   ```

2. **Check CSRF Protection:**
   - All forms should include CSRF tokens
   - Tokens should be validated on submission

## Debugging Tools

### Enable Debug Mode
```php
// In includes/config/constants.php
define('DEBUG_MODE', true);
```

### Check Error Logs
1. **PHP Error Log:**
   - Check XAMPP error logs
   - Enable error reporting in development

2. **Database Logs:**
   ```sql
   SHOW VARIABLES LIKE 'log_error';
   ```

### Database Connection Test
```php
<?php
require_once 'includes/config/database.php';

try {
    $db = getDB();
    $result = $db->fetchOne("SELECT 1 as test");
    echo "Database connection: SUCCESS\n";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "\n";
}
?>
```

## Quick Fix Checklist

If login is not working:

1. ✅ **Database exists and is accessible**
2. ✅ **All tables are created**
3. ✅ **User accounts exist with correct emails**
4. ✅ **Password hashes are correct (bcrypt)**
5. ✅ **User accounts are active (is_active = 1)**
6. ✅ **Roles are properly assigned**
7. ✅ **Database connection settings are correct**
8. ✅ **PHP sessions are working**
9. ✅ **CSRF tokens are being generated**
10. ✅ **No firewall/antivirus blocking connections**

## Support

If you continue to experience issues:

1. **Check the error logs** for specific error messages
2. **Verify your XAMPP installation** is working correctly
3. **Test with a simple PHP script** to isolate the issue
4. **Check browser console** for JavaScript errors
5. **Verify file permissions** on the project directory

---

**Last Updated:** July 21, 2025  
**Version:** 1.0 