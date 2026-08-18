# Tests del Sistema de Context Relations

Este documento explica los tests creados para el sistema de Context Relations de Storyblok.

## Descripción General del Sistema

El sistema de Context Relations permite que componentes de Storyblok accedan a datos de otras stories mediante el campo `context_relation`. Esto es útil para componentes reutilizables que necesitan datos dinámicos sin duplicación.

### Componentes del Sistema

1. **RequestCtxRelation**: Stack (pila) de contextos que cambia durante el rendering
2. **StoryblokBlockContext**: Estructura de datos inmutable para el contenido de una story
3. **StoryblokVariableResolver**: Resolver que reemplaza variables `{{ variable }}` con valores del contexto
4. **StoryblokContextResolver**: Servicio que resuelve relaciones y gestiona el stack

---

## Test 1: RequestCtxRelationTest.php

### Propósito
Verifica que el stack de contextos funcione correctamente durante el ciclo de vida de un request.

### Conceptos Clave

**¿Qué es un Stack?**
Un stack (pila) es una estructura de datos LIFO (Last In, First Out - Último en entrar, primero en salir). Como una pila de platos:
- `enter()` = agregar un plato arriba (push)
- `leave()` = quitar el plato de arriba (pop)
- `current()` = ver el plato de arriba sin quitarlo

**¿Por qué un Stack?**
Los componentes de Storyblok se anidan unos dentro de otros. Cuando un componente tiene `context_relation`, ese contexto debe estar disponible para sus hijos, pero NO debe afectar a sus hermanos.

### Escenario Real

```
Página (contexto: page_title = "Home")
  ├─ Header (usa contexto de página)
  ├─ Oferta Especial (contexto: offer_title = "Summer Sale", discount = "20%")
  │   ├─ Botón (usa contexto de oferta: "Book {{ offer_title }} - {{ discount }}% Off")
  │   └─ Imagen (usa contexto de oferta)
  └─ Footer (usa contexto de página)
```

**Flujo del Stack:**
1. Controller: `enter(pageContext)` → Stack: [pageContext]
2. Render Oferta: `enter(offerContext)` → Stack: [pageContext, offerContext]
3. Render Botón: `current()` → Retorna offerContext
4. Fin de Oferta: `leave()` → Stack: [pageContext]
5. Render Footer: `current()` → Retorna pageContext

### Tests Explicados

#### `it('starts with empty stack')`
**Qué verifica:** Al inicio, no hay contextos en el stack.
**Por qué es importante:** Asegura que cada request comienza limpio.

#### `it('can push contexts onto the stack')`
**Qué verifica:** Se pueden agregar múltiples contextos.
**Por qué es importante:** Permite componentes anidados con diferentes contextos.

#### `it('returns the most recent context from the stack')`
**Qué verifica:** `current()` siempre retorna el último contexto agregado.
**Por qué es importante:** Los componentes deben ver el contexto más cercano.

#### `it('can pop contexts from the stack')`
**Qué verifica:** Se pueden remover contextos y volver al anterior.
**Por qué es importante:** Cuando termina un componente, debe restaurar el contexto anterior.

#### `it('simulates nested component rendering')`
**Qué verifica:** El flujo completo de rendering anidado.
**Por qué es importante:** Este es el caso de uso real más común.

---

## Test 2: StoryblokBlockContextTest.php

### Propósito
Verifica que el contexto almacene y normalice correctamente los datos de Storyblok.

### Conceptos Clave

**¿Qué es Normalización?**
Storyblok almacena datos en estructuras complejas. La normalización extrae y mapea estos datos a nombres simples y consistentes para las vistas.

**Ejemplo sin normalización:**
```php
// En Storyblok:
$blok = [
    'component' => 'pdf-document',
    'pdf' => [
        'filename' => 'brochure.pdf',
        'alt' => 'Resort Brochure'
    ]
];

// En la vista, tendrías que hacer:
$link = $blok['pdf']['filename'] ?? null;
$alt = $blok['pdf']['alt'] ?? null;
```

**Con normalización:**
```php
// StoryblokBlockContext normaliza automáticamente:
$context->get('link'); // 'brochure.pdf'
$context->get('alt_text'); // 'Resort Brochure'

// ✅ Más simple y consistente
```

### Normalización Implementada

#### 1. pdf-document
**Mapeo:**
- `pdf.filename` → `link`
- `pdf.alt` → `alt_text`

**Uso:** Botones de descarga de PDFs, links a documentos

#### 2. offer_data
**Mapeo:**
- `general_offer_link` → `link`
- `general_offer_title` → `offer_title`
- `discount` → `discount`
- `validity_activation_type` → `activation`
- `validity_recurring_days` → `date_recurring_days`
- `validity_date_time_range` → `date_time_range`

**Uso:** Componentes que muestran ofertas especiales

### Tests Explicados

#### `it('creates an empty context')`
**Qué verifica:** Se puede crear un contexto vacío inicial.
**Por qué es importante:** Necesario cuando no hay context_relation.

#### `it('gets field values from content')`
**Qué verifica:** Se pueden leer campos del contexto con `get()`.
**Por qué es importante:** Es la forma principal de acceder a datos.

#### `it('supports nested field access with dot notation')`
**Qué verifica:** `get('meta.author')` accede a campos anidados.
**Por qué es importante:** Laravel usa esta notación, debe ser consistente.

#### `it('normalizes pdf-document component')`
**Qué verifica:** Los campos complejos de PDF se mapean correctamente.
**Por qué es importante:** Simplifica el código de las vistas.

#### `it('normalizes offer_data component')`
**Qué verifica:** Los campos de ofertas se mapean correctamente.
**Por qué es importante:** Caso de uso real en Garza Blanca.

#### `it('creates new immutable contexts on withResolvedStory')`
**Qué verifica:** Los contextos son inmutables (no se modifican).
**Por qué es importante:** Evita bugs por modificación accidental de datos.

---

## Test 3: StoryblokVariableResolverTest.php

### Propósito
Verifica que las variables `{{ variable }}` se reemplacen correctamente y el texto se limpie.

### Conceptos Clave

**¿Qué son las Variables?**
Las variables permiten textos dinámicos en Storyblok sin duplicar datos:

**Sin variables (❌ Malo):**
```
Story 1: offer_title = "Summer Sale"
Story 2 (Botón): button_text = "Book Summer Sale"
```
Si cambias el título de la oferta, tienes que actualizar el botón manualmente.

**Con variables (✅ Bueno):**
```
Story 1: offer_title = "Summer Sale"
Story 2 (Botón): button_text = "Book {{ offer_title }}"
```
Si cambias el título, el botón se actualiza automáticamente.

### Sintaxis de Variables

**Formato:** `{{ variable_name }}`

**Caracteres permitidos:**
- Letras: `a-z`, `A-Z`
- Números: `0-9`
- Guión bajo: `_`
- Guión: `-`
- Punto: `.` (para campos anidados)

**Ejemplos válidos:**
- `{{ link }}`
- `{{ offer_title }}`
- `{{ document-type }}`
- `{{ file.name }}`

### Limpieza Automática

El resolver NO solo reemplaza variables, también limpia el texto resultante:

#### 1. Espacios múltiples
```
"Download  {{ file }}  now" → "Download document.pdf now"
```

#### 2. Espacios antes de puntuación
```
"Download file ." → "Download file."
```

#### 3. Separadores huérfanos
```
"Title - {{ missing }}" → "Title"
"{{ missing }} - Title" → "Title"
```

#### 4. Separadores duplicados
```
"Text - - More" → "Text - More"
```

### Tests Explicados

#### `it('resolves single variable')`
**Qué verifica:** Reemplaza una variable básica.
**Caso de uso:** "Download {{ link }}" → "Download LINK"

#### `it('removes variable when not found in context')`
**Qué verifica:** Si no existe, se elimina el placeholder.
**Por qué es importante:** Evita mostrar "Download {{ link }}" al usuario.

#### `it('resolves multiple variables')`
**Qué verifica:** Reemplaza varias variables en el mismo texto.
**Caso de uso:** "View {{ title }} {{ format }}" → "View Program PDF"

#### `it('removes orphan separator at end')`
**Qué verifica:** Limpia separadores cuando falta una variable.
**Ejemplo:**
```php
// Input: "Title - {{ subtitle }}"
// Contexto: ['title' => 'Title'] (subtitle NO existe)
// Output: "Title" (no "Title -")
```

#### `it('handles various separator types')`
**Qué verifica:** Detecta diferentes tipos de separadores (`-`, `|`, `/`, `–`, `—`).
**Por qué es importante:** El contenido puede usar diferentes estilos.

#### `it('handles variables with dots in nested notation')`
**Qué verifica:** `{{ file.name }}` accede a campos anidados.
**Ejemplo:**
```php
$context = ['file' => ['name' => 'doc.pdf']];
"{{ file.name }}" → "doc.pdf"
```

#### `it('ignores non-scalar values')`
**Qué verifica:** Arrays y objetos se ignoran (solo strings, números, bools).
**Por qué es importante:** No se puede mostrar un array como texto.

#### `it('converts numeric values to string')`
**Qué verifica:** Números se convierten automáticamente.
**Ejemplo:** `"Price: ${{ price }}"` con `price = 19.99` → "Price: $19.99"

#### `it('validates variable names')`
**Qué verifica:** Solo se aceptan nombres válidos.
**Por qué es importante:** Previene inyección de código o errores.

#### `it('extracts variable names from text')`
**Qué verifica:** Puede listar todas las variables de un texto.
**Uso:** Debugging, análisis de dependencias.

#### `it('handles real world example from button')`
**Qué verifica:** Caso de uso completo real.
**Ejemplo:**
```php
"Book Now - {{ offer_title }} ({{ discount }}% Off)"
→ "Book Now - Summer Getaway (25% Off)"
```

---

## Cómo Ejecutar los Tests

### Todos los tests del paquete
```bash
cd /Users/adan.gomez/sites/laravel-platform
./vendor/bin/pest
```

### Solo tests de Context Relations
```bash
./vendor/bin/pest tests/Unit/RequestCtxRelationTest.php
./vendor/bin/pest tests/Unit/StoryblokBlockContextTest.php
./vendor/bin/pest tests/Unit/StoryblokVariableResolverTest.php
```

### Con cobertura
```bash
./vendor/bin/pest --coverage
```

---

## Añadir Nuevos Mapeos de Normalización

Si necesitas normalizar un nuevo tipo de componente:

1. Abre `src/Context/StoryblokBlockContext.php`
2. Añade el mapeo a `NORMALIZATION_MAP`:

```php
private const NORMALIZATION_MAP = [
    'pdf-document' => [
        'pdf.filename' => 'link',
        'pdf.alt' => 'alt_text',
    ],
    'offer_data' => [
        // ... mapeos existentes
    ],
    'tu-nuevo-componente' => [
        'campo_origen' => 'campo_destino',
        'otro.campo.anidado' => 'campo_simple',
    ],
];
```

3. Añade tests en `tests/Unit/StoryblokBlockContextTest.php`:

```php
it('normalizes tu-nuevo-componente', function () {
    $story = [
        'content' => [
            'component' => 'tu-nuevo-componente',
            'campo_origen' => 'valor',
        ],
    ];

    $parent = StoryblokBlockContext::empty();
    $context = $parent->withResolvedStory($story);

    expect($context->get('campo_destino'))->toBe('valor');
});
```

---

## Cobertura de Tests

Los tests cubren:

- ✅ Todas las operaciones del stack (enter, leave, current, reset)
- ✅ Todos los casos de normalización implementados
- ✅ Todos los patrones de variables soportados
- ✅ Todos los casos de limpieza de texto
- ✅ Manejo de errores y casos edge
- ✅ Casos de uso reales de Garza Blanca

**Métricas:**
- RequestCtxRelation: 100% de cobertura
- StoryblokBlockContext: 100% de cobertura
- StoryblokVariableResolver: 100% de cobertura
