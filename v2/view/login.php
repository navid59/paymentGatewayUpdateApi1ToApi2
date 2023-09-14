<?php 
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);

  /**
   * 
   * IMPORTANT 
   * To be aproved by wordpress team must be use "wp_enqueue_script"
   */

  require_once('../../../../../wp-load.php');

  
  wp_head();
  // echo "AAA : ".get_template_directory_uri() ;
  function wpse90382_popup_function() {
    wp_enqueue_style( 'netopiacredentialcss', plugin_dir_url( __DIR__ ).'css/custom.css?v=1.4');

    wp_enqueue_script( 'netopiacredentialjs', plugin_dir_url( __DIR__ ) . 'js/netopiaCredentials.js',array(),'3.3' ,true);
		wp_localize_script( 'netopiacredentialjs', 'netopiaUIPath_data', array(
			'plugin_url' => getAbsoulutFilePath(),
			'site_url' => get_site_url(),
		) );
  }
  
  wpse90382_popup_function();
  

  ?>
<!-- <link rel="stylesheet" href="<?php echo plugin_dir_url( __DIR__ ).'css/custom.css';?>"> -->
<!-- <script src="<?php echo plugin_dir_url( __DIR__ ).'/js/netopiaCredentials.js?v=4';?>"></script> -->
  <div class="imgcontainer">
    <span onclick="self.close();" class="close" title="Close Modal">&times;</span>
    <img src="../img/NETOPIA_Payments.svg" alt="Avatar" class="avatar" width="250px">
  </div>

  <form id="ntpPlatformLoginForm" class="modal-content animate" method="post">
    <div class="container">
      <label for="username"><b>Username</b></label>
      <input type="text" placeholder="Enter Username" id="ntpUsername" name="username" required>

      <label for="password"><b>Password</b></label>
      <input type="password" placeholder="Enter Password" id="ntpPassword" name="password" required>
        
      <button type="button" class="ntp-login-btn" id="loginToNetopiaPlatform">Login</button>
    </div>

    <div id="ntpPlatformAuthAlarm" class="alert danger" style="display: none;">
      <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
      <span id="ntpPlatformAuthAlarmContent"></span>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="self.close();" class="cancelbtn">Cancel</button>
      <span class="psw"><a href="https://admin.netopia-payments.com/reset-password" target="_blank">Forgot password?</a></span>
      <span id="ntpLoader" style="display: none;"><div class="spin"></div></span>
    </div>
  </form>


  <form id="ntpPlatformCredentialDataForm" class="modal-content animate" method="post" style="display: none; padding:10px;">
  <!-- Signatore Section -->
  <fieldset id="signature">
    <legend><b>Preferred POS signature:</b></legend>
    <div class="ntp-credential-list" id="signatureList">
      <span id="ntpSignatureLoader" style="display: block;"><div class="spin-credential"></div></span>
    </div>
    <div id="ntpSignatoreAlarm" class="alert warning" style="display: none;">
      <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
      <span id="ntpSignatoreAlarmContent"></span>
    </div>
  </fieldset>

  <!-- Apikey Live section -->
  <fieldset id="apiKeyLive">
    <legend><b>API Key for production environment:</b></legend>
    <div class="ntp-credential-list" id="apiKeyLiveList">
      <span class="apiKeyLoader" id="ntpApiKeyLiveLoader" style="display: block;"><div class="spin-credential"></div></span>
    </div>
    <div id="ntpApiKeyLiveAlarm" class="alert warning" style="display: none;">
      <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
      <span id="ntpApiKeyLiveAlarmContent">LIVE</span>
    </div>
  </fieldset>

  <!-- Apikey Sandbox section -->
  <fieldset id="apiKeySandbox">
    <legend><b>API Key for sandbox environment:</b></legend>
    <div class="ntp-credential-list" id="apiKeySandboxList">
      <span class="apiKeyLoader" id="ntpApiKeySandboxLoader" style="display: block;"><div class="spin-credential"></div></span>
    </div>
    <div id="ntpApiKeySandboxAlarm" class="alert warning" style="display: none;">
      <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
      <span id="ntpApiKeySandboxAlarmContent"></span>
    </div>
  </fieldset>
  
  <!-- <label class="container">
    <input type="checkbox" required>
    <span class="checkmark"></span>Agree to use the selected Credentials for configuration
  </label> -->
  <div style="text-align: center;">
    <button type="button" id="ntp-try-btn" class="ntp-try-btn" onClick="window.location.reload();" style="display: none;">try again</button>
    <button type="button" id="ntp-confirm-btn" class="ntp-confirm-btn" onclick="displaySelected();" >Confirm</button>
    <button type="button" class="ntp-close-btn" onclick="self.close();">Close</button>
  </div>
</form>
<hr>
<!-- Separate div to display selected radio box options -->
<div id="selectedOptions" class="alert info" style="display: none;">
  <span style="color: #333;">Selected Signature : </span><span id="selectedSignatureValue"></span><br>
  <span style="color: #333;">Selected Live Api Key : </span><span id="selectedLiveApiKeyValue"></span><br>
  <span style="color: #333;">Selected Sandbox Api Key : </span><span id="selectedSandboxApiKeyValue"></span>
</div>
<br>
<div id="wpRestResponse" class="alert success" style="text-align: center; display: none;"></div>

<?php wp_footer();?>