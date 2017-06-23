YMR Require
===========

YMR Require is a Wordpress Plugin in charge of find in all the available plugins of a wordpress project and search for certain requirements files with the plugins requirements in a JSON format.

For example we are developing a new Wordpress plugin A that depends that the akismet plugin is active. In the root folder of the plugin A we define a requirements file with a JSON format defining the required plugins and versions of the plugin A

The content of the requirement file is a JSON format like this:
```json
{
	"a-plugin/file.php": "version",
	"another-plugin/file.php": "version"
}
```
For the above example the requirements file looks like:

```json
{
	"akismet/akismet.php": "3.*"
}
```
In that file we define that the plugin A depends on the akismet plugin any version above 3.x

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
