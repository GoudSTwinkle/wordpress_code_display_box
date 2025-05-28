/**
 * Admin JavaScript for Code Display Box
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab functionality
        $('.code-display-box-tab-nav a').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            // Update active tab
            $('.code-display-box-tab-nav li').removeClass('active');
            $(this).parent().addClass('active');
            
            // Show target tab content
            $('.code-display-box-tab-pane').removeClass('active');
            $(target).addClass('active');
        });
        
        // Category change - load posts
        $('#code-display-box-category, #edit-code-display-box-category').on('change', function() {
            var categoryId = $(this).val();
            var postSelect = $(this).attr('id') === 'code-display-box-category' ? 
                $('#code-display-box-post') : $('#edit-code-display-box-post');
            
            if (!categoryId) {
                postSelect.html('<option value="">Select a post</option>');
                postSelect.prop('disabled', true);
                return;
            }
            
            // Show loading
            postSelect.html('<option value="">Loading posts...</option>');
            
            // Ajax request to get posts
            $.ajax({
                url: codeDisplayBox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'code_display_box_get_posts_by_category',
                    category_id: categoryId,
                    nonce: codeDisplayBox.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var options = '<option value="">Select a post</option>';
                        
                        $.each(response.data, function(index, post) {
                            options += '<option value="' + post.id + '">' + post.title + '</option>';
                        });
                        
                        postSelect.html(options);
                        postSelect.prop('disabled', false);
                    } else {
                        postSelect.html('<option value="">No posts found</option>');
                        postSelect.prop('disabled', true);
                    }
                },
                error: function() {
                    postSelect.html('<option value="">Error loading posts</option>');
                    postSelect.prop('disabled', true);
                }
            });
        });
        
        // Languages count change
        $('#code-display-box-languages-count').on('change', function() {
            updateLanguageTabs($(this).val(), 'code-display-box-languages-container');
        });
        
        // Languages count change for edit form
        $('#edit-code-display-box-languages-count').on('change', function() {
            updateLanguageTabs($(this).val(), 'edit-code-display-box-languages-container');
        });
        
        // Function to update language tabs
        function updateLanguageTabs(count, containerId) {
            count = parseInt(count, 10);
            var container = $('#' + containerId);
            var currentItems = container.find('.code-language-item').length;
            var prefix = containerId === 'edit-code-display-box-languages-container' ? 'edit-' : '';
            
            if (count < 1) {
                $('#' + prefix + 'code-display-box-languages-count').val(1);
                count = 1;
            }
            
            if (count > currentItems) {
                // Add new items
                for (var i = currentItems; i < count; i++) {
                    var template = 
                        '<div class="code-language-item" data-index="' + i + '">' +
                            '<h3>Language Tab ' + (i + 1) + '</h3>' +
                            '<table class="form-table">' +
                                '<tr>' +
                                    '<th scope="row"><label for="' + prefix + 'code-language-name-' + i + '">Language Name</label></th>' +
                                    '<td>' +
                                        '<input type="text" id="' + prefix + 'code-language-name-' + i + '" name="' + prefix + 'code-language-name-' + i + '" class="regular-text" placeholder="e.g. JavaScript, PHP, Python" required>' +
                                    '</td>' +
                                '</tr>' +
                                '<tr>' +
                                    '<th scope="row"><label for="' + prefix + 'code-content-' + i + '">Code Content</label></th>' +
                                    '<td>' +
                                        '<textarea id="' + prefix + 'code-content-' + i + '" name="' + prefix + 'code-content-' + i + '" rows="10" class="large-text code" placeholder="Enter your code here..." required></textarea>' +
                                    '</td>' +
                                '</tr>' +
                            '</table>' +
                        '</div>';
                    
                    container.append(template);
                }
            } else if (count < currentItems) {
                // Remove extra items
                for (var j = currentItems - 1; j >= count; j--) {
                    container.find('.code-language-item[data-index="' + j + '"]').remove();
                }
            }
        }
        
        // Form submission
        $('#code-display-box-form').on('submit', function(e) {
            e.preventDefault();
            
            var title = $('#code-display-box-title').val();
            var categoryId = $('#code-display-box-category').val();
            var postId = $('#code-display-box-post').val();
            var languagesCount = $('#code-display-box-languages-count').val();
            
            // Validate form
            if (!title || !categoryId || !postId || languagesCount < 1) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Collect code data
            var codeData = {};
            var isValid = true;
            
            for (var i = 0; i < languagesCount; i++) {
                var languageName = $('#code-language-name-' + i).val();
                var codeContent = $('#code-content-' + i).val();
                
                if (!languageName || !codeContent) {
                    alert('Please fill in all language fields.');
                    isValid = false;
                    break;
                }
                
                codeData[languageName] = codeContent;
            }
            
            if (!isValid) {
                return;
            }
            
            // Disable submit button
            var submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).text('Creating...');
            
            // Ajax request to save code display box
            $.ajax({
                url: codeDisplayBox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'code_display_box_save',
                    title: title,
                    category_id: categoryId,
                    post_id: postId,
                    languages_count: languagesCount,
                    code_data: codeData,
                    nonce: codeDisplayBox.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message with shortcode
                        $('#generated-shortcode').text(response.data.shortcode);
                        $('#code-display-box-shortcode-result').removeClass('hidden');
                        
                        // Reset form
                        $('#code-display-box-form')[0].reset();
                        $('#code-display-box-languages-container').html(
                            '<div class="code-language-item" data-index="0">' +
                                '<h3>Language Tab 1</h3>' +
                                '<table class="form-table">' +
                                    '<tr>' +
                                        '<th scope="row"><label for="code-language-name-0">Language Name</label></th>' +
                                        '<td>' +
                                            '<input type="text" id="code-language-name-0" name="code-language-name-0" class="regular-text" placeholder="e.g. JavaScript, PHP, Python" required>' +
                                        '</td>' +
                                    '</tr>' +
                                    '<tr>' +
                                        '<th scope="row"><label for="code-content-0">Code Content</label></th>' +
                                        '<td>' +
                                            '<textarea id="code-content-0" name="code-content-0" rows="10" class="large-text code" placeholder="Enter your code here..." required></textarea>' +
                                        '</td>' +
                                    '</tr>' +
                                '</table>' +
                            '</div>'
                        );
                        $('#code-display-box-post').html('<option value="">Select a post</option>').prop('disabled', true);
                        
                        // Scroll to result
                        $('html, body').animate({
                            scrollTop: $('#code-display-box-shortcode-result').offset().top - 50
                        }, 500);
                        
                        // Refresh page after 3 seconds to update the list
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while saving. Please try again.');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Create Code Display Box');
                }
            });
        });
        
        // Edit box form submission
        $('#code-display-box-edit-form').on('submit', function(e) {
            e.preventDefault();
            
            var id = $('#edit-box-id').val();
            var title = $('#edit-code-display-box-title').val();
            var categoryId = $('#edit-code-display-box-category').val();
            var postId = $('#edit-code-display-box-post').val();
            var languagesCount = $('#edit-code-display-box-languages-count').val();
            
            // Validate form
            if (!id || !title || !categoryId || !postId || languagesCount < 1) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Collect code data
            var codeData = {};
            var isValid = true;
            
            for (var i = 0; i < languagesCount; i++) {
                var languageName = $('#edit-code-language-name-' + i).val();
                var codeContent = $('#edit-code-content-' + i).val();
                
                if (!languageName || !codeContent) {
                    alert('Please fill in all language fields.');
                    isValid = false;
                    break;
                }
                
                codeData[languageName] = codeContent;
            }
            
            if (!isValid) {
                return;
            }
            
            // Disable submit button
            var submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).text('Updating...');
            
            // Ajax request to update code display box
            $.ajax({
                url: codeDisplayBox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'code_display_box_update',
                    id: id,
                    title: title,
                    category_id: categoryId,
                    post_id: postId,
                    languages_count: languagesCount,
                    code_data: codeData,
                    nonce: codeDisplayBox.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Code display box updated successfully!');
                        
                        // Refresh page to show updated list
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while updating. Please try again.');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Update Code Display Box');
                }
            });
        });
        
        // Cancel edit
        $('.cancel-edit').on('click', function(e) {
            e.preventDefault();
            $('.edit-tab').addClass('hidden');
            $('.code-display-box-tab-nav li:first-child a').trigger('click');
        });
        
        // Copy shortcode
        $(document).on('click', '.copy-shortcode', function() {
            var shortcode = $(this).data('shortcode') || $('#generated-shortcode').text();
            
            // Create temporary textarea
            var textarea = document.createElement('textarea');
            textarea.value = shortcode;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // Show success message
            var originalText = $(this).text();
            $(this).text('Copied!');
            
            setTimeout(function() {
                $('.copy-shortcode').text(originalText);
            }, 2000);
        });
        
        // Edit code box
        $(document).on('click', '.edit-code-box', function() {
            var id = $(this).data('id');
            
            // Show loading
            $(this).text('Loading...');
            
            // Ajax request to get code box data
            $.ajax({
                url: codeDisplayBox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'code_display_box_get',
                    id: id,
                    nonce: codeDisplayBox.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var boxData = response.data;
                        
                        // Fill the edit form
                        $('#edit-box-id').val(boxData.id);
                        $('#edit-code-display-box-title').val(boxData.title);
                        $('#edit-code-display-box-category').val(boxData.category_id);
                        $('#edit-code-display-box-languages-count').val(boxData.languages_count);
                        
                        // Clear language container
                        $('#edit-code-display-box-languages-container').empty();
                        
                        // Load posts for the selected category
                        $.ajax({
                            url: codeDisplayBox.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'code_display_box_get_posts_by_category',
                                category_id: boxData.category_id,
                                nonce: codeDisplayBox.nonce
                            },
                            success: function(postsResponse) {
                                if (postsResponse.success && postsResponse.data.length > 0) {
                                    var options = '<option value="">Select a post</option>';
                                    
                                    $.each(postsResponse.data, function(index, post) {
                                        var selected = post.id == boxData.post_id ? 'selected' : '';
                                        options += '<option value="' + post.id + '" ' + selected + '>' + post.title + '</option>';
                                    });
                                    
                                    $('#edit-code-display-box-post').html(options);
                                }
                                
                                // Add language tabs
                                var codeData = boxData.code_data;
                                var index = 0;
                                
                                $.each(codeData, function(language, code) {
                                    var template = 
                                        '<div class="code-language-item" data-index="' + index + '">' +
                                            '<h3>Language Tab ' + (index + 1) + '</h3>' +
                                            '<table class="form-table">' +
                                                '<tr>' +
                                                    '<th scope="row"><label for="edit-code-language-name-' + index + '">Language Name</label></th>' +
                                                    '<td>' +
                                                        '<input type="text" id="edit-code-language-name-' + index + '" name="edit-code-language-name-' + index + '" class="regular-text" value="' + language + '" placeholder="e.g. JavaScript, PHP, Python" required>' +
                                                    '</td>' +
                                                '</tr>' +
                                                '<tr>' +
                                                    '<th scope="row"><label for="edit-code-content-' + index + '">Code Content</label></th>' +
                                                    '<td>' +
                                                        '<textarea id="edit-code-content-' + index + '" name="edit-code-content-' + index + '" rows="10" class="large-text code" placeholder="Enter your code here..." required>' + code + '</textarea>' +
                                                    '</td>' +
                                                '</tr>' +
                                            '</table>' +
                                        '</div>';
                                    
                                    $('#edit-code-display-box-languages-container').append(template);
                                    index++;
                                });
                                
                                // Show edit tab
                                $('.edit-tab').removeClass('hidden');
                                $('.edit-tab a').trigger('click');
                            }
                        });
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while loading code box data. Please try again.');
                },
                complete: function() {
                    $('.edit-code-box').text('Edit');
                }
            });
        });
        
        // Delete code box
        $(document).on('click', '.delete-code-box', function() {
            if (!confirm('Are you sure you want to delete this code display box?')) {
                return;
            }
            
            var id = $(this).data('id');
            var row = $(this).closest('tr');
            
            $.ajax({
                url: codeDisplayBox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'code_display_box_delete',
                    id: id,
                    nonce: codeDisplayBox.nonce
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting. Please try again.');
                }
            });
        });
        
        // Filter by category in the list
        $('#filter-by-category').on('change', function() {
            var categoryId = $(this).val();
            var postSelect = $('#filter-by-post');
            
            if (categoryId) {
                // Filter the table rows
                $('.code-display-boxes-table tbody tr').hide();
                $('.code-display-boxes-table tbody tr[data-category="' + categoryId + '"]').show();
                
                // Load posts for this category
                $.ajax({
                    url: codeDisplayBox.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'code_display_box_get_posts_by_category',
                        category_id: categoryId,
                        nonce: codeDisplayBox.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            var options = '<option value="">Filter by Post</option>';
                            
                            $.each(response.data, function(index, post) {
                                options += '<option value="' + post.id + '">' + post.title + '</option>';
                            });
                            
                            postSelect.html(options);
                            postSelect.prop('disabled', false);
                        } else {
                            postSelect.html('<option value="">No posts found</option>');
                            postSelect.prop('disabled', true);
                        }
                    }
                });
            } else {
                // Show all rows
                $('.code-display-boxes-table tbody tr').show();
                postSelect.html('<option value="">Filter by Post</option>');
                postSelect.prop('disabled', true);
            }
        });
        
        // Filter by post in the list
        $('#filter-by-post').on('change', function() {
            var postId = $(this).val();
            var categoryId = $('#filter-by-category').val();
            
            if (postId) {
                // Filter the table rows
                $('.code-display-boxes-table tbody tr').hide();
                $('.code-display-boxes-table tbody tr[data-category="' + categoryId + '"][data-post="' + postId + '"]').show();
            } else if (categoryId) {
                // Show all rows for the selected category
                $('.code-display-boxes-table tbody tr').hide();
                $('.code-display-boxes-table tbody tr[data-category="' + categoryId + '"]').show();
            } else {
                // Show all rows
                $('.code-display-boxes-table tbody tr').show();
            }
        });
    });
})(jQuery);