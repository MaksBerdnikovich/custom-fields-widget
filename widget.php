<?php

namespace TheGem_Elementor\Widgets\TheGem_Custom_Fields;

use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Plugin;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Repeater;
use Elementor\Icons_Manager;

if (!defined('ABSPATH')) exit;

/**
 * Elementor widget for Portfolio Info.
 */
class TheGem_Custom_Fields extends Widget_Base
{
	public function __construct($data = [], $args = null)
	{
		$template_type = isset($GLOBALS['thegem_template_type']) ? $GLOBALS['thegem_template_type'] : thegem_get_template_type(get_the_ID());
		$this->is_loop_builder_template = $template_type === 'loop-item';

		if (isset($data['settings']) && (empty($_REQUEST['action']) || !in_array($_REQUEST['action'], array('thegem_importer_process', 'thegem_templates_new', 'thegem_blocks_import')))) {
			if (!isset($data['settings']['skin'])) {
				if ($this->is_loop_builder_template) {
					$data['settings']['skin'] = 'table';
				} else {
					$data['settings']['skin'] = 'modern';
				}
			}

			if (!isset($data['settings']['list_alignment'])) {
				if ($this->is_loop_builder_template) {
					$data['settings']['list_alignment'] = 'justify';
				} else {
					$data['settings']['list_alignment'] = 'left';
				}
			}

			if (!isset($data['settings']['label_text_style'])) {
				if ($this->is_loop_builder_template) {
					$data['settings']['label_text_style'] = 'title-text-body-tiny';
				} else {
					$data['settings']['label_text_style'] = '';
				}
			}

			if (!isset($data['settings']['value_text_style'])) {
				if ($this->is_loop_builder_template) {
					$data['settings']['value_text_style'] = 'title-text-body-tiny';
				} else {
					$data['settings']['value_text_style'] = '';
				}
			}
		}

		parent::__construct($data, $args);

		if (!defined('THEGEM_ELEMENTOR_WIDGET_CUSTOM_FIELDS_DIR')) {
			define('THEGEM_ELEMENTOR_WIDGET_CUSTOM_FIELDS_DIR', rtrim(__DIR__, ' /\\'));
		}

		if (!defined('THEGEM_ELEMENTOR_WIDGET_CUSTOM_FIELDS_URL')) {
			define('THEGEM_ELEMENTOR_WIDGET_CUSTOM_FIELDS_URL', rtrim(plugin_dir_url(__FILE__), ' /\\'));
		}

		wp_register_style('custom-fields-css', THEGEM_ELEMENTOR_WIDGET_CUSTOM_FIELDS_URL . '/assets/css/custom-fields.css');
	}

	/**
	 * Retrieve the widget name.
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'thegem-custom-fields';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return __('Custom Fields', 'thegem');
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return str_replace('thegem-', 'thegem-eicon thegem-eicon-', $this->get_name());
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories()
	{
		if (get_post_type() === 'thegem_templates' && thegem_get_template_type(get_the_id()) === 'single-post') {
			return ['thegem_single_post_builder'];
		}

		return ['thegem_elements'];
	}

	public function get_style_depends()
	{
		return ['custom-fields-css'];
	}

	public function get_script_depends()
	{
		return [];
	}

	/** Show reload button */
	public function is_reload_preview_required()
	{
		return true;
	}

	/** Get widget wrapper */
	public function get_widget_wrapper()
	{
		return 'thegem-te-custom-fields';
	}

	/** Get customize class */
	public function get_customize_class()
	{
		return ' .' . $this->get_widget_wrapper() . ' .custom-fields';
	}

	/** Get custom fields data */
	public function trim_plugins_group_slug($group) {
		preg_match('/_(.*?)_/', $group, $match);

		return str_replace($match[0], '', $group);
	}

	public function check_plugins_group($group) {
		preg_match('/_(.*?)_/', $group, $match);

		return !empty($match) ? $match[1] : $group;
	}

	public function get_edit_template_type()
	{
		$type = get_post_type(get_the_ID());

		if (thegem_get_template_type(get_the_ID()) === 'single-post' || thegem_get_template_type(get_the_ID()) === 'loop-item') {
			$type = 'post';
		}

		if (thegem_get_template_type(get_the_ID()) === 'portfolio') {
			$type = 'thegem_pf_item';
		}

		if (thegem_get_template_type(get_the_ID()) === 'single-product') {
			$type = 'product';
		}

		return $type;
	}

	public function get_post_types()
	{
		$post_types = array();
		foreach (get_post_types(array('public' => true), 'object') as $slug => $post_type) {
			if (!in_array($slug, array('thegem_news', 'thegem_footer', 'thegem_title', 'thegem_templates', 'attachment'), true)) {
				$post_types[$slug] = $post_type->label;
			}
		}

		return $post_types;
	}

	public function get_fields_by_post_type($pt)
	{
		$post_type = $pt;
		switch ($pt) {
			case 'page':
				$post_type = 'default';
				break;
			case 'thegem_pf_item':
				$post_type = 'portfolio';
				break;
		}

		if (!function_exists('thegem_theme_options_get_page_settings')) return;

		$pt_data = thegem_theme_options_get_page_settings($post_type);
		$custom_fields = !empty($pt_data['custom_fields']) ? $pt_data['custom_fields'] : null;
		$custom_fields_data = !empty($pt_data['custom_fields_data']) ? json_decode($pt_data['custom_fields_data'], true) : null;
		$data = array();

		if (empty($custom_fields)) return;

		if (!empty($custom_fields_data)) {
			foreach ($custom_fields_data as $field) {
				$data[$field['key']] = $field['title'];
			}
		}

		return $data;
	}

	public function get_acf_plugin_groups()
	{
		if (!thegem_is_plugin_active('advanced-custom-fields/acf.php') && !thegem_is_plugin_active('advanced-custom-fields-pro/acf.php')) return array();

		$groups = array();
		foreach (acf_get_field_groups() as $group) {
			if (!empty($group)) {
				$groups['_acf_' . $group['key']] = $group['title'] . ' (ACF)';
			}
		}

		return $groups;
	}

	public function get_acf_plugin_fields_by_group($gr)
	{
		if (!thegem_is_plugin_active('advanced-custom-fields/acf.php') && !thegem_is_plugin_active('advanced-custom-fields-pro/acf.php')) return array();

		$trim_gr = $this->trim_plugins_group_slug($gr);
		$fields = array();
		foreach (acf_get_fields($trim_gr) as $field) {
			if (!empty($field)) {
				$fields[$field['name']] = $field['label'];
			}
		}

		return $fields;
	}

	public function get_toolset_plugin_groups()
	{
		if (!thegem_is_plugin_active('types/wpcf.php')) return array();

		$groups = array();
		if (!empty(wpcf_admin_fields_get_groups())) {
			foreach (wpcf_admin_fields_get_groups() as $group) {
				if (!empty($group)) {
					$groups['_toolset_' . $group['slug']] = $group['name'] . ' (Toolset)';;
				}
			}
		}

		return $groups;
	}

	public function get_toolset_plugin_fields_by_group($gr, $output = 'list')
	{
		if (!thegem_is_plugin_active('types/wpcf.php')) return array();

		$trim_gr = $this->trim_plugins_group_slug($gr);

		$toolset_groups = wpcf_admin_fields_get_groups();
		$toolset_fields = [];
		if (!empty($toolset_groups)) {
			foreach($toolset_groups as $group){
				if($group['slug'] == $trim_gr){
					$toolset_fields[] = wpcf_admin_fields_get_fields_by_group($group['id']);
				}
			}
		}

		$fields = array();
		if (!empty($toolset_fields)) {
			foreach ($toolset_fields as $field) {
				if (!empty($field)) {
					foreach ($field as $fd) {
						switch ($output) {
							case 'list':
								$fields[$fd['meta_key']] = $fd['name'];
								break;
							case 'type':
								$fields[$fd['meta_key']] = $fd['type'];
								break;
						}
					}
				}
			}
		}

		return $fields;
	}

	public function get_source_options()
	{

		return array_merge(
			[
				'thegem_cf' => __('Custom Fields (TheGem)', 'thegem'),
				'manual_input' => __('Manual Input', 'thegem'),
			],
			$this->get_acf_plugin_groups(),
			$this->get_toolset_plugin_groups()
		);
	}

	protected function set_repeater_controls($type, $attr = null)
	{
		$repeater = new Repeater();
		$show_controls = true;

		if ($type == 'post_type') {
			$options = $this->get_fields_by_post_type($attr);
			$default = !empty($options) ? array_keys($options)[0] : '';
			if (empty($options)) $show_controls = false;

			$repeater->add_control(
				'select_field',
				[
					'label' => __('Select Custom Field', 'thegem'),
					'type' => Controls_Manager::SELECT,
					'options' => $options,
					'default' => $default,
					'description' => __('Go to the <a href="' . get_site_url() . '/wp-admin/admin.php?page=thegem-theme-options#/single-pages" target="_blank">Theme Options -> Single Pages</a> to manage your custom fields.', 'thegem')
				]
			);
		}

		if ($type == 'manual_input') {
			$repeater->add_control(
				'select_field',
				[
					'label' => __('Specify Field`s Name', 'thegem'),
					'type' => Controls_Manager::TEXT,
					'description' => __('Use field`s name / meta key as set in custom fields settings', 'thegem'),
					'dynamic' => [
						'active' => true,
					],
				]
			);
		}

		if ($type == 'acf') {
			$options = $this->get_acf_plugin_fields_by_group($attr);
			$default = !empty($options) ? array_keys($options)[0] : '';

			$repeater->add_control(
				'select_field',
				[
					'label' => __('Select Custom Field', 'thegem'),
					'type' => Controls_Manager::SELECT,
					'options' => $options,
					'default' => $default,
					'description' => __('Go to the <a href="' . get_site_url() . '/wp-admin/edit.php?post_type=acf-field-group" target="_blank">ACF -> Field Groups</a> to manage your custom fields.', 'thegem'),
				]
			);
		}

		if ($type == 'toolset') {
			$options = $this->get_toolset_plugin_fields_by_group($attr);
			$default = !empty($options) ? array_keys($options)[0] : '';

			$repeater->add_control(
				'select_field',
				[
					'label' => __('Select Custom Field', 'thegem'),
					'type' => Controls_Manager::SELECT,
					'options' => $options,
					'default' => $default,
					'description' => __('Go to the <a href="' . get_site_url() . '/wp-admin/admin.php?page=types-custom-fields" target="_blank">Toolset -> Custom Fields</a> to manage your custom fields.', 'thegem'),
				]
			);
		}

		if ($show_controls) {
			if ($type == 'acf' || $type == 'toolset') {
				$repeater->add_control(
					'field_type',
					[
						'label' => __('Field Type', 'thegem'),
						'type' => Controls_Manager::SELECT,
						'options' => [
							'inherit' => __('Inherit', 'thegem'),
							'text' => __('Text', 'thegem'),
							'number' => __('Number', 'thegem'),
						],
						'default' => 'inherit',
					]
				);
			} else {
				$repeater->add_control(
					'field_type',
					[
						'label' => __('Field Type', 'thegem'),
						'type' => Controls_Manager::SELECT,
						'options' => [
							'text' => __('Text', 'thegem'),
							'number' => __('Number', 'thegem'),
						],
						'default' => 'text',
					]
				);
			}

			$repeater->add_control(
				'field_format',
				[
					'label' => __('Number Format', 'thegem'),
					'type' => Controls_Manager::SELECT,
					'options' => [
						'wp_locale' => __('WP Locale', 'thegem'),
						'' => __('Disabled', 'thegem'),
					],
					'default' => 'wp_locale',
					'condition' => [
						'field_type' => 'number',
					],
				]
			);

			$repeater->add_control(
				'field_prefix',
				[
					'label' => __('Prefix', 'thegem'),
					'type' => Controls_Manager::TEXT,
					'default' => '',
					'condition' => [
						'field_type' => 'number',
					],
					'dynamic' => [
						'active' => true,
					],
				]
			);

			$repeater->add_control(
				'field_suffix',
				[
					'label' => __('Suffix', 'thegem'),
					'type' => Controls_Manager::TEXT,
					'default' => '',
					'condition' => [
						'field_type' => 'number',
					],
					'dynamic' => [
						'active' => true,
					],
				]
			);

			$repeater->add_control(
				'icon',
				[
					'label' => __('Icon', 'thegem'),
					'type' => Controls_Manager::SELECT,
					'options' => [
						'' => __('None', 'thegem'),
						'custom' => __('Custom', 'thegem'),
					],
					'default' => '',
				]
			);

			$repeater->add_control(
				'icon_select',
				[
					'label' => __('Select Icon', 'thegem'),
					'type' => Controls_Manager::ICONS,
					'condition' => [
						'icon' => 'custom',
					],
				]
			);

			$repeater->add_control(
				'label',
				[
					'label' => __('Label', 'thegem'),
					'type' => Controls_Manager::SWITCHER,
					'default' => 'yes',
					'return_value' => 'yes',
					'label_on' => __('On', 'thegem'),
					'label_off' => __('Off', 'thegem'),
				]
			);

			$repeater->add_control(
				'label_text',
				[
					'label' => __('Label Text', 'thegem'),
					'type' => Controls_Manager::TEXT,
					'default' => '',
					'condition' => [
						'label' => 'yes',
					],
					'dynamic' => [
						'active' => true,
					],
				]
			);

			$repeater->add_control(
				'link',
				[
					'label' => __('Link', 'thegem'),
					'type' => Controls_Manager::SWITCHER,
					'default' => '',
					'label_on' => __('On', 'thegem'),
					'label_off' => __('Off', 'thegem'),
				]
			);

			$repeater->add_control(
				'field_link',
				[
					'label' => __('Link Type', 'thegem'),
					'type' => Controls_Manager::URL,
					'default' => [
						'url' => '#'
					],
					'condition' => [
						'link' => 'yes',
					],
					'placeholder' => __('https://your-link.com', 'thegem'),
					'show_external' => true,
					'dynamic' => [
						'active' => true,
					],
				]
			);
		}

		return $repeater->get_controls();
	}

	/**
	 * Register the widget controls.
	 *
	 * @access protected
	 */
	protected function register_controls()
	{
		// General Section
		$this->start_controls_section(
			'section_general',
			[
				'label' => __('General', 'thegem'),
			]
		);

		$this->add_control(
			'source',
			[
				'label' => __('Source', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => $this->get_source_options(),
				'default' => 'thegem_cf',
				'description' => __('Choose between TheGem`s custom fields, ACF/Toolset field group or manual input of custom field name.', 'thegem'),
			]
		);

		$this->add_control(
			'post_type',
			[
				'label' => __('Post Type', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => $this->get_post_types(),
				'default' => $this->get_edit_template_type(),
				'condition' => [
					'source' => 'thegem_cf',
				],
			]
		);

		$this->add_control(
			'skin',
			[
				'label' => __('Skin', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'modern' => __('Modern', 'thegem'),
					'table' => __('Table', 'thegem'),
				],
				'default' => $this->is_loop_builder_template ? 'table' : 'modern',
			]
		);

		$this->add_control(
			'layout',
			[
				'label' => __('Layout', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'horizontal' => __('Horizontal', 'thegem'),
					'vertical' => __('Vertical', 'thegem'),
				],
				'default' => 'horizontal',
				'condition' => [
					'skin' => ['modern'],
				],
			]
		);

		// Custom Fields (TheGem) Repeater
		if (!empty($this->get_post_types())) {
			foreach ($this->get_post_types() as $pt => $label) {
				$this->add_control(
					$pt . '_info_content',
					[
						'label' => __('Items', 'thegem'),
						'type' => Controls_Manager::REPEATER,
						'show_label' => false,
						'fields' => $this->set_repeater_controls('post_type', $pt),
						'default' => [
							[
								'title' => 'Item #1',
								'source' => 'editor',
							],
						],
						'condition' => [
							'source' => 'thegem_cf',
							'post_type' => $pt,
						],
					]
				);
			}
		}

		// Manual Inputs Repeater
		$this->add_control(
			'info_content',
			[
				'label' => __('Items', 'thegem'),
				'type' => Controls_Manager::REPEATER,
				'show_label' => false,
				'fields' => $this->set_repeater_controls('manual_input'),
				'default' => [
					[
						'title' => 'Item #1',
						'source' => 'editor',
					],
				],
				'condition' => [
					'source' => 'manual_input',
				],
			]
		);

		// ACF Group Fields Repeater
		if ((thegem_is_plugin_active('advanced-custom-fields/acf.php') || thegem_is_plugin_active('advanced-custom-fields-pro/acf.php')) && !empty($this->get_acf_plugin_groups())) {
			foreach ($this->get_acf_plugin_groups() as $gr => $label) {
				$this->add_control(
					$gr . '_info_content',
					[
						'label' => __('Items', 'thegem'),
						'type' => Controls_Manager::REPEATER,
						'show_label' => false,
						'fields' => $this->set_repeater_controls('acf', $gr),
						'default' => [
							[
								'title' => 'Item #1',
								'source' => 'editor',
							],
						],
						'condition' => [
							'source' => $gr,
						],
					]
				);
			}
		}

		// Toolset Group Fields Repeater
		if ((thegem_is_plugin_active('types/wpcf.php')) && !empty($this->get_toolset_plugin_groups())) {
			foreach ($this->get_toolset_plugin_groups() as $gr => $label) {
				$this->add_control(
					$gr . '_info_content',
					[
						'label' => __('Items', 'thegem'),
						'type' => Controls_Manager::REPEATER,
						'show_label' => false,
						'fields' => $this->set_repeater_controls('toolset', $gr),
						'default' => [
							[
								'title' => 'Item #1',
								'source' => 'editor',
							],
						],
						'condition' => [
							'source' => $gr,
						],
					]
				);
			}
		}

		$this->end_controls_section();

		// List Section Style
		$this->start_controls_section(
			'section_list_style',
			[
				'label' => __('List', 'thegem'),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'list_alignment',
			[
				'label' => __('Alignment', 'thegem'),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => __('Left', 'thegem'),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __('Center', 'thegem'),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => __('Right', 'thegem'),
						'icon' => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => __('Justified', 'thegem'),
						'icon' => 'eicon-text-align-justify',
					],
				],
				'default' => $this->is_loop_builder_template ? 'justify' : 'left',
			]
		);

		$this->add_control(
			'list_divider',
			[
				'label' => __('Divider', 'thegem'),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'return_value' => 'yes',
				'label_on' => __('On', 'thegem'),
				'label_off' => __('Off', 'thegem'),
				'condition' => [
					'skin' => ['classic', 'table'],
				],
			]
		);

		$this->add_control(
			'list_divider_color',
			[
				'label' => __('Divider Color', 'thegem'),
				'type' => Controls_Manager::COLOR,
				'label_block' => false,
				'condition' => [
					'skin' => ['classic', 'table'],
					'list_divider' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}}'.$this->get_customize_class().' .custom-fields-item:not(:last-child):after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'list_spacing_horizontal',
			[
				'label' => __('Space Between', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'condition' => [
					'skin' => ['modern'],
					'layout' => 'horizontal',
				],
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item' => 'margin-right: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'list_spacing_vertical',
			[
				'label' => __('Space Between', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'condition' => [
					'skin' => ['modern'],
					'layout' => 'vertical',
				],
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'table_vertical_spacing',
			[
				'label' => __('Vertical Spacing', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'condition' => [
					'skin' => ['table'],
				],
				'selectors' => [
					'{{WRAPPER}}'.$this->get_customize_class().' .custom-fields-item .item-label' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}'.$this->get_customize_class().' .custom-fields-item .item-value' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'table_horizontal_spacing',
			[
				'label' => __('Horizontal Spacing', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'condition' => [
					'skin' => ['table'],
				],
				'selectors' => [
					'{{WRAPPER}}'.$this->get_customize_class().' .custom-fields-item .item-label' => 'padding-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Icon Section Style
		$this->start_controls_section(
			'section_icon_style',
			[
				'label' => __('Icon', 'thegem'),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label' => __('Size', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .icon i' => 'font-size: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_spacing',
			[
				'label' => __('Spacing', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .icon' => 'margin-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_spacing_vertical',
			[
				'label' => __('Vertical Spacing', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}}'.$this->get_customize_class().' .custom-fields-item .icon' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label' => __('Color', 'thegem'),
				'type' => Controls_Manager::COLOR,
				'label_block' => false,
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// Text Section Style
		$this->start_controls_section(
			'section_text_style',
			[
				'label' => __('Text', 'thegem'),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'text_layout',
			[
				'label' => __('Text Layout', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'inline' => __('Inline', 'thegem'),
					'vertical' => __('Vertical', 'thegem'),
				],
				'default' => 'inline',
			]
		);

		$this->add_control(
			'title_heading',
			[
				'label' => __('Label', 'thegem'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'label_text_spacing',
			[
				'label' => __('Label Spacing', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'condition' => [
					'skin' => ['classic', 'modern'],
				],
				'selectors' => [
					'{{WRAPPER}}'.$this->get_customize_class().' .custom-fields-item .item-label' => 'padding-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'label_text_style',
			[
				'label' => __('Text Style', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => __('Default', 'thegem'),
					'title-h1' => __('Title H1', 'thegem'),
					'title-h2' => __('Title H2', 'thegem'),
					'title-h3' => __('Title H3', 'thegem'),
					'title-h4' => __('Title H4', 'thegem'),
					'title-h5' => __('Title H5', 'thegem'),
					'title-h6' => __('Title H6', 'thegem'),
					'title-xlarge' => __('Title xLarge', 'thegem'),
					'styled-subtitle' => __('Styled Subtitle', 'thegem'),
					'title-main-menu' => __('Main Menu', 'thegem'),
					'title-text-body' => __('Body', 'thegem'),
					'title-text-body-tiny' => __('Tiny Body', 'thegem'),
				],
				'default' => $this->is_loop_builder_template ? 'title-text-body-tiny' : '',
			]
		);

		$this->add_control(
			'label_text_font_weight',
			[
				'label' => __('Font weight', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => __('Default', 'thegem'),
					'light' => __('Thin', 'thegem'),
				],
				'default' => '',
			]
		);

		$this->add_control(
			'label_text_letter_spacing',
			[
				'label' => __('Letter Spacing', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .item-label' => 'letter-spacing: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'label_text_transform',
			[
				'label' => __('Text Transform', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'default' => __('Default', 'thegem'),
					'none' => __('None', 'thegem'),
					'capitalize' => __('Capitalize', 'thegem'),
					'lowercase' => __('Lowercase', 'thegem'),
					'uppercase' => __('Uppercase', 'thegem'),
				],
				'default' => 'default',
				'selectors_dictionary' => [
					'default' => '',
					'none' => 'text-transform: none;',
					'capitalize' => 'text-transform: capitalize;',
					'lowercase' => 'text-transform: lowercase;',
					'uppercase' => 'text-transform: uppercase;',
				],
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .item-label' => '{{VALUE}};',
				],
			]
		);

		$this->add_control(
			'label_text_color',
			[
				'label' => __('Text Color', 'thegem'),
				'type' => Controls_Manager::COLOR,
				'label_block' => false,
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .item-label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'label_text_typography',
				'selector' => '{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .item-label',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'label_text_shadow',
				'selector' => '{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .item-label',
			]
		);

		$this->add_control(
			'label_colon',
			[
				'label' => __('Colon', 'thegem'),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'return_value' => 'yes',
				'label_on' => __('On', 'thegem'),
				'label_off' => __('Off', 'thegem'),
			]
		);

		$this->add_control(
			'value_title_heading',
			[
				'label' => __('Value', 'thegem'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'value_text_spacing',
			[
				'label' => __('Value Spacing', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'condition' => [
					'skin' => ['classic', 'modern'],
				],
				'selectors' => [
					'{{WRAPPER}}'.$this->get_customize_class().' .custom-fields-item .item-value' => 'padding-left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'value_text_style',
			[
				'label' => __('Text Style', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => __('Default', 'thegem'),
					'title-h1' => __('Title H1', 'thegem'),
					'title-h2' => __('Title H2', 'thegem'),
					'title-h3' => __('Title H3', 'thegem'),
					'title-h4' => __('Title H4', 'thegem'),
					'title-h5' => __('Title H5', 'thegem'),
					'title-h6' => __('Title H6', 'thegem'),
					'title-xlarge' => __('Title xLarge', 'thegem'),
					'styled-subtitle' => __('Styled Subtitle', 'thegem'),
					'title-main-menu' => __('Main Menu', 'thegem'),
					'title-text-body' => __('Body', 'thegem'),
					'title-text-body-tiny' => __('Tiny Body', 'thegem'),
				],
				'default' => $this->is_loop_builder_template ? 'title-text-body-tiny' : '',
			]
		);

		$this->add_control(
			'value_text_font_weight',
			[
				'label' => __('Font weight', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => __('Default', 'thegem'),
					'light' => __('Thin', 'thegem'),
				],
				'default' => '',
			]
		);

		$this->add_control(
			'value_text_letter_spacing',
			[
				'label' => __('Letter Spacing', 'thegem'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'em'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'rem' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .item-value' => 'letter-spacing: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .label-after' => 'letter-spacing: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'value_text_transform',
			[
				'label' => __('Text Transform', 'thegem'),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'default' => __('Default', 'thegem'),
					'none' => __('None', 'thegem'),
					'capitalize' => __('Capitalize', 'thegem'),
					'lowercase' => __('Lowercase', 'thegem'),
					'uppercase' => __('Uppercase', 'thegem'),
				],
				'default' => 'default',
				'selectors_dictionary' => [
					'default' => '',
					'none' => 'text-transform: none;',
					'capitalize' => 'text-transform: capitalize;',
					'lowercase' => 'text-transform: lowercase;',
					'uppercase' => 'text-transform: uppercase;',
				],
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .item-value' => '{{VALUE}};',
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .label-after' => '{{VALUE}};',
				],
			]
		);

		$this->add_control(
			'value_text_color',
			[
				'label' => __('Text Color', 'thegem'),
				'type' => Controls_Manager::COLOR,
				'label_block' => false,
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .item-value' => 'color: {{VALUE}};',
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item a' => 'color: {{VALUE}};',
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .label-after' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'value_color_hover',
			[
				'label' => __('Text Color on Hover', 'thegem'),
				'type' => Controls_Manager::COLOR,
				'label_block' => false,
				'selectors' => [
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item a:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item a:hover .icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'value_text_typography',
				'selector' =>
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .item-value,
				    {{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .label-after',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'cats_text_shadow',
				'selector' =>
					'{{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .item-value,
                     {{WRAPPER}}' . $this->get_customize_class() . ' .custom-fields-item .label-after',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @access protected
	 */
	public function render()
	{
		$params = array_merge(array(), $this->get_settings_for_display());

		switch ($this->get_edit_template_type()) {
			case 'post':
				$single_post = thegem_templates_init_post();
				$post_id = $single_post->ID;
				break;
			case 'thegem_pf_item':
				$portfolio = thegem_templates_init_portfolio();
				$post_id = $portfolio->ID;
				break;
			case 'product':
				$product = thegem_templates_init_product();
				$post_id = $product->get_id();
				break;
			default:
				$post_id = get_the_ID();
		}

		$info_content = $meta_data = $meta_types = array();

		if (!empty($params['source']) && $params['source'] == 'thegem_cf') {
			$post_type = $params['post_type'];
			$info_content = $params[$post_type . '_info_content'];
			$meta_data = $this->get_fields_by_post_type($post_type);
		}

		if (!empty($params['source']) && $params['source'] == 'manual_input') {
			$info_content = $params['info_content'];
		}

		$plugin_type = $this->check_plugins_group($params['source']);

		if ((thegem_is_plugin_active('advanced-custom-fields/acf.php') || thegem_is_plugin_active('advanced-custom-fields-pro/acf.php')) && !empty($params['source']) && $plugin_type == 'acf') {
			$source = $params['source'];
			$info_content = $params[$source . '_info_content'];
			$meta_data = $this->get_acf_plugin_fields_by_group($source);
		}

		if (thegem_is_plugin_active('types/wpcf.php') && !empty($params['source']) && $plugin_type == 'toolset') {
			$source = $params['source'];
			$info_content = $params[$source . '_info_content'];
			$meta_data = $this->get_toolset_plugin_fields_by_group($source);
			$meta_types = $this->get_toolset_plugin_fields_by_group($source, 'type');
		}

		$skin = 'custom-fields--' . $params['skin'];
		$layout = 'custom-fields--' . $params['layout'];
		$alignment = 'custom-fields--' . $params['list_alignment'];
		$divider = !empty($params['list_divider']) ? 'custom-fields--divider-show' : 'custom-fields--divider-hide';
		$colon = !empty($params['label_colon']) ? 'custom-fields--colon-show' : 'custom-fields--colon-hide';
		$text_layout = 'custom-fields--text-' . $params['text_layout'];
		$params['element_class'] = implode(' ', array(
			$this->get_widget_wrapper(),
			$skin,
			$layout,
			$alignment,
			$divider,
			$colon,
			$text_layout,
		));
		$label_text_styled = implode(' ', array($params['label_text_style'], $params['label_text_font_weight']));
		$value_text_styled = implode(' ', array($params['value_text_style'], $params['value_text_font_weight']));

		?>

		<div class="<?= esc_attr($params['element_class']); ?>">
			<div class="custom-fields">
				<?php foreach ($info_content as $item): ?>
					<?php
					$label_output = $value_output = '';
					$meta_value = !empty($item['select_field']) ? get_post_meta($post_id, $item['select_field'], true) : '';
					$meta_label = (!empty($meta_data) && !empty($item['select_field'])) ? $meta_data[$item['select_field']] : '';

					if (!empty($meta_value)) {
						if (!empty($item['icon']) && $item['icon'] == 'custom' && !empty($item['icon_select']['value'])) {
							ob_start();
							Icons_Manager::render_icon($item['icon_select'], ['aria-hidden' => 'true']);
							$label_output .= '<div class="icon">' . ob_get_clean() . '</div>';
						}

						if (!empty($item['label'])) {
							$label_text = !empty($item['label_text']) ? $item['label_text'] : $meta_label;
							$label_output .= '<div class="label-before">' . esc_html($label_text) . '<span class="colon">:</span></div>';
						}
						$label_output = '<div class="item-label ' . $label_text_styled . '">' . $label_output . '</div>';

						if (!empty($item['field_type']) && !empty($item['select_field'])){
							// ACF / Toolset inherit type fields
							if ($item['field_type'] == 'inherit'){
								if ($plugin_type == 'acf') {
									$field_type = get_field_object($item['select_field'])['type'];
								}

								if($plugin_type == 'toolset') {
									$field_type = $meta_types[$item['select_field']];
								}

								switch ($field_type) {
									case 'image':
										$id = attachment_url_to_postid( $meta_value );
										if (!empty($id)) {
											$image = wp_get_attachment_image($id, 'full', false, []);
										} else {
											$image = wp_get_attachment_image($meta_value, 'full', false, []);
										}

										$value_output = !empty($image) ? '<div class="meta">' . $image . '</div>' : '';
										break;
									case 'file':
										$file = wp_get_attachment_url($meta_value);
										$value_output = !empty($file) ? '<div class="meta">' . $file . '</div>' : '';
										break;
									case 'oembed':
										$width = get_field_object($item['select_field'])['width'];
										$styles = !empty($width) ? 'style="width: ' . $width . 'px; max-width: 100%; display: table-cell;"' : '';

										ob_start();
										echo get_field_object($item['select_field'])['value'];
										$value_output = '<div class="meta" '.$styles.'>' . ob_get_clean() . '</div>';
										break;
									case 'embed' :
										global $wp_embed;

										ob_start();
										echo $wp_embed->run_shortcode('[embed]' . $meta_value . '[/embed]');
										$value_output = '<div class="meta">' . ob_get_clean() . '</div>';
										break;
									case 'video' :
										ob_start();
										echo wp_video_shortcode( ['src' => $meta_value] );
										$value_output = '<div class="meta">' . ob_get_clean() . '</div>';
										break;
									case 'checkbox' :
										$checkbox = is_array($meta_value) && !empty($meta_value) ? implode(', ', $meta_value) : $meta_value;
										$value_output = !empty($checkbox) ? '<div class="meta">' . $checkbox . '</div>' : '';
										break;
									case 'checkboxes' :
										$checkboxes_list = [];
										if (is_array($meta_value)) {
											foreach ($meta_value as $key => $values) {
												foreach ($values as $value) {
													$checkboxes_list[] = $value;
												}
											}
										}
										$checkboxes = is_array($checkboxes_list) && !empty($checkboxes_list) ? implode(', ', $checkboxes_list) : '';
										$value_output = !empty($checkboxes) ? '<div class="meta">' . $checkboxes . '</div>' : '';
										break;
									default:
										$value_output = !is_array($meta_value) ? '<div class="meta">' . $meta_value . '</div>' : '';
								}
							} elseif ($item['field_type'] == 'number') {
								$meta_value = floatval($meta_value);
								$decimal = explode('.', $meta_value);
								$decimal = isset($decimal[1]) ? strlen(($decimal[1])) : 0;
								$decimal = $decimal <= 3 ? $decimal : 3;

								if (!empty($item['field_format']) && $item['field_format'] == 'wp_locale') {
									$meta_value = number_format_i18n($meta_value, $decimal);
								}

								if (!empty($item['field_prefix'])) {
									$meta_value = $item['field_prefix'] . '' . $meta_value;
								}

								if (!empty($item['field_suffix'])) {
									$meta_value = $meta_value . '' . $item['field_suffix'];
								}

								$value_output = '<div class="meta">' . $meta_value . '</div>';
							} else {
								$value_output = !is_array($meta_value) ? '<div class="meta">' . $meta_value . '</div>' : '';
							}
						}

						if (!empty($item['link'])) {
							if (!empty($item['field_link']['url'])) {
								$this->add_link_attributes('field_link', $item['field_link']);
							}

							$value_output = '<a ' . $this->get_render_attribute_string('field_link') . '>' . $value_output . '</a>';
						}

						$value_output = '<div class="item-value ' . $value_text_styled . '">' . $value_output . '</div>';

						echo '<div class="custom-fields-item">' . $label_output . ' ' . $value_output . '</div>';
					}
					?>
				<?php endforeach; ?>
			</div>
		</div>

		<?php
	}
}

Plugin::instance()->widgets_manager->register(new TheGem_Custom_Fields());
