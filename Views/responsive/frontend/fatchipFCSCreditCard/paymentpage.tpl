<!DOCTYPE html>
<html>
<body>
{if $fatchipCTURL}
    <div id="fatchipFCSCreditCardIFrame" hidden
         data-fatchipFCSUniqueID='{$fatchipFCSUniqueID}'
         data-fatchipFCSCreditcardIFrameUrl='{$fatchipFCSURL}'
         data-fatchipFCSErrorMessage='{$fatchipFCSErrorMessage}'
         data-fatchipFCSErrorCode='{$fatchipFCSErrorCode}'
    >
    </div>
{else}
    <iframe src="{$fatchipFCSIframeURL}" style="width:100%; height:750px;" frameBorder="0"></iframe>
{/if}
</body>
</html>