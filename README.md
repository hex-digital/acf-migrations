# acf-migrations

An easy way to migrate local fields and field groups using readable object oriented syntax (without the need for huge arrays).

By converting 113 lines to just 20 by using this easy-to-use class, you can dramatically reduce your development time and increase your workflow.

![Screenshot](https://cloud.githubusercontent.com/assets/9773040/13950509/495e53dc-f023-11e5-81cc-2ef836cf65fd.png)

### Set up

Simply create a directory in your theme called `acf` and add a `migrations.php` file containing the `example.php.dist` code. Once activated, the plugin will automatically generate the local Advanced Custom Fields code to be added to your project.

Be sure to add `acf/export.php` to your `.gitignore` file since this will not need to be added if you can run the generated executable upon deployment of your website.

After installing the plugin and successfully generating your fields, add the following code to your `functions.php` file:

    $acf_export_file = __DIR__ . '/acf/export.php';
    if ( file_exists( $acf_export_file ) ) include $acf_export_file;

### The executable

For deployments, it makes sense to have a single executable file that can run independently. To download this executable upon deployment, simply use curl:

    curl -o acf-migrations.phar https://raw.githubusercontent.com/hex-digital/acf-migrations/master/acf-migrations.phar

You can then use the acf-migrations.phar (much like [Composer](https://getcomposer.org)) to generate your Advanced Custom Field code:

    php acf-migrations.phar -t /var/www/vhosts/example.com/wp-content/themes/my-theme

Where the `-t` flag is the template directory of your WordPress website.

### How to use

#### Prerequisite - ACF Playground Process

Prior to writing ACF migrations, it's advised to gain some knowledge in what the export file looks like. To do this, have a play around with creating fields in the Custom Fields -> Field Groups section like normal. Once you have created several example ACF configurations, please head to Custom Fields -> Tools, check the example field groups then click Generate PHP. Please make key observations of the different array items that make up each type of field as this will come handy when creating complex field group configurations.

This playground process will help you define well defined custom fields via the migrations file.

#### Adding a field group

Now you have a general idea of what array items make up a field configuration, let's look at adding our first field.
Head over to `acf/migrations.php` and see if any existing fields exist. If so, great! You can use them as a template for adding further fields. If not, no worries, let's add one.

You'll first notice the `Migrations` object has been instanced (`$migrations`). To add a field group, add the following.

```php
$migrations->addFieldGroup('Our staff', [
    [
        'param' => 'post_type',
        'operator' => '==',
        'value' => 'page',
    ],
], [
    'hide_on_screen' => ['content_editor']
])
```

There are a few things to note here. Firstly, the key `Our staff` is simply the name of the field group. Secondaly, you'll
notice the array of page type binding arrays. In this example, the field group will display on any `page` post type. If you
want the field group to show on multiple post types, add another binding array to the configurations like so.

```php
    [
        'param' => 'post_type',
        'operator' => '==',
        'value' => 'page',
    ],
    [
        'param' => 'post_type',
        'operator' => '==',
        'value' => 'staff',
    ],
```
~Note: You can bind field groups to custom post types.~

#### Adding a field

Now that we have a field group, we want to add fields. To see what types of fields you can add, please refer to the [field types](https://www.advancedcustomfields.com/resources/#field-types) documentation. To create a field, add the following shortly after the newly created field group.

```php
    ->addField('text', 'Full name')
```

This will create a simple text field with the label `Full name`. This name is used to create the key of the field which is required when
trying to display the fields content on within templates or method code. The key generated is a snake case key so our example would become `full_name`.

If you want to find a the accepted field type values, you can again refer to generated export code via the playground process.

Note: the example above will apply the default configurations values for a text field. If you would like to add extra configurations to the field, please add like so

```php
    ->addField('text', 'Full name', [
        'instructions' => 'Add a full name for the staff member.',
    ])
```

There are many extra configurations that can be added to fields so please play around in the export playground as stated in the prerequisite to find the key => value pairs for these settings. The example below shows a well configured image field.

```php
    ->addField(
        'image',
        'Thumbnail',
        [
            'instructions' => 'Please add an image. Max width: 300px',
            'return_format' => 'array',
            'preview_size' => 'thumbnail',
            'min_width' => 50,
            'min_height' => 50,
            'min_size' => '',
            'max_width' => 300,
            'max_height' => 300,
            'max_size' => '0.5',
            'mime_types' => 'jpg, jpeg',
        ]
    )
```

#### Repeater fields & Sub fields

It is common to have a field that contains sub-fields which can be repeated. This is known as [repeater fields](https://www.advancedcustomfields.com/resources/repeater/). To create this type of field, add a repeater type field like so

```php
    ->addField(
        'repeater',
        'Award Items',
        [
            'layout' => 'block',
            'instructions' => 'Please add award items',
            'button_label' => 'Add Item',
        ]
    )
```

Now we have the repeater field setup, we can add sub-fields using the `addSubField()` method.

```php
        ->addSubField(
            'text',
            'Award Title',
            0,
            [
                'instructions' => 'Add a title for the award.',
            ]
        )
```

Notice that this method accepts the same type, name and configuration array parameters as the `addField` method. The only difference being the `depth` parameter which is an integer outlining the depth of the sub-field. In our example, the sub-field is at the first level under the main fields so the depth is set to 0. If, for example, you nest another repeater within, you will need to increase the depth of it's repsective sub-field parameters accordingly.

#### Flexible layouts

Beside field groups, field and sub-fields, we can also add [flexible layouts](https://www.advancedcustomfields.com/resources/flexible-content/) which provide a simple, structured, block-based editor. To add flexible content first add a `flexible_content` field.

```php
->addField(
    'flexible_content',
    'Content Blocks',
    [
        'button_label' => 'Add Block',
    ]
)
```

Now we have added the base field, we now need to define layout types. Do so by adding a `addLayout` method.

```php
    ->addLayout(
        'media_block',
        'Media Block'
    )
```

Notice that unlike `addField` or `addSubField` methods, the key and name parameter are seperate in this method.
Finally, we can now add fields to the layout by using the sub-field method.

```php
    ->addSubField(
        'text',
        'Title',
        0
    )
    ->addSubField(
        'image',
        'Media Item',
        0
    )
```

#### How to use guidance

As long as your syntax isn't breaking the export file from being created, and you know how to use these `addFieldGroup`, `addField`, `addSubField` and `addLayout` methods correctly, you should be able to create well validated and easy-to-use custom fields. If at anytime you are struggling to add any configurated fields, please play around with the ACF Playground Process.
