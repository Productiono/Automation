<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="uap-spacing-bottom">
        <?php wp_nonce_field( 'automator_fbla_manual_save', 'automator_fbla_nonce' ); ?>
        <input type="hidden" name="action" value="automator_fbla_manual_save" />

        <div class="uap-spacing-bottom">
                <label class="uap-settings-panel-content-subtitle" for="fbla_app_id">
                        <?php echo esc_html_x( 'App ID', 'Facebook Lead Ads', 'uncanny-automator' ); ?>
                </label>
                <input type="text" id="fbla_app_id" name="fbla_app_id" class="widefat" value="<?php echo esc_attr( $vars['credentials']['app_id'] ?? '' ); ?>" />
        </div>

        <div class="uap-spacing-bottom">
                <label class="uap-settings-panel-content-subtitle" for="fbla_app_secret">
                        <?php echo esc_html_x( 'App secret', 'Facebook Lead Ads', 'uncanny-automator' ); ?>
                </label>
                <input type="password" id="fbla_app_secret" name="fbla_app_secret" class="widefat" value="<?php echo esc_attr( $vars['credentials']['app_secret'] ?? '' ); ?>" />
        </div>

        <div class="uap-spacing-bottom">
                <label class="uap-settings-panel-content-subtitle" for="fbla_user_access_token">
                        <?php echo esc_html_x( 'User access token', 'Facebook Lead Ads', 'uncanny-automator' ); ?>
                </label>
                <input type="text" id="fbla_user_access_token" name="fbla_user_access_token" class="widefat" value="<?php echo esc_attr( $vars['credentials']['user_access_token'] ?? '' ); ?>" />
        </div>

        <div class="uap-spacing-bottom">
                <label class="uap-settings-panel-content-subtitle" for="fbla_page_name">
                        <?php echo esc_html_x( 'Page name', 'Facebook Lead Ads', 'uncanny-automator' ); ?>
                </label>
                <input type="text" id="fbla_page_name" name="fbla_page_name" class="widefat" value="<?php echo esc_attr( $vars['user']['name'] ?? '' ); ?>" />
        </div>

        <div class="uap-spacing-bottom">
                <label class="uap-settings-panel-content-subtitle" for="fbla_page_id">
                        <?php echo esc_html_x( 'Page ID', 'Facebook Lead Ads', 'uncanny-automator' ); ?>
                </label>
                <input type="text" id="fbla_page_id" name="fbla_page_id" class="widefat" value="<?php echo esc_attr( $vars['user']['id'] ?? '' ); ?>" />
        </div>

        <div class="uap-spacing-bottom">
                <label class="uap-settings-panel-content-subtitle" for="fbla_page_access_token">
                        <?php echo esc_html_x( 'Page access token', 'Facebook Lead Ads', 'uncanny-automator' ); ?>
                </label>
                <input type="text" id="fbla_page_access_token" name="fbla_page_access_token" class="widefat" value="<?php echo esc_attr( $vars['credentials']['pages_access_tokens'][0]['access_token'] ?? '' ); ?>" />
        </div>

        <uo-button type="submit">
                <uo-icon id="save"></uo-icon>
                <?php echo esc_html_x( 'Save connection details', 'Facebook Lead Ads', 'uncanny-automator' ); ?>
        </uo-button>
</form>
