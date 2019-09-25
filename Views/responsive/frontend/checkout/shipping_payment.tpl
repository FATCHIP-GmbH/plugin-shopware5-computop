{extends file="parent:frontend/checkout/shipping_payment.tpl"}

{block name='frontend_account_payment_error_messages'}
    <div>
        {if $CTError}
            {include file="frontend/_includes/messages.tpl" content="{$CTError.CTErrorMessage}:{$CTError.CTErrorCode}" type="error" bold=false}
        {/if}
    </div>
    {$smarty.block.parent}
{/block}

{block name="frontend_index_content"}
    {* TODO: only load if any klarna payment method is active*}
    <div id="fatchipCTKlarnaInformation" hidden
         data-payment-type="{$paymentType}"
         data-billing-address--street-address="{$billingAddressStreetAddress}"
         data-billing-address--city="{$billingAddressCity}"
         data-billing-address--given-name="{$billingAddressGivenName}"
         data-billing-address--postal-code="{$billingAddressPostalCode}"
         data-billing-address--family-name="{$billingAddressFamilyName}"
         data-billing-address--email="{$billingAddressEmail}"
         data-purchase-country="{$purchaseCountry}"
         data-purchase-currency="{$purchaseCurrency}"
         data-locale="{$locale}"
         data-billing-address--country="{$billingAddressCountry}"
{*         data-customer--date_of_birth="{$customerDateOfBirth}"*}
{*         data-billing-address--phone="{$billingAddressPhone}"*}
{*         data-billing-address--title="{$billingAddressTitle}"*}
{*         data-billing-address--street-address2="{$billingAddressStreetAddress2}"*}
{*         data-billing-address--region="{$billingAddressRegion}"*}
{*         data-customer--gender="{$customerGender}"*}
    ></div>
    <script>
        window.fatchipCTKlarnaPaymentType = null;

        window.klarnaAsyncCallback = () => {
            console.log('async callback');

            window.Klarna = Klarna;

            if (window.fatchipCTKlarnaPaymentType !== null) {
                // window.fatchipCTLoadKlarna was called, but window.Klarna object did not exist
                // so call window.fatchipCTLoadKlarna again
                console.log('recall fatchipCTFetchAccessToken');
                window.fatchipCTFetchAccessToken(window.fatchipCTKlarnaPaymentType);
            }
        };
    </script>
    <script src="https://x.klarnacdn.net/kp/lib/v1/api.js" async></script>
    <script>
        window.fatchipCTLoadKlarna = (paymentType, accessToken) => {
            console.log('fatchipCTLoadKlarna');

            if (!window.Klarna) {
                window.fatchipCTKlarnaPaymentType = paymentType;

                return;
            }

            window.fatchipCTKlarnaPaymentType = null;

            if (!accessToken || accessToken.length === 0) {
                console.log('no token');
                return;
            }

            console.log('Klarna.Payments.init');

            window.Klarna.Payments.init({
                client_token: accessToken
            });

            const payTypeTranslations = {
                pay_now:
                    'pay_now',
                pay_later:
                    'pay_later',
                slice_it:
                    'pay_over_time'
            };

            window.fatchipCTKlarnaPaymentType = payTypeTranslations[paymentType];

            if (! window.Klarna) return;
            console.log(paymentType);
            Klarna.Payments.load({
                container: '#fatchip-computop-payment-klarna-form-' + paymentType,
                payment_method_category: payTypeTranslations[paymentType]
            }, res => {
                console.debug(res);
            });
        };

        window.fatchipCTFetchAccessToken = paymentType => {
            const data = {
                paymentType: paymentType
            };

            let url = '{url controller="FatchipCTAjax" action="ctGetOrCreateAccessToken" forceSecure}';
            let delimiter = '?';

            for (let propertyKey in data) {
                url += delimiter + propertyKey + '=' + data[propertyKey];
                if (Object.keys(data) > 1) delimiter = '&';
            }

            fetch(url).then(
                response => {
                    return response.json();

                }).then(
                accessToken => {
                    window.fatchipCTLoadKlarna(paymentType, accessToken);
                });
        };
    </script>
    {$smarty.block.parent}
{/block}
