jQuery(function ($) {
    
    $('.cmpfile').click(function (event) {

        event.preventDefault();
        var getDataUrl = $(this).attr('data-url');
        var getPackageUrl = $(this).attr('package-url');
        var loaderimg = $(this).next( ".loaderimg" );
        
        $.ajax({
            type: "post",
            url: ajax_object.ajax_url,
            data: {
                action: 'pfcv_extract_plugin_package_ajax_action',
                security: ajax_object.ajax_nonce,
                redirecturl: getDataUrl,
                packageurl: getPackageUrl
            },
            beforeSend: function () {

                loaderimg.show();
                $(".cmpfile").hide();
            },
            success: function (response) {

                $(".loaderimg").hide();
                $(".cmpfile").show();

                var getObj = jQuery.parseJSON(response);
                
                if( getObj.sucs != 'false' ){
                    
                    window.open(getDataUrl,'_blank');
                    
                }else{
                    
                    alert(getObj.error);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {

                $(".loaderimg").hide();
                $(".cmpfile").show();
                console.log(textStatus, errorThrown);
            }
        });

        return false;
    });

});