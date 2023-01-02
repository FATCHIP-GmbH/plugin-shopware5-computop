<!DOCTYPE html>
<html>
<body>
{if $fatchipCTURL}
    <div id="fatchipCTCreditCardIFrame" hidden
         data-fatchipCTUniqueID='{$fatchipCTUniqueID}'
         data-fatchipCTCreditcardIFrameUrl='{$fatchipCTURL}'
         data-fatchipCTErrorMessage='{$fatchipCTErrorMessage}'
         data-fatchipCTErrorCode='{$fatchipCTErrorCode}'
    >
    </div>
{else}
    <iframe src="{$fatchipCTIframeURL}" style="width:100%; height:750px;" frameBorder="0" allow="payment"></iframe>
{/if}
</body>
</html>
