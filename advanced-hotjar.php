<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Advanced Hotjar
 * Description:       Add Hotjar to your site, with a few more options.
 * Version:           1.0.0
 * Author:            Jerome Paulos
 * Author URI:        https://jeromepaulos.com
 * Text Domain:       advanced-hotjar
 */
class ConfigureHotjar {
    private $configure_hotjar_options;
    public function __construct() {
        add_action('admin_menu', array($this, 'configure_hotjar_add_plugin_page'));
        add_action('admin_init', array($this, 'configure_hotjar_page_init'));
    }
    public function configure_hotjar_add_plugin_page() {
        add_options_page('Configure Hotjar', // page_title
        'Hotjar', // menu_title
        'manage_options', // capability
        'advanced_hotjar', // menu_slug
        array($this, 'configure_hotjar_create_admin_page') // function
        );
    }
    public function configure_hotjar_create_admin_page() {
        $this->configure_hotjar_options = get_option('configure_hotjar_option_name'); ?>

		<div class="wrap">
			<h2>Configure Hotjar (with Advanced Options)</h2>
			<p>Plugin by <a href="https://jeromepaulos.com/">Jerome Paulos</a></p>

			<form method="post" action="options.php">
				<?php
        settings_fields('configure_hotjar_option_group');
        do_settings_sections('configure-hotjar-admin');
        submit_button();
?>
			</form>
		</div>
	<?php
    }
    public function configure_hotjar_page_init() {
        register_setting('configure_hotjar_option_group', // option_group
        'configure_hotjar_option_name', // option_name
        array($this, 'configure_hotjar_sanitize') // sanitize_callback
        );
        add_settings_section('configure_hotjar_setting_section', // id
        '', // title
        array($this, 'configure_hotjar_section_info'), // callback
        'configure-hotjar-admin'
        // page
        );
        add_settings_field('status', // id
        'Status', // title
        array($this, 'status_callback'), // callback
        'configure-hotjar-admin', // page
        'configure_hotjar_setting_section'
        // section
        );
        add_settings_field('hotjar_id', // id
        'Hotjar ID', // title
        array($this, 'hotjar_id_callback'), // callback
        'configure-hotjar-admin', // page
        'configure_hotjar_setting_section'
        // section
        );
        add_settings_field('tracking', // id
        'Tracking', // title
        array($this, 'tracking_callback'), // callback
        'configure-hotjar-admin', // page
        'configure_hotjar_setting_section'
        // section
        );
        add_settings_field('ip_addresses', // id
        'Do not track these IP addresses (one per line)', // title
        array($this, 'ip_addresses_callback'), // callback
        'configure-hotjar-admin', // page
        'configure_hotjar_setting_section'
        // section
        );
    }
    public function configure_hotjar_sanitize($input) {
        $sanitary_values = array();
        if (isset($input['status'])) {
            $sanitary_values['status'] = $input['status'];
        }
        if (isset($input['hotjar_id'])) {
            $sanitary_values['hotjar_id'] = sanitize_text_field($input['hotjar_id']);
        }
        if (isset($input['tracking'])) {
            $sanitary_values['tracking'] = $input['tracking'];
        }
        if (isset($input['ip_addresses'])) {
            $sanitary_values['ip_addresses'] = esc_textarea($input['ip_addresses']);
        }
        return $sanitary_values;
    }
    public function configure_hotjar_section_info() {
    }
    public function status_callback() {
?> <select name="configure_hotjar_option_name[status]" id="status">
			<?php $selected = (isset($this->configure_hotjar_options['status']) && $this->configure_hotjar_options['status'] === 'disabled') ? 'selected' : ''; ?>
			<option value="disabled" <?php echo $selected; ?>>Disabled</option>
			<?php $selected = (isset($this->configure_hotjar_options['status']) && $this->configure_hotjar_options['status'] === 'enabled') ? 'selected' : ''; ?>
			<option value="enabled" <?php echo $selected; ?>>Enabled</option>
		</select> <?php
    }
    public function hotjar_id_callback() {
        printf('<input placeholder="XXXXXXX" class="regular-text" type="number" name="configure_hotjar_option_name[hotjar_id]" id="hotjar_id" value="%s">', isset($this->configure_hotjar_options['hotjar_id']) ? esc_attr($this->configure_hotjar_options['hotjar_id']) : '');
    }
    public function tracking_callback() {
?> <fieldset><?php $checked = (isset($this->configure_hotjar_options['tracking']) && $this->configure_hotjar_options['tracking'] === 'everybody') ? 'checked' : ''; ?>
		<label for="tracking-0"><input type="radio" name="configure_hotjar_option_name[tracking]" id="tracking-0" value="everybody" <?php echo $checked; ?>> Track everybody (except the IPs below)</label><br>
		<?php $checked = (isset($this->configure_hotjar_options['tracking']) && $this->configure_hotjar_options['tracking'] === 'admins') ? 'checked' : ''; ?>
		<label for="tracking-1"><input type="radio" name="configure_hotjar_option_name[tracking]" id="tracking-1" value="admins" <?php echo $checked; ?>> Do not track admins</label><br>
		<?php $checked = (isset($this->configure_hotjar_options['tracking']) && $this->configure_hotjar_options['tracking'] === 'users') ? 'checked' : ''; ?>
		<label for="tracking-2"><input type="radio" name="configure_hotjar_option_name[tracking]" id="tracking-2" value="users" <?php echo $checked; ?>> Do not track all users</label></fieldset> <?php
    }
    public function ip_addresses_callback() {
        printf('<textarea class="large-text" rows="5" name="configure_hotjar_option_name[ip_addresses]" id="ip_addresses">%s</textarea>', isset($this->configure_hotjar_options['ip_addresses']) ? esc_attr($this->configure_hotjar_options['ip_addresses']) : '');
    }
}
if (is_admin()):
    $configure_hotjar = new ConfigureHotjar();
endif;
add_action('init', 'hotjar_checker');
function hotjar_checker() {
    $configure_hotjar_options = get_option('configure_hotjar_option_name');
    $status = $configure_hotjar_options['status'];
    $hotjar_id = $configure_hotjar_options['hotjar_id'];
    $tracking = $configure_hotjar_options['tracking'];
    if (!empty($configure_hotjar_options['ip_addresses'])) {
        $ip_addresses = explode("\n", $configure_hotjar_options['ip_addresses']);
    } else {
        $ip_addresses = array('disabled');
    };
    if ($status == 'enabled' && !empty($hotjar_id)) {
        if (!in_array($_SERVER['REMOTE_ADDR'], $ip_addresses)) {
            if ($tracking == 'admins' && !in_array('administrator', wp_get_current_user()->roles)) {
                add_action('wp_head', 'hotjar_script');
            } else {
                if ($tracking == 'users' && !is_user_logged_in()) {
                    add_action('wp_head', 'hotjar_script');
                } else {
                    if ($tracking == 'everybody') {
                        add_action('wp_head', 'hotjar_script');
                    }
                }
            }
        }
    }
}
function hotjar_script() { ?>


	<!-- Hotjar Tracking Code for <?php echo join('.', explode('.', parse_url(get_site_url()) ['host'])); ?> -->
	<script>
		(function(h,o,t,j,a,r){
			h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
			h._hjSettings={hjid:<?php echo get_option('configure_hotjar_option_name') ['hotjar_id']; ?>,hjsv:6};
			a=o.getElementsByTagName('head')[0];
			r=o.createElement('script');r.async=1;
			r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
			a.appendChild(r);
		})(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
	</script>

<?php
} ?>