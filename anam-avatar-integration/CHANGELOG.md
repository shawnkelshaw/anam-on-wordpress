# Changelog

All notable changes to the Anam.ai WordPress Integration plugin will be documented in this file.

## [2.0.0] - 2025-01-15

### 🎉 Major Release - Complete Transcript Management System

This release transforms the plugin into a comprehensive conversation management platform with real-time capture, database storage, and AI-powered analysis.

### Added

#### Transcript Capture System
- **Real-time transcript capture** during avatar conversations
- **Automatic database storage** when avatar session closes
- **Live transcript display** on frontend (color-coded messages)
- **Message history tracking** via Anam SDK events

#### Admin Dashboard - Chat Transcripts Page
- **Session list view** with pagination (10 per page)
- **Three-tab modal system** for each session:
  - Transcript tab: Formatted conversation display (default active)
  - Session JSON tab: Full metadata from Anam API (lazy-loaded)
  - Transcript JSON tab: Raw database transcript data
- **Parse Chat button** for one-click AI analysis
- **Parse status tracking** with visual indicators and timestamps
- **Color-coded messages**: Blue for user (👤), green for avatar (🤖)

#### Database Integration
- **Auto-created `wp_anam_transcripts` table** on plugin activation
- **Complete schema** with 8 columns: id, session_id, transcript_data, message_count, parsed, parsed_at, created_at, updated_at
- **JSON data cleaning** to prevent corruption from URL encoding
- **Prepared statements** for SQL injection prevention

#### API Integration
- **Anam API session fetching** via `GET /v1/sessions`
- **Session metadata retrieval** via `GET /v1/sessions/{id}`
- **Lazy-loading** to reduce unnecessary API calls
- **Bearer token authentication** with base64-encoded API key

#### Parser Integration
- **Supabase parser endpoint** configuration
- **Structured JSON payload** with transcript, session metadata, and user profile
- **One-click parsing** from admin interface
- **Duplicate prevention** with parse status flags
- **Error handling** with meaningful user feedback

#### AJAX Handlers
- `anam_get_session_token` - Generate secure session tokens
- `anam_list_sessions` - Fetch paginated session list from Anam API
- `anam_get_session_details` - Retrieve transcript from database
- `anam_get_session_metadata` - Fetch session JSON from Anam API
- `anam_save_transcript` - Store conversation data in database
- `anam_parse_transcript` - Send transcript to parser endpoint

### Changed
- **Admin interface** expanded to 4 pages (Getting Started, Chat Transcripts, Display Settings, Database Integration)
- **Display method** simplified to Element ID and Page Position (removed non-functional shortcode)
- **Settings structure** reorganized for better UX
- **JavaScript architecture** improved with modular functions
- **Error handling** enhanced throughout plugin

### Fixed
- **Transcript data corruption** from URL encoding (decode → validate → re-encode)
- **Empty error messages** in modal (conditional display)
- **Tab switching** white background styling consistency
- **Session metadata** lazy-loading to prevent unnecessary API calls
- **Parse button state** properly reflects parsed status

### Security
- ✅ **Nonce verification** on all AJAX requests
- ✅ **Input sanitization** before database storage
- ✅ **Prepared statements** for database queries
- ✅ **Server-side API key storage** (never exposed to frontend)
- ✅ **Clean uninstall** removes all data via uninstall.php

### Technical Details
- **Database table**: `wp_anam_transcripts` with 8 columns
- **JavaScript events**: `AnamEvent.MESSAGE_HISTORY_UPDATED` for real-time capture
- **Data format**: JSON arrays with type, text, timestamp for each message
- **Parser payload**: Includes transcript, session_metadata, user_profile, timestamp
- **API endpoints**: Anam API v1 for sessions and metadata

### Documentation
- ✅ Updated README.md with complete feature documentation
- ✅ Database schema and JSON structure examples
- ✅ Parser integration guide with payload examples
- ✅ Troubleshooting section for common issues
- ✅ Security measures documentation

---

## [1.0.0] - 2025-01-12

### Added
- Initial release of Anam.ai WordPress integration plugin
- Multiple implementation approaches (ESM, UMD, iframe)
- Server-side session token generation
- Custom container placement support
- Comprehensive debugging tools
- API key validation utility
- CDN connectivity testing
- Shared hosting compatibility solutions

### Features
- **anam-on-wordpress.php** - Production-ready ESM implementation
- **anam-api-tester.php** - API key validation tool
- **anam-cdn-diagnostics.php** - CDN accessibility diagnostics
- **anam-official-format.php** - Official documentation format
- **anam-clean-version.php** - Minimal implementation
- Custom div container support (`anam-stream-container`)
- Fixed positioning fallback
- Secure server-side API key storage
- WordPress AJAX with nonce verification

### Technical Achievements
- Resolved CDN loading issues on shared hosting
- Implemented multiple SDK loading strategies
- Created comprehensive error handling and logging
- Developed hosting compatibility solutions
- Established secure authentication flow

### Compatibility
- WordPress 5.0+
- PHP 7.4+
- Modern browsers with WebRTC support
- HTTPS required
- Tested on shared hosting environments

### Known Issues
- Some shared hosting providers block external CDNs
- Anam.ai free tier has usage limitations
- WebRTC requires HTTPS and microphone permissions

### Documentation
- Comprehensive README with troubleshooting guide
- Inline code documentation
- Multiple implementation examples
- Hosting compatibility notes
