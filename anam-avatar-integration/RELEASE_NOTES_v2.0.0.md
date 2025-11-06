# Release Notes - v2.0.0

**Release Date:** January 15, 2025  
**Repository:** https://github.com/shawnkelshaw/anam-on-wordpress  
**Commit:** 9531add

---

## 🎉 What's New

This major release transforms the Anam WordPress plugin into a complete conversation management platform with enterprise-grade transcript capture and AI-powered analysis.

### Key Features

#### 1. Real-Time Transcript Capture
- Automatic recording of all avatar conversations
- Live display on frontend with color-coded messages
- Saves to WordPress database when session closes
- Zero data loss with automatic backup

#### 2. Admin Dashboard
- **Chat Transcripts** page with full session management
- Paginated list view (10 sessions per page)
- Three-tab modal viewer:
  - **Transcript**: Formatted conversation (default view)
  - **Session JSON**: Full metadata from Anam API
  - **Transcript JSON**: Raw database data

#### 3. AI Parsing Integration
- One-click "Parse Chat" button
- Sends structured JSON to Supabase parser endpoint
- Visual parse status indicators
- Prevents duplicate processing
- Includes session metadata and user profile

#### 4. Database Management
- Auto-creates `wp_anam_transcripts` table
- 8-column schema with full audit trail
- JSON data cleaning prevents corruption
- Prepared statements for security

---

## 📊 Technical Specifications

### Database Schema
```
wp_anam_transcripts:
- id (bigint, auto-increment)
- session_id (varchar 255, unique)
- transcript_data (longtext, JSON)
- message_count (int)
- parsed (tinyint, 0/1 flag)
- parsed_at (datetime)
- created_at (datetime)
- updated_at (datetime)
```

### AJAX Endpoints
- `anam_get_session_token` - Generate secure tokens
- `anam_list_sessions` - Fetch session list from Anam API
- `anam_get_session_details` - Retrieve transcript from DB
- `anam_get_session_metadata` - Fetch session JSON from API
- `anam_save_transcript` - Store conversation in DB
- `anam_parse_transcript` - Send to parser endpoint

### Parser Payload Structure
```json
{
  "session_id": "uuid",
  "transcript": [{"type": "user|avatar", "text": "...", "timestamp": "..."}],
  "session_metadata": {"personaId": "...", "createdAt": "...", ...},
  "user_profile": {"first_name": "...", "last_name": "...", "phone": "..."},
  "timestamp": "ISO 8601"
}
```

---

## 🔒 Security Enhancements

- ✅ Nonce verification on all AJAX requests
- ✅ Input sanitization before database storage
- ✅ Prepared statements prevent SQL injection
- ✅ Server-side API key storage (never exposed)
- ✅ Clean uninstall removes all data

---

## 📝 Files Changed

### Modified
- `anam-admin-settings.php` - Added transcript capture, database management, parser integration
- `README.md` - Complete documentation rewrite with current features
- `CHANGELOG.md` - Comprehensive v2.0.0 release notes

### Added
- `SESSION_TRANSCRIPT_ISSUE.md` - Technical documentation
- `TROUBLESHOOTING.md` - Common issues and solutions

---

## 🚀 Upgrade Instructions

### From v1.x to v2.0.0

1. **Backup your database** before upgrading
2. **Deactivate** the plugin in WordPress
3. **Replace** plugin files with v2.0.0
4. **Reactivate** the plugin
5. Database table will auto-create on activation
6. **Configure** parser endpoint in Settings → Database Integration
7. **Test** by having a conversation and viewing in Chat Transcripts

### New Installations

1. Upload plugin to `/wp-content/plugins/anam-avatar-integration/`
2. Activate via WordPress admin
3. Go to Settings → Anam Avatar
4. Configure API key and avatar settings
5. Add parser endpoint URL
6. Test on frontend

---

## 📖 Documentation

- **README.md** - Complete feature documentation
- **CHANGELOG.md** - Detailed version history
- **TROUBLESHOOTING.md** - Common issues and solutions
- **SESSION_TRANSCRIPT_ISSUE.md** - Technical deep-dive

---

## 🔗 Links

- **GitHub Repository:** https://github.com/shawnkelshaw/anam-on-wordpress
- **Anam.ai API Docs:** https://docs.anam.ai
- **WordPress Plugin Directory:** (pending submission)

---

## 🙏 Acknowledgments

This release represents extensive development and testing to create a production-ready WordPress integration for Anam.ai avatars with enterprise-grade conversation management.

**Key Achievements:**
- Zero data loss with automatic transcript capture
- Clean, maintainable codebase
- Comprehensive security measures
- Full WordPress best practices compliance
- Extensive documentation

---

## 📞 Support

- **Issues:** https://github.com/shawnkelshaw/anam-on-wordpress/issues
- **Anam.ai Support:** https://anam.ai/support
- **WordPress Forums:** https://wordpress.org/support

---

**Status:** ✅ Successfully uploaded to GitHub  
**Branch:** main  
**Commit:** 9531add
