# WebhostingPK Domain Registrar Module for WHMCS

## Installation Guide

### File Placement
1. Upload the registrar module file to your WHMCS installation:
   
   WHMCS_ROOT/modules/registrars/webhostingpk.php
   
   Replace `WHMCS_ROOT` with your actual WHMCS installation directory (typically `public_html/`, `whmcs/`, or your custom directory).

   Example full path:
   ```
   /home/username/public_html/my/modules/registrars/webhostingpk.php
   ```

### Module Activation
1. Log in to your WHMCS Admin Panel
2. Navigate to:
   
   Setup > Products/Services > Domain Registrars
   
3. Find "WebhostingPK" in the list
4. Click the "Activate" button

### Set as Default Registrar
1. In WHMCS Admin Panel, go to:

   Configuration > System Settings > General Settings
   
2. Select the "Domains" tab
3. Find "Default Domain Registrar" setting
4. Select "WebhostingPK" from the dropdown menu
5. Click "Save Changes"

## Configuration
1. After activation, click the "Configure" button for WebhostingPK
2. Enter your API credentials provided by WebhostingPK
3. Configure additional settings as required
4. Save your configuration

## Requirements
- WHMCS 7.0 or later
- Valid WebhostingPK API credentials
- PHP cURL extension enabled

## Troubleshooting
- If the module doesn't appear:
  - Verify file permissions (should be 644)
  - Check correct file placement path
  - Ensure PHP version compatibility
- For API errors:
  - Verify API credentials
  - Check WHMCS error logs
  - Ensure server can connect to WebhostingPK API endpoints

## Support
For technical support contact:
WebhostingPK Support Team
- Email: support@webhostingpk.com
- Phone: +92-322-2047786
- Website: www.webhostingpk.com
- WhatsApp: https://wa.me/923011119441

This guide includes:
1. Clear file placement instructions
2. Step-by-step activation process
3. Default registrar configuration steps
4. Basic troubleshooting information
5. Support contact details

Remember to:
1. Always backup your WHMCS installation before making changes
2. Test domain registration with test domains before going live
3. Keep your module updated with latest versions from WebhostingPK
