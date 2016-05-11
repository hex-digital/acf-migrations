<?php

class Migrations
{
    protected $fields;
    protected $layouts;
    protected $subFields;
    protected $fieldGroups;

    protected $fieldsCache;
    protected $layoutsCache;
    protected $fieldGroupCache;

    protected $fieldKeys;

    const STORAGE_DIRECTORY = 'acf';

    const FIELD_DELIMITER = '__';
    const FIELD_GROUP_PREFIX = 'group_';
    const FIELD_PREFIX = 'field_';

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
    private function getFieldKey( $name )
    {
        $fieldGroupKey = substr( $this->fieldGroupCache['key'], strlen( self::FIELD_GROUP_PREFIX ) );
        return $this->sanitiseKey( $fieldGroupKey . self::FIELD_DELIMITER . $name );
    }

    private function getHashedFieldKey( $name )
    {
        $fieldKey = substr( md5( $name ), 0, 7 );

        if ($this->fieldKeys === null) {
            $this->fieldKeys = [];
        }

        if ( in_array( $fieldKey, $this->fieldKeys ) ) {
            throw new \Exception( 'Duplicate MD5 hash found when generating field keys' );
        }

        $this->fieldKeys[] = $fieldKey;

        return $fieldKey;
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
        // If no key is defined, generate it from the name and parents
        if ( ! $key ) {
            $key = $this->getFieldKey( $name );
            $key = $this->getHashedFieldKey( $key );
        }

        // Add fields data to field
        $field = [
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
            $field = $this->replace( $field, $options );

        }

        // Add defined field type array with values to memory
        $this->fields[] = $field;

        // Return Migrations object
        return $this;
    }

    /**
     * Creates the array syntax for subfields inside a field array
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $name The file name of the layout
     * @param  array $label The label of the layout
     * @param  array $display The display type of the layout
     * @param  array $options The field type
     * @param  string $key The field key
     * @return array
     */
    public function addLayout( $name, $label, $display = false, $options = false, $key = false )
    {
        $parentField = end( $this->fields );

        // Check if the last field can support layouts
        if ( ! $this->checkLayoutSupport( $parentField['type'] ) ) {
            return $this;
        }

        // If no key is defined, generate it from the name and parents
        if ( ! $key ) {
            $key = $parentField['key'] . self::FIELD_DELIMITER . $this->sanitiseKey($name);
            $key = substr( $key, strlen( self::FIELD_PREFIX ) );
            $key = $this->getHashedFieldKey( $key );
        }

        // Add layout data to field
        $layout = [
            'key' => $key,
            'name' => $this->sanitiseName( $name ),
            'label' => $this->sanitiseLabel( $name ),
            'display' => $this->validateDisplay( $display ),
            'sub_fields' => []
        ];

        // Validate options
        $options = $this->validate( ['label', 'name', 'type'], $options );

        // Replace any options passed into the method
        $layout = $this->replace( $layout, $options );

        // Add defined layout array with values to memory
        $this->fields[ ( count( $this->fields ) - 1 ) ]['layouts'][] = $layout;

        // Return Migrations object
        return $this;
    }

    /**
     * Creates the array syntax for subfields inside a field array
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $name The name of the field
     * @param  array $options The field type
     * @param  string $key The field key
     * @return array
     */
    public function addSubField( $type, $name, $options = false, $key = false )
    {
        $parentField = end( $this->fields );
        $parentLayoutKey = $parentField['key'];

        if ( isset( end( $this->fields )['layouts'] ) ) {
            $parentLayoutKey = end( end( $this->fields )['layouts'] )['key'];
        }

        // Check if the last field can support subfields
        if ( ! $this->checkSubFieldSupport( $parentField['type'] ) ) {
            return $this;
        }

        // If no key is defined, generate it from the name and parents
        if ( ! $key ) {
            $key = $parentLayoutKey . self::FIELD_DELIMITER . $this->sanitiseKey($name);
            $key = substr( $key, strlen( self::FIELD_PREFIX ) );
            $key = $this->getHashedFieldKey( $key );
        }

        // Add sub field data to field
        $subField = [
            'key' => self::FIELD_PREFIX . $key,
            'label' => $this->sanitiseLabel( $name ),
            'name' => $this->sanitiseName( $name ),
            'type' => $this->sanitiseName( $type ),
        ];

        // Validate options
        $options = $this->validate( ['label', 'name', 'type'], $options );

        // Replace any options passed into the method
        $subField = $this->replace( $subField, $options );

        // Check if last field requires the data to go inside the layout
        if ( isset( $this->fields[ ( count( $this->fields ) - 1 ) ]['layouts'] ) ) {
            // Add defined sub field array with values to the last field's memory (to the layout)
            $this->fields[ ( count( $this->fields ) - 1 ) ]['layouts'][( count( $this->fields[ ( count( $this->fields ) - 1 ) ]['layouts'] ) - 1 )]['sub_fields'][] = $subField;

        } else {
            // Add defined sub field array with values to the last field's memory
            $this->fields[ ( count( $this->fields ) - 1 ) ]['sub_fields'][] = $subField;
        }

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
            $key = $this->getHashedFieldKey( $key );
        }

        // Allow shorter location syntax
        if ( isset( $locations[0] ) && ! is_array( $locations[0] ) ) {
            $locationParts = $locations;
            $locations = [
                [
                    "param" => $locationParts[0],
                    "operator" => $locationParts[1],
                    "value" => $locationParts[2]
                ]
            ];
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
            $fieldGroup = $this->replace( $fieldGroup, $options );

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
     * Replaces any options from the given field variable
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  array $fields  The field array
     * @param  array $options Any defined options
     * @return array          The amended field array
     */
    public function replace( $fields, $options )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option_key => $option_value ) {

                // Check to see if the option is available
                if ( ! isset( $fields[ $option_key ] ) ) {
                    continue;
                }

                $fields[ $option_key ] = $option_value;
            }
        }

        return $fields;
    }

    /**
     * Validates the correct display type
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $display The display type input
     * @return string          The valid display type
     */
    public function validateDisplay( $display = false )
    {
        $display = $this->sanitiseKey( $display );

        if ( ! in_array( $display, [ 'table', 'block', 'row' ] ) ) {
            return 'block';
        }

        return $display;
    }

    /**
     * Checks for support of sub fields
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $fieldType The field type
     * @return boolean
     */
    public function checkSubFieldSupport( $fieldType )
    {
        return in_array( $fieldType, ['flexible_content', 'repeater'] );
    }

    /**
     * Checks for support of layouts
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @param  string $fieldType The field type
     * @return boolean
     */
    public function checkLayoutSupport( $fieldType )
    {
        return in_array( $fieldType, ['flexible_content'] );
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
    public function generate( $template_directory )
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

        return file_put_contents( $template_directory . '/' . self::STORAGE_DIRECTORY . '/export.php', $data );
    }
}
