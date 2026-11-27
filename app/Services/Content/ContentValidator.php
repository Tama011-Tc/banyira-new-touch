<?php

namespace App\Services\Content;

use App\Models\Media;
use App\Rules\SafeLink;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Checks page content against the block catalogue and returns a clean copy:
 * only known fields, in the expected types. Drafts may be unfinished; content
 * being published must have every required field.
 */
class ContentValidator
{
    private const MAX_BLOCKS = 40;

    private const ANCHOR = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    public const NAV_COLUMNS = ['businesses', 'investors', 'company'];

    public function validate(array $content, bool $forPublishing = false): array
    {
        $catalogue = BlockCatalogue::all();

        $errors = [];
        $head = $this->check($content, [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:300'],
            'nav' => ['nullable', 'array'],
            'nav.header' => ['nullable', 'boolean'],
            'nav.header_label' => ['nullable', 'string', 'max:30'],
            'nav.footer' => ['nullable', Rule::in(self::NAV_COLUMNS)],
            'nav.footer_label' => ['nullable', 'string', 'max:60'],
            'nav.order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'blocks' => [$forPublishing ? 'required' : 'present', 'array', 'max:'.self::MAX_BLOCKS],
        ], '', $errors);

        $blocks = [];
        foreach (array_values((array) ($content['blocks'] ?? [])) as $i => $block) {
            $type = is_array($block) ? ($block['type'] ?? null) : null;
            if (! is_string($type) || ! isset($catalogue[$type])) {
                $errors["blocks.{$i}.type"] = 'This section type is not available.';

                continue;
            }
            $visible = (bool) ($block['visible'] ?? true);
            // A hidden section is never shown, so it does not have to be complete.
            $strict = $forPublishing && $visible;
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $fields = $catalogue[$type]['fields'];
            $this->check($data, $this->rules($fields, '', $strict), "blocks.{$i}.data.", $errors);

            $id = $block['id'] ?? null;
            $blocks[] = [
                'id' => is_string($id) && preg_match('/^[A-Za-z0-9-]{1,40}$/', $id) ? $id : (string) Str::uuid(),
                'type' => $type,
                'visible' => $visible,
                'data' => $this->clean($data, $fields),
            ];
        }

        if ($forPublishing && ! collect($blocks)->contains('visible', true)) {
            $errors['blocks'] = 'Add at least one visible section before publishing.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $nav = $head['nav'] ?? [];

        return [
            'title' => $head['title'],
            'description' => $head['description'] ?? '',
            'nav' => [
                'header' => (bool) ($nav['header'] ?? false),
                'header_label' => $nav['header_label'] ?? '',
                'footer' => $nav['footer'] ?? '',
                'footer_label' => $nav['footer_label'] ?? '',
                'order' => (int) ($nav['order'] ?? 100),
            ],
            'blocks' => $blocks,
        ];
    }

    private function check(array $data, array $rules, string $prefix, array &$errors): array
    {
        // The editor shows each message beside its field, so a message names the field, not its path.
        $names = [];
        foreach (array_keys($rules) as $key) {
            $names[$key] = str_replace('_', ' ', Str::afterLast(Str::chopEnd($key, '.*'), '.'));
        }
        $validator = Validator::make($data, $rules, [], $names);
        foreach ($validator->errors()->toArray() as $key => $messages) {
            $errors[$prefix.$key] = $messages[0];
        }

        return $validator->errors()->isEmpty() ? $validator->validated() : [];
    }

    private function rules(array $fields, string $prefix, bool $strict): array
    {
        $rules = [];
        foreach ($fields as $f) {
            $key = $prefix.$f['key'];
            $presence = $strict && $f['required'] ? 'required' : 'nullable';
            $rules[$key] = match ($f['kind']) {
                'boolean' => ['nullable', 'boolean'],
                'select' => ['nullable', Rule::in(array_column($f['options'], 'value'))],
                'media' => [$presence, 'integer', Rule::exists(Media::class, 'id')],
                'items' => [$presence, 'array', 'max:'.$f['max']],
                default => array_values(array_filter([
                    $presence, 'string', 'max:'.$f['max'],
                    match ($f['format'] ?? null) {
                        'link' => new SafeLink,
                        'anchor' => 'regex:'.self::ANCHOR,
                        default => null,
                    },
                ])),
            };
            if ($f['kind'] === 'items') {
                $rules[$key.'.*'] = ['array'];
                $rules += $this->rules($f['fields'], $key.'.*.', $strict);
            }
        }

        return $rules;
    }

    private function clean(array $data, array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $value = $data[$f['key']] ?? null;
            $out[$f['key']] = match ($f['kind']) {
                'boolean' => (bool) $value,
                'select' => is_string($value) && $value !== '' ? $value : $f['options'][0]['value'],
                'media' => is_numeric($value) ? (int) $value : null,
                'items' => array_values(array_map(fn ($item) => $this->clean((array) $item, $f['fields']), is_array($value) ? $value : [])),
                default => is_string($value) ? $value : '',
            };
        }

        return $out;
    }
}
