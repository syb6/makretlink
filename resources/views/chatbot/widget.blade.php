<button class="chatbot-fab" id="chatbotFab" title="Ask MarketLink Assistant" type="button" data-assistant-url="{{ route('assistant') }}">
    <i class="bi bi-chat-dots-fill"></i>
</button>

<div class="chatbot-window" id="chatbotWindow" role="dialog" aria-label="MarketLink Assistant">
    <div class="chatbot-head">
        <i class="bi bi-robot fs-5"></i>
        <div>
            <div class="fw-semibold">MarketLink Assistant</div>
            <div class="small opacity-75">FAQs & product search</div>
        </div>
        <button class="btn-close btn-close-white ms-auto" id="chatbotClose" aria-label="Close"></button>
    </div>
    <div class="chatbot-body" id="chatbotBody">
        <div class="chatbot-msg bot">Hi {{ auth()->user()?->name ?? 'there' }}! 👋 Ask me things like "when is Green Valley open?", "where can I buy tomatoes?" or "how do pre-orders work?"</div>
    </div>
    <div class="border-top p-2 bg-white">
        <form id="chatbotForm" class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" id="chatbotInput" placeholder="Type a question..." autocomplete="off">
            <button class="btn btn-ml btn-sm px-3" type="submit"><i class="bi bi-send"></i></button>
        </form>
    </div>
</div>
