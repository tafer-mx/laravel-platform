<?php

    namespace TAFER\Core\Repositories;

    use Illuminate\Contracts\Cache\Repository as CacheRepository;

    class StoryCacheRepository
    {
        private const CACHE_PREFIX = 'storyblok:slug:';

        public function __construct(private readonly CacheRepository $cache) {}

        public function getCv(string $slug): ?string
        {
            return $this->cache->get($this->buildKey($slug));
        }

        public function putCv(string $slug, string $cv): void
        {
            $this->cache->put($this->buildKey($slug), $cv);
        }

        public function invalidate(string $slug): void
        {
            $this->cache->forget($this->buildKey($slug));
        }

        private function buildKey(string $slug): string
        {
            return self::CACHE_PREFIX . ltrim($slug, '/');
        }
    }