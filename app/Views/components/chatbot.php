<style>
/* Chatbot Widget Styles */
.chatbot-widget {
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 1000;
    font-family: 'Public Sans', sans-serif;
}

.chatbot-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #696cff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(105, 108, 255, 0.4);
    transition: transform 0.3s ease;
}

.chatbot-btn:hover {
    transform: scale(1.1);
}

.chatbot-btn i {
    font-size: 28px;
}

.chatbot-window {
    display: none;
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 350px;
    height: 480px;
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.08);
}

.chatbot-window.active {
    display: flex;
}

.chatbot-header {
    padding: 15px 20px;
    background-color: #696cff;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}

.chatbot-header h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: white;
}

.chatbot-header .close-btn {
    cursor: pointer;
    font-size: 20px;
}

.chatbot-messages {
    flex-grow: 1;
    padding: 15px;
    overflow-y: auto;
    background-color: #f5f5f9;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.chat-message {
    max-width: 80%;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.4;
    word-wrap: break-word;
}

.chat-message.bot {
    background-color: #fff;
    color: #435971;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}

.chat-message.user {
    background-color: #696cff;
    color: white;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
    box-shadow: 0 2px 4px rgba(105, 108, 255, 0.2);
}

.chatbot-input-area {
    padding: 12px 15px;
    background-color: #fff;
    border-top: 1px solid #ebedf2;
    display: flex;
    gap: 10px;
}

.chatbot-input-area input {
    flex-grow: 1;
    padding: 10px 14px;
    border: 1px solid #d9dee3;
    border-radius: 20px;
    outline: none;
    font-size: 14px;
    transition: border-color 0.2s;
}

.chatbot-input-area input:focus {
    border-color: #696cff;
}

.chatbot-input-area button {
    background-color: #696cff;
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s;
}

.chatbot-input-area button:hover {
    background-color: #5f61e6;
}

.chatbot-input-area button:disabled {
    background-color: #a1a3ff;
    cursor: not-allowed;
}

/* Typing Indicator */
.typing-indicator {
    display: none;
    background-color: #fff;
    padding: 12px 16px;
    border-radius: 12px;
    border-bottom-left-radius: 4px;
    align-self: flex-start;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    margin-bottom: 10px;
}

.typing-indicator span {
    display: inline-block;
    width: 6px;
    height: 6px;
    background-color: #8c9ebf;
    border-radius: 50%;
    margin-right: 4px;
    animation: typing 1s infinite;
}

.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; margin-right: 0; }

@keyframes typing {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

.chatbot-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 10px 0;
    align-self: flex-start;
}

.suggestion-btn {
    background-color: #fff;
    border: 1px solid #696cff;
    color: #696cff;
    padding: 6px 12px;
    border-radius: 18px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.suggestion-btn:hover {
    background-color: #696cff;
    color: #fff;
    box-shadow: 0 4px 8px rgba(105, 108, 255, 0.2);
}
</style>

<div class="chatbot-widget">
    <div class="chatbot-window" id="chatbotWindow">
        <div class="chatbot-header">
            <h5><i class='bx bx-bot'></i>  Si- AJIP </h5>
            <i class='bx bx-x close-btn' id="closeChat"></i>
        </div>
        <div class="chatbot-messages" id="chatbotMessages">
            <div class="chat-message bot">Halo! Saya adalah (AJIP) Asisten Jenius Informasi Pengadaan. Ada yang bisa saya bantu terkait data pengadaan?</div>
            
            <div class="chatbot-suggestions" id="initialSuggestions">
                <button class="suggestion-btn" data-msg="Berapa total pagu swakelola?">Pagu Swakelola</button>
                <button class="suggestion-btn" data-msg="Berapa total pagu penyedia?">Pagu Penyedia</button>
                <button class="suggestion-btn" data-msg="Berapa total pagu tender?">Pagu Tender</button>
                <button class="suggestion-btn" data-msg="Berapa total pagu seleksi?">Pagu Seleksi</button>
                <button class="suggestion-btn" data-msg="Berapa total pagu e-purchasing?">Pagu E-Purchasing</button>
            </div>

            <div class="typing-indicator" id="typingIndicator">
                <span></span><span></span><span></span>
            </div>
        </div>
        <div class="chatbot-input-area">
            <input type="text" id="chatInput" placeholder="Ketik pesan disini..." autocomplete="off">
            <button id="sendMessageBtn"><i class='bx bx-send'></i></button>
        </div>
    </div>
    <div class="chatbot-btn" id="openChat">
        <i class='bx bx-message-dots'></i>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('openChat');
    const closeBtn = document.getElementById('closeChat');
    const chatWindow = document.getElementById('chatbotWindow');
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendMessageBtn');
    const messagesContainer = document.getElementById('chatbotMessages');
    const typingIndicator = document.getElementById('typingIndicator');

    // Toggle window
    openBtn.addEventListener('click', () => {
        chatWindow.classList.add('active');
        chatInput.focus();
    });

    closeBtn.addEventListener('click', () => {
        chatWindow.classList.remove('active');
    });

    // Send Message
    function sendMessage() {
        const message = chatInput.value.trim();
        if(!message) return;

        // Append User Message
        appendMessage(message, 'user');
        chatInput.value = '';
        
        // Show typing
        typingIndicator.style.display = 'block';
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Disable input
        chatInput.disabled = true;
        sendBtn.disabled = true;

        // POST Request
        const formData = new FormData();
        formData.append('message', message);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch('<?= base_url('chatbot/ask') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            typingIndicator.style.display = 'none';
            chatInput.disabled = false;
            sendBtn.disabled = false;
            chatInput.focus();

            if(data.status === 'success') {
                appendMessage(data.reply, 'bot');
            } else {
                appendMessage('Error: ' + (data.message || 'Terjadi kesalahan sistem.'), 'bot');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            typingIndicator.style.display = 'none';
            chatInput.disabled = false;
            sendBtn.disabled = false;
            appendMessage('Gagal terhubung ke server.', 'bot');
        });
    }

    // Append message helper
    function appendMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `chat-message ${sender}`;
        
        // Simple HTML encoding, replace newlines with br
        const escapedText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\n/g, "<br>");
        msgDiv.innerHTML = escapedText;
        
        // Add Copy button if sender is BOT
        if (sender === 'bot') {
            const btnContainer = document.createElement('div');
            btnContainer.style.marginTop = '8px';
            btnContainer.style.textAlign = 'right';
            
            const copyBtn = document.createElement('button');
            copyBtn.style.background = 'transparent';
            copyBtn.style.border = '1px solid #696cff';
            copyBtn.style.color = '#696cff';
            copyBtn.style.borderRadius = '4px';
            copyBtn.style.padding = '2px 8px';
            copyBtn.style.fontSize = '11px';
            copyBtn.style.cursor = 'pointer';
            copyBtn.innerHTML = '<i class="bx bx-copy"></i> Salin';
            
            copyBtn.addEventListener('click', () => {
                // Konversi kembali dari tampilan web ke plain text untuk dicopy
                const plainText = text;
                navigator.clipboard.writeText(plainText).then(() => {
                    const originalHTML = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="bx bx-check"></i> Tersalin!';
                    setTimeout(() => copyBtn.innerHTML = originalHTML, 2000);
                });
            });
            
            btnContainer.appendChild(copyBtn);
            msgDiv.appendChild(btnContainer);
        }
        
        // Insert before typing indicator
        messagesContainer.insertBefore(msgDiv, typingIndicator);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', function(e) {
        if(e.key === 'Enter') {
            sendMessage();
        }
    });

    // Handle suggestion clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('suggestion-btn')) {
            const msg = e.target.getAttribute('data-msg');
            chatInput.value = msg;
            sendMessage();
            
            // Hide suggestions after first use to clear space
            const suggestions = e.target.closest('.chatbot-suggestions');
            if (suggestions && suggestions.id === 'initialSuggestions') {
                suggestions.style.display = 'none';
            }
        }
    });
});
</script>
