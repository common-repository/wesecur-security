<?php

namespace WesecurSecurity\includes;

/**
 * Translation global variables
 *
 *
 * @class 	   WesecurGlobalVariables
 * @package    WeSecur Security
 * @subpackage WesecurGlobalVariables
 * @category   Class
 * @since      1.2.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurGlobalVariables {
    static function get($variable) {
        $wesecurGlobalEcommerceUrl = __('https://www.wesecur.com', 'wesecur-security');
        $wesecurGlobal = array(
            'WESECURSECURITY_ECOMMERCE_URL' => $wesecurGlobalEcommerceUrl,
            'WESECURSECURITY_ECOMMERCE_CONTACT_URL' => sprintf('%s%s', $wesecurGlobalEcommerceUrl, __('/contact/', 'wesecur-security')),
            'WESECURSECURITY_ECOMMERCE_WAF_URL' => sprintf('%s%s', $wesecurGlobalEcommerceUrl, __('/web-protection-plans/', 'wesecur-security')),
            'WESECURSECURITY_ECOMMERCE_ESSENTIAL_URL' => sprintf('%s%s', $wesecurGlobalEcommerceUrl, __('/detect-clean-plan/', 'wesecur-security')),
            'WESECURSECURITY_ECOMMERCE_WP_PLUGIN_URL' => sprintf('%s%s', $wesecurGlobalEcommerceUrl, __('/detect-clean-plan/wordpress/wordpress-security-plugin/', 'wesecur-security')),
            'WESECURSECURITY_ECOMMERCE_ESSENTIAL_YEARLY_URL' => sprintf('%s%s', $wesecurGlobalEcommerceUrl, __('/product/detect/?attribute_pa_subscription=annual-payment', 'wesecur-security')),
            'WESECURSECURITY_ECOMMERCE_PREMIUM_YEARLY_URL' => sprintf('%s%s', $wesecurGlobalEcommerceUrl, __('/product/premium-plan/?attribute_pa_subscription=annual-payment', 'wesecur-security')),
            'WESECURSECURITY_ECOMMERCE_HELP_NOW_URL' => sprintf('%s%s', $wesecurGlobalEcommerceUrl, __('/malware-cleaning-services/', 'wesecur-security')),
            'WESECURSECURITY_DASHBOARD_URL' => __('https://dashboard.wesecur.com', 'wesecur-security')
        );
        return $wesecurGlobal[$variable];
    }
}
