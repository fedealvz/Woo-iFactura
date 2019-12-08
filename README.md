<div align="center"><img alt="Woo-iFactura" src="../assets/Woo-iFactura-small.png?raw=true"></div>

# Woo-iFactura

![WordPress Plugin: Tested WP Version](https://img.shields.io/wordpress/plugin/tested/woocommerce)
![GitHub top language](https://img.shields.io/github/languages/top/fedealvz/Woo-iFactura)
[![GitHub issues](https://img.shields.io/github/issues/fedealvz/Woo-iFactura)](https://github.com/fedealvz/Woo-iFactura/issues)
[![GitHub license](https://img.shields.io/github/license/fedealvz/Woo-iFactura)](https://github.com/fedealvz/Woo-iFactura/blob/master/LICENSE)
![GitHub forks](https://img.shields.io/github/forks/fedealvz/Woo-iFactura?style=social)
![GitHub stars](https://img.shields.io/github/stars/fedealvz/Woo-iFactura?style=social)
![GitHub watchers](https://img.shields.io/github/watchers/fedealvz/Woo-iFactura?style=social)
[![Twitter](https://img.shields.io/twitter/url?style=social&url=https%3A%2F%2Fgithub.com%2Ffedealvz%2FWoo-iFactura)](https://twitter.com/intent/tweet?text=Factura%2BWooCommerce%20❤️%20@fedealvz&url=https%3A%2F%2Fgithub.com%2Ffedealvz%2FWoo-iFactura)

Woo-iFactura es un plugin para WooCommerce que permite emitir factura electrónica de AFIP (Argentina) mediante el servicio de [iFactura](https://www.ifactura.com.ar/).

## Capacidades y Funcionalidades

* Generación de factura electrónica con un solo clic
* Impresión o descarga de la factura electrónica desde el listado de pedidos
* Impresión o descarga de la factura electrónica desde el pedido
* Envío automático de la factura electrónica al cliente

## Instalación

1. Subí la carpeta "woo-ifactura" al directorio de plugins de WordPress `/wp-content/plugins/`
2. Activá el plugin en la sección "Plugins" de WordPress
3. Ingresá a WooCommerce -> Ajustes -> Configuración de iFactura y completá los siguientes datos:
    * Usuario y contraseña: Tu login de iFactura (email)
    * Punto de Venta: Número de punto de venta de iFactura que vas a usar para operar
    * Condición Impositiva: Tu condición impositiva para poder facturar
    * Activar "auto-envío": Activalo para enviar automáticamente los comprobantes emitidos (opcional)

## Uso

En el listado de pedidos de WooCommerce vas a ver un nuevo botón de Factura en las órdenes que se encuentren en estado `Procesando`. Al presionar este botón, se enviarán los datos necesarios a iFactura para generar el comprobante correspondiente.

Cada vez que ingreses al listado de pedidos, se actualizará el estado de las facturas. Si un pedido ya fue facturado, aparecerá un icono de una factura con una flecha verde indicando que ya se puede descargar el comprobante.

Si activaste "auto-envío", el envío de la factura al cliente no requiere ninguna acción ya que iFactura la envía automáticamente al correo electrónico de tu cliente.

## Capturas

<img alt="Emitir factura" title="Emitir factura" src="../assets/wooifactura-emitir.png?raw=true" width="200"> <img alt="Factura emitida" title="Factura emitida" src="../assets/wooifactura-emitido.png?raw=true" width="200"> <img alt="Configuración" title="Configuración" src="../assets/wooifactura-config.png?raw=true" width="200">

## Consideraciones importantes

* Funciona únicamente con moneda configurada en pesos argentinos
* Agrega un campo "DNI" en el proceso de checkout, por lo que si ya habías hecho ajustes para obtener el DNI del cliente, deberás quitarlo para evitar redundancias.

## Ventas previas a la instalación del plugin

En caso de querer facturar ventas previas a la instalación de este plugin, deberás especificar los datos faltantes de facturación del cliente.

Para ello, en la vista de la orden presionás en el lápiz que está al lado de la sección "Facturación" donde figuran los datos del cliente.

Además del paso anterior, deberás los 3 campos personalizados. Esto se encuentra abajo de la sección de artículos de la orden. Los mismos son: DNI, tipopersona y condicionimpositiva.

## Requisitos

- WordPress
- WooCommerce
- Tener una cuenta activada en [iFactura](https://www.ifactura.com.ar/)

## Contribuciones y Soporte

¡Las contribuciones son bienvenidas! Podés crear un nuevo Pull request para enviar tus correcciones y se fusionarán después de la moderación.

El soporte de este plugin es comunitario. En caso de problemas, errores o sugerencias, puedes [crear un nuevo Issue](https://github.com/fedealvz/Woo-iFactura/issues/new) o publicar comentarios en los [Issues activos](https://github.com/fedealvz/Woo-iFactura/issues). El staff de iFactura no brinda soporte técnico sobre el desarrollo o implementación de este plugin.

## Licencia

Woo-iFactura está disponible bajo la licencia [GNU General Public License v3.0](https://github.com/fedealvz/Woo-iFactura/blob/master/LICENSE).