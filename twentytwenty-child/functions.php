<?php
//region 样式与脚本
add_action('wp_head', 'lin_wp_head');
function lin_wp_head() {
    // 评论页面不要收录
    if (is_single() || is_page()) {
        if (function_exists('get_query_var')) {
            $cpage = intval(get_query_var('cpage'));
            $commentPage = intval(get_query_var('comment-page'));
        }
        if (!empty($cpage) || !empty($commentPage)) {
            echo '<meta name="robots" content="noindex, nofollow"/>' . "\n";
        }
    }
}

add_action('wp_enqueue_scripts', 'lin_enqueue_styles');
function lin_enqueue_styles() {
    $theme_version = wp_get_theme()->get('Version');
    $parent_style = 'twentytwenty-style';

    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
    wp_enqueue_style('twentytwenty-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array($parent_style),
        $theme_version
    );

    wp_enqueue_style('highlight-style',
        'https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@9.17.1/build/styles/default.min.css');
}

add_action('wp_footer', 'lin_wp_footer');
function lin_wp_footer() {
    wp_enqueue_script('highlight-js-main',
        'https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@9.17.1/build/highlight.min.js');
    wp_enqueue_script('highlight-js-linenumber',
        'https://cdn.jsdelivr.net/npm/highlightjs-line-numbers.js@2.7.0/dist/highlightjs-line-numbers.min.js');
    // 在 highlight 之后
    wp_enqueue_script('twentytwenty-child-js', get_stylesheet_directory_uri() . '/index.js',
        array(), wp_get_theme()->get('Version'), true);
    lin_top_bottom_nav();
    define_comment_reply_url();
}

function lin_top_bottom_nav() {
    $hasComments = false;
    $commentsOpen = false;
    if ((is_single() || is_page()) && (comments_open() || get_comments_number()) && !post_password_required()) {
        $hasComments = true;
        if (comments_open()) {
            $commentsOpen = true;
        }
    }
    ?>
    <div id="svg-nav">
        <svg id="svg-go-top" aria-label="到页面顶部">
            <path d="M3 25 L15 15 L27 25" fill="none" stroke="#ccc" stroke-width="3"/>
        </svg>
        <?php if ($hasComments) { ?>
            <svg id="svg-go-comments" aria-label="到评论列表">
                <g fill="none" stroke="#ccc" stroke-width="3">
                    <circle r="1.5" cx="2.5" cy="7" fill="#ccc" stroke-width="0"/>
                    <path d="M7 7 L29 7"/>
                    <circle r="1.5" cx="2.5" cy="15" fill="#ccc" stroke-width="0"/>
                    <path d="M7 15 L29 15"/>
                    <circle r="1.5" cx="2.5" cy="23" fill="#ccc" stroke-width="0"/>
                    <path d="M7 23 L29 23"/>
                </g>
            </svg>
        <?php } else {
            echo "<svg></svg>\n";// 用空的占个高度
        }
        if ($commentsOpen) { ?>
            <svg id="svg-go-reply" aria-label="到评论表单">
                <path fill="none" stroke="#ccc" stroke-width="3" d="M3 3 L27 3 L27 19 L24 19 L15 27 L19 19 L3 19 Z"/>
            </svg>
        <?php } ?>
        <svg id="svg-go-bottom" aria-label="到页面底部">
            <path d="M3 5 L15 15 L27 5" fill="none" stroke="#ccc" stroke-width="3"/>
        </svg>
    </div>
    <?php
}

//endregion

//region wordpress版权信息代码
function add_copyright($content) {
    if (is_single() or is_feed()) {
        $content .= "<hr />";
        $content .= '<div class="copyright" style="border: 1px solid;font-size: smaller;background-color: beige;border-radius: 9px">
					<div style="border-left-color: green;border-left-style: solid;border-left-width: 5px;margin: 1%;padding: 1%;">
					<h5 style="margin:0;"><small>声明</small></h5><ul style="margin:0">
					<li class="copyright-li">本作品采用<a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh" title="CC BY-NC-SA 4.0" target="_blank">署名-非商业性使用-相同方式共享 4.0 国际</a>许可协议进行许可。除非特别注明，<a href="https://youthlin.com" target="_blank"><strong>霖博客</strong></a>文章均为原创。</li>
					<li class="copyright-li">转载请保留本文(<a href="' . wp_get_shortlink(get_the_ID()) . '">《' . get_the_title() . '》</a>)链接地址 :  <u>' . wp_get_shortlink(get_the_ID()) . '</u></li>
					<li class="copyright-li">订阅本站：<a title="霖博客的RSS源" href="https://youthlin.com/feed/" rel="nofollow">http://youthlin.com/feed/</a></li></ul></div></div>';
    }
    return $content;
}

add_filter('the_content', 'add_copyright');

//endregion----------------------------------------------------------

//region 近期评论
class My_Widget_Recent_Comments extends WP_Widget_Recent_Comments {
    public function __construct() {
        $widget_ops = array(
            'classname' => 'my_widget_recent_comments',
            'description' => __('近期评论(带头像)'),
        );
        $this->WP_Widget('my-recent-comments', __('近期评论(带头像)'), $widget_ops);
    }

    /**
     * 输出近期评论
     *
     * @param array $args Display arguments including 'before_title', 'after_title',
     *                        'before_widget', and 'after_widget'.
     * @param array $instance 当前挂件的设置数据
     * @since 2.8.0
     *
     */
    public function widget($args, $instance) {
        if (!isset($args['widget_id'])) {
            $args['widget_id'] = $this->id;
        }

        $output = '';

        $title = (!empty($instance['title'])) ? $instance['title'] : __('Recent Comments');

        /** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
        $title = apply_filters('widget_title', $title, $instance, $this->id_base);

        $number = (!empty($instance['number'])) ? absint($instance['number']) : 5;
        if (!$number) {
            $number = 5;
        }

        /**
         * Filters the arguments for the Recent Comments widget.
         *
         * @param array $comment_args An array of arguments used to retrieve the recent comments.
         * @param array $instance Array of settings for the current widget.
         * @see WP_Comment_Query::query() for information on accepted arguments.
         *
         * @since 3.4.0
         * @since 4.9.0 Added the `$instance` parameter.
         *
         */
        $comments = get_comments(
            apply_filters(
                'widget_comments_args',
                array(
                    'number' => $number,
                    'status' => 'approve',
                    'post_status' => 'publish',
                    'type' => 'comment',    // 仅显示评论 不包括 ping-back
                    'user_id' => 0,         // 不显示作者评论
                ),
                $instance
            )
        );

        $output .= $args['before_widget'];
        if ($title) {
            $output .= $args['before_title'] . $title . $args['after_title'];
        }

        $output .= '<ul id="my-recent-comments">';
        if (is_array($comments) && $comments) {
            // Prime cache for associated posts. (Prime post term cache if we need it for permalinks.)
            $post_ids = array_unique(wp_list_pluck($comments, 'comment_post_ID'));
            _prime_post_caches($post_ids, strpos(get_option('permalink_structure'), '%category%'), false);

            foreach ((array)$comments as $comment) {
                $avatar = get_avatar($comment, 56, '', '头像');
                $output .= '<li class="recentcomments">';
                $output .=
                    '<div class="comment-avatar">' . $avatar . '</div>'
                    . '<div class="comment-content">'
                    . '  <div class="line line-1" title="' . htmlentities($comment->comment_content) . '">'
                    . mb_strimwidth(strip_tags($comment->comment_content), 0, 50, '...') . '</div>'
                    . '  <div class="line line-2">' . sprintf(
                    /* translators: Comments widget. 1: Comment author, 2: Post link. */
                        _x('%1$s on %2$s', 'widgets'),
                        get_comment_author_link($comment),
                        '<a href="' . esc_url(get_comment_link($comment)) . '">' . get_the_title($comment->comment_post_ID) . '</a>')
                    . '</div></div>';
                $output .= '</li>';
            }
        }
        $output .= '</ul>';
        $output .= $args['after_widget'];

        echo $output;
    }

}

register_widget('My_Widget_Recent_Comments');
//endregion

//region 博主动态

/** http://nichen.info/add-mood-widget/
 *  WP添加侧边栏“心情随笔”
 *
 *  http://www.nuodou.com/a/856.html
 * WordPress自定义侧边栏小工具
 * 2012年06月07日　Mr.诺豆
 *
 * 小工具接口
 * http://codex.wordpress.org/zh-cn:%E5%B0%8F%E5%B7%A5%E5%85%B7%E6%8E%A5%E5%8F%A3/
 */
class Saying_Comments extends WP_Widget {
    public function __construct() {
        $widget_ops = array('classname' => 'widget_saying', 'description' => _('一个类似QQ空间“说说”的小工具。'));
        parent::__construct(false, _('说说'), $widget_ops);
    }

    function form($instance) {
        //title:标题	pageid:页面ID	listnum:显示数量	charnum:截取字长
        $instance = wp_parse_args((array)$instance, array('title' => '说说', 'pageid' => 0, 'listnum' => 5, 'charnum' => 54));//默认值
        $title = htmlspecialchars($instance['title']);
        $pageid = htmlspecialchars($instance['pageid']);
        $listnum = htmlspecialchars($instance['listnum']);
        $charnum = htmlspecialchars($instance['charnum']);
        echo '<p style="text-align:left;"><label for="' . $this->get_field_name('title')
            . '">标题:<input style="width:200px;" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title')
            . '" type="text" value="' . $title . '" /></label></p>';
        echo '<p style="text-align:left;"><label for="' . $this->get_field_name('pageid')
            . '">页面ID:<input style="width:200px;" id="' . $this->get_field_id('pageid')
            . '" name="' . $this->get_field_name('pageid') . '" type="text" value="' . $pageid . '" /></label></p>';
        echo '<p style="text-align:left;"><label for="' . $this->get_field_name('listnum')
            . '">显示条数:<input style="width:200px;" id="' . $this->get_field_id('listnum') . '" name="'
            . $this->get_field_name('listnum') . '" type="text" value="' . $listnum . '" /></label></p>';
        echo '<p style="text-align:left;"><label for="' . $this->get_field_name('charnum')
            . '">截取字长:<input style="width:200px" id="' . $this->get_field_id('charnum') . '" name="'
            . $this->get_field_name('charnum') . '" type="text" value="' . $charnum . '" /></label></p>';
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags(stripslashes($new_instance['title']));
        $instance['pageid'] = $new_instance['pageid'];
        $instance['listnum'] = strip_tags(stripslashes($new_instance['listnum']));
        $instance['charnum'] = strip_tags(stripslashes($new_instance['charnum']));
        return $instance;

    }
    //$args是注册侧边栏的注册的几个变量
    //$instance是小工具的设置数据
    function widget($args, $instance) {
        // http://nichen.info/add-mood-widget/
        // @see WP_Widget_Recent_Comments
        global $comments, $comment;

        extract($args); //将数组展开
        $title = (!empty($instance['title'])) ? $instance['title'] : __('博主动态');
        $title = apply_filters('widget_title', $title, $instance, $this->id_base);
        //$title = apply_filters('widget_title', empty($instance['title']) ? __('说说') : $instance['title']);
        $pageid = empty($instance['pagedid']) ? $instance['pageid'] : 0;
        $listnum = empty($instance['listnum']) ? 5 : $instance['listnum'];
        $charnum = empty($instance['charnum']) ? 140 : $instance['charnum'];

        $avatar = get_avatar(1, 60, '', '头像');
        // http://codex.wordpress.org/Function_Reference/get_avatar

        $argument = array(
            'number' => $listnum,
            'post_id' => $pageid,
            'user_id' => '1',
            'parent' => '0'
        );

        // http://codex.wordpress.org/zh-cn:函数参考/get_comments
        $comments = get_comments($argument);
        $output = '';
        $output .= $args['before_widget'];
        if ($title) {
            $title = $avatar . '<a href="' . get_permalink($pageid) . '">' . $title . '</a>';
            $output .= $args['before_title'] . $title . $args['after_title'];
        }
        //echo $pageid;//输出页面ID调试以查看pageid是否正确
        $output .= '<ul id="saying" style="clear: both;">';
        if ($comments) {
            foreach ((array)$comments as $comment) {
                $output .= '<li class="saying">'
                    . '<span><a href="' . esc_url(get_comment_link($comment->comment_ID))
                    . '" title="' . htmlentities(strip_tags($comment->comment_content)) . '">'
                    . convert_smilies(mb_strimwidth(strip_tags($comment->comment_content), 0, $charnum, '[...]'))
                    // http://blog.wpjam.com/function_reference/convert_smilies/
                    . '</a></span><br />'
                    . '<span>' . get_comment_date('m月d日H:i ', $comment->comment_ID) . '</span></li>';
            }
        }
        $output .= '</ul>';
        $output .= $args['after_widget'];

        echo $output;
    }
}

register_widget('Saying_Comments');
//endregion---------------------------------------------------

//region 表情
// 系统表情指向主题表情(修改表情代码_代码1/4)
add_filter('smilies_src', 'custom_smilies_src', 1, 20);
function custom_smilies_src($img_src, $img, $siteurl) {
    return get_stylesheet_directory_uri() . '/images/smilies/' . $img;
}

// 表情代码转换图片(修改表情代码_代码2/4)
if (!isset($wpsmiliestrans)) {
    $wpsmiliestrans = array(
        '[/疑问]' => 'icon_question.gif',
        '[/调皮]' => 'icon_razz.gif',
        '[/难过]' => 'icon_sad.gif',
        '[/愤怒]' => 'icon_smile.gif',
        '[/可爱]' => 'icon_redface.gif',
        '[/坏笑]' => 'icon_biggrin.gif',
        '[/惊讶]' => 'icon_surprised.gif',
        '[/发呆]' => 'icon_eek.gif',
        '[/撇嘴]' => 'icon_confused.gif',
        '[/大兵]' => 'icon_cool.gif',
        '[/偷笑]' => 'icon_lol.gif',
        '[/得意]' => 'icon_mad.gif',
        '[/白眼]' => 'icon_rolleyes.gif',
        '[/鼓掌]' => 'icon_wink.gif',
        '[/亲亲]' => 'icon_neutral.gif',
        '[/流泪]' => 'icon_cry.gif',
        '[/流汗]' => 'icon_arrow.gif',
        '[/吓到]' => 'icon_exclaim.gif',
        '[/抠鼻]' => 'icon_evil.gif',
        '[/呲牙]' => 'icon_mrgreen.gif',
        ':?:' => 'icon_question.gif',
        ':razz:' => 'icon_razz.gif',
        ':sad:' => 'icon_sad.gif',
        ':evil:' => 'icon_smile.gif',
        ':!:' => 'icon_redface.gif',
        ':smile:' => 'icon_biggrin.gif',
        ':oops:' => 'icon_surprised.gif',
        ':grin:' => 'icon_eek.gif',
        ':eek:' => 'icon_confused.gif',
        ':shock:' => 'icon_cool.gif',
        ':???:' => 'icon_lol.gif',
        ':cool:' => 'icon_mad.gif',
        ':lol:' => 'icon_rolleyes.gif',
        ':mad:' => 'icon_wink.gif',
        ':twisted:' => 'icon_neutral.gif',
        ':roll:' => 'icon_cry.gif',
        ':wink:' => 'icon_arrow.gif',
        ':idea:' => 'icon_exclaim.gif',
        ':arrow:' => 'icon_evil.gif',
        ':neutral:' => 'icon_mrgreen.gif',
        ':cry:' => 'icon_eek.gif',
        ':mrgreen:' => 'icon_razz.gif',
    );
}
// 3/4 输出表情到评论框
function lin_get_smilies() {
    global $wpsmiliestrans;
    $wpsmilies = array_unique($wpsmiliestrans);
    $output = "\n";
    foreach ($wpsmilies as $alt => $src_path) {
        $output .= '<img title="' . $alt . '" alt="' . $alt . '" class="wp-smiley my-smiley" src="'
            . get_stylesheet_directory_uri() . '/images/smilies/' . $src_path . "\">\n";
    }
    return $output;
}

add_filter('comment_form_defaults', 'lin_output_smilies_to_comment_form');
function lin_output_smilies_to_comment_form($default) {
    $default['comment_field'] .= '<p class="comment-form-smilies">' . lin_get_smilies() . '</p>';
    return $default;
}

// 4/4: 在 js 中实现点击图片插入表情到评论框
//endregion

//region comment ajax
function define_comment_reply_url() {
    wp_localize_script('comment-reply', 'commentReply', array('url' => admin_url('admin-ajax.php')));
}

// wp_ajax_nopriv_{action} 用于登录用户 wp_ajax_{action} 用于未登录用户
add_action('wp_ajax_nopriv_lin_ajax_comment', 'lin_ajax_comment');
add_action('wp_ajax_lin_ajax_comment', 'lin_ajax_comment');
function lin_ajax_comment() {
    $comment = wp_handle_comment_submission(wp_unslash($_POST));
    if (is_wp_error($comment)) {
        header('HTTP/1.0 500 Internal Server Error');
        header('Content-Type: text/plain;charset=UTF-8');
        echo $comment->get_error_message();
        exit;
    }
    $user = wp_get_current_user();
    $cookies_consent = (isset($_POST['wp-comment-cookies-consent']));
    do_action('set_comment_cookies', $comment, $user, $cookies_consent);

    $comment_depth = 1;
    $comment_parent = $comment->comment_parent;
    while ($comment_parent) {
        $comment_depth++;
        $parent_comment = get_comment($comment_parent);
        $comment_parent = $parent_comment->comment_parent;
    }

    // 设置在 $GLOBALS 里 就可以用评论相关的函数了 如 comment_class() comment_text()
    $GLOBALS['comment'] = $comment;
    $GLOBALS['comment_depth'] = $comment_depth;
    ?>
    <div id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
        <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
            <footer class="comment-meta">
                <div class="comment-author vcard">
                    <?php
                    $comment_author_url = get_comment_author_url($comment);
                    $comment_author = get_comment_author($comment);
                    $avatar = get_avatar($comment, 120);
                    if (empty($comment_author_url)) {
                        echo wp_kses_post($avatar);
                    } else {
                        printf('<a href="%s" rel="external nofollow" class="url">', $comment_author_url);
                        echo wp_kses_post($avatar);
                    }

                    printf(
                        '<span class="fn">%1$s</span><span class="screen-reader-text says">%2$s</span>',
                        esc_html($comment_author),
                        __('says:', 'twentytwenty')
                    );

                    if (!empty($comment_author_url)) {
                        echo '</a>';
                    }
                    ?>
                </div>
                <div class="comment-metadata">
                    <a href="<?php echo esc_url(get_comment_link($comment)); ?>">
                        <?php
                        /* Translators: 1 = comment date, 2 = comment time */
                        $comment_timestamp = sprintf(__('%1$s at %2$s', 'twentytwenty'), get_comment_date('', $comment), get_comment_time());
                        ?>
                        <time datetime="<?php comment_time('c'); ?>"
                              title="<?php echo esc_attr($comment_timestamp); ?>">
                            <?php echo esc_html($comment_timestamp); ?>
                        </time>
                    </a>
                    <?php
                    if (get_edit_comment_link()) {
                        echo ' <span aria-hidden="true">&bull;</span> <a class="comment-edit-link" href="' . esc_url(get_edit_comment_link()) . '">' . __('Edit', 'twentytwenty') . '</a>';
                    }
                    ?>
                </div><!-- .comment-metadata -->
            </footer>
            <div class="comment-content entry-content">
                <?php
                comment_text();
                if ('0' === $comment->comment_approved) {
                    ?>
                    <p class="comment-awaiting-moderation"><?php _e('Your comment is awaiting moderation.', 'twentytwenty'); ?></p>
                    <?php
                }
                ?>
            </div>
            <footer class="comment-footer-meta">
                <?php
                $comment_reply_link = get_comment_reply_link(array(
                    'add_below' => 'div-comment',
                    'depth' => $comment_depth,
                    'max_depth' => get_option('thread_comments_depth'),
                    'before' => '<span class="comment-reply">',
                    'after' => '</span>',
                ));
                if ($comment_reply_link) {
                    echo $comment_reply_link;
                }
                if (twentytwenty_is_comment_by_post_author($comment)) {
                    echo '<span class="by-post-author">' . __('By Post Author', 'twentytwenty') . '</span>';
                }
                ?>
            </footer>
        </article>
    </div>
    <?php die();
}

add_action('wp_ajax_nopriv_lin_list_comment', 'lin_ajax_comment_list');
add_action('wp_ajax_lin_list_comment', 'lin_ajax_comment_list');
function lin_ajax_comment_list() {
    //todo ?action=lin_list_comment&post_id=1&cpage=1
    global $wp_query, $post, $comments;
    $wp_query->is_singular = true;
    $post = $_GET['post_id'];
    $page = $_GET['cpage'];
    $comments = get_comments(array('post_id' => $post, 'order' => 'ASC'));
    ?>
    <div class="comment-nav-wrap" id="comment-nav-wrap-top">
        <nav class="nav-links comment-nav-links">
            <?php paginate_comments_links(array('current' => $page)); ?>
        </nav>
    </div>
    <div id="comment-nav-list">
        <?php
        wp_list_comments(
            array(
                'walker' => new TwentyTwenty_Walker_Comment(),
                'avatar_size' => 120,
                'page' => $page,
                'per_page' => get_option('comments_per_page'),
                'style' => 'div',
                'order' => get_option('comment_order')
            ),
            $comments
        );
        ?>
    </div>
    <div class="comment-nav-wrap" id="comment-nav-wrap-bottom">
        <nav class="nav-links comment-nav-links">
            <?php paginate_comments_links(); ?>
        </nav>
    </div>
    <!--
        <?php echo (new Exception)->getTraceAsString(); ?>
        -->
    <?php
    die;
}
//endregion comment ajax
