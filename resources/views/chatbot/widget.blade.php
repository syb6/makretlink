<button class="chatbot-fab" id="chatbotFab" type="button"
        aria-expanded="false" aria-controls="chatbotWindow"
        title="Ask MarketLink Assistant" data-assistant-url="{{ route('assistant') }}">
    <i class="bi bi-chat-dots-fill"></i>
    <i class="bi bi-x-lg chatbot-fab-close" aria-hidden="true"></i>
</button>

<div class="chatbot-window" id="chatbotWindow" role="dialog" aria-label="MarketLink Assistant" aria-hidden="true">
    <div class="chatbot-head">
        <span class="chatbot-head-icon"><i class="bi bi-robot"></i></span>
        <div>
            <div class="fw-semibold">MarketLink Assistant</div>
            <div class="small">FAQs &amp; product search</div>
        </div>
        <button class="chatbot-close ms-auto" id="chatbotClose" type="button" aria-label="Close assistant">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="chatbot-body" id="chatbotBody">
        <div class="chatbot-msg bot">Hi {{ auth()->user()?->name ?? 'there' }}! 👋 Ask me things like "when is Green Valley open?", "where can I buy tomatoes?" or "how do pre-orders work?"</div>
    </div>
    <div class="chatbot-foot">
        <form id="chatbotForm" class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" id="chatbotInput" placeholder="Type a question..." autocomplete="off" aria-label="Your question">
            <button class="btn btn-ml btn-sm px-3" type="submit" aria-label="Send message">
                <span class="chatbot-send-idle"><i class="bi bi-send"></i></span>
                <span class="chatbot-send-busy spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </button>
        </form>
    </div>
</div>
