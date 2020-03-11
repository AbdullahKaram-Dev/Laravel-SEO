<?php

namespace romanzipp\Seo\Conductors\MixManifestConductor;

use Closure;
use romanzipp\Seo\Conductors\MixManifestConductor\Types\ManifestAsset;
use romanzipp\Seo\Exceptions\ManifestNotFoundException;
use romanzipp\Seo\Services\SeoService;
use romanzipp\Seo\Structs\Link;

class MixManifestConductor
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var \romanzipp\Seo\Conductors\MixManifestConductor\Types\ManifestAsset[]
     */
    private $assets = [];

    /**
     * @var \Closure|null
     */
    private $mapCallback = null;

    /**
     * MixManifestService constructor.
     */
    public function __construct()
    {
        $this->path = public_path('mix-manifest.json');
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return \romanzipp\Seo\Conductors\MixManifestConductor\Types\ManifestAsset[]
     */
    public function getAssets(): array
    {
        return $this->assets;
    }

    /**
     * @param \Closure $callback
     * @return \romanzipp\Seo\Conductors\MixManifestConductor\MixManifestConductor
     */
    public function map(Closure $callback): self
    {
        $this->mapCallback = $callback;

        return $this;
    }

    /**
     * @param string|null $path
     * @return \romanzipp\Seo\Conductors\MixManifestConductor\MixManifestConductor
     * @throws \romanzipp\Seo\Exceptions\ManifestNotFoundException
     */
    public function load(string $path = null): self
    {
        if ($path !== null) {
            $this->path = $path;
        }

        $this->assets = $this->readContents();

        if ($this->mapCallback !== null) {
            $this->assets = array_map($this->mapCallback, $this->assets);
        }

        $this->assets = array_filter($this->assets);

        foreach ($this->assets as $asset) {
            $this->generateStruct($asset);
        }

        return $this;
    }

    /**
     * @param \romanzipp\Seo\Conductors\MixManifestConductor\Types\ManifestAsset $asset
     * @return void
     */
    private function generateStruct(ManifestAsset $asset): void
    {
        $seo = app(SeoService::class);

        $seo->add(
            Link::make()
                ->rel($asset->rel)
                ->href($asset->buildFullUrl())
        );
    }

    /**
     * @return \romanzipp\Seo\Conductors\MixManifestConductor\Types\ManifestAsset[]
     * @throws \romanzipp\Seo\Exceptions\ManifestNotFoundException
     */
    private function readContents(): array
    {
        $content = @file_get_contents($this->getPath());

        if ($content === false) {
            throw new ManifestNotFoundException('The manifest file could not be found');
        }

        $data = @json_decode($content, true) ?? [];

        return array_map(static function ($path, $url) {
            return new ManifestAsset($path, $url);
        }, array_keys($data), $data);
    }
}
