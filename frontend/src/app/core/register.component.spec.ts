import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { AuthService } from './auth.service';
import { ToastService } from './toast.service';
import { RegisterComponent } from '../pages/auth/register.component';

afterEach(() => {
  TestBed.resetTestingModule();
});

describe('RegisterComponent', () => {
  it('requires terms acceptance and registers its checkbox with the form', async () => {
    const auth = { register: vi.fn() };
    const toast = { error: vi.fn(), success: vi.fn(), fromValidation: vi.fn() };

    TestBed.configureTestingModule({
      imports: [RegisterComponent],
      providers: [
        provideRouter([]),
        { provide: AuthService, useValue: auth },
        { provide: ToastService, useValue: toast },
      ],
    });

    const fixture = TestBed.createComponent(RegisterComponent);
    expect(() => fixture.detectChanges()).not.toThrow();

    await fixture.componentInstance.submit();

    expect(toast.error).toHaveBeenCalledWith(
      "Please agree to MarketLink's Terms of Service and Privacy Policy.",
    );
    expect(auth.register).not.toHaveBeenCalled();
  });
});
