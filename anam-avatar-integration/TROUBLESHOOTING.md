# Troubleshooting Guide - Anam Avatar Plugin

## Common Issues & Solutions

### 1. Transcripts Not Displaying

**Symptom**: Sessions appear in list but clicking "View" shows "No Transcript Available"

**Possible Causes**:

#### A. Session ID Mismatch (MOST COMMON)
- **Problem**: Transcript saved with wrong session ID (e.g., "auth" instead of UUID)
- **Check**: Query database:
  ```sql
  SELECT session_id, message_count FROM sknew_anam_transcripts ORDER BY created_at DESC LIMIT 5;
  ```
- **Expected**: Session IDs should be UUIDs like `cc4d71b4-28b6-4d25-9743-b6f223ee32f2`
- **If you see "auth"**: Old bug, fixed Nov 5, 2025. New conversations will work correctly.

#### B. Transcript Not Saved
- **Check console logs** during conversation for:
  - `✅ POST-STREAM: Session ID captured: [UUID]`
  - `✅ Transcript saved successfully`
- **If missing**: Session ID capture failed, check browser console for errors

#### C. Database Table Missing
- **Check**: Does table `sknew_anam_transcripts` exist?
- **Fix**: Deactivate and reactivate plugin to trigger table creation

---

### 2. Sessions List Not Loading

**Symptom**: "Loading sessions..." spinner never stops

**Possible Causes**:

#### A. API Key Issues
- **Check**: Avatar Setup page has valid API Key and Auth Token
- **Test**: Look for 401 errors in browser Network tab
- **Fix**: Verify API credentials in Anam.ai dashboard

#### B. JavaScript Error
- **Check**: Browser console for errors
- **Common**: `ANAM_CONFIG is not defined`
- **Fix**: Clear browser cache, reload page

#### C. AJAX Endpoint Blocked
- **Check**: Network tab for failed `admin-ajax.php` requests
- **Fix**: Check WordPress permalinks, server configuration

---

### 3. Avatar Not Appearing on Frontend

**Symptom**: No avatar shows on website

**Possible Causes**:

#### A. Display Method Not Configured
- **Check**: Display Settings page
- **Options**: "By Element ID" or "By Page and Position"
- **Fix**: Configure display method and save

#### B. Container Element Missing (Element ID method)
- **Check**: Page HTML has element with ID matching Container ID setting
- **Default**: `<div id="anam-stream-container"></div>`
- **Fix**: Add container element to page

#### C. Page Not Selected (Page & Position method)
- **Check**: Display Settings → Page Selection
- **Fix**: Check boxes for pages where avatar should appear

---

### 4. Session ID Shows as "auth"

**Symptom**: Database has session_id = "auth" instead of UUID

**Status**: Fixed Nov 5, 2025

**What Happened**:
- Anam client wasn't fully initialized when session ID was captured
- Code was capturing string "auth" instead of actual session UUID

**The Fix**:
- Added 2-second delay after streaming starts
- Added validation to reject "auth" 
- Added multiple fallback locations to find UUID
- Only saves transcripts with valid UUIDs (length > 30 chars)

**For Old Data**:
- Old transcripts with "auth" can't be matched to sessions
- Will be cleared on next config change
- Or manually delete: `DELETE FROM sknew_anam_transcripts WHERE session_id = 'auth';`

---

### 5. Parse Chat Button Not Working

**Symptom**: Button doesn't respond or shows error

**Possible Causes**:

#### A. Parser Endpoint Not Configured
- **Check**: Database Integration page has Parser Endpoint URL
- **Fix**: Add endpoint URL and save

#### B. Transcript Not Saved
- **Check**: Database has transcript for that session
- **Fix**: Can't parse if transcript doesn't exist

#### C. Parser Endpoint Down
- **Check**: Network tab for 500/503 errors
- **Fix**: Verify parser endpoint is running

---

## Debug Checklist

When something isn't working, check these in order:

### 1. Browser Console
```
Look for:
- ✅ Anam Admin - Ready!
- ✅ POST-STREAM: Session ID captured: [UUID]
- ✅ Transcript saved successfully
- ❌ Any red error messages
```

### 2. Network Tab
```
Look for:
- admin-ajax.php requests (should be 200 OK)
- api.anam.ai requests (should be 200 OK)
- Any 401, 403, 500 errors
```

### 3. Database
```sql
-- Check if table exists
SHOW TABLES LIKE 'sknew_anam_transcripts';

-- Check recent transcripts
SELECT session_id, message_count, created_at 
FROM sknew_anam_transcripts 
ORDER BY created_at DESC 
LIMIT 5;

-- Check for "auth" session IDs (bad)
SELECT COUNT(*) FROM sknew_anam_transcripts WHERE session_id = 'auth';
```

### 4. WordPress Debug Log
```
Location: wp-content/debug.log

Look for:
- 🔄 Config changed messages
- 🗑️ Transcript clearing messages
- ✅ Transcript saved messages
- ❌ Any error messages
```

---

## Quick Fixes

### Clear All Transcripts
```sql
TRUNCATE TABLE sknew_anam_transcripts;
```

### Delete "auth" Session IDs Only
```sql
DELETE FROM sknew_anam_transcripts WHERE session_id = 'auth';
```

### Reset Plugin Completely
1. Go to Anam Avatar → Getting Started
2. Click "Reset Plugin" button
3. Reconfigure settings

---

## Getting Help

When reporting issues, provide:

1. **Browser console logs** (copy/paste)
2. **Network tab screenshot** (showing failed requests)
3. **Database query results**:
   ```sql
   SELECT session_id, message_count, created_at 
   FROM sknew_anam_transcripts 
   ORDER BY created_at DESC 
   LIMIT 3;
   ```
4. **WordPress debug log** (last 50 lines)
5. **What you were trying to do** when the issue occurred

---

## Known Limitations

1. **Anam Token Consumption**: Each conversation uses tokens. Debugging can consume many tokens.
2. **Session ID Capture Timing**: Requires 2-second delay after streaming starts. Very short conversations might not save.
3. **Old Anam Sessions**: Sessions created before WordPress plugin was installed won't have transcripts.
4. **Database Prefix**: Uses WordPress database prefix (e.g., `sknew_` not `wp_`). Queries must use correct prefix.

---

**Last Updated**: Nov 5, 2025
