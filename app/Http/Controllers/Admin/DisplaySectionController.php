<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DisplaySectionController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->redirect();
    }

    public function create(): RedirectResponse
    {
        return $this->redirect();
    }

    public function store(): RedirectResponse
    {
        return $this->redirect();
    }

    public function edit(mixed $display_section): RedirectResponse
    {
        return $this->redirect();
    }

    public function update(mixed $display_section): RedirectResponse
    {
        return $this->redirect();
    }

    public function destroy(mixed $display_section): RedirectResponse
    {
        return $this->redirect();
    }

    private function redirect(): RedirectResponse
    {
        return redirect()
            ->route('admin.categories.index')
            ->with('info', 'أقسام العرض أصبحت جزءاً من شجرة التصنيفات. أدر التبويبات من صفحة الأقسام.');
    }
}
