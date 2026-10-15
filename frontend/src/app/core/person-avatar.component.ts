import { ChangeDetectionStrategy, Component, computed, input, OnChanges, signal, SimpleChanges } from '@angular/core';
import { avatar, initials } from './utils';

@Component({
  selector: 'app-person-avatar',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (photoUrl() && !imageFailed()) {
      <img [src]="photoUrl()" [alt]="'Profile photo of ' + name()" (error)="imageFailed.set(true)">
    } @else {
      <span class="person-avatar-fallback" role="img" [attr.aria-label]="'Profile initials for ' + name()">
        {{ initials(name()) }}
      </span>
    }
  `,
  styles: `
    :host {
      display: inline-flex;
      flex: 0 0 auto;
      vertical-align: middle;
    }

    img,
    .person-avatar-fallback {
      display: flex;
      width: 100%;
      height: 100%;
      aspect-ratio: 1;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }

    img {
      object-fit: cover;
    }

    .person-avatar-fallback {
      background: var(--primary-light, #e8edc5);
      color: var(--primary-contrast, #34410d);
      font-size: 0.9em;
      font-weight: 700;
      line-height: 1;
    }
  `,
})
export class PersonAvatarComponent implements OnChanges {
  readonly name = input('');
  readonly src = input<string | null | undefined>(null);
  readonly photoUrl = computed(() => avatar(this.src()));
  readonly imageFailed = signal(false);
  readonly initials = initials;

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['src']) this.imageFailed.set(false);
  }
}
