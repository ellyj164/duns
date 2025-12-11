/**
 * Theme Toggle JavaScript
 * Handles dark/light mode switching with localStorage persistence
 */

(function() {
    'use strict';
    
    // Initialize theme from localStorage or system preference
    function initTheme() {
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.body.setAttribute('data-theme', savedTheme);
        } else if (systemPrefersDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.body.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            document.body.setAttribute('data-theme', 'light');
        }
        
        updateToggleButton();
    }
    
    // Toggle theme
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        document.body.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        updateToggleButton();
        
        // Save to user preferences via AJAX if user is logged in
        saveThemePreference(newTheme);
    }
    
    // Update toggle button appearance
    function updateToggleButton() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const toggleButton = document.getElementById('theme-toggle');
        
        if (toggleButton) {
            toggleButton.setAttribute('aria-label', 
                currentTheme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'
            );
            toggleButton.setAttribute('title', 
                currentTheme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'
            );
        }
    }
    
    // Save theme preference to server
    function saveThemePreference(theme) {
        // Check if user is logged in
        if (typeof $ !== 'undefined' && window.location.pathname !== '/login.php') {
            $.ajax({
                url: 'api/v1/auth/save_preference.php',
                method: 'POST',
                data: {
                    preference: 'theme',
                    value: theme
                },
                dataType: 'json',
                success: function(response) {
                    // Preference saved successfully
                },
                error: function() {
                    // Silent fail - theme is still saved in localStorage
                }
            });
        }
    }
    
    // Listen for system theme changes
    function watchSystemTheme() {
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        darkModeQuery.addEventListener('change', function(e) {
            // Only apply system theme if user hasn't set a preference
            if (!localStorage.getItem('theme')) {
                const newTheme = e.matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', newTheme);
                document.body.setAttribute('data-theme', newTheme);
                updateToggleButton();
            }
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            watchSystemTheme();
            
            // Add event listener to toggle button
            const toggleButton = document.getElementById('theme-toggle');
            if (toggleButton) {
                toggleButton.addEventListener('click', toggleTheme);
            }
        });
    } else {
        initTheme();
        watchSystemTheme();
        
        // Add event listener to toggle button
        const toggleButton = document.getElementById('theme-toggle');
        if (toggleButton) {
            toggleButton.addEventListener('click', toggleTheme);
        }
    }
    
    // Expose toggle function globally for inline usage
    window.toggleTheme = toggleTheme;
})();
