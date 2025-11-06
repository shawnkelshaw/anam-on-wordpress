# Anam.ai WordPress Integration

A comprehensive WordPress plugin for integrating Anam.ai digital avatars using the JavaScript SDK. This plugin provides a complete admin interface, real-time transcript capture, and AI-powered conversation analysis.

## 🚀 Features

### Core Integration
- **Admin Interface**: Full WordPress admin panel with settings management
- **Multiple Display Methods**: Fixed positioning or custom element ID placement
- **Secure Implementation**: Server-side session token generation
- **Live Transcript Display**: Real-time conversation monitoring on frontend

### Transcript Management
- **🆕 Automatic Capture**: Real-time conversation recording during avatar sessions
- **🆕 Database Storage**: Complete transcript history in WordPress database
- **🆕 Admin Dashboard**: View all sessions with formatted transcripts
- **🆕 Session Metadata**: Full session details from Anam API
- **🆕 AI Parsing**: One-click conversation analysis with Supabase integration
- **🆕 Parse Status Tracking**: Visual indicators for processed transcripts

## 📦 Installation

1. Download or clone this repository
2. Upload to your WordPress `/wp-content/plugins/anam-avatar-integration/` directory
3. Activate the desired plugin version through the 'Plugins' menu
4. Configure your API key and persona settings

## 🔧 Plugin Files

### Main Plugin (Production Ready)
- **`anam-admin-settings.php`** - ✅ **Primary Plugin** - Complete WordPress integration with:
  - Full admin interface with 4 settings pages
  - Real-time transcript capture and storage
  - Session management and viewing
  - AI parsing integration
  - Database table auto-creation

### JavaScript Files
- **`anam-admin.js`** - Admin interface functionality (sessions list, modal, tabs, AJAX)
- **`anam-getting-started.js`** - Getting Started page functionality

### Utilities
- **`uninstall.php`** - Clean database removal on plugin deletion

## ⚙️ Configuration

### Option 1: Admin Interface (Recommended)
1. **Activate** `anam-admin-settings.php` plugin
2. **Go to** WordPress Admin → Settings → Anam Avatar
3. **Configure** your API key, avatar settings, and display options
4. **Test** your configuration with the built-in test tool
5. **Save** settings and view your avatar on the frontend

## 🎛️ Admin Interface

The plugin adds a comprehensive admin interface under **Settings → Anam Avatar** with 4 pages:

### 1. Getting Started
- Quick setup guide
- API key configuration
- Basic avatar settings (Persona ID, Avatar ID, Voice ID, LLM ID)
- Display method selection (Element ID or Page Position)

### 2. Chat Transcripts
- View all conversation sessions from Anam API
- Paginated session list with timestamps
- Three-tab modal for each session:
  - **Transcript**: Formatted conversation display (color-coded)
  - **Session JSON**: Full session metadata from Anam API
  - **Transcript JSON**: Raw transcript data from database
- **Parse Chat** button: Send transcript to AI parser (one-click)
- Parse status tracking (shows when already parsed)

### 3. Display Settings
- Avatar positioning options
- Page selection for where avatar appears
- Custom container ID configuration
- Position presets (bottom-right, bottom-left, etc.)

### 4. Database Integration
- Parser endpoint URL configuration
- Connects to Supabase parser for AI analysis
- Sends structured JSON with transcript + metadata

## 🎯 Custom Container Placement

To place the avatar in a specific location, use:

```html
<div id="anam-stream-container"></div>
```

The plugin will automatically stream to this container instead of creating a fixed position element.

## 🔧 How It Works

### Frontend Flow
1. **Session Token**: WordPress generates secure token via AJAX (`anam_get_session_token`)
2. **SDK Initialization**: Anam JavaScript SDK loads with session token
3. **Avatar Display**: Streams to custom container or fixed position
4. **Transcript Capture**: Real-time message recording via `MESSAGE_HISTORY_UPDATED` event
5. **Auto-Save**: Transcript saved to database when avatar closes

### Backend Flow
1. **Database**: Auto-creates `wp_anam_transcripts` table on plugin activation
2. **Storage**: Saves transcript JSON with session_id, message_count, timestamps
3. **API Integration**: Fetches session metadata from Anam API on demand
4. **Parser Integration**: Sends structured JSON to Supabase parser endpoint
5. **Status Tracking**: Marks transcripts as parsed with timestamp

## 🛠️ Troubleshooting

### Common Issues

**Avatar not appearing:**
- Check browser console for errors
- Verify API key and Persona ID are correct
- Ensure HTTPS is enabled (required for WebRTC)
- Check display method settings match your page setup

**Transcripts not saving:**
- Check browser console for AJAX errors
- Verify database table exists: `wp_anam_transcripts`
- Check WordPress debug.log for PHP errors
- Ensure proper nonce verification

**Parse button not working:**
- Verify parser endpoint URL is configured
- Check that transcript data exists in database
- Review browser console for AJAX errors
- Confirm parser endpoint is accessible

### Debug Tools

1. **Browser Console**: Check for JavaScript errors and AJAX responses
2. **Network Tab**: Verify API calls to Anam and parser endpoint
3. **WordPress Debug**: Enable `WP_DEBUG` in wp-config.php
4. **Database**: Check `wp_anam_transcripts` table for saved data

For detailed troubleshooting, see `TROUBLESHOOTING.md`

## 🔒 Security

- ✅ **API Key Protection**: Stored server-side in WordPress options table
- ✅ **Nonce Verification**: All AJAX requests validated with WordPress nonces
- ✅ **Session Tokens**: Generated server-side, never exposed in frontend code
- ✅ **HTTPS Required**: All API communication over secure connections
- ✅ **Input Sanitization**: All user inputs sanitized before database storage
- ✅ **Database Security**: Prepared statements prevent SQL injection
- ✅ **Clean Uninstall**: Complete data removal via uninstall.php

## 📋 Requirements

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **HTTPS**: Required for WebRTC
- **Browser**: Modern browsers with WebRTC support
- **Anam.ai Account**: Valid API key and persona configuration

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open source. Please respect Anam.ai's terms of service when using their API.

## 🆘 Support

For issues related to:
- **Plugin functionality**: Open a GitHub issue
- **Anam.ai API**: Contact Anam.ai support
- **WordPress integration**: Check WordPress documentation

## 📊 Database Schema

### wp_anam_transcripts Table

Created automatically on plugin activation:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint(20) | Auto-increment primary key |
| `session_id` | varchar(255) | Unique session identifier from Anam API |
| `transcript_data` | longtext | JSON array of conversation messages |
| `message_count` | int(11) | Total number of messages in conversation |
| `parsed` | tinyint(1) | Flag indicating if transcript has been parsed (0/1) |
| `parsed_at` | datetime | Timestamp when parsing occurred |
| `created_at` | datetime | When transcript was first saved |
| `updated_at` | datetime | Last update timestamp |

### Transcript JSON Structure

```json
[
  {
    "type": "user",
    "text": "Hello, I need help with my vehicle",
    "timestamp": "2025-01-15T10:30:00.000Z"
  },
  {
    "type": "avatar",
    "text": "Hello! I'd be happy to help you. What can I assist you with today?",
    "timestamp": "2025-01-15T10:30:02.000Z"
  }
]
```

## 🔗 Parser Integration

### JSON Payload Sent to Parser

When "Parse Chat" button is clicked, the plugin sends:

```json
{
  "session_id": "uuid-from-anam-api",
  "transcript": [
    {
      "type": "user|avatar",
      "text": "message content",
      "timestamp": "ISO 8601 datetime"
    }
  ],
  "session_metadata": {
    "personaId": "uuid",
    "createdAt": "ISO 8601",
    "updatedAt": "ISO 8601",
    "clientLabel": "string"
  },
  "user_profile": {
    "first_name": "Nick",
    "last_name": "Patterson",
    "phone": "(912) 233-1234"
  },
  "timestamp": "ISO 8601"
}
```

### Parser Endpoint Configuration

- Configure in: **Settings → Anam Avatar → Database Integration**
- Default: `https://iegsoumvmhvvhmdyxhxs.supabase.co/functions/v1/key-value-processor`
- Parser handles AI analysis and Supabase storage internally
- WordPress acts as messenger only

## 🏗️ Development Notes

This plugin provides a complete WordPress integration for Anam.ai avatars with enterprise-grade transcript management. The system automatically captures conversations, stores them locally, and provides one-click AI parsing integration with external tools.

**Key Design Decisions:**
- Real-time capture prevents data loss
- Local database storage ensures backup
- Lazy-loading session metadata reduces API calls
- Parse status tracking prevents duplicate processing
- Clean uninstall removes all traces
