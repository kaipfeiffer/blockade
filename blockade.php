<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://loworx.com
 * @since             1.0.0
 * @package           blockade
 *
 * @wordpress-plugin
 * Plugin Name:       Blockade
 * Plugin URI:        https://loworx.com
 * Description:       Blockiert E-Mail-Adressen und E-Mail-Hosts für Kommentare
 * Version:           1.0.0
 * Author:            Kai Pfeiffer
 * Author URI:        https://loworx.com
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       blockade
 * Domain Path:       /languages
 * Requires PHP:      7.3
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}


/**
 * Main class to bootstrap the plugin
 *
 * @author     Kai Pfeiffer <kp@loworx.com>
 * @package    blockade
 * @since      1.0.0
 */
class Blockade
{

    // register_deactivation_hook(__FILE__, 'deactivate_sbu_wc_handout');

    /**
     * The core plugin class that is used to define internationalization,
     * admin-specific hooks, and public-facing site hooks.
     */

    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @access public
     * @since   1.0.0 
     * @static
     */
    static function start()
    {
        add_action('admin_menu', array(__CLASS__, 'add_block_mail_submenu_page'));
        add_action('spam_comment', array(__CLASS__, 'delete_comment'), 10, 2);

        add_filter('comment_moderation_text', array(__CLASS__, 'comment_moderation_text'), 10, 2);
        add_filter('comment_row_actions', array(__CLASS__, 'comment_row_actions'), 10, 2);
    }

    /**
     * ablock_comments_page
     *
     *
     * @access  public
     * @since   1.0.0 
     * @static
     */
    static function block_comments_page()
    {
        $comment_id     = absint($_REQUEST['c']);
        $nonce_action   = 'delete-comment_';
        $nonce_action   .= $comment_id;

        $nonce          = $_REQUEST['_wpnonce'];
        if (wp_verify_nonce($nonce, $nonce_action)) {
            if (wp_spam_comment($comment_id)) {
                echo '<script>window.location="/wp-admin/edit-comments.php";</script>';
                wp_redirect('/wp-admin/edit-comments.php');
            }
        } else {

            $action     = strval($_REQUEST['action']);
            $comment        = get_comment($comment_id);
            $title          = __('Block Comment');
            error_log(__CLASS__ . '->' . __LINE__ . '->' . print_r($comment, 1));
            switch ($action) {
                case 'block_email': {
                        $email          = $comment->comment_author_email;
                        $caution_msg    = __('Du willst diesen Kommentar als Spam markieren und die E-Mail-Adresse <b>"%1$s"</b> blockieren');
                        $button         =
                            $title      = __('E-Mail-Adresse blockieren');
                        break;
                    }
                case 'block_email_host': {
                        $email          = (explode('@', $comment->comment_author_email))[1];
                        $caution_msg    = __('Du willst diesen Kommentar als Spam markieren und dessen E-Mail-Host <b>"%1$s"</b> blockieren');
                        $button         =
                            $title          = __('E-Mail-Host blockieren');
                        break;
                    }
            }
            $caution_msg    = sprintf($caution_msg, $email);
            $sub_action     = $action;
            $action         = 'spam';
            $formaction     = $action . 'comment';

            include_once(__DIR__ . '/templates/block-comment.php');
        }
        return true;
    }

    /**
     * add_block_mail_submenu_page
     *
     *
     * @access  public
     * @since   1.0.0 
     * @static
     */
    static function add_block_mail_submenu_page()
    {
        error_log(__CLASS__ . '->' . __LINE__ . '->' . is_callable(array(__CLASS__, 'block_comments_page')));
        add_submenu_page(
            '',
            'E-Mail blockieren',
            'E-Mail blockieren',
            'moderate_comments',
            'block_comment_email',
            array(__CLASS__, 'block_comments_page')
        );
    }

    /**
     * block_email
     *
     * E-Mail-Adresse oder E-Mail-Host zur Option "disallowed_keys" hinzufügen
     *
     * @access  public
     * @param   string
     * @since   1.0.0 
     * @static
     */
    static function block_email($mail)
    {
        $disallowed_keys        = get_option('disallowed_keys');
        $disallowed_key_list    = explode("\n", $disallowed_keys);
        if (!in_array($mail, $disallowed_key_list)) {
            array_push($disallowed_key_list, $mail);
            $disallowed_keys        = implode("\n", $disallowed_key_list);
            update_option('disallowed_keys', $disallowed_keys);
        }
    }

    /**
     * comment_row_actions
     *
     * adjust PHPMailer settings to use mailhog
     *
     * @access  public
     * @param   string
     * @param   integer
     * @since   1.0.0 
     * @static
     */
    static function delete_comment($id, $comment)
    {
        // error_log(__CLASS__ . '->' . __LINE__ .'->'. print_r($_GET, 1));
        if (isset($_GET['sub_action']) || isset($_GET['_wp_http_referer'])) {
            $action = $_GET['sub_action'];
            if (!$action) {
                $regex = '/sub_action=(\w+)/';
                if (preg_match($regex, $_GET['_wp_http_referer'], $matches)) {
                    $action = $matches[1];
                }
            }
            if ('block_email' === $action) {
                error_log(__CLASS__ . '->' . __LINE__ . '->Blockiere-E-Mail ->' . $comment->comment_author_email);
                static::block_email($comment->comment_author_email);
            } elseif ('block_email_host' === $action) {
                $host = 'Nicht verfügbar';
                if (preg_match('/@(.*)$/', $comment->comment_author_email, $matches)) {
                    $host   = '@'.$matches[1];
                }
                error_log(__CLASS__ . '->' . __LINE__ . '->Blockiere-E-Mail-Host ->' . $host);
                static::block_email($host);
            }
        }

        $location = '/wp-admin/edit-comments.php';

        header('Location: ' . $location);

        // error_log(__CLASS__ . '->' . __LINE__ .'->'. print_r(debug_backtrace(), 1));
    }

    /**
     * comment_row_actions
     *
     * adjust PHPMailer settings to use mailhog
     *
     * @access  public
     * @param   string
     * @param   integer
     * @since   1.0.0 
     * @static
     */
    static function comment_row_actions($actions, $comment)
    {
        preg_match('/_wpnonce=([\da-fA-F]+)/', $actions['spam'], $matches);
        $nonce      = $matches[1];

        // error_log(__CLASS__ . '->' . __LINE__ .'->'. print_r($comment, 1));
        $action_string          = '<a href="comment.php?c=%1$d&#038;action=spamcomment&#038;sub_action=%2$s&#038;_wpnonce=%3$s"  data-wp-lists="%2$s:the-comment-list:comment-%1$d::spam=1:%2$s=1" class="vim-s vim-destructive aria-button-if-js" aria-label="%4$s">%5$s</a>' . "\n  ";

        $actions['block_email'] = sprintf(
            $action_string,
            $comment->comment_ID,
            'block_email',
            $nonce,
            /*TODO: Internationalisieren */
            'Diese E-Mail-Adresse blockieren',
            'E-Mail blockieren'
        );

        $actions['block_email_host'] = sprintf(
            $action_string,
            $comment->comment_ID,
            'block_email_host',
            $nonce,
            /*TODO: Internationalisieren */
            'Gesamten E-Mail-Host blockieren',
            'E-Mail-Host blockieren'
        );

        return $actions;
    }

    /**
     * comment_moderation_text
     *
     * adjust PHPMailer settings to use mailhog
     *
     * @access  public
     * @param   string
     * @param   integer
     * @since   1.0.0 
     * @static
     */
    static function comment_moderation_text($notify_message, $comment_id)
    {
        $site_url   = get_site_url();
        $site_url   = preg_quote($site_url, '/');
        $regex      = '/[^\n]*\n^' . $site_url . '\/wp-admin\/[^\n]*$/m';
        $replacement = sprintf(
            'Block-EMail: %1$s/wp-admin/admin.php?page=block_comment_email&action=block_email&c=%2$d#wpbody-content' . "\n" .
                'Block-EMail-Host: %1$s/wp-admin/admin.php?page=block_comment_email&action=block_email_host&c=%2$d#wpbody-content' . "\n$0",
            str_replace('\\', '', $site_url),
            $comment_id
        );

        $notify_message = preg_replace($regex, $replacement, $notify_message);

        return $notify_message;
    }


    /**
     * wp_mail_from
     *
     * adjust PHPMailer settings to use mailhog
     *
     * @access  public
     * @param   String
     * @since   1.0.0 
     * @static
     */
    static function wp_mail_from(String $from_email)
    {
        return 'kai@idevo.de';
    }

    /**
     * setup_mailhog
     *
     * adjust PHPMailer settings to use mailhog
     *
     * @access  public
     * @param   PHPMailer
     * @since   1.0.0 
     * @static
     */
    static function setup_mailhog($phpmailer)
    {
        $phpmailer->isSMTP();
        $phpmailer->SMTPAuth    = false;
        $phpmailer->SMTPSecure  = '';
        $phpmailer->Host        = 'mailhog';
        $phpmailer->Port        = '1025';
        $phpmailer->Username    = null;
        $phpmailer->Password    = null;
    }
}
Blockade::start();
