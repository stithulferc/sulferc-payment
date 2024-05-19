jQuery('.cpgw_modal-toggle').on('click', function (e) {
    e.preventDefault();
    const $=jQuery;
    $('.cpgw_modal').toggleClass('is-visible');
    var iBody = $("#cpgw_custom_cpgw_modal").contents().find("body");
    //  var updt_trgt = $(iBody).find('#plugin-information-footer a').attr('target', '_blank');
    var trget_link = $(iBody).find('#plugin-information-footer a').attr('href');
    // var chklink = (trget_link == "undefined") ?"Latest Version Installed":"";
    var adddat = $(iBody).find('#plugin-information-footer').html("<a data-slug='woocommerce' id='plugin_install_from_iframe' class='button button-primary right' href=" + trget_link + " target='_blank'>Install Now</a>")
    // console.log(trget_link)


});