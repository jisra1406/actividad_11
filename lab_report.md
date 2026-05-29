Informe de Laboratorio DevOps - Automatización CI/CD y Estrategia de Ramas

Curso: IF7100 - Ingeniería del Software I
Proyecto: BovWeight CR (API)

1. Introducción

En este laboratorio se configuró un pipeline de Integración Continua (CI) usando GitHub Actions para automatizar pruebas del proyecto BovWeight CR. Además, se aplicaron reglas de protección de ramas para asegurar que solo código validado pueda llegar a producción.

2. Pipeline de CI para Laravel

El workflow se configuró en .github/workflows/ci.yml.
Cada vez que se hace un push o un pull request, GitHub Actions:

Descarga el proyecto.
Levanta una base de datos MySQL.
Ejecuta migraciones.
Corre las pruebas automáticas con PHPUnit.
2.1 Análisis
¿Cuánto tardó el pipeline?

El pipeline tardó aproximadamente 53 segundos.
El paso más lento fue iniciar el contenedor de MySQL, porque GitHub debe descargar y preparar la imagen antes de ejecutar las pruebas.

¿Qué pasa si una prueba falla?

Si alguna prueba falla:

GitHub Actions marca el pipeline en rojo.
El Pull Request muestra un error.
No se puede hacer merge mientras las pruebas sigan fallando.




¿Por qué usar MySQL y no SQLite?

Se utilizó MySQL porque es el mismo motor que se usaría en producción. Esto permite probar el sistema en condiciones más reales y evitar errores que podrían aparecer solo en producción.

Ventaja de actions/checkout@v4

Esta acción facilita descargar el repositorio automáticamente y de forma segura, sin tener que configurar comandos manuales de Git.

3. Estrategia de Ramas

Se utilizó una estrategia sencilla basada en Git Flow:

Rama	Uso
main	Código estable y listo para producción
develop	Integración de nuevas funciones
feature/*	Desarrollo de funcionalidades
hotfix/*	Correcciones rápidas de errores
Protección de ramas

La rama main fue protegida para:

Obligar Pull Requests.
Exigir que las pruebas pasen correctamente.
Requerir al menos una aprobación antes del merge.
4. Simulación de Falla

Se creó una rama de prueba con un error intencional.
Al abrir el Pull Request:

GitHub Actions ejecutó las pruebas.
Las pruebas fallaron.
El pipeline quedó en rojo.
GitHub bloqueó el botón de merge automáticamente.

Esto demuestra cómo CI ayuda a mantener la calidad del proyecto evitando que código con errores llegue a producción.
