<?php
// includes/chatbot_widget.php
?>
<style>
    /* Chatbot Widget Styles */
    #chatbot-widget {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999;
        font-family: 'Inter', sans-serif;
    }

    /* Floating Toggle Button */
    #chatbot-toggle {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #8a2be2, #4facfe);
        border-radius: 50%;
        box-shadow: 0 8px 24px rgba(138, 43, 226, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
    }

    #chatbot-toggle:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 12px 30px rgba(138, 43, 226, 0.6);
    }

    #chatbot-toggle ion-icon {
        font-size: 32px;
        color: white;
        transition: transform 0.3s;
    }

    #chatbot-toggle.open ion-icon {
        transform: rotate(45deg);
        /* Turn message icon into close icon if specific icon used, or just rotate */
    }

    /* Chat Window */
    #chat-window {
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 350px;
        height: 500px;
        background: #1a1a1d;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform: scale(0);
        transform-origin: bottom right;
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        pointer-events: none;
    }

    #chat-window.active {
        transform: scale(1);
        opacity: 1;
        pointer-events: all;
    }

    /* Header */
    .chat-header {
        padding: 15px 20px;
        background: linear-gradient(135deg, rgba(138, 43, 226, 0.1), rgba(79, 172, 254, 0.1));
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .chat-title h4 {
        margin: 0;
        color: white;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chat-status {
        width: 8px;
        height: 8px;
        background: #00ff88;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 8px #00ff88;
    }

    .chat-actions ion-icon {
        color: #aaa;
        font-size: 20px;
        cursor: pointer;
        transition: color 0.2s;
        margin-left: 10px;
    }

    .chat-actions ion-icon:hover {
        color: white;
    }

    /* Messages Area */
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 15px;
        scroll-behavior: smooth;
    }

    .message {
        max-width: 80%;
        padding: 10px 15px;
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.4;
        position: relative;
        word-wrap: break-word;
    }

    .message.bot {
        align-self: flex-start;
        background: rgba(255, 255, 255, 0.05);
        color: #eee;
        border-bottom-left-radius: 2px;
    }

    .message.user {
        align-self: flex-end;
        background: var(--accent-color, #8a2be2);
        color: white;
        border-bottom-right-radius: 2px;
    }

    /* Typing Indicator */
    .typing-indicator {
        display: flex;
        gap: 4px;
        padding: 12px 15px;
        background: rgba(255, 255, 255, 0.05);
        width: fit-content;
        border-radius: 12px;
        border-bottom-left-radius: 2px;
        margin-bottom: 10px;
        display: none;
        /* Hidden by default */
    }

    .typing-dot {
        width: 6px;
        height: 6px;
        background: #aaa;
        border-radius: 50%;
        animation: typing 1.4s infinite ease-in-out both;
    }

    .typing-dot:nth-child(1) {
        animation-delay: -0.32s;
    }

    .typing-dot:nth-child(2) {
        animation-delay: -0.16s;
    }

    @keyframes typing {

        0%,
        80%,
        100% {
            transform: scale(0);
        }

        40% {
            transform: scale(1);
        }
    }

    /* Action Button within Bot Message */
    .chat-action-btn {
        display: inline-block;
        margin-top: 8px;
        padding: 6px 12px;
        background: rgba(138, 43, 226, 0.2);
        color: #cbb2fe;
        border: 1px solid rgba(138, 43, 226, 0.4);
        border-radius: 6px;
        font-size: 12px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .chat-action-btn:hover {
        background: rgba(138, 43, 226, 0.4);
        color: white;
    }

    /* Input Area */
    .chat-input-area {
        padding: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        background: #151518;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #chat-input {
        flex: 1;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 10px 15px;
        color: white;
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s;
    }

    #chat-input:focus {
        border-color: var(--accent-color);
    }

    #chat-send {
        background: transparent;
        border: none;
        color: var(--accent-color);
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        transition: transform 0.2s;
    }

    #chat-send:active {
        transform: scale(0.9);
    }

    /* Quick Replies */
    .quick-replies {
        padding: 10px 20px;
        display: flex;
        gap: 8px;
        overflow-x: auto;
        white-space: nowrap;
    }

    .quick-replies::-webkit-scrollbar {
        height: 4px;
    }

    .quick-replies::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    .chip {
        padding: 6px 12px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        font-size: 12px;
        color: #ccc;
        cursor: pointer;
        transition: all 0.2s;
    }

    .chip:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-color: rgba(255, 255, 255, 0.2);
    }
</style>

<div id="chatbot-widget">
    <!-- Chat Window -->
    <div id="chat-window">
        <div class="chat-header">
            <div class="chat-title">
                <h4>
                    <span class="chat-status"></span>
                    ShelfBot
                </h4>
            </div>
            <div class="chat-actions">
                <ion-icon name="refresh-outline" title="Clear Chat" onclick="clearChat()"></ion-icon>
                <ion-icon name="close-outline" onclick="toggleChat()"></ion-icon>
            </div>
        </div>

        <div class="chat-messages" id="chat-messages">
            <!-- Messages will appear here -->
            <div class="message bot">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    Hi Admin! 👋 I'm **ShelfBot**. I can help you check approvals, revenue, or platform stats.
                <?php else: ?>
                    Hi! 👋 I'm **ShelfBot**. I can help you find assets, check your balance, or manage your library.
                <?php endif; ?>
            </div>
        </div>

        <div class="typing-indicator" id="typing-indicator">
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        </div>

        <div class="quick-replies">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="chip" onclick="sendQuickReply('Pending approvals')">Pending Approvals</div>
                <div class="chip" onclick="sendQuickReply('Revenue stats')">Total Revenue</div>
                <div class="chip" onclick="sendQuickReply('What can you do?')">Bot Help</div>
            <?php else: ?>
                <div class="chip" onclick="sendQuickReply('What is my balance?')">Balance</div>
                <div class="chip" onclick="sendQuickReply('Show my library')">My Library</div>
                <div class="chip" onclick="sendQuickReply('What is popular?')">Trending</div>
                <div class="chip" onclick="sendQuickReply('How do I upload?')">Selling</div>
            <?php endif; ?>
        </div>

        <div class="chat-input-area">
            <input type="text" id="chat-input" placeholder="Type a message..." autocomplete="off">
            <button id="chat-send" onclick="sendMessage()">
                <ion-icon name="send"></ion-icon>
            </button>
        </div>
    </div>

    <!-- Toggle Button -->
    <div id="chatbot-toggle" onclick="toggleChat()">
        <ion-icon name="chatbubble-ellipses"></ion-icon>
    </div>
</div>

<script>
    const chatWindow = document.getElementById('chat-window');
    const chatInput = document.getElementById('chat-input');
    const messagesContainer = document.getElementById('chat-messages');
    const typingIndicator = document.getElementById('typing-indicator');

    // Toggle Chat Visibility
    function toggleChat() {
        chatWindow.classList.toggle('active');
        const icon = document.querySelector('#chatbot-toggle ion-icon');

        if (chatWindow.classList.contains('active')) {
            icon.name = 'close-outline';
            chatInput.focus();
        } else {
            icon.name = 'chatbubble-ellipses';
        }
    }

    // Enter key to send
    chatInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Send Quick Reply
    function sendQuickReply(text) {
        chatInput.value = text;
        sendMessage();
    }

    // Core Send Logic
    function sendMessage() {
        const text = chatInput.value.trim();
        if (!text) return;

        // Add User Message
        addMessage(text, 'user');
        chatInput.value = '';

        // Show Typing
        showTyping();

        // Send to Backend
        fetch('api/chat_logic.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: text,
                role: '<?php echo $_SESSION['role'] ?? 'guest'; ?>'
            })
        })
            .then(response => response.json())
            .then(data => {
                setTimeout(() => { 
                    hideTyping();
                    addMessage(data.response, 'bot', data.action);
                }, 600 + Math.random() * 400);
            })
            .catch(error => {
                console.error('Error:', error);
                hideTyping();
                addMessage("Sorry, I'm having trouble connecting to the server.", 'bot');
            });
    }

    function addMessage(text, sender, action = null) {
        const div = document.createElement('div');
        div.className = `message ${sender}`;

        // Format: **Bold** and \n to <br>
        let formattedText = text.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
        formattedText = formattedText.replace(/\n/g, '<br>');
        div.innerHTML = formattedText;

        if (action) {
            const btn = document.createElement('a');
            btn.className = 'chat-action-btn';
            btn.href = action.url;
            btn.textContent = action.text;
            div.appendChild(document.createElement('br'));
            div.appendChild(btn);
        }

        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    function showTyping() {
        typingIndicator.style.display = 'flex';
        messagesContainer.appendChild(typingIndicator); 
        scrollToBottom();
    }

    function hideTyping() {
        typingIndicator.style.display = 'none';
        // Move typing indicator back after messages if needed, but display: none handles it
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function clearChat() {
        messagesContainer.innerHTML = `<div class="message bot">Hi! 👋 I'm **ShelfBot**. How can I help you today?</div>`;
        scrollToBottom();
    }
</script>