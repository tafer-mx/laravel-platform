# Correcciones diferidas

## Formato heredado fuera del resolver de contexto

- **Estado:** Pendiente.
- **Contexto:** Detectado al validar el cambio que resuelve sub-resorts desde la URL.
- **App o paquete:** `laravel-platform`.
- **Comando:** `./vendor/bin/pint --test`.
- **Paso:** Validación global de estilo con Laravel Pint.
- **Resultado esperado:** Todos los archivos del paquete cumplen el formato configurado.
- **Resultado actual:** Pint solicita cambios en 13 archivos no modificados por este fix, principalmente contextos Storyblok, servicios compartidos y sus pruebas.
- **Impacto conocido:** La suite funcional pasa, pero el chequeo global de estilo continúa fallando por formato heredado.
- **Decisión:** No mezclar el reformateo global con este cambio. Corregir esos 13 archivos en una tarea dedicada; los archivos del resolver de sub-resorts sí deben pasar Pint de forma aislada.
