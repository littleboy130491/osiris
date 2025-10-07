// public/js/tracker.js
(function() {
    'use strict';
    
    // Configuration
    var API_ENDPOINT = import.meta.env.VITE_API_URL;
    var DEBUG = false; // Set to true for development
    
    // Initialize global function
    window.osiris = window.osiris || function() {
        (window.osiris.q = window.osiris.q || []).push(arguments);
    };

    // UUID generation
    function generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        var d = new Date().getTime();
        var d2 = (performance && performance.now && (performance.now() * 1000)) || 0;
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
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
    }

    // Cookie helpers
    function getCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
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
    }

    // Get or create visitor ID
    function getOrCreateVisitorId() {
        var visitorId = getCookie('osiris_visitor_uuid');
        if (visitorId) {
            var uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            if (uuidRegex.test(visitorId)) {
                return visitorId;
            }
        }
        visitorId = generateUUID();
        setCookie('osiris_visitor_uuid', visitorId, 365);
        return visitorId;
    }

    // Get or create session ID
    function getOrCreateSessionId() {
        var sessionId = null;
        try {
            sessionId = sessionStorage.getItem('osiris_session_uuid');
        } catch (e) {
            sessionId = getCookie('osiris_session_uuid');
        }
        if (sessionId) {
            var uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            if (uuidRegex.test(sessionId)) {
                return sessionId;
            }
        }
        sessionId = generateUUID();
        try {
            sessionStorage.setItem('osiris_session_uuid', sessionId);
        } catch (e) {
            setCookie('osiris_session_uuid', sessionId, 0.02);
        }
        return sessionId;
    }

    // Fix getUrlParams
    function getUrlParameters() {
        var params = {};
        try {
            var searchParams = new URLSearchParams(window.location.search);
            searchParams.forEach(function(value, key) {
                params[key] = value;
            });
        } catch (e) {
            // Fallback for older browsers
            var search = window.location.search.substring(1);
            if (search) {
                var pairs = search.split('&');
                for (var i = 0; i < pairs.length; i++) {
                    var pair = pairs[i].split('=');
                    if (pair[0]) {
                        var key = decodeURIComponent(pair[0]);
                        var value = pair[1] ? decodeURIComponent(pair[1].replace(/\+/g, ' ')) : '';
                        params[key] = value;
                    }
                }
            }
        }
        return params;
    }

    // Main tracking function
    function track(eventName, eventData, userInfo) {
        if (DEBUG) console.log('🔥 Osiris tracking:', eventName, eventData);
        
        var payload = {
            event: eventName,
            url: window.location.href,
            referrer: document.referrer || null,
            visitor_uuid: getOrCreateVisitorId(),
            session_uuid: getOrCreateSessionId(),
            visitor_name: (userInfo && userInfo.name) || null,
            visitor_email: (userInfo && userInfo.email) || null,
            visitor_phone: (userInfo && userInfo.phone) || null,
            query_strings: getUrlParameters(),
            data: eventData || {}
        };

        // Send to your Laravel API
        fetch(API_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(function(response) {
            if (DEBUG) {
                if (response.ok) {
                    console.log('✅ Osiris tracking success:', eventName);
                } else {
                    console.error('❌ Osiris tracking failed:', response.status);
                }
            }
        })
        .catch(function(error) {
            if (DEBUG) console.error('❌ Osiris tracking error:', error);
        });
    }

    // Check if object is Arguments-like
    function isArgumentsObject(obj) {
        return obj && 
               typeof obj === 'object' && 
               typeof obj.length === 'number' && 
               obj.length >= 0 && 
               (obj.toString() === '[object Arguments]' || 
                (obj.callee && typeof obj.callee === 'function') ||
                (obj[0] === 'event' && typeof obj[1] === 'string'));
    }

    // DataLayer integration
    function setupDataLayerIntegration() {
        window.dataLayer = window.dataLayer || [];
        var originalPush = window.dataLayer.push;
        
        window.dataLayer.push = function() {
            var result = originalPush.apply(window.dataLayer, arguments);
            
            // Handle direct arguments format
            if (arguments.length >= 2 && arguments[0] === 'event' && typeof arguments[1] === 'string') {
                var eventName = arguments[1];
                var eventData = arguments[2] || {};
                
                if (!eventName.startsWith('gtm.')) {
                    track(eventName, eventData);
                }
            } else {
                // Handle standard format and Arguments objects
                for (var i = 0; i < arguments.length; i++) {
                    var data = arguments[i];
                    
                    if (isArgumentsObject(data)) {
                        if (data.length >= 2 && data[0] === 'event' && typeof data[1] === 'string') {
                            var eventName = data[1];
                            var eventData = data[2] || {};
                            
                            if (!eventName.startsWith('gtm.')) {
                                track(eventName, eventData);
                            }
                        }
                    } else if (data && data.event && !data.event.startsWith('gtm.')) {
                        track(data.event, data);
                    }
                }
            }
            
            return result;
        };
        
        // Process existing dataLayer events
        for (var i = 0; i < window.dataLayer.length; i++) {
            var data = window.dataLayer[i];
            
            if (isArgumentsObject(data)) {
                if (data.length >= 2 && data[0] === 'event' && typeof data[1] === 'string') {
                    var eventName = data[1];
                    if (!eventName.startsWith('gtm.')) {
                        track(eventName, data[2] || {});
                    }
                }
            } else if (data && data.event && !data.event.startsWith('gtm.')) {
                track(data.event, data);
            }
        }
    }

    // Public API
    var osirisTracker = {
        track: track,
        identify: function(userInfo) {
            // Store user info for future events
            osirisTracker.userInfo = userInfo;
            // Also send identify event
            track('identify', {}, userInfo);
        },
        debug: function(enabled) {
            DEBUG = enabled;
        }
    };

    // Process queued commands
    if (window.osiris.q) {
        window.osiris.q.forEach(function(args) {
            var command = args[0];
            if (command === 'track') {
                track(args[1], args[2], args[3]);
            } else if (command === 'identify') {
                osirisTracker.identify(args[1]);
            } else if (command === 'debug') {
                osirisTracker.debug(args[1]);
            }
        });
    }
    
    // Replace queued function with real API
    window.osiris = function(command) {
        var args = Array.prototype.slice.call(arguments, 1);
        if (command === 'track') {
            track.apply(null, args);
        } else if (command === 'identify') {
            osirisTracker.identify.apply(null, args);
        } else if (command === 'debug') {
            osirisTracker.debug.apply(null, args);
        }
    };

    // Setup dataLayer integration
    setupDataLayerIntegration();
    
    // Auto-track page view
    track('page_view', {
        page_title: document.title,
        page_location: window.location.href
    });

    if (DEBUG) console.log('🔥 Osiris Tracker initialized');
})();