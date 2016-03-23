<?php

class Migrations
{
    protected $fields;
    protected $fieldGroups;
    protected $fieldGroupCache;

    const STORAGE_DIRECTORY = 'acf';
    const ACF_PLUGIN_LOCATION = 'advanced-custom-fields-pro/acf.php';
    const ACF_FIELDS_LOCATION = 'advanced-custom-fields-pro/fields';

    public function __construct()
    {
        if ( ! $this->localFieldGroupsEnabled() ) {
            throw new \Exception( 'Local field groups are not enabled' );
        }
    }

    /**
     * Check to see if the Advanced Custom Fields local group option is
     * available to use
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return boolean
     */
    private function localFieldGroupsEnabled()
    {
        return function_exists( 'acf_add_local_field_group' );
    }

    /**
     * Sanitise the label for the field array
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $label The field label
     * @return string
     */
    private function sanitiseLabel( $label )
    {
        $label = str_replace( ['-', '_'], ' ', $label );
        $label = ucwords( $label );
        return $label;
    }

    /**
     * Sanitise the name for the field array
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $name The field name
     * @return string
     */
    private function sanitiseName( $name )
    {
        $name = strtolower( $name );
        $name = str_replace( [' ', '-'], '_', $name );
        return $name;
    }

    /**
     * Returns the default field values from the ACF Pro plugin classes
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $type The field type
     * @return array
     */
    public function getDefaults( $type )
    {
        $type = $this->sanitiseName( $type );
        $class = 'acf_field_' . $type;
        $acf_file = WP_PLUGIN_DIR . '/' . self::ACF_FIELDS_LOCATION . '/' . $type . '.php';

        if ( file_exists( $acf_file ) && ! class_exists( $acf_file ) ) {
            include_once $acf_file;
        }

        $field = new $class;
        if ( isset( $field->defaults ) ) {
            return $field->defaults;
        }

        return [];
    }

    /**
     * Creates the array syntax for a custom field
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $name The name of the field
     * @param  string $type The field type
     * @param  array $options The field type
     * @return array
     */
    public function addField( $type, $name, $options = false )
    {
        $defaults = $this->getDefaults( $type );

        // Add fields data to field
        $fields = [
            'key' => 'field_' . uniqid(),
            'label' => $this->sanitiseLabel( $name ),
            'name' => $this->sanitiseName( $name ),
            'type' => $this->sanitiseName( $type ),
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ]
        ];

        // Add class defaults to fields array
        $fields += $defaults;

        // Check to see if options have been defined
        if ( $options && is_array( $options ) ) {

            // Validate options
            $options = $this->validate( ['key', 'label', 'name', 'type'], $options );

            // Replace any options passed into the method
            foreach ( $options as $option_key => $option_value ) {

                // Check to see if the option is available
                if ( ! isset( $fields[ $option_key ] ) ) {
                    continue;
                }

                $fields[ $option_key ] = $option_value;
            }

        }

        // Add defined field type array with values to memory
        $this->fields[] = $fields;

        // Return Migrations object
        return $this;
    }

    /**
     * Creates the array for a field group
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param string $name     The name of the field group
     * @param array $locations The locations array
     * @param array $options   The options array
     * @return class           The migrations class
     */
    public function addFieldGroup( $name, $locations = [], $options = false )
    {
        // Add cached field group data to memory
        $this->appendFieldGroupFromCache();

        // Add field groups data to field group
        $fieldGroup = [
            'key' => 'group_' . uniqid(),
            'title' => $this->sanitiseLabel( $name ),
            'fields' => [
                $this->fields
            ],
            'location' => [
                $locations
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => 1,
            'description' => '',
        ];

        // Check to see if options have been defined
        if ( $options && is_array( $options ) ) {

            // Validate options
            $options = $this->validate( ['key', 'title', 'fields', 'location'], $options);

            // Replace any options passed into the method
            foreach ( $options as $option_key => $option_value ) {

                // Check to see if the option is available
                if ( ! isset( $fieldGroup[ $option_key ] ) ) {
                    continue;
                }

                $fieldGroup[ $option_key ] = $option_value;
            }

        }

        // Cache defined field group array
        $this->fieldGroupCache = $fieldGroup;

        return $this;
    }

    /**
     * Appends the cached field group array to the field groups property
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return boolean
     */
    public function appendFieldGroupFromCache()
    {
        if ( $this->fieldGroupCache !== null ) {
            $this->fieldGroupCache['fields'] = $this->fields;
            $this->fieldGroups[] = $this->fieldGroupCache;
            $this->fieldGroupCache = null;
            return true;
        }

        return false;
    }

    /**
     * Validates option keys and removes them from the array
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string|array $key The key to remove
     * @param  array $array      The options array
     * @return array
     */
    public function validate($key, $array)
    {
        if ( is_string( $key ) ) {
            if ( isset( $array[$key] ) ) {
                unset( $array[$key] );
            }
        }

        if ( is_array( $key ) ) {
            foreach ( $key as $option ) {
                $array = $this->validate( $option, $array );
            }
        }

        return $array;
    }

    /**
     * Adds the field groups to Advanced Custom Fields. This can be used during
     * development to stop having to re-run the migration executable.
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return void
     */
    public function addFieldGroups()
    {
        // Finish final array for the last $fieldGroup
        $this->appendFieldGroupFromCache();

        foreach ( $this->fieldGroups as $fieldGroup ) {
            acf_add_local_field_group( $fieldGroup );
        }
    }

    /**
     * Similar to var_export but supports PHP 5.4 array syntax
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  mixed $var     The variable to parse as text
     * @param  string $indent The text indentation to use in the output
     * @return string
     */
    public function export( $var, $indent = '' )
    {
        switch ( gettype( $var ) ) {
            case 'string':
                return '"' . addcslashes( $var, "\\\$\"\r\n\t\v\f" ) . '"';
            case 'array':
                $indexed = array_keys( $var ) === range( 0, count( $var ) - 1 );
                $r = [];
                foreach ( $var as $key => $value ) {
                    $r[] = "$indent    "
                         . ( $indexed ? "" : $this->export( $key ) . " => " )
                         . $this->export( $value, "$indent    " );
                }
                return "[\n" . implode( ",\n", $r ) . "\n" . $indent . "]";
            case 'boolean':
                return $var ? 'true' : 'false';
            default:
                return var_export( $var, true );
        }
    }

    /**
     * Generates and  the generated code to be cached as a PHP array
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return array
     */
    public function generate()
    {
        // Finish final array for the last $fieldGroup
        $this->appendFieldGroupFromCache();

        // Declare $data variable as blank string
        $data = '';

        // Wrap acf_add_local_field_group() to each field group array
        foreach ( $this->fieldGroups as $fieldGroup ) {
            $data .= '    acf_add_local_field_group( ' . $this->export( $this->fieldGroups, '    ' ) . " );\n\n";
        }

        // Remove additional line breaks
        $data = rtrim( $data );

        // Add function to use in hook
        $data = "function acf_migrations_add_local_field_groups() {\n\n" . $data;
        $data .= "\n\n}\n\nadd_action( 'acf/init', 'acf_migrations_add_local_field_groups' );";

        // Add PHP opening tag and end with line break
        $data = "<?php\n\n" . $data . "\n";

        return file_put_contents( get_template_directory() . '/' . self::STORAGE_DIRECTORY . '/export.php', $data );
    }
}
