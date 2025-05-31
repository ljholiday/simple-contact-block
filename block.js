const { registerBlockType } = wp.blocks;
const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
const { PanelBody, TextControl, ColorPicker } = wp.components;
const { Fragment, createElement } = wp.element;

registerBlockType('cfb/contact-form', {
    title: 'Contact Form',
    icon: 'email',
    category: 'widgets',

    attributes: {
        buttonText: {
            type: 'string',
            default: 'Submit'
        },
        buttonColor: {
            type: 'string',
            default: '#000000'
        }
    },

    supports: {
        color: {
            text: true,
            background: true
        },
        typography: {
            fontSize: true
        }
    },

    edit: (props) => {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();

        return createElement(Fragment, null, [
            createElement(
                InspectorControls,
                {},
                createElement(
                    PanelBody,
                    { title: 'Button Settings', initialOpen: true },
                    createElement(TextControl, {
                        label: 'Button Text',
                        value: attributes.buttonText,
                        onChange: (val) => setAttributes({ buttonText: val })
                    }),
                    createElement('div', { style: { marginTop: '16px' } },
                        createElement('label', {}, 'Button Color'),
                        createElement(ColorPicker, {
                            color: attributes.buttonColor,
                            onChangeComplete: (val) => setAttributes({ buttonColor: val.hex }),
                            disableAlpha: true
                        })
                    )
                )
            ),
            createElement('form', { ...blockProps, style: { pointerEvents: 'none' } }, [
                createElement('label', { htmlFor: 'cfb_email' }, 'Your Email:'),
                createElement('br'),
                createElement('input', {
                    type: 'email',
                    name: 'cfb_email',
                    id: 'cfb_email',
                    required: true,
                    style: { display: 'block', marginBottom: '8px', marginTop: '4px' }
                }),
                createElement('input', {
                    type: 'submit',
                    value: attributes.buttonText,
                    disabled: true,
                    style: {
                        backgroundColor: attributes.buttonColor,
                        color: '#fff',
                        padding: '8px 16px',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'not-allowed'
                    }
                }),
                createElement('p', {
                    style: { fontSize: '12px', color: '#777', marginTop: '10px' }
                }, 'Form preview (disabled in editor)')
            ])
        ]);
    },

    save: () => {
        return null; // Rendered via PHP
    }
});