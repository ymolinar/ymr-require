YMR Require
===========

YMR Require is a Wordpress Plugin in charge of find in all the available plugins of a wordpress project and search for certain requirements files with the plugins requirements in a JSON format.
The content of the requirement file is a JSON format like this:
```json
{
	"a-plugin/file.php": "version",
	...,
	"another-plugin/file.php": "version"
}
```
The version represents the minimal installed and active version of the plugin required. The version can be set in a valid PHP version format like:
> **Available version format:**

> - 1.1.1
> - 1.1.*
> - 1.*
> *

Requirements File
------------------------
We search for specific requirements file names in the root of the plugin
> **Available version format:**

> - requirements
> - require
> - depends_on

Unsatisfied Requirements
----------------------------------
When the plugin detects an unsatisfied requirement for a plugin he disable the plugin's activate link and show the name of the unsatisfied plugins and versions
