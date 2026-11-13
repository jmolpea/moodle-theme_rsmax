// Areas: headings for the Moodle activity list, without moving its items. Each area is a label
// marked as one (theme_rsmax\local\course_areas); on the course page it becomes the heading.
define('theme_rsmax/course_groups', [], function() {
    'use strict';
    /**
     * In the course index an area is a small heading, not an item to open.
     *
     * @param {Array} ids course module ids of the areas
     */
    var markIndex = function(ids) {
        var mark = function() {
            ids.forEach(function(id) {
                document.querySelectorAll('.courseindex [data-for="cm"][data-id="' + id + '"]').forEach(function(item) {
                    item.classList.add('rsmax-index-area');
                });
            });
        };
        mark();
        var index = document.getElementById('courseindex');
        if (index && window.MutationObserver) {
            new MutationObserver(mark).observe(index, {childList: true, subtree: true});
        }
    };

    return {
        init: function(groups) {
            if (!Array.isArray(groups)) {
                return;
            }
            markIndex(groups.map(function(group) {
                return group.id;
            }));
            groups.forEach(function(group) {
                var item = document.getElementById('module-' + group.id);
                if (!item || !item.parentElement || document.getElementById('rsmax-group-' + group.id)) {
                    return;
                }
                if (group.source) {
                    // The label that holds the area: its heading replaces it on the page.
                    item.classList.add('rsmax-area-source');
                }
                var heading = document.createElement('li');
                heading.id = 'rsmax-group-' + group.id;
                heading.className = 'rsmax-activity-group';
                var title = document.createElement('h3');
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'rsmax-group-toggle';
                button.textContent = group.label;
                button.setAttribute('aria-expanded', 'true');
                button.addEventListener('click', function() {
                    var collapsed = button.getAttribute('aria-expanded') === 'true';
                    var ids = [];
                    for (var node = heading.nextElementSibling; node; node = node.nextElementSibling) {
                        if (node.classList.contains('rsmax-activity-group')) {
                            break;
                        }
                        if (node.matches('li.activity')) {
                            node.classList.toggle('rsmax-group-collapsed', collapsed);
                            if (node.id) {
                                ids.push(node.id);
                            }
                        }
                    }
                    if (ids.length) {
                        button.setAttribute('aria-controls', ids.join(' '));
                    }
                    button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                });
                title.appendChild(button);
                heading.appendChild(title);
                item.parentElement.insertBefore(heading, item);
            });
        },
        /**
         * Edit mode: a link to add an area at the end of each section on the page.
         *
         * @param {Object} config courseid and url of the page that adds an area
         */
        editAreas: function(config) {
            markIndex(config.areas || []);
            var add = function() {
                document.querySelectorAll('[data-for="section"][data-number]').forEach(function(section) {
                    if (section.querySelector('.rsmax-area-add') || section.dataset.number === undefined) {
                        return;
                    }
                    var link = document.createElement('a');
                    link.className = 'rsmax-area-add';
                    link.href = config.url + '?course=' + encodeURIComponent(config.courseid)
                        + '&section=' + encodeURIComponent(section.dataset.number);
                    link.textContent = config.label;
                    var icon = document.createElement('i');
                    icon.className = 'fa fa-layer-group';
                    icon.setAttribute('aria-hidden', 'true');
                    link.prepend(icon, ' ');
                    var list = section.querySelector('[data-for="cmlist"]');
                    if (list && list.parentElement) {
                        list.parentElement.insertBefore(link, list.nextSibling);
                    } else {
                        section.appendChild(link);
                    }
                });
            };
            add();
            // The course editor redraws sections as the teacher works: keep the link in place.
            var region = document.querySelector('.course-content');
            if (region && window.MutationObserver) {
                new MutationObserver(add).observe(region, {childList: true, subtree: true});
            }
        },
        assistantSuggestions: function(prompts, label) {
            document.querySelectorAll('.openaiagent-container').forEach(function(container) {
                var card = container.querySelector('.openaiagent-card');
                var trigger = container.querySelector('.openaiagent-trigger-btn');
                var input = container.querySelector('.openaiagent-input');
                if (!card || !trigger || !input || card.querySelector('.rsmax-assistant-suggestions')) {
                    return;
                }
                var box = document.createElement('div');
                box.className = 'rsmax-assistant-suggestions';
                box.setAttribute('aria-label', label || '');
                prompts.forEach(function(prompt) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = prompt.label;
                    button.addEventListener('click', function() {
                        trigger.click();
                        input.value = prompt.text;
                        input.dispatchEvent(new Event('input', {bubbles: true}));
                        input.focus();
                    });
                    box.appendChild(button);
                });
                card.appendChild(box);
            });
        }
    };
});
