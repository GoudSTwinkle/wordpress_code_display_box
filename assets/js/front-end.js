/**
 * Front-end JavaScript for Code Display Box
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab functionality
        $('.code-tab').on('click', function() {
            var language = $(this).data('language');
            var codeBox = $(this).closest('.code-display-box');

            // Update active tab
            codeBox.find('.code-tab').removeClass('active');
            $(this).addClass('active');

            // Show corresponding code block
            codeBox.find('.code-block').removeClass('active');
            var $activeBlock = codeBox.find('.code-block[data-language="' + language + '"]');
            $activeBlock.addClass('active');

            // Highlight code with Prism if available
            if (window.Prism && $activeBlock.length) {
                $activeBlock.find('code').each(function() {
                    Prism.highlightElement(this);
                });
            }
        });

        // Highlight all code blocks on page load
        if (window.Prism) {
            $('pre code').each(function() {
                Prism.highlightElement(this);
            });
        }
    });
})(jQuery);