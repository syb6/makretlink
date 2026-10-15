/** Small formatting helpers shared by every SPA page. */

export function money(v: number | string | null | undefined): string {
  const n = Number(v ?? 0);
  // Rs. mirrors the Blade format_money; prices are plain decimals, no intl needed.
  return 'Rs ' + n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

export function date(v: string | null | undefined, withTime = false): string {
  if (!v) return '—';
  const d = new Date(v);
  if (isNaN(d.getTime())) return v;
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
    + (withTime ? ', ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) : '');
}

export function formatTime(value: string | null | undefined): string {
  if (!value) return '';
  const match = value.match(/(?:T|^)(\d{1,2}):(\d{2})/);
  return match ? `${match[1].padStart(2, '0')}:${match[2]}` : value;
}

export const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const imageFolders = {
  product: 'product-images',
  market: 'market-images',
  category: 'category-images',
} as const;

const imageFallbacks = {
  product: '/images/placeholders/product-images/heirloom-tomatoes.png',
  market: '/images/placeholders/market-images/green-villey-community-market.jpeg',
  category: '/images/placeholders/category-images/vegetables.png',
} as const;

function encodePath(path: string): string {
  return path.split('/').map((part) => encodeURIComponent(part)).join('/');
}

export function fallbackImage(type: 'product' | 'market' | 'category'): string {
  return imageFallbacks[type];
}

export function initials(name: string | null | undefined): string {
  const parts = name?.trim().split(/\s+/u).filter(Boolean) ?? [];
  if (parts.length === 0) return '?';

  const first = Array.from(parts[0])[0] ?? '';
  const last = parts.length > 1 ? Array.from(parts[parts.length - 1])[0] ?? '' : '';
  return (first + last).toLocaleUpperCase();
}

export function imageLoaded(event: Event): void {
  const image = event.currentTarget;
  if (image instanceof HTMLImageElement) {
    image.classList.add('is-loaded');
  }
}

export function imageError(event: Event, type: 'product' | 'market' | 'category'): void {
  const image = event.currentTarget;
  if (!(image instanceof HTMLImageElement)) return;

  const fallback = fallbackImage(type);
  if (image.getAttribute('src') !== fallback) {
    image.src = fallback;
  } else {
    image.hidden = true;
  }
}

export function dayName(i: number | string | null | undefined): string {
  return DAY_NAMES[Number(i) % 7] ?? '';
}

/**
 * Resolve image paths the same way as Laravel's ImageLibrary: uploads,
 * seeded assets, storage paths and absolute URLs all remain valid.
 */
export function img(type: 'product' | 'market' | 'category', path: string | null | undefined): string {
  if (!path?.trim()) return fallbackImage(type);

  const value = path.trim();
  if (/^(?:https?:)?\/\//i.test(value)) return value;
  if (value.startsWith('/storage/')) return encodePath(value);
  if (value.startsWith('storage/')) return '/' + encodePath(value);
  if (value.startsWith('profiles/')) return '/storage/' + encodePath(value);
  if (value.startsWith('/images/')) return encodePath(value);
  if (value.startsWith('images/')) return '/' + encodePath(value);

  const folder = imageFolders[type];
  const entityFolder = `${type}/`;
  const relativePath = value.startsWith(entityFolder) ? value.slice(entityFolder.length) : value;
  const assetPath = relativePath.startsWith(`${folder}/`)
    ? relativePath
    : `${folder}/${relativePath}`;

  return '/images/placeholders/' + encodePath(assetPath);
}

export function avatar(path: string | null | undefined): string | null {
  if (!path?.trim()) return null;
  const value = path.trim();
  if (/^(?:https?:)?\/\//i.test(value)) return value;
  if (value.startsWith('/')) return encodePath(value);
  return '/storage/' + encodePath(value);
}

export function statusBadge(s: string | null | undefined): string {
  const map: Record<string, string> = {
    active: 'success', approved: 'success', visible: 'success', published: 'success',
    completed: 'success', accepted: 'primary', ready_for_pickup: 'info', placed: 'warning',
    pending: 'warning', placed_warn: 'warning', inactive: 'secondary', suspended: 'danger',
    rejected: 'danger', hidden: 'danger', declined: 'danger', cancelled: 'secondary',
    sold_out: 'danger', unavailable: 'secondary', draft: 'secondary', archived: 'dark', full: 'warning',
  };
  return map[s ?? ''] ?? 'light';
}

export function statusLabel(s: string | null | undefined): string {
  return (s ?? '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
