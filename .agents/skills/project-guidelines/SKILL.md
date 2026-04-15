---
name: project-guidelines
description: Reglas y guías del proyecto que consolidan las skills instaladas (Vue, Tailwind, TypeScript, Vite, Laravel, PHP). Carga este skill antes de tareas relacionadas para aplicar las reglas del equipo.
license: MIT
metadata:
  author: repositorio/CarritoCompra
  version: "1.0.0"
---

# Project Guidelines

Este skill consolida las reglas y prácticas de las skills instaladas por `autoskills` y ofrece instrucciones rápidas para que los agentes y desarrolladores las apliquen en tareas del proyecto.

## Propósito
- Unificar recomendaciones de arquitectura, estilo y prácticas para tareas de Vue, Tailwind, TypeScript, Vite y Laravel.
- Proveer enlaces y referencias rápidas a los `SKILL.md` instalados.

## Cuándo cargar
- Antes de trabajar en componentes `.vue`, migraciones, controladores o tareas de frontend/back-end que involucren las tecnologías detectadas.

## Reglas principales (resumen)
- **Vue:** usar Vue 3 + Composition API con `<script setup lang="ts">`. Mantener componentes pequeños y separar lógica en composables. Más detalles en: `.agents/skills/vue-best-practices/SKILL.md`.
- **TypeScript:** tipar entradas/props y composables; preferir `computed` para derivadas y `ref`/`reactive` para estado. Ver: `.agents/skills/typescript-advanced-types/SKILL.md`.
- **Tailwind:** usar utilidades responsivas y evitar generación dinámica de clases en tiempo de ejecución. Ver: `.agents/skills/tailwind-css-patterns/SKILL.md`.
- **Vite:** seguir configuración recomendada y usar `import.meta.env` para variables de entorno. Ver: `.agents/skills/vite/SKILL.md`.
- **Laravel/PHP:** seguir convenciones de Eloquent, separar servicios y controladores, usar validaciones y recursos API. Ver: `.agents/skills/laravel-specialist/SKILL.md` y `.agents/skills/php-pro/SKILL.md`.

## Cómo usar este skill
- Los agentes deben leer este archivo primero y luego abrir las referencias listadas para la tecnología específica.
- Para uso manual: ejecutar `npx autoskills -y` (ya se ejecutó en este repositorio).
- Para copia a otra ruta requerida por una herramienta externa, copiar carpetas desde `.agents/skills/`.

## Activación automática (sugerencia)
- Añadir pre-step en pipelines o scripts de desarrollo que copie o liste los `SKILL.md` relevantes y los incluya en el contexto del agente.

Ejemplo de script (PowerShell) para cargar reglas relevantes en una sesión:

```powershell
# Cargar reglas de Vue y Tailwind en contexto de revisión
Get-Content .agents\skills\vue-best-practices\SKILL.md -Raw
Get-Content .agents\skills\tailwind-css-patterns\SKILL.md -Raw
```

## Archivos relacionados
- `.agents/skills/vue-best-practices/SKILL.md`
- `.agents/skills/vue-debug-guides/SKILL.md`
- `.agents/skills/tailwind-css-patterns/SKILL.md`
- `.agents/skills/typescript-advanced-types/SKILL.md`
- `.agents/skills/vite/SKILL.md`
- `.agents/skills/laravel-specialist/SKILL.md`
- `.agents/skills/php-pro/SKILL.md`

## Próximos pasos opcionales
- Extraer reglas concretas y generar un `CODE_STYLE.md` o linters/ESLint/Stylelint configurados para aplicar automáticamente estas reglas.
- Añadir hooks de pre-commit que verifiquen reglas de estilo y buenas prácticas.

---

## Caveman (modo de comunicación comprimida)

- **Propósito:** permitir respuestas ultra-comprimidas y eficientes en tokens para revisiones rápidas, resúmenes y comandos.
- **Skill instalado:** `.agents/skills/caveman/SKILL.md`
- **Comandos / activación:** `/caveman lite|full|ultra|wenyan-lite|wenyan-full|wenyan-ultra`
- **Modo por defecto:** `full` (fragmentos cortos, sin artículos, sin relleno).
- **Desactivar:** escribir `stop caveman` o `normal mode` para volver a respuestas normales.
- **Excepciones automáticas:** el modo caveman se suspende automáticamente para advertencias de seguridad, confirmaciones irreversibles y cuando el usuario pide aclaración detallada.

### Cómo usar

- Agente o desarrollador carga este skill antes de tareas donde quiera respuestas comprimidas.
- Para ejecutar localmente (ejemplo PowerShell):

```powershell
# Modo full
echo "/caveman full"
# Volver a normal
echo "stop caveman"
```

### Nota
- Mantener reglas de caveman en mente al generar commits o PRs: para contenidos formales (commits, código) usar lenguaje normal.


