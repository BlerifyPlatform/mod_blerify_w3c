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
 * Narrows the template select to the chosen project and previews the template
 * image, so a teacher sees what they are about to issue before saving.
 *
 * @module     mod_blerify/template_picker
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    return {
        init: function(config) {
            var project = document.getElementById('id_projectid');
            var template = document.getElementById('id_templateid');
            var preview = document.getElementById('blerify-template-preview');

            if (!project || !template) {
                return;
            }

            var byProject = config.templatesByProject || {};

            var find = function(id) {
                var list = byProject[project.value] || [];
                for (var i = 0; i < list.length; i++) {
                    if (list[i].id === id) {
                        return list[i];
                    }
                }
                return null;
            };

            var showPreview = function() {
                if (!preview) {
                    return;
                }

                var chosen = find(template.value);
                preview.textContent = '';

                if (chosen && chosen.image) {
                    var img = document.createElement('img');
                    img.src = chosen.image;
                    img.alt = chosen.title;
                    img.style.maxWidth = '320px';
                    img.style.width = '100%';
                    img.style.height = 'auto';
                    img.style.border = '1px solid #dee2e6';
                    img.style.borderRadius = '6px';
                    preview.appendChild(img);
                } else {
                    var note = document.createElement('small');
                    note.className = 'text-muted';
                    note.textContent = config.noPreview || '';
                    preview.appendChild(note);
                }

                if (chosen && chosen.description) {
                    var desc = document.createElement('div');
                    desc.className = 'text-muted mt-1';
                    desc.textContent = chosen.description;
                    preview.appendChild(desc);
                }
            };

            var fillTemplates = function(keep) {
                var list = byProject[project.value] || [];
                var previous = keep ? template.value : null;

                template.textContent = '';

                var placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = config.chooseDots || '';
                template.appendChild(placeholder);

                list.forEach(function(item) {
                    var option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.title;
                    template.appendChild(option);
                });

                if (previous) {
                    template.value = previous;
                }
                // A project with no templates of its own leaves the select empty.
                if (!template.value && list.length) {
                    template.value = list[0].id;
                }

                showPreview();
            };

            project.addEventListener('change', function() {
                fillTemplates(false);
            });
            template.addEventListener('change', showPreview);

            // Keep the saved template selected when reopening an existing activity.
            fillTemplates(true);
        }
    };
});
