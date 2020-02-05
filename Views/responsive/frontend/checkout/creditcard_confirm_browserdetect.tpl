<!DOCTYPE html>
<html lang="en">
<head>
    <noscript>
        <meta http-equiv="refresh" content="0;URL={$url}?javaScriptEnabled=false">
    </noscript>
    <meta charset="UTF-8"/>
    <title></title>
</head>
<body>
<script>
    var javaScriptEnabled = true;
    var javaEnabled=navigator.javaEnabled();
    var screenHeight = screen.height;
    var screenWidth = screen.width;
    var colorDepth = screen.colorDepth;
    var date = new Date();
    var timeZoneOffset = date.getTimezoneOffset();

    console.log('JavaScriptEnabled:');
    console.log(javaScriptEnabled);
    console.log('JavaEnabled:');
    console.log(javaEnabled);
    console.log('screenHeight:');
    console.log(screenHeight);
    console.log('screenWidth:');
    console.log(screenWidth);
    console.log('ColorDepth:');
    console.log(colorDepth);
    console.log('timeZoneOffset:');
    console.log(timeZoneOffset);

    var myForm = document.createElement('form');
    myForm.setAttribute('action', '{$url}');
    myForm.setAttribute('method', 'post');
    myForm.setAttribute('hidden', 'true');
    var javascriptEnabledInput = document.createElement('input');
    javascriptEnabledInput.setAttribute('type', 'text');
    javascriptEnabledInput.setAttribute('name', 'javaScriptEnabled');
    javascriptEnabledInput.setAttribute('value', javaScriptEnabled);

    var javaEnabledInput = document.createElement('input');
    javaEnabledInput.setAttribute('type', 'text');
    javaEnabledInput.setAttribute('name', 'javaEnabled');
    javaEnabledInput.setAttribute('value', javaEnabled);

    var screenHeightInput = document.createElement('input');
    screenHeightInput.setAttribute('type', 'text');
    screenHeightInput.setAttribute('name', 'screenHeight');
    screenHeightInput.setAttribute('value', screenHeight);

    var screenWidthInput = document.createElement('input');
    screenWidthInput.setAttribute('type', 'text');
    screenWidthInput.setAttribute('name', 'screenWidth');
    screenWidthInput.setAttribute('value', screenWidth);

    var colorDepthInput = document.createElement('input');
    colorDepthInput.setAttribute('type', 'text');
    colorDepthInput.setAttribute('name', 'colorDepth');
    colorDepthInput.setAttribute('value', colorDepth);

    var timeZoneOffsetInput = document.createElement('input');
    timeZoneOffsetInput.setAttribute('type', 'text');
    timeZoneOffsetInput.setAttribute('name', 'timeZoneOffset');
    timeZoneOffsetInput.setAttribute('value', timeZoneOffset);

    myForm.appendChild(javascriptEnabledInput);
    myForm.appendChild(javaEnabledInput);
    myForm.appendChild(screenHeightInput);
    myForm.appendChild(screenWidthInput);
    myForm.appendChild(colorDepthInput);
    myForm.appendChild(timeZoneOffsetInput);

    document.body.appendChild(myForm);
    myForm.submit();
</script>
</body>
</html>