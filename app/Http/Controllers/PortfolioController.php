<?php

namespace App\Http\Controllers;

use App\Models\PortfolioItem;
use Illuminate\Support\Facades\Auth;

class PortfolioController extends Controller
{
    /**
     * Страница «Портфолио» в ЛК: сертификаты, дипломы, награды.
     */
    public function index()
    {
        $items = PortfolioItem::where('display', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('portfolio.index', [
            'items' => $items,
        ]);
    }
}
