# Session & Transcript Issue - Status Summary

## 🚨 CRITICAL ISSUE IDENTIFIED (Nov 5, 2025)

**Root Cause:** Session ID being saved as `"auth"` instead of actual UUID

### The Problem:
- Anam API returns sessions with UUID like: `cc4d71b4-28b6-4d25-9743-b6f223ee32f2`
- WordPress saves transcripts with session_id: `"auth"` (incorrect)
- When clicking "View", query looks for UUID but finds nothing
- Result: Transcripts exist in database but can't be matched to sessions

### Database Evidence:
```sql
SELECT * FROM sknew_anam_transcripts;
-- Shows: session_id = "auth", message_count = 7, transcript_data = [actual conversation]
```

### The Fix (Applied Nov 5, 2025):
1. Added session ID validation to reject "auth" 
2. Added delayed capture (2 seconds after streaming starts)
3. Added multiple fallback locations to find real UUID
4. Only saves transcripts with valid UUIDs (length > 30 chars)

**Status:** Fix deployed, awaiting testing with new conversation

---

## The Original Architecture Issue

**Two separate data sources are causing confusion:**

1. **Anam API Sessions** (Cloud) - Stores ALL sessions ever created with your API key
2. **WordPress Transcripts** (Local) - Stores only transcripts saved from your WordPress site

## What's Currently Happening

### When You Change Config (API Key, Persona ID, etc.):
✅ **WordPress transcripts ARE cleared** from local database  
❌ **Anam API sessions are NOT cleared** - they still exist in Anam's cloud  
❌ **Sessions list shows old sessions** because it pulls from Anam API, not WordPress

### The Mismatch:
- **Chat Transcripts page** → Lists sessions from **Anam API** (cloud)
- **View button** → Tries to show transcript from **WordPress database** (local)
- **Result** → Sessions appear but transcripts are missing

## The Architecture Issue

```
┌─────────────────────────────────────────────────────────────┐
│ ANAM API (Cloud)                                            │
│ - Stores ALL sessions for your API key                     │
│ - Sessions from ALL personas with that key                 │
│ - Never automatically deleted                              │
│ - Filtered by: apiKeyId + personaId                        │
└─────────────────────────────────────────────────────────────┘
                              ↓
                    (API call to list sessions)
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ WORDPRESS (Local)                                           │
│ - wp_anam_transcripts table                                │
│ - Only stores transcripts YOU saved                        │
│ - Gets cleared when config changes                         │
└─────────────────────────────────────────────────────────────┘
```

## Solutions (Pick One Tomorrow)

### Option 1: Only Show Sessions With Local Transcripts ⭐ RECOMMENDED
- Modify `list_sessions()` to cross-reference Anam API sessions with WordPress database
- Only display sessions that have transcripts in `wp_anam_transcripts`
- When you clear transcripts → sessions list becomes empty
- New conversations → appear immediately

**Pros:**
- Clean slate when config changes
- No orphaned sessions
- Fast implementation

**Cons:**
- Hides old Anam sessions (but you can't see their transcripts anyway)

### Option 2: Show All Sessions, Mark Which Have Transcripts
- Display all Anam sessions
- Add "Has Transcript" column/indicator
- Sessions without local transcripts show "No Transcript Available"

**Pros:**
- See everything in Anam
- Know which sessions have data

**Cons:**
- Cluttered list with unusable sessions
- Confusing for users

### Option 3: Delete Old Sessions From Anam API
- When config changes, call Anam DELETE API for each session
- Actually remove old sessions from Anam's cloud
- Fresh start everywhere

**Pros:**
- True clean slate
- No data mismatch

**Cons:**
- Permanent deletion (can't undo)
- Requires DELETE API calls (may fail)
- Slower (one API call per session)

## Current Code Status

### What Works:
✅ All config values (API Key, Auth Token, Persona ID, Avatar ID, Voice ID, LLM ID) are dynamic  
✅ Frontend uses fresh config on every page load  
✅ Session token generation pulls fresh config  
✅ WordPress transcripts clear when config changes  
✅ Admin notice shows when transcripts are cleared  

### What Doesn't Work:
❌ Sessions list still shows old Anam API sessions  
❌ Old sessions have no transcripts (because local DB was cleared)  
❌ User sees sessions but can't view transcripts  

## Debug Logs To Check Tomorrow

Look in `wp-content/debug.log` for:

```
🔄 Config changed: [field] changed from '[old]' to '[new]'
🗑️ CONFIG CHANGED - Cleared all transcripts from database. Fresh start with new config!
=== LIST SESSIONS DEBUG ===
List Sessions - Persona ID: [value]
🎯 Creating session with personaId: [value]
```

## Next Steps (Tomorrow)

1. **Decide which solution you want** (I recommend Option 1)
2. **Test the current behavior:**
   - Change a config value
   - Check if transcripts are cleared (they should be)
   - Have a NEW conversation
   - See if it appears in sessions list
   - See if transcript displays

3. **If new sessions work but old ones clutter the list** → Implement Option 1

## Files Modified

### Nov 5, 2025 - Session ID Fix
- `anam-admin-settings.php`:
  - **Lines 1397-1426**: Added session ID validation in STREAM_STARTED event listener
  - **Lines 1477-1497**: Added POST-STREAM delayed session ID capture (2 second delay)
  - **Lines 1654-1684**: Added validation in closeElementIdAvatar() to reject "auth" session IDs
  - Validates session ID is UUID (length > 30 chars, not "auth")
  - Tries multiple fallback locations: `anamClient.sessionId`, `anamClient.session.id`, `anamClient._session.id`, etc.

### Earlier Changes
- `anam-admin-settings.php`:
  - Lines 738-760: Config change detection
  - Lines 848-857: Transcript clearing on config change
  - Lines 863-875: Admin notice display
  - Lines 2469-2499: Session listing with debug logs

## Code Architecture Issues Identified

### Current State:
- **File Size**: 3,187 lines (monolithic)
- **Mixed Concerns**: PHP backend + inline JavaScript
- **Maintenance**: Difficult to debug and extend

### Refactoring Plan Created:
See memory: "Refactoring Plan for anam-admin-settings.php"
- Phase 1: Extract JS to separate files
- Phase 2: Split PHP into logical classes
- Phase 3: Move HTML to templates
- **Status**: Deferred until transcript issue is resolved

## Testing Checklist (When Tokens Available)

1. ✅ Sessions list loads from Anam API
2. ✅ Session ID captured correctly (verified in console)
3. ⏳ **NEEDS TESTING**: New conversation saves with UUID (not "auth")
4. ⏳ **NEEDS TESTING**: View button displays transcript correctly
5. ⏳ **NEEDS TESTING**: Database query matches session ID

## Database Schema

**Table**: `sknew_anam_transcripts` (note: custom prefix, not `wp_`)

```sql
CREATE TABLE sknew_anam_transcripts (
  id bigint(20) AUTO_INCREMENT PRIMARY KEY,
  session_id varchar(255) NOT NULL,
  transcript_data longtext NOT NULL,
  message_count int(11) NOT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  parsed tinyint(1) DEFAULT 0,
  parsed_at datetime DEFAULT NULL
);
```

## Known Issues

1. **Old transcripts with "auth" session ID**: Exist in database but can't be matched to sessions
   - **Solution**: Will be prevented by new validation
   - **Cleanup**: Can manually delete or wait for config change to clear

2. **Anam token consumption during debugging**: Used up tokens testing
   - **Impact**: Can't test fix until tokens replenish
   - **Mitigation**: Fix is in place, just needs verification

## Questions To Answer (Future)

1. Do you want to see old Anam sessions that have no transcripts?
2. Should changing config completely hide old sessions?
3. Do you ever need to access old session data from Anam?

---

**Status**: Fix deployed. Awaiting Anam token replenishment for testing.
