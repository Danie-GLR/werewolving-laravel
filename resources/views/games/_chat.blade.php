{{--
    resources/views/games/_chat.blade.php
    Include in play.blade.php like: @include('games._chat')
    Requires: $game, $myPlayer (nullable), $isGM
--}}

@php
    $phase     = $game->phase;
    $isWolf    = $myPlayer && $myPlayer->role === 'Werewolf' && $myPlayer->is_alive;
    $isAlive   = $myPlayer && $myPlayer->is_alive;

    // Can this person write in the current channel?
    $canWrite = match(true) {
        $phase === 'day'   => $isAlive,          // all alive players
        $phase === 'night' => $isWolf,            // werewolves only
        default            => false,
    };

    // Can they read? (night chat hidden from non-wolves)
    $canRead = $phase === 'day' || $isWolf || $isGM;

    $channelLabel = $phase === 'day'
        ? '☀️ Village Chat'
        : '🐺 Werewolf Chat';
@endphp

<style>
.chat-box {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-top: 1.5rem;
    display: flex;
    flex-direction: column;
    max-height: 420px;
}
.chat-header {
    padding: .8rem 1rem;
    border-bottom: 1px solid var(--border);
    font-size: .82rem;
    font-weight: 800;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .07em;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.chat-header .phase-dot {
    width: 8px; height: 8px; border-radius: 99px;
    background: {{ $phase === 'day' ? '#f59e0b' : '#6366f1' }};
    display: inline-block;
}
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: .75rem 1rem;
    display: flex;
    flex-direction: column;
    gap: .5rem;
    min-height: 160px;
}
.chat-msg { display: flex; flex-direction: column; gap: .1rem; }
.chat-msg-meta {
    font-size: .72rem;
    font-weight: 800;
    color: var(--muted);
}
.chat-msg-meta .msg-name { color: var(--pink); }
.chat-msg-body {
    font-size: .88rem;
    background: var(--bg3);
    border-radius: 0 8px 8px 8px;
    padding: .35rem .65rem;
    display: inline-block;
    max-width: 90%;
    word-break: break-word;
}
.chat-msg.mine .chat-msg-meta { text-align: right; }
.chat-msg.mine .chat-msg-body {
    background: rgba(255,61,127,0.15);
    border-radius: 8px 0 8px 8px;
    align-self: flex-end;
}
.chat-msg.mine { align-items: flex-end; }
.chat-locked {
    text-align: center;
    color: var(--muted);
    font-size: .85rem;
    padding: 2rem 1rem;
}
.chat-input-row {
    display: flex;
    gap: .5rem;
    padding: .75rem 1rem;
    border-top: 1px solid var(--border);
}
.chat-input {
    flex: 1;
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    padding: .45rem .75rem;
    font-size: .88rem;
    outline: none;
    transition: border-color .15s;
}
.chat-input:focus { border-color: var(--pink); }
.chat-send-btn {
    background: var(--pink);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    padding: .45rem .9rem;
    font-weight: 800;
    font-size: .85rem;
    cursor: pointer;
    transition: opacity .15s;
}
.chat-send-btn:disabled { opacity: .4; cursor: default; }
</style>

<div class="chat-box" id="chat-box">
    <div class="chat-header">
        <span class="phase-dot"></span>
        {{ $channelLabel }}
        @if($phase === 'night' && !$isWolf && !$isGM)
            — <span style="font-weight:400; text-transform:none; font-size:.78rem;">hidden from non-werewolves</span>
        @endif
    </div>

    @if($canRead)
        <div class="chat-messages" id="chat-messages">
            <p style="color:var(--muted); font-size:.82rem; text-align:center;" id="chat-loading">Loading messages…</p>
        </div>

        @if($canWrite)
            <div class="chat-input-row">
                <input
                    class="chat-input"
                    type="text"
                    id="chat-input"
                    placeholder="Type a message…"
                    maxlength="300"
                    autocomplete="off"
                >
                <button class="chat-send-btn" id="chat-send">Send</button>
            </div>
        @else
            <div style="padding:.6rem 1rem; border-top:1px solid var(--border);
                        font-size:.8rem; color:var(--muted); text-align:center;">
                @if(!$isAlive)
                    You have been eliminated and cannot chat.
                @elseif($isGM)
                    GM view — read only.
                @else
                    Only alive players can chat during the day.
                @endif
            </div>
        @endif
    @else
        <div class="chat-locked">
            🌙 The village sleeps. Only werewolves may speak.
        </div>
    @endif
</div>

@if($canRead)
<script>
(function () {
    const chatUrl    = "{{ route('games.chat', $game) }}";
    const sendUrl    = "{{ route('games.chat.send', $game) }}";
    const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const myName     = @json($myPlayer ? $myPlayer->name : ($isGM ? '🎭 GM' : null));

    let lastId       = 0;
    let pollInterval = null;

    const messagesEl = document.getElementById('chat-messages');
    const inputEl    = document.getElementById('chat-input');
    const sendBtn    = document.getElementById('chat-send');

    function renderMessages(messages) {
        if (messages.length === 0 && lastId === 0) {
            messagesEl.innerHTML = '<p style="color:var(--muted);font-size:.82rem;text-align:center;">No messages yet. Say something!</p>';
            return;
        }

        // Only append new messages (those with id > lastId)
        const newMsgs = messages.filter(m => m.id > lastId);
        if (newMsgs.length === 0) return;

        // Remove placeholder
        const placeholder = messagesEl.querySelector('p');
        if (placeholder) placeholder.remove();

        newMsgs.forEach(m => {
            const isMine = m.name === myName;
            const div = document.createElement('div');
            div.className = 'chat-msg' + (isMine ? ' mine' : '');
            div.innerHTML = `
                <div class="chat-msg-meta">
                    <span class="msg-name">${escHtml(m.name)}</span>
                    <span> · ${escHtml(m.time)}</span>
                </div>
                <div class="chat-msg-body">${escHtml(m.message)}</div>
            `;
            messagesEl.appendChild(div);
            lastId = Math.max(lastId, m.id);
        });

        // Scroll to bottom
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    async function fetchMessages() {
        try {
            const res  = await fetch(chatUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.locked) renderMessages(data.messages);
        } catch (e) {
            // silently ignore network blips
        }
    }

    async function sendMessage() {
        const msg = inputEl?.value.trim();
        if (!msg) return;

        if (sendBtn) sendBtn.disabled = true;

        try {
            const res = await fetch(sendUrl, {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-CSRF-TOKEN':     csrfToken,
                },
                body: JSON.stringify({ message: msg }),
            });

            if (res.ok) {
                if (inputEl) inputEl.value = '';
                await fetchMessages();
            }
        } catch (e) {
            // ignore
        } finally {
            if (sendBtn) sendBtn.disabled = false;
            if (inputEl) inputEl.focus();
        }
    }

    // Wire up send button & Enter key
    if (sendBtn) sendBtn.addEventListener('click', sendMessage);
    if (inputEl) {
        inputEl.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });
    }

    // Initial load then poll every 5 seconds for chat (chat needs to feel live)
    fetchMessages();
    pollInterval = setInterval(fetchMessages, 5000);
})();
</script>
@endif
