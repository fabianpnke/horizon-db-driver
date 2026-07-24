// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// See: https://docusaurus.io/docs/api/docusaurus-config

import {themes as prismThemes} from 'prism-react-renderer';

// This runs in Node.js - Don't use client-side code here (browser APIs, JSX...)

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'Horizon Db Driver',
  tagline: 'Run Laravel Horizon on a database instead of Redis.',
  favicon: 'img/favicon.ico',

  // Future flags, see https://docusaurus.io/docs/api/docusaurus-config#future
  future: {
    v4: true, // Improve compatibility with the upcoming Docusaurus v4
  },

  // Set the production url of your site here
  url: 'https://fabianpnke.github.io',
  // Set the /<baseUrl>/ pathname under which your site is served
  // For GitHub pages deployment, it is often '/<projectName>/'
  baseUrl: '/horizon-db-driver/',

  // GitHub pages deployment config.
  organizationName: 'fabianpnke',
  projectName: 'horizon-db-driver',
  deploymentBranch: 'gh-pages',
  trailingSlash: false,

  onBrokenLinks: 'throw',

  // Even if you don't use internationalization, you can use this field to set
  // useful metadata like html lang. For example, if your site is Chinese, you
  // may want to replace "en" with "zh-Hans".
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          path: 'docs',
          routeBasePath: '/',
          sidebarPath: './sidebars.js',
          editUrl:
            'https://github.com/fabianpnke/horizon-db-driver/edit/main/docs-site/docs/',
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      colorMode: {
        respectPrefersColorScheme: true,
      },
      navbar: {
        title: 'Horizon Db Driver',
        items: [
          {
            type: 'docSidebar',
            sidebarId: 'docsSidebar',
            position: 'left',
            label: 'Documentation',
          },
          {
            href: 'https://packagist.org/packages/fabianpnke/horizon-db-driver',
            label: 'Packagist',
            position: 'right',
          },
          {
            href: 'https://github.com/fabianpnke/horizon-db-driver',
            label: 'GitHub',
            position: 'right',
          },
        ],
      },
      footer: {
        style: 'dark',
        links: [
          {
            title: 'Docs',
            items: [
              {label: 'Introduction', to: '/'},
              {label: 'Installation', to: '/installation'},
              {label: 'Configuration', to: '/configuration'},
              {label: 'Usage', to: '/usage'},
            ],
          },
          {
            title: 'Package',
            items: [
              {
                label: 'GitHub',
                href: 'https://github.com/fabianpnke/horizon-db-driver',
              },
              {
                label: 'Packagist',
                href: 'https://packagist.org/packages/fabianpnke/horizon-db-driver',
              },
              {
                label: 'Changelog',
                href: 'https://github.com/fabianpnke/horizon-db-driver/blob/main/CHANGELOG.md',
              },
            ],
          },
          {
            title: 'Upstream',
            items: [
              {
                label: 'Laravel Horizon',
                href: 'https://laravel.com/docs/horizon',
              },
              {
                label: 'laravel/horizon#1762 (origin)',
                href: 'https://github.com/laravel/horizon/pull/1762',
              },
            ],
          },
        ],
        copyright: `Copyright © ${new Date().getFullYear()} fabianpnke. Built with Docusaurus.`,
      },
      prism: {
        theme: prismThemes.github,
        darkTheme: prismThemes.dracula,
        additionalLanguages: ['php', 'bash'],
      },
    }),
};

export default config;
