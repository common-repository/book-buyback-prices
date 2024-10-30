<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


/**
 * Adds a privacy policy statement.
 */
function bbbp_plugin_add_privacy_policy_content() {
    if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
        return;
    }
    
    $content = '<h2>' .__('Customer (Public) Privacy Information','bbbp').'</h2>';
    $content .= '<p class="privacy-policy-tutorial">' .__('This information is for your public privacy page and concerns customer data','bbbp').'</p>';
    $content .= '<p>'.sprintf(__('We utilize book buyback lookup services from BuyBackData.com.  The only data that is submitted to them is the ISBN of the book being displayed or searched for. 
    					Their privacy policy is <a href="%1$s" target="_blank">here</a>.','bbbp'),'https://www.buybackdata.com/privacy-policy/').'</p>';
    $content .= '<p>'.sprintf(__('Google Recaptcha is used to prevent abuse.
    					Their privacy policy is <a href="%1$s" target="_blank">here</a>.','bbbp'),'https://www.google.com/intl/en-GB/policies/privacy/').'</p>';
    
    $content .= '<h2>' .__('WordPress Administrator (Private) Privacy Information','bbbp').'</h2>';
    $content .= '<p class="privacy-policy-tutorial">' .__('This information is for your internal use and may not be necessary for your public privacy page','bbbp').'</p>';

    $content .= '<p>'.sprintf(__('BuyBackData.com uses your domain name to set up an API key. This prevents abuse of the system. Documentation for the Book BuyBack Prices plugin is also stored on this site.
    					Their privacy policy is <a href="%1$s" target="_blank">here</a>.','bbbp'),'https://buybackdata.com/privacy-policy/').'</p>';

    $content .= '<p>'.sprintf(__('Book BuyBack Prices uses Freemium to track basic plugin stat usage and handle premium plugin accounts and account management.    
    					Their privacy policy is <a href="%1$s" target="_blank">here</a>.','bbbp'),'https://freemius.com/privacy/').'</p>';


    
            
    wp_add_privacy_policy_content( 'Book BuyBack Prices', wp_kses_post( wpautop( $content, false ) ) );
}
 
