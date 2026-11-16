const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement } = window.wp.element;
const { decodeEntities } = window.wp.htmlEntities;

const settings = window.wc.wcSettings.getSetting( 'originpay_data', {} );
const defaultLabel = 'Originpay';
const label = decodeEntities( settings.title || defaultLabel );

const Content = () => {
    return createElement(
        'div',
        { className: 'originpay-blocks-content' },
        decodeEntities( settings.description || '' )
    );
};

const Label = () => {
    const elements = [ label ];
    if ( settings.icon ) {
        elements.push(
            createElement( 'img', {
                src: settings.icon,
                alt: label,
                key: 'originpay-icon',
                style: { maxHeight: '24px', marginLeft: '10px', verticalAlign: 'middle' }
            } )
        );
    }
    return createElement( 'span', null, elements );
};

registerPaymentMethod( {
    name: 'originpay',
    label: createElement( Label, null ),
    content: createElement( Content, null ),
    edit: createElement( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports || [ 'products' ],
    },
} );
