CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Google Calendar Import provides a way to import Google Calendar events
from a publicly available calendar into Drupal.

Once into Drupal, you can layer on additional fields, theming, access
control, and all the other things that make Drupal Entities so excellent
to work with.

 * More information is available at the project page:
   https://www.drupal.org/project/google_calendar

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/google_calendar

The module creates two new entity types - one for Calendars as a whole,
and one for Events (entries) in those Calendars. Each Calendar has a
name which can be, but does not have to be, the name of the calendar in
the Google web view.

To import calendar entries, the appropriate authentication process must
be completed with Google, and then you can select (Import) from the
calendar edit screen to initiate an import. Importing is also carried
out as a system "cron" task.

Calendar Events are made available via the normal Entity data patterns,
including as a source for Views.

REQUIREMENTS
------------

 * This module requires the google_secrets module to manage storage of
   google API secrets.

 * The composer.json file defines other packages required for use,
   notably the google/apiclient package that provides the Google_Client
   class. If you install this module using composer, these will be
   installed as well, otherwise you must install them yourself.


INSTALLATION
------------

 * Install the Google Calendar Import module as you would normally
   install a contributed Drupal module.
   Visit https://www.drupal.org/node/1897420 for further information.

 * You must generate and download the "credentials.json" file from the
   Google API developer pages before you can start importing calendar
   entries from Google (although you can set up the module without it).
   Visit the Settings page to upload this file.


CONFIGURATION
-------------

1. Navigate to Administration > Extend and enable the module.

2. Navigate to admin/config/google_calendar/settings to configure
   the module settings. (Configuration > Google Calendar > Settings)

3. Navigate to admin/config/google_calendar/wizard/1 to configure the
   Google Calendar API for access by Drupal. (Configuration > Google
   Calendar > Wizard)

4. Navigate to admin/config/google_calendar/calendars/import to inspect
   the list of calendars visible via the Google Service account and
   import those accounts you wish to see.

5. View calendars currently configured at admin/content/google_calendar.


NOTABLE CHANGES from 1.0
------------------------

New features include:

 * A verbose configuration wizard for the Google API and local settings;

 * List calendars visible from Google and enable auto-import of them;

 * Additional drush commands to call the Google API and display calendars
   and events, delete events for one or all calendars, and also to do Google
   synchronize on just the calendars, to avoid running global cron on the
   whole site very frequently;

 * Additional fields imported into the Calendar entity, and also
   timestamps for sync progress;

 * Additional fields imported into the Event entity;

 * A container-service and Interface to manage Google credential storage,
   with managed and static file plugins - now in module "google_secrets";

 * Statistics collected and recorded in the logs, to simplify operation
   checks.

 * Expose as config options the timespan covered by the calendar,
   disposition of old events, minimum intervals for resync, and more;

 * Proper Incremental resync;

 * Configurable past and future horizons, defining the period for which
   events are wanted as relative dates. Events outside this period can
   be deleted, unpublished, or just ignored, on a per-calendar basis.


THINGS NOT SUPPORTED
--------------------

 * "Infinite" future view of calendar: repeating events are imported as
   their expanded, concrete versions to avoid any possibility of the
   local repat algorithm differing from Google's. This means there is a
   future horizon beyond which no events are visible. This is not a bug.


KNOWN ISSUES
------------

 * Handling multiple calendar accounts, and managing private calendars,
   require a significant amount of additional work and so neither option
   is supported.
   See https://www.drupal.org/project/google_calendar/issues/3082332

 * There are currently no useful tests, because almost everything requires
   a live Google API connection, and mocking up something for this is not
   easy. Assistance with this is sought.
   See https://www.drupal.org/project/google_calendar/issues/3089100

 * There may, in some cases, be problems importing very large numbers of
   events (many thousands) over many calendars, because the current logic
   checks for events that can be deleted by loading all events. It is
   not simple to avoid doing this without breaking the Entity API, but
   solutions to this are being sought.
   See https://www.drupal.org/project/google_calendar/issues/3110585


MAINTAINERS
-----------

 * Ruth Ivimey-Cook (rivimey) - https://www.drupal.org/u/rivimey
 * Ariel Barreiro (hanoii) - https://www.drupal.org/u/hanoii

CREATOR
-------

 * Drew Trafton (dtraft) - https://www.drupal.org/u/dtraft
