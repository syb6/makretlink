import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-toasts',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="ml-alert-stack spa-alert-stack">
      @for (t of toastService.toasts(); track $index) {
        <div [class]="'ml-alert ' + t.kind" [style.--ttl]="t.ttl + 'ms'"
             (mouseenter)="toastService.pause(t)" (mouseleave)="toastService.resume(t)"
             [attr.role]="t.kind === 'error' ? 'alert' : 'status'"
             [attr.aria-live]="t.kind === 'error' ? 'assertive' : 'polite'">
          <div class="ml-alert-icon">
            <i class="bi" [class]="iconFor(t.kind)" aria-hidden="true"></i>
          </div>
          <div class="ml-alert-body">
            <div class="ml-alert-title">{{ t.title }}</div>
            @if (t.messages.length) {
              <ul class="ml-alert-msg mb-0 ps-3">
                @for (message of t.messages; track $index) { <li>{{ message }}</li> }
              </ul>
            } @else {
              <div class="ml-alert-msg">{{ t.text }}</div>
            }
          </div>
          <button type="button" class="ml-alert-close" aria-label="Dismiss notification" (click)="toastService.dismiss(t)">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
          </button>
          @if (t.ttl > 0) { <span class="ml-alert-progress" aria-hidden="true"></span> }
        </div>
      }
    </div>
  `,
})
export class ToastsComponent {
  readonly toastService = inject(ToastService);

  iconFor(kind: string): string {
    return kind === 'success'
      ? 'bi bi-check-circle-fill'
      : kind === 'error' ? 'bi bi-exclamation-triangle-fill' : 'bi bi-info-circle-fill';
  }

}
