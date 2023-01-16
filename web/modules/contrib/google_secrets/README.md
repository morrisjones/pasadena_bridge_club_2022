# Google Secrets API

This is a Drupal 8 module to manage storage of Google Secrets files,
which are used by some applications that use the Google API.

The module defines a new plugin type, GoogleSecretsStore, and two
initial implementations of the plugin, which store the secrets using
a static file 'somewhere' on the filesystem, or a private drupal-
managed file. The intent of the module is to enable site-designers
to use other, potentially more secure or more flexible methods of
storing this data.

You do not need this module unless a module using it depends on it.


## Enable

-  Download and enable the module

There is no configuration form for the module as a whole. Plugins
can define their own configuration snippets which can be used by
other modules' in their configuration forms.


## Plugin: Static File

The static file plugin has a single configuration parameter, a file
path name. If the name is not an absolute path then it is relative
to the Drupal Root folder (contains index.php). Such a relative path
can use "../" to reference directories outside of root.
 

## Plugin: Managed File

The managed file plugin has a single configuration parameter, a file
id, which must be the ID of a file in the "private:" filesystem.


# Developer

The plugin can be implemented in the Plugin/GoogleSecretsStore namespace
of a Drupal module, must be annotated with @GoogleSecretsStore(...)
and implement GoogleSecretsStoreInterface.

The documentation for the interface should be sufficient, however these
notes might help.

## Core

The plugin name and description are defined in the annotation and can
be retrieved from the GoogleSecretsStore base class functions for
plugins derived from that.

The critical functions of the plugin are:

    getFilePath()
    get()

which return the filename of the secrets file, and the content of that
file, respectively. The filename can be passed to Google_Client API
function setAuthConfig(). The content is not needed for authorisation,
but might be needed for other purposes.

A plugin can chose not to implement getFilePath(), for example because
the data secrets are not stored in a file, and to create a file for this
purpose is considered insecure. In this case that function should return
null, and must implement get() and return from that an appropriate JSON dictionary which a client can use
to initialise a Google_Client manually. Client programs do not have to
accept manual client configuration, so this should only be used as a
last resort.

## UI

The functions:

    render()
    buildForm()
    submitForm()
    instructions()

exist to support configuration of the plugin using Drupal Forms API. 
buildForm() should return a form-api snippet sufficient to configure
the plugin, typically consisting of one or more text fields.
submitForm() will receive the FormState and must extract the needed
information from it (such as the filename). Form element names should be
prefixed with the plugin name.

The render() function exists for those cases that the plugin client
wishes to show the current value of the plugin configuration. It should
not display any secrets.
