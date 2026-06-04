import re

with open('assets/lang-fields.js', 'r') as f:
    text = f.read()

bad1 = """            menuHtml += '</ul>';
            $group.append(menuHtml);

            // Klick auf Menüeintrag"""

new1 = """            var showIncomplete = localStorage.getItem('ylf_show_incomplete') === 'true';
            var iconClass = showIncomplete ? 'fa-check-square-o' : 'fa-square-o';
            menuHtml += '<li role="separator" class="divider"></li>';
            menuHtml += '<li><a href="#" class="ylf-toggle-incomplete"><i class="fa fa-fw ' + iconClass + '"></i> <small>Fehlende hervorheben</small></a></li>';
            menuHtml += '</ul>';
            $group.append(menuHtml);

            // Klick auf Incomplete Toggle
            $group.on('click', '.ylf-toggle-incomplete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var isEnabled = localStorage.getItem('ylf_show_incomplete') === 'true';
                isEnabled = !isEnabled;
                localStorage.setItem('ylf_show_incomplete', isEnabled ? 'true' : 'false');
                
                var $icon = $(this).find('.fa');
                if (isEnabled) {
                    $icon.removeClass('fa-square-o').addClass('fa-check-square-o');
                } else {
                    $icon.removeClass('fa-check-square-o').addClass('fa-square-o');
                }
                self.applyIncompleteHighlighting();
            });

            // Klick auf Menüeintrag"""

bad2 = """            // Initiale Anzeige
            self.applyActiveLang(activeClang.id);
        },

        /**"""

new2 = """            // Initiale Anzeige
            self.applyActiveLang(activeClang.id);
            self.applyIncompleteHighlighting();
        },

        applyIncompleteHighlighting: function() {
            var isEnabled = localStorage.getItem('ylf_show_incomplete') === 'true';
            if (isEnabled) {
                $('body').addClass('ylf-show-incomplete');
            } else {
                $('body').removeClass('ylf-show-incomplete');
            }
        },

        /**"""

text = text.replace(bad1, new1)
text = text.replace(bad2, new2)

with open('assets/lang-fields.js', 'w') as f:
    f.write(text)

