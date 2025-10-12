# Anam.ai WordPress Integration

A comprehensive WordPress plugin for integrating Anam.ai digital avatars using the JavaScript SDK. This plugin provides multiple implementation approaches and extensive debugging tools for successful deployment on various hosting environments.

## 🚀 Features

- **Multiple Integration Methods**: ESM imports, UMD scripts, and iframe fallbacks
- **Flexible Placement**: Fixed positioning or custom div containers
- **Comprehensive Debugging**: Built-in connectivity tests and API validation
- **Secure Implementation**: Server-side session token generation
- **Hosting Compatibility**: Solutions for shared hosting restrictions

## 📦 Installation

1. Download or clone this repository
2. Upload to your WordPress `/wp-content/plugins/anam-avatar-integration/` directory
3. Activate the desired plugin version through the 'Plugins' menu
4. Configure your API key and persona settings

## 🔧 Plugin Files

### Production Ready
- **`anam-admin-settings.php`** - ✅ **Recommended** - Full admin interface with settings page
- **`anam-on-wordpress.php`** - Working implementation (requires manual configuration)
- **`anam-avatar-plugin.php`** - Clean main plugin with sanitized configuration

### Utilities
- **`anam-api-tester.php`** - API key validation tool
- **`anam-cdn-diagnostics.php`** - Diagnostic tool for CDN accessibility
- **`anam-admin.js`** - JavaScript for admin interface functionality

## ⚙️ Configuration

### Option 1: Admin Interface (Recommended)
1. **Activate** `anam-admin-settings.php` plugin
2. **Go to** WordPress Admin → Settings → Anam Avatar
3. **Configure** your API key, avatar settings, and display options
4. **Test** your configuration with the built-in test tool
5. **Save** settings and view your avatar on the frontend

### Option 2: Manual Configuration
Update these values in your chosen plugin file:

```php
$this->api_key = 'your-anam-api-key-here';
$this->avatar_id = 'your-avatar-id';
$this->voice_id = 'your-voice-id';
$this->llm_id = 'your-llm-id';
$this->target_page_slug = 'your-target-page-slug';
```

## 🎛️ Admin Interface Features

- **🔐 Secure API Key Storage** - Encrypted storage in WordPress database
- **🎨 Avatar Customization** - Configure persona, avatar, voice, and LLM
- **📍 Display Control** - Choose where and when avatar appears
- **🧪 Built-in Testing** - Test API connection and configuration
- **📱 Responsive Design** - Works on all device sizes
- **🎯 Smart Positioning** - Multiple positioning options or custom containers

## 🎯 Custom Container Placement

To place the avatar in a specific location, use:

```html
<div id="anam-stream-container"></div>
```

The plugin will automatically stream to this container instead of creating a fixed position element.

## 🔧 How It Works

1. **Server-side**: WordPress generates secure session tokens using your API key
2. **Client-side**: JavaScript SDK initializes the avatar with the session token  
3. **Display**: Avatar streams to specified container or fixed position

## 🛠️ Troubleshooting

### Common Issues

**"Usage limit reached"** - Upgrade your Anam.ai plan
**"esmClient.on is not a function"** - SDK loading issue, try different plugin version
**404 CDN errors** - Hosting provider blocking external CDNs

### Debugging Steps

1. **Test API Key**: Use `anam-api-tester.php`
2. **Check CDN Access**: Use `cdn-connectivity-test.php`
3. **Console Logs**: Check browser developer tools
4. **Network Tab**: Verify SDK loading and API calls

### Hosting Compatibility

**Shared Hosting Issues:**
- External CDN blocking
- MIME type restrictions
- CORS policy conflicts

**Solutions:**
- Contact hosting support to whitelist CDN domains
- Use ESM-only version (most compatible)
- Consider VPS/dedicated hosting for full SDK features

## 🔒 Security

- ✅ API key stored server-side only
- ✅ Session tokens via WordPress AJAX with nonce verification
- ✅ HTTPS required for all API communication
- ✅ No sensitive data exposed to client-side

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

## 🏗️ Development History

This plugin was developed through extensive testing and debugging on shared hosting environments, resulting in multiple implementation approaches to handle various hosting restrictions and CDN accessibility issues.
