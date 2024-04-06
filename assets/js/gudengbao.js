( function( blocks, element ) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;

    if (typeof wpc_switcher_navi_data != 'undefined') {
        var aProps = wpc_switcher_navi_data['wpcs_navi']
        var type_arr = wpc_switcher_navi_data['type_arr']
 
        if (type_arr[0] == 0) {
            var spans = aProps.map(function(props) {
                
                return el(
                    'span',
                    { id: props.id, className: props.className },
                    el('a', { className: 'wpcs_link', href: 'javascript:void()', onClick: `wpcsRedirectToVariant('${props.variant}')`, title: props.title, style: {marginRight: '10px'} }, props.innerText)
                );
            });
            registerBlockType( 'my-theme/gutenberg-castle', {
                title: '简繁切换',
                icon: 'admin-home',
                category: 'widgets',
                edit: function() {
                    return el('div', { id: "wpcs_widget_inner" }, spans);
                },
                save: function() {
                    return el('div', { id: "wpcs_widget_inner" }, spans);
                },
            } );
        } else {
            const htmlString = aProps[0]
            // console.log(htmlString);
            registerBlockType( 'my-theme/gutenberg-castle', {
                title: '简繁切换',
                icon: 'admin-home',
                category: 'widgets',
                edit: function() {
                    return el('div', {dangerouslySetInnerHTML: { __html: htmlString }} );
                },
                save: function() {
                    return el('div', {dangerouslySetInnerHTML: { __html: htmlString }} );
                },
            } );
        }
    } 
} )(
    window.wp.blocks,
    window.wp.element
);
