var ntpNotify = netopiaUIPath_data.ntp_notify;
var ntpShopConfig = "http://localhost/shopConfiguration/";

document.addEventListener('DOMContentLoaded', function() {
    var popupLink = document.getElementById('woocommerce_netopiapayments_wizard_button');
    document.getElementById('woocommerce_netopiapayments_ntp_notify').innerHTML= ntpNotify;
    popupLink.addEventListener('click', function(e) {
        // var netopiaUIPath_dataPluginUrl = netopiaUIPath_data.plugin_url;
        var netopiaUIPath_dataSiteUrl = netopiaUIPath_data.site_url;
        var pluginCallback = netopiaUIPath_dataSiteUrl+'/index.php/wp-json/netopiapayments/v1/updatecredential/';
        

      e.preventDefault();
      // openPopupWindow(netopiaUIPath_dataSiteUrl + netopiaUIPath_dataPluginUrl+'v2/view/login.php', 'Popup Form', 700, 700);
      openPopupWindow(ntpShopConfig+'?callback='+pluginCallback, 'Popup Form', 700, 700);
    });
  });
  
  function openPopupWindow(url, title, width, height) {
    var left = (window.innerWidth - width) / 2;
    var top = (window.innerHeight - height) / 2;
    var options = 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + width + ', height=' + height + ', top=' + top + ', left=' + left;
    window.open(url, title, options);
  }

  // Function to handle the popup window close event
  function handlePopupWindowClose() {    
    // Reload the parent window (NETOPIA Payments admin page)
    location.reload();
  }