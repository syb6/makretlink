import { TestBed } from '@angular/core/testing';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { MotionService } from './motion.service';

class MockIntersectionObserver {
  static instances: MockIntersectionObserver[] = [];
  observed: Element[] = [];

  constructor(readonly callback: IntersectionObserverCallback) {
    MockIntersectionObserver.instances.push(this);
  }

  observe(target: Element): void {
    this.observed.push(target);
  }

  unobserve(): void {}
  disconnect(): void {}
}

afterEach(() => {
  TestBed.resetTestingModule();
  document.body.replaceChildren();
  MockIntersectionObserver.instances = [];
  vi.unstubAllGlobals();
});

describe('MotionService', () => {
  it('animates async counters from their loaded value instead of the initial placeholder', async () => {
    vi.stubGlobal('matchMedia', vi.fn(() => ({ matches: false })));
    vi.stubGlobal('IntersectionObserver', MockIntersectionObserver);
    TestBed.configureTestingModule({ providers: [MotionService] });

    const counter = document.createElement('span');
    counter.className = 'stat-number';
    counter.textContent = '0';
    document.body.append(counter);

    const motion = TestBed.inject(MotionService);
    motion.start();

    counter.textContent = '4';
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(counter.getAttribute('data-target')).toBe('4');
    expect(MockIntersectionObserver.instances[1].observed).toContain(counter);

    motion.stop();
  });

  it('shows image loading state until the image finishes loading', async () => {
    vi.stubGlobal('matchMedia', vi.fn(() => ({ matches: false })));
    vi.stubGlobal('IntersectionObserver', MockIntersectionObserver);
    TestBed.configureTestingModule({ providers: [MotionService] });

    const image = document.createElement('img');
    image.src = '/slow-image.png';
    document.body.append(image);

    const motion = TestBed.inject(MotionService);
    motion.start();

    expect(image.classList.contains('image-loading')).toBe(true);
    image.dispatchEvent(new Event('load'));
    expect(image.classList.contains('image-loading')).toBe(false);
    expect(image.classList.contains('image-ready')).toBe(true);

    motion.stop();
  });

  it('restarts the image loading state when an existing image source changes', async () => {
    vi.stubGlobal('matchMedia', vi.fn(() => ({ matches: false })));
    vi.stubGlobal('IntersectionObserver', MockIntersectionObserver);
    TestBed.configureTestingModule({ providers: [MotionService] });

    const image = document.createElement('img');
    image.src = '/first-image.png';
    document.body.append(image);

    const motion = TestBed.inject(MotionService);
    motion.start();
    image.dispatchEvent(new Event('load'));
    expect(image.classList.contains('image-ready')).toBe(true);

    image.src = '/second-image.png';
    await new Promise((resolve) => setTimeout(resolve, 0));
    expect(image.classList.contains('image-loading')).toBe(true);
    expect(image.classList.contains('image-ready')).toBe(false);

    image.dispatchEvent(new Event('load'));
    expect(image.classList.contains('image-loading')).toBe(false);
    expect(image.classList.contains('image-ready')).toBe(true);

    motion.stop();
  });
});
