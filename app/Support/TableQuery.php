<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Search, filters, sorting and page size for a staff table, read from the query
 * string. Only the columns and values named in code are ever applied; anything
 * else in the address is ignored.
 */
class TableQuery
{
    private const PAGE_SIZES = [25, 50, 100];

    private array $state = [];

    private function __construct(private Builder $query, private Request $request) {}

    public static function for(Builder $query, Request $request): self
    {
        return new self($query, $request);
    }

    /** @param list<string> $columns */
    public function search(array $columns): self
    {
        $term = trim($this->input('q'));
        $this->state['q'] = $term;
        if ($term !== '') {
            // Wildcards typed by the user are matched literally.
            $like = '%'.addcslashes($term, '\\%_').'%';
            $this->query->where(function (Builder $q) use ($columns, $like) {
                foreach ($columns as $column) {
                    $q->orWhereLike($column, $like);
                }
            });
        }

        return $this;
    }

    /** @param list<string> $allowed */
    public function filter(string $name, Closure $apply, array $allowed): self
    {
        $value = $this->input($name);
        $this->state[$name] = in_array($value, $allowed, true) ? $value : '';
        if ($this->state[$name] !== '') {
            $apply($this->query, $value);
        }

        return $this;
    }

    /** @param array<string, string> $columns public name => database column */
    public function sort(array $columns, string $default, string $direction = 'desc'): self
    {
        $key = $this->input('sort');
        $key = isset($columns[$key]) ? $key : $default;
        $dir = $this->input('dir');
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : $direction;
        $this->state += ['sort' => $key, 'dir' => $dir];
        // The id keeps rows with equal values in the same order from one page to the next.
        $this->query->orderBy($columns[$key], $dir)->orderBy($this->query->getModel()->getQualifiedKeyName(), $dir);

        return $this;
    }

    public function paginate(): LengthAwarePaginator
    {
        $size = (int) $this->input('per');
        $this->state['per'] = in_array($size, self::PAGE_SIZES, true) ? $size : self::PAGE_SIZES[0];

        return $this->query->paginate($this->state['per'])->withQueryString();
    }

    /** What is applied, for the page controls. Call after paginate(). */
    public function state(): array
    {
        return $this->state;
    }

    private function input(string $key): string
    {
        $value = $this->request->query($key);

        return is_string($value) ? mb_substr($value, 0, 100) : '';
    }
}
