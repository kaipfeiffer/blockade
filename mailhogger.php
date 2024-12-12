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
 * @package           ridepool
 *
 * @wordpress-plugin
 * Plugin Name:       Mailhogger
 * Plugin URI:        https://loworx.com
 * Description:       Sendet Mails zum Mailhog Host
 * Version:           1.0.0
 * Author:            Kai Pfeiffer
 * Author URI:        https://loworx.com
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       ridepool
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
 * @package    mailhogger
 * @since      1.0.0
 */
class Mailhogger
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
        add_action('phpmailer_init', array(__CLASS__, 'setup_mailhog'), 10, 1);

        add_action('spam_comment', array(__CLASS__, 'delete_comment'), 10, 2);
        add_filter('wp_mail_from', array(__CLASS__, 'wp_mail_from'), 10, 1);
        // add_filter('pre_wp_mail', array(__CLASS__, 'pre_wp_mail'), 10, 2);
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
        if(wp_verify_nonce($nonce,$nonce_action)){
            if(wp_spam_comment($comment_id)){
                // wp_redirect('/wp-admin/edit-comments.php');
            }
        }else{

        $action     = strval($_REQUEST['action']);
        $comment        = get_comment($comment_id);
        error_log(__CLASS__ . '->' . __LINE__ .'->'. print_r($comment, 1));
        switch($action){
            case 'block_email':{
                $email          = $comment ->comment_author_email;
                $caution_msg    = __('Du willst diesen Kommentar als Spam markieren und die E-Mail-Adresse <b>"%1$s"</b> blockieren');
                $button         = __('E-Mail-Adresse blockieren');
                break;
            }
            case 'block_email_host':{
                $email          = (explode('@',$comment ->comment_author_email))[1];
                $caution_msg    = __('Du willst diesen Kommentar als Spam markieren und dessen E-Mail-Host <b>"%1$s"</b> blockieren');
                $button         = __('E-Mail-Host blockieren');
                break;
            }
        }
        $caution_msg    = sprintf($caution_msg, $email);
        $sub_action     = $action;
        $action         = 'spam';
		$formaction     = $action . 'comment';
		$title          = __( 'Moderate Comment' );
?>
        <div class="wrap">
            <h1><?php echo esc_html($title); ?></h1>

            <?php

            if ('0' !== $comment->comment_approved) { // If not unapproved.
                $message = '';
                switch ($comment->comment_approved) {
                    case '1':
                        $message = __('This comment is currently approved.');
                        break;
                    case 'spam':
                        $message = __('This comment is currently marked as spam.');
                        break;
                    case 'trash':
                        $message = __('This comment is currently in the Trash.');
                        break;
                }
                if ($message) {
                    echo '<div id="message" class="notice notice-info"><p>' . $message . '</p></div>';
                }
            }
            ?>
            <div id="message" class="notice notice-warning">
                <p><strong><?php _e('Caution:'); ?></strong> <?php echo $caution_msg; ?></p>
            </div>

            <table class="form-table comment-ays">
                <tr>
                    <th scope="row"><?php _e('Author'); ?></th>
                    <td><?php comment_author($comment); ?></td>
                </tr>
                <?php if (get_comment_author_email($comment)) { ?>
                    <tr>
                        <th scope="row"><?php _e('Email'); ?></th>
                        <td><?php comment_author_email($comment); ?></td>
                    </tr>
                <?php } ?>
                <?php if (get_comment_author_url($comment)) { ?>
                    <tr>
                        <th scope="row"><?php _e('URL'); ?></th>
                        <td><a href="<?php comment_author_url($comment); ?>"><?php comment_author_url($comment); ?></a></td>
                    </tr>
                <?php } ?>
                <tr>
                    <th scope="row"><?php /* translators: Column name or table row header. */ _e('In response to'); ?></th>
                    <td>
                        <?php
                        $post_id = $comment->comment_post_ID;
                        if (current_user_can('edit_post', $post_id)) {
                            $post_link  = "<a href='" . esc_url(get_edit_post_link($post_id)) . "'>";
                            $post_link .= esc_html(get_the_title($post_id)) . '</a>';
                        } else {
                            $post_link = esc_html(get_the_title($post_id));
                        }
                        echo $post_link;

                        if ($comment->comment_parent) {
                            $parent      = get_comment($comment->comment_parent);
                            $parent_link = esc_url(get_comment_link($parent));
                            $name        = get_comment_author($parent);
                            printf(
                                /* translators: %s: Comment link. */
                                ' | ' . __('In reply to %s.'),
                                '<a href="' . $parent_link . '">' . $name . '</a>'
                            );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Submitted on'); ?></th>
                    <td>
                        <?php
                        $submitted = sprintf(
                            /* translators: 1: Comment date, 2: Comment time. */
                            __('%1$s at %2$s'),
                            /* translators: Comment date format. See https://www.php.net/manual/datetime.format.php */
                            get_comment_date(__('Y/m/d'), $comment),
                            /* translators: Comment time format. See https://www.php.net/manual/datetime.format.php */
                            get_comment_date(__('g:i a'), $comment)
                        );
                        if ('approved' === wp_get_comment_status($comment) && ! empty($comment->comment_post_ID)) {
                            echo '<a href="' . esc_url(get_comment_link($comment)) . '">' . $submitted . '</a>';
                        } else {
                            echo $submitted;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php /* translators: Field name in comment form. */ _ex('Comment', 'noun'); ?></th>
                    <td class="comment-content">
                        <?php comment_text($comment); ?>
                        <p class="edit-comment">
                            <a href="<?php echo esc_url(admin_url("comment.php?action=editcomment&c={$comment->comment_ID}")); ?>"><?php esc_html_e('Edit'); ?></a>
                        </p>
                    </td>
                </tr>
            </table>

            <form action="edit-comments.php" method="get" class="comment-ays-submit">
                <p>
                    <?php submit_button($button, 'primary', 'submit', false); ?>
                    <a href="<?php echo esc_url(admin_url('edit-comments.php')); ?>" class="button-cancel"><?php esc_html_e('Cancel'); ?></a>
                </p>

                <?php wp_nonce_field($nonce_action); ?>
                <input type="hidden" name="action" value="<?php echo esc_attr($formaction); ?>" />
                <input type="hidden" name="page" value="block_comment_email" />
                <input type="hidden" name="sub_action" value="<?php echo esc_attr($sub_action); ?>" />
                <input type="hidden" name="c" value="<?php echo esc_attr($comment->comment_ID); ?>" />
                <input type="hidden" name="noredir" value="1" />
            </form>

        </div>
        </div>
<?php
            
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
            'edit-comments.php',
            'E-Mail blockieren',
            'E-Mail blockieren',
            'moderate_comments',
            'block_comment_email',
            array(__CLASS__, 'block_comments_page')
        );
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
            } elseif ('block_email_host' === $action) {
                $host = 'Nicht verfügbar';
                if (preg_match('/@(.*)$/', $comment->comment_author_email, $matches)) {
                    $host   = $matches[1];
                }
                error_log(__CLASS__ . '->' . __LINE__ . '->Blockiere-E-Mail-Host ->' . $host);
            }
        }

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
            'Block-EMail: http://localhost:8180/wp-admin/edit-comments.php?page=block_comment_email&action=block_email&c=%1$d#wpbody-content' . "\n" .
                'Block-EMail-Host: http://localhost:8180/wp-admin/edit-comments.php?page=block_comment_email&action=block_email_host&c=%1$d#wpbody-content' . "\n$0",
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
Mailhogger::start();
