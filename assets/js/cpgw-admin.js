
jQuery(document).ready(function ($) {
    var url = window.location.href;
    if (url.includes('page=cpgw-metamask-settings')) {
        $('[href="admin.php?page=cpgw-metamask-settings"]').parent('li').addClass('current');
        const selectElement = document.querySelector('select[name="cpgw_settings[Chain_network]"]');
        const optionToDisableFrom = selectElement.querySelector('option[value="0x61"]');
        let option = optionToDisableFrom.nextElementSibling;

        while (option) {
            option.disabled = true;
            option = option.nextElementSibling;
        }
    }

    var data = $('#adminmenu #toplevel_page_woocommerce ul li a[href="admin.php?page=cpgw-metamask-settings"]');
    data.each(function () {
        if ($(this).is(':empty')) {
            $(this).hide();
        }
    });



});


