/*
 * Interface bêta : améliore les tableaux de liste (style lm-table, cartes sous 640 px) et regroupe les actions de ligne.
 * Chargé par assets/lm-ui.js ; sans effet hors interface bêta (html[data-ui="beta"]). Règles du guide /design §5 et §5b :
 * - 3 actions ou moins : toutes visibles ; 4 ou plus : action principale + bouton « Actions » (menu) ;
 * - action destructive en rouge, séparée dans le menu ;
 * - sous 640 px, la ligne devient une carte et les actions passent dans un menu « ⋯ ».
 * Un tableau est ignoré s'il contient des cellules fusionnées ou n'a pas d'en-tête.
 * Gère aussi « Afficher plus » ([data-lm-more]) : paquets de 25 lignes et recherche côté client.
 */
const DESTRUCTIVE = /supprim|delete|retirer|remove|refus|reject|d[ée]sinscri/i;

function labelOf(el) {
    return (el.getAttribute('title') || el.getAttribute('aria-label') || el.textContent || '').replace(/\s+/g, ' ').trim() || 'Action';
}

function unitOf(el, cell) {
    const form = el.closest('form');
    return form && cell.contains(form) ? form : el;
}

function itemInfo(el) {
    const text = el.textContent.replace(/\s+/g, ' ').trim();
    const icon = el.querySelector('i');
    return {
        label: labelOf(el),
        hasText: text !== '',
        icon: icon ? icon.className : '',
        destructive: /danger/.test(el.className) || DESTRUCTIVE.test((el.getAttribute('href') || '') + ' ' + labelOf(el)),
    };
}

function styleAsMenuItem(el, info) {
    el.className = 'lm-act-item' + (info.destructive ? ' is-danger' : '');
    el.removeAttribute('data-bs-toggle');
    el.removeAttribute('data-toggle');
    el.removeAttribute('data-bs-placement');
    el.innerHTML = (info.icon ? '<i class="' + info.icon + '" aria-hidden="true"></i>' : '') + '<span></span>';
    el.querySelector('span').textContent = info.label;
}

function styleAsButton(el, info) {
    el.className = 'lm-btn lm-btn--sm' + (info.destructive ? ' lm-btn--danger' : '') + (info.hasText ? '' : ' lm-btn--icon');
    el.removeAttribute('data-toggle');
    if (!info.hasText) {
        el.setAttribute('aria-label', info.label);
    }
}

function menu(summaryHtml, summaryClass, units, extraClass) {
    const details = document.createElement('details');
    details.className = 'lm-act-menu' + (extraClass ? ' ' + extraClass : '');
    const summary = document.createElement('summary');
    summary.className = summaryClass;
    summary.innerHTML = summaryHtml;
    const panel = document.createElement('div');
    panel.className = 'lm-act-panel';
    let danger = false;
    units.forEach(function (u) {
        if (u.info.destructive && !danger) {
            danger = true;
            if (panel.children.length) {
                panel.appendChild(document.createElement('hr'));
            }
        }
        panel.appendChild(u.node);
    });
    details.append(summary, panel);
    return details;
}

function buildActions(cell) {
    if (cell.querySelector('.dropdown, .dropdown-menu, select, input:not([type=hidden]), textarea')) {
        return;
    }
    const els = Array.from(cell.querySelectorAll('a[href], button'));
    if (!els.length) {
        return;
    }
    const entries = els.map(function (el) {
        return {el: el, unit: unitOf(el, cell), info: itemInfo(el)};
    });
    // Menu mobile : copies de toutes les actions, créées avant de restyler les originaux.
    const mobileUnits = entries.map(function (e) {
        const clone = e.unit.cloneNode(true);
        const target = clone.matches('a[href], button') ? clone : clone.querySelector('a[href], button');
        styleAsMenuItem(target, e.info);
        return {node: clone, info: e.info};
    });
    const mobile = menu('<i class="fa fa-ellipsis" aria-hidden="true"></i>', 'lm-btn lm-btn--sm lm-btn--icon', mobileUnits, 'lm-act-mobile');
    mobile.querySelector('summary').setAttribute('aria-label', 'Actions');

    const inline = document.createElement('div');
    inline.className = 'lm-actions lm-actions--end lm-act-inline';
    if (entries.length <= 3) {
        entries.forEach(function (e) {
            styleAsButton(e.el, e.info);
            inline.appendChild(e.unit);
        });
    } else {
        const primaryIndex = Math.max(0, entries.findIndex(function (e) { return !e.info.destructive; }));
        const rest = [];
        entries.forEach(function (e, i) {
            if (i === primaryIndex) {
                styleAsButton(e.el, e.info);
                inline.appendChild(e.unit);
            } else {
                styleAsMenuItem(e.el, e.info);
                rest.push({node: e.unit, info: e.info});
            }
        });
        inline.appendChild(menu('Actions <i class="fa fa-chevron-down" aria-hidden="true"></i>', 'lm-btn lm-btn--sm', rest));
    }
    const notes = Array.from(cell.querySelectorAll('.badge, .lm-badge')).map(function (b) { return b.cloneNode(true); });
    cell.replaceChildren(...notes, inline, mobile);
    cell.classList.add('lm-col-actions');
}

/** Regroupe le contenu d'une cellule dans un seul bloc : en carte mobile, icône et texte restent côte à côte (sinon chaque enfant devient un élément flex écarté). */
function wrapCell(td) {
    if (!td.firstChild || (td.children.length === 1 && td.firstElementChild.classList.contains('lm-cell-block'))) {
        return;
    }
    const box = document.createElement('div');
    box.className = 'lm-cell-value';
    while (td.firstChild) {
        box.appendChild(td.firstChild);
    }
    td.appendChild(box);
}

function enhance(table) {
    const head = table.tHead;
    if (!head || head.rows.length !== 1 || table.querySelector('[colspan], [rowspan]') || table.closest('form')) {
        return;
    }
    const ths = Array.from(head.rows[0].cells);
    if (ths.length < 2) {
        return;
    }
    const labels = ths.map(function (th) { return th.textContent.replace(/\s+/g, ' ').trim(); });
    const actionIdx = /^actions?$/i.test(labels[labels.length - 1]) ? labels.length - 1 : -1;
    const mainIdx = labels.findIndex(function (l) { return /^(nom|label|libell[ée]|pseudo|titre|nom du .+)$/i.test(l); });

    table.classList.remove('table', 'table-striped', 'table-bordered', 'table-sm', 'table-hover');
    table.classList.add('lm-table', 'lm-table--cards');
    const wrap = table.parentElement;
    if (wrap && wrap.classList.contains('table-responsive')) {
        wrap.classList.add('lm-table-wrap');
    }
    if (actionIdx >= 0) {
        ths[actionIdx].classList.add('lm-col-actions');
    }
    Array.from(table.tBodies).forEach(function (body) {
        Array.from(body.rows).forEach(function (row) {
            if (row.cells.length !== ths.length) {
                return;
            }
            Array.from(row.cells).forEach(function (td, i) {
                td.setAttribute('data-label', labels[i]);
                if (i === mainIdx) {
                    td.classList.add('lm-cell-main');
                }
                if (i !== actionIdx) {
                    wrapCell(td);
                }
            });
            if (actionIdx >= 0) {
                buildActions(row.cells[actionIdx]);
            }
        });
    });
}

// Menus d'actions : panneau positionné à l'écran (le tableau peut défiler), fermé au clic extérieur, à Échap ou au défilement.
function closeMenus(except) {
    document.querySelectorAll('details.lm-act-menu[open]').forEach(function (d) {
        if (d !== except) {
            d.open = false;
        }
    });
}

function onToggle(event) {
    const d = event.target;
    if (!(d instanceof HTMLElement) || !d.matches('details.lm-act-menu') || !d.open) {
        return;
    }
    closeMenus(d);
    const panel = d.querySelector('.lm-act-panel');
    const rect = d.querySelector('summary').getBoundingClientRect();
    panel.style.right = Math.max(8, window.innerWidth - rect.right) + 'px';
    const below = rect.bottom + 4;
    panel.style.top = below + panel.offsetHeight > window.innerHeight && rect.top > panel.offsetHeight
        ? (rect.top - panel.offsetHeight - 4) + 'px'
        : below + 'px';
}


// « Afficher plus » : [data-lm-more] contient les lignes ([data-lm-row]) ; paquets de 25 + recherche côté client
// et filtre optionnel [data-lm-more-gn] (ids de GN dans data-gn de chaque ligne).
function setupMore(box) {
    if (box.dataset.lmMoreReady) {
        return;
    }
    box.dataset.lmMoreReady = '1';
    const rows = Array.from(box.querySelectorAll('[data-lm-row]'));
    const step = parseInt(box.dataset.lmMore, 10) || 25;
    const gnSelect = box.querySelector('[data-lm-more-gn]');
    if (rows.length <= step && !gnSelect) {
        return;
    }
    let shown = step;
    let query = '';
    let gn = '';
    const search = box.querySelector('[data-lm-more-search]');
    const button = box.querySelector('[data-lm-more-button]');
    const count = box.querySelector('[data-lm-more-count]');
    const empty = box.querySelector('[data-lm-more-empty]');
    function norm(s) {
        return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }
    function render() {
        const filtering = query !== '' || gn !== '';
        const matching = rows.filter(function (r) {
            return (query === '' || norm(r.textContent).includes(query))
                && (gn === '' || (r.dataset.gn || '').split(' ').includes(gn));
        });
        rows.forEach(function (r) { r.hidden = true; });
        const visible = filtering ? matching : matching.slice(0, shown);
        visible.forEach(function (r) { r.hidden = false; });
        if (count) {
            count.textContent = !filtering
                ? visible.length + ' affichés sur ' + rows.length
                : matching.length + ' résultat' + (matching.length > 1 ? 's' : '') + ' sur ' + rows.length;
        }
        if (button) {
            button.hidden = filtering || visible.length >= rows.length;
        }
        if (empty) {
            empty.hidden = matching.length > 0;
        }
    }
    if (search) {
        search.addEventListener('input', function () {
            query = norm(search.value.trim());
            render();
        });
    }
    if (gnSelect) {
        gnSelect.addEventListener('change', function () {
            gn = gnSelect.value;
            render();
        });
    }
    if (button) {
        button.addEventListener('click', function () {
            shown += step;
            render();
        });
    }
    box.querySelectorAll('[data-lm-more-tools]').forEach(function (t) { t.hidden = false; });
    render();
}

function init() {
    document.querySelectorAll('#content table.table').forEach(enhance);
    document.querySelectorAll('[data-lm-more]').forEach(setupMore);
}

if (document.documentElement.dataset.ui === 'beta' && !window.lmListsBound) {
    window.lmListsBound = true;
    // Menus d'actions : fermés au clic extérieur, à Échap ou au défilement.
    document.addEventListener('toggle', onToggle, true);
    document.addEventListener('click', (event) => {
        closeMenus(event.target.closest ? event.target.closest('details.lm-act-menu') : null);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenus(null);
        }
    });
    window.addEventListener('scroll', () => closeMenus(null), {passive: true});

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    document.addEventListener('turbo:load', init);
}
