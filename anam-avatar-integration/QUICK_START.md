# Quick Start Guide - Vehicle Data Capture

## ✅ Implementation Complete!

All features have been successfully implemented. Here's what you need to do to get started.

---

## 📦 Files Created/Modified

### New Files
- ✅ `anam-sessions-admin.php` - Admin interface for reviewing sessions
- ✅ `anam-sessions-admin.js` - JavaScript for admin interface
- ✅ `IMPLEMENTATION_GUIDE.md` - Comprehensive documentation
- ✅ `QUICK_START.md` - This file

### Modified Files
- ✅ `anam-transcript-handler.php` - Added parsing, email, Supabase integration
- ✅ `anam-admin-settings.php` - Added Supabase and email settings

---

## 🚀 5-Minute Setup

### 1. Activate Database Migration (30 seconds)

```
WordPress Admin → Plugins → Anam on WordPress
Click "Deactivate" then "Activate"
```

**What happens:** Database automatically adds new columns for review workflow.

### 2. Configure Supabase (2 minutes)

```
WordPress Admin → Settings → Anam Avatar
Scroll to "Supabase Integration"
```

Enter:
- **Supabase URL**: `https://your-project.supabase.co`
- **Supabase API Key**: Your anon or service role key
- **Table Name**: `vehicle_conversations`

### 3. Enable Email Notifications (10 seconds)

```
Same settings page → "Email Notifications"
Check the box to enable
```

### 4. Create Supabase Table (1 minute)

Go to Supabase SQL Editor and run:

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
  processed_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

### 5. Test It! (1 minute)

1. Have a conversation with your avatar
2. Mention: "I have a 2020 Toyota Camry, VIN is 1HGBH41JXMN109186"
3. Close the avatar
4. Wait 10 seconds
5. Check your email or WordPress Admin → Settings → Anam Sessions

---

## 🎯 What Happens Next

### Automatic Process

```
User closes avatar
    ↓ (10 seconds)
Transcript fetched from Anam
    ↓
Vehicle data parsed
    ↓
Email sent to admin
    ↓
Session appears in admin table
```

### Manual Review

```
Admin opens email
    ↓
Clicks "View Session" link
    ↓
Reviews parsed data
    ↓
Edits if needed
    ↓
Clicks "Send to Supabase"
    ↓
Data pushed to Supabase
```

---

## 📋 Checklist

- [ ] Plugin reactivated (database migrated)
- [ ] Supabase URL configured
- [ ] Supabase API key configured  
- [ ] Supabase table created
- [ ] Email notifications enabled
- [ ] Test conversation completed
- [ ] Email received
- [ ] Session visible in admin
- [ ] Data sent to Supabase

---

## 🔍 Where to Find Things

### Admin Pages
- **Settings**: WordPress Admin → Settings → Anam Avatar
- **Sessions**: WordPress Admin → Settings → Anam Sessions

### Database Tables
- **WordPress**: `wp_anam_conversations`
- **Supabase**: `vehicle_conversations`

### Logs
- **WordPress**: `/wp-content/debug.log` (enable WP_DEBUG)
- **Browser**: Developer Tools → Console
- **Supabase**: Dashboard → Logs

---

## 🆘 Common Issues

### "Email not received"
✅ **Solution**: Check WordPress Admin → Settings → Anam Sessions (sessions still appear)
✅ **Optional**: Install WP Mail SMTP plugin

### "Supabase connection failed"
✅ **Check**: URL format includes `https://` and `.supabase.co`
✅ **Check**: API key is correct (anon or service role)
✅ **Check**: Table exists in Supabase

### "No sessions appearing"
✅ **Check**: Avatar conversation was closed
✅ **Check**: Anam API key is valid
✅ **Check**: WordPress cron is running

### "Parsing not accurate"
✅ **Solution**: Edit parsed data in admin before sending
✅ **Future**: Add more vehicle makes or use LLM parsing

---

## 📊 Example Supabase Queries

### View all captured data
```sql
SELECT * FROM vehicle_conversations 
ORDER BY created_at DESC 
LIMIT 10;
```

### Count by make
```sql
SELECT make, COUNT(*) 
FROM vehicle_conversations 
GROUP BY make 
ORDER BY COUNT(*) DESC;
```

### Find specific VIN
```sql
SELECT * FROM vehicle_conversations 
WHERE vin = '1HGBH41JXMN109186';
```

---

## 🎓 Next Steps

1. **Test the workflow** end-to-end
2. **Review parsing accuracy** and adjust if needed
3. **Set up Supabase RLS** for production security
4. **Create Supabase views** for reporting
5. **Build dashboards** with your data

---

## 📚 Full Documentation

See `IMPLEMENTATION_GUIDE.md` for:
- Complete workflow details
- Advanced configuration
- Custom parsing fields
- Troubleshooting guide
- Security best practices
- Analytics queries

---

## ✨ Features Included

- ✅ Automatic vehicle data parsing (Year, Make, Model, VIN)
- ✅ Email notifications with parsed data preview
- ✅ Admin review interface with edit capability
- ✅ Manual Supabase integration (human-in-the-loop)
- ✅ Complete audit trail
- ✅ Automatic database migration
- ✅ Error handling and retry logic
- ✅ Duplicate send prevention
- ✅ Session filtering and search
- ✅ Transcript viewing

---

## 🎉 You're Ready!

Your vehicle data capture system is now fully configured and ready to use.

**Questions?** Check `IMPLEMENTATION_GUIDE.md` for detailed documentation.

**Issues?** Enable WP_DEBUG and check `/wp-content/debug.log` for error messages.

---

**Happy data capturing! 🚗💨**
