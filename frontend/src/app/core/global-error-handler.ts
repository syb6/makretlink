import { ErrorHandler, Injectable, inject } from '@angular/core';
import { ApiError } from './api.service';
import { ToastService } from './toast.service';

@Injectable()
export class GlobalErrorHandler implements ErrorHandler {
  private readonly toast = inject(ToastService);

  handleError(error: unknown): void {
    console.error(error);
    if (error instanceof ApiError && error.reported) return;
    this.toast.error('An unexpected error occurred. Please try again.');
  }
}
