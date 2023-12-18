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
import 'tinymce';

// require jQuery normally
const $ = require('jquery');
// create global $ and jQuery variables
global.$ = global.jQuery = $;

bsCustomFileInput.init();

require('bootstrap');

$(document).ready(function() {

    tinymce.init({
        selector: '.tinymce',
        theme: "modern",
        plugins: "spellchecker,insertdatetime,preview,link,autolink",
        browser_spellcheck: true,
        menubar: "edit, insert, view, format, tools",
        toolbar: "undo, redo, formatselect, bold, italic, alignright, aligncenter, alignright, alignjustify, bullist, numlist  link",
        link_assume_external_targets: true
    });

    document.getElementById('btnSwitch').addEventListener('click',()=>{
        console.log(document.documentElement.getAttribute('data-bs-theme'));
        if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
            document.documentElement.setAttribute('data-bs-theme','light')
        } else {
            document.documentElement.setAttribute('data-bs-theme','dark')
        }
    })
});
