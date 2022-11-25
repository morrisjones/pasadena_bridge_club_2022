/**
 * @file
 * This file is to add any custom js for the drupal8 w3css subtheme.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.pbcLeftSidebarExpand = {
      // Perform jQuery as normal in here.
    attach: function(context,settings) {
      // Show or hide the left sidebar
      $(context)
        .find('#show-hide-left-sidebar')
        .once('left-sidebar-more-less')
        .on(
          'click', function () {
            let leftSidebar = $("#left-sidebar");
            let showHideLeftSidebar = $("#show-hide-left-sidebar");
            if (leftSidebar.is(':hidden')) {
              leftSidebar.show(480);
              showHideLeftSidebar
                .addClass('left-sidebar-nav-displayed')
                .removeClass('left-sidebar-nav');
            }
            else {
              leftSidebar.hide(480);
              showHideLeftSidebar
                .addClass('left-sidebar-nav')
                .removeClass('left-sidebar-nav-displayed');
            }
          }
        );
    }
  };

  Drupal.behaviors.pbcExperimental = {
    attach: function(context,settings) {
      ;
    }
  };

})(jQuery);
