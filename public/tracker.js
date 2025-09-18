(function(window, document) {
  'use strict';

  // Tracker constructor
  function Tracker(config) {
    this.config = config || {};
    this.visitorId = this.getCookie('__visitor_id');
    this.sessionId = this.getCookie('__session_id');
    this.endpoint = (config.domain || window.location.origin) + '/api/events';
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