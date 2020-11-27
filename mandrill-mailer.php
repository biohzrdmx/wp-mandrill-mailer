<?php
	/**
	 * Plugin Name: Mandrill Mailer
	 * Description: Send transactional email using Mandrill easily and without hassle
	 * Author: biohzrdmx
	 * Version: 1.0
	 * Plugin URI: http://github.com/biohzrdmx/wp-mandrill-mailer
	 * Author URI: http://github.com/biohzrdmx/
	 */

	if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	if( ! class_exists('MandrillMailer') ) {

		/**
		 * MandrillMessage class
		 */
		class MandrillMessage {
			public $subject;
			public $from;
			public $to;
			public $contents;
			public $template;
			public $attachments;
			public $images;
			public $replacements;
			/**
			 * Default constructor
			 */
			function __construct() {
				$this->subject = '';
				$this->from = '';
				$this->to = '';
				$this->contents = '';
				$this->template = '';
				$this->attachments = array();
				$this->images = array();
			}
			/**
			 * MandrillMessage factory class for chaining
			 * @return object The newly-created MandrillMessage instance
			 */
			static function newInstance() {
				$new = new self();
				return $new;
			}
			/**
			 * Set message subject
			 * @param string $subject Message subject
			 */
			public function setSubject($subject) {
				$this->subject = $subject;
				return $this;
			}
			/**
			 * Set message sender
			 * @param array $from Array of (name => email) pairs or email addresses
			 */
			public function setFrom($from) {
				$this->from = $from;
				return $this;
			}
			/**
			 * Set message destination
			 * @param array $from Array of (name => email) pairs or email addresses
			 */
			public function setTo($to) {
				$this->to = $to;
				return $this;
			}
			/**
			 * Set email contents
			 * @param string $contents Message contents (html or plain-text)
			 */
			public function setContents($contents) {
				$this->contents = $contents;
				return $this;
			}
			/**
			 * Set template path, expects a file-system path
			 * @param string $template Path to the template file
			 */
			public function setTemplate($template) {
				$this->template = $template;
				return $this;
			}
			/**
			 * Set message attachments
			 * @param array $attachments Array of (name => path) pairs
			 */
			public function setAttachments($attachments) {
				$this->attachments = $attachments;
				return $this;
			}
			/**
			 * Set message images
			 * @param array $images Array of (name => path) pairs
			 */
			public function setImages($images) {
				$this->images = $images;
				return $this;
			}
			/**
			 * Set message replacements for your template using shortcodes
			 * @param array $replacements Array of (shortcode => value) pairs
			 */
			public function setReplacements($replacements) {
				$this->replacements = $replacements;
				return $this;
			}
		}

		/**
		 * MandrillMailer class
		 */
		class MandrillMailer {

			public static function actionAdminMenu() {
				add_menu_page('Mandrill Mailer', 'Mandrill Mailer', 'manage_options', 'mandrill_mailer', 'MandrillMailer::callbackAdminPage', 'dashicons-email-alt');
			}

			public static function actionAdminInit() {
				register_setting( 'mandrill_mailer', 'mandrill_mailer_options' );
				add_settings_section( 'mandrill_mailer_settings', __( 'General settings', 'mandrill_mailer' ), 'MandrillMailer::callbackSettings', 'mandrill_mailer' );
				add_settings_field( 'mandrill_mailer_field_key', __('Secret key', 'mandrill_mailer'), 'MandrillMailer::fieldPassword', 'mandrill_mailer', 'mandrill_mailer_settings', [ 'label_for' => 'mandrill_mailer_field_key', 'class' => 'mandrill_mailer_row' ] );
				add_settings_field( 'mandrill_mailer_field_pool', __('Pool', 'mandrill_mailer'), 'MandrillMailer::fieldText', 'mandrill_mailer', 'mandrill_mailer_settings', [ 'label_for' => 'mandrill_mailer_field_pool', 'class' => 'mandrill_mailer_row', 'default' => 'Main Pool' ] );
			}

			public static function adminSettingsLink($links, $file) {
				$links = (array) $links;
				if ( $file === 'wp-mandrill-mailer/mandrill-mailer.php' && current_user_can( 'manage_options' ) ) {
					$url = admin_url('admin.php?page=mandrill_mailer');
					$link = sprintf( '<a href="%s">%s</a>', $url, __( 'Settings', 'mandrill_mailer' ) );
					array_unshift($links, $link);
				}
				return $links;
			}

			public static function fieldPassword($args) {
				$options = get_option( 'mandrill_mailer_options' );
				?>
					<input type="password" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="mandrill_mailer_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo esc_html( isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : '' ); ?>" autocomplete="new-password">
				<?php
			}

			public static function fieldText($args) {
				$options = get_option( 'mandrill_mailer_options' );
				?>
					<input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="mandrill_mailer_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo esc_html( isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : $args['default'] ); ?>">
				<?php
			}

			public static function callbackAdminPage() {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				if ( isset( $_GET['settings-updated'] ) ) {
					add_settings_error( 'mandrill_mailer_messages', 'mandrill_mailer_message', __( 'Settings Saved', 'mandrill_mailer' ), 'updated' );
				}
				settings_errors( 'mandrill_mailer_messages' );
				?>
					<div class="wrap">
						<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
						<form action="options.php" method="post">
							<?php
							settings_fields( 'mandrill_mailer' );
							do_settings_sections( 'mandrill_mailer' );
							submit_button( __('Save Settings', 'mandrill_mailer') );
							?>
						</form>
					</div>
				<?php
			}

			public static function callbackSettings() {
				?>
					<p><?php esc_html_e('Configure here your Mandrill parameters.'); ?></p>
				<?php
			}

			/**
			 * Get an item from an array/object, or a default value if it's not set
			 * @param  mixed $var      Array or object
			 * @param  mixed $key      Key or index, depending on the array/object
			 * @param  mixed $default  A default value to return if the item it's not in the array/object
			 * @return mixed           The requested item (if present) or the default value
			 */
			protected static function getItem($var, $key, $default = '') {
				return is_object($var) ?
					( isset( $var->$key ) ? $var->$key : $default ) :
					( isset( $var[$key] ) ? $var[$key] : $default );
			}

			/**
			 * Send a message using the Mandrill transactional email service
			 * @param  object $message  The MailerMessage instance
			 * @return boolean          True if the message was sent, False otherwise
			 */
			static function send($message) {
				$ret = false;
				# Build arrays
				$to = array();
				$global_vars = self::getItem($options, 'vars', array());
				$vars = array();
				if (! is_array($message->to) ) {
					$message->to = array($message->to);
				}
				if ( $message->to ) {
					foreach ($message->to as $key => $value) {
						$name = '';
						$email = '';
						$usr_vars = array();
						$extra = array();
						# Check destination type
						if ( is_object($value) ) {
							# Item is an user object
							$email = $value->email;
							$name = "{$value->first_name} {$value->last_name}";
							# Include additional merge vars
							if ( isset($value->vars) ) {
								foreach ($value->vars as $code => $content) {
									$extra[] = array(
										'name' => $code,
										'content' => $content
									);
								}
							}
						} else {
							# Item is an 'email' => 'name' array
							$email = $key;
							$name = $value;
						}
						# Add destination
						$to[] = array(
							'email' => $email,
							'name' => $name,
							'type' => 'to'
						);
						# Build merge vars
						$usr_vars = array(
							array(
								'name' => 'email',
								'content' => $email
							),
							array(
								'name' => 'name',
								'content' => $name
							)
						);
						$usr_vars = array_merge($usr_vars, $extra);
						# Add merge vars
						$vars[] = array(
							'rcpt' => $email,
							'vars' => $usr_vars
						);
					}
				}
				$subject = $message->subject;
				$from_email = key($message->from);
				$from_name = $message->from[$from_email];
				$headers = self::getItem($options, 'headers', array());
				$contents = $message->contents;
				# Load template
				$template = $message->template;
				if ($template) {
					$html = file_get_contents( $template );
					$html = str_replace('%email-site%', home_url('/'), $html);
					$html = str_replace('%email-body%', $contents, $html);

					if($message->replacements) {
						foreach ($message->replacements as $shortcode => $value) {
							$html = str_replace($shortcode, $value, $html);
						}
					}
				} else {
					$html = $contents;
				}
				# Attachments
				$attachments = array();
				foreach ($message->attachments as $name => $attachment) {
					# File must exist
					if (! file_exists($attachment) ) continue;
					# Determine (guess by extension) mime type
					$ext = strtolower( substr( $attachment, strrpos($attachment, '.') + 1 ) );
					switch ($ext) {
						case 'gif':
						case 'png':
							$mime = "image/{$ext}";
						case 'jpg':
						case 'jpeg':
							$mime = 'image/jpeg';
							break;
						case 'mpeg':
						case 'mp4':
						case 'ogg':
						case 'webm':
							$mime = "video/{$ext}";
							break;
						case 'pdf':
						case 'zip':
							$mime = "application/{$ext}";
							break;
						case 'csv':
						case 'xml':
							$mime = "text/{$ext}";
							break;
						default:
							$mime = 'application/octet-stream';
					}
					# Add to attachments array
					$attachments[] = array(
						'type' => $mime,
						'name' => $name,
						'content' => base64_encode( file_get_contents($attachment) )
					);
				}
				# Images
				$images = array();
				foreach ( $message->images as $name => $image) {
					# File must exist
					if (! file_exists($image) ) continue;
					# Determine (guess by extension) mime type
					$ext = strtolower( substr( $image, strrpos($image, '.') + 1 ) );
					switch ($ext) {
						case 'gif':
						case 'png':
							$mime = "image/{$ext}";
						case 'jpg':
						case 'jpeg':
							$mime = 'image/jpeg';
							break;
					}
					# Add to images array
					$images[] = array(
						'type' => $mime,
						'name' => $name,
						'content' => base64_encode( file_get_contents($image) )
					);
				}
				# Include library
				$dir = plugin_dir_path( __FILE__ );
				include_once "{$dir}/lib/Mandrill.php";
				#
				$options = get_option( 'mandrill_mailer_options' );
				#
				try {
					$mandrill = new Mandrill( self::getItem($options, 'mandrill_mailer_field_key', '') );
					$message = array(
						'html' => $html,
						'text' => strip_tags($contents),
						'subject' => $subject,
						'from_email' => $from_email,
						'from_name' => $from_name,
						'to' => $to,
						'headers' => $headers,
						'attachments' => $attachments,
						'important' => true,
						'track_opens' => true,
						'track_clicks' => true,
						'merge' => true,
						'preserve_recipients' => false,
						'global_merge_vars' => $global_vars,
						'merge_vars' => $vars,
						'inline_css' => true,
						'images' => $images
					);
					# Use SSL always
					curl_setopt($mandrill->ch, CURLOPT_SSL_VERIFYHOST, 2);
					curl_setopt($mandrill->ch, CURLOPT_SSL_VERIFYPEER, true);
					curl_setopt($mandrill->ch, CURLOPT_CAINFO, "{$dir}/cacert.pem");
					#
					$async = self::getItem($options, 'mandrill_mailer_field_async', false);
					$ip_pool = self::getItem($options, 'mandrill_mailer_field_pool', 'Main Pool');
					$send_at = '';
					$result = $mandrill->messages->send($message, $async, $ip_pool, $send_at);
					$ret = true;
				} catch(Mandrill_Error $e) {
					echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
					throw $e;
				}
				return $ret;
			}
		}

		add_action( 'admin_init', 'MandrillMailer::actionAdminInit' );
		add_action( 'admin_menu', 'MandrillMailer::actionAdminMenu' );
		add_filter( 'plugin_action_links', 'MandrillMailer::adminSettingsLink', 10, 5 );
	}
?>