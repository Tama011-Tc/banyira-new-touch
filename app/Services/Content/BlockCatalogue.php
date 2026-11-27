<?php

namespace App\Services\Content;

/**
 * Every kind of section a page can be built from, and its fields. This is the
 * single definition: validation is derived from it and the editor draws its
 * forms from it. Each type also needs a component in
 * resources/js/Components/Blocks (see BlockRenderer.vue).
 */
class BlockCatalogue
{
    public const ICONS = ['bank', 'users', 'check', 'log', 'shield', 'verified', 'scale', 'wallet', 'savings', 'receipt', 'calendar', 'message', 'doc', 'briefcase', 'building', 'trend', 'target', 'help'];

    /** Live information a block shows, loaded only when a page uses it. */
    public const NEEDS = [
        'products' => ['products'],
        'fees' => ['rules'],
        'review' => ['rules'],
        'investment_limits' => ['rules', 'minOffer'],
    ];

    /** @return array<string, array{label: string, description: string, fields: list<array>}> */
    public static function all(): array
    {
        return [
            'hero' => self::type('Opening banner', 'Large two-line headline with buttons and an optional funding example.', [
                self::text('headline', 'Headline', required: true),
                self::text('headline_muted', 'Second line (grey)'),
                self::textarea('lead', 'Introduction'),
                self::buttons(),
                self::boolean('show_example', 'Show the funding example'),
                self::text('example_title', 'Example heading'),
                self::textarea('example_caption', 'Example caption'),
            ]),
            'page_header' => self::type('Page title', 'Large title and introduction at the top of a page.', [
                self::text('title', 'Title', required: true),
                self::textarea('subtitle', 'Introduction'),
                self::buttons(),
            ]),
            'text' => self::type('Text', 'A heading with paragraphs. Leave a blank line between paragraphs.', [
                ...self::heading(),
                self::textarea('body', 'Text', max: 5000, required: true),
                self::links(),
                ...self::appearance(),
            ]),
            'columns' => self::type('Columns', 'Short titled points side by side.', [
                ...self::heading(),
                self::items('items', 'Columns', 'Column', [self::text('title', 'Title', required: true), self::textarea('text', 'Text', required: true)]),
                ...self::appearance(),
            ]),
            'steps' => self::type('Numbered steps', 'Titled steps, numbered in order.', [
                ...self::heading(),
                self::items('items', 'Steps', 'Step', [self::text('title', 'Title', required: true), self::textarea('text', 'Text', required: true)]),
                ...self::appearance(),
            ]),
            'list' => self::type('Divided list', 'One line per item, with an optional description.', [
                ...self::heading(),
                self::items('items', 'Items', 'Item', [self::text('title', 'Item', max: 200, required: true), self::textarea('text', 'Description')]),
                self::select('columns', 'Columns', [['1', 'One'], ['2', 'Two']]),
                self::links(),
                ...self::appearance(),
            ]),
            'features' => self::type('Points with icons', 'Points that each have a small icon.', [
                ...self::heading(),
                self::items('items', 'Points', 'Point', [
                    self::select('icon', 'Icon', array_map(fn ($i) => [$i, ucfirst($i)], self::ICONS)),
                    self::text('title', 'Title', required: true),
                    self::textarea('text', 'Text', required: true),
                ]),
                ...self::appearance(),
            ]),
            'review' => self::type('Assessment and risk classes', 'Numbered checks followed by the live risk-class table.', [
                ...self::heading(),
                self::items('items', 'Checks', 'Check', [self::text('title', 'Check', max: 200, required: true)]),
                self::text('footnote', 'Note under the table', max: 200),
                ...self::appearance(),
            ]),
            'warning' => self::type('Important statement', 'Statements with an amber edge, for risk and other warnings.', [
                ...self::heading(),
                self::textarea('body', 'Text', max: 3000, required: true),
                ...self::appearance(),
            ]),
            'notice' => self::type('Notice', 'A short highlighted note.', [
                self::select('tone', 'Tone', [['info', 'Information'], ['warn', 'Warning']]),
                self::text('title', 'Title'),
                self::textarea('body', 'Text', required: true),
            ]),
            'journey' => self::type('Phased guide', 'Phases, each with numbered steps.', [
                self::items('phases', 'Phases', 'Phase', [
                    self::text('title', 'Phase', required: true),
                    self::items('steps', 'Steps', 'Step', [self::text('title', 'Title', required: true), self::textarea('text', 'Text', required: true)]),
                ], max: 8),
            ]),
            'faq' => self::type('Questions and answers', 'Questions that open to show the answer.', [
                ...self::heading(),
                self::items('items', 'Questions', 'Question', [self::text('question', 'Question', max: 200, required: true), self::textarea('answer', 'Answer', required: true)]),
                ...self::appearance(),
            ]),
            'documents' => self::type('Policy sections', 'Headed sections that can be linked to directly, such as terms and privacy.', [
                self::items('items', 'Sections', 'Section', [
                    self::text('anchor', 'Link name (after # in the address)', max: 40, format: 'anchor'),
                    self::text('title', 'Heading', required: true),
                    self::textarea('body', 'Text', max: 8000, required: true),
                ]),
            ]),
            'products' => self::type('Financing products', 'Live list of the active products.', [
                ...self::heading(),
                self::select('display', 'Show as', [['list', 'List'], ['cards', 'Cards']]),
                ...self::appearance(),
            ]),
            'fees' => self::type('Fees and limits', 'Live fee, interest ceiling and risk-class tables.', [
                ...self::heading(),
                ...self::appearance(),
            ]),
            'investment_limits' => self::type('Investment limits', 'Live minimum offer, maximum rate and longest periods.', [
                ...self::heading(),
                ...self::appearance(),
            ]),
            'funding_example' => self::type('Shared funding illustration', 'Explains how several investors fund one business.', [
                ...self::heading(),
                self::text('title', 'Sub-heading'),
                self::textarea('body', 'Explanation'),
                self::text('caption', 'Illustration caption', max: 300, required: true),
                ...self::appearance(),
            ]),
            'image' => self::type('Image', 'A picture from the media library.', [
                self::media('media_id', 'Image', required: true),
                self::text('caption', 'Caption', max: 300),
            ]),
            'contact_form' => self::type('Contact form', 'The enquiry form with office details beside it.', [
                self::items('items', 'Details beside the form', 'Detail', [self::text('title', 'Heading', required: true), self::textarea('text', 'Text', required: true)], max: 8),
            ]),
            'call_to_action' => self::type('Closing call to action', 'A final invitation with a button. Two panels show side by side on green.', [
                self::items('panels', 'Panels', 'Panel', [
                    self::text('title', 'Title', required: true),
                    self::textarea('text', 'Text'),
                    self::text('button_label', 'Button text', max: 60, required: true),
                    self::text('button_href', 'Button link', max: 255, required: true, format: 'link'),
                ], max: 2),
            ]),
        ];
    }

    /** @return list<string> */
    public static function types(): array
    {
        return array_keys(self::all());
    }

    private static function type(string $label, string $description, array $fields): array
    {
        return compact('label', 'description', 'fields');
    }

    private static function heading(): array
    {
        return [self::text('heading', 'Section heading'), self::textarea('intro', 'Text under the heading', max: 400)];
    }

    private static function appearance(): array
    {
        return [
            self::select('layout', 'Layout', [['section', 'Wide section'], ['compact', 'Compact row']]),
            self::boolean('shaded', 'White background (wide sections)'),
            self::text('anchor', 'Link name (after # in the address)', max: 40, format: 'anchor'),
        ];
    }

    private static function buttons(): array
    {
        return self::items('buttons', 'Buttons', 'Button', self::linkFields(), max: 3, required: false);
    }

    private static function links(): array
    {
        return self::items('links', 'Links underneath', 'Link', self::linkFields(), max: 4, required: false);
    }

    private static function linkFields(): array
    {
        return [self::text('label', 'Text', max: 60, required: true), self::text('href', 'Link', max: 255, required: true, format: 'link')];
    }

    private static function text(string $key, string $label, int $max = 160, bool $required = false, ?string $format = null): array
    {
        return array_filter(['key' => $key, 'label' => $label, 'kind' => 'text', 'max' => $max, 'required' => $required, 'format' => $format], fn ($v) => $v !== null);
    }

    private static function textarea(string $key, string $label, int $max = 1200, bool $required = false): array
    {
        return ['key' => $key, 'label' => $label, 'kind' => 'textarea', 'max' => $max, 'required' => $required];
    }

    private static function boolean(string $key, string $label): array
    {
        return ['key' => $key, 'label' => $label, 'kind' => 'boolean', 'required' => false];
    }

    /** The first option is the default. @param list<array{0: string, 1: string}> $options */
    private static function select(string $key, string $label, array $options): array
    {
        return ['key' => $key, 'label' => $label, 'kind' => 'select', 'required' => true, 'options' => array_map(fn ($o) => ['value' => $o[0], 'label' => $o[1]], $options)];
    }

    private static function media(string $key, string $label, bool $required = false): array
    {
        return ['key' => $key, 'label' => $label, 'kind' => 'media', 'required' => $required];
    }

    private static function items(string $key, string $label, string $itemLabel, array $fields, int $max = 30, bool $required = true): array
    {
        return ['key' => $key, 'label' => $label, 'kind' => 'items', 'itemLabel' => $itemLabel, 'fields' => $fields, 'max' => $max, 'required' => $required];
    }
}
