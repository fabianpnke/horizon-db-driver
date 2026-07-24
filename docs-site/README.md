# Horizon Db Driver docs

The [Docusaurus](https://docusaurus.io/) site behind the Horizon Db Driver documentation, published to GitHub Pages at https://fabianpnke.github.io/horizon-db-driver/.

## Local development

```bash
npm install
npm start
```

Starts a local dev server with hot reload at `http://localhost:3000/horizon-db-driver/`.

## Build

```bash
npm run build
```

Generates static content into the `build` directory, servable with any static hosting service.

## Deployment

Pushes to `main` that touch `docs-site/**` are built and published automatically by [`.github/workflows/deploy-docs.yml`](../.github/workflows/deploy-docs.yml) via GitHub Actions. The repository's Pages source must be set to **GitHub Actions** (Settings → Pages) for this to take effect.
