let wpc_switcher_use_permalink_type
if (typeof wpc_switcher_use_permalink !== 'undefined') {
    wpc_switcher_use_permalink_type = wpc_switcher_use_permalink['type']
}

function updateUrlParameter(uri, key, value) {
    const i = uri.indexOf('#');
    const hash = i === -1 ? '' : uri.substr(i);
    uri = i === -1 ? uri : uri.substr(0, i);
    
    if (value === '') {
      // to handle multiple occurrence of `key`
      const re = new RegExp("([?&]" + key + "=)[^&]*", "g");
      uri = uri.replace(re, '');
      uri = uri.replace(/&&/g, '&');
      uri = uri.replace(/&$/, '');
      uri = uri.replace(/&/, '?');
      if(uri.endsWith('?')) {
          uri = uri.slice(0, -1);
      }
    } else {
      // remove all `key` occurrences 
      const re = new RegExp("([?&]" + key + "=)[^&]*", "g");
      uri = uri.replace(re, '');
      uri = uri.replace(/&&/g, '&');
      uri = uri.replace(/&$/, '');
      uri = uri.replace(/&/, '?');
      // add the `key=value` pair
      const separator = uri.indexOf('?') !== -1 ? "&" : "?";
      uri = uri + separator + key + "=" + value;
    }
    return uri + hash;
}

function wpcsRedirectToPage() {
    var selectEle = document.getElementById('wpcs_translate_type');
    var variantValue = selectEle.value;
    let url = window.location.href;
    url = updateUrlParameter(url, 'variant', variantValue);
    window.location.href = url;
}

function wpcsRedirectToVariant(variantValue) {
    let locationUrl = window.location.href;
    if (!wpc_switcher_use_permalink_type || wpc_switcher_use_permalink_type == '0') {
        locationUrl = updateUrlParameter(locationUrl, 'variant', variantValue);
        window.location.href = locationUrl;
    } else {
        if (wpc_switcher_use_permalink_type == '1') {
            let urlParts = locationUrl.split('?');
            let queryParams = urlParts.length > 1 ? '?' + urlParts[1] : '';
            let regExp = /\/([^\/?]+)$/;
            let match = urlParts[0].match(regExp);
            let languages = ["zh-tw", "zh-cn", "zh-sg", "zh-hant", "zh-hans", "zh-my", "zh-mo", "zh-hk", "zh", "zh-reset"];
            if (match) {
                if (languages.includes(match[1])) {
                    locationUrl = urlParts[0].replace(match[1], variantValue) + queryParams;
                } 
                else {      
                    locationUrl = urlParts[0].endsWith('/') ? `${urlParts[0]}${variantValue}${queryParams}` : `${urlParts[0]}/${variantValue}${queryParams}`;
                }
            }

            let newUrl = locationUrl.endsWith('/') ? locationUrl.slice(0, -1) : locationUrl;
            window.location.href = newUrl;
        } else if (wpc_switcher_use_permalink_type == '2') {
            let regExp = /https?:\/\/[^\/]+\/([^\/]+)/;
            let match = locationUrl.match(regExp);
            if (match && ["zh-tw", "zh-cn", "zh-sg", "zh-hant", "zh-hans", "zh-my", "zh-mo", "zh-hk", "zh", "zh-reset"].includes(match[1])) {
                const newUrl = variantValue 
                    ? locationUrl.replace(match[1], variantValue) 
                    : locationUrl.replace(match[1] + '/', '');
                window.location.href = newUrl;
            }
        }
    }
}

