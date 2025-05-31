<?php
/*
Plugin Name: Contact Form Block
Description: A Gutenberg block that saves email addresses to a text file.
Version: 1.1
Author: Lonn Holiday
*/
function cfb_register_block_assets() {
    // Register editor JS
    wp_register_script(
        'cfb-block-js',
        plugins_url( 'block.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-block-editor', 'wp-components' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'block.js' )
    );
    // Register block with PHP render
    register_block_type( 'cfb/contact-form', array(
        'editor_script'   => 'cfb-block-js',
        'render_callback' => 'cfb_render_callback',
        'attributes' => array(
            'buttonText' => array(
                'type' => 'string',
                'default' => 'Submit',
            ),
            'buttonColor' => array(
                'type' => 'string',
                'default' => '#000000',
            )
        )
    ) );
}
add_action( 'init', 'cfb_register_block_assets' );
function cfb_render_callback( $attributes ) {
    $button_color = isset( $attributes['buttonColor'] ) ? esc_attr( $attributes['buttonColor'] ) : '#000000';
    $button_text  = isset( $attributes['buttonText'] ) ? esc_html( $attributes['buttonText'] ) : 'Submit';
    // Handle form submission
    if ( isset( $_POST['cfb_email'] ) ) {
        $email = sanitize_email( $_POST['cfb_email'] );
        if ( is_email( $email ) ) {
            $file = plugin_dir_path( __FILE__ ) . 'email_list.txt';
            file_put_contents( $file, $email . PHP_EOL, FILE_APPEND | LOCK_EX );
            echo '<p>Thank you!</p>';
        }
    }
    // Output the form
ob_start(); ?>
<style>
@media (max-width: 600px) {
    .cfb-form {
        flex-direction: column;
        align-items: stretch;
    }
    .cfb-form input[type="email"] {
        border-radius: 1.5em 1.5em 0 0;
        margin-bottom: 0.5em;
    }
    .cfb-form input[type="submit"] {
        border-radius: 0 0 1.5em 1.5em;
    }
}
</style>
<form method="POST" class="cfb-form" style="display: flex; align-items: center; max-width: 500px;">
    <input type="email" name="cfb_email" required
        placeholder="Enter your email"
        style="
            flex: 1;
            padding: 0.75em 1em;
            border: none;
            border-top-left-radius: 1.5em;
            border-bottom-left-radius: 1.5em;
            background-color: #f4dcdc;
            font-size: 16px;
            color: #333;
        ">
    <input type="submit" value="Join Early Access"
        style="
            padding: 0.75em 1.5em;
            border: none;
            border-top-right-radius: 1.5em;
            border-bottom-right-radius: 1.5em;
            background-color: #c75858;
            color: #fff;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
        ">
</form>
<?php return ob_get_clean();
}
// Add admin menu
add_action('admin_menu', function () {
    add_menu_page(
        'Contact Form Emails',
        'Contact Emails',
        'manage_options',
        'cfb-email-list',
        'cfb_render_admin_page',
        'dashicons-email',
        80
    );
});
// Render the admin page
function cfb_render_admin_page() {
    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized user');
    }
    $file = plugin_dir_path(__FILE__) . 'email_list.txt';
    $emails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    echo '<div class="wrap"><h1>Contact Form Submissions</h1>';
    if (isset($_GET['cfb-cleared']) && $_GET['cfb-cleared'] === '1') {
        echo '<div class="notice notice-success"><p>Email list cleared.</p></div>';
    }
    echo '<form method="post" style="margin-bottom:1em;">';
    wp_nonce_field('cfb_clear_emails');
    echo '<input type="submit" name="cfb_clear" class="button button-secondary" value="Clear Email List" onclick="return confirm(\'Are you sure?\')">';
    echo '</form>';
    echo '<form method="get" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin-bottom:1em;">';
    echo '<input type="hidden" name="action" value="cfb_export_csv">';
    echo '<input type="submit" class="button button-primary" value="Download as CSV">';
    echo '</form>';
    if (!empty($emails)) {
        echo '<table class="widefat"><thead><tr><th>Email Address</th></tr></thead><tbody>';
        foreach ($emails as $email) {
            echo '<tr><td>' . esc_html($email) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No emails submitted yet.</p>';
    }
    echo '</div>';
}
// Export CSV handler
add_action('admin_post_cfb_export_csv', function () {
    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized user');
    }
    $file = plugin_dir_path(__FILE__) . 'email_list.txt';
    $emails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="contact_emails.csv"');
    $output = fopen('php://output', 'w');
    foreach ($emails as $email) {
        fputcsv($output, [$email]);
    }
    fclose($output);
    exit;
});
// Clear email list
add_action('admin_init', function () {
    if (isset($_POST['cfb_clear']) && current_user_can('manage_options') && check_admin_referer('cfb_clear_emails')) {
        $file = plugin_dir_path(__FILE__) . 'email_list.txt';
        if (file_exists($file)) {
            file_put_contents($file, '');
        }
        wp_redirect(admin_url('admin.php?page=cfb-email-list&cfb-cleared=1'));
        exit;
    }
});