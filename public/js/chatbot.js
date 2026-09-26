/* MarketLink rule-based assistant widget */
document.addEventListener('DOMContentLoaded', function () {
    var fab = document.getElementById('chatbotFab');
    if (!fab) return;

    var endpoint = fab.dataset.assistantUrl;

    var win = document.getElementById('chatbotWindow');
    var body = document.getElementById('chatbotBody');
    var form = document.getElementById('chatbotForm');
    var input = document.getElementById('chatbotInput');
    var closeBtn = document.getElementById('chatbotClose');

    function open() { win.classList.add('open'); input.focus(); }
    function close() { win.classList.remove('open'); }

    fab.addEventListener('click', open);
    closeBtn.addEventListener('click', close);

    function addMsg(text, who, links) {
        var div = document.createElement('div');
        div.className = 'chatbot-msg ' + who;
        div.textContent = text;
        if (links && links.length) {
            var ul = document.createElement('div');
            ul.className = 'mt-1 d-grid gap-1';
            links.forEach(function (l) {
                var a = document.createElement('a');
                a.href = l.url;
                a.textContent = '→ ' + l.label;
                div.appendChild(document.createElement('br'));
                div.appendChild(a);
            });
        }
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var msg = input.value.trim();
        if (!msg) return;
        addMsg(msg, 'user');
        input.value = '';

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ message: msg }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                addMsg(data.reply, 'bot', data.links || []);
            })
            .catch(function () {
                addMsg('Sorry, something went wrong. Please try again.', 'bot');
            });
    });
});
