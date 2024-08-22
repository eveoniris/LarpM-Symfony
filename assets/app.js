import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

import 'bootstrap';
import bsCustomFileInput from 'bs-custom-file-input';
import 'bootstrap-select';

// Import TinyMCE
import tinymce from 'tinymce/tinymce';

// A theme is also required
import 'tinymce/themes/silver/theme';

// Any plugins you want to use has to be imported
import 'tinymce/plugins/link';
import 'tinymce/plugins/code';
import 'tinymce/plugins/table';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/media';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/image';

/* Note
If we want to User pref color scheme to set default theme
Theme = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "";,
* */

// require jQuery normally
const $ = require('jquery');
// create global $ and jQuery variables
global.$ = global.jQuery = $;

bsCustomFileInput.init();

require('bootstrap');

// Modal 2
var $mainModal = $('#mainModal');

function openModalX(title, body, footer, htmlOptions) {
    let defaultOptions = {
        'width' : null,
        'height': null
    };

    if (typeof htmlOptions === "undefined") {
        htmlOptions = {};
    }

    for (let index in defaultOptions) {
        if (typeof htmlOptions[index] === "undefined") {
            htmlOptions[index] = defaultOptions[index];
        }
    }

    $mainModal.find('.modal-dialog').removeAttr('style');
    for (let index in htmlOptions) {
        if (htmlOptions[index] !== null) {
            $mainModal.find('.modal-dialog').css(index, htmlOptions[index]);
        }
    }
    $mainModal.find('.modal-header .modal-title').html(title);
    $mainModal.find('.modal-body').html(body);
    $mainModal.find('.modal-footer').html(footer);
}

function closeModal() {
    $mainModal.modal('hide');
}

function openErrorModal(error) {
    openModalX(
        'Error',
        error,
        '<a href="#" class="btn btn-secondary" data-dismiss="modal">Fermer</a>'
    )
}

function getDataSet(value, defaultValue) {
    if (typeof value === 'undefined' || value === false) {
        value = defaultValue;
    }

    return value;
}

function openEventDataModal(elmt, withAjaxCall) {
    let htmlOptions = {};
    let hasError    = false;
    let closeLabel  = getDataSet(elmt.data('modal-close'), 'Fermer');
    let submitLabel = getDataSet(elmt.data('modal-submit'), 'Valider');
    let header      = getDataSet(elmt.data('modal-header'), 'Confirmation');
    let body        = getDataSet(elmt.data('modal-body'), 'Todo');
    let footer      = getDataSet(elmt.data('modal-footer'), '<a href="#" class="btn btn-secondary" data-dismiss="modal">' + closeLabel + '</a> <a href="#" class="btn btn-secondary btn-modal-valid">' + submitLabel + '</a>');
    let width       = getDataSet(elmt.data('modal-width'), false);
    let height      = getDataSet(elmt.data('modal-height'), false);
    let href        = getDataSet(elmt.attr('href'), false);

    if (width) {
        htmlOptions.width = width;
    }

    if (height) {
        htmlOptions.height = height;
    }

    if (withAjaxCall) {

    }

}

$(document).ready(function () {
    $("[data-toggle='tooltip']").tooltip();
    $("[data-bs-toggle='tooltip']").tooltip();

    // Editeur de text TinyMCE
    let tinyMCEPlugins =
        [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor',
            'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
        ];
    let tinyMCEPluginsFull =
        [
            'print preview powerpaste casechange importcss tinydrive searchreplace codesample',
            'autolink autosave save directionality advcode visualblocks visualchars fullscreen image link media mediaembed template r',
            'table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists checklist wordcount',
            'tinymcespellchecker a11ychecker imagetools textpattern noneditable help formatpainter permanentpen',
            'pageembed charmap tinycomments mentions quickbars linkchecker emoticons advtable export'
        ];
    let tinyMCEtoolbar = 'undo redo | formatselect | ' +
        'bold italic backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | help';
    let tinyMCEtoolbarFull = 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect ' +
        'formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist checklist | ' +
        'forecolor backcolor casechange permanentpen formatpainter removeformat | pagebreak | charmap emoticons | fullscreen  ' +
        'preview save print | insertfile image media pageembed template link anchor codesample | a11ycheck ltr rtl | ' +
        'showcomments addcomment';

    let tinyMCEThemeDark = {
        selector: 'textarea.tinymce',
        plugins: tinyMCEPlugins,
        toolbar: tinyMCEtoolbar,
        link_assume_external_targets: true,
        skin: "oxide-dark",
        content_css: "dark"
    };
    let tinyMCEThemeLight = {
        selector: 'textarea.tinymce',
        plugins: tinyMCEPlugins,
        toolbar: tinyMCEtoolbar,
        link_assume_external_targets: true,
        skin: "oxide",
        content_css: ""
    };
    let tinyMCEThemeDarkFull = {
        selector: 'textarea.tinymce-full',
        plugins: tinyMCEPlugins,
        toolbar: tinyMCEtoolbar,
        link_assume_external_targets: true,
        skin: "oxide-dark",
        content_css: "dark"
    };
    let tinyMCEThemeLightFull = {
        selector: 'textarea.tinymce-full',
        plugins: tinyMCEPlugins,
        toolbar: tinyMCEtoolbar,
        link_assume_external_targets: true,
        skin: "oxide",
        content_css: ""
    };

    function toggleTinyMCE(mode) {
        let activeEditor = tinymce.activeEditor;

        if (!activeEditor) {
            return;
        }

        let content = activeEditor.getContent();
        activeEditor.destroy();

        if (mode === 'dark') {
            tinymce.init(tinyMCEThemeDark);
            //tinymce.init(tinyMCEThemeDarkFull);
        } else {
            tinymce.init(tinyMCEThemeLight);
            //tinymce.init(tinyMCEThemeLightFull);
        }
    }

    // END Editeur de text TinyMCE

    tinymce.init(tinyMCEThemeDark);
    //tinymce.init(tinyMCEThemeDarkFull);
    // End editeur de text

    // Switch de theme. TODO: En session || cookie
    let btnSwitch = document.getElementById('btnSwitch');
    if (btnSwitch) {
        document.getElementById('btnSwitch').addEventListener('click', () => {
            if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', 'light')
                toggleTinyMCE('light');
            } else {
                document.documentElement.setAttribute('data-bs-theme', 'dark')
                toggleTinyMCE('dark');
            }
        })
    }

    // Modal
    const confirmModal = document.getElementById('mainModal')
    if (confirmModal) {
        confirmModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget
            // const modal = event.target is like confirmModal var

            // Extract info from data-bs-* attributes
            const title = button.getAttribute('data-bs-title')
            const body = button.getAttribute('data-bs-body')
            const action = button.getAttribute('data-bs-action')
            const ajaxContentPath = button.getAttribute('data-bs-body-path')
            const formMethod = button.getAttribute('data-bs-method')

            // Update the modal's content.
            const modalTitle = confirmModal.querySelector('.modal-title')
            const modalBody = confirmModal.querySelector('.modal-body')
            const modalConfirmBtn = $('#mainModal .btn-modal-confirm')
            const modalConfirmForm = $('#mainModal .modal-footer form')

            // Load content from an ajax call
            if (ajaxContentPath) {
                $.ajax({
                    url    : href,
                    type   : getDataSet(ajaxContentPath, 'GET'),
                    success: function (html) {
                        modalBody.innerHTML = html;
                    },
                    error  : function (e) {
                        modalBody.innerHTML = e.responseText;
                    },
                    async  : false // <- this turns it into synchronous
                });
            } else {
                modalBody.innerHTML = body
            }

            modalTitle.textContent = title

            if (modalConfirmForm && action) {
                modalConfirmForm.attr('action', action)
            }

            if (modalConfirmForm && formMethod) {
                modalConfirmForm.attr('method', formMethod.toUpperCase())
            }

            // sample for input value
            // const modalBodyInput = exampleModal.querySelector('.modal-body input')
            // modalBodyInput.value = recipient

            if (modalConfirmBtn) {
                modalConfirmBtn.off('click').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    let form = $('#mainModal .modal-body form');
                    if (form.length > 0) {
                        form.submit();
                        return;
                    }

                    if (modalConfirmForm) {
                        modalConfirmForm.submit();
                        return;
                    }

                    if (action) {
                        window.location.replace(action);
                        //return;
                    }
                });
            }

            // Dissmiss
            $mainModal.find('#footer-close-mainmodal').click();
        })
    }

    $('.btn-confirm-conf[data-bs-body]').off('click').on('click', function (e) {
        e.preventDefault();
        openEventDataModal($(this), false);
        e.stopPropagation();
    });

    $('.btn-form-direct-submit[data-bs-body], .btn-open-ajax-modal').off('click').on('click', function (e) {
        e.preventDefault();
        openEventDataModal($(this), true);
    });

});
