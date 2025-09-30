# GTM CRM Tracking Installation Guide

## Overview

This guide will help you install a comprehensive event tracking system that sends all Google Analytics 4 (GA4) events from your website to your CRM system through Google Tag Manager (GTM).

## Prerequisites

- Google Tag Manager container installed on your website
- GA4 tracking already configured
- CRM API endpoint ready to receive events
- API token for authentication

## Step 1: Prepare Your CRM Endpoint

### 1.1 Verify API Endpoint
Your CRM should have an endpoint that accepts POST requests:
```
POST https://your-crm-domain.com/api/events
```

### 1.2 Required Headers
```
Content-Type: application/json
X-CRM-TOKEN: your-api-token
```

### 1.3 Expected Payload Format
```json
{
  "event": "page_view",
  "url": "https://yoursite.com/page",
  "referrer": "https://google.com",
  "visitor_uuid": "12345678-1234-4567-8910-123456789012",
  "session_uuid": "87654321-4321-7654-0192-210987654321",
  "visitor_name": "John Doe",
  "visitor_email": "john@example.com",
  "visitor_phone": "+1234567890",
  "query_strings": {
    "utm_source": "google",
    "utm_medium": "cpc",
    "utm_campaign": "summer_sale",
    "gclid": "abc123",
    "fbclid": "def456"
  },
  "data": {
    "value": 100,
    "currency": "USD",
    "items": [...],
    "event_parameters": {...}
  }
}
```

## Step 2: Configure GTM Container

### 2.1 Create Custom HTML Tag

1. **Login to Google Tag Manager**
2. **Navigate to your container**
3. **Create a new Tag:**
   - Click **"New"** under Tags
   - Choose **"Custom HTML"** as tag type
   - **Tag Name:** `CRM Event Tracker`

4. **Add the tracking code:**

```html
<script async src="https://your-crm-domain.com/js/tracker.js"></script>
```


### 2.2 Assign Trigger to Tag

Use All Pages - Page View as trigger

## Step 3: Test the Setup

### 3.1 Use GTM Preview Mode

1. **In GTM, click "Preview"**
2. **Enter your website URL**
3. **Navigate to your website**

### 3.2 Verify Tag Firing

1. **In GTM Preview, check the "Tags Fired" section**
2. **Look for "CRM Event Tracker" tag**
3. **Verify it fires on various events (page views, clicks, etc.)**

### 3.3 Check Browser Console

1. **Open browser Developer Tools (F12)**
2. **Navigate to Console tab**
3. **Look for any error messages**
4. **Successful requests should be silent (no console output)**

### 3.4 Verify CRM Data

1. **Check your CRM database**
2. **Verify events are being recorded**
3. **Check that visitor_uuid and session_uuid are persistent**
4. **Verify UTM parameters are captured correctly**

## Step 4: Deploy to Production

### 4.1 Publish GTM Container

1. **In GTM, click "Submit"**
2. **Add version name and description**
3. **Click "Publish"**

### 4.2 Monitor Performance

1. **Check CRM logs for incoming events**
2. **Monitor for any error patterns**
3. **Verify data quality and completeness**

## Troubleshooting

### Common Issues

**Tag Not Firing**
- Check trigger configuration
- Ensure regex matching is enabled
- Verify GA4 events are firing first

**401 Unauthorized Error**
- Check API token is correct
- Verify token in CRM middleware
- Check Laravel logs for authentication errors

**Empty UTM Parameters**
- Test with URLs containing UTM parameters
- Check sessionStorage in browser DevTools
- Verify UTM persistence across page navigation

**Missing Event Data**
- Check GA4 dataLayer structure
- Verify event parameters are being sent
- Check CRM payload validation

### Debug Mode

To enable debug mode temporarily, uncomment these lines in the script:
```javascript
// console.log('CRM Success:', JSON.parse(xhr.responseText));
```

### Testing UTM Tracking

Test with URLs like:
```
https://yoursite.com?utm_source=google&utm_medium=cpc&utm_campaign=test&gclid=abc123
```

## Security Considerations

- API token is visible in client-side code
- Consider using IP whitelisting for additional security
- Monitor for unusual traffic patterns
- Implement rate limiting if needed

## Data Privacy

- Ensure compliance with GDPR/CCPA requirements
- Consider cookie consent integration
- Document data collection in privacy policy
- Provide opt-out mechanisms if required

## Advanced Configuration

### Custom Event Filtering

To track only specific events, modify the trigger:
```
Event name: page_view|purchase|form_submit|click
```

### Additional Data Extraction

Add custom data extraction in the payload:
```javascript
custom_data: {
  page_title: document.title,
  user_agent: navigator.userAgent,
  screen_resolution: screen.width + 'x' + screen.height
}
```

### Error Handling

Add retry logic for failed requests:
```javascript
var retryCount = 0;
var maxRetries = 3;

function sendWithRetry() {
  // Implement exponential backoff retry logic
}
```

## Support

For technical support:
- Check CRM API logs
- Monitor GTM debug console
- Review Laravel application logs
- Test individual components separately

This installation guide provides a complete setup for tracking all website events and sending them to your CRM system through GTM.