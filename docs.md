# Website Tracking Script Documentation

## Overview

This document provides the JavaScript tracking script that needs to be installed on your website to collect visitor data and send it to your tracking API. The script works similarly to Meta Pixel but is specifically designed for your Laravel tracking system.

## Installation

### Basic Installation

Add the following script to the `<head>` section of your website, replacing `YOUR_TRACKING_DOMAIN` with your actual domain:

```html
<script>
!function(w,d){
  if(w.Tracker)return;
  w.Tracker={
    q:[],
    config:{},
    init:function(c){this.config=c;for(var i=0;i<this.q.length;i++)this.q[i]();this.q=null},
    track:function(e,d){this.q?this.q.push(function(){this._track(e,d)}):this._track(e,d)},
    _track:function(e,d){/* tracking logic */}
  };
  var s=d.createElement('script');s.async=1;
  s.src='//YOUR_TRACKING_DOMAIN/tracker.js';
// Or if hosting locally:
// s.src='/tracker.js';
  d.getElementsByTagName('head')[0].appendChild(s);
}(window,document);

// Initialize with your domain
Tracker.init({
  domain: 'YOUR_TRACKING_DOMAIN',
  autoTrack: true
});
</script>
```

### Advanced Installation with Custom Events

```html
<script>
!function(w,d){
  if(w.Tracker)return;
  w.Tracker={
    q:[],
    config:{},
    init:function(c){this.config=c;for(var i=0;i<this.q.length;i++)this.q[i]();this.q=null},
    track:function(e,d){this.q?this.q.push(function(){this._track(e,d)}):this._track(e,d)},
    _track:function(e,d){/* tracking logic */}
  };
  var s=d.createElement('script');s.async=1;
  s.src='//YOUR_TRACKING_DOMAIN/tracker.js';
// Or if hosting locally:
// s.src='/tracker.js';
  d.getElementsByTagName('head')[0].appendChild(s);
}(window,document);

// Initialize
Tracker.init({
  domain: 'YOUR_TRACKING_DOMAIN',
  autoTrack: true,
  debug: false
});

// Track custom events
Tracker.track('button_click', {
  element_id: 'signup-button',
  page_section: 'hero'
});

// Track form submissions
document.getElementById('contact-form').addEventListener('submit', function() {
  Tracker.track('form_submit', {
    form_id: 'contact-form',
    form_type: 'contact'
  });
});
</script>
```

## Complete Tracking Script

Here's the full JavaScript tracking script that you need to host on your server at `/tracker.js`:

```javascript
(function(window, document) {
  'use strict';

  // Tracker constructor
  function Tracker(config) {
    this.config = config || {};
    this.visitorId = this.getCookie('__visitor_id');
    this.sessionId = this.getCookie('__session_id');
    this.endpoint = (config.domain || '') + '/api/events';
    this.queue = [];
    this.isInitialized = false;

    // Auto-generate IDs if not exist
    if (!this.visitorId) {
      this.visitorId = this.generateUUID();
      this.setCookie('__visitor_id', this.visitorId, 365);
    }

    if (!this.sessionId) {
      this.sessionId = this.generateUUID();
      this.setCookie('__session_id', this.sessionId, 0.5); // 12 hours
    }

    this.isInitialized = true;
    this.processQueue();
  }

  // Generate UUID v4
  Tracker.prototype.generateUUID = function() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  };

  // Cookie utilities
  Tracker.prototype.setCookie = function(name, value, days) {
    var expires = '';
    if (days) {
      var date = new Date();
      date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
      expires = '; expires=' + date.toGMTString();
    }
    document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
  };

  Tracker.prototype.getCookie = function(name) {
    var nameEQ = name + '=';
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == ' ') c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  };

  // Get URL parameters
  Tracker.prototype.getUrlParams = function() {
    var params = {};
    var urlParams = new URLSearchParams(window.location.search);
    for (var [key, value] of urlParams) {
      params[key] = value;
    }
    return params;
  };

  // Get attribution parameters
  Tracker.prototype.getAttributionParams = function() {
    var params = this.getUrlParams();
    var attribution = {};

    // UTM parameters
    if (params.utm_source) attribution.utm_source = params.utm_source;
    if (params.utm_medium) attribution.utm_medium = params.utm_medium;
    if (params.utm_campaign) attribution.utm_campaign = params.utm_campaign;
    if (params.utm_term) attribution.utm_term = params.utm_term;
    if (params.utm_content) attribution.utm_content = params.utm_content;

    // Click IDs
    if (params.gclid) attribution.gclid = params.gclid;
    if (params.fbclid) attribution.fbclid = params.fbclid;

    return attribution;
  };

  // Send tracking data
  Tracker.prototype.send = function(data, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', this.endpoint, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          try {
            var response = JSON.parse(xhr.responseText);
            if (response.success && response.visitor_uuid && response.session_uuid) {
              // Update stored IDs if server returned new ones
              if (response.visitor_uuid !== this.visitorId) {
                this.visitorId = response.visitor_uuid;
                this.setCookie('__visitor_id', this.visitorId, 365);
              }
              if (response.session_uuid !== this.sessionId) {
                this.sessionId = response.session_uuid;
                this.setCookie('__session_id', this.sessionId, 0.5);
              }
            }
            if (callback) callback(null, response);
          } catch (e) {
            if (callback) callback(e);
          }
        } else {
          if (callback) callback(new Error('HTTP ' + xhr.status));
        }
      }
    }.bind(this);

    xhr.send(JSON.stringify(data));
  };

  // Track event
  Tracker.prototype.track = function(eventName, customData) {
    if (!this.isInitialized) {
      this.queue.push([eventName, customData]);
      return;
    }

    var data = {
      event: eventName,
      url: window.location.href,
      referrer: document.referrer,
      visitor_uuid: this.visitorId,
      session_uuid: this.sessionId,
      query_strings: this.getUrlParams(),
      data: customData || {}
    };

    // Add attribution parameters
    var attribution = this.getAttributionParams();
    Object.assign(data, attribution);

    this.send(data, function(error, response) {
      if (this.config.debug) {
        if (error) {
          console.error('Tracking error:', error);
        } else {
          console.log('Tracking success:', response);
        }
      }
    }.bind(this));
  };

  // Process queued events
  Tracker.prototype.processQueue = function() {
    while (this.queue.length > 0) {
      var item = this.queue.shift();
      this.track(item[0], item[1]);
    }
  };

  // Auto-track page views
  Tracker.prototype.autoTrackPageView = function() {
    // Track initial page view
    this.track('page_view');

    // Track page changes (for SPAs)
    var currentUrl = window.location.href;
    var self = this;

    // Listen for browser navigation
    window.addEventListener('popstate', function() {
      if (window.location.href !== currentUrl) {
        currentUrl = window.location.href;
        self.track('page_view');
      }
    });

    // Listen for history API changes
    var pushState = history.pushState;
    history.pushState = function() {
      pushState.apply(history, arguments);
      if (window.location.href !== currentUrl) {
        currentUrl = window.location.href;
        self.track('page_view');
      }
    };

    var replaceState = history.replaceState;
    history.replaceState = function() {
      replaceState.apply(history, arguments);
      if (window.location.href !== currentUrl) {
        currentUrl = window.location.href;
        self.track('page_view');
      }
    };
  };

  // Global Tracker object
  if (!window.Tracker) {
    window.Tracker = {
      q: [],
      config: {},
      init: function(config) {
        this.config = config || {};
        window._tracker = new Tracker(this.config);

        if (this.config.autoTrack !== false) {
          window._tracker.autoTrackPageView();
        }

        // Process queued calls
        for (var i = 0; i < this.q.length; i++) {
          var call = this.q[i];
          if (call[0] === 'track') {
            window._tracker.track(call[1], call[2]);
          }
        }
        this.q = null;
      },
      track: function(eventName, data) {
        if (window._tracker) {
          window._tracker.track(eventName, data);
        } else {
          this.q.push(['track', eventName, data]);
        }
      }
    };
  }

})(window, document);
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `domain` | string | `''` | Your tracking API domain |
| `autoTrack` | boolean | `true` | Automatically track page views |
| `debug` | boolean | `false` | Enable console logging |

## Usage Examples

### Basic Page Tracking

```javascript
// Just initialize - page views are tracked automatically
Tracker.init({
  domain: 'https://your-tracking-domain.com'
});
```

### Custom Event Tracking

```javascript
// Track button clicks
Tracker.track('button_click', {
  button_id: 'cta-button',
  button_text: 'Sign Up Now',
  page_section: 'hero'
});

// Track form submissions
Tracker.track('form_submit', {
  form_id: 'contact-form',
  form_fields: ['name', 'email', 'message']
});

// Track purchases
Tracker.track('purchase', {
  product_id: '12345',
  product_name: 'Premium Plan',
  value: 99.99,
  currency: 'USD'
});
```

### E-commerce Tracking

```javascript
// Track product views
Tracker.track('product_view', {
  product_id: '12345',
  product_name: 'Widget Pro',
  category: 'Electronics',
  price: 49.99
});

// Track add to cart
Tracker.track('add_to_cart', {
  product_id: '12345',
  quantity: 2,
  cart_total: 99.98
});
```

### Error Tracking

```javascript
// Track JavaScript errors
window.addEventListener('error', function(e) {
  Tracker.track('javascript_error', {
    message: e.message,
    filename: e.filename,
    lineno: e.lineno,
    colno: e.colno,
    stack: e.error ? e.error.stack : null
  });
});
```

## Advanced Features

### Session Management

The script automatically manages visitor and session IDs using cookies:
- `__visitor_id`: Stored for 365 days
- `__session_id`: Stored for 12 hours (resets on new session)

### Attribution Tracking

Automatically captures:
- UTM parameters (`utm_source`, `utm_medium`, `utm_campaign`, etc.)
- Google Click ID (`gclid`)
- Facebook Click ID (`fbclid`)

### SPA Support

For single-page applications, the script automatically detects page changes using:
- `popstate` events
- History API interception (`pushState`, `replaceState`)

## Privacy & Compliance

### GDPR Compliance

```javascript
// Check for consent before initializing
if (hasUserConsent()) {
  Tracker.init({
    domain: 'https://your-tracking-domain.com'
  });
}

// Or disable tracking
Tracker.init({
  domain: 'https://your-tracking-domain.com',
  disabled: true
});
```

### Cookie Management

The script uses minimal, essential cookies:
- No third-party cookies
- SameSite=Lax for security
- Reasonable expiration times

## Troubleshooting

### Common Issues

1. **Script not loading**: Check that the script URL is correct and accessible
2. **Events not tracking**: Verify the API endpoint is responding correctly
3. **CORS errors**: Ensure your server allows requests from the tracked domain
4. **Cookies blocked**: The script will still work but may create new IDs on each page load

### Debug Mode

Enable debug mode to see tracking activity in the console:

```javascript
Tracker.init({
  domain: 'https://your-tracking-domain.com',
  debug: true
});
```

## Server-Side Hosting

Host the tracking script on your server and serve it with appropriate headers:

```apache
# Apache .htaccess
<Files "tracker.js">
  Header set Cache-Control "public, max-age=3600"
  Header set Access-Control-Allow-Origin "*"
</Files>
```

```nginx
# Nginx configuration
location /tracker.js {
  add_header Cache-Control "public, max-age=3600";
  add_header Access-Control-Allow-Origin *;
}
```

## Performance Considerations

- Script is loaded asynchronously to avoid blocking page load
- Minimal footprint (~5KB gzipped)
- Batched requests to reduce server load
- Automatic retry logic for failed requests
- Cookie-based caching to reduce API calls

## Integration Checklist

- [ ] Add tracking script to website `<head>`
- [ ] Replace `YOUR_TRACKING_DOMAIN` with actual domain
- [ ] Test page view tracking
- [ ] Test custom event tracking
- [ ] Verify attribution parameter capture
- [ ] Check cookie storage
- [ ] Test on different browsers/devices
- [ ] Verify GDPR compliance
- [ ] Set up proper caching headers