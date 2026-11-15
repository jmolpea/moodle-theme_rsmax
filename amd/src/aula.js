// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The RSMAX classroom on the pages of a course: one vocabulary (names without codes, the type of
 * each activity in words with its duration), the presentation of each module at the top of its
 * section in any course format, and the delivery panel of the tasks.
 * Presentation only: names, order, restrictions and completion stay as Moodle has them.
 *
 * @module     theme_rsmax/aula
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('theme_rsmax/aula', [], function() {
    'use strict';

    var CODE = /^(\s*)(A\d+\.\d+[a-z]?|Lección \d+(?:\.\d+)*|Hito H\d+)\s*·\s*/;
    var S = {};

    /**
     * Shortens the leading code of the first text node of an element: «Lección 0.2 · » becomes «0.2 · » and
     * «Hito H1 · » becomes «H1 · ». The code stays visible because the lessons and the tasks refer to each other
     * by it («haz la A0.3», «repasa la lección 5.2»): without it nobody can find what the text points to.
     *
     * @param {Element} element The element whose first text is shortened.
     */
    var clean = function(element) {
        if (!element) {
            return;
        }
        var walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
        var node = walker.nextNode();
        while (node && !node.nodeValue.trim()) {
            node = walker.nextNode();
        }
        if (node && CODE.test(node.nodeValue)) {
            node.nodeValue = node.nodeValue.replace(CODE, function(match, space, code) {
                return space + code.replace(/^Lección /, '').replace(/^Hito /, '') + ' · ';
            });
        }
    };

    var label = function(one) {
        return one[0] + (one[1] ? ' · ' + one[1] : '');
    };

    var decorateList = function(meta) {
        document.querySelectorAll('li.activity[data-id]').forEach(function(item) {
            var one = meta[item.dataset.id];
            var name = item.querySelector('.activityname .instancename');
            if (!name || item.dataset.rsmaxAula) {
                return;
            }
            item.dataset.rsmaxAula = '1';
            clean(name);
            if (one) {
                var kind = name.querySelector('.accesshide');
                if (kind) {
                    kind.textContent = label(one);
                    kind.classList.add('rsmax-kind');
                }
                if (one[2]) {
                    item.classList.add('rsmax-k-' + one[2]);
                }
            }
        });
    };

    var decorateIndex = function() {
        document.querySelectorAll('.courseindex-cm .courseindex-link, .courseindex-item .courseindex-link').forEach(clean);
    };

    var decoratePage = function(step) {
        document.querySelectorAll('#page-header h1, .page-header-headings h1, #region-main h1, .breadcrumb-item,' +
            ' #prev-activity-link, #next-activity-link, .rsmax-linearnav a, .rsmax-dock-name, #sticky-footer a,' +
            ' .activity-navigation a')
            .forEach(function(element) {
                element.querySelectorAll('*').forEach(clean);
                clean(element);
            });
        // A generated lesson opens with «Lección 0.2 · lectura de 5 min»: it becomes its place in the module.
        var chip = document.querySelector('#region-main .rsx > .rsx-label:first-child');
        if (chip && step && /^Lección/.test(chip.textContent.trim())) {
            var number = (chip.textContent.trim().match(/^Lección [\d.]+/) || [step.module])[0];
            var rest = chip.textContent.trim().replace(/^Lección [\d.]+\s*(·\s*)?/, '');
            chip.textContent = number + ' · ' + S.step.replace('{$a->step}', step.step).replace('{$a->total}', step.total) +
                (rest ? ' · ' + rest : '');
        }
    };

    /** A brief written in the content (generated courses): the first lines, and the rest on demand. */
    var foldBrief = function() {
        document.querySelectorAll('.course-content .rsx-brief:not(.rsmax-pres-brief)').forEach(function(brief) {
            if (brief.classList.contains('rsmax-brief-folded') || brief.dataset.rsmaxAula) {
                return;
            }
            brief.dataset.rsmaxAula = '1';
            var texts = brief.querySelectorAll('.rsx-brief__text');
            if (!texts.length) {
                return;
            }
            brief.classList.add('rsmax-brief-folded');
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'rsmax-brief-toggle';
            button.textContent = S.readfull;
            button.setAttribute('aria-expanded', 'false');
            button.addEventListener('click', function() {
                var open = brief.classList.toggle('rsmax-brief-folded') === false;
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
                button.textContent = open ? S.readless : S.readfull;
            });
            texts[texts.length - 1].after(button);
        });
    };

    /**
     * A long text of a presentation shows its beginning and opens on demand.
     *
     * @param {Element} text The text of the presentation.
     */
    var foldLong = function(text) {
        if (text.dataset.rsmaxFold || text.scrollHeight < 340) {
            return;
        }
        text.dataset.rsmaxFold = '1';
        text.classList.add('rsmax-pres-folded');
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'rsmax-brief-toggle';
        button.textContent = S.readmore;
        button.setAttribute('aria-expanded', 'false');
        button.addEventListener('click', function() {
            var open = text.classList.toggle('rsmax-pres-folded') === false;
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            button.textContent = open ? S.readless : S.readmore;
        });
        text.after(button);
    };

    /**
     * The presentation of each module goes to the top of its section, before its activities.
     *
     * @param {Object} config editing, url and the ids of the sections with a presentation
     */
    var placePresentations = function(config) {
        var store = document.getElementById('rsmax-presentations');
        var nodes = {};
        if (store) {
            store.querySelectorAll('[data-rsmax-presentation]').forEach(function(node) {
                nodes[node.dataset.rsmaxPresentation] = node;
            });
        }
        var place = function() {
            document.querySelectorAll('[data-for="section"][data-id]').forEach(function(section) {
                // Subsections are part of a module, not modules of their own.
                if (section.closest('[data-for="cmitem"]')) {
                    return;
                }
                var list = section.querySelector('[data-for="cmlist"]');
                if (!list || !list.parentElement) {
                    return;
                }
                var id = section.dataset.id;
                var node = nodes[id];
                if (node && !section.contains(node)) {
                    list.parentElement.insertBefore(node, list);
                    node.querySelectorAll('.rsmax-pres-text').forEach(foldLong);
                }
                // The first section holds general resources, not a module: it is offered a presentation only if it has one.
                if (config.editing && !section.querySelector('.rsmax-pres-edit') && (node || section.dataset.number !== '0')) {
                    var bar = document.createElement('div');
                    bar.className = 'rsmax-pres-edit';
                    var link = document.createElement('a');
                    link.className = 'rsmax-area-add';
                    link.href = config.url + '?id=' + encodeURIComponent(id);
                    var icon = document.createElement('i');
                    icon.className = 'fa fa-photo-film';
                    icon.setAttribute('aria-hidden', 'true');
                    link.append(icon, ' ', node ? S.editpresentation : S.addpresentation);
                    bar.appendChild(link);
                    list.parentElement.insertBefore(bar, node && section.contains(node) ? node : list);
                }
            });
        };
        place();
        var region = document.querySelector('.course-content');
        if (region && window.MutationObserver) {
            var frame = 0;
            new MutationObserver(function() {
                if (!frame) {
                    frame = window.requestAnimationFrame(function() {
                        frame = 0;
                        place();
                    });
                }
            }).observe(region, {childList: true, subtree: true});
        }
    };

    /** Task: the state of the delivery and its main button, above the brief. */
    var deliverPanel = function() {
        var main = document.querySelector('#region-main [role="main"]');
        var table = document.querySelector('.submissionstatustable');
        if (!main || !table || document.querySelector('.rsmax-deliver')) {
            return;
        }
        var row = function(test) {
            var found = '';
            table.querySelectorAll('tr').forEach(function(tr) {
                var th = tr.querySelector('th');
                var td = tr.querySelector('td');
                if (!found && th && td && test.test(th.textContent)) {
                    found = td.textContent.trim();
                }
            });
            return found;
        };
        var state = row(/estado de la entrega|submission status/i);
        var attempt = row(/intento|attempt/i).replace(/^(Este es el intento|This is attempt)\s*/i, '').replace(/\.$/, '');
        var grade = row(/estado de la calificaci|grading status/i);
        var action = document.querySelector('#region-main form input[name="action"][value="editsubmission"]');
        var actionbutton = action ? action.form.querySelector('button[type="submit"], input[type="submit"]') : null;
        var nothing = /no se han realizado|sin enviar|no entregado|no submission|no attempt|not submitted/i;
        var submitted = state !== '' && !nothing.test(state);

        var panel = document.createElement('aside');
        panel.className = 'rsmax-deliver';
        panel.setAttribute('aria-label', S.delivery);
        var title = document.createElement('p');
        title.className = 'rsmax-deliver-title';
        title.textContent = S.delivery;
        panel.appendChild(title);
        var list = document.createElement('dl');
        [[S.state, submitted ? state : S.notsent], [S.attempt, attempt], [S.grade, grade]].forEach(function(pair) {
            if (!pair[1]) {
                return;
            }
            var dt = document.createElement('dt');
            dt.textContent = pair[0];
            var dd = document.createElement('dd');
            dd.textContent = pair[1];
            list.append(dt, dd);
        });
        panel.appendChild(list);
        if (actionbutton) {
            var go = document.createElement('button');
            go.type = 'button';
            go.className = 'btn btn-primary rsmax-deliver-go';
            go.textContent = submitted ? S.editsubmission : S.addsubmission;
            go.addEventListener('click', function() {
                actionbutton.click();
            });
            panel.appendChild(go);
            document.body.classList.add('rsmax-has-deliver');
        } else if (document.querySelector('#region-main .feedback')) {
            var see = document.createElement('a');
            see.className = 'btn btn-primary rsmax-deliver-go';
            see.href = '#';
            see.textContent = S.seefeedback;
            see.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('#region-main .feedback').scrollIntoView({behavior: 'smooth', block: 'start'});
            });
            panel.appendChild(see);
        }
        if (document.querySelector('.openaiagent-trigger-btn')) {
            var help = document.createElement('button');
            help.type = 'button';
            help.className = 'rsmax-deliver-help';
            help.textContent = S.stuck;
            help.addEventListener('click', function() {
                document.querySelector('.openaiagent-trigger-btn').click();
            });
            panel.appendChild(help);
        }
        var header = document.querySelector('#region-main > .activity-header, #region-main .activity-header');
        if (header && header.parentElement) {
            header.parentElement.insertBefore(panel, header);
        } else {
            main.insertBefore(panel, main.firstChild);
        }
    };

    return {
        /**
         * @param {Object} data meta, step, assign, strings and presentations; left out, they are
         *     read from the page, where the layout leaves them in #rsmax-auladata
         */
        init: function(data) {
            if (!data) {
                var store = document.getElementById('rsmax-auladata');
                try {
                    data = store ? JSON.parse(store.textContent) : {};
                } catch (error) {
                    data = {};
                }
            }
            data = data || {};
            S = data.strings || {};
            var meta = data.meta || {};
            var run = function() {
                decorateList(meta);
                decorateIndex();
                foldBrief();
            };
            run();
            decoratePage(data.step);
            if (data.assign) {
                deliverPanel();
            }
            if (data.presentations) {
                placePresentations(data.presentations);
            }
            // The course index and the course page are redrawn by Moodle: keep the names clean.
            var frame = 0;
            var observer = new MutationObserver(function() {
                if (!frame) {
                    frame = window.requestAnimationFrame(function() {
                        frame = 0;
                        run();
                    });
                }
            });
            ['#courseindex', '.course-content'].forEach(function(selector) {
                var target = document.querySelector(selector);
                if (target) {
                    observer.observe(target, {childList: true, subtree: true});
                }
            });
        }
    };
});
