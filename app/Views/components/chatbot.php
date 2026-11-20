<style>
/* Base Widget Container */
.chatbot-widget {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
    font-family: 'Public Sans', 'Inter', sans-serif;
}

/* 
 * Toggle Button (The Original Floating Button)
 */
.chatbot-btn {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(58, 123, 213, 0.4);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(58, 123, 213, 0.7); }
    70% { box-shadow: 0 0 0 15px rgba(58, 123, 213, 0); }
    100% { box-shadow: 0 0 0 0 rgba(58, 123, 213, 0); }
}

.chatbot-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(58, 123, 213, 0.6);
}

.chatbot-btn i {
    font-size: 32px;
}

/* 
 * The Desktop Window wrapper - Original size and position, Modern UI
 */
.chatbot-window {
    display: none;
    position: absolute;
    bottom: 85px;
    right: 0;
    width: 380px;
    height: 550px;
    background: linear-gradient(180deg, #fbfafd 0%, #f4f6fa 100%);
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.6);
    transform-origin: bottom right;
    animation: scaleIn 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
}

.chatbot-window.active {
    display: flex;
}

@keyframes scaleIn {
    0% { opacity: 0; transform: scale(0.5); }
    100% { opacity: 1; transform: scale(1); }
}

/* Modern Top Actions (Close button overlay) */
.chatbot-top-actions {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 10;
}
.chatbot-top-actions .close-btn {
    font-size: 24px;
    color: #94a3b8;
    cursor: pointer;
    background: rgba(255,255,255,0.7);
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

/* Modern Messages Area */
.chatbot-messages {
    flex-grow: 1;
    padding: 20px;
    padding-top: 55px; /* space for close button */
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
    scroll-behavior: smooth;
}
.chatbot-messages::-webkit-scrollbar {
    width: 4px;
}
.chatbot-messages::-webkit-scrollbar-track {
    background: transparent;
}
.chatbot-messages::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

/* User Message */
.chat-user-group {
    align-self: flex-end;
    max-width: 85%;
}
.chat-user-msg {
    background-color: #e6f0fa;
    color: #1e293b;
    padding: 12px 18px;
    border-radius: 20px 20px 0 20px;
    font-size: 14px;
    line-height: 1.5;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

/* Bot Message Group */
.chat-bot-group {
    align-self: flex-start;
    max-width: 90%;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.chat-bot-header {
    display: flex;
    align-items: center;
    gap: 8px;
}
/* Bot Avatar (Changed from Hexagon to Image) */
.bot-hex-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-image: url('<?= base_url('assets/img/bot-avatar.png') ?>');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    background-color: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    flex-shrink: 0;
}
.bot-hex-avatar i {
    display: none;
}
.bot-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 14px;
}

.chat-bot-msg {
    font-size: 14px;
    color: #334155;
    line-height: 1.6;
}

.chat-bot-subtext {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 2px;
}

.chat-bot-actions {
    display: flex;
    gap: 12px;
    margin-top: 4px;
    color: #94a3b8;
}
.chat-bot-actions i {
    font-size: 18px;
    cursor: pointer;
    transition: color 0.2s, transform 0.2s;
}
.chat-bot-actions i:hover {
    color: #3b82f6;
    transform: scale(1.1);
}

/* Suggestions */
.chatbot-suggestions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 10px;
    align-items: flex-start;
}
.suggestion-btn {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    color: #475569;
    padding: 10px 16px;
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
    text-align: left;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.suggestion-btn:hover {
    background-color: #f8fafc;
}

/* Modern Input Area */
.chat-input-wrapper {
    padding: 15px;
    background: transparent;
}
.chat-input-box {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid #e2e8f0;
    border-radius: 25px;
    padding: 6px 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
}
.chat-input-box input {
    flex-grow: 1;
    border: none;
    background: transparent;
    padding: 8px;
    font-size: 14px;
    color: #334155;
    outline: none;
}
.chat-input-box input::placeholder {
    color: #94a3b8;
}
.chat-input-icons {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    padding-right: 5px;
}
.chat-input-icons i {
    font-size: 22px;
    cursor: pointer;
}
.chat-input-icons .send-btn {
    color: #3b82f6;
    display: none;
}

/* Typing Dots */
.typing-dots span {
    display: inline-block;
    width: 5px;
    height: 5px;
    background-color: #94a3b8;
    border-radius: 50%;
    margin-right: 3px;
    animation: typing 1.4s infinite ease-in-out both;
}
.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }
@keyframes typing {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}
</style>

<div class="chatbot-widget">
    <div class="chatbot-window" id="chatbotWindow">
        <div class="chatbot-top-actions">
            <div class="close-btn" id="closeChat">
                <i class='bx bx-x'></i>
            </div>
        </div>
        
        <div class="chatbot-messages" id="chatbotMessages">
            
            <?php 
            $history = session()->get('chat_history') ?? [];
            if (empty($history)): 
            ?>
            <div class="chat-bot-group">
                <div class="chat-bot-header">
                    <div class="bot-hex-avatar">
                        <i class='bx bx-bar-chart-alt-2'></i>
                    </div>
                    <span class="bot-name">AJIP</span>
                </div>
                <div class="chat-bot-msg">
                    Halo! Ada yang bisa saya bantu hari ini terkait data pengadaan?
                </div>
                <div class="chat-bot-subtext">Dihasilkan oleh AI, hanya untuk referensi</div>
                <div class="chat-bot-actions">
                    <i class='bx bx-like' title="Bagus"></i>
                    <i class='bx bx-dislike' title="Buruk"></i>
                    <i class='bx bx-volume-full' title="Bacakan Teks"></i>
                    <i class='bx bx-share-alt' title="Bagikan"></i>
                    <i class='bx bx-refresh' title="Ulangi Pertanyaan Terakhir"></i>
                </div>
                
                <div class="chatbot-suggestions" id="initialSuggestions">
                    <button class="suggestion-btn" data-msg="Tampilkan Ringkasan Pagu RUP">Ringkasan Pagu RUP</button>
                    <button class="suggestion-btn" data-msg="Realisasi LPSE & E-Purchasing">Realisasi LPSE & E-Purchasing</button>
                    <button class="suggestion-btn" data-msg="Bagaimana status Monev Proyek saat ini?">Bantu saya cek status proyek</button>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($history as $msg): ?>
                    <?php if ($msg['role'] === 'system' || $msg['role'] === 'tool') continue; ?>
                    <?php 
                        $isUser = $msg['role'] === 'user';
                        $formattedText = str_replace("\n", "<br>", htmlspecialchars($msg['content']));
                        $formattedText = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $formattedText);
                    ?>
                    <?php if ($isUser): ?>
                        <div class="chat-user-group">
                            <div class="chat-user-msg"><?= $formattedText ?></div>
                        </div>
                    <?php else: ?>
                        <div class="chat-bot-group">
                            <div class="chat-bot-header">
                                <div class="bot-hex-avatar">
                                    <i class='bx bx-bar-chart-alt-2'></i>
                                </div>
                                <span class="bot-name">AJIP</span>
                            </div>
                            <div class="chat-bot-msg"><?= $formattedText ?></div>
                            <div class="chat-bot-subtext">Dihasilkan oleh AI, hanya untuk referensi</div>
                            <div class="chat-bot-actions">
                                <i class='bx bx-like' title="Bagus"></i>
                                <i class='bx bx-dislike' title="Buruk"></i>
                                <i class='bx bx-volume-full' title="Bacakan Teks"></i>
                                <i class='bx bx-share-alt' title="Bagikan"></i>
                                <i class='bx bx-refresh' title="Ulangi Pertanyaan Terakhir"></i>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="chat-bot-group" id="typingIndicator" style="display:none;">
                <div class="chat-bot-header">
                    <div class="bot-hex-avatar">
                        <i class='bx bx-bar-chart-alt-2'></i>
                    </div>
                    <span class="bot-name">AJIP</span>
                </div>
                <div class="chat-bot-msg">
                    <div class="typing-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
            
        </div>
        
        <div class="chat-input-wrapper">
            <div class="chat-input-box">
                <input type="text" id="chatInput" placeholder="Tanya saya kapan saja..." autocomplete="off">
                <div class="chat-input-icons">
                    <i class='bx bx-microphone' id="micBtn"></i>
                    <i class='bx bxs-send send-btn' id="sendMessageBtn"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="chatbot-btn" id="openChat" style="padding: 0; background: transparent; overflow: hidden;">
        <img src="<?= base_url('assets/img/bot-avatar.png') ?>" alt="Chat AJIP" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; box-shadow: 0 4px 15px rgba(58,123,213,0.5);">
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatWindow = document.getElementById('chatbotWindow');
    const closeBtn = document.getElementById('closeChat');
    const openBtn = document.getElementById('openChat');
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendMessageBtn');
    const micBtn = document.getElementById('micBtn');
    const messagesContainer = document.getElementById('chatbotMessages');
    const typingIndicator = document.getElementById('typingIndicator');

    // Toggle logic
    if (openBtn) {
        openBtn.addEventListener('click', () => {
            if (chatWindow.classList.contains('active')) {
                chatWindow.classList.remove('active');
                sessionStorage.setItem('chatbot_open', 'false');
            } else {
                chatWindow.classList.add('active');
                sessionStorage.setItem('chatbot_open', 'true');
                setTimeout(() => chatInput.focus(), 100);
            }
        });
    }

    closeBtn.addEventListener('click', () => {
        chatWindow.classList.remove('active');
        sessionStorage.setItem('chatbot_open', 'false');
    });

    // Check session
    if (sessionStorage.getItem('chatbot_open') === 'true') {
        chatWindow.classList.add('active');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Input changes
    chatInput.addEventListener('input', function() {
        if(this.value.trim() !== '' && !micBtn.classList.contains('listening')) {
            micBtn.style.display = 'none';
            sendBtn.style.display = 'block';
        } else {
            micBtn.style.display = 'block';
            sendBtn.style.display = 'none';
        }
    });

    // Voice Input (Speech Recognition)
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        const recognition = new SpeechRecognition();
        recognition.lang = 'id-ID';
        recognition.interimResults = true;
        
        micBtn.addEventListener('click', () => {
            if (micBtn.classList.contains('listening')) {
                recognition.stop();
            } else {
                recognition.start();
            }
        });
        
        recognition.onstart = function() {
            micBtn.classList.add('listening');
            micBtn.style.color = '#ef4444'; // Merah saat mendengarkan
            micBtn.classList.remove('bx-microphone');
            micBtn.classList.add('bx-microphone-off');
            chatInput.placeholder = "Mendengarkan suara Anda...";
            chatInput.dispatchEvent(new Event('input')); // Update UI buttons
        };
        
        recognition.onresult = function(event) {
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }
            chatInput.value = transcript;
            // Kita tidak trigger input event di sini agar tidak memunculkan tombol send saat mendengarkan
        };
        
        recognition.onend = function() {
            micBtn.classList.remove('listening');
            micBtn.style.color = ''; 
            micBtn.classList.add('bx-microphone');
            micBtn.classList.remove('bx-microphone-off');
            chatInput.placeholder = "Tanya saya kapan saja...";
            chatInput.dispatchEvent(new Event('input')); // Munculkan tombol send jika ada teks
        };
        
        recognition.onerror = function(event) {
            micBtn.classList.remove('listening');
            micBtn.style.color = '';
            micBtn.classList.add('bx-microphone');
            micBtn.classList.remove('bx-microphone-off');
            chatInput.placeholder = "Tanya saya kapan saja...";
            chatInput.dispatchEvent(new Event('input'));
        };
    } else {
        micBtn.addEventListener('click', () => {
            alert('Browser Anda tidak mendukung fitur Voice Input (Speech Recognition).');
        });
    }

    // Send Message
    function sendMessage() {
        const message = chatInput.value.trim();
        if(!message) return;

        appendUserMessage(message);
        chatInput.value = '';
        chatInput.dispatchEvent(new Event('input')); // trigger icon reset
        
        typingIndicator.style.display = 'flex';
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        chatInput.disabled = true;
        sendBtn.style.pointerEvents = 'none';

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
            sendBtn.style.pointerEvents = 'auto';
            chatInput.focus();

            if(data.status === 'success') {
                appendBotMessage(data.reply, true);
            } else {
                appendBotMessage('Error: ' + (data.message || 'Terjadi kesalahan.'), false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            typingIndicator.style.display = 'none';
            chatInput.disabled = false;
            sendBtn.style.pointerEvents = 'auto';
            appendBotMessage('Gagal terhubung ke server.', false);
        });
    }

    function appendUserMessage(text) {
        const div = document.createElement('div');
        div.className = 'chat-user-group';
        let formattedText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\n/g, "<br>");
        div.innerHTML = `<div class="chat-user-msg">${formattedText}</div>`;
        messagesContainer.insertBefore(div, typingIndicator);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function appendBotMessage(text, showSuggestions) {
        const div = document.createElement('div');
        div.className = 'chat-bot-group';
        
        let formattedText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, "<br>");

        div.innerHTML = `
            <div class="chat-bot-header">
                <div class="bot-hex-avatar">
                    <i class='bx bx-bar-chart-alt-2'></i>
                </div>
                <span class="bot-name">AJIP</span>
            </div>
            <div class="chat-bot-msg"></div>
            <div class="chat-bot-subtext" style="display:none;">Dihasilkan oleh AI, hanya untuk referensi</div>
            <div class="chat-bot-actions" style="display:none;">
                <i class='bx bx-like' title="Bagus"></i>
                <i class='bx bx-dislike' title="Buruk"></i>
                <i class='bx bx-volume-full' title="Bacakan Teks"></i>
                <i class='bx bx-share-alt' title="Bagikan"></i>
                <i class='bx bx-refresh' title="Ulangi Pertanyaan Terakhir"></i>
            </div>
        `;
        
        messagesContainer.insertBefore(div, typingIndicator);
        
        const msgDiv = div.querySelector('.chat-bot-msg');
        const subtext = div.querySelector('.chat-bot-subtext');
        const actions = div.querySelector('.chat-bot-actions');
        
        // Typewriter effect
        let tempDiv = document.createElement('div');
        tempDiv.innerHTML = formattedText;
        
        function typeWriterHTML(sourceNode, targetNode, callback) {
            if (!sourceNode) return callback && callback();
            let childNodes = Array.from(sourceNode.childNodes);
            function processNextNode(index) {
                if (index >= childNodes.length) return callback && callback();
                let child = childNodes[index];
                if (child.nodeType === Node.TEXT_NODE) {
                    let textContent = child.textContent;
                    let textIndex = 0;
                    let textNode = document.createTextNode('');
                    targetNode.appendChild(textNode);
                    function typeText() {
                        if (textIndex < textContent.length) {
                            textNode.textContent += textContent.charAt(textIndex);
                            textIndex++;
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            setTimeout(typeText, 10);
                        } else {
                            processNextNode(index + 1);
                        }
                    }
                    typeText();
                } else if (child.nodeType === Node.ELEMENT_NODE) {
                    let newElement = document.createElement(child.tagName);
                    Array.from(child.attributes).forEach(attr => newElement.setAttribute(attr.name, attr.value));
                    targetNode.appendChild(newElement);
                    typeWriterHTML(child, newElement, () => processNextNode(index + 1));
                } else {
                    processNextNode(index + 1);
                }
            }
            processNextNode(0);
        }

        typeWriterHTML(tempDiv, msgDiv, () => {
            subtext.style.display = 'block';
            actions.style.display = 'flex';
            
            if (showSuggestions) {
                const suggDiv = document.createElement('div');
                suggDiv.className = 'chatbot-suggestions';
                const menus = [
                    { text: "Ringkasan Pagu RUP", msg: "Tampilkan Ringkasan Pagu RUP" },
                    { text: "Realisasi LPSE", msg: "Tampilkan Realisasi LPSE dan E-Purchasing" },
                    { text: "Status Monev Proyek", msg: "Bagaimana status Monev Proyek saat ini?" }
                ];
                menus.forEach(menu => {
                    const btn = document.createElement('button');
                    btn.className = 'suggestion-btn';
                    btn.setAttribute('data-msg', menu.msg);
                    btn.textContent = menu.text;
                    suggDiv.appendChild(btn);
                });
                div.appendChild(suggDiv);
            }
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', function(e) {
        if(e.key === 'Enter') sendMessage();
    });

    // Event delegation for suggestions and bottom actions
    document.addEventListener('click', function(e) {
        // Handle suggestion clicks
        if (e.target.classList.contains('suggestion-btn')) {
            const msg = e.target.getAttribute('data-msg');
            chatInput.value = msg;
            chatInput.dispatchEvent(new Event('input'));
            sendMessage();
            
            const suggestions = e.target.closest('.chatbot-suggestions');
            if (suggestions) {
                suggestions.style.display = 'none';
            }
        }
        
        // Handle Like
        if (e.target.classList.contains('bx-like') || e.target.classList.contains('bxs-like')) {
            const isLike = e.target.classList.contains('bx-like');
            e.target.className = isLike ? 'bx bxs-like' : 'bx bx-like';
            e.target.style.color = isLike ? '#3b82f6' : '';
            
            const dislikeBtn = e.target.parentElement.querySelector('.bxs-dislike');
            if (dislikeBtn) {
                dislikeBtn.className = 'bx bx-dislike';
                dislikeBtn.style.color = '';
            }
            // Optional: you can show a toast here if you like
        }
        
        // Handle Dislike
        if (e.target.classList.contains('bx-dislike') || e.target.classList.contains('bxs-dislike')) {
            const isDislike = e.target.classList.contains('bx-dislike');
            e.target.className = isDislike ? 'bx bxs-dislike' : 'bx bx-dislike';
            e.target.style.color = isDislike ? '#ef4444' : '';
            
            const likeBtn = e.target.parentElement.querySelector('.bxs-like');
            if (likeBtn) {
                likeBtn.className = 'bx bx-like';
                likeBtn.style.color = '';
            }
        }
        
        // Handle Speak (Text-to-Speech)
        if (e.target.classList.contains('bx-volume-full')) {
            const msgText = e.target.closest('.chat-bot-group').querySelector('.chat-bot-msg').innerText;
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(msgText);
                utterance.lang = 'id-ID';
                window.speechSynthesis.speak(utterance);
            } else {
                alert('Browser Anda tidak mendukung fitur suara.');
            }
        }
        
        // Handle Share (Inline Bubble Chat)
        if (e.target.classList.contains('bx-share-alt')) {
            const botGroup = e.target.closest('.chat-bot-group');
            let msgText = botGroup.querySelector('.chat-bot-msg').innerText;
            
            // Potong teks jika ada bagian "Pilihan Lanjutan" (mengabaikan huruf besar/kecil, dan menghapus garis pemisah ---)
            const splitMatch = msgText.match(/(?:\n+[-—_]+)?\n*.*Pilihan Lanjutan/i);
            if (splitMatch) {
                msgText = msgText.substring(0, splitMatch.index).trim();
            }
            
            // Bersihkan juga garis pemisah yang tertinggal di akhir teks jika ada
            msgText = msgText.replace(/\n+[-—_]+\s*$/, '').trim();
            
            // Potong pertanyaan lanjutan dari AI di bagian akhir teks (biasanya berawalan "Apakah", "Silakan", dsb)
            msgText = msgText.replace(/\n+Apakah[\s\S]*$/, '').trim();
            msgText = msgText.replace(/\n+Ada yang[\s\S]*$/, '').trim();
            msgText = msgText.replace(/\n+Atau ingin[\s\S]*$/, '').trim();
            msgText = msgText.replace(/\n+Silakan pilih[\s\S]*$/, '').trim();
            
            const encodedText = encodeURIComponent(msgText);
            const currentUrl = encodeURIComponent(window.location.href);
            
            let shareBubble = botGroup.querySelector('.inline-share-bubble');
            if (shareBubble) {
                shareBubble.style.display = shareBubble.style.display === 'none' ? 'flex' : 'none';
            } else {
                shareBubble = document.createElement('div');
                shareBubble.className = 'inline-share-bubble';
                // Styling it like a chat bubble
                shareBubble.style.cssText = 'display:flex; justify-content:flex-start; gap:12px; margin-top:8px; padding:10px 15px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:15px 15px 15px 0; animation:scaleIn 0.2s ease-out; width:fit-content;';
                shareBubble.innerHTML = `
                    <button class="share-copy-inline" data-text="${encodedText}" title="Salin Teks" style="width:36px; height:36px; border-radius:50%; background:#fff; border:1px solid #e2e8f0; cursor:pointer; color:#334155; display:flex; align-items:center; justify-content:center; transition:0.2s; box-shadow:0 2px 5px rgba(0,0,0,0.02);">
                        <i class='bx bx-copy' style="font-size:18px;"></i>
                    </button>
                    <a href="https://api.whatsapp.com/send?text=${encodedText}" target="_blank" title="WhatsApp" style="width:36px; height:36px; border-radius:50%; background:#25D366; text-decoration:none; color:white; display:flex; align-items:center; justify-content:center; transition:0.2s; box-shadow:0 2px 5px rgba(37,211,102,0.2);">
                        <i class='bx bxl-whatsapp' style="font-size:18px;"></i>
                    </a>
                    <a href="https://t.me/share/url?url=${currentUrl}&text=${encodedText}" target="_blank" title="Telegram" style="width:36px; height:36px; border-radius:50%; background:#0088cc; text-decoration:none; color:white; display:flex; align-items:center; justify-content:center; transition:0.2s; box-shadow:0 2px 5px rgba(0,136,204,0.2);">
                        <i class='bx bxl-telegram' style="font-size:18px;"></i>
                    </a>
                    <a href="mailto:?subject=Balasan AJIP&body=${encodedText}" title="Email" style="width:36px; height:36px; border-radius:50%; background:#64748b; text-decoration:none; color:white; display:flex; align-items:center; justify-content:center; transition:0.2s; box-shadow:0 2px 5px rgba(100,116,139,0.2);">
                        <i class='bx bx-envelope' style="font-size:18px;"></i>
                    </a>
                `;
                botGroup.appendChild(shareBubble);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
        
        // Handle Inline Copy from Share Bubble
        if (e.target.closest('.share-copy-inline')) {
            const btn = e.target.closest('.share-copy-inline');
            const textToCopy = decodeURIComponent(btn.getAttribute('data-text'));
            navigator.clipboard.writeText(textToCopy).then(() => {
                const icon = btn.querySelector('i');
                const originalClass = icon.className;
                icon.className = 'bx bx-check';
                icon.style.color = '#10b981';
                setTimeout(() => {
                    icon.className = originalClass;
                    icon.style.color = '';
                }, 2000);
            });
        }
        
        // Handle Refresh (Resend last query)
        if (e.target.classList.contains('bx-refresh')) {
            const currentGroup = e.target.closest('.chat-bot-group');
            let prevNode = currentGroup.previousElementSibling;
            let lastQuery = '';
            
            while(prevNode) {
                if(prevNode.classList.contains('chat-user-group')) {
                    lastQuery = prevNode.innerText.trim();
                    break;
                }
                prevNode = prevNode.previousElementSibling;
            }
            
            if (lastQuery) {
                chatInput.value = lastQuery;
                chatInput.dispatchEvent(new Event('input'));
                sendMessage();
            } else {
                // If there's no prior query (like the greeting msg), just focus input
                chatInput.focus();
            }
        }
    });
    
    function fallbackCopy(text, iconElem) {
        navigator.clipboard.writeText(text).then(() => {
            const originalClass = iconElem.className;
            iconElem.className = 'bx bx-check';
            iconElem.style.color = '#10b981';
            setTimeout(() => {
                iconElem.className = originalClass;
                iconElem.style.color = '';
            }, 2000);
        });
    }

});
</script>
