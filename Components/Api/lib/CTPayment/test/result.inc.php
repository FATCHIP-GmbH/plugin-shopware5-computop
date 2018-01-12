<?php
/**
 * This file creates the output for success.php and failure.php
 */

namespace Fatchip\CTPayment\test;

use Fatchip\CTPayment\CTPaymentMethodsIframe\CreditCard;

include('../CTPayment/CTMethodsIframe/CreditCard');


// check, if the data and len has been passed, otherwise exit
if (empty($_GET["Data"]) || empty($_GET["Len"])) {
    echo "</table>No or wrong parameters in the query!</body></html>";
    exit;
}


// obtain input values (method="GET")
$Data = $_GET["Data"];
$Len = $_GET["Len"];


// decrypt the data string
$CTPaymentMethod = new CreditCard(null, null, '', '', '', '', '', '');

$plaintext = $CTPaymentMethod->ctDecrypt($Data, $Len, $CTPaymentMethod->getMAC());


// prepare information string
/*$a = "";
$a = explode('&', $plaintext);
$info = $myPayGate->ctSplit($a, '=');
$Status = $myPayGate->ctSplit($a, '=', 'Status');
*/

// check transmitted decrypted status



// info output
include('html.inc.php');
?>
<!--  button to call notify locally -->
<script>
    function call_notify() {
        var http = new XMLHttpRequest();
        var url = "notify.php";
        var params = "<?php print $_SERVER['QUERY_STRING'] ?>";
        http.open("POST", url, true);
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http.setRequestHeader("Content-length", params.length.toString());
        http.setRequestHeader("Connection", "close");
        http.send(params);
    }
</script>
<button onClick='call_notify();'>Call notify (locally)</button>

<!-- Close up html -->
</section></body></html>
