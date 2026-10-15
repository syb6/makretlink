import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-about',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <section class="page-banner">
      <div class="container">
        <span class="badge-sub">OUR MISSION</span>
        <h1 class="section-title">Fresh. Local. <span class="accent">Predictable.</span></h1>
        <p class="section-subtitle">MarketLink connects local farmers with the people who love fresh food.</p>
        <i class="bi bi-basket2 page-banner-icon" aria-hidden="true"></i>
      </div>
    </section>

    <div class="container py-4 py-lg-5">
      <div class="about-grid mb-5">
        <div class="about-content">
          <h2 class="h3 fw-bold">Why MarketLink exists</h2>
          <p>Local farmers markets are thriving, but shoppers do not always know which growers will be there, what they will bring, or what is still available. MarketLink makes weekly stock and prices easy to discover before market day.</p>
          <p>Farmers publish their produce and customers browse, reserve, and collect at the market. That means fewer wasted trips, better harvest planning, and stronger local communities.</p>
          <div class="about-mini-grid">
            <div class="about-mini-card"><i class="bi bi-basket"></i><span>Fresh &amp; local</span></div>
            <div class="about-mini-card"><i class="bi bi-clock-history"></i><span>Easy planning</span></div>
            <div class="about-mini-card"><i class="bi bi-people"></i><span>Stronger community</span></div>
          </div>
        </div>
        <div class="about-img-box">
          <img src="/images/placeholders/market-images/green-villey-community-market.jpeg"
               class="w-100 h-100" alt="Farmers market stall" fetchpriority="high">
        </div>
      </div>

      <div class="row g-4 mb-5">
        <div class="col-6 col-lg-3"><article class="card-ml p-4 text-center h-100"><i class="bi bi-flower1 about-value-icon"></i><h3 class="h6 fw-bold mt-3">Local produce</h3><p class="small text-muted mb-0">Seasonal, fresh, and grown nearby.</p></article></div>
        <div class="col-6 col-lg-3"><article class="card-ml p-4 text-center h-100"><i class="bi bi-basket about-value-icon"></i><h3 class="h6 fw-bold mt-3">Pre-order</h3><p class="small text-muted mb-0">Reserve before market day and skip the disappointment.</p></article></div>
        <div class="col-6 col-lg-3"><article class="card-ml p-4 text-center h-100"><i class="bi bi-hand-thumbs-up about-value-icon"></i><h3 class="h6 fw-bold mt-3">Fair for farmers</h3><p class="small text-muted mb-0">Plan harvests against real demand.</p></article></div>
        <div class="col-6 col-lg-3"><article class="card-ml p-4 text-center h-100"><i class="bi bi-geo-alt about-value-icon"></i><h3 class="h6 fw-bold mt-3">Simple pickup</h3><p class="small text-muted mb-0">Know where and when to collect your order.</p></article></div>
      </div>

      <div class="about-cta rounded-4 text-center">
        <h2 class="h3">Ready to taste the difference?</h2>
        <p>Join as a customer to pre-order, or as a farmer to start selling.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
          <a routerLink="/register" class="btn btn-ml px-4">Join as a customer</a>
          <a routerLink="/register" [queryParams]="{ role: 'farmer' }" class="btn btn-outline-ml px-4">Join as a farmer</a>
        </div>
      </div>
    </div>
  `,
  imports: [RouterLink],
})
export class AboutComponent {}
