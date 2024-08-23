<?php

// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Twig;

use App\Utils;
use Symfony\Component\Asset;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ParcelAssetExtension extends AbstractExtension
{
    public function __construct(
        private string $pathToPublic,
        private string $pathToAssets,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('parcel_asset', [$this, 'parcelAsset']),
        ];
    }

    public function parcelAsset(string $assetPath): string
    {
        $assetStrategy = new Utils\AssetsMtimeStrategy($this->pathToPublic);
        $assetPackage = new Asset\Package($assetStrategy);

        $assetPathname = "/{$this->pathToAssets}/{$assetPath}";

        return $assetPackage->getUrl($assetPathname);
    }
}
