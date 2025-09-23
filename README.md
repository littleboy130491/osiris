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
<script>
(function() {
    // Debug configuration - set to true to enable console logging
    var DEBUG = false;
    
    // Configuration
    var CRM_ENDPOINT = 'https://your-crm-domain.com/api/events';
    var API_TOKEN = 'your-api-token';
    
    if (DEBUG) console.log('🚀 CRM Tracking Script Initializing...');
    
    // Initialize dataLayer if it doesn't exist
    window.dataLayer = window.dataLayer || [];
    if (DEBUG) console.log('📊 DataLayer initialized, current length:', window.dataLayer.length);

    // Fixed UUID generation function
    function generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            var uuid = crypto.randomUUID();
            if (DEBUG) console.log('🔑 Generated UUID using crypto.randomUUID():', uuid);
            return uuid;
        }
        var d = new Date().getTime();
        var d2 = (performance && performance.now && (performance.now() * 1000)) || 0;
        var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16;
            if (d > 0) {
                r = (d + r) % 16 | 0;
                d = Math.floor(d / 16);
            } else {
                r = (d2 + r) % 16 | 0;
                d2 = Math.floor(d2 / 16);
            }
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
        if (DEBUG) console.log('🔑 Generated UUID using fallback method:', uuid);
        return uuid;
    }

    function getCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) {
                var value = c.substring(nameEQ.length, c.length);
                if (DEBUG) console.log('🍪 Retrieved cookie', name + ':', value);
                return value;
            }
        }
        if (DEBUG) console.log('🍪 Cookie not found:', name);
        return null;
    }

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
        if (DEBUG) console.log('🍪 Set cookie', name + ':', value, '(expires in', days, 'days)');
    }

    function getOrCreateVisitorId() {
        if (DEBUG) console.log('👤 Getting or creating visitor ID...');
        var visitorId = getCookie('visitor_uuid');
        if (visitorId) {
            var uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            if (uuidRegex.test(visitorId)) {
                if (DEBUG) console.log('👤 Using existing visitor ID:', visitorId);
                return visitorId;
            }
            if (DEBUG) console.log('⚠️ Invalid visitor UUID in cookie, generating new one');
        }
        visitorId = generateUUID();
        setCookie('visitor_uuid', visitorId, 365);
        if (DEBUG) console.log('👤 Created new visitor ID:', visitorId);
        return visitorId;
    }

    function getOrCreateSessionId() {
        if (DEBUG) console.log('🔗 Getting or creating session ID...');
        var sessionId = null;
        try {
            sessionId = sessionStorage.getItem('session_uuid');
            if (DEBUG && sessionId) console.log('🔗 Found session ID in sessionStorage:', sessionId);
        } catch (e) {
            if (DEBUG) console.log('⚠️ SessionStorage not available, trying cookie fallback');
            sessionId = getCookie('session_uuid');
        }
        if (sessionId) {
            var uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            if (uuidRegex.test(sessionId)) {
                if (DEBUG) console.log('🔗 Using existing session ID:', sessionId);
                return sessionId;
            }
            if (DEBUG) console.log('⚠️ Invalid session UUID, generating new one');
        }
        sessionId = generateUUID();
        try {
            sessionStorage.setItem('session_uuid', sessionId);
            if (DEBUG) console.log('🔗 Stored session ID in sessionStorage:', sessionId);
        } catch (e) {
            setCookie('session_uuid', sessionId, 0.02);
            if (DEBUG) console.log('🔗 Stored session ID in cookie (sessionStorage failed):', sessionId);
        }
        if (DEBUG) console.log('🔗 Created new session ID:', sessionId);
        return sessionId;
    }

    function getUtmParameters() {
        var params = {};
        var searchParams = window.location.search.substring(1);
        if (searchParams) {
            var pairs = searchParams.split('&');
            for (var i = 0; i < pairs.length; i++) {
                var pair = pairs[i].split('=');
                if (pair[0]) {
                    params[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
                }
            }
            if (DEBUG) console.log('🔗 Extracted UTM parameters:', params);
        } else {
            if (DEBUG) console.log('🔗 No UTM parameters found');
        }
        return params;
    }

    function sendToCRM(eventName, eventData) {
        if (DEBUG) console.log('📤 === Sending to CRM ===');
        if (DEBUG) console.log('📤 Event:', eventName);
        if (DEBUG) console.log('📤 Event Data:', eventData);
        
        var visitorUuid = getOrCreateVisitorId();
        var sessionUuid = getOrCreateSessionId();
        
        var payload = {
            event: eventName,
            url: window.location.href,
            referrer: document.referrer || null,
            visitor_uuid: visitorUuid,
            session_uuid: sessionUuid,
            visitor_name: null,
            visitor_email: null,
            visitor_phone: null,
            query_strings: getUtmParameters(),
            timestamp: new Date().toISOString(),
            data: eventData || {}
        };

        if (DEBUG) console.log('📤 Final Payload:', JSON.stringify(payload, null, 2));

        var xhr = new XMLHttpRequest();
        xhr.open('POST', CRM_ENDPOINT, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CRM-TOKEN', API_TOKEN);
        
        if (DEBUG) console.log('📤 Sending request to:', CRM_ENDPOINT);
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status >= 200 && xhr.status < 300) {
                    if (DEBUG) console.log('✅ CRM Success for event:', eventName);
                    if (DEBUG) console.log('✅ Response status:', xhr.status);
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (DEBUG) console.log('✅ Response data:', response);
                    } catch (e) {
                        if (DEBUG) console.log('✅ Response (not JSON):', xhr.responseText);
                    }
                } else {
                    if (DEBUG) console.error('❌ CRM Error for event:', eventName);
                    if (DEBUG) console.error('❌ Status:', xhr.status);
                    if (DEBUG) console.error('❌ Response:', xhr.responseText);
                }
            }
        };
        
        xhr.onerror = function() {
            if (DEBUG) console.error('❌ CRM Request Failed for event:', eventName);
            if (DEBUG) console.error('❌ Network error or CORS issue');
        };
        
        xhr.send(JSON.stringify(payload));
    }

    // Function to check if object is Arguments-like
    function isArgumentsObject(obj) {
        return obj && 
               typeof obj === 'object' && 
               typeof obj.length === 'number' && 
               obj.length >= 0 && 
               (obj.toString() === '[object Arguments]' || 
                (obj.callee && typeof obj.callee === 'function') ||
                (obj[0] === 'event' && typeof obj[1] === 'string'));
    }

    // Store the original dataLayer.push function
    var originalPush = window.dataLayer.push;
    if (DEBUG) console.log('🔧 Storing original dataLayer.push function');
    
    // Override dataLayer.push to intercept all events
    window.dataLayer.push = function() {
        if (DEBUG) console.log('📊 dataLayer.push intercepted with', arguments.length, 'items');
        
        // Call the original push function first
        var result = originalPush.apply(window.dataLayer, arguments);
        
        // Handle both standard format and arguments format
        if (arguments.length >= 2 && arguments[0] === 'event' && typeof arguments[1] === 'string') {
            // Direct arguments format: dataLayer.push('event', 'event_name', {...})
            var eventName = arguments[1];
            var eventData = arguments[2] || {};
            
            if (DEBUG) console.log('📊 Detected direct arguments format - Event:', eventName);
            
            if (!eventName.startsWith('gtm.')) {
                if (DEBUG) console.log('✅ Processing custom event (direct arguments format):', eventName);
                sendToCRM(eventName, eventData);
            } else {
                if (DEBUG) console.log('⏭️ Skipping GTM internal event (direct arguments format):', eventName);
            }
        } else {
            // Standard format or Arguments object format
            for (var i = 0; i < arguments.length; i++) {
                var data = arguments[i];
                if (DEBUG) console.log('📊 Processing dataLayer item', i + ':', data);
                
                // Check if this is an Arguments object
                if (isArgumentsObject(data)) {
                    if (DEBUG) console.log('🔍 Detected Arguments object format');
                    
                    if (data.length >= 2 && data[0] === 'event' && typeof data[1] === 'string') {
                        var eventName = data[1];
                        var eventData = data[2] || {};
                        
                        if (DEBUG) console.log('📊 Extracted from Arguments - Event:', eventName);
                        
                        if (!eventName.startsWith('gtm.')) {
                            if (DEBUG) console.log('✅ Processing custom event (Arguments object format):', eventName);
                            sendToCRM(eventName, eventData);
                        } else {
                            if (DEBUG) console.log('⏭️ Skipping GTM internal event (Arguments object format):', eventName);
                        }
                    } else {
                        if (DEBUG) console.log('⏭️ Arguments object format not recognized');
                    }
                }
                // Check if this is a standard event object
                else if (data && data.event) {
                    if (data.event.startsWith('gtm.')) {
                        if (DEBUG) console.log('⏭️ Skipping GTM internal event:', data.event);
                    } else {
                        if (DEBUG) console.log('✅ Processing custom event (standard format):', data.event);
                        sendToCRM(data.event, data);
                    }
                } else {
                    if (DEBUG) console.log('⏭️ No event property found in dataLayer item');
                }
            }
        }
        
        return result;
    };
    
    if (DEBUG) console.log('🔧 dataLayer.push override installed');
    
    // Process any events that were already in dataLayer before our script loaded
    if (DEBUG) console.log('🔍 Processing existing dataLayer events...');
    var processedCount = 0;
    for (var i = 0; i < window.dataLayer.length; i++) {
        var data = window.dataLayer[i];
        
        // Check for Arguments objects in existing dataLayer
        if (isArgumentsObject(data)) {
            if (data.length >= 2 && data[0] === 'event' && typeof data[1] === 'string') {
                var eventName = data[1];
                if (!eventName.startsWith('gtm.')) {
                    if (DEBUG) console.log('✅ Processing existing custom event (Arguments format):', eventName);
                    sendToCRM(eventName, data[2] || {});
                    processedCount++;
                }
            }
        }
        // Check for standard event objects
        else if (data && data.event) {
            if (data.event.startsWith('gtm.')) {
                if (DEBUG) console.log('⏭️ Skipping existing GTM event:', data.event);
            } else {
                if (DEBUG) console.log('✅ Processing existing custom event:', data.event);
                sendToCRM(data.event, data);
                processedCount++;
            }
        }
    }
    
    // Send automatic page view event
    if (DEBUG) console.log('📄 Sending automatic page view event');
    sendToCRM('page_view', {
        page_title: document.title,
        page_location: window.location.href,
        page_referrer: document.referrer,
        navigation_type: 'initial_load'
    });
    
    if (DEBUG) console.log('🎉 CRM tracking initialized successfully!');
    if (DEBUG) console.log('📊 Processed', processedCount, 'existing events');
    if (DEBUG) console.log('👂 Now listening for all new dataLayer events');
})();
</script>
```

### 2.2 Update Configuration Variables

In the script above, update these two lines:
```javascript
var CRM_ENDPOINT = 'https://your-crm-domain.com/api/events';
var API_TOKEN = 'your-api-token-here';
```

Replace with your actual CRM endpoint URL and API token.

### 2.3 Create Trigger

1. **Create a new Trigger:**
   - Click **"New"** under Triggers
   - **Trigger Name:** `All Events Trigger`
   - **Trigger Type:** `Custom Event`

2. **Configure the trigger:**
   - **Event name:** `.*`
   - **Use regex matching:** ✅ **Check this box**
   - **This trigger fires on:** `All Custom Events`

3. **Save the trigger**

### 2.4 Assign Trigger to Tag

1. **Go back to your CRM Event Tracker tag**
2. **In the Triggering section, click to add a trigger**
3. **Select the "All Events Trigger" you just created**
4. **Save the tag**

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