<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

class SpaController extends Controller
{
    public function __invoke(): Response
    {
        $index = public_path('spa/index.html');

        if (! is_file($index)) {
            return response('Angular build missing — run: npm run build', 503);
        }

        return response()->file($index, [
            'Cache-Control' => 'no-store',
        ]);
    }
}
