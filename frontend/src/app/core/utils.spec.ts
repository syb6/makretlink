import { describe, expect, it } from 'vitest';
import { avatar, fallbackImage, formatTime, img, initials } from './utils';

describe('image URL helpers', () => {
  it('resolves bare and already-prefixed image paths', () => {
    expect(img('product', 'apples.png')).toBe('/images/placeholders/product-images/apples.png');
    expect(img('market', 'market-images/old-town-morning-market.jpeg'))
      .toBe('/images/placeholders/market-images/old-town-morning-market.jpeg');
    expect(img('category', 'category/vegetables.png'))
      .toBe('/images/placeholders/category-images/vegetables.png');
  });

  it('encodes path segments without altering folder separators', () => {
    expect(img('product', 'rainbow carrot.png'))
      .toBe('/images/placeholders/product-images/rainbow%20carrot.png');
  });

  it('preserves absolute and storage image URLs', () => {
    expect(img('product', 'https://cdn.example.test/tomato.png')).toBe('https://cdn.example.test/tomato.png');
    expect(img('product', 'profiles/farmer photo.jpg')).toBe('/storage/profiles/farmer%20photo.jpg');
    expect(avatar('profiles/farmer photo.jpg')).toBe('/storage/profiles/farmer%20photo.jpg');
    expect(avatar(null)).toBeNull();
  });

  it('returns initials from the first and last name', () => {
    expect(initials('Ayesha Khan')).toBe('AK');
    expect(initials('  Ayesha   Noor Khan ')).toBe('AK');
    expect(initials('Farmer')).toBe('F');
    expect(initials('')).toBe('?');
  });

  it('uses a valid per-entity placeholder for empty image values', () => {
    expect(img('product', null)).toBe(fallbackImage('product'));
    expect(img('market', '')).toBe(fallbackImage('market'));
    expect(img('category', undefined)).toBe(fallbackImage('category'));
  });

  it('formats database time values as times rather than ISO timestamps', () => {
    expect(formatTime('2026-10-16T04:00:00.000000Z')).toBe('04:00');
    expect(formatTime('4:30:00')).toBe('04:30');
    expect(formatTime(null)).toBe('');
  });
});
