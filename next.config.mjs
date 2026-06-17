/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  images: {
    remotePatterns: [],
  },
  webpack: (config, { dev }) => {
    // Empêche la réutilisation d'un CSS Tailwind périmé via le cache de build
    // (Vercel) lorsque seuls des fichiers .tsx changent : on force une
    // génération propre du CSS à chaque build de production.
    if (!dev) config.cache = false;
    return config;
  },
};

export default nextConfig;
