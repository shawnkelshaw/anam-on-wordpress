# Uninstall Functionality

## ✅ Implemented

I've created an `uninstall.php` file that handles complete data removal when the plugin is deleted.

---

## How It Works

### Deactivate vs Delete

**Deactivate Plugin:**
- Plugin stops running
- **All data is preserved** (settings, conversations, etc.)
- User can reactivate and everything is still there

**Delete Plugin:**
1. WordPress shows built-in confirmation dialog: "Are you sure you want to delete this plugin?"
2. If user confirms, WordPress runs `uninstall.php`
3. All plugin data is permanently removed:
   - `anam_options` (API keys, avatar IDs, all settings)
   - `anam_api_verified` (verification status)
   - `anam_api_verified_at` (verification timestamp)
   - `wp_anam_conversations` table (all conversation data)
   - All transients/cached data

4. If plugin is reinstalled later, it's like starting fresh (first-use state)

---

## What Gets Deleted

### Database Options:
```php
delete_option('anam_options');           // All settings
delete_option('anam_api_verified');      // Verification status
delete_option('anam_api_verified_at');   // Verification time
```

### Database Tables:
```sql
DROP TABLE IF EXISTS wp_anam_conversations;
```

### Cached Data:
```php
delete_transient('anam_api_status');
delete_transient('anam_session_token');
```

---

## Reset All Button (Different Purpose)

The **Reset All** button in settings:
- Clears form fields
- Returns UI to first-use state
- **Does NOT delete from database**
- User can re-enter data and save

---

## Testing

### To Test Uninstall:

1. Go to **Plugins** page in WordPress admin
2. Find "Anam on WordPress - Admin Settings"
3. Click **Deactivate** (data preserved)
4. Click **Delete** 
5. WordPress shows confirmation: "Are you sure?"
6. Click **Yes, delete these files**
7. All data removed
8. If you reinstall, it's fresh start

### To Verify Data Was Deleted:

Check database after deletion:
```sql
-- Should return NULL
SELECT * FROM wp_options WHERE option_name LIKE 'anam%';

-- Should return error (table doesn't exist)
SELECT * FROM wp_anam_conversations;
```

---

## File Location

```
/anam-avatar-integration/
  ├── anam-admin-settings.php  (main plugin file)
  ├── uninstall.php             (runs on delete)
  └── ...
```

WordPress automatically looks for `uninstall.php` in the same directory as the main plugin file.

---

## Summary

✅ **Deactivate** = Turn off, keep data  
✅ **Delete** = Remove everything, fresh start if reinstalled  
✅ **Reset All button** = Clear form, don't delete from DB  

**The uninstall hook is now properly configured!** 🎉
