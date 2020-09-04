<?php

/**
 * Registration Form Conditional Question (RFCQ)
 *
 * @package               Event Espresso
 * @subpackage            /modules/registration_form_conditional_question/
 * @author                Alfred Dagenais <jesuis@alfreddagenais.com>
 */
class EED_Registration_Form_Conditional_Question extends EED_Module
{

    /**
     * $_initialized - has the RFCQ controller already been initialized ?
     *
     * @access private
     * @var bool $_initialized
     */
    private static $_initialized = false;

    /**
     * @return EED_Module|EED_Registration_Form_Conditional_Question
     */
    public static function instance()
    {
        add_filter('EED_Registration_Form_Conditional_Question__RFCQ_active', '__return_true');
        return parent::get_instance(__CLASS__);
    }

    /**
     *    set_hooks - for hooking into EE Core, other modules, etc
     *
     * @access    public
     * @return    void
     * @throws EE_Error
     */
    public static function set_hooks()
    {
        EED_Registration_Form_Conditional_Question::set_definitions();
    }

    /**
     *    set_hooks_admin - for hooking into EE Admin Core, other modules, etc
     *
     * @access    public
     * @return    void
     * @throws EE_Error
     */
    public static function set_hooks_admin()
    {

        EED_Registration_Form_Conditional_Question::set_definitions();
        if (! (defined('DOING_AJAX') && DOING_AJAX)) {
            return;
        }

        // going to start an output buffer in case anything gets accidentally output
        // that might disrupt our JSON response
        ob_start();
        // set ajax hooks here



    }

    /**
     *    set_definitions
     *
     * @access    public
     * @return    void
     * @throws EE_Error
     */
    public static function set_definitions()
    {
        if (defined('RFCQ_BASE_PATH')) {
            return;
        }
        define(
            'RFCQ_BASE_PATH',
            rtrim(str_replace(array('\\', '/'), '/', plugin_dir_path(__FILE__)), '/') . '/'
        );
        define('RFCQ_CSS_URL', plugin_dir_url(__FILE__) . 'css/');
        define('RFCQ_IMG_URL', plugin_dir_url(__FILE__) . 'img/');
        define('RFCQ_JS_URL', plugin_dir_url(__FILE__) . 'js/');
        define('RFCQ_INC_PATH', RFCQ_BASE_PATH . 'inc/');

        //EEH_Autoloader::register_autoloaders_for_each_file_in_folder(RFCQ_BASE_PATH, true); // no needed for now
        EED_Registration_Form_Conditional_Question::instance()->enqueue_styles_and_scripts();

        /**
         * global action hook
         */
        do_action('AHEE__EED_Registration_Form_Conditional_Question__set_definitions');

    }

    /**
     *    enqueue_styles_and_scripts
     *
     * @access        public
     * @return        void
     * @throws EE_Error
     */
    public function enqueue_styles_and_scripts()
    {
        
        if ( defined('DOING_AJAX') && !DOING_AJAX) {
            return;
        }

        // load css
        wp_register_style(
            'registration_form_conditional_question',
            RFCQ_CSS_URL . 'registration_form_conditional_question.css',
            array('espresso-ui-theme', 'espresso_menu'),
            EVENT_ESPRESSO_VERSION
        );
        wp_enqueue_style('registration_form_conditional_question');

        // load JS
        wp_register_script(
            'registration_form_conditional_question',
            RFCQ_JS_URL . 'registration_form_conditional_question.js',
            array('jquery', 'espresso_core', 'underscore'),
            EVENT_ESPRESSO_VERSION,
            true
        );
        wp_enqueue_script('registration_form_conditional_question');

        /**
         * global action hook for enqueueing styles and scripts with
         */
        do_action('AHEE__EED_Registration_Form_Conditional_Question__enqueue_styles_and_scripts', $this);

    }

    /**
     * run - initial module setup
     * this method is primarily used for activating resources in the EE_Front_Controller thru the use of filters
     *
     * @var WP $WP
     * @access    public
     * @return    void
     */
    public function run($WP)
    {
        // TODO: Implement run() method.
    }

}
