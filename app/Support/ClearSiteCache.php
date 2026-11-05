<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * Perintahkan browser membuang cache situs saat sesi berakhir,
 * agar halaman milik user tidak terbaca lewat tombol Back.
 */
class ClearSiteCache
{
    public static function apply(Response $response): Response
    {
        $response->headers->set('Clear-Site-Data', '"cache", "storage"');

        return $response;
    }
}
