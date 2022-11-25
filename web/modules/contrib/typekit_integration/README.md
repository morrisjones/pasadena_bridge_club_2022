Typekit Integration (Adobe Fonts)
================

The Typekit Integration module is a lightweight module
to integrate Adobe Fonts with Drupal.

It comes with a simple configuration page to add your Adobe Font Project ID
and outputs the required code to use Adobe Fonts on your site.

Installation
============

The easiest way to install this module is to use composer.
  
    composer require drupal/typekit_integration

Enable the module as you would any other Drupal module.

Configuration
=============

Follow these instuctions to setup your Adobe Fonts and configure your module

**Adobe Fonts Setup**
- Login or create an account at typekit.com or fonts.adobe.com
- Setup a new Web Project.
  - Projects are groups of fonts that will be packaged and distributed over a CDN.
  - A Project lets you configure the fonts, selections, and other settings
    that Typekit/Adobe Fonts will apply to your web pages.
- You will need to add a project name.
- Once your project is saved, it should give you a Project ID.
  - This will be a 7 digit code that will be entered into the module configuration page.

**Module Setup**
- Enable the Typekit Integration module
- Navigate to: /admin/config/services/typekit
- Enter the Project ID from your Web Project