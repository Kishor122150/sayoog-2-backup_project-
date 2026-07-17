/**
 * chatbot/chatbot.js
 * Frontend JavaScript Module for the Sayog AI Chatbot.
 * 
 * Features:
 * - Floating chat button with animated pulse
 * - Expandable chat window with smooth animations
 * - User and bot avatars with typing indicator
 * - Auto-scroll to latest messages
 * - Suggested questions as clickable buttons
 * - Unread message badge
 * - Session-based conversation persistence
 * - Minimize/Maximize support
 * - Responsive design
 * - Dark mode compatibility
 */

(function() {
    'use strict';

    // ============================================================
    // CONFIGURATION
    // ============================================================
    var CONFIG = {
        apiUrl: 'chatbot/api.php',
        botName: 'Sayog Assistant',
        botAvatar: null, // Will use emoji if null
        userAvatar: null, // Will use initials if null
        typingDelay: 800,   // ms before bot "types"
        minTypingDelay: 400,
        maxMessageLength: 500,
        storageKey: 'sayog_chatbot_session',
        suggestions: [
            'What is Sayog?',
            'How to donate food',
            'Available food',
            'Platform Statistics',
            'Become a Volunteer',
            'Contact Info'
        ]
    };

    // ============================================================
    // STATE
    // ============================================================
    var state = {
        isOpen: false,
        isMinimized: false,
        isProcessing: false,
        unreadCount: 0,
        messages: [],
        sessionId: generateSessionId()
    };

    // ============================================================
    // DOM REFERENCES (populated on init)
    // ============================================================
    var els = {};

    // ============================================================
    // UTILITY FUNCTIONS
    // ============================================================

    function generateSessionId() {
        return 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getCurrentPage() {
        return window.location.pathname.split('/').pop() || 'index.php';
    }

    function getStorage(key) {
        try {
            return JSON.parse(sessionStorage.getItem(key));
        } catch(e) {
            return null;
        }
    }

    function setStorage(key, value) {
        try {
            sessionStorage.setItem(key, JSON.stringify(value));
        } catch(e) {}
    }

    // ============================================================
    // CREATE DOM ELEMENTS
    // ============================================================

    function createChatWidget() {
        // ── Container ──
        var container = document.createElement('div');
        container.id = 'sayog-chatbot-container';
        container.className = 'sayog-chatbot-container';
        container.setAttribute('aria-label', 'Sayog AI Chatbot');

        // ── Floating Button ──
        var button = document.createElement('button');
        button.id = 'sayog-chatbot-btn';
        button.className = 'sayog-chatbot-btn';
        button.setAttribute('aria-label', 'Open chat assistant');
        button.innerHTML = '<i class="fa-solid fa-comment-dots"></i>';
        
        // Unread badge
        var badge = document.createElement('span');
        badge.id = 'sayog-chatbot-badge';
        badge.className = 'sayog-chatbot-badge';
        badge.style.display = 'none';
        badge.textContent = '1';
        button.appendChild(badge);

        // ── Chat Window ──
        var window = document.createElement('div');
        window.id = 'sayog-chatbot-window';
        window.className = 'sayog-chatbot-window';
        window.setAttribute('role', 'dialog');
        window.setAttribute('aria-label', 'Chat with Sayog Assistant');

        // Header
        var header = document.createElement('div');
        header.className = 'sayog-chatbot-header';
        header.innerHTML = '' +
            '<div class="sayog-chatbot-header-info">' +
                '<div class="sayog-chatbot-avatar sayog-chatbot-bot-avatar">' +
                    '<i class="fa-solid fa-robot"></i>' +
                '</div>' +
                '<div class="sayog-chatbot-header-text">' +
                    '<div class="sayog-chatbot-header-name">' + escapeHtml(CONFIG.botName) + '</div>' +
                    '<div class="sayog-chatbot-header-status">Online</div>' +
                '</div>' +
            '</div>' +
            '<div class="sayog-chatbot-header-actions">' +
                '<button class="sayog-chatbot-action-btn" id="sayog-chatbot-minimize" aria-label="Minimize chat">' +
                    '<i class="fa-solid fa-minus"></i>' +
                '</button>' +
                '<button class="sayog-chatbot-action-btn" id="sayog-chatbot-close" aria-label="Close chat">' +
                    '<i class="fa-solid fa-xmark"></i>' +
                '</button>' +
            '</div>';

        // Messages area
        var messages = document.createElement('div');
        messages.id = 'sayog-chatbot-messages';
        messages.className = 'sayog-chatbot-messages';
        messages.setAttribute('role', 'log');
        messages.setAttribute('aria-live', 'polite');

        // Input area
        var inputArea = document.createElement('div');
        inputArea.className = 'sayog-chatbot-input-area';
        inputArea.innerHTML = '' +
            '<div class="sayog-chatbot-input-wrapper">' +
                '<textarea id="sayog-chatbot-input" class="sayog-chatbot-input" placeholder="Type your message..." rows="1" aria-label="Type your message"></textarea>' +
                '<button id="sayog-chatbot-send" class="sayog-chatbot-send-btn" aria-label="Send message">' +
                    '<i class="fa-solid fa-paper-plane"></i>' +
                '</button>' +
            '</div>' +
            '<div class="sayog-chatbot-footer-text">' +
                'Powered by Sayog AI' +
            '</div>';

        window.appendChild(header);
        window.appendChild(messages);
        window.appendChild(inputArea);

        container.appendChild(button);
        container.appendChild(window);

        document.body.appendChild(container);

        // Store refs
        els.container = container;
        els.button = button;
        els.badge = badge;
        els.window = window;
        els.messages = messages;
        els.input = document.getElementById('sayog-chatbot-input');
        els.sendBtn = document.getElementById('sayog-chatbot-send');
        els.minimizeBtn = document.getElementById('sayog-chatbot-minimize');
        els.closeBtn = document.getElementById('sayog-chatbot-close');
    }

    // ============================================================
    // MESSAGE RENDERING
    // ============================================================

    function addMessage(role, text, isTyping) {
        var msgDiv = document.createElement('div');
        msgDiv.className = 'sayog-chatbot-message sayog-chatbot-message-' + role;

        var avatar = document.createElement('div');
        avatar.className = 'sayog-chatbot-avatar';
        
        if (role === 'bot') {
            avatar.innerHTML = '<i class="fa-solid fa-robot"></i>';
        } else {
            avatar.innerHTML = '<i class="fa-solid fa-user"></i>';
        }

        var content = document.createElement('div');
        content.className = 'sayog-chatbot-message-content';
        
        var bubble = document.createElement('div');
        bubble.className = 'sayog-chatbot-bubble';
        
        if (isTyping) {
            bubble.className += ' sayog-chatbot-typing';
            bubble.innerHTML = '<span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>';
        } else {
            bubble.innerHTML = text;
        }
        
        content.appendChild(bubble);

        var time = document.createElement('div');
        time.className = 'sayog-chatbot-time';
        time.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        if (role === 'bot') {
            msgDiv.appendChild(avatar);
            msgDiv.appendChild(content);
            content.appendChild(time);
        } else {
            msgDiv.appendChild(content);
            msgDiv.appendChild(avatar);
            content.appendChild(time);
        }

        els.messages.appendChild(msgDiv);
        scrollToBottom();
        return msgDiv;
    }

    function addSuggestions(suggestions) {
        if (!suggestions || suggestions.length === 0) return;

        var container = document.createElement('div');
        container.className = 'sayog-chatbot-suggestions';
        
        suggestions.forEach(function(s) {
            var btn = document.createElement('button');
            btn.className = 'sayog-chatbot-suggestion-btn';
            btn.textContent = s.text || s;
            btn.addEventListener('click', function() {
                sendMessage(s.text || s);
            });
            container.appendChild(btn);
        });

        els.messages.appendChild(container);
        scrollToBottom();
    }

    function showTypingIndicator() {
        var typingDiv = document.createElement('div');
        typingDiv.id = 'sayog-chatbot-typing-indicator';
        typingDiv.className = 'sayog-chatbot-message sayog-chatbot-message-bot';
        typingDiv.innerHTML = '' +
            '<div class="sayog-chatbot-avatar"><i class="fa-solid fa-robot"></i></div>' +
            '<div class="sayog-chatbot-message-content">' +
                '<div class="sayog-chatbot-bubble sayog-chatbot-typing">' +
                    '<span class="typing-dot"></span>' +
                    '<span class="typing-dot"></span>' +
                    '<span class="typing-dot"></span>' +
                '</div>' +
            '</div>';
        els.messages.appendChild(typingDiv);
        scrollToBottom();
    }

    function hideTypingIndicator() {
        var indicator = document.getElementById('sayog-chatbot-typing-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    function scrollToBottom() {
        els.messages.scrollTop = els.messages.scrollHeight;
    }

    // ============================================================
    // CHAT LOGIC
    // ============================================================

    function sendMessage(message) {
        if (state.isProcessing) return;
        if (!message || message.trim() === '') return;

        message = message.trim();
        
        if (message.length > CONFIG.maxMessageLength) {
            message = message.substr(0, CONFIG.maxMessageLength);
        }

        // Add user message
        addMessage('user', escapeHtml(message));
        els.input.value = '';
        autoResizeInput();

        state.isProcessing = true;

        // Show typing indicator
        showTypingIndicator();

        // Determine delay based on message length
        var delay = Math.min(
            CONFIG.typingDelay + message.length * 2,
            CONFIG.typingDelay * 3
        );

        // Call API
        var xhr = new XMLHttpRequest();
        xhr.open('POST', CONFIG.apiUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            hideTypingIndicator();
            state.isProcessing = false;

            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    
                    setTimeout(function() {
                        if (response.success && response.message) {
                            addMessage('bot', response.message);
                            
                            // Add suggestions
                            if (response.suggestions && response.suggestions.length > 0) {
                                addSuggestions(response.suggestions);
                            }
                            
                            // Handle action
                            if (response.action) {
                                handleAction(response.action, response.data);
                            }
                        } else if (response.message) {
                            addMessage('bot', response.message);
                        } else {
                            addMessage('bot', 'I had trouble processing that. Please try again.');
                        }
                    }, 200);
                } catch (e) {
                    addMessage('bot', 'I encountered an error. Please try again.');
                }
            } else {
                addMessage('bot', 'Connection error. Please check your internet and try again.');
                state.isProcessing = false;
            }
        };

        xhr.onerror = function() {
            hideTypingIndicator();
            addMessage('bot', 'Network error. Please try again.');
            state.isProcessing = false;
        };

        var params = 'message=' + encodeURIComponent(message);
        xhr.send(params);
    }

    function handleAction(action, data) {
        // Future: handle actions like redirect, open form, etc.
        if (action === 'redirect' && data && data.url) {
            window.location.href = data.url;
        }
    }

    function autoResizeInput() {
        els.input.style.height = 'auto';
        els.input.style.height = Math.min(els.input.scrollHeight, 120) + 'px';
    }

    function addWelcomeMessage() {
        addMessage('bot', 
            '👋 Hello! I\'m <strong>Sayog Assistant</strong>, your AI-powered guide.<br><br>' +
            'I can help you with:<br>' +
            '🔹 Learning about Sayog<br>' +
            '🔹 Donating food<br>' +
            '🔹 Requesting food<br>' +
            '🔹 Registration & Login<br>' +
            '🔹 Platform Statistics<br>' +
            '🔹 Becoming a Volunteer<br><br>' +
            'What would you like to know? 😊'
        );
        
        addSuggestions(CONFIG.suggestions);
    }

    // ============================================================
    // WINDOW MANAGEMENT
    // ============================================================

    function openChat() {
        state.isOpen = true;
        state.isMinimized = false;
        els.window.classList.add('sayog-chatbot-window-open');
        els.window.classList.remove('sayog-chatbot-window-minimized');
        els.button.style.display = 'none';
        els.badge.style.display = 'none';
        state.unreadCount = 0;
        
        els.input.focus();

        // Add welcome message if first open
        if (els.messages.children.length === 0) {
            addWelcomeMessage();
        }
    }

    function closeChat() {
        state.isOpen = false;
        els.window.classList.remove('sayog-chatbot-window-open');
        els.button.style.display = 'flex';
        state.unreadCount = 1;
        els.badge.textContent = '1';
        els.badge.style.display = 'flex';
    }

    function minimizeChat() {
        state.isMinimized = true;
        els.window.classList.add('sayog-chatbot-window-minimized');
        // Show button again
        els.button.style.display = 'flex';
        els.badge.style.display = 'none';
    }

    function restoreFromMinimized() {
        state.isMinimized = false;
        els.window.classList.remove('sayog-chatbot-window-minimized');
        els.button.style.display = 'none';
        els.input.focus();
    }

    // ============================================================
    // EVENT HANDLERS
    // ============================================================

    function initEvents() {
        // Toggle chat button
        els.button.addEventListener('click', openChat);

        // Close button
        els.closeBtn.addEventListener('click', closeChat);

        // Minimize button
        els.minimizeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            minimizeChat();
        });

        // Restore from minimized when clicking the button
        els.button.addEventListener('click', function() {
            if (state.isMinimized) {
                restoreFromMinimized();
            } else {
                openChat();
            }
        });

        // Send on button click
        els.sendBtn.addEventListener('click', function() {
            sendMessage(els.input.value);
        });

        // Send on Enter (Shift+Enter for newline)
        els.input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage(els.input.value);
            }
        });

        // Auto-resize input
        els.input.addEventListener('input', autoResizeInput);

        // Save session data periodically
        setInterval(function() {
            setStorage(CONFIG.storageKey, {
                sessionId: state.sessionId,
                messages: els.messages.innerHTML,
                timestamp: Date.now()
            });
        }, 5000);

        // Restore session
        var saved = getStorage(CONFIG.storageKey);
        if (saved && saved.messages && Date.now() - saved.timestamp < 1800000) { // 30 min expiry
            try {
                els.messages.innerHTML = saved.messages;
            } catch(e) {}
        }
    }

    // ============================================================
    // INITIALIZATION
    // ============================================================

    function init() {
        // Don't initialize on admin pages (separate integration)
        if (window.location.pathname.indexOf('/admin/') !== -1 || 
            window.location.pathname.indexOf('admin.php') !== -1) {
            return;
        }

        // Don't initialize if chatbot already exists
        if (document.getElementById('sayog-chatbot-container')) {
            return;
        }

        // Create DOM elements
        createChatWidget();

        // Initialize events
        initEvents();

        // Auto-open chat on small screens after a short delay? No, let user click.
        // But show the badge to attract attention
        setTimeout(function() {
            if (!state.isOpen && els.badge) {
                els.badge.style.display = 'flex';
            }
        }, 3000);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
