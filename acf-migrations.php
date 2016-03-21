<?php
/**
 * Plugin Name: ACF Migrations
 * Plugin URI: https://github.com/hex-digital/acf-migrations
 * Description: An easy way to migrate local fields and field groups using readable object oriented syntax (without the need for huge arrays)
 * Author: Oliver Tappin
 * Version: 0.0.1
 */

class Migrations
{
    protected $fields;
    protected $fieldGroups;

    const STORAGE_DIRECTORY = 'acf';
    const ACF_PLUGIN_LOCATION = 'advanced-custom-fields-pro/acf.php';
    const ACF_FIELDS_LOCATION = 'advanced-custom-fields-pro/fields';

    public function __construct()
    {
        // Check for ACF Pro Plugin
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
     * @return array
     */
    public function addField( $type, $name, $options = false )
    {
        $defaults = $this->getDefaults( $type );

        // Add fields to field group
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
            if ( isset( $options['key'] ) ) unset( $options['key'] );
            if ( isset( $options['label'] ) ) unset( $options['label'] );
            if ( isset( $options['name'] ) ) unset( $options['name'] );
            if ( isset( $options['type'] ) ) unset( $options['type'] );

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
     * @param [type] $name      [description]
     * @param [type] $locations [description]
     */
    public function addFieldGroup( $name, $locations = false )
    {
        // Add defined field group array with values to memory
        $this->fieldGroups[] = [
            'key' => 'group_' . uniqid(),
            'title' => 'My Field Group',
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

        return $this;
    }

    /**
     * Adds the field groups to Advanced Custom Fields
     *
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return void
     */
    public function addFieldGroups()
    {
        if ( ! $this->localFieldGroupsEnabled() ) {
            throw new \Exception( 'Local field groups are not enabled' );
        }

        foreach ( $this->fieldGroups as $fieldGroup ) {
            acf_add_local_field_group( $fieldGroup );
        }
    }

    /**
     * Similar to var_export but supports PHP 5.4 array syntax
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
     * @author Oliver Tappin <oliver@hexdigital.com>
     * @return array
     */
    public function generate()
    {
        $data = $this->export( $this->fieldGroups );
        $data = "<?php\n\nreturn " . $data . "\n";
        return file_put_contents( get_template_directory() . '/' . self::STORAGE_DIRECTORY . '/export.php', $data );
    }
}

function change_order_of_loaded_plugins() {

    // Get the array of all active plugins
    $active_plugins = get_option( 'active_plugins' );

    // Get the ACF Pro plugin details to check against the array
    $acf_plugin = plugin_basename( self::ACF_PLUGIN_LOCATION );
    $acf_plugin_key = array_search( $acf_plugin, $active_plugins );

    // If the $plugin_key value is 0, then the ACF Pro plugin is already first
    // in the array so there's no need to continue
    if ( $acf_plugin_key ) {
        array_splice( $active_plugins, $acf_plugin_key, 1 );
        array_unshift( $active_plugins, $acf_plugin );
        update_option( 'active_plugins', $active_plugins );
    }
}

// Ensure the ACF Pro plugin loads first
add_action( 'activated_plugin', 'change_order_of_loaded_plugins' );

// Include theme migrations
$migrations_file = get_template_directory() . '/' . Migrations::STORAGE_DIRECTORY . '/migrations.php';
if ( file_exists( $migrations_file ) ) include $migrations_file;
