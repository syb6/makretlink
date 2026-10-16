import { Injectable } from '@angular/core';

interface DialogOptions {
  title?: string;
  okLabel?: string;
  danger?: boolean;
}

/**
 * Port of the Blade theme's mlConfirm()/mlAlert() (part 0 of public/js/app.js):
 * promise-based replacements for window.confirm/alert so destructive actions
 * and errors use the themed MarketLink dialogs instead of browser chrome.
 */
@Injectable({ providedIn: 'root' })
export class ConfirmService {
  confirm(message: string, options: DialogOptions = {}): Promise<boolean> {
    return new Promise((resolve) => {
      const dialog = this.render(message, {
        ...options,
        title: options.title ?? 'Are you sure?',
        okLabel: options.okLabel ?? 'Yes, continue',
        danger: options.danger ?? true,
        mode: 'confirm',
      });
      const finish = (confirmed: boolean) => {
        cleanup();
        this.close(dialog);
        resolve(confirmed);
      };
      dialog.querySelector('[data-dialog-ok]')?.addEventListener('click', () => finish(true));
      dialog.querySelector('[data-dialog-cancel]')?.addEventListener('click', () => finish(false));
      dialog.addEventListener('click', (event) => { if (event.target === dialog) finish(false); });
      const onKey = (event: KeyboardEvent) => {
        if (event.key === 'Escape') { event.preventDefault(); finish(false); }
        else if (event.key === 'Enter') { event.preventDefault(); finish(true); }
      };
      const cleanup = () => document.removeEventListener('keydown', onKey);
      document.addEventListener('keydown', onKey);
      dialog.querySelector<HTMLButtonElement>('[data-dialog-ok]')?.focus();
    });
  }

  alert(message: string, options: DialogOptions = {}): Promise<void> {
    return new Promise((resolve) => {
      const dialog = this.render(message, {
        title: options.title ?? (options.danger ? 'Something went wrong' : 'Notice'),
        okLabel: options.okLabel ?? 'Got it',
        danger: options.danger ?? false,
        mode: 'alert',
      });
      const finish = () => {
        cleanup();
        this.close(dialog);
        resolve();
      };
      dialog.querySelector('[data-dialog-ok]')?.addEventListener('click', finish);
      dialog.addEventListener('click', (event) => { if (event.target === dialog) finish(); });
      const onKey = (event: KeyboardEvent) => {
        if (event.key === 'Escape' || event.key === 'Enter') { event.preventDefault(); finish(); }
      };
      const cleanup = () => document.removeEventListener('keydown', onKey);
      document.addEventListener('keydown', onKey);
      dialog.querySelector<HTMLButtonElement>('[data-dialog-ok]')?.focus();
    });
  }

  private render(message: string, { title, okLabel, danger, mode }: {
    title: string; okLabel: string; danger: boolean; mode: 'confirm' | 'alert';
  }): HTMLElement {
    const wrap = document.createElement('div');
    wrap.className = 'ml-dialog-backdrop';
    wrap.setAttribute('role', 'presentation');
    wrap.innerHTML =
      `<div class="ml-dialog" role="${mode === 'alert' ? 'alertdialog' : 'dialog'}" aria-modal="true" aria-label="${this.escape(title)}">`
      + `<div class="ml-dialog-icon ml-dialog-icon-${danger ? 'danger' : 'primary'}">`
      + `<i class="bi ${danger ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'}"></i></div>`
      + `<h5 class="ml-dialog-title">${this.escape(title)}</h5>`
      + `<p class="ml-dialog-message">${this.escape(message)}</p>`
      + `<div class="ml-dialog-actions">`
      + (mode === 'confirm' ? '<button type="button" class="btn btn-outline-ml btn-sm" data-dialog-cancel>Cancel</button>' : '')
      + `<button type="button" class="btn ${danger ? 'btn-danger' : 'btn-ml'} btn-sm" data-dialog-ok>${this.escape(okLabel)}</button>`
      + `</div></div>`;
    document.body.appendChild(wrap);
    requestAnimationFrame(() => requestAnimationFrame(() => wrap.classList.add('show')));
    return wrap;
  }

  private close(dialog: HTMLElement): void {
    dialog.classList.remove('show');
    setTimeout(() => dialog.remove(), 220);
  }

  private escape(value: string): string {
    return value.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c] ?? c));
  }
}
