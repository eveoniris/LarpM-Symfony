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

$(document).ready(function() {
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
    document.getElementById('btnSwitch').addEventListener('click',()=>{
        //console.log(document.documentElement.getAttribute('data-bs-theme'));
        if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
            document.documentElement.setAttribute('data-bs-theme','light')
            toggleTinyMCE('light');
        } else {
            document.documentElement.setAttribute('data-bs-theme','dark')
            toggleTinyMCE('dark');
        }
    })
});
