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

    <form action="admin.php?page=block_comment_email" method="get" class="comment-ays-submit">
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