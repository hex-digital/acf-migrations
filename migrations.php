<?php

class Migrations
{
    protected $fields;
    protected $fieldGroups;
    protected $fieldGroupCache;

    const STORAGE_DIRECTORY = 'acf';

    const FIELD_PREFIX = 'field_';
    const FIELD_GROUP_PREFIX = 'group_';

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
     * Sanitise the key for the field array
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $name The field name
     * @return string
     */
    private function sanitiseKey( $name )
    {
        $name = $this->sanitiseName( $name );
        return $name;
    }

    /**
     * Create the field key which is prefixed by the field group key and
     * seperated with a double underscore.
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $name The field name
     * @return string
     */
    private function getFieldKey( $name ) {
        $fieldGroupKey = substr( $this->fieldGroupCache['key'], strlen( self::FIELD_GROUP_PREFIX ) );
        return $this->sanitiseKey( $fieldGroupKey . '__' . $name );
    }

    /**
     * Creates the array syntax for a custom field
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $name The name of the field
     * @param  string $type The field type
     * @param  array $options The field type
     * @param  string $key The field key
     * @return array
     */
    public function addField( $type, $name, $options = false, $key = false )
    {
        if ( ! $key ) {
            $key = $this->getFieldKey( $name );
        }

        // Add fields data to field
        $fields = [
            'key' => self::FIELD_PREFIX . $key,
            'label' => $this->sanitiseLabel( $name ),
            'name' => $this->sanitiseName( $name ),
            'type' => $this->sanitiseName( $type )
        ];

        // Check to see if options have been defined
        if ( $options && is_array( $options ) ) {

            // Validate options
            $options = $this->validate( ['label', 'name', 'type'], $options );

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
    public function addFieldGroup( $name, $locations = [], $options = false, $key = false )
    {
        // Add cached field group data to memory
        $this->appendFieldGroupFromCache();

        if ( ! $key ) {
            $key = $this->sanitiseKey( $name );
        }

        // Add field groups data to field group
        $fieldGroup = [
            'key' => self::FIELD_GROUP_PREFIX . $key,
            'title' => $this->sanitiseLabel( $name ),
            'fields' => [
                $this->fields
            ],
            'location' => [
                $locations
            ],
            'options' => [
                'position' => 'normal',
                'hide_on_screen' => ''
            ],
            'menu_order' => 0
        ];

        // Check to see if options have been defined
        if ( $options && is_array( $options ) ) {

            // Validate options
            $options = $this->validate( ['title', 'fields', 'location'], $options);

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
     * @todo   Change double quotes to single quotes for values
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

        // Declare the indentation variable (4 spaces)
        $indentation = '    ';

        // Declare $data variable as blank string
        $data = '';

        // Wrap acf_add_local_field_group() to each field group array
        foreach ( $this->fieldGroups as $fieldGroup ) {
            $data .= $indentation . 'acf_add_local_field_group( ' . $this->export( $fieldGroup, $indentation ) . " );\n\n";
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
