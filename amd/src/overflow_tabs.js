// Keeps Moodle's own tabs and puts the ones that do not fit in a disclosure at the end of the line.
define('theme_rsmax/overflow_tabs', [], function() {
    'use strict';
    return {
        init: function(strings) {
            strings = strings || {};
            var morelabel = strings.more || 'More';
            var currentlabel = strings.current || (morelabel + ': {$a}');
            document.querySelectorAll('.tabs-wrapper').forEach(function(wrapper) {
                var tabs = wrapper.querySelector(':scope > ul.nav.nav-tabs');
                if (!tabs || wrapper.classList.contains('rsmax-tabs-enhanced')) {
                    return;
                }
                var items = Array.from(tabs.querySelectorAll(':scope > li.nav-item'));
                if (!items.length) {
                    return;
                }
                var more = document.createElement('li');
                more.className = 'nav-item rsmax-tab-more';
                var details = document.createElement('details');
                var summary = document.createElement('summary');
                summary.textContent = morelabel;
                var overflow = document.createElement('ul');
                overflow.className = 'rsmax-overflow-items';
                details.append(summary, overflow);
                more.appendChild(details);
                tabs.appendChild(more);
                wrapper.classList.add('rsmax-tabs-enhanced');

                var frame = 0;
                var update = function() {
                    frame = 0;
                    if (!wrapper.getBoundingClientRect().width) {
                        return;
                    }
                    details.open = false;
                    items.forEach(function(item) {
                        tabs.insertBefore(item, more);
                    });
                    more.hidden = true;
                    more.classList.remove('contains-active');
                    tabs.scrollLeft = 0;
                    if (tabs.scrollWidth <= tabs.clientWidth + 1) {
                        details.open = false;
                        return;
                    }
                    more.hidden = false;
                    for (var i = items.length - 1; i >= 0 && tabs.scrollWidth > tabs.clientWidth + 1; i--) {
                        overflow.insertBefore(items[i], overflow.firstChild);
                    }
                    var active = overflow.querySelector('.nav-link.active');
                    more.classList.toggle('contains-active', Boolean(active));
                    summary.setAttribute('aria-label', active
                        ? currentlabel.replace('{$a}', active.textContent.trim())
                        : morelabel);
                };
                var schedule = function() {
                    if (!frame) {
                        frame = window.requestAnimationFrame(update);
                    }
                };
                window.addEventListener('resize', schedule);
                if (window.ResizeObserver) {
                    new ResizeObserver(schedule).observe(wrapper);
                }
                document.addEventListener('click', function(event) {
                    if (!more.contains(event.target)) {
                        details.open = false;
                    }
                });
                details.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape' && details.open) {
                        details.open = false;
                        summary.focus();
                    }
                });
                schedule();
            });
        }
    };
});
