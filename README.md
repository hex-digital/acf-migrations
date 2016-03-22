# acf-migrations

An easy way to migrate local fields and field groups using readable object oriented syntax (without the need for huge arrays).

By converting 113 lines to just 20 by using this easy-to-use class, you can dramatically reduce your development time and increase your workflow.

![Screenshot](https://cloud.githubusercontent.com/assets/9773040/13950509/495e53dc-f023-11e5-81cc-2ef836cf65fd.png)

Simply create a diretory in your theme called `acf` and add a `migrations.php` file containing the `example.php.dist` code. Once activated, the plugin will automatically generate the local Advanced Custom Fields code to be added to your project.

Be sure to add `acf/export.php` to your `.gitignore` file since this will not need to be added if you can run the generated executable upon deployment of your website.

### Unsupported fields

The following list shows current unsupported fields which will be added in a future release.

* Repeater
* Flexible
