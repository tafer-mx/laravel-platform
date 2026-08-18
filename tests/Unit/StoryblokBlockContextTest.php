<?php

use TAFER\Core\Context\StoryblokBlockContext;

/**
 * Tests para StoryblokBlockContext - Data Structure & Normalization
 *
 * StoryblokBlockContext es una estructura de datos inmutable que representa
 * el contenido de una story de Storyblok. Este test verifica:
 *
 * 1. Creación de contextos vacíos y con datos
 * 2. Acceso a campos con get() y has()
 * 3. Normalización de componentes específicos (pdf-document, offer_data)
 * 4. Resolución de stories y creación de nuevos contextos
 * 5. Soporte para notación de punto (nested fields)
 *
 * Normalización:
 * Algunos componentes de Storyblok tienen estructuras complejas. La normalización
 * extrae y mapea campos específicos a nombres más simples y consistentes.
 *
 * Ejemplo: pdf-document
 * - Input: { pdf: { filename: 'file.pdf', alt: 'My PDF' } }
 * - Output: { link: 'file.pdf', alt_text: 'My PDF' }
 *
 * Esto permite que las vistas usen campos consistentes independientemente de
 * la estructura interna del componente.
 */

it('creates an empty context', function () {
    $context = StoryblokBlockContext::empty();

    expect($context->isEmpty())->toBeTrue()
        ->and($context->story)->toBeNull()
        ->and($context->content)->toBeNull();
});

it('creates a context with content', function () {
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'field' => 'value']
    );

    expect($context->isEmpty())->toBeFalse()
        ->and($context->content)->toBe(['component' => 'test', 'field' => 'value']);
});

it('gets field values from content', function () {
    $context = new StoryblokBlockContext(
        content: [
            'component' => 'test',
            'title' => 'My Title',
            'description' => 'My Description',
        ]
    );

    expect($context->get('title'))->toBe('My Title')
        ->and($context->get('description'))->toBe('My Description')
        ->and($context->get('component'))->toBe('test');
});

it('returns default value when field not found', function () {
    $context = new StoryblokBlockContext(
        content: ['component' => 'test']
    );

    expect($context->get('missing_field'))->toBeNull()
        ->and($context->get('missing_field', 'default'))->toBe('default');
});

it('checks if field exists', function () {
    $context = new StoryblokBlockContext(
        content: [
            'component' => 'test',
            'title' => 'My Title',
        ]
    );

    expect($context->has('title'))->toBeTrue()
        ->and($context->has('missing'))->toBeFalse();
});

it('supports nested field access with dot notation', function () {
    $context = new StoryblokBlockContext(
        content: [
            'component' => 'test',
            'meta' => [
                'author' => 'John Doe',
                'date' => '2024-01-01',
            ],
        ]
    );

    expect($context->get('meta.author'))->toBe('John Doe')
        ->and($context->get('meta.date'))->toBe('2024-01-01')
        ->and($context->has('meta.author'))->toBeTrue()
        ->and($context->has('meta.missing'))->toBeFalse();
});

it('normalizes pdf-document component', function () {
    $story = [
        'content' => [
            'component' => 'pdf-document',
            'pdf' => [
                'filename' => 'https://example.com/document.pdf',
                'alt' => 'My PDF Document',
            ],
        ],
    ];

    $parent = StoryblokBlockContext::empty();
    $context = $parent->withResolvedStory($story);

    expect($context->get('component'))->toBe('pdf-document')
        ->and($context->get('link'))->toBe('https://example.com/document.pdf')
        ->and($context->get('alt_text'))->toBe('My PDF Document');
});

it('normalizes offer_data component', function () {
    $story = [
        'content' => [
            'component' => 'offer_data',
            'general_offer_link' => 'https://example.com/offer',
            'general_offer_title' => 'Summer Sale',
            'discount' => '20%',
            'status' => 'active',
            'validity_activation_type' => 'date_range',
            'validity_recurring_days' => ['monday', 'friday'],
            'validity_date_time_range' => [
                'start' => '2024-06-01',
                'end' => '2024-08-31',
            ],
        ],
    ];

    $parent = StoryblokBlockContext::empty();
    $context = $parent->withResolvedStory($story);

    expect($context->get('component'))->toBe('offer_data')
        ->and($context->get('link'))->toBe('https://example.com/offer')
        ->and($context->get('offer_title'))->toBe('Summer Sale')
        ->and($context->get('discount'))->toBe('20%')
        ->and($context->get('status'))->toBe('active')
        ->and($context->get('activation'))->toBe('date_range')
        ->and($context->get('date_recurring_days'))->toBe(['monday', 'friday'])
        ->and($context->get('date_time_range'))->toBe([
            'start' => '2024-06-01',
            'end' => '2024-08-31',
        ]);
});

it('does not normalize unknown components', function () {
    $story = [
        'content' => [
            'component' => 'custom-component',
            'field1' => 'value1',
            'field2' => 'value2',
        ],
    ];

    $parent = StoryblokBlockContext::empty();
    $context = $parent->withResolvedStory($story);

    // Debe mantener la estructura original
    expect($context->get('component'))->toBe('custom-component')
        ->and($context->get('field1'))->toBe('value1')
        ->and($context->get('field2'))->toBe('value2');
});

it('handles stories without content', function () {
    $story = [];

    $parent = StoryblokBlockContext::empty();
    $context = $parent->withResolvedStory($story);

    expect($context->isEmpty())->toBeTrue();
});

it('only normalizes existing fields', function () {
    $story = [
        'content' => [
            'component' => 'pdf-document',
            'pdf' => [
                'filename' => 'document.pdf',
                // alt no está presente
            ],
        ],
    ];

    $parent = StoryblokBlockContext::empty();
    $context = $parent->withResolvedStory($story);

    expect($context->get('link'))->toBe('document.pdf')
        ->and($context->get('alt_text'))->toBeNull();
});

it('creates new immutable contexts on withResolvedStory', function () {
    $parent = new StoryblokBlockContext(
        content: ['parent_field' => 'parent_value']
    );

    $story = [
        'content' => [
            'component' => 'test',
            'child_field' => 'child_value',
        ],
    ];

    $child = $parent->withResolvedStory($story);

    // Parent no debe cambiar
    expect($parent->get('parent_field'))->toBe('parent_value')
        ->and($parent->has('child_field'))->toBeFalse();

    // Child debe tener solo el nuevo contenido
    expect($child->get('child_field'))->toBe('child_value')
        ->and($child->has('parent_field'))->toBeFalse();
});

it('handles null values in normalization gracefully', function () {
    $story = [
        'content' => [
            'component' => 'pdf-document',
            'pdf' => [
                'filename' => null,
                'alt' => null,
            ],
        ],
    ];

    $parent = StoryblokBlockContext::empty();
    $context = $parent->withResolvedStory($story);

    // Los campos null no deben aparecer en el contexto normalizado
    expect($context->get('link'))->toBeNull()
        ->and($context->get('alt_text'))->toBeNull();
});
