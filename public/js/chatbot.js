/* MarketLink AI assistant widget (Groq-powered, rule-based fallback).
   Renders bot suggestions as small clickable product cards with images. */
document.addEventListener('DOMContentLoaded', function () {
    var fab = document.getElementById('chatbotFab');
    if (!fab) return;

    var endpoint = fab.dataset.assistantUrl;

    var win = document.getElementById('chatbotWindow');
    var body = document.getElementById('chatbotBody');
    var form = document.getElementById('chatbotForm');
    var input = document.getElementById('chatbotInput');
    var closeBtn = document.getElementById('chatbotClose');

    function isOpen() { return win.classList.contains('open'); }

    function open() {
        win.classList.add('open');
        win.setAttribute('aria-hidden', 'false');
        fab.classList.add('open');
        fab.setAttribute('aria-expanded', 'true');
        input.focus();
    }

    function close() {
        win.classList.remove('open');
        win.setAttribute('aria-hidden', 'true');
        fab.classList.remove('open');
        fab.setAttribute('aria-expanded', 'false');
    }

    fab.addEventListener('click', function () { isOpen() ? close() : open(); });
    closeBtn.addEventListener('click', close);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) {
            close();
            fab.focus(); // keyboard users shouldn't lose their place on close
        }
    });

    function addMsg(text, who, links, source) {
        var div = document.createElement('div');
        div.className = 'chatbot-msg ' + who;
        div.textContent = text; // textContent — never inject markup from remote text

        if (links && links.length) {
            var wrap = document.createElement('div');
            wrap.className = 'chatbot-links mt-2 d-grid gap-2';

            links.forEach(function (l) {
                var a = document.createElement('a');
                a.href = l.url;
                a.className = 'chatbot-link-card';
                a.textContent = '🛒 ' + l.label;
                wrap.appendChild(a);
            });

            div.appendChild(wrap);
        }

        if (source === 'groq') {
            var tag = document.createElement('div');
            tag.className = 'small opacity-75 mt-1';
            tag.textContent = '✦ AI';
            div.appendChild(tag);
        }

        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
    }

    /* Typing indicator — a temporary bot bubble with animated dots */
    function showTyping() {
        var div = document.createElement('div');
        div.className = 'chatbot-msg bot chatbot-typing';
        div.setAttribute('aria-label', 'Assistant is typing');
        div.innerHTML = '<span class="chatbot-typing-dot"></span>' +
                        '<span class="chatbot-typing-dot"></span>' +
                        '<span class="chatbot-typing-dot"></span>';
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
        return div;
    }

    function setPending(pending) {
        form.classList.toggle('sending', pending);
        input.disabled = pending;
    }

    function send(message) {
        var msg = (message || '').trim();
        if (!msg || form.classList.contains('sending')) return;
        addMsg(msg, 'user');
        input.value = '';
        setPending(true);
        var typing = showTyping();

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: msg }),
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                typing.remove();
                addMsg(data.reply || 'Sorry, I could not answer that.', 'bot', data.links || [], data.source);
            })
            .catch(function () {
                typing.remove();
                addMsg(navigator.onLine
                    ? 'Sorry — I could not reach the server just now. Please try again in a moment.'
                    : 'You appear to be offline. Reconnect to the internet and try again.', 'bot');
            })
            .finally(function () {
                setPending(false);
                input.focus();
            });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        send(input.value);
    });
});
