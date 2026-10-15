import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-not-found',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink],
  template: `
    <div class="text-center py-5">
      <div class="display-1 fw-bold text-success">404</div>
      <p class="lead mb-4">That page wandered off with the goats.</p>
      <a routerLink="/" class="btn btn-success">Back to the market</a>
    </div>
  `,
})
export class NotFoundComponent {}
