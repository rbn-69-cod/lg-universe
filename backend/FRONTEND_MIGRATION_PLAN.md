# Migracion progresiva Blade/Livewire a Angular

Fecha: 2026-08-13

## Estructura actual limpia

- Backend Laravel: `E:\mipaghe\backend`
- Frontend Angular: `E:\mipaghe\frontend`
- Public/document root: `E:\mipaghe\backend\public`
- No debe existir otro Angular dentro de `E:\mipaghe\backend`.

## Regla de trabajo

La migracion cambia solo la capa frontend. Laravel sigue como backend y MySQL como base de datos. Blade, Livewire y Fortify permanecen activos mientras cada pantalla se reemplaza de forma controlada.

No se agregan funcionalidades nuevas ni se cambian reglas de negocio en esta etapa.

## Mapa de pantallas actuales

| Pantalla actual | Ruta actual | Implementacion actual | Datos / acciones actuales | Futura pantalla Angular |
| --- | --- | --- | --- | --- |
| Menu publico NetCode | `/` | `netcode-menu.blade.php` | Links a NetCode, acceso 4 digitos, catalogo y login admin | `Home/MenuComponent` |
| NetCode hogar / temporal | `/netcode/codigos` | `netcode.blade.php` + JS inline | Tutoriales publicos, POST `/api/buscar-email`, polling, copia codigo/link | `NetcodeComponent` |
| NetCode acceso 4 digitos | `/netcode/inicio-sesion` | `netcode.blade.php` + JS inline | POST `/api/netflix-validar`, POST `/api/buscar-email`, validaciones por pasos | `NetcodeAccessComponent` |
| Catalogo publico | `/plataformas` | `plataformas.blade.php` | Lee `Plataforma`, busqueda cliente, carrito `localStorage`, checkout `sessionStorage` | `CatalogPage` |
| Pago | `/pago` | `pago.blade.php` + JS inline | Lee `ig_cart_pro` y `checkout_payload`, calcula total, abre WhatsApp | `PaymentPage` |
| Login | `/login` | Fortify/Livewire | Login, sesion, 2FA segun Fortify | `Auth/LoginComponent` solo cuando Sanctum/API este listo |
| Recuperar password | `/forgot-password`, `/reset-password` | Fortify/Livewire | Solicitud y reset de password | `Auth/PasswordResetComponent` |
| Dashboard | `/dashboard` | `DashboardController` + `dashboard.blade.php` | Stats, cuentas, perfiles, rangos, sync Excel, links admin, logout | `AdminDashboardComponent` |
| Configuracion perfil | `/settings/profile` | Livewire `Settings/Profile` | Actualizar nombre/email | `SettingsProfileComponent` |
| Cambiar password | `/settings/password` | Livewire `Settings/Password` | Actualizar password | `SettingsPasswordComponent` |
| Apariencia | `/settings/appearance` | Livewire `Settings/Appearance` | Preferencia visual | `SettingsAppearanceComponent` |
| 2FA | `/settings/two-factor` | Livewire `Settings/TwoFactor` | Activar/desactivar 2FA, recovery codes | Mantener Livewire hasta definir auth API |
| Admin plataformas | `/admin/plataformas` | `Admin\PlataformaController` + Blade | Listar, crear, editar, eliminar, subir/bajar orden | `AdminPlatformsComponent` |
| Admin tutoriales | `/admin/tutoriales` | `Admin\TutorialController` + Blade | Editar tutoriales, subir/eliminar media | `AdminTutorialsComponent` |
| Admin Excel ranges | `/admin/excel-import-ranges` | `Admin\ExcelImportRangeController` + Blade | CRUD rangos, toggle, sync Excel | `AdminExcelRangesComponent` |

## Orden recomendado

1. Catalogo publico `/plataformas`.
   - Bajo riesgo.
   - Ya usa comportamiento cliente con `localStorage`.
   - Permite validar Angular + API sin tocar autenticacion.
2. Pago `/pago`. Migrado a Angular.
   - Mantiene el mismo contrato de carrito: `ig_cart_pro` y `checkout_payload`.
   - Sigue sin crear pedidos nuevos ni cambiar reglas de pago.
3. Menu publico `/`. Migrado a Angular.
   - Solo navegacion, bajo riesgo.
4. NetCode hogar/temporal y acceso 4 digitos. Migrado a Angular.
   - Riesgo medio: polling, validaciones y endpoints existentes.
5. Dashboard. Migrado a Angular.
   - Riesgo medio/alto: datos sensibles de cuentas/perfiles.
6. Admin plataformas.
   - CRUD simple autenticado.
7. Admin tutoriales.
   - Requiere uploads y manejo de media.
8. Admin Excel ranges.
   - Requiere validaciones complejas y sincronizacion Excel.
9. Settings/Auth.
   - Mantener Fortify/Livewire hasta que Sanctum/API este consolidado.

## Primera migracion ejecutada

Pantalla migrada: catalogo publico.

Estado actual:

- Blade original `/plataformas` sigue existiendo.
- Angular implementa `CatalogPage` en `E:\mipaghe\frontend\src\app\features\catalog`.
- Laravel expone `GET /api/v1/plataformas`.
- La pantalla Angular mantiene:
  - busqueda por nombre
  - tarjetas de plataformas
  - features existentes
  - precio mensual
  - agregar al carrito
  - pagar ahora
  - modal/carrito
  - cantidades
  - eliminar
  - subtotal y total
  - `localStorage` con clave `ig_cart_pro`
  - `sessionStorage` con clave `checkout_payload`
  - redireccion a `/pago`

## Notas tecnicas

- Se uso Angular 21 porque Angular 22 requiere Node `24.15.0` minimo y esta maquina tiene Node `24.13.0`.
- La app Angular esta en `E:\mipaghe\frontend`.
- El dev server Angular usa `proxy.conf.json` para consumir Laravel Docker en `http://localhost:8081`.
- La API versionada comienza en `routes/api.php` bajo `/api/v1`.

## Segunda migracion ejecutada

Pantalla migrada: pago.

Estado actual:

- Blade original `/pago` sigue existiendo.
- Angular implementa `PaymentPage` en `E:\mipaghe\frontend\src\app\features\payment`.
- No se agregaron endpoints API porque la pantalla Blade actual no recibe datos dinamicos de Laravel.
- La pantalla Angular mantiene:
  - lectura principal desde `sessionStorage.checkout_payload`
  - fallback a `localStorage.ig_cart_pro`
  - resumen de productos seleccionados
  - cantidades
  - subtotal
  - total
  - estado pendiente
  - metodos actuales Yape / Plin opcion 1 y opcion 2
  - titulares, numeros y WhatsApp destino actuales
  - copia de numero
  - envio de comprobante por WhatsApp
  - estado vacio con retorno al catalogo

Validacion de flujo:

- Se abrio Angular `/plataformas`.
- Se cargo un payload compatible con el que genera el catalogo.
- Se navego a Angular `/pago`.
- Se verifico que `/pago` muestre producto, cantidad, total y ambos metodos de pago.

## Tercera migracion ejecutada

Pantalla migrada: menu publico NetCode.

Estado actual:

- Blade original `/` sigue existiendo como `netcode-menu.blade.php`.
- Angular implementa `HomeMenuPage` en `E:\mipaghe\frontend\src\app\features\home`.
- No se agregaron endpoints API porque la pantalla actual no consume datos dinamicos.
- La pantalla Angular mantiene:
  - loader inicial
  - marca `LG UNIVERSE`
  - acceso a login administrador
  - opcion `Actualizar Hogar - Code Temporal`
  - opcion `Inicio sesion 4 digitos`
  - opcion `Mi Catalogo`
  - aviso informativo
  - footer `Calidad Garantizada - Soporte VIP`
  - boton flotante de ayuda hacia NetCode

Compatibilidad:

- `/plataformas` y `/pago` siguen como rutas Angular migradas.
- `/netcode/codigos`, `/netcode/inicio-sesion` y `/login` siguen atendidos por Laravel/Blade/Fortify durante la migracion.
- El proxy de desarrollo Angular envia esas rutas pendientes al Laravel Docker local.

Validacion:

- Se abrio Angular `/`.
- Se verifico marca, opciones principales y enlaces:
  - `/login`
  - `/netcode/codigos`
  - `/netcode/inicio-sesion`
  - `/plataformas`

## Cuarta migracion ejecutada

Pantallas migradas: NetCode hogar / temporal y acceso 4 digitos.

Analisis NetCode actual:

- `/netcode` redirige a `/`.
- `/netcode/codigos` usa `netcode.blade.php` con `netcodePage = codigos`.
- `/netcode/inicio-sesion` usa la misma vista con `netcodePage = acceso4`.
- `/api/buscar-email` busca correos procesados en `emails_pedidos`.
- `/api/netflix-validar` valida WhatsApp, perfil y PIN contra `perfiles` y `cuentas`.
- Los tutoriales publicos salen de `TutorialContent::public()`.
- No hay componentes Livewire especificos de NetCode.
- La administracion relacionada es `admin/tutoriales` y `admin/excel-import-ranges`, todavia no migrada.

Pantallas NetCode detectadas:

1. Hogar / temporal: email libre, Code hogar, Code temporal, tutoriales, busqueda con polling, resultado codigo/link.
2. Inicio sesion 4 digitos: validacion WhatsApp -> nombre -> PIN, muestra datos de cuenta/perfil, luego busca codigo `acceso4`.

Orden de migracion NetCode:

1. `/netcode/codigos`: migrado a Angular.
2. `/netcode/inicio-sesion`: migrado a Angular.

Estado actual de `/netcode/codigos`:

- Blade original `/netcode/codigos` sigue existiendo.
- Angular implementa `NetcodeCodesPage` en `E:\mipaghe\frontend\src\app\features\netcode`.
- La pantalla Angular mantiene:
  - campo email Netflix
  - link `No recuerdo mi correo`
  - botones `Code hogar` y `Code temporal`
  - tutorial hogar
  - tutorial temporal
  - espacio informativo de tutorial
  - volver al menu
  - confirmacion antes de buscar
  - temporizador de 60 segundos
  - polling cada 4 segundos
  - cambiar correo/cuenta
  - cancelar busqueda
  - resultado codigo/link
  - copiar codigo o abrir enlace
  - mensajes existentes principales

Endpoints API agregados:

- `GET /api/v1/netcode/tutorials`
- `POST /api/v1/netcode/buscar-email`

Los endpoints reutilizan:

- `TutorialContent::public()`
- `ApiBuscarEmailController::buscar()`

Compatibilidad:

- `/netcode/codigos` y `/netcode/inicio-sesion` originales siguen disponibles en Laravel.
- Angular sirve `/netcode/codigos` y `/netcode/inicio-sesion` en desarrollo.
- Laravel conserva las rutas Blade originales para validacion durante la migracion.

Estado actual de `/netcode/inicio-sesion`:

- Blade original `/netcode/inicio-sesion` sigue existiendo.
- Angular implementa `NetcodeAccessPage` en `E:\mipaghe\frontend\src\app\features\netcode`.
- La pantalla Angular mantiene:
  - ingreso de WhatsApp
  - validacion por pasos WhatsApp -> nombre -> PIN
  - limite de 3 intentos por paso
  - tutoriales por paso
  - visualizacion de datos de cuenta/perfil al validar correctamente
  - confirmacion antes de buscar codigo de 4 digitos
  - temporizador de 60 segundos
  - polling cada 4 segundos
  - cancelar busqueda
  - resultado codigo/link
  - copiar codigo o abrir enlace
  - mensajes existentes principales

Endpoint API agregado:

- `POST /api/v1/netcode/netflix-validar`

El endpoint reutiliza:

- `ApiNetflixProfileController::validateAccess()`

## Quinta migracion ejecutada

Pantalla migrada: dashboard.

Estado actual:

- Blade original `/dashboard` sigue existiendo con `DashboardController` y `dashboard.blade.php`.
- Angular implementa `DashboardPage` en `E:\mipaghe\frontend\src\app\features\dashboard`.
- Laravel comparte los datos mediante `App\Services\Dashboard\DashboardData`.
- La pantalla Angular mantiene:
  - navegacion por secciones: Resumen, Tablas Excel, Cuentas, Perfiles y Rangos
  - metricas de cuentas, perfiles, ocupados, disponibles y ultima lectura
  - boton `Leer Excel ahora`
  - creacion/edicion/eliminacion de tablas Excel por plataforma
  - sincronizacion de una tabla especifica sin leer las demas
  - campos independientes para plataforma, nombre de tabla, URL Excel, hoja, filas y URLs de bot
  - accesos a NetCode hogar/temporal, acceso 4 digitos, tutoriales, plataformas e inicio publico
  - cierre de sesion
  - vista agrupada de cuentas y perfiles
  - tabla de detalle de perfiles
  - tabla de rangos activos
  - proteccion por sesion autenticada

Endpoints API agregados:

- `GET /api/v1/dashboard`
- `POST /api/v1/dashboard/excel-sync`
- `POST /api/v1/dashboard/excel-ranges`
- `PUT /api/v1/dashboard/excel-ranges/{excelImportRange}`
- `DELETE /api/v1/dashboard/excel-ranges/{excelImportRange}`

Los endpoints reutilizan:

- `DashboardData`
- `NetflixPremiumExcelImporter`
- autenticacion web/Fortify existente
- proteccion CSRF por cookie/header `X-XSRF-TOKEN`

Correccion Excel:

- La lectura fallaba en Docker con `memory_limit=128M` y salida `255`.
- Se agrego `docker/backend/php.ini` con `memory_limit=512M`.
- La imagen `backend` y `worker` copian ese archivo en `/usr/local/etc/php/conf.d/app.ini`.
- La sincronizacion ya corre sin flags manuales.
- Se agregaron campos `nombre_tabla`, `producto_slug`, `bot_codigo_url` y `bot_soporte_url` a `excel_import_ranges`.
- El importador permite leer todos los rangos, solo una plataforma o solo un rango por `range_id`.
- `/dashboard` autenticado redirige a Angular. La vista Blade queda disponible temporalmente en `/legacy/dashboard`.
