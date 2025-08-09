// Chat System JavaScript - Improved version without blinking
class ChatBox {
    constructor() {
        this.currentConversationId = null;
        this.guestName = null;
        this.guestContact = null;
        this.sellerId = null;
        this.productId = null;
        this.isOpen = false;
        this.messagePolling = null;
        this.lastMessageId = 0; // Track the last message ID we've seen
        this.messagesCache = new Map(); // Cache messages to avoid duplicates
        this.init();
    }

    init() {
        this.createChatHTML();
        this.bindEvents();
    }

    createChatHTML() {
        const chatHTML = `
            <div id="chatPopup" class="chat-popup">
                <div class="chat-header">
                    <div class="seller-info">
                        <div class="seller-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="seller-details">
                            <h4 id="sellerName">Seller</h4>
                            <span id="sellerStatus" class="status online">Online</span>
                        </div>
                    </div>
                    <button id="closeChatBtn" class="close-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <div class="welcome-message">
                        <div class="welcome-content">
                            <i class="fas fa-comments"></i>
                            <h3>Start a conversation</h3>
                            <p>Ask questions about products or get help from our sellers</p>
                        </div>
                    </div>
                </div>

                <div class="chat-input-container">
                    <div class="chat-input-wrapper">
                        <input type="text" id="messageInput" placeholder="Type your message...">
                        <button id="sendMessageBtn" class="send-btn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Guest Info Modal -->
            <div id="guestInfoModal" class="modal-overlay">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Start Chatting</h3>
                        <button class="close-modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="guestInfoForm">
                            <div class="form-group">
                                <label for="guestNameInput">Your Name *</label>
                                <input type="text" id="guestNameInput" required placeholder="Enter your name">
                            </div>
                            <div class="form-group">
                                <label for="guestContactInput">Contact (Phone/Email)</label>
                                <input type="text" id="guestContactInput" placeholder="Your phone or email">
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn-cancel">Cancel</button>
                                <button type="submit" class="btn-start">Start Chat</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Chat Trigger Button -->
            <div id="chatTrigger" class="chat-trigger">
                <i class="fas fa-comments"></i>
                <span class="chat-badge" id="chatBadge" style="display: none;">1</span>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', chatHTML);
    }

    bindEvents() {
        // Chat trigger button
        document.getElementById('chatTrigger').addEventListener('click', () => {
            this.toggleChat();
        });

        // Close chat button
        document.getElementById('closeChatBtn').addEventListener('click', () => {
            this.closeChat();
        });

        // Send message
        document.getElementById('sendMessageBtn').addEventListener('click', () => {
            this.sendMessage();
        });

        // Enter key to send message
        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });

        // Guest info form
        document.getElementById('guestInfoForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.startConversation();
        });

        // Modal close buttons
        document.querySelectorAll('.close-modal, .btn-cancel').forEach(btn => {
            btn.addEventListener('click', () => {
                this.hideGuestInfoModal();
            });
        });

        // Click outside modal to close
        document.getElementById('guestInfoModal').addEventListener('click', (e) => {
            if (e.target.id === 'guestInfoModal') {
                this.hideGuestInfoModal();
            }
        });
    }

    openChat(sellerId, productId = null) {
        this.sellerId = sellerId;
        this.productId = productId;

        if (!this.guestName) {
            this.showGuestInfoModal();
        } else {
            this.showChat();
            if (!this.currentConversationId) {
                this.createConversation();
            }
        }

        this.loadSellerInfo();
    }

    showGuestInfoModal() {
        document.getElementById('guestInfoModal').style.display = 'flex';
    }

    hideGuestInfoModal() {
        document.getElementById('guestInfoModal').style.display = 'none';
    }

    async startConversation() {
        const nameInput = document.getElementById('guestNameInput');
        const contactInput = document.getElementById('guestContactInput');

        this.guestName = nameInput.value.trim();
        this.guestContact = contactInput.value.trim();

        if (!this.guestName) {
            alert('Please enter your name');
            return;
        }

        this.hideGuestInfoModal();
        this.showChat();
        await this.createConversation();
    }

    async createConversation() {
        try {
            const response = await fetch('chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'start_conversation',
                    guest_name: this.guestName,
                    guest_contact: this.guestContact,
                    seller_id: this.sellerId,
                    product_id: this.productId
                })
            });

            const data = await response.json();

            if (data.success) {
                this.currentConversationId = data.conversation_id;
                this.loadMessages(true); // Initial load - replace all content
                this.startMessagePolling();
            } else {
                console.error('Error creating conversation:', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async loadSellerInfo() {
        try {
            const response = await fetch('chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_seller_info',
                    seller_id: this.sellerId
                })
            });

            const data = await response.json();

            if (data.success) {
                const seller = data.seller;
                const sellerName = seller.business_name || `${seller.first_name} ${seller.last_name}`;
                document.getElementById('sellerName').textContent = sellerName;
                
                if (seller.stall_number) {
                    document.getElementById('sellerName').textContent += ` (Stall ${seller.stall_number})`;
                }
            }
        } catch (error) {
            console.error('Error loading seller info:', error);
        }
    }

    async sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();

        if (!message || !this.currentConversationId) {
            return;
        }

        // Disable send button temporarily to prevent spam
        const sendBtn = document.getElementById('sendMessageBtn');
        const originalContent = sendBtn.innerHTML;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        sendBtn.disabled = true;

        try {
            const response = await fetch('chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'send_message',
                    conversation_id: this.currentConversationId,
                    sender_name: this.guestName,
                    message: message
                })
            });

            const data = await response.json();

            if (data.success) {
                messageInput.value = '';
                // Force an immediate check for new messages
                this.checkForNewMessages();
            } else {
                console.error('Error sending message:', data.message);
                alert('Failed to send message. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to send message. Please try again.');
        } finally {
            // Re-enable send button
            sendBtn.innerHTML = originalContent;
            sendBtn.disabled = false;
        }
    }

    async loadMessages(forceReplace = false) {
        if (!this.currentConversationId) return;

        try {
            const response = await fetch('chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_messages',
                    conversation_id: this.currentConversationId,
                    after_id: forceReplace ? 0 : this.lastMessageId // Get messages after last known ID
                })
            });

            const data = await response.json();

            if (data.success) {
                if (forceReplace || data.messages.length === 0) {
                    // First load or no new messages
                    this.displayMessages(data.messages, forceReplace);
                } else {
                    // Append new messages only
                    this.appendNewMessages(data.messages);
                }
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    // New method to check for new messages without blinking
    async checkForNewMessages() {
        if (!this.currentConversationId) return;

        try {
            const response = await fetch('chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_messages',
                    conversation_id: this.currentConversationId,
                    after_id: this.lastMessageId
                })
            });

            const data = await response.json();

            if (data.success && data.messages.length > 0) {
                this.appendNewMessages(data.messages);
            }
        } catch (error) {
            console.error('Error checking for new messages:', error);
        }
    }

    displayMessages(messages, forceReplace = false) {
        const chatMessages = document.getElementById('chatMessages');
        
        if (messages.length === 0 && forceReplace) {
            return;
        }

        if (forceReplace) {
            // Clear cache and reset last message ID
            this.messagesCache.clear();
            this.lastMessageId = 0;
        }

        let html = '';
        messages.forEach(message => {
            // Cache the message to avoid duplicates
            this.messagesCache.set(message.id, message);
            
            // Update last message ID
            if (message.id > this.lastMessageId) {
                this.lastMessageId = message.id;
            }

            const isGuest = message.sender_type === 'guest';
            const senderName = isGuest ? message.sender_name : 
                              (message.seller_first_name ? `${message.seller_first_name} ${message.seller_last_name}` : 'Seller');
            
            const messageTime = new Date(message.sent_at).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });

            html += `
                <div class="message ${isGuest ? 'guest-message' : 'seller-message'}" data-message-id="${message.id}">
                    <div class="message-content">
                        <div class="message-header">
                            <span class="sender-name">${senderName}</span>
                            <span class="message-time">${messageTime}</span>
                        </div>
                        <div class="message-text">${this.escapeHtml(message.message_text)}</div>
                    </div>
                </div>
            `;
        });

        if (forceReplace) {
            chatMessages.innerHTML = html;
        } else {
            chatMessages.innerHTML += html;
        }

        this.scrollToBottom();
    }

    // New method to append only new messages
    appendNewMessages(messages) {
        if (messages.length === 0) return;

        const chatMessages = document.getElementById('chatMessages');
        let html = '';
        let hasNewMessages = false;

        messages.forEach(message => {
            // Skip if we already have this message
            if (this.messagesCache.has(message.id)) {
                return;
            }

            hasNewMessages = true;
            this.messagesCache.set(message.id, message);
            
            // Update last message ID
            if (message.id > this.lastMessageId) {
                this.lastMessageId = message.id;
            }

            const isGuest = message.sender_type === 'guest';
            const senderName = isGuest ? message.sender_name : 
                              (message.seller_first_name ? `${message.seller_first_name} ${message.seller_last_name}` : 'Seller');
            
            const messageTime = new Date(message.sent_at).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });

            html += `
                <div class="message ${isGuest ? 'guest-message' : 'seller-message'}" data-message-id="${message.id}" style="opacity: 0; transform: translateY(10px);">
                    <div class="message-content">
                        <div class="message-header">
                            <span class="sender-name">${senderName}</span>
                            <span class="message-time">${messageTime}</span>
                        </div>
                        <div class="message-text">${this.escapeHtml(message.message_text)}</div>
                    </div>
                </div>
            `;
        });

        if (hasNewMessages) {
            // Remove welcome message if it exists
            const welcomeMessage = chatMessages.querySelector('.welcome-message');
            if (welcomeMessage) {
                welcomeMessage.remove();
            }

            // Append new messages
            chatMessages.insertAdjacentHTML('beforeend', html);

            // Animate new messages
            const newMessages = chatMessages.querySelectorAll('.message[style*="opacity: 0"]');
            newMessages.forEach((msg, index) => {
                setTimeout(() => {
                    msg.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    msg.style.opacity = '1';
                    msg.style.transform = 'translateY(0)';
                }, index * 100);
            });

            this.scrollToBottom();
        }
    }

    scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTo({
            top: chatMessages.scrollHeight,
            behavior: 'smooth'
        });
    }

    startMessagePolling() {
        if (this.messagePolling) {
            clearInterval(this.messagePolling);
        }

        this.messagePolling = setInterval(() => {
            this.checkForNewMessages(); // Use the new non-blinking method
        }, 2000); // Reduced to 2 seconds for better responsiveness
    }

    stopMessagePolling() {
        if (this.messagePolling) {
            clearInterval(this.messagePolling);
            this.messagePolling = null;
        }
    }

    showChat() {
        document.getElementById('chatPopup').classList.add('open');
        document.getElementById('chatTrigger').style.display = 'none';
        this.isOpen = true;
    }

    closeChat() {
        document.getElementById('chatPopup').classList.remove('open');
        document.getElementById('chatTrigger').style.display = 'flex';
        this.stopMessagePolling();
        this.isOpen = false;
    }

    toggleChat() {
        if (this.isOpen) {
            this.closeChat();
        } else {
            if (this.currentConversationId) {
                this.showChat();
                this.loadMessages(true); // Force reload when reopening
                this.startMessagePolling();
            } else {
                // If no active conversation, show guest info modal
                this.showGuestInfoModal();
            }
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chat system
const chatBox = new ChatBox();

// Function to start chat with seller (called from product buttons)
function startChatWithSeller(sellerId, productId = null) {
    chatBox.openChat(sellerId, productId);
}