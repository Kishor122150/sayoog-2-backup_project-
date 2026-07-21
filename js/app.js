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
// INTERACTIVE NEPAL MAP — Leaflet + MarkerCluster + City Search
// ============================================================
(function() {
    'use strict';

    var _mapCache = {};
    var _markerMap = {};
    var _mapInstance = null;
    var _mapUserLat = null;
    var _mapUserLng = null;
    var _markerClusterGroup = null;

    var NEPAL_BOUNDS_RAW = [[26.3, 80.0], [30.5, 88.3]];
    var NEPAL_CENTER = [28.2, 84.1];

    var NEPAL_CITIES = {
        'kathmandu': [27.7172, 85.3240], 'lalitpur': [27.6588, 85.3247], 'patan': [27.6588, 85.3247],
        'bhaktapur': [27.6710, 85.4298], 'pokhara': [28.2096, 83.9856], 'bharatpur': [27.6833, 84.4333],
        'birgunj': [27.0170, 84.8660], 'biratnagar': [26.4524, 87.2718], 'janakpur': [26.7288, 85.9248],
        'nepalgunj': [28.0500, 81.6167], 'butwal': [27.6833, 83.4500], 'dharan': [26.8167, 87.2833],
        'hetauda': [27.4167, 85.0333], 'dhangadhi': [28.6833, 80.6000], 'chitwan': [27.5333, 84.3333]
    };

    function extractCity(addr) {
        if (!addr) return '';
        var a = addr.toLowerCase();
        for (var c in NEPAL_CITIES) { if (a.indexOf(c) !== -1) return c.charAt(0).toUpperCase()+c.slice(1); }
        return '';
    }

    function localGeocode(address) {
        var a = address.toLowerCase().trim();
        for (var c in NEPAL_CITIES) { if (a.indexOf(c) !== -1) return Promise.resolve({lat:NEPAL_CITIES[c][0],lng:NEPAL_CITIES[c][1]}); }
        if (_mapCache[address]) return Promise.resolve(_mapCache[address]);
        return fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(address+', Nepal')+'&limit=1',
            {headers:{'Accept-Language':'en','User-Agent':'Sayog/1.0'}})
        .then(function(r){return r.json();}).then(function(d){
            if(d&&d.length>0){var r={lat:parseFloat(d[0].lat),lng:parseFloat(d[0].lon)};_mapCache[address]=r;return r;}
            return null;
        }).catch(function(){return null;});
    }

    function hDist(lat1,lng1,lat2,lng2){var R=6371,dL=(lat2-lat1)*Math.PI/180,dLn=(lng2-lng1)*Math.PI/180,a=Math.sin(dL/2)*Math.sin(dL/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLn/2)*Math.sin(dLn/2);return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));}

    window.filterDonations = function(filterType) {
        var n=document.getElementById('searchFoodName'),l=document.getElementById('searchLocation'),c=document.getElementById('cityFilter'),r=document.getElementById('radiusFilter');
        var nq=n?n.value.toLowerCase().trim():'',lq=l?l.value.toLowerCase().trim():'',sc=c?c.value:'',rk=r?parseFloat(r.value):10;
        document.querySelectorAll('.filter-chip').forEach(function(f){f.classList.remove('active');});
        var ac=document.querySelector('.filter-chip[data-filter="'+filterType+'"]');if(ac)ac.classList.add('active');
        var vc=0,vb=[];
        document.querySelectorAll('.product-card').forEach(function(cd){
            var fi=(cd.getAttribute('data-food-item')||'').toLowerCase(),pa=(cd.getAttribute('data-pickup-address')||'').toLowerCase(),di=cd.getAttribute('data-donation-id');
            var mn=!nq||fi.indexOf(nq)!==-1,ml=!lq||pa.indexOf(lq)!==-1,mc=true;
            if(sc&&pa.indexOf(sc.toLowerCase())===-1)mc=false;
            var sh=mn&&ml&&mc;
            if(sh&&filterType==='nearby'){var md=_markerMap[di];if(!_mapUserLat||!_mapUserLng||!md||!md.lat)sh=false;else sh=hDist(_mapUserLat,_mapUserLng,md.lat,md.lng)<=rk;}
            if(sh){cd.style.display='';vc++;var md=_markerMap[di];if(md&&md.marker){md.marker.setOpacity(1);vb.push([md.lat,md.lng]);}}else{cd.style.display='none';var md=_markerMap[di];if(md&&md.marker)md.marker.setOpacity(0.2);}
        });
        var cb=document.getElementById('mapResultsCount');if(cb){cb.textContent=vc+' / '+document.querySelectorAll('.product-card').length+' donations';}
        if(_mapInstance&&vb.length>0)_mapInstance.fitBounds(L.latLngBounds(vb),{padding:[50,50],maxZoom:14});
        else if(_mapInstance)_mapInstance.setView(NEPAL_CENTER,7);
    };

    window.initDonationsMap = function(elementId, donations) {
        var container = document.getElementById(elementId);
        if (!container || !donations || !donations.length) return;

        container.innerHTML = '<div class="map-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading Nepal map...</div>';

        function ensureCluster(cb) {
            if (typeof L !== 'undefined' && typeof L.markerClusterGroup !== 'undefined') { cb(); return; }
            var css1=document.createElement('link');css1.rel='stylesheet';css1.href='https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css';document.head.appendChild(css1);
            var css2=document.createElement('link');css2.rel='stylesheet';css2.href='https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css';document.head.appendChild(css2);
            var s=document.createElement('script');s.src='https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js';s.onload=cb;s.onerror=cb;document.head.appendChild(s);
        }

        function loadLeaflet(cb) {
            if(typeof L!=='undefined'){ensureCluster(cb);return;}
            var l=document.createElement('link');l.rel='stylesheet';l.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';document.head.appendChild(l);
            var s=document.createElement('script');s.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';s.onload=function(){ensureCluster(cb);};document.head.appendChild(s);
        }

        loadLeaflet(function() {
            container.innerHTML = '';
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            _mapInstance = L.map(container, {center: NEPAL_CENTER, zoom: 7, maxBounds: L.latLngBounds(NEPAL_BOUNDS_RAW), maxBoundsViscosity: 1.0});

            L.tileLayer(isDark?'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png':'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
                attribution: isDark?'&copy; OSM &copy; CARTO':'&copy; OpenStreetMap contributors', maxZoom:18, minZoom:7
            }).addTo(_mapInstance);

            try {
                _markerClusterGroup = L.markerClusterGroup({
                    chunkedLoading:true, maxClusterRadius:50, spiderfyOnMaxZoom:true,
                    showCoverageOnHover:false, zoomToBoundsOnClick:true,
                    iconCreateFunction:function(cl){
                        var c=cl.getChildCount(),s=c<10?'small':(c<50?'medium':'large'),p=c<10?36:(c<50?44:52);
                        return L.divIcon({html:'<div class="map-cluster-icon map-cluster-icon-'+s+'" style="width:'+p+'px;height:'+p+'px;">'+c+'</div>',className:'map-cluster',iconSize:L.point(p,p)});
                    }
                });
                _mapInstance.addLayer(_markerClusterGroup);
            } catch(e) { _markerClusterGroup = null; }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos){
                    _mapUserLat=pos.coords.latitude;_mapUserLng=pos.coords.longitude;
                    if(_mapInstance){
                        var u=L.divIcon({className:'',html:'<div class="user-location-pulse"></div>',iconSize:[14,14],iconAnchor:[7,7]});
                        L.marker([_mapUserLat,_mapUserLng],{icon:u}).addTo(_mapInstance).bindPopup('<strong>You are here</strong>');
                    }
                },function(){},{timeout:8000,enableHighAccuracy:true});
            }

            _markerMap = {}; var bounds = [];
            var withCoords = [], withoutCoords = [];

            donations.forEach(function(d){
                if(!d||typeof d!=='object')return;
                var lat=d.latitude?parseFloat(d.latitude):null,lng=d.longitude?parseFloat(d.longitude):null;
                if(lat&&lng&&!isNaN(lat)&&!isNaN(lng)&&lat>=26&&lat<=31)withCoords.push({id:d.id,address:d.address||d.pickup_address,food_item:d.food_item,d:d,lat:lat,lng:lng});
                else withoutCoords.push({id:d.id,address:d.address||d.pickup_address,food_item:d.food_item,d:d});
            });

            withCoords.forEach(function(item){addMarker(item.lat,item.lng,item);bounds.push([item.lat,item.lng]);});

            function addMarker(lat,lng,item){
                if(!lat||!lng||!_mapInstance)return;
                var icon=L.divIcon({className:'custom-marker',
                    html:'<div class="donor-marker-icon"><i class="fa-solid fa-utensils"></i></div>',
                    iconSize:[36,36],iconAnchor:[18,18],popupAnchor:[0,-22]});
                var marker=L.marker([lat,lng],{icon:icon});
                if(_markerClusterGroup)_markerClusterGroup.addLayer(marker);else marker.addTo(_mapInstance);
                _markerMap[item.id]={marker:marker,lat:lat,lng:lng,donation:item.d};
                var city=extractCity(item.address),dist='';
                if(_mapUserLat&&_mapUserLng){var d=hDist(_mapUserLat,_mapUserLng,lat,lng);dist='<div class="popup-distance">📍 '+d.toFixed(1)+' km away</div>';}
                var gm='https://www.google.com/maps/dir/?api=1&destination='+encodeURIComponent(item.address);
                marker.bindPopup('<strong>'+item.food_item+'</strong><div class="popup-address">'+item.address+'</div>'+(city?'<div style="color:#059669;">🏙️ '+city+'</div>':'')+dist+
                    '<div style="margin-top:8px;display:flex;gap:6px;"><a href="/frontend/donation.php?id='+item.id+'" class="popup-link">View</a><a href="'+gm+'" target="_blank" class="popup-link" style="background:rgba(59,130,246,0.08);color:#3b82f6;">Directions</a></div>');
            }

            function processQueue(idx){
                if(idx>=withoutCoords.length){
                    if(bounds.length>0&&_mapInstance)try{_mapInstance.fitBounds(L.latLngBounds(bounds),{padding:[40,40],maxZoom:12});}catch(e){}
                    setTimeout(function(){if(_mapInstance)_mapInstance.invalidateSize();},300);
                    return;
                }
                var item=withoutCoords[idx];
                if(!item.address){processQueue(idx+1);return;}
                localGeocode(item.address).then(function(coords){
                    if(coords){addMarker(coords.lat,coords.lng,item);bounds.push([coords.lat,coords.lng]);}
                    else{_markerMap[item.id]={marker:null,lat:null,lng:null,donation:item.d};}
                    setTimeout(function(){processQueue(idx+1);},200);
                });
            }
            processQueue(0);
        });
    };

    window.initSingleDonationMap = function(elementId, address, foodItem, donationId, lat, lng) {
        var container = document.getElementById(elementId);
        if (!container || !address) return;
        container.innerHTML = '<div class="map-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading map...</div>';

        function loadLeaflet(cb){if(typeof L!=='undefined'){cb();return;}
            var l=document.createElement('link');l.rel='stylesheet';l.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';document.head.appendChild(l);
            var s=document.createElement('script');s.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';s.onload=cb;document.head.appendChild(s);}

        loadLeaflet(function(){
            if(lat&&lng&&!isNaN(lat)&&!isNaN(lng)){renderMap(parseFloat(lat),parseFloat(lng));return;}
            localGeocode(address).then(function(c){if(c)renderMap(c.lat,c.lng);else renderMap(null,null);});
        });

        function renderMap(lat,lng){
            container.innerHTML='';
            var isDark=document.documentElement.getAttribute('data-theme')==='dark';
            var map=L.map(container,{maxBounds:L.latLngBounds(NEPAL_BOUNDS_RAW),maxBoundsViscosity:1.0});
            L.tileLayer(isDark?'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png':'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
                attribution:isDark?'&copy; OSM &copy; CARTO':'&copy; OpenStreetMap contributors',maxZoom:18
            }).addTo(map);
            if(lat&&lng){
                map.setView([lat,lng],15);
                var gm='https://www.google.com/maps/dir/?api=1&destination='+encodeURIComponent(address);
                var icon=L.divIcon({className:'custom-marker',html:'<div class="donor-marker-icon" style="width:40px;height:40px;font-size:18px;"><i class="fa-solid fa-utensils"></i></div>',iconSize:[40,40],iconAnchor:[20,20]});
                L.marker([lat,lng],{icon:icon}).addTo(map).bindPopup('<strong>'+foodItem+'</strong><div class="popup-address">'+address+'</div><a href="'+gm+'" target="_blank" class="popup-link" style="background:#3b82f6;color:white;">Open in Google Maps</a>').openPopup();
            } else {
                map.setView([27.7172,85.3240],12);
                if(!container.querySelector('.map-geocode-error')) container.insertAdjacentHTML('beforeend',
                    '<div class="map-geocode-error" style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);background:var(--surface,#fff);padding:10px 18px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.15);font-size:13px;color:var(--text-muted);z-index:1000;">'+
                    '<i class="fa-solid fa-triangle-exclamation"></i> Could not locate. <a href="https://www.google.com/maps/search/'+encodeURIComponent(address)+'" target="_blank" style="color:#3b82f6;font-weight:600;">Search on Google Maps</a></div>');
            }
            setTimeout(function(){map.invalidateSize();},300);
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
