import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

@Component({
  selector: 'app-loading-state',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="loading-state" role="status" aria-live="polite" [attr.aria-label]="label">
      <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
      <span>{{ label }}</span>
      <span class="visually-hidden">Please wait.</span>
    </div>
  `,
  styles: `
    .loading-state {
      display: flex;
      min-height: 8rem;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      color: var(--muted, #6c757d);
    }

    .spinner-border {
      color: var(--primary-dark, #66720f);
    }
  `,
})
export class LoadingStateComponent {
  @Input() label = 'Loading…';
}
