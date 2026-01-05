<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class TestUIController extends Controller
{
    /**
     * テストUIを表示
     */
    public function index(): View
    {
        return view('test-ui');
    }
}
