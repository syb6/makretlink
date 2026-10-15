# Angular frontend

This is the Angular 22 client for the Laravel API.

## Local development

From the repository root, start Laravel on `127.0.0.1:8000`. In this directory run:

```bash
npm ci
npm start
```

Open <http://localhost:4200>. The dev-server proxy forwards `/api` and `/sanctum`
requests to Laravel. `SANCTUM_STATEFUL_DOMAINS` in the root `.env` must include
`localhost:4200` (or `127.0.0.1:4200` if that is the browser host).

## Production build

```bash
npm run build
```

The production bundle is written to `../public/spa` and is served by Laravel from
`/spa/`. The root URL also serves the Angular app.
