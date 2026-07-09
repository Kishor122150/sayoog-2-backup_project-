/**
 * Sayog App JS - Theme, Language, Notifications
 * Handles dark/light mode toggle, Nepali/English translation, and notification enhancements
 */

// ============================================================
// THEME (Dark/Light Mode)
// ============================================================
(function() {
    const THEME_KEY = 'sayog_theme';
    const html = document.documentElement;

    // Load saved theme
    const savedTheme = localStorage.getItem(THEME_KEY) || 'light';
    if (savedTheme === 'dark') {
        html.setAttribute('data-theme', 'dark');
    }

    window.toggleTheme = function() {
        const current = html.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem(THEME_KEY, next);
        
        // Update toggle button icons
        document.querySelectorAll('.theme-toggle i').forEach(icon => {
            icon.className = next === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        });
    };
})();

// ============================================================
// LANGUAGE (Nepali/English)
// ============================================================
const LANG_KEY = 'sayog_lang';

const translations = {
    en: {
        // Navigation
        'nav.home': 'Home',
        'nav.food_listings': 'Food Listings',
        'nav.about': 'About',
        'nav.contact': 'Contact',
        'nav.login': 'Login',
        'nav.get_started': 'Get Started',
        'nav.dashboard': 'Dashboard',
        'nav.logout': 'Log Out',
        
        // Dashboard Sidebar
        'sidebar.home_feed': 'Home Feed',
        'sidebar.create_donation': 'Create Donation',
        'sidebar.approval_tracking': 'Approval Tracking',
        'sidebar.request_food': 'Request Food',
        'sidebar.manage_incoming': 'Manage Incoming Request',
        'sidebar.our_requests': 'Our Requests',
        'sidebar.track_donations': 'Track our Donations',
        'sidebar.track_requests': 'Track our Requests',
        'sidebar.notifications': 'Notifications',
        'sidebar.my_profile': 'My Profile',
        'sidebar.logout': 'Log Out',
        'sidebar.member': 'Member',
        
        // Common buttons
        'btn.submit': 'Submit',
        'btn.save': 'Save',
        'btn.cancel': 'Cancel',
        'btn.delete': 'Delete',
        'btn.edit': 'Edit',
        'btn.view_all': 'View All',
        'btn.back': 'Back',
        'btn.search': 'Search',
        'btn.view_all_listings': 'View all listings',
        'btn.send_message': 'Send Message',
        
        // Notifications
        'notif.no_notifications': 'No notifications yet',
        'notif.mark_all_read': 'Mark All as Read',
        'notif.mark_read': 'Mark as Read',
        'notif.view_details': 'View Details',
        
        // Theme & Language
        'theme.light': 'Light Mode',
        'theme.dark': 'Dark Mode',
        'lang.english': 'English',
        'lang.nepali': 'नेपाली',
        
        // Hero
        'hero.tagline': 'Empowering Communities',
        'hero.browse_listings': 'Browse Food Listings',
        'hero.member_login': 'Member Login',
        
        // Section Titles
        'section.featured_title': 'Featured Food Listings',
        'section.how_it_works': 'How Sayog Works',
        'section.quick_actions': 'Quick Actions',
        'donations.heading': 'Available Food Listings',
        'donations.description': 'Browse recent food donations that are ready for pickup or request.',
        'about.explore': 'Explore Donations',
        'about.contact_us': 'Contact Us',
        'contact.heading': 'Contact Sayoog',
        'contact.description': 'Have questions about food donation? Need help using the platform? Feel free to contact us anytime.',
        'contact.send_message': 'Send Message',
        
        // Footer
        'footer.quick_links': 'Quick Links',
        'footer.contact': 'Contact',
        'footer.follow_us': 'Follow Us',
        
        // Form labels
        'form.name': 'Full Name',
        'form.email': 'Email Address',
        'form.password': 'Password',
        'form.confirm_password': 'Confirm Password',
        'form.phone': 'Phone Number',
        'form.address': 'Address',
        'form.message': 'Message',
        'form.subject': 'Subject',
        'form.photo': 'Profile Photo',
        
        // Page titles
        'title.register': 'Create Account',
        'title.login': 'Sign In',
        'title.home': 'Home',
        'title.donations': 'Food Listings',
        'title.about': 'About',
        'title.contact': 'Contact',
        'title.profile': 'My Profile',
    },
    np: {
        // Navigation
        'nav.home': 'गृहपृष्ठ',
        'nav.food_listings': 'खाद्य सूची',
        'nav.about': 'बारेमा',
        'nav.contact': 'सम्पर्क',
        'nav.login': 'लगइन',
        'nav.get_started': 'सुरु गर्नुहोस्',
        'nav.dashboard': 'ड्यासबोर्ड',
        'nav.logout': 'बाहिर निस्कनुहोस्',
        
        // Dashboard Sidebar
        'sidebar.home_feed': 'गृह फिड',
        'sidebar.create_donation': 'दान सिर्जना गर्नुहोस्',
        'sidebar.approval_tracking': 'अनुमोदन ट्र्याकिङ',
        'sidebar.request_food': 'खाद्य अनुरोध',
        'sidebar.manage_incoming': 'आगमन अनुरोध व्यवस्थापन',
        'sidebar.our_requests': 'हाम्रो अनुरोध',
        'sidebar.track_donations': 'हाम्रो दान ट्र्याक गर्नुहोस्',
        'sidebar.track_requests': 'हाम्रो अनुरोध ट्र्याक गर्नुहोस्',
        'sidebar.notifications': 'सूचनाहरू',
        'sidebar.my_profile': 'मेरो प्रोफाइल',
        'sidebar.logout': 'बाहिर निस्कनुहोस्',
        'sidebar.member': 'सदस्य',
        
        // Common buttons
        'btn.submit': 'पेश गर्नुहोस्',
        'btn.save': 'सुरक्षित गर्नुहोस्',
        'btn.cancel': 'रद्द गर्नुहोस्',
        'btn.delete': 'मेटाउनुहोस्',
        'btn.edit': 'सम्पादन गर्नुहोस्',
        'btn.view_all': 'सबै हेर्नुहोस्',
        'btn.back': 'पछाडि',
        'btn.search': 'खोज्नुहोस्',
        'btn.view_all_listings': 'सबै सूची हेर्नुहोस्',
        'btn.send_message': 'सन्देश पठाउनुहोस्',
        
        // Notifications
        'notif.no_notifications': 'अहिलेसम्म कुनै सूचना छैन',
        'notif.mark_all_read': 'सबै पढिएको चिन्ह लगाउनुहोस्',
        'notif.mark_read': 'पढिएको चिन्ह लगाउनुहोस्',
        'notif.view_details': 'विवरण हेर्नुहोस्',
        
        // Theme & Language
        'theme.light': 'उज्यालो मोड',
        'theme.dark': 'अँध्यारो मोड',
        'lang.english': 'English',
        'lang.nepali': 'नेपाली',
        
        // Hero
        'hero.tagline': 'समुदायलाई सशक्त बनाउँदै',
        'hero.browse_listings': 'खाद्य सूची ब्राउज गर्नुहोस्',
        'hero.member_login': 'सदस्य लगइन',
        
        // Section Titles
        'section.featured_title': 'विशेष खाद्य सूचीहरू',
        'section.how_it_works': 'Sayog कसरी काम गर्छ',
        'section.quick_actions': 'द्रुत कार्यहरू',
        'donations.heading': 'उपलब्ध खाद्य सूचीहरू',
        'donations.description': 'पिकअप वा अनुरोधको लागि तयार रहेका हालैका खाद्य दानहरू ब्राउज गर्नुहोस्।',
        'about.explore': 'दानहरू अन्वेषण गर्नुहोस्',
        'about.contact_us': 'हामीलाई सम्पर्क गर्नुहोस्',
        'contact.heading': 'Sayoog सम्पर्क',
        'contact.description': 'खाद्य दानको बारेमा प्रश्नहरू छन्? प्लेटफर्म प्रयोग गर्न मद्दत चाहिन्छ? कृपया जुनसुकै बेला हामीलाई सम्पर्क गर्नुहोस्।',
        'contact.send_message': 'सन्देश पठाउनुहोस्',
        
        // Footer
        'footer.quick_links': 'द्रुत लिङ्कहरू',
        'footer.contact': 'सम्पर्क',
        'footer.follow_us': 'हामीलाई पछ्याउनुहोस्',
        
        // Form labels
        'form.name': 'पूरा नाम',
        'form.email': 'इमेल ठेगाना',
        'form.password': 'पासवर्ड',
        'form.confirm_password': 'पासवर्ड पुष्टि गर्नुहोस्',
        'form.phone': 'फोन नम्बर',
        'form.address': 'ठेगाना',
        'form.message': 'सन्देश',
        'form.subject': 'विषय',
        'form.photo': 'प्रोफाइल फोटो',
        
        // Page titles
        'title.register': 'खाता सिर्जना गर्नुहोस्',
        'title.login': 'साइन इन',
        'title.home': 'गृहपृष्ठ',
        'title.donations': 'खाद्य सूची',
        'title.about': 'बारेमा',
        'title.contact': 'सम्पर्क',
        'title.profile': 'मेरो प्रोफाइल',
    }
};

(function() {
    let currentLang = localStorage.getItem(LANG_KEY) || 'en';
    
    // Apply saved language on load
    document.documentElement.setAttribute('lang', currentLang === 'np' ? 'ne' : 'en');
    applyTranslation(currentLang);

    window.toggleLanguage = function() {
        currentLang = currentLang === 'en' ? 'np' : 'en';
        localStorage.setItem(LANG_KEY, currentLang);
        document.documentElement.setAttribute('lang', currentLang === 'np' ? 'ne' : 'en');
        applyTranslation(currentLang);
        
        // Update toggle button text
        document.querySelectorAll('.lang-toggle span').forEach(span => {
            span.textContent = currentLang === 'en' ? 'नेपाली' : 'English';
        });
        document.querySelectorAll('.lang-toggle').forEach(btn => {
            if (currentLang === 'np') {
                btn.style.background = 'rgba(16, 185, 129, 0.15)';
            } else {
                btn.style.background = 'rgba(59, 130, 246, 0.1)';
            }
        });
    };

    function applyTranslation(lang) {
        const dict = translations[lang] || translations.en;
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (dict[key]) {
                if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                    el.setAttribute('placeholder', dict[key]);
                } else {
                    el.textContent = dict[key];
                }
            }
        });
        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            const key = el.getAttribute('data-i18n-title');
            if (dict[key]) {
                el.setAttribute('title', dict[key]);
            }
        });
    }

    // Expose for dynamic elements
    window._t = function(key) {
        return translations[currentLang]?.[key] || translations.en[key] || key;
    };
    
    window.getCurrentLang = function() {
        return currentLang;
    };
})();

// ============================================================
// NOTIFICATION ENHANCEMENTS (Per-notification mark as read)
// ============================================================
(function() {
    // Mark single notification as read via AJAX
    window.markNotificationRead = function(notifId, btnEl) {
        const formData = new FormData();
        formData.append('action', 'mark_notification_read');
        formData.append('notification_id', notifId);
        
        fetch('dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const card = btnEl.closest('.notification-card');
                if (card) {
                    card.classList.remove('unread');
                    card.classList.add('read');
                }
                btnEl.remove();
                // Refresh badge count
                if (window.refreshNotifBadge) window.refreshNotifBadge();
            }
        })
        .catch(() => {});
    };
})();

// ============================================================
// PROFILE PHOTO PREVIEW
// ============================================================
(function() {
    window.previewProfilePhoto = function(input) {
        const preview = document.getElementById('profilePhotoPreview');
        if (!preview || !input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    };
})();

// ============================================================
// INTERACTIVE MAP — Leaflet + OpenStreetMap with distance calculation
// ============================================================
(function() {
    'use strict';

    var _mapCache = {};
    var _markerMap = {}; // id -> { marker, lat, lng, donation }
    var _donationsMap = null;
    var _mapInstance = null;
    var _mapUserLat = null;
    var _mapUserLng = null;

    /**
     * Calculate distance between two coordinates using the Haversine formula
     */
    function haversineDistance(lat1, lng1, lat2, lng2) {
        var R = 6371; // Earth's radius in km
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    /**
     * Geocode an address using Nominatim (OpenStreetMap)
     * Returns a promise with {lat, lng, displayName}
     */
    function geocodeAddress(address) {
        if (_mapCache[address]) {
            return Promise.resolve(_mapCache[address]);
        }
        var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' +
                  encodeURIComponent(address + ', Nepal') + '&limit=1';
        return fetch(url, {
            headers: { 'Accept-Language': 'en' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data && data.length > 0) {
                var result = {
                    lat: parseFloat(data[0].lat),
                    lng: parseFloat(data[0].lon),
                    displayName: data[0].display_name
                };
                _mapCache[address] = result;
                return result;
            }
            // Try without 'Nepal' suffix for international addresses
            var url2 = 'https://nominatim.openstreetmap.org/search?format=json&q=' +
                  encodeURIComponent(address) + '&limit=1';
            return fetch(url2, {
                headers: { 'Accept-Language': 'en' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data2) {
                if (data2 && data2.length > 0) {
                    var result2 = {
                        lat: parseFloat(data2[0].lat),
                        lng: parseFloat(data2[0].lon),
                        displayName: data2[0].display_name
                    };
                    _mapCache[address] = result2;
                    return result2;
                }
                return null;
            })
            .catch(function() { return null; });
        })
        .catch(function() {
            return null;
        });
    }

    /**
     * Filter donations by name and location search
     */
    window.filterDonations = function(filterType) {
        var nameInput = document.getElementById('searchFoodName');
        var locationInput = document.getElementById('searchLocation');
        var nameQuery = nameInput ? nameInput.value.toLowerCase().trim() : '';
        var locationQuery = locationInput ? locationInput.value.toLowerCase().trim() : '';

        // Update active filter chip
        document.querySelectorAll('.filter-chip').forEach(function(chip) {
            chip.classList.remove('active');
        });
        if (filterType === 'all' || filterType === 'nearby') {
            var activeChip = document.querySelector('.filter-chip[data-filter="' + filterType + '"]');
            if (activeChip) activeChip.classList.add('active');
        }

        var visibleCount = 0;
        var visibleBounds = [];

        document.querySelectorAll('.product-card').forEach(function(card) {
            var foodItem = (card.getAttribute('data-food-item') || '').toLowerCase();
            var pickupAddress = (card.getAttribute('data-pickup-address') || '').toLowerCase();
            var donationId = card.getAttribute('data-donation-id');

            var matchesName = !nameQuery || foodItem.indexOf(nameQuery) !== -1;
            var matchesLocation = !locationQuery || pickupAddress.indexOf(locationQuery) !== -1;

            var showCard = matchesName && matchesLocation;

            if (showCard && filterType === 'nearby') {
                // For nearby filter, only show if we have user location AND distance data
                var markerData = _markerMap[donationId];
                if (!_mapUserLat || !_mapUserLng) {
                    // Geolocation not available yet
                    showCard = false;
                } else if (markerData) {
                    var dist = haversineDistance(_mapUserLat, _mapUserLng, markerData.lat, markerData.lng);
                    showCard = dist <= 10; // Within 10 km
                } else {
                    showCard = false;
                }
            }

            if (showCard) {
                card.style.display = '';
                visibleCount++;

                // Show/hide corresponding map marker
                var markerData = _markerMap[donationId];
                if (markerData && markerData.marker) {
                    markerData.marker.setOpacity(1);
                    visibleBounds.push([markerData.lat, markerData.lng]);
                }
            } else {
                card.style.display = 'none';
                var markerData = _markerMap[donationId];
                if (markerData && markerData.marker) {
                    markerData.marker.setOpacity(0.2);
                }
            }
        });

        // Update count badge
        var countBadge = document.getElementById('mapResultsCount');
        if (countBadge) {
            var total = document.querySelectorAll('.product-card').length;
            countBadge.textContent = visibleCount + ' / ' + total + ' donations';
        }

        // Fit map to visible markers
        if (_mapInstance && visibleBounds.length > 0) {
            _mapInstance.fitBounds(visibleBounds, { padding: [50, 50], maxZoom: 15 });
        }
    };

    /**
     * Initialize a map with multiple markers (donations listing page)
     */
    window.initDonationsMap = function(elementId, donations) {
        var container = document.getElementById(elementId);
        if (!container || !donations || !donations.length) return;

        // Show loading
        container.innerHTML = '<div class="map-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading map...</div>';

        // Load Leaflet dynamically
        if (typeof L === 'undefined') {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);

            var script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = function() { buildMap(); };
            document.head.appendChild(script);
        } else {
            buildMap();
        }

        function buildMap() {
            // Get user's location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    _mapUserLat = pos.coords.latitude;
                    _mapUserLng = pos.coords.longitude;

                    // Add user marker to map
                    if (_mapInstance) {
                        var userIcon = L.divIcon({
                            className: 'user-location-marker',
                            html: '<div style="background:#3b82f6;color:#fff;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);font-size:13px;"><i class="fa-solid fa-location-dot"></i></div>',
                            iconSize: [28, 28],
                            iconAnchor: [14, 14]
                        });
                        L.marker([_mapUserLat, _mapUserLng], { icon: userIcon }).addTo(_mapInstance)
                            .bindPopup('<strong>You are here</strong>');
                    }
                }, function() {
                    // Geolocation failed
                }, { timeout: 8000 });
            }

            container.innerHTML = '';
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            _mapInstance = L.map(container).setView([27.7172, 85.3240], 11); // Default: Kathmandu

            // Use dark tiles in dark mode
            var tileUrl = isDark
                ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
                : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
            var tileAttribution = isDark
                ? '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>'
                : '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

            L.tileLayer(tileUrl, {
                attribution: tileAttribution,
                maxZoom: 18
            }).addTo(_mapInstance);

            var bounds = [];
            var geocodePromises = [];
            _markerMap = {};

            // Process geocode requests with concurrency limit (max 2 at a time)
            // to respect Nominatim's usage policy (~1 req/sec)
            function processGeocodeQueue(queue, concurrency) {
                var idx = 0;

                function next() {
                    if (idx >= queue.length) return Promise.resolve();
                    var batch = [];
                    for (var j = 0; j < concurrency && idx < queue.length; j++, idx++) {
                        // Use IIFE to capture current item value (avoids var closure bug)
                        (function() {
                            var currentIdx = idx;
                            var item = queue[currentIdx];
                            var delay = j * 400;
                            var p = new Promise(function(resolve) {
                                setTimeout(function() {
                                    geocodeAddress(item.address).then(function(coords) {
                                        item.coords = coords;
                                        resolve();
                                    });
                                }, delay);
                            });
                            batch.push(p);
                        })();
                    }
                    return Promise.all(batch).then(function() {
                        return next();
                    });
                }

                return next();
            }

            var geocodeQueue = [];
            donations.forEach(function(d) {
                if (!d || typeof d !== 'object') return;
                var address = d.address || d.pickup_address || '';
                if (!address) return;
                geocodeQueue.push({ id: d.id, address: address, food_item: d.food_item, d: d });
            });

            var totalToGeocode = geocodeQueue.length;
            processGeocodeQueue(geocodeQueue, 2).then(function() {
                geocodeQueue.forEach(function(item) {
                    if (item.coords) {
                        var coords = item.coords;
                        var leafletIcon = L.divIcon({
                            className: 'custom-marker',
                            html: '<div style="background:#059669;color:#fff;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);font-size:14px;"><i class="fa-solid fa-utensils"></i></div>',
                            iconSize: [32, 32],
                            iconAnchor: [16, 16],
                            popupAnchor: [0, -20]
                        });

                        var marker = L.marker([coords.lat, coords.lng], { icon: leafletIcon }).addTo(_mapInstance);
                        bounds.push([coords.lat, coords.lng]);

                        // Store in marker map for filtering
                        _markerMap[item.id] = { marker: marker, lat: coords.lat, lng: coords.lng, donation: item.d };

                        var distText = '';
                        if (_mapUserLat && _mapUserLng) {
                            var dist = haversineDistance(_mapUserLat, _mapUserLng, coords.lat, coords.lng);
                            distText = '<div class="popup-distance">📍 ' + dist.toFixed(1) + ' km away</div>';

                            // Update distance on the card
                            var distEl = document.querySelector('[data-donation-id="' + item.id + '"] .donation-distance');
                            if (distEl) {
                                distEl.innerHTML = '<i class="fa-solid fa-location-dot"></i> ' + dist.toFixed(1) + ' km away';
                            }
                        }

                        var googleMapsLink = 'https://www.google.com/maps/dir/?api=1&destination=' +
                            encodeURIComponent(item.address);

                        marker.bindPopup(
                            '<strong>' + (item.food_item || 'Food Donation') + '</strong>' +
                            '<div class="popup-address">' + item.address + '</div>' +
                            distText +
                            '<div style="margin-top:8px;display:flex;gap:6px;">' +
                            '<a href="donation.php?id=' + item.id + '" class="popup-link"><i class="fa-solid fa-eye"></i> View</a>' +
                            '<a href="' + googleMapsLink + '" target="_blank" class="popup-link" style="background:rgba(59,130,246,0.08);color:#3b82f6;"><i class="fa-solid fa-map-pin"></i> Navigate</a>' +
                            '</div>'
                        );
                    }
                });

                if (bounds.length > 0) {
                    _mapInstance.fitBounds(bounds, { padding: [40, 40] });
                }
            });

            // Fix map rendering after load
            setTimeout(function() { _mapInstance.invalidateSize(); }, 500);
        }
    };

    /**
     * Initialize a map for a single donation (detail page)
     */
    window.initSingleDonationMap = function(elementId, address, foodItem, donationId) {
        var container = document.getElementById(elementId);
        if (!container || !address) return;

        container.innerHTML = '<div class="map-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading map...</div>';

        if (typeof L === 'undefined') {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);

            var script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = function() { buildSingleMap(); };
            document.head.appendChild(script);
        } else {
            buildSingleMap();
        }

        function buildSingleMap() {
            geocodeAddress(address).then(function(coords) {
                container.innerHTML = '';

                var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                var map = L.map(container);

                var tileUrl = isDark
                    ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
                    : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                var tileAttribution = isDark
                    ? '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>'
                    : '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

                L.tileLayer(tileUrl, {
                    attribution: tileAttribution,
                    maxZoom: 18
                }).addTo(map);

                if (coords) {
                    map.setView([coords.lat, coords.lng], 15);

                    var leafletIcon = L.divIcon({
                        className: 'custom-marker',
                        html: '<div style="background:#059669;color:#fff;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);font-size:16px;"><i class="fa-solid fa-utensils"></i></div>',
                        iconSize: [36, 36],
                        iconAnchor: [18, 18],
                        popupAnchor: [0, -22]
                    });

                    var googleMapsLink = 'https://www.google.com/maps/dir/?api=1&destination=' +
                        encodeURIComponent(address);

                    L.marker([coords.lat, coords.lng], { icon: leafletIcon }).addTo(map)
                        .bindPopup(
                            '<strong>' + (foodItem || 'Pickup Location') + '</strong>' +
                            '<div class="popup-address">' + address + '</div>' +
                            '<a href="' + googleMapsLink + '" target="_blank" class="popup-link"><i class="fa-solid fa-map-pin"></i> Open in Google Maps</a>'
                        )
                        .openPopup();
                } else {
                    map.setView([27.7172, 85.3240], 12);
                    if (container.querySelector('.map-geocode-error') === null) {
                        container.insertAdjacentHTML('beforeend',
                            '<div class="map-geocode-error" style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);background:var(--surface,#fff);padding:10px 18px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.15);font-size:13px;color:var(--text-muted,#94a3b8);z-index:1000;">' +
                            '<i class="fa-solid fa-triangle-exclamation"></i> Could not locate this address on the map. ' +
                            '<a href="https://www.google.com/maps/search/' + encodeURIComponent(address) + '" target="_blank" style="color:#3b82f6;font-weight:600;text-decoration:none;">Search on Google Maps</a>' +
                            '</div>'
                        );
                    }
                }

                setTimeout(function() { map.invalidateSize(); }, 300);
            });
        }
    };
})();

// ============================================================
// SEARCH & FILTER — Real-time donation filtering by name/location
// ============================================================
(function() {
    'use strict';

    function initSearch() {
        var nameInput = document.getElementById('searchFoodName');
        var locationInput = document.getElementById('searchLocation');

        if (!nameInput && !locationInput) return;

        function doFilter() {
            if (typeof window.filterDonations === 'function') {
                // Determine current filter type
                var activeChip = document.querySelector('.filter-chip.active');
                var filterType = activeChip ? activeChip.getAttribute('data-filter') : 'all';
                window.filterDonations(filterType);
            }
        }

        if (nameInput) {
            nameInput.addEventListener('input', doFilter);
        }
        if (locationInput) {
            locationInput.addEventListener('input', doFilter);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSearch);
    } else {
        initSearch();
    }
})();

// ============================================================
// EXPIRY COUNTDOWN — Real-time donation expiry timers
// ============================================================
(function() {
    'use strict';

    function initCountdowns() {
        const elements = document.querySelectorAll('[data-expiry]');
        if (!elements.length) return;

        function updateAll() {
            const now = new Date().getTime();
            elements.forEach(function(el) {
                const expiryStr = el.getAttribute('data-expiry');
                if (!expiryStr) return;

                // Parse ISO/datetime string for cross-browser compatibility
                const expiry = new Date(expiryStr.replace(' ', 'T')).getTime();
                if (isNaN(expiry)) return;

                const diff = expiry - now;

                if (diff <= 0) {
                    el.innerHTML = '\u274C Donation Expired';
                    el.className = 'countdown-badge countdown-expired';
                    return;
                }

                var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((diff % (1000 * 60)) / 1000);

                var text = '\u23F3 Expires in ';
                if (days > 0) text += days + 'd ';
                text += hours + 'h ' + minutes + 'm ' + seconds + 's';

                el.innerHTML = text;
                el.className = 'countdown-badge';
                if (days === 0 && hours < 1) {
                    el.classList.add('countdown-urgent');
                }
            });
        }

        updateAll();
        setInterval(updateAll, 1000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCountdowns);
    } else {
        initCountdowns();
    }
})();
