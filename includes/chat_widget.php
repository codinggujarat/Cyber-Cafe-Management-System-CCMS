<!-- Chat Widget -->
<div id="chat-widget" style="position: fixed; bottom: 24px; right: 24px; z-index: 99999;" class="flex flex-col items-end gap-4 font-sans">

    <!-- Chat Window -->
    <div id="chat-window" class="hidden w-80 md:w-96 bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-200/50 overflow-hidden flex flex-col transition-all duration-300 transform origin-bottom-right scale-95 opacity-0 ring-1 ring-black/5">
        
        <!-- Header -->
        <div class="bg-black p-4 flex items-center justify-between text-white relative overflow-hidden">
            <!-- Decorative Glow -->
            <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-indigo-500/30 blur-xl"></div>
            
            <div class="flex items-center gap-3 relative z-10">
                <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center backdrop-blur-sm border border-white/10">
                    <i class="fas fa-robot text-indigo-400"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm tracking-wide">Service AI</h3>
                    <p class="text-[10px] text-gray-400 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Always Online
                    </p>
                </div>
            </div>
            <button onclick="toggleChat()" class="text-white/50 hover:text-white transition relative z-10 w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Messages Area -->
        <div id="chat-messages" class="h-80 overflow-y-auto p-4 flex flex-col gap-3 bg-gray-50/50 scroll-smooth">
            <!-- Bot Welcome -->
            <div class="flex items-start gap-2.5 max-w-[85%]">
                <div class="w-6 h-6 rounded-full bg-black flex items-center justify-center text-white text-[10px] shrink-0 mt-1 shadow-sm">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="bg-white p-3.5 rounded-2xl rounded-tl-none text-sm text-gray-700 shadow-sm border border-gray-100/50 leading-relaxed">
                    Hello! 👋 I'm your Cyber Cafe Assistant. <br>
                    <span class="text-xs text-gray-500 mt-1 block">Ask me about prices, documents, or services.</span>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-3 bg-white/80 border-t border-gray-100 backdrop-blur-sm">
            <form id="chat-form" onsubmit="sendMessage(event)" class="relative flex items-center gap-2">
                <input type="text" id="chat-input" placeholder="Type your question..." 
                    class="w-full pl-4 pr-12 py-3 bg-gray-100/80 border-0 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-black/5 focus:bg-white transition-all placeholder-gray-400 text-gray-800">
                
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center bg-black text-white rounded-lg hover:bg-gray-800 transition shadow-lg transform active:scale-95">
                    <i class="fas fa-arrow-up text-xs"></i>
                </button>
            </form>
            <div class="flex justify-center items-center gap-1.5 mt-2 opacity-50">
                <i class="fab fa-google text-[10px]"></i>
                <p class="text-[9px] font-medium tracking-wider uppercase">Powered by Gemini</p>
            </div>
        </div>
    </div>

    <!-- Toggle Button -->
    <button onclick="toggleChat()" id="chat-toggle-btn" style="background-color: black; color: white;" class="w-14 h-14 rounded-full shadow-[0_8px_30px_rgb(0,0,0,0.3)] hover:scale-110 hover:shadow-[0_20px_40px_rgb(0,0,0,0.4)] transition-all duration-300 flex items-center justify-center group relative overflow-hidden ring-4 ring-white/20">
        <div class="absolute inset-0 bg-gradient-to-tr from-indigo-500/20 to-purple-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        <i class="fas fa-comment-dots text-xl relative z-10 group-hover:rotate-12 transition-transform duration-300"></i>
        
        <!-- Notification Dot -->
        <span class="absolute top-3 right-3 w-2.5 h-2.5 bg-red-500 border-2 border-black rounded-full animate-bounce"></span>
    </button>

</div>
</div>

<script>
console.log("Chat Widget Script Loaded Successfully!");
const chatWindow = document.getElementById('chat-window');
const chatMsgs = document.getElementById('chat-messages');

function toggleChat() {
    chatWindow.classList.toggle('hidden');
    // Simple animation logic would require removing 'hidden' first then changing opacity, 
    // strictly for now we just toggle display. For smoothness we can refine later.
    if (!chatWindow.classList.contains('hidden')) {
        chatWindow.classList.remove('scale-95', 'opacity-0');
        chatWindow.classList.add('scale-100', 'opacity-100');
        document.getElementById('chat-input').focus();
    } else {
        chatWindow.classList.add('scale-95', 'opacity-0');
        chatWindow.classList.remove('scale-100', 'opacity-100');
    }
}

async function sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('chat-input');
    const msg = input.value.trim();
    if (!msg) return;

    // Append User Message
    appendMessage(msg, 'user');
    input.value = '';

    // Show Typing
    const typingId = showTyping();

    // Call API
    try {
        const res = await fetch('<?= BASE_URL ?>api/chat_bot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: msg, user_id: <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?> })
        });
        const data = await res.json();
        
        removeTyping(typingId);
        appendMessage(data.reply || "Error connecting to AI.", 'bot');

    } catch (err) {
        removeTyping(typingId);
        appendMessage("Network error. Please try again.", 'bot');
    }
}

function appendMessage(text, sender) {
    const div = document.createElement('div');
    const isUser = sender === 'user';
    
    div.className = `flex items-end gap-2 max-w-[85%] ${isUser ? 'ml-auto flex-row-reverse' : ''}`;
    
    const avatar = isUser ? '' : `
        <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs shrink-0 mb-1">
            <i class="fas fa-robot"></i>
        </div>
    `;

    const bubble = `
        <div class="p-3 rounded-2xl text-sm shadow-sm ${
            isUser 
            ? 'bg-black text-white rounded-br-none' 
            : 'bg-white text-gray-700 border border-gray-100 rounded-bl-none'
        }">
            ${text}
        </div>
    `;

    div.innerHTML = avatar + bubble;
    chatMsgs.appendChild(div);
    chatMsgs.scrollTop = chatMsgs.scrollHeight;
}

function showTyping() {
    const id = 'typing-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = "flex items-center gap-2 max-w-[85%]";
    div.innerHTML = `
        <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs shrink-0">
            <i class="fas fa-robot"></i>
        </div>
        <div class="bg-gray-100 p-3 rounded-2xl rounded-tl-none text-xs text-gray-500 animate-pulse">
            Thinking...
        </div>
    `;
    chatMsgs.appendChild(div);
    chatMsgs.scrollTop = chatMsgs.scrollHeight;
    return id;
}

function removeTyping(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}
</script>
