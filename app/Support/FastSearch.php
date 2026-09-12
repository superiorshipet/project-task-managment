<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FastSearch
{
    public static function apply(Builder $query, ?string $keyword, string $column = 'search_text'): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        $tokens = collect(preg_split('/\s+/u', $keyword) ?: [])
            ->map(fn (string $token) => trim(preg_replace('/[^\pL\pN_-]+/u', '', $token) ?? ''))
            ->filter()
            ->values();

        if ($tokens->isEmpty()) {
            return $query;
        }

        if ($tokens->contains(fn (string $token) => Str::length($token) < 3)) {
            return $query->where(function (Builder $query) use ($tokens, $column): void {
                foreach ($tokens as $token) {
                    $query->whereRaw("LOWER({$column}) REGEXP ?", [self::wordPrefixPattern($token)]);
                }
            });
        }

        $booleanQuery = $tokens
            ->map(fn (string $token) => '+'.$token.'*')
            ->implode(' ');

        return $query->whereRaw("MATCH({$column}) AGAINST (? IN BOOLEAN MODE)", [$booleanQuery]);
    }

    private static function wordPrefixPattern(string $token): string
    {
        return '(^|[[:space:][:punct:]])'.preg_quote(Str::lower($token), '/');
    }
}
