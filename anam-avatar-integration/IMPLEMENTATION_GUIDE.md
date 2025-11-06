# Anam WordPress Plugin - Implementation Guide

## 🎉 What's New (v2.1.0)

### Architecture Refactoring - Phase 1 Complete ✅

The plugin has been refactored for better maintainability and follows WordPress best practices:

- **Modular JavaScript** - Separated into `assets/js/admin.js` and `assets/js/frontend.js`
- **Proper Enqueuing** - Uses `wp_enqueue_script()` instead of inline code
- **Reduced Complexity** - Main PHP file reduced from 3,209 to 2,131 lines
- **Better Debugging** - Separate files in browser DevTools
- **Backward Compatible** - All functionality preserved

See `REFACTORING.md` for complete details.

---

## 🎉 What's New (v2.0.0)

This update adds a complete **human-in-the-loop** workflow for capturing vehicle data from Anam avatar conversations and sending it to Supabase.

### New Features

1. **Automatic Vehicle Data Parsing** - Extracts Year, Make, Model, VIN from conversations
2. **Email Notifications** - Get notified when new conversations need review
3. **Admin Review Interface** - View, edit, and approve parsed data before sending
4. **Supabase Integration** - Push approved data to your Supabase data warehouse
5. **Complete Audit Trail** - Track all conversations, parsing, and submissions

---

## 📋 Installation Steps

### Step 1: Update Plugin Files

Upload these new/modified files to your WordPress plugin directory:

- `anam-transcript-handler.php` (modified)
- `anam-admin-settings.php` (modified)
- `anam-sessions-admin.php` (new)
- `anam-sessions-admin.js` (new)

### Step 2: Activate Database Migration

The database will automatically migrate when you:

1. Go to WordPress Admin → Plugins
2. Deactivate "Anam on WordPress - Admin Settings"
3. Reactivate the plugin

**What happens:** New columns are added to `wp_anam_conversations` table automatically. No manual SQL required!

### Step 3: Configure Supabase

1. Go to **WordPress Admin → Settings → Anam Avatar**
2. Scroll to **Supabase Integration** section
3. Enter your Supabase details:
   - **Supabase URL**: `https://your-project.supabase.co`
   - **Supabase API Key**: Your anon or service role key
   - **Table Name**: `vehicle_conversations` (or your custom table name)

### Step 4: Configure Email Notifications

1. In the same settings page, scroll to **Email Notifications**
2. Check "Enable Email Notifications"
3. Emails will be sent to your WordPress admin email
4. **(Optional)** Install WP Mail SMTP plugin for better deliverability

### Step 5: Create Supabase Table

Run this SQL in your Supabase SQL Editor:

```sql
CREATE TABLE vehicle_conversations (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  session_id TEXT NOT NULL,
  year TEXT,
  make TEXT,
  model TEXT,
  vin TEXT,
  page_url TEXT,
  conversation_date TIMESTAMP WITH TIME ZONE,
  processed_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Add indexes for better performance
CREATE INDEX idx_session_id ON vehicle_conversations(session_id);
CREATE INDEX idx_vin ON vehicle_conversations(vin);
CREATE INDEX idx_created_at ON vehicle_conversations(created_at DESC);
```

---

## 🔄 How It Works

### The Complete Workflow

```
1. User has conversation with avatar
   ↓
2. User closes avatar (or avatar auto-closes)
   ↓
3. Session ID captured automatically
   ↓
4. WordPress cron job triggered (10 seconds later)
   ↓
5. Transcript fetched from Anam API
   ↓
6. Vehicle data parsed (Year, Make, Model, VIN)
   ↓
7. Email sent to admin
   ↓
8. Admin reviews in WordPress dashboard
   ↓
9. Admin clicks "Send to Supabase"
   ↓
10. Data pushed to Supabase table
```

### Parsing Logic

The system uses **regex pattern matching** to extract:

- **VIN**: 17-character alphanumeric (excludes I, O, Q)
- **Year**: 4-digit year (1900-2099)
- **Make**: Matches against 30+ vehicle manufacturers
- **Model**: Extracted after make mention

---

## 🖥️ Using the Admin Interface

### Viewing Sessions

1. Go to **WordPress Admin → Settings → Anam Sessions**
2. You'll see a table with all conversations
3. Filter by status: All / Pending Review / Sent to Supabase

### Session Table Columns

- **Session ID**: Unique identifier from Anam
- **Date**: When conversation occurred
- **Page URL**: Where the conversation happened
- **Vehicle Data**: Parsed fields (Year, Make, Model, VIN)
- **Status**: Pending / Sent / Error
- **Actions**: View Details / Send to Supabase / Delete

### Reviewing a Session

1. Click **"View Details"** button
2. Modal shows:
   - Full session information
   - Parsed vehicle data
   - Edit form (to correct any parsing errors)
   - Transcript viewer
3. Edit any fields if needed
4. Click **"Save Changes"** to update

### Sending to Supabase

1. Review the parsed data
2. Click **"Send to Supabase"** button
3. Confirm the action
4. Data is pushed to your Supabase table
5. Status changes to "Sent"
6. Supabase ID is displayed

---

## 📧 Email Notifications

### What You'll Receive

**Subject:** `[Anam] New Vehicle Conversation Ready for Review`

**Body includes:**
- Session ID
- Date and time
- Page URL where conversation occurred
- Parsed vehicle data preview
- Direct link to review the session
- Link to view all pending sessions

### Email Fallback

If email fails (common on shared hosting):
- Admin notice created in WordPress dashboard
- Session still appears in admin table
- Everything works normally without email

### Improving Email Delivery

Install **WP Mail SMTP** plugin:
1. WordPress Admin → Plugins → Add New
2. Search "WP Mail SMTP"
3. Install and configure with Gmail/SendGrid/etc.

---

## 🔧 Configuration Options

### Admin Settings Page

**WordPress Admin → Settings → Anam Avatar**

#### Supabase Integration
- **Supabase URL**: Your project URL
- **Supabase API Key**: Anon or service role key
- **Table Name**: Target table (default: `vehicle_conversations`)

#### Email Notifications
- **Enable/Disable**: Toggle email notifications
- **Recipient**: Shows current admin email

#### Parser Tool (Optional)
- Keep existing parser tool URL if you want dual processing
- Leave empty to only use Supabase

---

## 🗄️ Database Schema

### WordPress Table: `wp_anam_conversations`

```sql
id                  - Auto-increment primary key
session_id          - Anam session ID (unique)
page_url            - Where conversation occurred
timestamp           - Conversation timestamp
status              - Processing status (pending/completed/error)
metadata            - Additional session metadata (JSON)
error_message       - Error details if failed
created_at          - Record creation time
processed_at        - When processing completed
parsed_data         - Extracted vehicle data (JSON)
review_status       - Review workflow status (pending/sent)
reviewed_by         - WordPress user ID who reviewed
reviewed_at         - When reviewed
supabase_id         - ID returned from Supabase
supabase_sent_at    - When sent to Supabase
email_sent          - Boolean flag
email_sent_at       - When email was sent
transcript_raw      - Full transcript from Anam (JSON)
```

### Supabase Table: `vehicle_conversations`

```sql
id                  - UUID primary key
session_id          - Anam session ID
year                - Vehicle year
make                - Vehicle make
model               - Vehicle model
vin                 - Vehicle VIN
page_url            - Source page URL
conversation_date   - Original conversation timestamp
processed_at        - When sent from WordPress
created_at          - Supabase record creation
```

---

## 🔍 Troubleshooting

### Database Migration Issues

**Problem:** New columns not appearing

**Solution:**
```php
// Check migration status
// Add to wp-config.php temporarily:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check /wp-content/debug.log for migration messages
```

**Manual Migration (if needed):**
```sql
ALTER TABLE wp_anam_conversations 
ADD COLUMN parsed_data longtext DEFAULT NULL,
ADD COLUMN review_status varchar(20) DEFAULT 'pending',
ADD COLUMN reviewed_by bigint(20) DEFAULT NULL,
ADD COLUMN reviewed_at datetime DEFAULT NULL,
ADD COLUMN supabase_id varchar(100) DEFAULT NULL,
ADD COLUMN supabase_sent_at datetime DEFAULT NULL,
ADD COLUMN email_sent tinyint(1) DEFAULT 0,
ADD COLUMN email_sent_at datetime DEFAULT NULL,
ADD COLUMN transcript_raw longtext DEFAULT NULL;
```

### Email Not Sending

**Check:**
1. WordPress Admin → Settings → General → Admin Email
2. Test with WP Mail SMTP plugin
3. Check spam folder
4. Review `/wp-content/debug.log` for errors

**Fallback:** Sessions still appear in admin table even without email

### Supabase Connection Errors

**Common Issues:**
1. **Wrong URL format** - Must include `https://` and `.supabase.co`
2. **Wrong API key** - Use anon key or service role key (not project API key)
3. **Table doesn't exist** - Run the CREATE TABLE SQL first
4. **RLS policies** - Disable Row Level Security or add policy for service role

**Test Connection:**
```bash
curl -X POST 'https://your-project.supabase.co/rest/v1/vehicle_conversations' \
  -H "apikey: YOUR_KEY" \
  -H "Authorization: Bearer YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"session_id":"test","year":"2020","make":"Toyota","model":"Camry"}'
```

### Parsing Not Working

**Check:**
1. Transcript is being fetched (check `transcript_raw` column)
2. Parsing logic matches your conversation format
3. Debug log shows parsing results

**Improve Parsing:**
- Add more vehicle makes to the array in `parse_vehicle_data()`
- Adjust regex patterns for your specific use case
- Consider adding LLM-based parsing for better accuracy

### Sessions Not Appearing

**Check:**
1. Avatar conversation actually closed
2. WordPress cron is running (`wp cron event list`)
3. Anam API key is valid
4. Check error_message column in database

**Force Process:**
```php
// In WordPress admin, go to Tools → Site Health → Info → Cron
// Look for 'anam_process_with_parser_tool' events
```

---

## 🚀 Advanced Usage

### Custom Parsing Fields

Edit `anam-transcript-handler.php` line 500:

```php
private function parse_vehicle_data($transcript_text) {
    $parsed = array(
        'year' => null,
        'make' => null,
        'model' => null,
        'vin' => null,
        // Add custom fields:
        'mileage' => null,
        'color' => null,
        'price' => null
    );
    
    // Add custom parsing logic
    if (preg_match('/(\d{1,6})\s*miles?/i', $transcript_text, $matches)) {
        $parsed['mileage'] = $matches[1];
    }
    
    return $parsed;
}
```

### Custom Supabase Payload

Edit `anam-transcript-handler.php` line 856:

```php
$payload = array(
    'session_id' => $conversation->session_id,
    'year' => $parsed_data['year'] ?? null,
    'make' => $parsed_data['make'] ?? null,
    'model' => $parsed_data['model'] ?? null,
    'vin' => $parsed_data['vin'] ?? null,
    // Add custom fields:
    'mileage' => $parsed_data['mileage'] ?? null,
    'source' => 'wordpress_anam',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
);
```

### Bulk Operations

Add to `anam-sessions-admin.php`:

```php
// Bulk send to Supabase
public function bulk_send_to_supabase($session_ids) {
    foreach ($session_ids as $id) {
        $handler = anam_get_transcript_handler();
        $handler->send_to_supabase($id);
    }
}
```

### Webhook Integration

Add webhook notification after Supabase send:

```php
// In send_to_supabase() method, after successful send:
wp_remote_post('https://your-webhook-url.com/notify', array(
    'body' => json_encode(array(
        'event' => 'vehicle_data_captured',
        'session_id' => $session_id,
        'supabase_id' => $supabase_id,
        'data' => $payload
    ))
));
```

---

## 📊 Analytics & Reporting

### Query Supabase for Insights

```sql
-- Most common vehicle makes
SELECT make, COUNT(*) as count 
FROM vehicle_conversations 
GROUP BY make 
ORDER BY count DESC;

-- Conversations by date
SELECT DATE(conversation_date) as date, COUNT(*) 
FROM vehicle_conversations 
GROUP BY DATE(conversation_date) 
ORDER BY date DESC;

-- VIN capture rate
SELECT 
  COUNT(*) as total,
  COUNT(vin) as with_vin,
  ROUND(COUNT(vin)::numeric / COUNT(*) * 100, 2) as capture_rate
FROM vehicle_conversations;
```

### WordPress Dashboard Widget

Add to `anam-admin-settings.php`:

```php
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'anam_stats',
        'Anam Conversations',
        function() {
            global $wpdb;
            $pending = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}anam_conversations WHERE review_status = 'pending'");
            $sent = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}anam_conversations WHERE review_status = 'sent'");
            echo "<p><strong>Pending Review:</strong> $pending</p>";
            echo "<p><strong>Sent to Supabase:</strong> $sent</p>";
            echo "<p><a href='admin.php?page=anam-sessions'>View All Sessions →</a></p>";
        }
    );
});
```

---

## 🔐 Security Best Practices

1. **Use Service Role Key** for Supabase (not anon key) for admin operations
2. **Enable RLS** on Supabase table after testing
3. **Limit WordPress user permissions** - only admins should access sessions
4. **Sanitize all inputs** - already implemented in code
5. **Use HTTPS** - required for Anam API and Supabase

---

## 🆘 Support & Resources

### Documentation
- [Anam API Docs](https://docs.anam.ai)
- [Supabase Docs](https://supabase.com/docs)
- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)

### Logs
- WordPress: `/wp-content/debug.log`
- Browser Console: Check for JavaScript errors
- Supabase: Dashboard → Logs

### Getting Help
1. Check error messages in WordPress debug log
2. Review Supabase API logs
3. Test with WP_DEBUG enabled
4. Check browser console for AJAX errors

---

## 📝 Version History

### v2.0.0 (Current)
- ✅ Vehicle data parsing (Year, Make, Model, VIN)
- ✅ Email notifications
- ✅ Admin review interface
- ✅ Supabase integration
- ✅ Automatic database migration
- ✅ Complete audit trail

### v1.0.0
- Basic Anam avatar integration
- Session token generation
- Admin settings page

---

## 🎯 Next Steps

1. **Test the workflow** with a sample conversation
2. **Configure email notifications** and test delivery
3. **Set up Supabase table** and test connection
4. **Review first session** in admin interface
5. **Send test data** to Supabase
6. **Monitor and optimize** parsing accuracy

---

## ✅ Checklist

- [ ] Plugin files uploaded
- [ ] Plugin reactivated (database migrated)
- [ ] Supabase URL configured
- [ ] Supabase API key configured
- [ ] Supabase table created
- [ ] Email notifications enabled
- [ ] Test conversation completed
- [ ] Email received (or admin notice seen)
- [ ] Session reviewed in admin
- [ ] Data sent to Supabase successfully
- [ ] Verified data in Supabase table

---

**Congratulations! Your vehicle data capture workflow is now live! 🚗💨**
