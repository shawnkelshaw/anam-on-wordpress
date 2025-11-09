# Client Tools Implementation - Complete

## Overview
Successfully implemented a **Client Tools** admin page that allows you to configure redirect tools for your Anam avatar. The avatar can now redirect users to different pages (like appointment calendars) based on conversation context.

---

## What Was Implemented

### 1. **New Admin Page: Client Tools** (#6 in menu)
- Location: `Anam Avatar → Client Tools`
- Manage multiple redirect tools with enable/disable toggles
- Each tool has:
  - **Enable checkbox**: Turn tool on/off
  - **Tool Name**: Unique identifier (e.g., `redirect_to_calendar`)
  - **Display Name**: Friendly name (e.g., "Appointment Calendar")
  - **Redirect URL**: Where to send users
  - **Description**: When the avatar should use this tool

### 2. **System Prompt Snippet Generator**
- Auto-generates instructions for your avatar based on enabled tools
- Copy-to-clipboard functionality
- Add snippet to Avatar Setup → System Prompt

### 3. **Frontend Event Listener**
- Listens for `AnamEvent.TOOL_CALL` events
- Automatically redirects when avatar triggers a redirect tool
- Works with both display methods (Element ID and Page Position)

### 4. **Settings Persistence**
- Tools saved in WordPress options table
- Proper sanitization for all fields
- Survives plugin updates

---

## How to Use

### Step 1: Create Your First Redirect Tool

1. Go to **Anam Avatar → Client Tools**
2. Click **"➕ Add Redirect Tool"**
3. Fill in the fields:
   - **Enable**: ✓ Check this box
   - **Tool Name**: `redirect_to_calendar` (lowercase, underscores only)
   - **Display Name**: `Appointment Calendar`
   - **Redirect URL**: `https://calendar.app.google/syVqGgCryE9dHUfA6`
   - **Description**: `When user asks to schedule an appointment or book a time with Alan`
4. Click **"Save Client Tools"**

### Step 2: Copy System Prompt Snippet

1. Scroll to **"📋 System Prompt Snippet"** section
2. Click **"📋 Copy to Clipboard"**
3. You'll see: `✅ Copied!`

### Step 3: Add to Avatar System Prompt

1. Go to **Anam Avatar → Avatar Setup**
2. Scroll to **System Prompt** field
3. Paste the snippet at the end of your existing prompt
4. Click **"Save Settings"**

### Step 4: Create the Tool in Anam Dashboard

You need to create the actual tool in Anam's system:

**API Request:**
```bash
POST https://api.anam.ai/v1/tools
Content-Type: application/json
Authorization: Bearer YOUR_API_KEY

{
  "type": "client",
  "name": "redirect_to_calendar",
  "description": "Redirect to appointment calendar when user asks to schedule an appointment or book a time with Alan",
  "parameters": {
    "type": "object",
    "properties": {
      "url": {
        "type": "string",
        "description": "The URL to redirect to"
      }
    },
    "required": ["url"]
  }
}
```

**Response:**
```json
{
  "id": "tool-uuid-123",
  "name": "redirect_to_calendar",
  ...
}
```

### Step 5: Attach Tool to Persona

**API Request:**
```bash
PUT https://api.anam.ai/v1/personas/{personaId}
Content-Type: application/json
Authorization: Bearer YOUR_API_KEY

{
  "toolIds": ["tool-uuid-123"]
}
```

---

## How It Works

### User Conversation Flow:

1. **User says**: "I'd like to schedule an appointment with Alan"
2. **LLM recognizes intent** and calls the `redirect_to_calendar` tool
3. **Tool invocation**: 
   ```json
   {
     "toolName": "redirect_to_calendar",
     "arguments": {
       "url": "https://calendar.app.google/syVqGgCryE9dHUfA6"
     }
   }
   ```
4. **Frontend event listener** catches the `TOOL_CALL` event
5. **Browser redirects**: `window.location.href = event.arguments.url`
6. **User lands** on Alan's Google Calendar

---

## Code Changes Made

### Files Modified:

1. **`anam-admin-settings.php`**
   - Added `client_tools_page()` method (lines 1149-1347)
   - Added `generate_system_prompt_snippet()` method (lines 1349-1384)
   - Added Client Tools menu item (lines 115-123)
   - Added client_tools sanitization (lines 898-910)

2. **`assets/js/frontend.js`**
   - Added TOOL_CALL event listener for Element ID method (lines 346-359)
   - Added TOOL_CALL event listener for Page Position method (lines 980-993)

---

## Example: Adding Multiple Redirects

You can add multiple redirect tools for different purposes:

### Example 1: Appointment Calendar
- **Tool Name**: `redirect_to_calendar`
- **Display Name**: Appointment Calendar
- **URL**: `https://calendar.app.google/syVqGgCryE9dHUfA6`
- **Description**: When user asks to schedule appointment or book time

### Example 2: Contact Form
- **Tool Name**: `redirect_to_contact`
- **Display Name**: Contact Form
- **URL**: `https://example.com/contact`
- **Description**: When user wants to send a message or contact support

### Example 3: Vehicle Inventory
- **Tool Name**: `redirect_to_inventory`
- **Display Name**: Vehicle Inventory
- **URL**: `https://example.com/inventory`
- **Description**: When user wants to browse available vehicles

---

## System Prompt Example

When you have the appointment calendar tool enabled, the generated snippet looks like this:

```
You have access to the following redirect tools:

- **Appointment Calendar** (tool: `redirect_to_calendar`)
  URL: https://calendar.app.google/syVqGgCryE9dHUfA6
  When to use: When user asks to schedule an appointment or book a time with Alan

When a user requests to go to one of these destinations, use the appropriate redirect tool with the URL specified above.
```

---

## Testing Checklist

- [ ] Go to Anam Avatar → Client Tools
- [ ] Add a new redirect tool
- [ ] Enable the tool
- [ ] Save settings
- [ ] Copy system prompt snippet
- [ ] Add snippet to Avatar Setup → System Prompt
- [ ] Create tool via Anam API
- [ ] Attach tool to persona via Anam API
- [ ] Test conversation: "I'd like to schedule an appointment"
- [ ] Verify redirect happens

---

## Important Notes

### Tool Naming Convention
- Use lowercase letters and underscores only
- Must start with `redirect_` for the event listener to recognize it
- Examples: `redirect_to_calendar`, `redirect_to_contact`, `redirect_to_inventory`

### URL Requirements
- Must be a valid URL (starts with `http://` or `https://`)
- WordPress will sanitize the URL automatically
- Can be any external or internal page

### System Prompt Integration
- The snippet is a guide for the LLM
- You can customize the wording
- Make sure it's clear when each tool should be used

### Anam API Requirements
- You must create the tool in Anam's system
- You must attach the tool to your persona
- Tool names must match exactly between WordPress and Anam

---

## Troubleshooting

### Tool not triggering?
1. Check browser console for `🔧 Tool called:` log
2. Verify tool name starts with `redirect_`
3. Confirm tool is enabled in Client Tools page
4. Check system prompt includes the tool instructions

### Redirect not happening?
1. Check browser console for `🔀 Redirecting to:` log
2. Verify URL is valid in Client Tools page
3. Check for browser popup blockers
4. Ensure `event.arguments.url` is present

### Tool not available in conversation?
1. Verify tool created in Anam API
2. Confirm tool attached to persona
3. Check system prompt includes tool instructions
4. Verify persona ID is correct in Avatar Setup

---

## Next Steps

1. **Create your first redirect tool** for Alan's calendar
2. **Test the redirect** in a conversation
3. **Add more tools** as needed (contact form, inventory, etc.)
4. **Monitor console logs** to see tool calls in action

---

## Support

If you encounter issues:
1. Check browser console for error messages
2. Verify all API calls to Anam succeeded
3. Confirm tool names match exactly
4. Review system prompt includes tool instructions
