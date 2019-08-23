(function ($) {
    'use strict';
    var loader = '<img src="img/loading.gif"/> ';
    $(function () {        
        $('.fy-invoice-button').click(function (e) {
            $(this).css('pointer-events', 'none').css('cursor', 'default');
            e.preventDefault();
            if (jQuery("#post_ID").length > 0) {
                var order_id = jQuery("#post_ID").val();
            } else {
                var order_aux = jQuery(this).closest('tr').attr('id').split('-');
                var order_id = order_aux[1];
            }
            var td = $(this).closest('p');
            var esto = $(this);
            var data = {
                action: 'woo_ifactura_do_ajax_request',
                order: order_id
            }
            var awaitingButton = '<a class="button tips fy-awaiting-button" data-invoice="0" href="#">Esperando generación</a>';
            td.html(awaitingButton);
            jQuery.post(ajaxurl, data, function (response) {
                var obj = JSON.parse(response);
                if ($("#wf-tipo-comprobante-metabox").length > 0) {
                    if (obj.Exito == true) {
                        var viewButton = '<a class="button tips fy-view-invoice-button" data-invoice="' + obj.IdFactura + '" href="#">Ver Comprobante</a>';
                        td.html("");
                        $.when(td.append(viewButton)).then(function () {
                            $(".fy-view-invoice-button").click(viewInvoice);
                            esto.remove();
                            jQuery('<div class="notice notice-success is-dismissible"><p><strong>' + obj.Mensaje + '</strong></p></div>').insertAfter(jQuery('.wp-header-end'));
                        });
                    } else {
                        jQuery('<div class="notice notice-warning is-dismissible"><p><strong>' + obj.Mensaje + '</strong></p></div>').insertAfter(jQuery('.wp-header-end'));
                        esto.css('pointer-events', 'none').css('cursor', 'default');
                    }
                } else {
                    if (obj.Exito == true) {
                        var viewButton = '<a class="button tips fy-view-invoice-button" data-invoice="' + obj.IdFactura + '" href="#">Ver Comprobante</a>';
                        td.html("");
                        $.when(td.append(viewButton)).then(function () {
                            $(".fy-view-invoice-button").click(viewInvoice);
                            esto.remove();
                            jQuery('<div class="notice notice-success is-dismissible"><p><strong>' + obj.Mensaje + '</strong></p></div>').insertAfter(jQuery('.wp-header-end'));
                        });
                    } else {
                        jQuery('<div class="notice notice-warning is-dismissible"><p><strong>' + obj.Mensaje + '</strong></p></div>').insertAfter(jQuery('.wp-header-end'));
                        esto.css('pointer-events', 'none').css('cursor', 'default');
                    }
                }
            });
        });
        jQuery(".fy-view-invoice-button").click(viewInvoice);
    });
})(jQuery);
function viewInvoice() {
    if (jQuery("#post_ID").length > 0) {
        var order_id = jQuery("#post_ID").val();
    } else {
        var order_aux = jQuery(this).closest('tr').attr('id').split('-');
        var order_id = order_aux[1];
    }
    var data = {
        action: 'woo_ifactura_view_ajax_request',
        order: order_id
    }
    //console.log(data);
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        async: false,
        data: data,
    }).then(
        function (data) {
            var obj = JSON.parse(data);
            if (obj.Exito == true) {
                window.open(obj.URLPDF);
                return true;
            } else {
                jQuery('<div class="notice notice-warning is-dismissible"><p><strong>' + obj.Mensaje + '</strong></p></div>').insertAfter(jQuery('.wp-header-end'));
            }
        }
    );
}
function isNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : event.keyCode
    if (charCode > 31 && (charCode < 48 || charCode > 57))
        return false;
    return true;
}