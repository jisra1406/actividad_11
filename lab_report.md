# Informe de Laboratorio DevOps - Automatización CI/CD y Estrategia de Ramas
**Curso:** IF7100 - Ingeniería del Software I  
**Sede:** Sede de Guanacaste, Recinto de Liberia | I Ciclo 2026  
**Proyecto:** BovWeight CR (API Component)

---

## 1. Introducción y Contexto

Este reporte recopila la implementación de los conceptos de DevOps, Integración Continua (CI) y políticas de protección de ramas para el proyecto **BovWeight CR** utilizando GitHub Actions y las políticas de calidad de GitHub. Se configuró un pipeline que levanta automáticamente un servidor MySQL en un contenedor de servicio, ejecuta migraciones y corre las pruebas automáticas en PHPUnit.

---

## 2. Ejercicio 1: Pipeline de CI para el API Laravel

El flujo de trabajo automatizado se configuró en el archivo `.github/workflows/ci.yml` del repositorio `bovweight-api`. Cada vez que se genera un `push` a las ramas `main` o `develop`, o un `pull_request` a `main`, el pipeline realiza la construcción del entorno y valida el código.

### 2.1 Actividad de Análisis

#### 1. ¿Cuánto tiempo tardó el pipeline en completarse? Identifique el step más lento.
* **Tiempo real de ejecución:** El pipeline tardó **53 segundos** en completarse en la corrida real sobre GitHub Actions.
* **Paso más lento (Cuello de botella):** El paso más lento fue la inicialización del contenedor MySQL (el step **Initialize containers**), con una duración de **28 segundos** (lo que equivale a más del 50% del tiempo total del pipeline). Esto ocurre porque GitHub Actions debe descargar la imagen Docker oficial de MySQL 8.0 y levantar el servicio, esperando a que responda de forma saludable antes de iniciar los tests.

#### 2. ¿Qué ocurre con el Pull Request si alguna prueba falla?
* **Resultado del Flujo:** Si una prueba falla (por ejemplo, PHPUnit reporta un fallo de aserción), el runner de PHPUnit devuelve un código de salida (exit code) `1`. GitHub Actions interpreta cualquier código de salida diferente de `0` como un fallo del paso y detiene la ejecución del Job marcándolo con una cruz roja (`x`).
* **Efecto en el Pull Request:**
  * La sección inferior de la interfaz de la Pull Request muestra un estado de error resaltado en color rojo: **"Some checks were not successful"**.
  * Si las **Branch Protection Rules** (Reglas de protección de ramas) están configuradas para exigir que los checks de estado pasen obligatoriamente (`Require status checks to pass before merging`), **el botón verde de "Merge pull request" se bloquea por completo** (se vuelve de color gris/rojo con un candado) indicando: *"Merge blocked: Required status checks have not succeeded"*.
  * Ningún desarrollador puede fusionar la rama a `main` hasta que el código sea corregido, se haga un push que solucione la falla y el pipeline se ejecute con éxito (marcando un check verde `✔`).

#### 3. ¿Por qué se usa un servicio MySQL en lugar de SQLite para las pruebas? Argumente con base en los requisitos no funcionales de BovWeight CR.
El uso de un servicio MySQL en lugar de SQLite en memoria se fundamenta en principios clave de la ingeniería de software y en los requisitos no funcionales del sistema BovWeight CR:

1. **Paridad de Entornos (Dev/Prod Parity - 12-Factor App):** Probar el código en un motor de base de datos idéntico al de producción (MySQL) evita fallos invisibles. SQLite y MySQL implementan de forma distinta la sintaxis SQL, tipos de datos y restricciones de integridad. Una consulta o migración que funciona en SQLite podría fallar rotundamente en el MySQL de producción.
2. **Requisitos de Datos Específicos de BovWeight CR:**
   * **Tipos de Datos JSON:** BovWeight CR registra estimaciones de peso a partir de imágenes u otros parámetros de sensores. Es común almacenar metadatos o variables específicas de la estimación en columnas tipo `JSON`. SQLite tiene soporte limitado y diferente sintaxis para la manipulación y consultas de JSON en comparación con MySQL.
   * **Datos Geoespaciales (Coordenadas GIS):** Para rastrear la ubicación del ganado o las fincas (Recinto de Liberia, Guanacaste), el sistema requiere registrar coordenadas geográficas utilizando tipos de datos espaciales (`GEOMETRY`, `POINT`, `POLYGON`). MySQL maneja indexación y consultas geoespaciales nativas avanzadas (como `ST_Contains` o `ST_Distance`). SQLite no soporta estas características a menos que se compile con extensiones pesadas de terceros (SpatiaLite), lo cual no es práctico en la nube de producción.
3. **Concurrencia y Bloqueos de Transacciones:** SQLite bloquea toda la base de datos durante las escrituras, mientras que MySQL implementa bloqueos a nivel de fila (`Row-level locking`). Las pruebas de concurrencia y transacciones complejas en BovWeight CR se deben simular sobre el motor que soporta la carga concurrente real de los usuarios en producción.

#### 4. ¿Qué ventaja tiene usar `actions/checkout@v4` frente a clonar manualmente el repositorio?
La utilización de la acción oficial de GitHub `actions/checkout` proporciona múltiples ventajas de rendimiento, seguridad y simplicidad:
* **Gestión Segura de Credenciales:** `actions/checkout@v4` utiliza de forma transparente el token temporal del pipeline (`GITHUB_TOKEN`) generado para ese Job específico. No requiere que el desarrollador configure claves SSH privadas ni tokens de acceso personal (`PAT`) en texto plano en las variables del pipeline.
* **Clonación Superficial (Shallow Clone):** Por defecto, la acción realiza un clonado superficial con `fetch-depth: 1`. Esto significa que solo descarga el estado del último commit de la rama correspondiente, en lugar de descargar todo el historial completo de commits del proyecto. Esto ahorra gigabytes de transferencia de red y reduce el tiempo de arranque del job a milisegundos.
* **Configuración del Entorno de Git:** Configura automáticamente las referencias locales de Git, las rutas y las variables del entorno del sistema de control de versiones en el runner, garantizando compatibilidad con cualquier comando de Git subsecuente y previniendo errores de permisos en entornos Linux.
* **Compatibilidad Multiplataforma:** Es un módulo mantenido directamente por GitHub que está optimizado para funcionar perfectamente en runners basados en Ubuntu (Linux), Windows y macOS, abstrayendo al desarrollador de tener que escribir comandos específicos de clonado por consola según el Runner.

---

## 3. Ejercicio 2: Estrategia de Ramas y Branch Protection

### 3.1 Resumen de la Estrategia de Ramas
Para garantizar el flujo correcto y la calidad del software en **BovWeight CR**, se implementa una versión del modelo **Git Flow** adaptada:

| Rama | Propósito | Reglas de Protección |
| :--- | :--- | :--- |
| `main` | Código en producción estable. | PR obligatorio + CI en verde + 1 revisor mínimo + No Force Push. |
| `develop` | Integración continua de nuevas características. | CI obligatorio + Merge por Squash + 0 revisores requeridos. |
| `feature/*` | Desarrollo de nuevas funcionalidades. | CI en cada Push + Fusión únicamente hacia `develop`. |
| `hotfix/*` | Correcciones críticas inmediatas en producción. | Fusión directa a `main` y `develop` tras aprobación y CI exitoso. |

---

### 3.2 Guía Paso a Paso para Configurar Branch Protection en GitHub

Para aplicar estas directrices en el repositorio en la nube, siga las siguientes instrucciones en la interfaz de GitHub:

1. **Navegar a la configuración:**
   * Ingrese a su repositorio en GitHub (`github.com/usuario/bovweight-api`).
   * Haga clic en la pestaña superior derecha **Settings** (Configuración).
2. **Acceder a la sección de Ramas:**
   * En el menú lateral izquierdo, bajo la sección **Code and automation**, haga clic en **Branches** (Ramas).
3. **Agregar regla para la rama principal:**
   * En el apartado **Branch protection rules**, haga clic en el botón **Add branch ruleset** (o **Add rule**).
   * **Branch pattern:** Escriba `main`.
4. **Habilitar las directrices requeridas por el laboratorio:**
   * Seleccione **Require a pull request before merging** (Requerir un pull request antes de fusionar).
     * Seleccione **Require approvals** y asigne el valor `1` (requiere la aprobación de al menos un revisor).
   * Seleccione **Require status checks to pass before merging** (Requerir que los controles de estado pasen antes de fusionar).
     * En la barra de búsqueda de checks de estado, busque y seleccione el nombre del Job de su flujo de trabajo (en nuestro caso, `laravel-tests`).
   * Seleccione **Require branches to be up to date before merging** (Garantiza que la rama esté actualizada con la rama base antes de hacer merge).
   * Marque la opción **Do not allow bypassing the above settings** (No permitir omitir la configuración anterior, asegurando que incluso los administradores estén obligados a cumplir con el pipeline verde).
5. **Guardar los cambios:**
   * Desplácese hasta el final de la página y haga clic en **Create** (o **Save changes**). GitHub solicitará su contraseña o confirmación de 2FA para validar el cambio.

---

### 3.3 Simulación de Protección con Falla de Pruebas
Si creamos una rama llamada `feature/test-protection`, realizamos un cambio intencionado en el código que rompa las pruebas (por ejemplo, modificando la aserción de `BovineWeightTest` de `450.5` a `999.9` sin guardar ese valor en la base de datos) y abrimos una Pull Request hacia `main`:
1. El trigger de `pull_request` disparará inmediatamente el flujo de trabajo de GitHub Actions.
2. El runner levantará MySQL, ejecutará la migración y correrá PHPUnit.
3. PHPUnit fallará reportando la aserción fallida.
4. El check de estado `laravel-tests` se marcará con una cruz roja (`x`).
5. GitHub detectará que el check obligatorio falló. El botón de **"Merge"** se deshabilitará por completo y mostrará el mensaje en rojo indicando que la rama no cumple las políticas y el merge está bloqueado.

Esto demuestra cómo el pipeline de CI actúa como una **puerta de calidad automática e inquebrantable** para el proyecto BovWeight CR.
