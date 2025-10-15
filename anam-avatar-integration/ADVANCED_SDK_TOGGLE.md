# Advanced SDK Toggle Feature

## ✅ Implemented

Added a checkbox on the Getting Started page to toggle advanced Anam SDK functionality.

---

## What Was Done

### 1. **Getting Started Page Updates**

**Removed:**
- Documentation block (bottom section)

**Added:**
- Checkbox: "Turn on advanced Anam SDK functionality"
- Default state: **Unchecked**

### 2. **Conditional Logic**

**When Checkbox is CHECKED:**
- ✅ "View Session Transcripts" button is **visible**
- ✅ "Session Transcripts" menu item appears in Anam Avatar menu

**When Checkbox is UNCHECKED:**
- ❌ "View Session Transcripts" button is **hidden**
- ❌ "Session Transcripts" menu item is **removed** from menu

---

## Technical Implementation

### PHP Changes (`anam-admin-settings.php`):

1. **Getting Started Page:**
   - Added checkbox with ID `anam-advanced-sdk-toggle`
   - Button visibility controlled by PHP based on saved state
   - Removed Documentation block

2. **Menu Registration:**
   - Session Transcripts menu item now conditional
   - Only registers if `advanced_sdk_enabled` is true

3. **AJAX Handler:**
   - New function: `handle_toggle_advanced_sdk()`
   - Saves checkbox state to `anam_options['advanced_sdk_enabled']`

4. **Sanitization:**
   - Added `advanced_sdk_enabled` to `sanitize_settings()`

### JavaScript (`anam-getting-started.js`):

1. **Checkbox Event Handler:**
   - Listens for checkbox change
   - Immediately shows/hides button
   - Sends AJAX request to save state
   - Reloads page to update menu

2. **AJAX Call:**
   - Action: `anam_toggle_advanced_sdk`
   - Nonce: `anam_admin_nonce`
   - Data: `enabled` (true/false)

---

## Files Modified

### PHP:
- `anam-admin-settings.php`
  - Updated `getting_started_page()` function
  - Updated `add_admin_menu()` function (conditional menu)
  - Added `handle_toggle_advanced_sdk()` AJAX handler
  - Updated `sanitize_settings()` function
  - Updated `enqueue_admin_scripts()` function

### JavaScript:
- `anam-getting-started.js` (NEW FILE)
  - Handles checkbox toggle
  - AJAX save functionality
  - Page reload after save

---

## Database

**Option:** `anam_options['advanced_sdk_enabled']`
- Type: Boolean
- Default: `false`
- Saved via: AJAX handler

---

## User Flow

1. **User goes to Getting Started page**
2. **Sees checkbox (unchecked by default)**
3. **Checks the checkbox**
4. **Button appears immediately**
5. **AJAX saves setting to database**
6. **Page reloads**
7. **Session Transcripts menu item now visible**

**To disable:**
1. **Uncheck the checkbox**
2. **Button hides immediately**
3. **AJAX saves setting**
4. **Page reloads**
5. **Session Transcripts menu item removed**

---

## Testing Checklist

### Initial State (Fresh Install):
- [ ] Checkbox is unchecked
- [ ] "View Session Transcripts" button is hidden
- [ ] "Session Transcripts" NOT in menu

### Enable Advanced SDK:
- [ ] Check the checkbox
- [ ] Button appears immediately
- [ ] Page reloads automatically
- [ ] "Session Transcripts" appears in menu
- [ ] Checkbox remains checked after reload

### Disable Advanced SDK:
- [ ] Uncheck the checkbox
- [ ] Button hides immediately
- [ ] Page reloads automatically
- [ ] "Session Transcripts" removed from menu
- [ ] Checkbox remains unchecked after reload

### Persistence:
- [ ] Setting persists across page reloads
- [ ] Setting persists across browser sessions
- [ ] Setting persists after plugin deactivation/reactivation

---

## Result

✅ **Documentation block removed**  
✅ **Checkbox added with proper label**  
✅ **Button visibility controlled**  
✅ **Menu item conditionally registered**  
✅ **State saved to database**  
✅ **Page reloads to update menu**  

**The advanced SDK toggle is fully functional!** 🎉
