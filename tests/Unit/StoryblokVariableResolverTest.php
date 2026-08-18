<?php

use TAFER\Core\Context\StoryblokBlockContext;
use TAFER\Core\Services\StoryblokVariableResolver;

/**
 * Tests para StoryblokVariableResolver - Variable Resolution & Text Cleanup
 *
 * StoryblokVariableResolver reemplaza placeholders {{ variable }} en textos
 * con valores del contexto actual. Este test verifica:
 *
 * 1. Resolución de variables: Reemplazar {{ variable }} con valores del contexto
 * 2. Variables faltantes: Remover placeholders cuando no hay valor
 * 3. Limpieza de texto: Remover separadores huérfanos, espacios extra, etc.
 * 4. Casos especiales: Variables con puntos, guiones, múltiples variables
 * 5. Validación: Nombres de variables válidos
 * 6. Extracción: Obtener lista de variables de un texto
 *
 * Uso típico en vistas Blade:
 * ```php
 * $buttonText = "Download {{ document_type }} - {{ file_size }}";
 * $resolved = $variableResolver->resolve($buttonText, $context);
 * // Con contexto: "Download PDF - 2.5MB"
 * // Sin contexto: "Download"
 * ```
 *
 * La limpieza automática asegura que el texto resultante sea limpio y profesional,
 * sin separadores huérfanos como "Download - " o espacios extra.
 */

it('resolves single variable', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'link' => 'LINK']
    );

    $result = $resolver->resolve('DOWNLOAD {{ link }}', $context);

    expect($result)->toBe('DOWNLOAD LINK');
});

it('removes variable when not found in context', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test']
    );

    $result = $resolver->resolve('DOWNLOAD {{ link }}', $context);

    expect($result)->toBe('DOWNLOAD');
});

it('resolves variable at start of text', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'alt_text' => 'PDF']
    );

    $result = $resolver->resolve('{{ alt_text }} DOWNLOAD', $context);

    expect($result)->toBe('PDF DOWNLOAD');
});

it('resolves multiple variables', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: [
            'component' => 'test',
            'alt_text' => 'PROGRAM',
            'document_type' => 'PDF',
        ]
    );

    $result = $resolver->resolve(
        'VIEW {{ alt_text }} {{ document_type }}',
        $context
    );

    expect($result)->toBe('VIEW PROGRAM PDF');
});

it('resolves repeated variables', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'link' => 'DOCUMENT']
    );

    $result = $resolver->resolve('{{ link }} - {{ link }}', $context);

    expect($result)->toBe('DOCUMENT - DOCUMENT');
});

it('handles text without variables', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'link' => 'LINK']
    );

    $result = $resolver->resolve('DOWNLOAD NOW', $context);

    expect($result)->toBe('DOWNLOAD NOW');
});

it('handles null text', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'link' => 'LINK']
    );

    $result = $resolver->resolve(null, $context);

    expect($result)->toBe('');
});

it('handles empty text', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'link' => 'LINK']
    );

    $result = $resolver->resolve('', $context);

    expect($result)->toBe('');
});

it('handles null context', function () {
    $resolver = new StoryblokVariableResolver();

    $result = $resolver->resolve('DOWNLOAD {{ link }}', null);

    expect($result)->toBe('DOWNLOAD');
});

it('handles empty context', function () {
    $resolver = new StoryblokVariableResolver();
    $context = StoryblokBlockContext::empty();

    $result = $resolver->resolve('DOWNLOAD {{ link }}', $context);

    expect($result)->toBe('DOWNLOAD');
});

it('removes orphan separator at end', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'alt_text' => 'PROGRAM']
    );

    $result = $resolver->resolve(
        'DOWNLOAD {{ alt_text }} - {{ document_type }}',
        $context
    );

    expect($result)->toBe('DOWNLOAD PROGRAM');
});

it('removes orphan separator at start', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'document_type' => 'PDF']
    );

    $result = $resolver->resolve(
        '{{ alt_text }} - {{ document_type }}',
        $context
    );

    expect($result)->toBe('PDF');
});

it('handles various separator types', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'text' => 'CONTENT']
    );

    expect($resolver->resolve('{{ text }} - {{ missing }}', $context))->toBe('CONTENT')
        ->and($resolver->resolve('{{ text }} | {{ missing }}', $context))->toBe('CONTENT')
        ->and($resolver->resolve('{{ text }} / {{ missing }}', $context))->toBe('CONTENT')
        ->and($resolver->resolve('{{ text }} – {{ missing }}', $context))->toBe('CONTENT')
        ->and($resolver->resolve('{{ text }} — {{ missing }}', $context))->toBe('CONTENT');
});

it('removes multiple spaces', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'text' => 'WORD']
    );

    $result = $resolver->resolve('DOWNLOAD  {{ text }}  NOW', $context);

    expect($result)->toBe('DOWNLOAD WORD NOW');
});

it('removes spaces before punctuation', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'text' => 'FILE']
    );

    $result = $resolver->resolve('DOWNLOAD {{ text }} .', $context);

    expect($result)->toBe('DOWNLOAD FILE.');
});

it('handles variables with dots in nested notation', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: [
            'component' => 'test',
            'file' => ['name' => 'document.pdf'],
        ]
    );

    $result = $resolver->resolve('{{ file.name }}', $context);

    expect($result)->toBe('document.pdf');
});

it('handles variables with underscores', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'alt_text' => 'TEXT']
    );

    $result = $resolver->resolve('{{ alt_text }}', $context);

    expect($result)->toBe('TEXT');
});

it('handles variables with hyphens', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'document-type' => 'PDF']
    );

    $result = $resolver->resolve('{{ document-type }}', $context);

    expect($result)->toBe('PDF');
});

it('handles variables with extra spaces', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: ['component' => 'test', 'link' => 'URL']
    );

    $result = $resolver->resolve('{{    link    }}', $context);

    expect($result)->toBe('URL');
});

it('ignores non-scalar values', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: [
            'component' => 'test',
            'array_value' => ['nested' => 'value'],
            'text' => 'CONTENT',
        ]
    );

    $result = $resolver->resolve(
        '{{ text }} {{ array_value }}',
        $context
    );

    expect($result)->toBe('CONTENT');
});

it('converts numeric values to string', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: [
            'component' => 'test',
            'count' => 42,
            'price' => 19.99,
        ]
    );

    $result = $resolver->resolve(
        'Items: {{ count }}, Price: ${{ price }}',
        $context
    );

    expect($result)->toBe('Items: 42, Price: $19.99');
});

it('converts boolean values to string', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: [
            'component' => 'test',
            'enabled' => true,
            'disabled' => false,
        ]
    );

    $result = $resolver->resolve(
        'Enabled: {{ enabled }}, Disabled: {{ disabled }}',
        $context
    );

    expect($result)->toBe('Enabled: 1, Disabled:');
});

it('validates variable names', function () {
    $resolver = new StoryblokVariableResolver();

    expect($resolver->isValidVariableName('link'))->toBeTrue()
        ->and($resolver->isValidVariableName('alt_text'))->toBeTrue()
        ->and($resolver->isValidVariableName('document-type'))->toBeTrue()
        ->and($resolver->isValidVariableName('file.name'))->toBeTrue()
        ->and($resolver->isValidVariableName('var123'))->toBeTrue()
        ->and($resolver->isValidVariableName('var name'))->toBeFalse()
        ->and($resolver->isValidVariableName('var@name'))->toBeFalse()
        ->and($resolver->isValidVariableName('var#name'))->toBeFalse();
});

it('extracts variable names from text', function () {
    $resolver = new StoryblokVariableResolver();
    $text = 'DOWNLOAD {{ link }} and {{ alt_text }}';

    $variables = $resolver->extractVariableNames($text);

    expect($variables)->toBe(['link', 'alt_text']);
});

it('extracts repeated variable names', function () {
    $resolver = new StoryblokVariableResolver();
    $text = '{{ link }} - {{ link }}';

    $variables = $resolver->extractVariableNames($text);

    expect($variables)->toBe(['link', 'link']);
});

it('returns empty array when no variables', function () {
    $resolver = new StoryblokVariableResolver();
    $text = 'DOWNLOAD NOW';

    $variables = $resolver->extractVariableNames($text);

    expect($variables)->toBe([]);
});

it('handles real world example from button', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: [
            'component' => 'offer_data',
            'offer_title' => 'Summer Getaway',
            'discount' => '25',
        ]
    );

    $result = $resolver->resolve(
        'Book Now - {{ offer_title }} ({{ discount }}% Off)',
        $context
    );

    expect($result)->toBe('Book Now - Summer Getaway (25% Off)');
});

it('handles missing variables in real world example', function () {
    $resolver = new StoryblokVariableResolver();
    $context = new StoryblokBlockContext(
        content: [
            'component' => 'offer_data',
            'offer_title' => 'Summer Getaway',
            // discount no está presente
        ]
    );

    $result = $resolver->resolve(
        'Book Now - {{ offer_title }} - {{ discount }}% Off',
        $context
    );

    // Cuando discount falta, queda el separador "- " antes de "% Off"
    // Esto es correcto porque "%" no se reconoce como un separador huérfano
    expect($result)->toBe('Book Now - Summer Getaway - % Off');
});
