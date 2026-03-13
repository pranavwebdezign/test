/**
 * WooBooking Pro – Gutenberg Block (with Config ID support)
 */
( function( blocks, element, components ) {
    var el         = element.createElement;
    var TextControl = components.TextControl;
    var PanelBody   = components.PanelBody;
    var InspectorControls = wp.blockEditor ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls;

    blocks.registerBlockType( 'woo-booking-pro/booking-form', {
        title      : 'WooBooking Form',
        icon       : 'calendar-alt',
        category   : 'widgets',
        description: 'Multi-step WooCommerce booking form.',
        supports   : { html: false },
        attributes : {
            id: { type: 'string', default: '' }
        },

        edit: function( props ) {
            var id = props.attributes.id;
            return [
                el( InspectorControls, { key: 'inspector' },
                    el( PanelBody, { title: 'Booking Config', initialOpen: true },
                        el( TextControl, {
                            label: 'Config ID (optional)',
                            help : 'Leave blank to use global Postcode Zones. Enter a Booking Config post ID to use its specific zones.',
                            value: id,
                            onChange: function(v){ props.setAttributes({ id: v }); }
                        })
                    )
                ),
                el( 'div', {
                    key:'preview',
                    style: { padding:'24px', background:'#f0f4ff', border:'2px dashed #7b96d4', borderRadius:'6px', textAlign:'center' }
                },
                    el('span',{ className:'dashicons dashicons-calendar-alt', style:{fontSize:'32px',color:'#7b96d4'} }),
                    el('p',  { style:{margin:'8px 0 0',fontWeight:'bold',color:'#333'} }, 'WooBooking Pro Form'),
                    id
                        ? el('p', { style:{color:'#2c7be5',fontSize:'13px'} }, '📋 Config ID: ' + id + ' — [woo_booking id="' + id + '"]')
                        : el('p', { style:{color:'#666',  fontSize:'13px'} }, '[woo_booking] — global zones')
                )
            ];
        },

        save: function() { return null; }
    });
} ( window.wp.blocks, window.wp.element, window.wp.components ) );
