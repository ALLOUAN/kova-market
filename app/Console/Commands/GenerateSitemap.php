<?php

namespace App\Console\Commands;

use App\Services\Storefront\SitemapGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Nightly sitemap (F-154): new products, categories and pages reach the search engines the next day.
 */
#[Signature('seo:sitemap')]
#[Description('Generate the sitemap.xml served to search engines')]
class GenerateSitemap extends Command
{
    public function handle(SitemapGenerator $sitemap): int
    {
        $count = substr_count($sitemap->generate(), '<url>');

        $this->info("Sitemap généré : {$count} adresse(s).");

        return self::SUCCESS;
    }
}
