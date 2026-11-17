<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class EditorialSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)
            || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value)
            || in_array($value, ['admin', 'login', 'cauta', 'coperti', 'up', 'media', 'poze', 'video', 'forum', 'jocuri', 'contact', 'anunturi', 'anunturi-adoptii', 'anunturi-cabinete-veterinare'], true)
            || Article::where('slug', $value)->exists()
            || Category::where('slug', $value)->exists()
            || DB::table('legacy_urls')->where('path', $value)->exists()) {
            $fail('Adresa este rezervată, există deja sau nu are formatul corect.');
        }
    }
}
