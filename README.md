# acf-migrations

An easy way to migrate local fields and field groups using readable object oriented syntax (without the need for huge arrays).

By converting 113 lines to just 20 by using this easy-to-use class, you can dramatically reduce your development time and increase your workflow.

![Screenshot](https://cloud.githubusercontent.com/assets/9773040/13950509/495e53dc-f023-11e5-81cc-2ef836cf65fd.png)

Simply create a diretory in your theme called `acf` and add a `migrations.php` file containing the `example.php.dist` code. Once activated, the plugin will automatically generate the local Advanced Custom Fields code to be added to your project.

Be sure to add `acf/export.php` to your `.gitignore` file since this will not need to be added if you can run the generated executable upon deployment of your website.

After installing the plugin and successfully generating your fields, add the following code to your `functions.php` file:

    $acf_export_file = __DIR__ . '/acf/export.php';
    if ( file_exists( $acf_export_file ) ) include $acf_export_file;

### Unsupported fields

The only unsupported field types are those which support sub fields but are nested under another subfield. For example, this plugin cannot generate the export code for a repeater field which is nested under another repeater field.
