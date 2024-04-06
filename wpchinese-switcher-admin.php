<?php

class wpcs_Admin {
	var $base = '';
	var $is_submitted = false;
	var $is_success = false;
	var $is_error = false;
	var $message = '';
	var $options = false;
	var $langs = false;
	var $url = '';
	var $admin_lang = false;

	function wpcs_Admin() {
		return $this->__construct();
	}

	function __construct() {
		global $wpcs_options, $wpcs_langs;
		$locale = str_replace( '_', '-', strtolower( get_locale() ) );
		if ( $wpcs_options === false ) {
			$wpcs_options = get_option( 'wpcs_options' );
		}
		$this->langs   = &$wpcs_langs;
		$this->options = $wpcs_options;

		if ( ! empty( $_GET['variant'] ) && in_array( $_GET['variant'], array_keys( $this->langs ) ) ) {
			$this->admin_lang = $_GET['variant'];
		} else if ( in_array( $locale, array_keys( $this->langs ) ) ) {
			$this->admin_lang = $locale;
		}
		$this->base = str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) );
		$this->url  = admin_url( 'options-general.php?page=' . $this->base . 'wpchinese-switcher.php' );
		add_filter( 'plugin_action_links', array( &$this, 'action_links' ), 10, 2 );
		if ( function_exists( 'add_options_page' ) ) {
			add_options_page( 'WP Chinese Switcher', 'Chinese Switcher', 'manage_options', $this->base . 'wpchinese-switcher.php', array(
				&$this,
				'display_options'
			) );
		}

		wp_enqueue_script( 'jquery' );
	}

	function action_links( $links, $file ) {
		if ( $file == $this->base . 'wpchinese-switcher.php' ) {
			$links[] = '<a href="options-general.php?page=' . $file . '" title="Change Settings">Settings</a>';
		}

		return $links;
	}

	function install_cache_module() {
		global $wpcs_options;

		$ret = true;

		$file = file_get_contents( dirname( __FILE__ ) . '/wpcs-wp-super-cache-plugin.php' );

		$used_langs = 'Array()';
		if ( count( $wpcs_options['wpcs_used_langs'] ) > 0 ) {
			$used_langs = "Array('" . implode( "', '", $wpcs_options['wpcs_used_langs'] ) . "')";
		}
		$file = str_replace( '##wpcs_auto_language_recong##',
			$wpcs_options['wpcs_auto_language_recong'], $file );
		$file = str_replace( '##wpcs_used_langs##',
			$used_langs, $file );

		$fp = @fopen( WP_PLUGIN_DIR . '/wp-super-cache/plugins/wpcs-wp-super-cache-plugin.php', 'w' );
		if ( $fp ) {
			fputs( $fp, $file );
			fclose( $fp );
		} else {
			$ret = false;
		}

		return true;
	}

	function uninstall_cache_module() {
		return unlink( WP_PLUGIN_DIR . '/wp-super-cache/plugins/wpcs-wp-super-cache-plugin.php' );
	}

	function get_cache_status() {
		if ( ! is_plugin_active( 'wp-super-cache/wp-cache.php' ) ) {
			return 0; // not activated
		}
		if ( ! file_exists( WP_PLUGIN_DIR . '/wp-super-cache/plugins/wpcs-wp-super-cache-plugin.php' ) ) {
			return 1;
		}

		return 2;
	}

	function display_options() {
		global $wp_rewrite;

		if ( ! empty( $_POST['wpcso_uninstall_nonce'] ) ) {
			delete_option( 'wpcs_options' );
			update_option( 'rewrite_rules', '' );
			echo '<div class="wrap"><h2>WP Chinese Switcher Setting</h2><div class="updated">Uninstall Successfully. 卸载成功, 现在您可以到<a href="plugins.php">插件菜单</a>里禁用本插件.</div></div>';

			return;
		} else if ( $this->options === false ) {
			echo '<div class="wrap"><h2>WP Chinese Switcher Setting</h2><div class="error">错误: 没有找到配置信息, 可能由于WordPress系统错误或者您已经卸载了本插件. 您可以<a href="plugins.php">尝试</a>禁用本插件后再重新激活.</div></div>';

			return;
		}

		if ( ! empty( $_POST['toggle_cache'] ) ) {
			if ( $this->get_cache_status() == 1 ) {
				$result = $this->install_cache_module();
				if ( $result ) {
					echo '<div class="updated fade" style=""><p>'._e('安装WP Super Cache 兼容成功.', 'wpchinese-switcher').'</p></div>';
				} else {
					echo '<div class="error" style=""><p>'._e('错误: 安装WP Super Cache 兼容失败', 'wpchinese-switcher').'.</p></div>';
				}
			} else if ( $this->get_cache_status() == 2 ) {
				$result = $this->uninstall_cache_module();
				if ( $result ) {
					echo '<div class="updated fade" style=""><p>'._e('卸载WP Super Cache 兼容成功', 'wpchinese-switcher').'.</p></div>';
				} else {
					echo '<div class="error" style=""><p>'._e('错误: 卸载WP Super Cache 兼容失败', 'wpchinese-switcher').'.</p></div>';
				}
			}
		}

		if ( ! empty( $_POST['wpcso_submitted'] ) ) {
			$this->is_submitted = true;
			$this->process();

			if ( $this->get_cache_status() == 2 ) {
				$this->install_cache_module();
			}
		}
		?>
        <script type="text/javascript">
            //<!--
            function toggleVisibility(id) {
                var e = document.getElementById(id);
                if (!e) return;
                if (e.style.display == "block")
                    e.style.display = "none";
                else
                    e.style.display = "block";
                return false;
            }

            //-->
        </script>
        <div class="wrap">
            <style>
                .tooltip {
                    position: relative;
                    display: inline-block;
                    cursor: pointer;
                }

                .tooltip .tooltiptext {
                    visibility: hidden;
                    width: 400px; /* 宽度可以根据内容调整 */
                    background-color: #555; /* 深灰色背景 */
                    color: #fff; /* 文字颜色 */
                    text-align: center;
                    padding: 5px 10px; /* 上下填充5px，左右填充10px */
                    border-radius: 6px; /* 圆角 */

                    /* 位置 */
                    position: absolute;
                    z-index: 1;
                    bottom: 150%; /* 调整位置确保提示不会与其他元素重叠 */
                    left: 50%;
                    margin-left: -100px; /* 根据宽度调整，保持居中 */

                    /* 箭头效果 */
                    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); /* 阴影效果 */

                    /* 淡入效果 */
                    opacity: 0;
                    transition: opacity 0.3s;
                }

                .tooltip:hover .tooltiptext {
                    visibility: visible;
                    opacity: 1;
                }

                .tooltip::after {
                    content: "?";
                    background-color: #f9f9f9; /* 浅灰色背景 */
                    color: #555; /* 深灰色文字 */
                    padding: 0 5px; /* 左右填充 */
                    border-radius: 50%; /* 圆形 */
                    font-weight: bold; /* 加粗字体 */
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); /* 阴影效果 */
                }


                .tab button {
                    background-color: #f1f1f1;
                    float: left;
                    border: none;
                    outline: none;
                    cursor: pointer;
                    padding: 14px 16px;
                    transition: 0.3s;
                }

                .tab button:hover {
                    background-color: #ddd;
                }

                .tab button.active {
                    background-color: #ccc;
                }

                .tabcontent {
                    display: none;
                    padding: 6px 12px;
                    border: 1px solid #ccc;
                    border-top: none;
                    clear: both; /* 防止内容显示在Tab按钮的右侧 */
                }
            </style>

            <script>
                function openTab(evt, tabName) {
                    var i, tabcontent, tablinks;

                    tabcontent = document.getElementsByClassName("tabcontent");
                    for (i = 0; i < tabcontent.length; i++) {
                        tabcontent[i].style.display = "none";
                    }

                    tablinks = document.getElementsByClassName("tablinks");
                    for (i = 0; i < tablinks.length; i++) {
                        tablinks[i].className = tablinks[i].className.replace(" active", "");
                    }

                    document.getElementById(tabName).style.display = "block";
                    if (evt) evt.currentTarget.className += " active";
                }

                // 当页面加载完成后自动激活Tab1
                document.addEventListener("DOMContentLoaded", function () {
                    // 默认打开Tab1
                    document.querySelector(".tablinks").click();
                });
            </script>


            <div style="padding: 2px 5px 0 0;">Select Admin Language: <?php echo $this->navi(); ?></div>
            <h2>WP Chinese Switcher Settings</h2>
			<?php ob_start(); ?>
			<?php if ( $this->is_submitted && $this->is_success ) { ?>
                <div class="updated fade" style=""><p><?php echo $this->message; ?></p></div>
			<?php } else if ( $this->is_submitted && $this->is_error ) { ?>
                <div class="error" style=""><p><?php echo $this->message; ?></p></div>
			<?php } ?>
            <p><?php printf( __( '版本 %s', 'wpchinese-switcher' ), wpcs_VERSION ); ?>. <a href="https://wenpai.org/" title="<?php _e('文派开源', 'wpchinese-switcher'); ?>"
                                                    target="_blank"><?php _e('文派开源', 'wpchinese-switcher'); ?></a> | <a
                        href="https://wpchinese.cn" target="_blank" title="<?php _e('插件主页', 'wpchinese-switcher'); ?>"><?php _e('插件主页', 'wpchinese-switcher'); ?></a></p>

            <div class="tab">
                <button class="tablinks" onclick="openTab(event, 'Tab1')">
                    <?php echo __('基础设置', 'wpchinese-switcher'); ?>
                </button>
                <button class="tablinks" onclick="openTab(event, 'Tab2')"><?php echo __( '高级设置', 'wpchinese-switcher' ); ?></button>
            </div>

            <div id="Tab1" class="tabcontent">
                <form id="wpcso_form" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"><input type="hidden"
                                                                                                           name="wpcso_submitted"
                                                                                                           value="1"/>
                    <table class="form-table">
                        <tbody>

                        <tr>
                        <td valign="top" width="30%"><?php _e('自定义"不转换"标签名', 'wpchinese-switcher'); ?>
  <span class="tooltip">
    <span class="tooltiptext"><?php _e('本插件输出的widget中将包含当前页面原始版本链接, 您可以在这里自定义其显示的名称. 如果留空则使用默认的"不转换".', 'wpchinese-switcher'); ?></span>
  </span>
</td>
                </span>
                            </td>
                            <td><!--wpcs_NC_START-->
                                <input type="text" style="width: 100px;" name="wpcso_no_conversion_tip"
                                       id="wpcso_no_conversion_tip"
                                       value="<?php echo esc_html( $this->options['nctip'] ); ?>"/><!--wpcs_NC_END-->
                            </td>
                        </tr>

                        <tr>
                        <td valign="top" width="30%"><?php _e('对下面几种中文开启转换功能', 'wpchinese-switcher'); ?>
  <span class="tooltip">
    <span class="tooltiptext"><?php _e('未选中的中文语言将不被使用,此项设置为全局设置。您应该选中至少一种中文语言,否则本插件全部功能都不会工作。在每个复选框后的文本输入框里可以输入该语言自定义名称, 如果留空会使用默认值.', 'wpchinese-switcher'); ?> <!--wpcs_NC_START--><?php _ex('("简体中文", "繁体中文"...)', 'list of languages', 'wpchinese-switcher'); ?><!--wpcs_NC_END--></span>
  </span>
</td>
                            <td><!--wpcs_NC_START-->
								<?php foreach ( $this->langs as $key => $value ) { ?>
                                <input type="checkbox" id="wpcso_variant_<?php echo $key; ?>"
                                       name="wpcso_variant_<?php echo $key; ?>"<?php echo in_array( $key, $this->options['wpcs_used_langs'] ) ? ' checked="checked"' : ''; ?> />
                                <label for="wpcso_variant_<?php echo $key; ?>"><?php $str = $value[2] . ' (' . $key . ')';
									echo str_replace( ' ', '&nbsp;', str_pad( $str, 14 + strlen( $str ) - mb_strlen( $str ) ) ); ?></label>
                                <input type="text" placeholder="<?php esc_attr_e('请输入展示名称(默认值如左)', 'wpchinese-switcher'); ?>"
                                       style="width: 200px;margin-bottom: 5px"
                                       name="<?php echo $this->langs[ $key ][1]; ?>"
                                       value="<?php echo ! empty( $this->options[ $value[1] ] ) ? esc_html( $this->options[ $value[1] ] ) : ''; ?>"/><br/>
								<?php } ?><!--wpcs_NC_END-->
                            </td>
                        </tr>

                        <tr>
                            <td valign="top" width="30%"><?php _e('展示形式', 'wpchinese-switcher'); ?>
                                <span class="tooltip">
                                    <span class="tooltiptext"><?php _e('修改语言切换按钮的展现形式', 'wpchinese-switcher'); ?></span>
                                </span>
                            </td>
                            <td>
								<?php $wpcs_translate_type = $this->options['wpcs_translate_type'] ?? 0 ?>
                                <select id="wpcs_translate_type" value="<?php echo $wpcs_translate_type ?>"
                                        name="wpcs_translate_type" style="width: 250px;">
                                        <option value="0"<?php echo $wpcs_translate_type == 0 ? ' selected="selected"' : ''; ?>>
                                            <?php _e('平铺', 'wpchinese-switcher'); ?>
                                        </option>
                                        <option value="1"<?php echo $wpcs_translate_type == 1 ? ' selected="selected"' : ''; ?>>
                                            <?php _e('下拉列表', 'wpchinese-switcher'); ?>
                                        </option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td scope="row" valign="top" width="30%">
                                <?php _e( '文搜索关键词简繁转换', 'wpchinese-switcher' ) ?>
                                <span class="tooltip">
                                    <span class="tooltiptext">
                                        <?php _e('>本选项将增强WordPress搜索功能, 使其对中文关键词繁简统一处理.
                  例如搜索"<!--wpcs_NC_START--><code>网络</code><!--wpcs_NC_END-->"时, 数据库里含有"
                        <!--wpcs_NC_START--><code>网络</code><!--wpcs_NC_END-->",
                  "<!--wpcs_NC_START--><code>网络</code><!--wpcs_NC_END-->" 和"<!--wpcs_NC_START--><code>网络</code>
                        <!--wpcs_NC_END-->"的文章都会放到搜索结果里返回.
                  支持多个中文词语搜索, 如搜索"<!--wpcs_NC_START--><code>简体 繁体</code><!--wpcs_NC_END-->"时,
                  含有"<!--wpcs_NC_START--><code>简体</code><!--wpcs_NC_END-->"和"<!--wpcs_NC_START--><code>繁体</code>
                        <!--wpcs_NC_END-->"两个词的文章也会被返回.
                  (此功能将增加搜索时数据库负担)', 'wpchinese-switcher'); ?>
                                    </span>
                                </span>
                            </td>
                            <td>
                                <select id="wpcso_search_conversion" name="wpcso_search_conversion" style="width: 250px;">
                                    <option value="2"<?php echo $this->options['wpcs_search_conversion'] == 2 ? ' selected="selected"' : ''; ?>>
                                        <?php echo __( '开启', 'wpchinese-switcher' ); ?>
                                    </option>
                                    <option value="0"<?php echo ( $this->options['wpcs_search_conversion'] != 2 && $this->options['wpcs_search_conversion'] != 1 ) ? ' selected="selected"' : ''; ?>>
                                        <?php echo __( '关闭', 'wpchinese-switcher' ); ?>
                                    </option>
                                    <option value="1"<?php echo $this->options['wpcs_search_conversion'] == 1 ? ' selected="selected"' : ''; ?>>
                                        <?php echo __( '仅当中文语言不是"不转换"时开启(默认值)', 'wpchinese-switcher' ); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td valign="top" width="30%">
                                <?php _e('识别浏览器中文语言动作', 'wpchinese-switcher'); ?>
                                <span class="tooltip">
                                    <span class="tooltiptext">
                                        <b><?php _e('本项设置不会应用于搜索引擎.', 'wpchinese-switcher'); ?></b> 
                                        <?php _e('如果本选项设置不为"关闭", 进程将识别访客浏览器首选中文语言。', 'wpchinese-switcher'); ?>
                                        <?php _e('如果设置为"跳转到对应繁简版本页面", 进程将302重定向到当前页面的访客浏览器首选语言版本。', 'wpchinese-switcher'); ?>
                                        <?php _e('如果设置为"直接显示对应繁简版本内容",进程将直接显示对应中文转换版本内容而不进行重定向。', 'wpchinese-switcher'); ?>
                                        <b><?php _e('如果本选项设置为"直接显示对应繁简版本内容", 必须把下一个选项"使用Cookie保存并识别用户语言偏好"关闭或也设置为直接显示对应繁简版本,否则本插件只会在浏览器第一次访问时直接显示, 其他情况跳转。', 'wpchinese-switcher'); ?></b>
                                        <br/><br/>
                                        <b><?php _e('关于"允许繁简语系内部通用"复选项说明:', 'wpchinese-switcher'); ?></b>
                                        <?php _e('只有当"识别浏览器动作"选项不为"关闭"时该复选框才有效。具体说明，', 'wpchinese-switcher'); ?>
                                        <?php _e('假如您在本页第一项设置里禁用了部分中文,如zh-hk, 那幺浏览器里语言设置里只有"<!--wpcs_NC_START--> 港澳繁体 <!--wpcs_NC_END--> "的用户访问时默认不会被识别;', 'wpchinese-switcher'); ?>
                                        <?php _e('但如果选中了此复选框,只要您开启了"<!--wpcs_NC_START--> 繁体中文<!--wpcs_NC_END--> ", "
                        <!--wpcs_NC_START--> 台湾正体
                        <!--wpcs_NC_END--> "
                  或 "<!--wpcs_NC_START--> 港澳繁体<!--wpcs_NC_END--> " 中任一种语言, 浏览器使用这三种语言的用户都会被本插件识别并根据选项做出动作.', 'wpchinese-switcher'); ?>
                                        <?php _e('(但此时页面被转换后的语言可能并不是用户浏览器设置的那种, 而是您开启的那种繁体语言)。简体语系同理。', 'wpchinese-switcher'); ?>
                                    </span>
                                </span>
                            </td>
                            <td>
                                <select id="wpcso_browser_redirect" value="" name="wpcso_browser_redirect"
                                        style="width: 250px;">
                                    <option value="2"<?php echo $this->options['wpcs_browser_redirect'] == 2 ? ' selected="selected"' : ''; ?>>
                                        <?php _e('直接显示对应繁简版本内容', 'wpchinese-switcher'); ?>
                                    </option>
                                    <option value="1"<?php echo $this->options['wpcs_browser_redirect'] == 1 ? ' selected="selected"' : ''; ?>>
                                        <?php _e('跳转到对应繁简版本页面', 'wpchinese-switcher'); ?>
                                    </option>
                                    <option value="0"<?php echo $this->options['wpcs_browser_redirect'] == 0 ? ' selected="selected"' : ''; ?>>
                                        <?php _e('关闭此功能(默认值)', 'wpchinese-switcher'); ?>
                                    </option>
                                </select> <input type="checkbox" name="wpcso_auto_language_recong"
                                                 id="wpcso_auto_language_recong"
                                                 value=""<?php echo $this->options['wpcs_auto_language_recong'] == 1 ? ' checked="checked"' : ''; ?> /><label
                                        for="wpcso_auto_language_recong"><?php _e('允许繁简语系内部通用', 'wpchinese-switcher'); ?>.</label>
                            </td>
                        </tr>

                        <tr>
                            <td valign="top" width="30%">
                            <?php _e('使用Cookie保存并识别用户语言偏好', 'wpchinese-switcher'); ?>
                                <span class="tooltip">
                    <span class="tooltiptext"><?php _e('<b>本项设置不会应用于搜索引擎.</b> 如果开启这项设置,本插件将自动保存访客的语言选择.举例而言,
                  当用户通过 "', 'wpchinese-switcher'); ?><?php echo $this->options['wpcs_use_permalink'] ?
		                    esc_html( trailingslashit( wpcs_link_conversion( get_option( 'home' ) . '/', 'zh-tw' ) ) ) :
		                    esc_html( wpcs_link_conversion( get_option( 'home' ) . '/', 'zh-tw' ) ); ?>"
                            <?php _e('这个链接访问了您网站的台湾繁体版本时,进程将保存信息到Cookie中. 如果该用户重启浏览器并通过', 'wpchinese-switcher'); ?>
                   "<?php echo get_option( 'home' ); ?>/" 
                   <?php _e('再次访问您网站时,
                  则会被自动跳转到台湾繁体版本的地址. 如果设置为"直接显示对应繁简版本",则不会进行跳转.
                  (参见上一项的说明)<br/><br/><b>本选项和上一项"识别浏览器"选项与缓存插件不兼容</b>.如果您使用了WP Super Cache/ Hyper Cache之类Cache插件,
                  请把这两项设置均设为"关闭",否则这两个功能不但不会正常工作, 而且可能造成缓存异常.</span>
                </span>', 'wpchinese-switcher'); ?>
                            </td>
                            <td>
                                <select id="wpcso_use_cookie_variant" value="" name="wpcso_use_cookie_variant"
                                        style="width: 250px;">';
                                    <option value="2"<?php echo $this->options['wpcs_use_cookie_variant'] == 2 ? ' selected="selected"' : ''; ?>>
                                        <?php _e('直接显示对应繁简版本内容', 'wpchinese-switcher'); ?>
                                    </option>
                                    <option value="1"<?php echo $this->options['wpcs_use_cookie_variant'] == 1 ? ' selected="selected"' : ''; ?>>
                                    <?php _e('跳转到对应繁简版本页面', 'wpchinese-switcher'); ?>
                                    </option>
                                    <option value="0"<?php echo $this->options['wpcs_use_cookie_variant'] == 0 ? ' selected="selected"' : ''; ?>>
                                    <?php _e('关闭此功能(默认值)', 'wpchinese-switcher'); ?>
                                    </option>
                                </select>
                                <input type="checkbox" name="wpcso_auto_language_recong"
                                    id="wpcso_auto_language_recong"
                                    value=""<?php echo $this->options['wpcs_auto_language_recong'] == 1 ? ' checked="checked"' : ''; ?> />
                                <label for="wpcso_auto_language_recong"><?php _e('允许繁简语系内部通用', 'wpchinese-switcher'); ?>.</label>
                            </td>
                        </tr>

                        <tr>
                            <td valign="top" width="30%">
                                <?php _e('不转换文章中某些HTML标签里中文', 'wpchinese-switcher'); ?>
                                <span class="tooltip">
                                    <span class="tooltiptext">
                                        <?php _e('这里输入的HTML标签里内容将不进行中文繁简转换(仅适用文章内容), 保持原样输出。请输入HTML标签名,', 'wpchinese-switcher'); ?>
                                        <?php _e('如', 'wpchinese-switcher'); ?> <code>pre</code>;
                                        <?php _e('多个HTML标签之间以', 'wpchinese-switcher'); ?> <code>,</code> <?php _e('分割, 如', 'wpchinese-switcher'); ?> <code>pre,code</code>. 
                                        <?php _e('支持部分基本的', 'wpchinese-switcher'); ?><a href="http://www.w3schools.com/cssref/css_selectors.asp" target="_blank"><?php _e('CSS选择器', 'wpchinese-switcher'); ?></a><?php _e('的DOM筛选语法，如', 'wpchinese-switcher'); ?><code>div.nocc</code>,
                                        <code>.class1,div#para1</code>, <code>table,span.nocc,div[attr="hello"]</code>. 
                                        <?php _e('如果遇到html错误, 请关闭此选项.', 'wpchinese-switcher'); ?>
                                    </span>
                                </span>
                            </td>
                            <td>
                                <input type="text" value="<?php echo esc_attr($this->options['wpcs_no_conversion_tag']); ?>"
                                    style="width: 250px;"
                                    name="wpcso_no_conversion_tag" id="wpcso_no_conversion_tag"/> (<?php _e('默认空', 'wpchinese-switcher'); ?>)
                            </td>
                        </tr>

                        <tr>
                            <td valign="top" width="30%">
                                <?php _e('不转换日语(lang="ja")的HTML标签里内容', 'wpchinese-switcher'); ?>
                                <span class="tooltip">
                                    <span class="tooltiptext">
                                        <?php _e('如果选中此选项, 文章内容中用 lang="ja" 标记为日本语的html tag将不进行繁简转换, 保持原样输出。', 'wpchinese-switcher'); ?>
                                        <?php _e('例如:', 'wpchinese-switcher'); ?> "<!--wpcs_NC_START--><code lang="ja">&lt;span lang="ja"&gt;あなたを、おつれしましょうか？ この町の願いが叶う場所に。&lt;/span&gt;</code><!--wpcs_NC_END-->"
                                        <?php _e('中的CJK汉字', 'wpchinese-switcher'); ?> <!--wpcs_NC_START--><code lang="ja">連</code><!--wpcs_NC_END--> <?php _e('和', 'wpchinese-switcher'); ?> <!--wpcs_NC_START--><code lang="ja">叶</code><!--wpcs_NC_END--> <?php _e('将不会进行繁简转换。', 'wpchinese-switcher'); ?>
                                        <?php _e('如果遇到html错误, 请关闭此选项。', 'wpchinese-switcher'); ?>
                                    </span>
                                </span>
                            </td>
                            <td>
                                <input type="checkbox" name="wpcso_no_conversion_ja" id="wpcso_no_conversion_ja" <?php echo !empty($this->options['wpcs_no_conversion_ja']) ? ' checked="checked"' : ''; ?> />
                                <label for="wpcso_no_conversion_ja"><?php _e('(默认关闭)', 'wpchinese-switcher'); ?></label>
                            </td>
                        </tr>

                        <tr>
                            <td valign="top" width="30%"><?php _e('不转换HTML中任意内容TAG', 'wpchinese-switcher'); ?>
                                <span class="tooltip">
                                    <span class="tooltiptext"><?php _e('HTML中所有位于 <!--wpcs_NC_START--> 和 <!--wpcs_NC_END-->之间的内容将不会进行繁简转换,
                                            保持原样输出.
                                            您可以在模板或post内容中使用这个标签.<br/>您可以选择在WordPress的文章编辑器(html模式)工具栏中插入一个按钮(显示为"wpcs_NC"), 方便快速在文章中插入这个标签.', 'wpchinese-switcher'); ?></span>
                                </span>
                            </td>
                            <td>
                                <!--wpcs_NC_START--><code>&lt;!--wpcs_NC_START--&gt;爱与正义, 剑与魔法, 光荣与梦想&lt;!--wpcs_NC_END--&gt;</code>
                                <!--wpcs_NC_END--><br/>
                                <input type="checkbox" name="wpcso_no_conversion_qtag"
                                    id="wpcso_no_conversion_qtag" <?php checked( ! empty( $this->options['wpcs_no_conversion_qtag'] ) ); ?> />
                                <label for="wpcso_no_conversion_qtag"><?php _e('在WordPress文章html编辑器中添加"不转换中文"的Quick tag', 'wpchinese-switcher'); ?></label>
                            </td>
                        </tr>

                        <tr>
                            <td valign="top" width="30%">
                                <?php _e('繁简转换页面永久链接格式', 'wpchinese-switcher'); ?>
                                <span class="tooltip">
                                    <span class="tooltiptext">
                                        <?php _e('更改此项设置前,<b>请仔细阅读下面的说明:</b><br/>本项设置决定插件生成的繁简转换页面Permalink.
                                        默认的形式为您原始Permalink后加上?variant=zh-tw参数.(zh-tw为当前请求的语言代码) .您可以修改这个permalink形式.本插件提供两种非默认的Permalink格式:
                                        您原始的Permalink后加上/zh-tw 或/zh-tw/; 或/zh-tw后加上您原来Permalink. 两种区别在于中文语言代码(zh-tw)附加在您原来Permalink的末尾或开头.
                                        URL末尾是否有 / 取决于您的WordPress永久链接末尾是否有/. 但<b>首页的繁简转换版本URL末尾永远有 "/"</b>. 如果您的WordPress未开启永久链接,
                                        本项设置只能选择第一种URL形式.', 'wpchinese-switcher'); ?>
                                    </span>
                                </span>
                            </td>
                            <td>
                                <label><input type="radio" name="wpcso_use_permalink"
                                              value="0"<?php echo $this->options['wpcs_use_permalink'] == 0 ? ' checked="checked"' : ''; ?> />
                                    <code><?php echo get_option( 'home' ) . ( empty( $wp_rewrite->permalink_structure ) ? '/?p=123&variant=zh-tw' : $wp_rewrite->permalink_structure . '?variant=zh-tw' ); ?></code>
                                    (默认)</label><br/>
                                <label><input type="radio" name="wpcso_use_permalink"
                                              value="1"<?php echo empty( $wp_rewrite->permalink_structure ) ? ' disabled="disabled"' : ''; ?><?php echo $this->options['wpcs_use_permalink'] == 1 ? ' checked="checked"' : ''; ?> />
                                    <code><?php echo get_option( 'home' ) . user_trailingslashit( trailingslashit( $wp_rewrite->permalink_structure ) . 'zh-tw' ) . ( empty( $wp_rewrite->permalink_structure ) ? '/' : '' ); ?></code></label><br/>
                                <label><input type="radio" name="wpcso_use_permalink"
                                              value="2"<?php echo empty( $wp_rewrite->permalink_structure ) ? ' disabled="disabled"' : ''; ?><?php echo $this->options['wpcs_use_permalink'] == 2 ? ' checked="checked"' : ''; ?> />
                                    <code><?php echo get_option( 'home' ) . '/zh-tw' . $wp_rewrite->permalink_structure . ( empty( $wp_rewrite->permalink_structure ) ? '/' : '' ); ?></code></label><br/>
                            </td>
                        </tr>

                        <tr>
                            <td valign="top" width="30%">
                                <?php _e('对页面内容整体转换', 'wpchinese-switcher'); ?>
                                <span class="tooltip">
                                    <span class="tooltiptext">
                                        <?php _e('开启此选项后,插件将对WordPress输出的全部页面内容进行中文整体转换(使用ob_start和ob_flush函数),
                                        这将极大提高页面生成速度并减少资源使用.但也可能造成意想不到问题.如果遇到异常(包括中文转换错误, HTML页面错误或php错误等),请关闭此选项.', 'wpchinese-switcher'); ?>
                                    </span>
                                </span>
                            </td>
                            <td>
                                <input type="checkbox" id="wpcso_use_fullpage_conversion"
                                    name="wpcso_use_fullpage_conversion"<?php checked( $this->options['wpcs_use_fullpage_conversion'], 1 ); ?> />
                                <label for="wpcso_use_fullpage_conversion"><?php _e('(默认开启)', 'wpchinese-switcher'); ?></label>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <input class="button" type="submit" name="submit" value="<?php esc_attr_e('保存选项', 'wpchinese-switcher'); ?>"/>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </div>

            <div id="Tab2" class="tabcontent">
				<?php
				$cache_status = $this->get_cache_status();
				?>
                <div id="wpcs_block_cache" style="display: <?php
				echo ( $cache_status != 0 ) ? 'block' : 'none';
				?>;">
                    <div style="padding-top: 30px; padding-bottom: 20px; ">
                        <b><?php _e('WP Super Cache兼容', 'wpchinese-switcher'); ?>
                            <span class="tooltip">
                                <span class="tooltiptext">
                                    <?php _e('默认情况下, 本插件的"识别浏览器中文语言动作"和"使用Cookie保存并识别用户语言偏好"这两个功能与缓存插件不兼容. 如果您使用的是', 'wpchinese-switcher'); ?><a href="http://wordpress.org/extend/plugins/wp-super-cache/"><?php _e('WP Super Cache', 'wpchinese-switcher'); ?></a><?php _e('的"Legacy page caching"缓存模式, 您可以点击下面的按钮安装WP Super Cache兼容. ', 'wpchinese-switcher'); ?><b><?php _e('注意: 如果您没有开启"识别浏览器中文语言动作"和"使用Cookie保存并识别用户语言偏好"这两个功能(默认均为关闭), 则无需安装此兼容', 'wpchinese-switcher'); ?></b>; <?php _e('安装本兼容将增加WP Super Cache的缓存磁盘空间占用; ', 'wpchinese-switcher'); ?><b><?php _e('仅支持WP Super Cache插件的"Legacy page caching"模式, 不支持"PHP Cache"和"mod_rewrite cache"模式. (WP Super Cache 插件安装后默认为"PHP Cache"模式, 您必须手动切换到"Legacy"模式.)', 'wpchinese-switcher'); ?></b>
                                </span>
                            </span>
                        </b>
                    </div>
                    <form id="wpcso_cache_form" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <td valign="top" width="30%"><?php
                                    if ( $cache_status == 2 ) {
                                        echo '<div style="font-weight: solid; width: 50px; color: green; border: 1px solid #333; margin: 2px; padding: 5px">' . __("已安装", "wpchinese-switcher") . '</div>';
                                    } else if ( empty( $this->options['wpcs_browser_redirect'] ) && empty( $this->options['wpcs_use_cookie_variant'] ) ) {
                                        echo '<div style="color: green; font-weight: solid; width: 350px; border: 1px solid #333; margin: 2px; padding: 5px">' . __("未开启\"识别浏览器中文语言动作\"和\"使用Cookie保存并识别用户语言偏好\"功能. 无需安装", "wpchinese-switcher") . '</div>';
                                    } else {
                                        echo '<div style="font-weight: solid; width: 50px; color: red; border: 1px solid #333; margin: 2px; padding: 5px">' . __("未安装", "wpchinese-switcher") . '</div>';
                                    }
                                    ?></td>
                                <td><?php
                                    if ( $cache_status == 2 ) {
                                        if ( empty( $GLOBALS['cache_enabled'] ) ) {
                                            echo '<div style="font-weight: solid; border: 1px solid #333; margin: 2px; padding: 5px">' . __("WP Super Cache未开启缓存", "wpchinese-switcher") . '</div>';
                                        } else if ( ! empty( $GLOBALS['super_cache_enabled'] ) ) {
                                            echo '<div style="color: red; font-weight: solid; border: 1px solid #333; margin: 2px; padding: 5px">' . __("警告: WP Super Cache未设为\"legacy page caching\"模式, 本兼容模块无法正常工作.", "wpchinese-switcher") . '</div>';
                                        }
                                    }
                                    ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="hidden" name="toggle_cache" value="1"/>
                                    <input class="button" type="submit" name="submit" value="<?php
                                    if ( $this->get_cache_status() == 0 ) {
                                        echo __("未使用WP Super Cache插件", "wpchinese-switcher");
                                    } else if ( $this->get_cache_status() == 1 ) {
                                        echo __("安装兼容", "wpchinese-switcher");
                                    } else {
                                        echo __("卸载兼容", "wpchinese-switcher");
                                    }
                                    ?>" <?php
                                    if ( $this->get_cache_status() == 0 || ( empty( $this->options['wpcs_browser_redirect'] ) && empty( $this->options['wpcs_use_cookie_variant'] ) ) ) {
                                        echo 'disabled="disabled"';
                                    }
                                    ?> />
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </form>
                </div>

                <div style="padding-top: 30px;padding-bottom: 20px;"><b><?php _e('卸载本插件:', 'wpchinese-switcher'); ?></b></div>
                <form id="wpcso_uninstall_form" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <td valign="top" width="30%"><?php _e('确定卸载本插件?', 'wpchinese-switcher'); ?>
                                <span class="tooltip">
                                    <span class="tooltiptext">
                                        <?php _e('这将清除数据库options表中本插件的设置项(键值为wpcs_options), 提交后还需要到wordpress插件管理菜单里禁用本插件.', 'wpchinese-switcher'); ?>
                                    </span>
                                </span>
                            </td>
                            <td>
                                <input type="checkbox" name="wpcso_uninstall_nonce" id="wpcso_uninstall_nonce"
                                        value="1"/>
                                <label for="wpcso_uninstall_nonce"><?php _e('确认卸载 (此操作不可逆)', 'wpchinese-switcher'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input class="button" type="submit" name="submit" value="<?php _e('卸载插件', 'wpchinese-switcher'); ?>"/>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div> <!-- close wrap div -->
		<?php
		$o = ob_get_clean();
		if ( $this->admin_lang ) {
			wpcs_load_conversion_table();
			$o = limit_zhconversion( $o, $this->langs[ $this->admin_lang ][0] );
		}
		echo $o;
	}

	function navi() {
		$variant = ! empty( $_GET['variant'] ) ? $_GET['variant'] : '';
		$str     = '<span><a title="'._e('默认/ 默认', 'wpchinese-switcher').'" href="' . $this->url . '" ' . ( ! $variant ? 'style="color: #464646; text-decoration: none !important;"' : '' ) . ' >'._e('默认/ 默认', 'wpchinese-switcher').'</a></span>&nbsp;';
		if ( ! $this->options['wpcs_used_langs'] ) {
			return $str;
		}
		foreach ( $this->langs as $key => $value ) {
			$str .= '<span><a href="' . $this->url . '&variant=' . $key . '" title="' . $value[2] . '" ' . ( $variant == $key ? 'style="color: #464646; text-decoration: none !important;"' : '' ) . '>' . $value[2] . '</a>&nbsp;</span>';
		}

		return $str;
	}

	function process() {
		global $wp_rewrite, $wpcs_options;
		$langs = array();
		foreach ( $this->langs as $key => $value ) {
			if ( isset( $_POST[ 'wpcso_variant_' . $key ] ) ) {
				$langs[] = $key;
			}
		}
		$options = array(
			'wpcs_used_langs'              => $langs,
			'wpcs_search_conversion'       => intval( $_POST['wpcso_search_conversion'] ),
			'wpcs_browser_redirect'        => intval( $_POST['wpcso_browser_redirect'] ),
			'wpcs_translate_type'          => ( isset( $_POST['wpcs_translate_type'] ) ? intval( $_POST['wpcs_translate_type'] ) : 0 ),
			// 0列表展示，1下拉展示
			'wpcs_use_cookie_variant'      => intval( $_POST['wpcso_use_cookie_variant'] ),
			'wpcs_use_fullpage_conversion' => ( isset( $_POST['wpcso_use_fullpage_conversion'] ) ? 1 : 0 ),
			'wpcs_trackback_plugin_author' => ( isset( $_POST['wpcso_trackback_plugin_author'] ) ? intval( $_POST['wpcso_trackback_plugin_author'] ) : 0 ),
			'wpcs_add_author_link'         => ( isset( $_POST['wpcso_add_author_link'] ) ? 1 : 0 ),
			'wpcs_use_permalink'           => intval( $_POST['wpcso_use_permalink'] ),
			'wpcs_auto_language_recong'    => ( isset( $_POST['wpcso_auto_language_recong'] ) ? 1 : 0 ),
			'wpcs_no_conversion_tag'       => trim( $_POST['wpcso_no_conversion_tag'], " \t\n\r\0\x0B,|" ),
			'wpcs_no_conversion_ja'        => ( isset( $_POST['wpcso_no_conversion_ja'] ) ? 1 : 0 ),
			'wpcs_no_conversion_qtag'      => ( isset( $_POST['wpcso_no_conversion_qtag'] ) ? 1 : 0 ),
			'nctip'                        => trim( $_POST['wpcso_no_conversion_tip'] ),
		);

		foreach ( $this->langs as $lang => $value ) {
			if ( ! empty( $_POST[ $value[1] ] ) ) {
				$options[ $value[1] ] = trim( $_POST[ $value[1] ] );
			}
		}

		if ( $this->get_cache_status() == 2 && empty( $options['wpcs_browser_redirect'] ) && empty( $options['wpcs_use_cookie_variant'] ) ) {
			$this->uninstall_cache_module();
		}

		if ( $options['wpcs_trackback_plugin_author'] == 1 ) {
			$options['wpcs_trackback_plugin_author'] = $this->trackback();
			if ( $options['wpcs_trackback_plugin_author'] == 2 ) {
				$this->message .= '已成功向<a href="https://oogami.name/project/wpcs/" target="_blank">插件主页</a>发送Trackback。感谢您的支持。<br />';
			} else {
				$this->message .= '向<a href="https://oogami.name/project/wpcs/" target="_blank">插件主页</a>发送Trackback失败，您可以尝试重新提交。<br />';
			}
		}

		if ( $options['wpcs_add_author_link'] == 1 && $this->options['wpcs_add_author_link'] == 0 ) {
			if ( $options['wpcs_add_author_link'] = wp_insert_link( array(
				'link_target'      => '_blank',
				'link_description' => '小野大神的 Blog，关注文明与幻想。',
				'link_rss'         => 'https://oogami.name/feed/',
				'link_name'        => '小野大神',
				'link_url'         => 'https://oogami.name/'
			) ) ) {
				$this->message .= '已成功添加<a href="https://oogami.name/" target="_blank">插件作者</a>的主页链接。感谢您的支持<br />';
			} else {
				$options['wpcs_add_author_link'] = 0;
				$this->message                   .= '添加插件作者主页链接失败。请重试。<br />';
			}
		} else if ( $options['wpcs_add_author_link'] == 0 && $this->options['wpcs_add_author_link'] != 0 ) {
			if ( wp_delete_link( $this->options['wpcs_add_author_link'] ) ) {
				$this->message .= '已删除插件作者的主页链接。<br />';
			}
		}

		$wpcs_options = $options; //因为可能需要刷新rewrite规则, 必须立即更新wpcs_options全局变量
		if ( $this->options['wpcs_use_permalink'] != $options['wpcs_use_permalink'] ||
		     ( $this->options['wpcs_use_permalink'] != 0 && $this->options['wpcs_used_langs'] != $options['wpcs_used_langs'] )
		) {
			if ( ! has_filter( 'rewrite_rules_array', 'wpcs_rewrite_rules' ) ) {
				add_filter( 'rewrite_rules_array', 'wpcs_rewrite_rules' );
			}
			$wp_rewrite->flush_rules();
		}

		update_option( 'wpcs_options', $options );

		$this->options    = $options;
		$this->is_success = true;
		$this->message    .= '<br />设置已更新。';
	}

	function trackback() {
		$options            = array();
		$options['timeout'] = 4;
		$options['body']    = array(
			'title'     => 'WPCS Trackback',
			'url'       => get_option( 'home' ) . '/',
			'blog_name' => get_option( 'blogname' ),
			'excerpt'   => 'Hello, I am using the WP Chinese Switcher Plugin Version ' . wpcs_VERSION
		);
		$response           = @wp_remote_post( 'https://oogami.name/project/wpcs/trackback/', $options );
		$message            = 'WPCS Version ' . wpcs_VERSION . "\n";
		$message            .= get_option( 'home' ) . '/';
		@wp_mail( 'support@feibisi.com', 'WPCS Feedback - ' . get_option( 'blogname' ), $message );

		if ( is_wp_error( $response ) ) {
			return 0;
		}

		return 2;
	}

}
