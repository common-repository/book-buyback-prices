<?php

if ( ! defined( 'WPINC' ) ) die("No cheating!");

?>

<?php

	if ( isset( $_GET['settings-updated'] ) ) {
        // add settings saved message with the class of "updated"
        add_settings_error( 'bbbp_messages', 'bbbp_message', __( 'Settings Saved', 'bbbp' ), 'updated' );
    }
 
    // show error/update messages
    settings_errors( 'bbbp_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "wporg"
            settings_fields( 'bbbp' );
            // output setting sections and their fields
            // (sections are registered for "wporg", each field is registered to a specific section)
            do_settings_sections( 'bbbp' );
            // output save settings button
            submit_button( 'Save Settings' );
            ?>
        </form>
        
		<h1>Shortcode Setup</h1>
		<p>
			To add a book buyback pricing comparison list to any page, just add the shortcode: <pre>[bbbp]</pre>
			<br>
			If you want to hide the search bar and only show resuls for a specific ISBN, you can create a shortcode like this: 
			<pre>[bbbp ISBN=123456789X]</pre>
		
        </p>
        
        <h1>Want your own Book BuyBack Site?</h1>
		<p>
			If you&#39;re interested in having your own buyback site, check out the software that is already widely used in the industry: <a href="https://kobibooks.com" target="_blank">KobiBooks</a>.
        </p>

        <h1>Or maybe your own Device BuyBack Site?</h1>
		<p>
			Or maybe you&#39;re more interested in buying devices?  Check out <a href="https://kobidevices.com" target="_blank">KobiDevices</a> for full-service websites.  Or if you prefer to build your own, the <a href="https://gytabuyback.com" target="_blank">Gyta BuyBack Plugin</a> can transform a wordpress site into a device buyback site.
			
        </p>
    </div>
