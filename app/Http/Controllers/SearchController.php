<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PropertyIndex;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Top-bar search: owner / agent listings, builder projects (view only, reviewed by BRM) and users. */
class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $properties = $q === '' ? collect() : PropertyIndex::paginate(['tab' => 'all', 'source' => '', 'type' => '', 'q' => $q], 8);
        $projects = $q === '' ? collect() : PropertyIndex::paginate(['tab' => 'builder', 'source' => '', 'type' => '', 'q' => $q], 8);
        $users = $q === '' ? collect() : User::query()
            ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('mobile', 'like', "%{$q}%"))
            ->orderBy('name')->limit(8)->get();

        return view('pages.search', ['q' => $q, 'properties' => $properties, 'projects' => $projects, 'users' => $users]);
    }
}
