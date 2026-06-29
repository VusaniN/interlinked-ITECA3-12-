<!-- Floating Chat Bot -->
<div id="interlinked-chatbot" class="interlinked-chatbot shadow-lg d-none">
    <div class="chatbot-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="bot-avatar">🤖</div>
            <div>
                <div class="fw-700" style="color:#000">Interlinked Assistant</div>
                <small style="color:rgba(0,0,0,.6);font-weight:600">Always active</small>
            </div>
        </div>
        <button class="btn btn-sm text-dark border-0 p-0" id="close-chat" style="font-size:1.2rem">✕</button>
    </div>

    <div class="chatbot-messages" id="chat-messages"></div>

    <div class="chatbot-suggestions p-2 d-flex flex-wrap gap-1">
        <button class="suggest-chip" onclick="sendQuick('How to sell?')">Selling</button>
        <button class="suggest-chip" onclick="sendQuick('QR payments')">Payments</button>
        <button class="suggest-chip" onclick="sendQuick('Account verify')">Trust</button>
    </div>

    <div class="chatbot-input p-3">
        <div class="input-group">
            <input type="text" id="chat-input" class="form-control border-0" placeholder="Type a message..." style="background:rgba(255,255,255,0.05)">
            <button class="btn btn-primary" id="send-chat" style="border-radius:0 12px 12px 0">
                <i data-feather="send" style="width:16px"></i>
            </button>
        </div>
    </div>
</div>

<button id="open-chat" class="chat-toggle-btn animate-float">
    <i data-feather="message-circle"></i>
</button>

<style>
.chat-toggle-btn {
    position: fixed;
    right: 30px;
    bottom: 30px;
    width: 65px;
    height: 65px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, var(--accent), var(--accent-hover));
    color: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    box-shadow: 0 15px 35px rgba(212, 175, 55, 0.4);
    transition: var(--transition);
}
.chat-toggle-btn:hover { transform: scale(1.1); }

.interlinked-chatbot {
    position: fixed;
    bottom: 110px;
    right: 30px;
    width: 380px;
    max-width: calc(100vw - 60px);
    height: 550px;
    z-index: 1051;
    border-radius: 24px;
    overflow: hidden;
    background: var(--surface);
    border: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    animation: slideUp 0.4s ease-out;
}

.chatbot-header {
    background: var(--accent);
    padding: 1.2rem;
}

.bot-avatar {
    width: 40px;
    height: 40px;
    background: rgba(0,0,0,0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.chatbot-messages {
    flex-grow: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 16px;
    scrollbar-width: thin;
}

.chatbot-suggestions {
    border-top: 1px solid rgba(255,255,255,0.05);
    background: rgba(0,0,0,0.1);
}

.suggest-chip {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.85);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    transition: var(--transition);
}
.suggest-chip:hover {
    background: var(--accent);
    color: #000;
    border-color: var(--accent);
}

.chat-msg {
    max-width: 85%;
    padding: 1rem 1.2rem;
    border-radius: 18px;
    font-size: .92rem;
    position: relative;
}

.chat-msg.bot {
    background: rgba(255,255,255,0.05);
    color: var(--text);
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}

.chat-msg.user {
    background: linear-gradient(135deg, var(--accent), var(--accent-hover));
    color: #000;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
    font-weight: 600;
}

.typing-indicator {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.85);
    font-style: italic;
    margin-bottom: 8px;
}

/* work in progress - trying to get this right */
@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const chat = document.getElementById('interlinked-chatbot');
    const openBtn = document.getElementById('open-chat');
    const closeBtn = document.getElementById('close-chat');
    const input = document.getElementById('chat-input');
    const sendBtn = document.getElementById('send-chat');
    const messages = document.getElementById('chat-messages');

    const addMessage = (text, type = 'bot') => {
        const bubble = document.createElement('div');
        bubble.className = `chat-msg ${type}`;
        // Use innerHTML to support the markdown-style bolding from PHP
        bubble.innerHTML = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
    };

    const showTyping = () => {
        const el = document.createElement('div');
        el.id = 'typing-indicator';
        el.className = 'typing-indicator';
        el.textContent = 'Assistant is thinking...';
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
        return el;
    };

    addMessage('Welcome to **Interlinked Marketplace**. I\'m here to help you buy, sell, or manage your account. What can I do for you today?');

    openBtn.addEventListener('click', () => {
        chat.classList.toggle('d-none');
        if (typeof feather !== 'undefined') feather.replace();
    });

    closeBtn.addEventListener('click', () => {
        chat.classList.add('d-none');
    });

    const respond = async (value) => {
        const typing = showTyping();
        
        try {
            const response = await fetch('<?= url('api/chatbot.php') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: value })
            });
            const data = await response.json();
            typing.remove();
            addMessage(data.response, 'bot');
        } catch (error) {
            typing.remove();
            addMessage('I\'m having trouble connecting to my brain right now. Please try again later!', 'bot');
        }
    };

    const handleSend = () => {
        const value = input.value.trim();
        if (!value) return;

        addMessage(value, 'user');
        input.value = '';
        respond(value);
    };

    window.sendQuick = (text) => {
        input.value = text;
        handleSend();
    };

    sendBtn.addEventListener('click', handleSend);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') handleSend();
    });
});
</script>

