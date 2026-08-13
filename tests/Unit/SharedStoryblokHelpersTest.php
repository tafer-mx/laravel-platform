<?php

use Carbon\Carbon;
use Orchestra\Testbench\TestCase;
use TAFER\Core\Storyblok\InlineHtmlSanitizer;
use TAFER\Core\Storyblok\StoryblokComponentHelper;
use TAFER\Core\Storyblok\StoryblokRichTextHelper;
use TAFER\Core\Support\ConditionalOfferHelper;

uses(TestCase::class);

it('uses Villa Palmar image transformation rules', function () {
    $image = 'https://a.storyblok.com/f/123/1600x900/hash/image.jpg';

    expect(storyblokImage($image, ['width' => 1200, 'format' => 'jpg']))
        ->toContain('/m/1200x/')
        ->toContain('format(webp)')
        ->and(storyblokImage('https://a.storyblok.com/f/123/hash/logo.svg', ['width' => 1200]))
        ->toBe('https://a.storyblok.com/f/123/hash/logo.svg')
        ->and(storyblokImage('https://example.com/image.jpg', ['width' => 1200]))
        ->toBe('https://example.com/image.jpg');
});

it('builds responsive Storyblok image values', function () {
    $image = 'https://a.storyblok.com/f/123/1600x900/hash/image.jpg';

    expect(storyblokImageResponsive($image))->toHaveKeys(['mobile', 'tablet', 'desktop', 'large'])
        ->and(storyblokImageSrcset($image, [480, 1200]))
        ->toContain('480w')
        ->toContain('1200w');
});

it('sanitizes inline Storyblok html using the Villa Palmar allowlist', function () {
    $html = InlineHtmlSanitizer::sanitize(
        'Title<em class="bad">Em</em><strong onclick="bad()">Bold</strong><script>alert(1)</script>',
    );

    expect($html)->toBe('Title<em>Em</em><strong>Bold</strong>')
        ->and(InlineHtmlSanitizer::stripTags('<strong>Title</strong>'))->toBe('Title');
});

it('finds and augments nested Storyblok components', function () {
    $story = ['content' => ['body' => [['component' => 'target', 'title' => 'Found']]]];

    expect(StoryblokComponentHelper::getFirstField($story, 'target', 'title'))->toBe('Found');

    StoryblokComponentHelper::addDataToBlok($story, 'target', 'dynamic', true);

    expect($story['content']['body'][0]['dynamic'])->toBeTrue()
        ->and(StoryblokComponentHelper::sanitizeGapClasses('gap-4 absolute gap-x-2'))
        ->toBe('gap-4 gap-x-2');
});

it('renders rich text links with Villa Palmar link policies', function () {
    config(['app.url' => 'https://www.villapalmarcancun.com']);

    $node = [
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'External',
                'marks' => [[
                    'type' => 'link',
                    'attrs' => [
                        'href' => 'http://example.com',
                        'linktype' => 'url',
                    ],
                ]],
            ]],
        ]],
    ];

    $html = StoryblokRichTextHelper::render($node);

    expect($html)->toContain('href="https://example.com"')
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer nofollow"');
});

it('evaluates conditional offers across midnight', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-10 23:00:00', 'America/Mexico_City'));

    $result = ConditionalOfferHelper::isOfferActive(
        ['Monday'],
        [['Start_Time' => '22:00', 'End_Time' => '02:00']],
    );

    expect($result['isActive'])->toBeTrue()
        ->and(ConditionalOfferHelper::formatForJs($result['nextEnd']))->not->toBeNull();

    Carbon::setTestNow();
});
