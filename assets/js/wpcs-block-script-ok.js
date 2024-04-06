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
    let url = window.location.href;
    url = updateUrlParameter(url, 'variant', variantValue);
    window.location.href = url;
}

