import { ChangeDetectionStrategy, Component, ElementRef, inject, signal, viewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';

interface BotLink { label: string; url: string; }
interface ChatEntry { text: string; who: 'user' | 'bot'; links?: BotLink[]; source?: string; }

/**
 * Global AI assistant widget, ported from the Blade layout (chatbot/widget.blade.php
 * + public/js/chatbot.js): themed FAB, dialog window, typing indicator and
 * clickable product suggestion cards. Every non-admin, non-auth page shows it.
 */
@Component({
  selector: 'app-chatbot',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <button class="chatbot-fab" [class.open]="open()" type="button"
            (click)="toggle()" aria-controls="chatbotWindow"
            [attr.aria-expanded]="open()" title="Ask MarketLink Assistant">
      <i class="bi bi-chat-dots-fill"></i>
      <i class="bi bi-x-lg chatbot-fab-close" aria-hidden="true"></i>
    </button>

    <div class="chatbot-window" id="chatbotWindow" [class.open]="open()"
         role="dialog" aria-label="MarketLink Assistant" [attr.aria-hidden]="!open()">
      <div class="chatbot-head">
        <span class="chatbot-head-icon"><i class="bi bi-robot"></i></span>
        <div>
          <div class="fw-semibold">MarketLink Assistant</div>
          <div class="small">FAQs &amp; product search</div>
        </div>
        <button class="chatbot-close ms-auto" type="button" (click)="toggle()" aria-label="Close assistant">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="chatbot-body" #body>
        @for (entry of entries(); track $index) {
          <div class="chatbot-msg" [class.bot]="entry.who === 'bot'" [class.user]="entry.who === 'user'">
            {{ entry.text }}
            @if (entry.links?.length) {
              <div class="chatbot-links mt-2 d-grid gap-2">
                @for (link of entry.links ?? []; track link.url) {
                  <a [href]="link.url" class="chatbot-link-card" target="_blank" rel="noopener">🛒 {{ link.label }}</a>
                }
              </div>
            }
            @if (entry.source === 'groq') {
              <div class="small opacity-75 mt-1">✦ AI</div>
            }
          </div>
        }
        @if (typing()) {
          <div class="chatbot-msg bot chatbot-typing" aria-label="Assistant is typing">
            <span class="chatbot-typing-dot"></span><span class="chatbot-typing-dot"></span><span class="chatbot-typing-dot"></span>
          </div>
        }
      </div>
      <div class="chatbot-foot">
        <form class="d-flex gap-2" [class.sending]="busy()" (ngSubmit)="send()">
          <input type="text" class="form-control form-control-sm" placeholder="Type a question..."
                 autocomplete="off" aria-label="Your question" name="message" [disabled]="busy()"
                 [(ngModel)]="draft" (keydown.escape)="toggle()">
          <button class="btn btn-ml btn-sm px-3" type="submit" aria-label="Send message">
            <span class="chatbot-send-idle"><i class="bi bi-send"></i></span>
            <span class="chatbot-send-busy spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          </button>
        </form>
      </div>
    </div>
  `,
})
export class ChatbotComponent {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private body = viewChild.required<ElementRef<HTMLDivElement>>('body');

  open = signal(false);
  busy = signal(false);
  typing = signal(false);
  entries = signal<ChatEntry[]>([]);
  draft = '';

  constructor() {
    if (typeof document !== 'undefined') {
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && this.open()) this.open.set(false);
      });
    }
  }

  toggle(): void {
    const next = !this.open();
    this.open.set(next);
    if (next && this.entries().length === 0) {
      const name = this.auth.user()?.name?.trim() ?? 'there';
      this.entries.set([{
        who: 'bot',
        text: `Hi ${name.split(/\s+/)[0] ?? name}! 👋 Ask me things like "when is Green Valley open?", "where can I buy tomatoes?" or "how do pre-orders work?"`,
      }]);
    }
  }

  async send(): Promise<void> {
    const message = this.draft.trim();
    if (!message || this.busy()) return;

    this.entries.update((list) => [...list, { who: 'user', text: message }]);
    this.draft = '';
    this.busy.set(true);
    this.typing.set(true);
    this.scrollDown();

    try {
      // The widget sends the raw message; reply back with links + source tag.
      const data = await this.api.form<{ reply: string; links: BotLink[]; source: string }>('/assistant', { message });
      this.entries.update((list) => [
        ...list,
        { who: 'bot', text: data.reply || 'Sorry, I could not answer that.', links: data.links ?? [], source: data.source },
      ]);
    } catch {
      this.entries.update((list) => [
        ...list,
        { who: 'bot', text: navigator.onLine
          ? 'Sorry — I could not reach the server just now. Please try again in a moment.'
          : 'You appear to be offline. Reconnect to the internet and try again.' },
      ]);
    } finally {
      this.typing.set(false);
      this.busy.set(false);
      this.scrollDown();
    }
  }

  private scrollDown(): void {
    setTimeout(() => {
      const el = this.body().nativeElement;
      el.scrollTop = el.scrollHeight;
    });
  }
}
