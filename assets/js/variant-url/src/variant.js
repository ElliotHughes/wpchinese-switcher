import URI from 'urijs'

const langArr = [
    'zh-tw',
    'zh-cn',
    'zh-sg',
    'zh-hant',
    'zh-hans',
    'zh-my',
    'zh-mo',
    'zh-hk',
    'zh',
    'zh-reset',
]

export function wpcsRedirectToPage($event) {
    let variantValue = $event.value
    wpcsRedirectToVariant(variantValue)
}

export function wpcsRedirectToVariant(variantValue) {
    let newUrl = window.location.href
    let myUrl = new URI(newUrl)

    const segments = myUrl.segment()
    const firstSegment = segments[0]
    const lastSegment = segments[segments.length - 1]
    if (
        !wpc_switcher_use_permalink_type ||
        wpc_switcher_use_permalink_type == '0'
    ) {
        // 'variant'
        myUrl.removeQuery('variant');
        // add 'variant'
        if (variantValue) myUrl.addQuery('variant', variantValue);
    } else {
        if (wpc_switcher_use_permalink_type == '1') {
            // lastSegment add variantValue
            if (langArr.includes(lastSegment)) {
                // remove last segment
                myUrl.segment(segments.length - 1, '');
            }
            // add segment
            if (variantValue) {
                myUrl.segment(variantValue);
            }
        } else if (wpc_switcher_use_permalink_type == '2') {
            // firstSegment add variantValue
            if (langArr.includes(firstSegment)) {
                // remove first segment
                myUrl.segment(0, '');
            }
            // add segment
            if (variantValue) {
                myUrl.segment(0, variantValue);
            }
        }
    }
    window.location.href = myUrl.toString()
}