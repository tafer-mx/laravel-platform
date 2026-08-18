<?php

use TAFER\Core\Context\RequestCtxRelation;
use TAFER\Core\Context\StoryblokBlockContext;

/**
 * Tests para RequestCtxRelation - Stack Management
 *
 * RequestCtxRelation gestiona un stack (pila) de contextos de Storyblok durante
 * el rendering de componentes. Este test verifica:
 *
 * 1. Stack vacío inicial: Al inicio, el stack debe estar vacío y retornar contexto vacío
 * 2. Enter/Leave: Poder agregar y remover contextos del stack (push/pop)
 * 3. Current: Obtener siempre el contexto en el tope del stack
 * 4. Depth: Verificar la profundidad del stack
 * 5. Reset: Limpiar todo el stack
 *
 * Escenario típico:
 * - PageController inicial → enter(contexto de página)
 * - Componente con context_relation → enter(nuevo contexto)
 * - Componente hijo accede → current() retorna el contexto más reciente
 * - Componente termina → leave() remueve ese contexto
 * - Siguiente componente → current() retorna el contexto anterior
 */

it('starts with empty stack', function () {
    $ctxRelation = new RequestCtxRelation();

    expect($ctxRelation->isEmpty())->toBeTrue()
        ->and($ctxRelation->depth())->toBe(0)
        ->and($ctxRelation->current()->isEmpty())->toBeTrue();
});

it('can push contexts onto the stack', function () {
    $ctxRelation = new RequestCtxRelation();

    $context1 = new StoryblokBlockContext(content: ['field1' => 'value1']);
    $context2 = new StoryblokBlockContext(content: ['field2' => 'value2']);

    $ctxRelation->enter($context1);
    $ctxRelation->enter($context2);

    expect($ctxRelation->depth())->toBe(2)
        ->and($ctxRelation->isEmpty())->toBeFalse();
});

it('returns the most recent context from the stack', function () {
    $ctxRelation = new RequestCtxRelation();

    $context1 = new StoryblokBlockContext(content: ['field' => 'first']);
    $context2 = new StoryblokBlockContext(content: ['field' => 'second']);

    $ctxRelation->enter($context1);
    $ctxRelation->enter($context2);

    $current = $ctxRelation->current();

    expect($current->get('field'))->toBe('second');
});

it('can pop contexts from the stack', function () {
    $ctxRelation = new RequestCtxRelation();

    $context1 = new StoryblokBlockContext(content: ['field' => 'first']);
    $context2 = new StoryblokBlockContext(content: ['field' => 'second']);

    $ctxRelation->enter($context1);
    $ctxRelation->enter($context2);

    // Verificar que el actual es el segundo
    expect($ctxRelation->current()->get('field'))->toBe('second');

    // Remover el segundo
    $ctxRelation->leave();

    // Ahora debe retornar el primero
    expect($ctxRelation->current()->get('field'))->toBe('first')
        ->and($ctxRelation->depth())->toBe(1);
});

it('returns empty context when all contexts are popped', function () {
    $ctxRelation = new RequestCtxRelation();

    $context = new StoryblokBlockContext(content: ['field' => 'value']);

    $ctxRelation->enter($context);
    $ctxRelation->leave();

    expect($ctxRelation->current()->isEmpty())->toBeTrue()
        ->and($ctxRelation->depth())->toBe(0);
});

it('handles leave on empty stack gracefully', function () {
    $ctxRelation = new RequestCtxRelation();

    // No debe lanzar error
    $ctxRelation->leave();

    expect($ctxRelation->depth())->toBe(0)
        ->and($ctxRelation->current()->isEmpty())->toBeTrue();
});

it('can reset the entire stack', function () {
    $ctxRelation = new RequestCtxRelation();

    $context1 = new StoryblokBlockContext(content: ['field1' => 'value1']);
    $context2 = new StoryblokBlockContext(content: ['field2' => 'value2']);
    $context3 = new StoryblokBlockContext(content: ['field3' => 'value3']);

    $ctxRelation->enter($context1);
    $ctxRelation->enter($context2);
    $ctxRelation->enter($context3);

    expect($ctxRelation->depth())->toBe(3);

    $ctxRelation->reset();

    expect($ctxRelation->depth())->toBe(0)
        ->and($ctxRelation->isEmpty())->toBeTrue()
        ->and($ctxRelation->current()->isEmpty())->toBeTrue();
});

it('simulates nested component rendering', function () {
    $ctxRelation = new RequestCtxRelation();

    // Contexto de página inicial
    $pageContext = new StoryblokBlockContext(content: ['page_title' => 'Home']);
    $ctxRelation->enter($pageContext);

    expect($ctxRelation->current()->get('page_title'))->toBe('Home')
        ->and($ctxRelation->depth())->toBe(1);

    // Componente con context_relation entra
    $offerContext = new StoryblokBlockContext(content: [
        'offer_title' => 'Summer Sale',
        'discount' => '20%',
    ]);
    $ctxRelation->enter($offerContext);

    expect($ctxRelation->current()->get('offer_title'))->toBe('Summer Sale')
        ->and($ctxRelation->depth())->toBe(2);

    // Otro componente anidado con context_relation
    $detailContext = new StoryblokBlockContext(content: ['detail' => 'Limited time']);
    $ctxRelation->enter($detailContext);

    expect($ctxRelation->current()->get('detail'))->toBe('Limited time')
        ->and($ctxRelation->depth())->toBe(3);

    // Se sale del componente más profundo
    $ctxRelation->leave();
    expect($ctxRelation->current()->get('offer_title'))->toBe('Summer Sale')
        ->and($ctxRelation->depth())->toBe(2);

    // Se sale del componente de oferta
    $ctxRelation->leave();
    expect($ctxRelation->current()->get('page_title'))->toBe('Home')
        ->and($ctxRelation->depth())->toBe(1);

    // Se sale de la página
    $ctxRelation->leave();
    expect($ctxRelation->current()->isEmpty())->toBeTrue()
        ->and($ctxRelation->depth())->toBe(0);
});

it('enter returns the same context for chaining', function () {
    $ctxRelation = new RequestCtxRelation();

    $context = new StoryblokBlockContext(content: ['field' => 'value']);
    $returned = $ctxRelation->enter($context);

    expect($returned)->toBe($context)
        ->and($returned->get('field'))->toBe('value');
});
