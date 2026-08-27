=== Woo iFactura ===
Contributors: fedealvz
Tags: woocommerce, factura electronica, afip, arca, argentina
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 7.3.0
WC tested up to: 11.0.1
Stable tag: 2.0.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Integra WooCommerce con el servicio de factura electrónica de iFactura.com.ar (ARCA/AFIP) para tiendas argentinas.

== Description ==

Woo iFactura es un plugin para WooCommerce que permite emitir factura electrónica de ARCA (AFIP) mediante el servicio de [iFactura](https://www.ifactura.com.ar/).

= Capacidades y funcionalidades =

* Generación de factura electrónica con un solo clic
* Impresión o descarga de la factura electrónica desde el listado de pedidos
* Impresión o descarga de la factura electrónica desde el pedido
* Envío automático de la factura electrónica al cliente

= Requisitos =

* WordPress
* WooCommerce
* Tener una cuenta activada en [iFactura](https://www.ifactura.com.ar/)

= Consideraciones importantes =

* Funciona únicamente con moneda configurada en pesos argentinos.
* Agrega un campo "DNI" en el proceso de checkout, por lo que si ya habías hecho ajustes para obtener el documento del cliente, deberás quitarlo para evitar redundancias.
* El campo "DNI" se usa en referencia a cualquier documento (DNI, CUIT o CUIL) y se mantiene el mismo nombre por cuestiones de retrocompatibilidad del plugin.

= Consideraciones de IVA =

En caso de facturar productos con IVA (21% y otros) es necesario tener configurado correctamente el apartado de impuestos en WooCommerce:

* Configurar WooCommerce para que procese los valores de los productos como si no tuvieran los impuestos cargados.
* En la sección "Tarifas Estándar" hay que configurar el impuesto IVA al porcentaje adecuado para los productos.
* En caso de querer facturar elementos cuyo IVA sea de tipo "Exento" o "No gravado" existe una configuración en el módulo para interpretar el IVA 0% como esa condición.

== Installation ==

1. Subí la carpeta "woo-ifactura" al directorio de plugins de WordPress `/wp-content/plugins/`.
2. Activá el plugin en la sección "Plugins" de WordPress.
3. Ingresá a WooCommerce -> Ajustes -> Configuración de iFactura y completá los siguientes datos:
    * Usuario y contraseña: tu login de iFactura (email).
    * Punto de Venta: número de punto de venta de iFactura que vas a usar para operar.
    * Condición Impositiva: tu condición impositiva para poder facturar.
    * Activar "auto-envío": activalo para enviar automáticamente los comprobantes emitidos (opcional).
    * Facturar automáticamente: permite que cuando la orden cambia su estado se genere la factura directamente sin tener que realizar ninguna acción extra (desactivado por defecto).
    * Ignorar envíos: permite que los elementos de una orden relacionados al envío de la misma sean ignorados al momento de generar una factura (desactivado por defecto).
    * Agrupar elementos de la venta: permite facturar directamente el total de la orden en un solo ítem de la factura. Toma automáticamente el valor de IVA de todos los productos. Esta funcionalidad no es compatible con órdenes que posean más de un IVA diferente (desactivado por defecto).
    * Productos con IVA 0% o sin IVA: parámetro exclusivo para los Responsables Inscriptos que permite procesar los elementos que no contengan IVA a un tipo especial de IVA que puede ser "Exento" o "No gravado".

= Ventas previas a la instalación del plugin =

En caso de querer facturar ventas previas a la instalación de este plugin, deberás especificar los datos faltantes de facturación del cliente.

Para ello, en la vista de la orden, presioná el lápiz que está al lado de la sección "Facturación" donde figuran los datos del cliente. Además, deberás completar los 2 campos personalizados que se encuentran debajo de la sección de artículos de la orden: "DNI" y "condicionimpositiva".

== Usage ==

En el listado de pedidos de WooCommerce vas a ver un nuevo botón de Factura en las órdenes que se encuentren en estado `Procesando`. Al presionar este botón, se enviarán los datos necesarios a iFactura para generar el comprobante correspondiente.

Cada vez que ingreses al listado de pedidos, se actualizará el estado de las facturas. Si un pedido ya fue facturado, aparecerá un ícono de una factura con una flecha verde indicando que ya se puede descargar el comprobante.

Si activaste "auto-envío", el envío de la factura al cliente no requiere ninguna acción, ya que iFactura la envía automáticamente al correo electrónico de tu cliente.

== Frequently Asked Questions ==

= ¿Funciona con otras monedas además del peso argentino? =

No, el plugin funciona únicamente con la tienda configurada en pesos argentinos (ARS).

= ¿Dónde reporto un problema o sugerencia? =

En los [Issues de GitHub](https://github.com/fedealvz/Woo-iFactura/issues). El soporte de este plugin es comunitario; el staff de iFactura no brinda soporte técnico sobre el desarrollo o implementación de este plugin.

== Changelog ==

= 2.0.2 =
* Corrección: las comprobaciones `$woocommerce->version >= "3.0"` comparaban strings, lo que las hacía evaluar `false` a partir de WooCommerce 10.0 (ej. `"11.0.1" >= "3.0"` es falso comparado como texto). Esto hacía caer el código en rutas obsoletas: `get_order_id()` accedía a la propiedad `$order->id` en desuso, y `procesarShipping()` leía `$sm['item_meta']['cost'][0]`, un formato que ya no refleja cómo WooCommerce guarda las líneas de envío — afectando el monto de envío facturado en cada comprobante emitido desde que la tienda actualizó a WooCommerce 10+. Se eliminaron las ramas obsoletas (el plugin ya requiere WooCommerce 7.3.0+).
* Corrección: `woo_ifactura_buttons_column()` no inicializaba `$new_columns`, generando un aviso de variable indefinida.
* Seguridad: se agregó verificación de nonce y de capacidad (`manage_woocommerce`) a los 5 endpoints AJAX de facturación/cancelación/borrado, que antes solo requerían estar autenticado como cualquier usuario de WordPress.
* Se actualizó "WC tested up to" a 11.0.1 y se agregó "Tested up to: 7.1" (WordPress), verificado en un entorno de pruebas real con esa combinación de versiones.

= 2.0.1 =
* Corrección: se reemplazó el uso de `get_post_meta()` por la API de pedidos de WooCommerce en `armarCliente()`, ya que `get_post_meta()` falla silenciosamente cuando HPOS (High Performance Order Storage) está activo en WooCommerce 8+, dejando `billing_state` vacío y provocando que la API de iFactura rechace la factura por "El campo Provincia es requerido".

= 2.0 =
* Reescritura del plugin.

== Upgrade Notice ==

= 2.0.2 =
Corrige un bug de comparación de versión que afectaba el monto de envío facturado desde WooCommerce 10+, y agrega verificación de nonce/permisos a los endpoints AJAX. Se recomienda actualizar.
